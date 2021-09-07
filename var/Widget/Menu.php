<?php

namespace Widget;

use Typecho\Common;
use Widget\Plugins\Config;
use Widget\Themes\Files;
use Widget\Users\Edit as UsersEdit;
use Widget\Contents\Attachment\Edit as AttachmentEdit;
use Widget\Contents\Post\Edit as PostEdit;
use Widget\Contents\Page\Edit as PageEdit;
use Widget\Contents\Post\Admin as PostAdmin;
use Widget\Comments\Admin as CommentsAdmin;
use Widget\Metas\Category\Admin as CategoryAdmin;
use Widget\Metas\Category\Edit as CategoryEdit;
use Widget\Metas\Tag\Admin as TagAdmin;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 后台菜单显示
 *
 * @package Widget
 */
class Menu extends Base
{
    /**
     * 当前菜单标题
     * @var string
     */
    public $title;

    /**
     * 当前增加项目链接
     * @var string
     */
    public $addLink;

    /**
     * 父菜单列表
     *
     * @var array
     */
    private $menu = [];

    /**
     * 当前父菜单
     *
     * @var integer
     */
    private $currentParent = 1;

    /**
     * 当前子菜单
     *
     * @var integer
     */
    private $currentChild = 0;

    /**
     * 当前页面
     *
     * @var string
     */
    private $currentUrl;

    /**
     * 执行函数,初始化菜单
     */
    public function execute()
    {
        $parentNodes = [null, _t('控制台'), _t('撰写'), _t('管理'), _t('设置')];

        $childNodes = [
            [
                [_t('登录'), _t('登录到%s', $this->options->title), 'login.php', 'visitor'],
                [_t('注册'), _t('注册到%s', $this->options->title), 'register.php', 'visitor']
            ],
            [
                [_t('概要'), _t('网站概要'), 'index.php', 'subscriber'],
                [_t('个人设置'), _t('个人设置'), 'profile.php', 'subscriber'],
                [_t('插件'), _t('插件管理'), 'plugins.php', 'administrator'],
                [[Config::class, 'getMenuTitle'], [Config::class, 'getMenuTitle'], 'options-plugin.php?config=', 'administrator', true],
                [_t('外观'), _t('网站外观'), 'themes.php', 'administrator'],
                [[Files::class, 'getMenuTitle'], [Files::class, 'getMenuTitle'], 'theme-editor.php', 'administrator', true],
                [_t('设置外观'), _t('设置外观'), 'options-theme.php', 'administrator', true],
                [_t('备份'), _t('备份'), 'backup.php', 'administrator'],
                [_t('升级'), _t('升级程序'), 'upgrade.php', 'administrator', true],
                [_t('欢迎'), _t('欢迎使用'), 'welcome.php', 'subscriber', true]
            ],
            [
                [_t('撰写文章'), _t('撰写新文章'), 'write-post.php', 'contributor'],
                [[PostEdit::class, 'getMenuTitle'], [PostEdit::class, 'getMenuTitle'], 'write-post.php?cid=', 'contributor', true],
                [_t('创建页面'), _t('创建新页面'), 'write-page.php', 'editor'],
                [[PageEdit::class, 'getMenuTitle'], [PageEdit::class, 'getMenuTitle'], 'write-page.php?cid=', 'editor', true],
            ],
            [
                [_t('文章'), _t('管理文章'), 'manage-posts.php', 'contributor', false, 'write-post.php'],
                [[PostAdmin::class, 'getMenuTitle'], [PostAdmin::class, 'getMenuTitle'], 'manage-posts.php?uid=', 'contributor', true],
                [_t('独立页面'), _t('管理独立页面'), 'manage-pages.php', 'editor', false, 'write-page.php'],
                [_t('评论'), _t('管理评论'), 'manage-comments.php', 'contributor'],
                [[CommentsAdmin::class, 'getMenuTitle'], [CommentsAdmin::class, 'getMenuTitle'], 'manage-comments.php?cid=', 'contributor', true],
                [_t('分类'), _t('管理分类'), 'manage-categories.php', 'editor', false, 'category.php'],
                [_t('新增分类'), _t('新增分类'), 'category.php', 'editor', true],
                [[CategoryAdmin::class, 'getMenuTitle'], [CategoryAdmin::class, 'getMenuTitle'], 'manage-categories.php?parent=', 'editor', true, [CategoryAdmin::class, 'getAddLink']],
                [[CategoryEdit::class, 'getMenuTitle'], [CategoryEdit::class, 'getMenuTitle'], 'category.php?mid=', 'editor', true],
                [[CategoryEdit::class, 'getMenuTitle'], [CategoryEdit::class, 'getMenuTitle'], 'category.php?parent=', 'editor', true],
                [_t('标签'), _t('管理标签'), 'manage-tags.php', 'editor'],
                [[TagAdmin::class, 'getMenuTitle'], [TagAdmin::class, 'getMenuTitle'], 'manage-tags.php?mid=', 'editor', true],
                [_t('文件'), _t('管理文件'), 'manage-medias.php', 'editor'],
                [[AttachmentEdit::class, 'getMenuTitle'], [AttachmentEdit::class, 'getMenuTitle'], 'media.php?cid=', 'contributor', true],
                [_t('用户'), _t('管理用户'), 'manage-users.php', 'administrator', false, 'user.php'],
                [_t('新增用户'), _t('新增用户'), 'user.php', 'administrator', true],
                [[UsersEdit::class, 'getMenuTitle'], [UsersEdit::class, 'getMenuTitle'], 'user.php?uid=', 'administrator', true],
            ],
            [
                [_t('基本'), _t('基本设置'), 'options-general.php', 'administrator'],
                [_t('评论'), _t('评论设置'), 'options-discussion.php', 'administrator'],
                [_t('阅读'), _t('阅读设置'), 'options-reading.php', 'administrator'],
                [_t('永久链接'), _t('永久链接设置'), 'options-permalink.php', 'administrator'],
            ]
        ];

        /** 获取扩展菜单 */
        $panelTable = unserialize($this->options->panelTable);
        $extendingParentMenu = empty($panelTable['parent']) ? [] : $panelTable['parent'];
        $extendingChildMenu = empty($panelTable['child']) ? [] : $panelTable['child'];
        $currentUrl = $this->request->makeUriByRequest();
        $adminUrl = $this->options->adminUrl;
        $menu = [];
        $defaultChildeNode = [null, null, null, 'administrator', false, null];

        $currentUrlParts = parse_url($currentUrl);
        $currentUrlParams = [];
        if (!empty($currentUrlParts['query'])) {
            parse_str($currentUrlParts['query'], $currentUrlParams);
        }

        if ('/' == $currentUrlParts['path'][strlen($currentUrlParts['path']) - 1]) {
            $currentUrlParts['path'] .= 'index.php';
        }

        foreach ($extendingParentMenu as $key => $val) {
            $parentNodes[10 + $key] = $val;
        }

        foreach ($extendingChildMenu as $key => $val) {
            $childNodes[$key] = array_merge($childNodes[$key] ?? [], $val);
        }

        foreach ($parentNodes as $key => $parentNode) {
            // this is a simple struct than before
            $children = [];
            $showedChildrenCount = 0;
            $firstUrl = null;

            foreach ($childNodes[$key] as $inKey => $childNode) {
                // magic merge
                $childNode += $defaultChildeNode;
                [$name, $title, $url, $access] = $childNode;

                $hidden = $childNode[4] ?? false;
                $addLink = $childNode[5] ?? null;

                // 保存最原始的hidden信息
                $orgHidden = $hidden;

                // parse url
                $url = Common::url($url, $adminUrl);

                // compare url
                $urlParts = parse_url($url);
                $urlParams = [];
                if (!empty($urlParts['query'])) {
                    parse_str($urlParts['query'], $urlParams);
                }

                $validate = true;
                if ($urlParts['path'] != $currentUrlParts['path']) {
                    $validate = false;
                } else {
                    foreach ($urlParams as $paramName => $paramValue) {
                        if (!isset($currentUrlParams[$paramName])) {
                            $validate = false;
                            break;
                        }
                    }
                }

                if (
                    $validate
                    && basename($urlParts['path']) == 'extending.php'
                    && !empty($currentUrlParams['panel']) && !empty($urlParams['panel'])
                    && $urlParams['panel'] != $currentUrlParams['panel']
                ) {
                    $validate = false;
                }

                if ($hidden && $validate) {
                    $hidden = false;
                }

                if (!$hidden && !$this->user->pass($access, true)) {
                    $hidden = true;
                }

                if (!$hidden) {
                    $showedChildrenCount++;

                    if (empty($firstUrl)) {
                        $firstUrl = $url;
                    }

                    if (is_array($name)) {
                        [$widget, $method] = $name;
                        $name = self::widget($widget)->$method();
                    }

                    if (is_array($title)) {
                        [$widget, $method] = $title;
                        $title = self::widget($widget)->$method();
                    }

                    if (is_array($addLink)) {
                        [$widget, $method] = $addLink;
                        $addLink = self::widget($widget)->$method();
                    }
                }

                if ($validate) {
                    if ('visitor' != $access) {
                        $this->user->pass($access);
                    }

                    $this->currentParent = $key;
                    $this->currentChild = $inKey;
                    $this->title = $title;
                    $this->addLink = $addLink ? Common::url($addLink, $adminUrl) : null;
                }

                $children[$inKey] = [
                    $name,
                    $title,
                    $url,
                    $access,
                    $hidden,
                    $addLink,
                    $orgHidden
                ];
            }

            $menu[$key] = [$parentNode, $showedChildrenCount > 0, $firstUrl, $children];
        }

        $this->menu = $menu;
        $this->currentUrl = $currentUrl;
    }

    /**
     * 获取当前菜单
     *
     * @return array
     */
    public function getCurrentMenu(): ?array
    {
        return $this->currentParent > 0 ? $this->menu[$this->currentParent][3][$this->currentChild] : null;
    }

    /**
     * 输出父级菜单
     */
    public function output($class = 'focus', $childClass = 'focus')
    {
        foreach ($this->menu as $key => $node) {
            if (!$node[1] || !$key) {
                continue;
            }

            echo "<ul class=\"root" . ($key == $this->currentParent ? ' ' . $class : null)
                . "\"><li class=\"parent\"><a href=\"{$node[2]}\">{$node[0]}</a>"
                . "</li><ul class=\"child\">";

            $last = 0;
            foreach ($node[3] as $inKey => $inNode) {
                if (!$inNode[4]) {
                    $last = $inKey;
                }
            }

            foreach ($node[3] as $inKey => $inNode) {
                if ($inNode[4]) {
                    continue;
                }

                $classes = [];
                if ($key == $this->currentParent && $inKey == $this->currentChild) {
                    $classes[] = $childClass;
                } elseif ($inNode[6]) {
                    continue;
                }

                if ($inKey == $last) {
                    $classes[] = 'last';
                }

                echo "<li" . (!empty($classes) ? ' class="' . implode(' ', $classes) . '"' : null) . "><a href=\""
                    . ($key == $this->currentParent && $inKey == $this->currentChild ? $this->currentUrl : $inNode[2])
                    . "\">{$inNode[0]}</a></li>";
            }

            echo "</ul></ul>";
        }
    }
}
