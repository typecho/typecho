<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 后台菜单显示
 *
 * @package Widget
 */
class Widget_Menu extends Typecho_Widget
{
    /**
     * 父菜单列表
     *
     * @access private
     * @var array
     */
    private $_menu = array();

    /**
     * 当前父菜单
     *
     * @access private
     * @var integer
     */
    private $_currentParent = 1;

    /**
     * 当前子菜单
     *
     * @access private
     * @var integer
     */
    private $_currentChild = 0;

    /**
     * 当前页面
     *
     * @access private
     * @var string
     */
    private $_currentUrl;

    /**
     * 全局选项
     *
     * @access protected
     * @var Widget_Options
     */
    protected $options;

    /**
     * 用户对象
     *
     * @access protected
     * @var Widget_User
     */
    protected $user;

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
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     * @return void
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /** 初始化常用组件 */
        $this->options = $this->widget('Widget_Options');
        $this->user = $this->widget('Widget_User');
    }

    /**
     * 执行函数,初始化菜单
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $parentNodes = array(NULL, _t('控制台'), _t('撰写'), _t('管理'), _t('设置'));

        $childNodes =  array(
        array(
            array(_t('登录'), _t('登录到%s', $this->options->title), 'login.php', 'visitor'),
            array(_t('注册'), _t('注册到%s', $this->options->title), 'register.php', 'visitor')
        ),
        array(
            array(_t('概要'), _t('网站概要'), 'index.php', 'subscriber'),
            array(_t('个人设置'), _t('个人设置'), 'profile.php', 'subscriber'),
            array(_t('插件'), _t('插件管理'), 'plugins.php', 'administrator'),
            array(array('Widget_Plugins_Config', 'getMenuTitle'), array('Widget_Plugins_Config', 'getMenuTitle'), 'options-plugin.php?config=', 'administrator', true),
            array(_t('外观'), _t('网站外观'), 'themes.php', 'administrator'),
            array(array('Widget_Themes_Files', 'getMenuTitle'), array('Widget_Themes_Files', 'getMenuTitle'), 'theme-editor.php', 'administrator', true),
            array(_t('设置外观'), _t('设置外观'), 'options-theme.php', 'administrator', true),
            array(_t('升级'), _t('升级程序'), 'upgrade.php', 'administrator', true),
            array(_t('欢迎'), _t('欢迎使用'), 'welcome.php', 'subscriber', true)
        ),
        array(
            array(_t('撰写文章'), _t('撰写新文章'), 'write-post.php', 'contributor'),
            array(array('Widget_Contents_Post_Edit', 'getMenuTitle'), array('Widget_Contents_Post_Edit', 'getMenuTitle'), 'write-post.php?cid=', 'contributor', true),
            array(_t('创建页面'), _t('创建新页面'), 'write-page.php', 'editor'),
            array(array('Widget_Contents_Page_Edit', 'getMenuTitle'), array('Widget_Contents_Page_Edit', 'getMenuTitle'), 'write-page.php?cid=', 'editor', true),
        ),
        array(
            array(_t('文章'), _t('管理文章'), 'manage-posts.php', 'contributor', false, 'write-post.php'),
            array(array('Widget_Contents_Post_Admin', 'getMenuTitle'), array('Widget_Contents_Post_Admin', 'getMenuTitle'), 'manage-posts.php?uid=', 'contributor', true),
            array(_t('独立页面'), _t('管理独立页面'), 'manage-pages.php', 'editor', false, 'write-page.php'),
            array(_t('评论'), _t('管理评论'), 'manage-comments.php', 'contributor'),
            array(array('Widget_Comments_Admin', 'getMenuTitle'), array('Widget_Comments_Admin', 'getMenuTitle'), 'manage-comments.php?cid=', 'contributor', true),
            array(_t('分类'), _t('管理分类'), 'manage-categories.php', 'editor', false, 'category.php'),
            array(_t('新增分类'), _t('新增分类'), 'category.php', 'editor', true),
            array(array('Widget_Metas_Category_Admin', 'getMenuTitle'), array('Widget_Metas_Category_Admin', 'getMenuTitle'), 'manage-categories.php?parent=', 'editor', true, array('Widget_Metas_Category_Admin', 'getAddLink')),
            array(array('Widget_Metas_Category_Edit', 'getMenuTitle'), array('Widget_Metas_Category_Edit', 'getMenuTitle'), 'category.php?mid=', 'editor', true),
            array(array('Widget_Metas_Category_Edit', 'getMenuTitle'), array('Widget_Metas_Category_Edit', 'getMenuTitle'), 'category.php?parent=', 'editor', true),
            array(_t('标签'), _t('管理标签'), 'manage-tags.php', 'editor'),
            array(array('Widget_Metas_Tag_Admin', 'getMenuTitle'), array('Widget_Metas_Tag_Admin', 'getMenuTitle'), 'manage-tags.php?mid=', 'editor', true),
            array(_t('文件'), _t('管理文件'), 'manage-medias.php', 'editor'),
            array(array('Widget_Contents_Attachment_Edit', 'getMenuTitle'), array('Widget_Contents_Attachment_Edit', 'getMenuTitle'), 'media.php?cid=', 'contributor', true),
            array(_t('用户'), _t('管理用户'), 'manage-users.php', 'administrator', false, 'user.php'),
            array(_t('新增用户'), _t('新增用户'), 'user.php', 'administrator', true),
            array(array('Widget_Users_Edit', 'getMenuTitle'), array('Widget_Users_Edit', 'getMenuTitle'), 'user.php?uid=', 'administrator', true),
        ),
        array(
            array(_t('基本'), _t('基本设置'), 'options-general.php', 'administrator'),
            array(_t('评论'), _t('评论设置'), 'options-discussion.php', 'administrator'),
            array(_t('阅读'), _t('阅读设置'), 'options-reading.php', 'administrator'),
            array(_t('永久链接'), _t('永久链接设置'), 'options-permalink.php', 'administrator'),
        ));

        /** 获取扩展菜单 */
        $panelTable = unserialize($this->options->panelTable);
        $extendingParentMenu = empty($panelTable['parent']) ? array() : $panelTable['parent'];
        $extendingChildMenu = empty($panelTable['child']) ? array() : $panelTable['child'];
        $currentUrl = $this->request->makeUriByRequest();
        $adminUrl = $this->options->adminUrl;
        $menu = array();
        $defaultChildeNode = array(NULL, NULL, NULL, 'administrator', false, NULL);

        $currentUrlParts = parse_url($currentUrl);
        $currentUrlParams = array();
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
            $childNodes[$key] = array_merge(isset($childNodes[$key]) ? $childNodes[$key] : array(), $val);
        }

        foreach ($parentNodes as $key => $parentNode) {
            // this is a simple struct than before
            $children = array();
            $showedChildrenCount = 0;
            $firstUrl = NULL;
            
            foreach ($childNodes[$key] as $inKey => $childNode) {
                // magic merge
                $childNode += $defaultChildeNode;
                list ($name, $title, $url, $access, $hidden, $addLink) = $childNode;

                // 保存最原始的hidden信息
                $orgHidden = $hidden;

                // parse url
                $url = Typecho_Common::url($url, $adminUrl);

                // compare url
                $urlParts = parse_url($url);
                $urlParams = array();
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
                
                if ($validate
                    && basename($urlParts['path']) == 'extending.php'
                    && !empty($currentUrlParams['panel']) && !empty($urlParams['panel'])
                    && $urlParams['panel'] != $currentUrlParams['panel']){
                    $validate = false;
                }
                
                if ($hidden && $validate) {
                    $hidden = false;
                }

                if (!$hidden && !$this->user->pass($access, true)) {
                    $hidden = true;
                }

                if (!$hidden) {
                    $showedChildrenCount ++;

                    if (empty($firstUrl)) {
                        $firstUrl = $url;
                    }

                    if (is_array($name)) {
                        list($widget, $method) = $name;
                        $name = Typecho_Widget::widget($widget)->$method();
                    }
                    
                    if (is_array($title)) {
                        list($widget, $method) = $title;
                        $title = Typecho_Widget::widget($widget)->$method();
                    }

                    if (is_array($addLink)) {
                        list($widget, $method) = $addLink;
                        $addLink = Typecho_Widget::widget($widget)->$method();
                    }
                }

                if ($validate) {
                    if ('visitor' != $access) {
                        $this->user->pass($access);
                    }
                    
                    $this->_currentParent = $key;
                    $this->_currentChild = $inKey;
                    $this->title = $title;
                    $this->addLink = $addLink ? Typecho_Common::url($addLink, $adminUrl) : NULL;
                } 

                $children[$inKey] = array(
                    $name,
                    $title,
                    $url,
                    $access,
                    $hidden,
                    $addLink,
                    $orgHidden
                );
            }

            $menu[$key] = array($parentNode, $showedChildrenCount > 0, $firstUrl,$children);
        }

        $this->_menu = $menu;
        $this->_currentUrl = $currentUrl;
    }

    /**
     * 获取当前菜单
     *
     * @access public
     * @return array
     */
    public function getCurrentMenu()
    {
        return $this->_currentParent > 0 ? $this->_menu[$this->_currentParent][3][$this->_currentChild] : NULL;
    }

    /**
     * 输出父级菜单
     *
     * @access public
     * @return string
     */
    public function output($class = 'focus', $childClass = 'focus')
    {
        foreach ($this->_menu as $key => $node) {
            if (!$node[1] || !$key) {
                continue;
            }

            echo "<ul class=\"root" . ($key == $this->_currentParent ? ' ' . $class : NULL) 
                . "\"><li class=\"parent\"><a href=\"{$node[2]}\">{$node[0]}</a></dt>"
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

                $classes = array();
                if ($key == $this->_currentParent && $inKey == $this->_currentChild) {
                    $classes[] = $childClass;
                } else if ($inNode[6]) {
                    continue;
                }

                if ($inKey == $last) {
                    $classes[] = 'last';
                }

                echo "<li" . (!empty($classes) ? ' class="' . implode(' ', $classes) . '"' : NULL) .
                    "><a href=\"" . ($key == $this->_currentParent && $inKey == $this->_currentChild ? $this->_currentUrl : $inNode[2]) . "\">{$inNode[0]}</a></li>";
            }

            echo "</ul></ul>";
        }
    }
}

