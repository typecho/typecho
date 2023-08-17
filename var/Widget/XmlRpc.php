<?php

namespace Widget;

use IXR\Date;
use IXR\Error;
use IXR\Exception;
use IXR\Hook;
use IXR\Pingback;
use IXR\Server;
use ReflectionMethod;
use Typecho\Common;
use Typecho\Router;
use Typecho\Widget;
use Typecho\Widget\Exception as WidgetException;
use Widget\Base\Comments;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\Contents\Page\Admin as PageAdmin;
use Widget\Contents\Post\Admin as PostAdmin;
use Widget\Contents\Attachment\Admin as AttachmentAdmin;
use Widget\Contents\Post\Edit as PostEdit;
use Widget\Contents\Page\Edit as PageEdit;
use Widget\Contents\Attachment\Edit as AttachmentEdit;
use Widget\Metas\Category\Edit as CategoryEdit;
use Widget\Metas\Category\Rows as CategoryRows;
use Widget\Metas\Tag\Cloud;
use Widget\Comments\Edit as CommentsEdit;
use Widget\Comments\Admin as CommentsAdmin;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * XmlRpc接口
 *
 * @author blankyao
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class XmlRpc extends Contents implements ActionInterface, Hook
{
    /**
     * 当前错误
     *
     * @var Error
     */
    private $error;

    /**
     * wordpress风格的系统选项
     *
     * @access private
     * @var array
     */
    private $wpOptions;

    /**
     * 已经使用过的组件列表
     *
     * @access private
     * @var array
     */
    private $usedWidgetNameList = [];

    /**
     * 如果这里没有重载, 每次都会被默认执行
     *
     * @param bool $run 是否执行
     */
    public function execute(bool $run = false)
    {
        if ($run) {
            parent::execute();
        }

        // 临时保护模块
        $this->security->enable(false);

        $this->wpOptions = [
            // Read only options
            'software_name'    => [
                'desc'     => _t('软件名称'),
                'readonly' => true,
                'value'    => $this->options->software
            ],
            'software_version' => [
                'desc'     => _t('软件版本'),
                'readonly' => true,
                'value'    => $this->options->version
            ],
            'blog_url'         => [
                'desc'     => _t('博客地址'),
                'readonly' => true,
                'option'   => 'siteUrl'
            ],
            'home_url'         => [
                'desc'     => _t('博客首页地址'),
                'readonly' => true,
                'option'   => 'siteUrl'
            ],
            'login_url'        => [
                'desc'     => _t('登录地址'),
                'readonly' => true,
                'value'    => $this->options->siteUrl . 'admin/login.php'
            ],
            'admin_url'        => [
                'desc'     => _t('管理区域的地址'),
                'readonly' => true,
                'value'    => $this->options->siteUrl . 'admin/'
            ],

            'post_thumbnail'     => [
                'desc'     => _t('文章缩略图'),
                'readonly' => true,
                'value'    => false
            ],

            // Updatable options
            'time_zone'          => [
                'desc'     => _t('时区'),
                'readonly' => false,
                'option'   => 'timezone'
            ],
            'blog_title'         => [
                'desc'     => _t('博客标题'),
                'readonly' => false,
                'option'   => 'title'
            ],
            'blog_tagline'       => [
                'desc'     => _t('博客关键字'),
                'readonly' => false,
                'option'   => 'description'
            ],
            'date_format'        => [
                'desc'     => _t('日期格式'),
                'readonly' => false,
                'option'   => 'postDateFormat'
            ],
            'time_format'        => [
                'desc'     => _t('时间格式'),
                'readonly' => false,
                'option'   => 'postDateFormat'
            ],
            'users_can_register' => [
                'desc'     => _t('是否允许注册'),
                'readonly' => false,
                'option'   => 'allowRegister'
            ]
        ];
    }

    /**
     * 获取pageId指定的page
     * about wp xmlrpc api, you can see http://codex.wordpress.org/XML-RPC
     *
     * @param int $blogId
     * @param int $pageId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetPage(int $blogId, int $pageId, string $userName, string $password): array
    {
        /** 获取页面 */
        $page = PageEdit::alloc(null, ['cid' => $pageId], false);

        /** 对文章内容做截取处理，以获得description和text_more*/
        [$excerpt, $more] = $this->getPostExtended($page);

        return [
            'dateCreated'            => new Date($this->options->timezone + $page->created),
            'userid'                 => $page->authorId,
            'page_id'                => $page->cid,
            'page_status'            => $this->typechoToWordpressStatus($page->status, 'page'),
            'description'            => $excerpt,
            'title'                  => $page->title,
            'link'                   => $page->permalink,
            'permaLink'              => $page->permalink,
            'categories'             => $page->categories,
            'excerpt'                => $page->description,
            'text_more'              => $more,
            'mt_allow_comments'      => intval($page->allowComment),
            'mt_allow_pings'         => intval($page->allowPing),
            'wp_slug'                => $page->slug,
            'wp_password'            => $page->password,
            'wp_author'              => $page->author->name,
            'wp_page_parent_id'      => '0',
            'wp_page_parent_title'   => '',
            'wp_page_order'          => $page->order,     //meta是描述字段, 在page时表示顺序
            'wp_author_id'           => $page->authorId,
            'wp_author_display_name' => $page->author->screenName,
            'date_created_gmt'       => new Date($page->created),
            'custom_fields'          => [],
            'wp_page_template'       => $page->template
        ];
    }

    /**
     * @param string $methodName
     * @param ReflectionMethod $reflectionMethod
     * @param array $parameters
     * @throws Exception
     */
    public function beforeRpcCall(string $methodName, ReflectionMethod $reflectionMethod, array $parameters)
    {
        $valid = 2;
        $auth = [];

        $accesses = [
            'wp.newPage'           => 'editor',
            'wp.deletePage'        => 'editor',
            'wp.getPageList'       => 'editor',
            'wp.getAuthors'        => 'editor',
            'wp.deleteCategory'    => 'editor',
            'wp.getPageStatusList' => 'editor',
            'wp.getPageTemplates'  => 'editor',
            'wp.getOptions'        => 'administrator',
            'wp.setOptions'        => 'administrator',
            'mt.setPostCategories' => 'editor',
        ];

        foreach ($reflectionMethod->getParameters() as $key => $parameter) {
            $name = $parameter->getName();
            if ($name == 'userName' || $name == 'password') {
                $auth[$name] = $parameters[$key];
                $valid--;
            }
        }

        if ($valid == 0) {
            if ($this->user->login($auth['userName'], $auth['password'], true)) {
                /** 验证权限 */
                if ($this->user->pass($accesses[$methodName] ?? 'contributor', true)) {
                    $this->user->execute();
                } else {
                    throw new Exception(_t('权限不足'), 403);
                }
            } else {
                throw new Exception(_t('无法登陆, 密码错误'), 403);
            }
        }
    }

    /**
     * @param string $methodName
     * @param mixed $result
     */
    public function afterRpcCall(string $methodName, &$result): void
    {
        Widget::destroy();
    }

    /**
     * 获取所有的page
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetPages(int $blogId, string $userName, string $password): array
    {
        /** 过滤type为page的contents */
        /** 同样需要flush一下, 需要取出所有status的页面 */
        $pages = PageAdmin::alloc(null, 'status=all');

        /** 初始化要返回的数据结构 */
        $pageStructs = [];

        while ($pages->next()) {
            /** 对文章内容做截取处理，以获得description和text_more*/
            [$excerpt, $more] = $this->getPostExtended($pages);
            $pageStructs[] = [
                'dateCreated'            => new Date($this->options->timezone + $pages->created),
                'userid'                 => $pages->authorId,
                'page_id'                => intval($pages->cid),
                'page_status'            => $this->typechoToWordpressStatus(
                    ($pages->hasSaved || 'page_draft' == $pages->type) ? 'draft' : $pages->status,
                    'page'
                ),
                'description'            => $excerpt,
                'title'                  => $pages->title,
                'link'                   => $pages->permalink,
                'permaLink'              => $pages->permalink,
                'categories'             => $pages->categories,
                'excerpt'                => $pages->description,
                'text_more'              => $more,
                'mt_allow_comments'      => intval($pages->allowComment),
                'mt_allow_pings'         => intval($pages->allowPing),
                'wp_slug'                => $pages->slug,
                'wp_password'            => $pages->password,
                'wp_author'              => $pages->author->name,
                'wp_page_parent_id'      => 0,
                'wp_page_parent_title'   => '',
                'wp_page_order'          => intval($pages->order),     //meta是描述字段, 在page时表示顺序
                'wp_author_id'           => $pages->authorId,
                'wp_author_display_name' => $pages->author->screenName,
                'date_created_gmt'       => new Date($pages->created),
                'custom_fields'          => [],
                'wp_page_template'       => $pages->template
            ];
        }

        return $pageStructs;
    }

    /**
     * 撰写一个新page
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param array $content
     * @param bool $publish
     * @return int
     * @throws \Typecho\Db\Exception
     */
    public function wpNewPage(int $blogId, string $userName, string $password, array $content, bool $publish): int
    {
        $content['post_type'] = 'page';
        return $this->mwNewPost($blogId, $userName, $password, $content, $publish);
    }

    /**
     * MetaWeblog API
     * about MetaWeblog API, you can see http://www.xmlrpc.com/metaWeblogApi
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param array $content
     * @param bool $publish
     * @return int
     * @throws \Typecho\Db\Exception
     */
    public function mwNewPost(int $blogId, string $userName, string $password, array $content, bool $publish): int
    {
        /** 取得content内容 */
        $input = [];
        $type = isset($content['post_type']) && 'page' == $content['post_type'] ? 'page' : 'post';

        $input['title'] = trim($content['title']) == null ? _t('未命名文档') : $content['title'];

        if (isset($content['slug'])) {
            $input['slug'] = $content['slug'];
        } elseif (isset($content['wp_slug'])) {
            //fix issue 338, wlw只发送这个
            $input['slug'] = $content['wp_slug'];
        }

        $input['text'] = !empty($content['mt_text_more']) ? $content['description']
            . "\n<!--more-->\n" . $content['mt_text_more'] : $content['description'];
        $input['text'] = self::pluginHandle()->textFilter($input['text'], $this);

        $input['password'] = $content["wp_password"] ?? null;
        $input['order'] = $content["wp_page_order"] ?? null;

        $input['tags'] = $content['mt_keywords'] ?? null;
        $input['category'] = [];

        if (isset($content['postId'])) {
            $input['cid'] = $content['postId'];
        }

        if ('page' == $type && isset($content['wp_page_template'])) {
            $input['template'] = $content['wp_page_template'];
        }

        if (isset($content['dateCreated'])) {
            /** 解决客户端与服务器端时间偏移 */
            $input['created'] = $content['dateCreated']->getTimestamp()
                - $this->options->timezone + $this->options->serverTimezone;
        }

        if (!empty($content['categories']) && is_array($content['categories'])) {
            foreach ($content['categories'] as $category) {
                if (
                    !$this->db->fetchRow($this->db->select('mid')
                        ->from('table.metas')->where('type = ? AND name = ?', 'category', $category))
                ) {
                    $this->wpNewCategory($blogId, $userName, $password, ['name' => $category]);
                }

                $input['category'][] = $this->db->fetchObject($this->db->select('mid')
                    ->from('table.metas')->where('type = ? AND name = ?', 'category', $category)
                    ->limit(1))->mid;
            }
        }

        $input['allowComment'] = (isset($content['mt_allow_comments']) && (1 == $content['mt_allow_comments']
                || 'open' == $content['mt_allow_comments']))
            ? 1 : ((isset($content['mt_allow_comments']) && (0 == $content['mt_allow_comments']
                    || 'closed' == $content['mt_allow_comments']))
                ? 0 : $this->options->defaultAllowComment);

        $input['allowPing'] = (isset($content['mt_allow_pings']) && (1 == $content['mt_allow_pings']
                || 'open' == $content['mt_allow_pings']))
            ? 1 : ((isset($content['mt_allow_pings']) && (0 == $content['mt_allow_pings']
                    || 'closed' == $content['mt_allow_pings'])) ? 0 : $this->options->defaultAllowPing);

        $input['allowFeed'] = $this->options->defaultAllowFeed;
        $input['do'] = $publish ? 'publish' : 'save';
        $input['markdown'] = $this->options->xmlrpcMarkdown;

        /** 调整状态 */
        if (isset($content["{$type}_status"])) {
            $status = $this->wordpressToTypechoStatus($content["{$type}_status"], $type);
            $input['visibility'] = $content["visibility"] ?? $status;
            if ('publish' == $status || 'waiting' == $status || 'private' == $status) {
                $input['do'] = 'publish';

                if ('private' == $status) {
                    $input['private'] = 1;
                }
            } else {
                $input['do'] = 'save';
            }
        }

        /** 对未归档附件进行归档 */
        $unattached = $this->db->fetchAll($this->select()->where('table.contents.type = ? AND
        (table.contents.parent = 0 OR table.contents.parent IS NULL)', 'attachment'), [$this, 'filter']);

        if (!empty($unattached)) {
            foreach ($unattached as $attach) {
                if (false !== strpos($input['text'], $attach['attachment']->url)) {
                    if (!isset($input['attachment'])) {
                        $input['attachment'] = [];
                    }

                    $input['attachment'][] = $attach['cid'];
                }
            }
        }

        /** 调用已有组件 */
        if ('page' == $type) {
            $widget = PageEdit::alloc(null, $input, function (PageEdit $page) {
                $page->writePage();
            });
        } else {
            $widget = PostEdit::alloc(null, $input, function (PostEdit $post) {
                $post->writePost();
            });
        }

        return $widget->cid;
    }

    /**
     * 添加一个新的分类
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param array $category
     * @return int
     * @throws \Typecho\Db\Exception
     */
    public function wpNewCategory(int $blogId, string $userName, string $password, array $category): int
    {
        /** 开始接受数据 */
        $input['name'] = $category['name'];
        $input['slug'] = Common::slugName(empty($category['slug']) ? $category['name'] : $category['slug']);
        $input['parent'] = $category['parent_id'] ?? ($category['parent'] ?? 0);
        $input['description'] = $category['description'] ?? $category['name'];

        /** 调用已有组件 */
        $categoryWidget = CategoryEdit::alloc(null, $input, function (CategoryEdit $category) {
            $category->insertCategory();
        });

        if (!$categoryWidget->have()) {
            throw new Exception(_t('分类不存在'), 404);
        }

        return $categoryWidget->mid;
    }

    /**
     * 删除pageId指定的page
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param int $pageId
     * @return bool
     * @throws \Typecho\Db\Exception
     */
    public function wpDeletePage(int $blogId, string $userName, string $password, int $pageId): bool
    {
        PageEdit::alloc(null, ['cid' => $pageId], function (PageEdit $page) {
            $page->deletePage();
        });
        return true;
    }

    /**
     * 编辑pageId指定的page
     *
     * @param int $blogId
     * @param int $pageId
     * @param string $userName
     * @param string $password
     * @param array $content
     * @param bool $publish
     * @return bool
     */
    public function wpEditPage(
        int $blogId,
        int $pageId,
        string $userName,
        string $password,
        array $content,
        bool $publish
    ): bool {
        $content['post_type'] = 'page';
        $this->mwEditPost($pageId, $userName, $password, $content, $publish);
        return true;
    }

    /**
     * 编辑post
     *
     * @param int $postId
     * @param string $userName
     * @param string $password
     * @param array $content
     * @param bool $publish
     * @return int
     * @throws \Typecho\Db\Exception
     */
    public function mwEditPost(
        int $postId,
        string $userName,
        string $password,
        array $content,
        bool $publish = true
    ): int {
        $content['postId'] = $postId;
        return $this->mwNewPost(1, $userName, $password, $content, $publish);
    }

    /**
     * 编辑postId指定的post
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param int $postId
     * @param array $content
     * @return bool
     * @throws \Typecho\Db\Exception
     */
    public function wpEditPost(int $blogId, string $userName, string $password, int $postId, array $content): bool
    {
        $post = Archive::alloc('type=single', ['cid' => $postId], false);
        if ($post->type == 'attachment') {
            $attachment['title'] = $content['post_title'];
            $attachment['slug'] = $content['post_excerpt'];

            $text = unserialize($post->text);
            $text['description'] = $content['description'];

            $attachment['text'] = serialize($text);

            /** 更新数据 */
            $updateRows = $this->update($attachment, $this->db->sql()->where('cid = ?', $postId));
            return $updateRows > 0;
        }

        return $this->mwEditPost($postId, $userName, $password, $content) > 0;
    }

    /**
     * 获取page列表，没有wpGetPages获得的详细
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetPageList(int $blogId, string $userName, string $password): array
    {
        $pages = PageAdmin::alloc(null, 'status=all');
        $pageStructs = [];

        while ($pages->next()) {
            $pageStructs[] = [
                'dateCreated'      => new Date($this->options->timezone + $pages->created),
                'date_created_gmt' => new Date($this->options->timezone + $pages->created),
                'page_id'          => $pages->cid,
                'page_title'       => $pages->title,
                'page_parent_id'   => '0',
            ];
        }

        return $pageStructs;
    }

    /**
     * 获得一个由blog所有作者的信息组成的数组
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @return array
     * @throws \Typecho\Db\Exception
     */
    public function wpGetAuthors(int $blogId, string $userName, string $password): array
    {
        /** 构建查询*/
        $select = $this->db->select('table.users.uid', 'table.users.name', 'table.users.screenName')
            ->from('table.users');
        $authors = $this->db->fetchAll($select);

        $authorStructs = [];
        foreach ($authors as $author) {
            $authorStructs[] = [
                'user_id'      => $author['uid'],
                'user_login'   => $author['name'],
                'display_name' => $author['screenName']
            ];
        }

        return $authorStructs;
    }

    /**
     * 获取由给定的string开头的链接组成的数组
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param string $category
     * @param int $maxResults
     * @return array
     * @throws \Typecho\Db\Exception
     */
    public function wpSuggestCategories(
        int $blogId,
        string $userName,
        string $password,
        string $category,
        int $maxResults = 0
    ): array {
        /** 构造出查询语句并且查询*/
        $key = Common::filterSearchQuery($category);
        $key = '%' . $key . '%';
        $select = Metas::alloc()->select()->where(
            'table.metas.type = ? AND (table.metas.name LIKE ? OR slug LIKE ?)',
            'category',
            $key,
            $key
        );

        if ($maxResults > 0) {
            $select->limit($maxResults);
        }

        /** 不要category push到contents的容器中 */
        $categories = $this->db->fetchAll($select);

        /** 初始化categorise数组*/
        $categoryStructs = [];
        foreach ($categories as $category) {
            $categoryStructs[] = [
                'category_id'   => $category['mid'],
                'category_name' => $category['name'],
            ];
        }

        return $categoryStructs;
    }

    /**
     * 获取用户
     *
     * @param string $userName 用户名
     * @param string $password 密码
     * @return array
     */
    public function wpGetUsersBlogs(string $userName, string $password): array
    {
        return [
            [
                'isAdmin'  => $this->user->pass('administrator', true),
                'url'      => $this->options->siteUrl,
                'blogid'   => '1',
                'blogName' => $this->options->title,
                'xmlrpc'   => $this->options->xmlRpcUrl
            ]
        ];
    }

    /**
     * 获取用户
     *
     * @param int $blogId
     * @param string $userName 用户名
     * @param string $password 密码
     * @return array
     */
    public function wpGetProfile(int $blogId, string $userName, string $password): array
    {
        return [
            'user_id'      => $this->user->uid,
            'username'     => $this->user->name,
            'first_name'   => '',
            'last_name'    => '',
            'registered'   => new Date($this->options->timezone + $this->user->created),
            'bio'          => '',
            'email'        => $this->user->mail,
            'nickname'     => $this->user->screenName,
            'url'          => $this->user->url,
            'display_name' => $this->user->screenName,
            'roles'        => $this->user->group
        ];
    }

    /**
     * 获取标签列表
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetTags(int $blogId, string $userName, string $password): array
    {
        $struct = [];
        $tags = Cloud::alloc();

        while ($tags->next()) {
            $struct[] = [
                'tag_id'   => $tags->mid,
                'name'     => $tags->name,
                'count'    => $tags->count,
                'slug'     => $tags->slug,
                'html_url' => $tags->permalink,
                'rss_url'  => $tags->feedUrl
            ];
        }

        return $struct;
    }

    /**
     * 删除分类
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param integer $categoryId
     * @return bool
     */
    public function wpDeleteCategory(int $blogId, string $userName, string $password, int $categoryId): bool
    {
        CategoryEdit::alloc(null, ['mid' => $categoryId], function (CategoryEdit $category) {
            $category->deleteCategory();
        });

        return true;
    }

    /**
     * 获取评论数目
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param integer $postId
     * @return array
     */
    public function wpGetCommentCount(int $blogId, string $userName, string $password, int $postId): array
    {
        $stat = Stat::alloc(null, ['cid' => $postId]);

        return [
            'approved'            => $stat->currentPublishedCommentsNum,
            'awaiting_moderation' => $stat->currentWaitingCommentsNum,
            'spam'                => $stat->currentSpamCommentsNum,
            'total_comments'      => $stat->currentCommentsNum
        ];
    }

    /**
     * 获取文章类型列表
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetPostFormats(int $blogId, string $userName, string $password): array
    {
        return [
            'standard' => _t('标准')
        ];
    }

    /**
     * 获取文章状态列表
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetPostStatusList(int $blogId, string $userName, string $password): array
    {
        return [
            'draft'   => _t('草稿'),
            'pending' => _t('待审核'),
            'publish' => _t('已发布')
        ];
    }

    /**
     * 获取页面状态列表
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetPageStatusList(int $blogId, string $userName, string $password): array
    {
        return [
            'draft'   => _t('草稿'),
            'publish' => _t('已发布')
        ];
    }

    /**
     * 获取评论状态列表
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetCommentStatusList(int $blogId, string $userName, string $password): array
    {
        return [
            'hold'    => _t('待审核'),
            'approve' => _t('显示'),
            'spam'    => _t('垃圾')
        ];
    }

    /**
     * 获取页面模板
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function wpGetPageTemplates(int $blogId, string $userName, string $password): array
    {
        $templates = array_flip($this->getTemplates());
        $templates['Default'] = '';

        return $templates;
    }

    /**
     * 获取系统选项
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param array $options
     * @return array
     */
    public function wpGetOptions(int $blogId, string $userName, string $password, array $options = []): array
    {
        $struct = [];
        if (empty($options)) {
            $options = array_keys($this->wpOptions);
        }

        foreach ($options as $option) {
            if (isset($this->wpOptions[$option])) {
                $struct[$option] = $this->wpOptions[$option];
                if (isset($struct[$option]['option'])) {
                    $struct[$option]['value'] = $this->options->{$struct[$option]['option']};
                    unset($struct[$option]['option']);
                }
            }
        }

        return $struct;
    }

    /**
     * 设置系统选项
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param array $options
     * @return array
     * @throws \Typecho\Db\Exception
     */
    public function wpSetOptions(int $blogId, string $userName, string $password, array $options = []): array
    {
        $struct = [];
        foreach ($options as $option => $value) {
            if (isset($this->wpOptions[$option])) {
                $struct[$option] = $this->wpOptions[$option];
                if (isset($struct[$option]['option'])) {
                    $struct[$option]['value'] = $this->options->{$struct[$option]['option']};
                    unset($struct[$option]['option']);
                }

                if (!$this->wpOptions[$option]['readonly'] && isset($this->wpOptions[$option]['option'])) {
                    if (
                        $this->db->query($this->db->update('table.options')
                            ->rows(['value' => $value])
                            ->where('name = ?', $this->wpOptions[$option]['option'])) > 0
                    ) {
                        $struct[$option]['value'] = $value;
                    }
                }
            }
        }

        return $struct;
    }

    /**
     * 获取评论
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param integer $commentId
     * @return array
     * @throws Exception
     */
    public function wpGetComment(int $blogId, string $userName, string $password, int $commentId): array
    {
        $comment = CommentsEdit::alloc(null, ['coid' => $commentId], function (CommentsEdit $comment) {
            $comment->getComment();
        });

        if (!$comment->have()) {
            throw new Exception(_t('评论不存在'), 404);
        }

        if (!$comment->commentIsWriteable()) {
            throw new Exception(_t('没有获取评论的权限'), 403);
        }

        return [
            'date_created_gmt' => new Date($this->options->timezone + $comment->created),
            'user_id'          => $comment->authorId,
            'comment_id'       => $comment->coid,
            'parent'           => $comment->parent,
            'status'           => $this->typechoToWordpressStatus($comment->status, 'comment'),
            'content'          => $comment->text,
            'link'             => $comment->permalink,
            'post_id'          => $comment->cid,
            'post_title'       => $comment->title,
            'author'           => $comment->author,
            'author_url'       => $comment->url,
            'author_email'     => $comment->mail,
            'author_ip'        => $comment->ip,
            'type'             => $comment->type
        ];
    }

    /**
     * 获取评论列表
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param array $struct
     * @return array
     */
    public function wpGetComments(int $blogId, string $userName, string $password, array $struct): array
    {
        $input = [];
        if (!empty($struct['status'])) {
            $input['status'] = $this->wordpressToTypechoStatus($struct['status'], 'comment');
        } else {
            $input['__typecho_all_comments'] = 'on';
        }

        if (!empty($struct['post_id'])) {
            $input['cid'] = $struct['post_id'];
        }

        $pageSize = 10;
        if (!empty($struct['number'])) {
            $pageSize = abs(intval($struct['number']));
        }

        if (!empty($struct['offset'])) {
            $offset = abs(intval($struct['offset']));
            $input['page'] = ceil($offset / $pageSize);
        }

        $comments = CommentsAdmin::alloc('pageSize=' . $pageSize, $input, false);
        $commentsStruct = [];

        while ($comments->next()) {
            $commentsStruct[] = [
                'date_created_gmt' => new Date($this->options->timezone + $comments->created),
                'user_id'          => $comments->authorId,
                'comment_id'       => $comments->coid,
                'parent'           => $comments->parent,
                'status'           => $this->typechoToWordpressStatus($comments->status, 'comment'),
                'content'          => $comments->text,
                'link'             => $comments->permalink,
                'post_id'          => $comments->cid,
                'post_title'       => $comments->title,
                'author'           => $comments->author,
                'author_url'       => $comments->url,
                'author_email'     => $comments->mail,
                'author_ip'        => $comments->ip,
                'type'             => $comments->type
            ];
        }

        return $commentsStruct;
    }

    /**
     * 获取评论
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param integer $commentId
     * @return boolean
     * @throws \Typecho\Db\Exception
     */
    public function wpDeleteComment(int $blogId, string $userName, string $password, int $commentId): bool
    {
        CommentsEdit::alloc(null, ['coid' => $commentId], function (CommentsEdit $comment) {
            $comment->deleteComment();
        });
        return true;
    }

    /**
     * 编辑评论
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param integer $commentId
     * @param array $struct
     * @return boolean
     * @throws \Typecho\Db\Exception
     */
    public function wpEditComment(int $blogId, string $userName, string $password, int $commentId, array $struct): bool
    {
        $input = [];

        if (isset($struct['date_created_gmt']) && $struct['date_created_gmt'] instanceof Date) {
            $input['created'] = $struct['date_created_gmt']->getTimestamp()
                - $this->options->timezone + $this->options->serverTimezone;
        }

        if (isset($struct['status'])) {
            $input['status'] = $this->wordpressToTypechoStatus($struct['status'], 'comment');
        }

        if (isset($struct['content'])) {
            $input['text'] = $struct['content'];
        }

        if (isset($struct['author'])) {
            $input['author'] = $struct['author'];
        }

        if (isset($struct['author_url'])) {
            $input['url'] = $struct['author_url'];
        }

        if (isset($struct['author_email'])) {
            $input['mail'] = $struct['author_email'];
        }


        $comment = CommentsEdit::alloc(null, $input, function (CommentsEdit $comment) {
            $comment->editComment();
        });
        return $comment->have();
    }

    /**
     * 更新评论
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param mixed $path
     * @param array $struct
     * @return int
     * @throws \Exception
     */
    public function wpNewComment(int $blogId, string $userName, string $password, $path, array $struct): int
    {
        if (is_numeric($path)) {
            $post = Archive::alloc('type=single', ['cid' => $path], false);

            if ($post->have()) {
                $path = $post->permalink;
            }
        } else {
            $path = Common::url(substr($path, strlen($this->options->index)), '/');
        }

        $input = [
            'permalink' => $path,
            'type'      => 'comment'
        ];

        if (isset($struct['comment_author'])) {
            $input['author'] = $struct['author'];
        }

        if (isset($struct['comment_author_email'])) {
            $input['mail'] = $struct['author_email'];
        }

        if (isset($struct['comment_author_url'])) {
            $input['url'] = $struct['author_url'];
        }

        if (isset($struct['comment_parent'])) {
            $input['parent'] = $struct['comment_parent'];
        }

        if (isset($struct['content'])) {
            $input['text'] = $struct['content'];
        }

        $comment = Feedback::alloc(['checkReferer' => false], $input, function (Feedback $comment) {
            $comment->action();
        });
        return $comment->have() ? $comment->coid : 0;
    }

    /**
     * 获取媒体文件
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param array $struct
     * @return array
     */
    public function wpGetMediaLibrary(int $blogId, string $userName, string $password, array $struct): array
    {
        $input = [];

        if (!empty($struct['parent_id'])) {
            $input['parent'] = $struct['parent_id'];
        }

        if (!empty($struct['mime_type'])) {
            $input['mime'] = $struct['mime_type'];
        }

        $pageSize = 10;
        if (!empty($struct['number'])) {
            $pageSize = abs(intval($struct['number']));
        }

        if (!empty($struct['offset'])) {
            $input['page'] = abs(intval($struct['offset'])) + 1;
        }

        $attachments = AttachmentAdmin::alloc('pageSize=' . $pageSize, $input, false);
        $attachmentsStruct = [];

        while ($attachments->next()) {
            $attachmentsStruct[] = [
                'attachment_id'    => $attachments->cid,
                'date_created_gmt' => new Date($this->options->timezone + $attachments->created),
                'parent'           => $attachments->parent,
                'link'             => $attachments->attachment->url,
                'title'            => $attachments->title,
                'caption'          => $attachments->slug,
                'description'      => $attachments->attachment->description,
                'metadata'         => [
                    'file' => $attachments->attachment->path,
                    'size' => $attachments->attachment->size,
                ],
                'thumbnail'        => $attachments->attachment->url,
            ];
        }
        return $attachmentsStruct;
    }

    /**
     * 获取媒体文件
     *
     * @param integer $blogId
     * @param string $userName
     * @param string $password
     * @param int $attachmentId
     * @return array
     */
    public function wpGetMediaItem(int $blogId, string $userName, string $password, int $attachmentId): array
    {
        $attachment = AttachmentEdit::alloc(null, ['cid' => $attachmentId]);

        return [
            'attachment_id'    => $attachment->cid,
            'date_created_gmt' => new Date($this->options->timezone + $attachment->created),
            'parent'           => $attachment->parent,
            'link'             => $attachment->attachment->url,
            'title'            => $attachment->title,
            'caption'          => $attachment->slug,
            'description'      => $attachment->attachment->description,
            'metadata'         => [
                'file' => $attachment->attachment->path,
                'size' => $attachment->attachment->size,
            ],
            'thumbnail'        => $attachment->attachment->url,
        ];
    }

    /**
     * 获取指定id的post
     *
     * @param int $postId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function mwGetPost(int $postId, string $userName, string $password): array
    {
        $post = PostEdit::alloc(null, ['cid' => $postId], false);

        /** 对文章内容做截取处理，以获得description和text_more*/
        [$excerpt, $more] = $this->getPostExtended($post);
        /** 只需要分类的name*/
        $categories = array_column($post->categories, 'name');
        $tags = array_column($post->tags, 'name');

        return [
            'dateCreated'            => new Date($this->options->timezone + $post->created),
            'userid'                 => $post->authorId,
            'postid'                 => $post->cid,
            'description'            => $excerpt,
            'title'                  => $post->title,
            'link'                   => $post->permalink,
            'permaLink'              => $post->permalink,
            'categories'             => $categories,
            'mt_excerpt'             => $post->description,
            'mt_text_more'           => $more,
            'mt_allow_comments'      => intval($post->allowComment),
            'mt_allow_pings'         => intval($post->allowPing),
            'mt_keywords'            => implode(', ', $tags),
            'wp_slug'                => $post->slug,
            'wp_password'            => $post->password,
            'wp_author'              => $post->author->name,
            'wp_author_id'           => $post->authorId,
            'wp_author_display_name' => $post->author->screenName,
            'date_created_gmt'       => new Date($post->created),
            'post_status'            => $this->typechoToWordpressStatus($post->status, 'post'),
            'custom_fields'          => [],
            'sticky'                 => 0
        ];
    }

    /**
     * 获取前$postsNum个post
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param int $postsNum
     * @return array
     */
    public function mwGetRecentPosts(int $blogId, string $userName, string $password, int $postsNum): array
    {
        $posts = PostAdmin::alloc('pageSize=' . $postsNum, 'status=all');

        $postStructs = [];
        /** 如果这个post存在则输出，否则输出错误 */
        while ($posts->next()) {
            /** 对文章内容做截取处理，以获得description和text_more*/
            [$excerpt, $more] = $this->getPostExtended($posts);

            /** 只需要分类的name*/
            /** 可以用flatten函数处理 */
            $categories = array_column($posts->categories, 'name');
            $tags = array_column($posts->tags, 'name');

            $postStructs[] = [
                'dateCreated'            => new Date($this->options->timezone + $posts->created),
                'userid'                 => $posts->authorId,
                'postid'                 => $posts->cid,
                'description'            => $excerpt,
                'title'                  => $posts->title,
                'link'                   => $posts->permalink,
                'permaLink'              => $posts->permalink,
                'categories'             => $categories,
                'mt_excerpt'             => $posts->description,
                'mt_text_more'           => $more,
                'wp_more_text'           => $more,
                'mt_allow_comments'      => intval($posts->allowComment),
                'mt_allow_pings'         => intval($posts->allowPing),
                'mt_keywords'            => implode(', ', $tags),
                'wp_slug'                => $posts->slug,
                'wp_password'            => $posts->password,
                'wp_author'              => $posts->author->name,
                'wp_author_id'           => $posts->authorId,
                'wp_author_display_name' => $posts->author->screenName,
                'date_created_gmt'       => new Date($posts->created),
                'post_status'            => $this->typechoToWordpressStatus(
                    ($posts->hasSaved || 'post_draft' == $posts->type) ? 'draft' : $posts->status,
                    'post'
                ),
                'custom_fields'          => [],
                'wp_post_format'         => 'standard',
                'date_modified'          => new Date($this->options->timezone + $posts->modified),
                'date_modified_gmt'      => new Date($posts->modified),
                'wp_post_thumbnail'      => '',
                'sticky'                 => 0
            ];
        }

        return $postStructs;
    }

    /**
     * 获取所有的分类
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function mwGetCategories(int $blogId, string $userName, string $password): array
    {
        $categories = CategoryRows::alloc();

        /** 初始化category数组*/
        $categoryStructs = [];
        while ($categories->next()) {
            $categoryStructs[] = [
                'categoryId'          => $categories->mid,
                'parentId'            => $categories->parent,
                'categoryName'        => $categories->name,
                'categoryDescription' => $categories->description,
                'description'         => $categories->name,
                'htmlUrl'             => $categories->permalink,
                'rssUrl'              => $categories->feedUrl,
            ];
        }

        return $categoryStructs;
    }

    /**
     * mwNewMediaObject
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param array $data
     * @return array
     * @throws Exception
     * @throws \Typecho\Db\Exception
     */
    public function mwNewMediaObject(int $blogId, string $userName, string $password, array $data): array
    {
        $result = Upload::uploadHandle($data);

        if (false === $result) {
            throw new Exception('upload failed', -32001);
        } else {
            $insertId = $this->insert([
                'title'        => $result['name'],
                'slug'         => $result['name'],
                'type'         => 'attachment',
                'status'       => 'publish',
                'text'         => serialize($result),
                'allowComment' => 1,
                'allowPing'    => 0,
                'allowFeed'    => 1
            ]);

            $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $insertId)
                ->where('table.contents.type = ?', 'attachment'), [$this, 'push']);

            /** 增加插件接口 */
            self::pluginHandle()->upload($this);

            return [
                'file' => $this->attachment->name,
                'url'  => $this->attachment->url
            ];
        }
    }

    /**
     * 获取 $postNum个post title
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param int $postsNum
     * @return array
     */
    public function mtGetRecentPostTitles(int $blogId, string $userName, string $password, int $postsNum): array
    {
        /** 读取数据*/
        $posts = PostAdmin::alloc('pageSize=' . $postsNum, 'status=all');

        /**初始化*/
        $postTitleStructs = [];
        while ($posts->next()) {
            $postTitleStructs[] = [
                'dateCreated'      => new Date($this->options->timezone + $posts->created),
                'userid'           => $posts->authorId,
                'postid'           => $posts->cid,
                'title'            => $posts->title,
                'date_created_gmt' => new Date($this->options->timezone + $posts->created)
            ];
        }

        return $postTitleStructs;
    }

    /**
     * 获取分类列表
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function mtGetCategoryList(int $blogId, string $userName, string $password): array
    {
        $categories = CategoryRows::alloc();

        /** 初始化categorise数组*/
        $categoryStructs = [];
        while ($categories->next()) {
            $categoryStructs[] = [
                'categoryId'   => $categories->mid,
                'categoryName' => $categories->name,
            ];
        }
        return $categoryStructs;
    }

    /**
     * 获取指定post的分类
     *
     * @param int $postId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function mtGetPostCategories(int $postId, string $userName, string $password): array
    {
        $post = PostEdit::alloc(null, ['cid' => $postId], false);

        /** 格式化categories*/
        $categories = [];
        foreach ($post->categories as $category) {
            $categories[] = [
                'categoryName' => $category['name'],
                'categoryId'   => $category['mid'],
                'isPrimary'    => true
            ];
        }

        return $categories;
    }

    /**
     * 修改post的分类
     *
     * @param int $postId
     * @param string $userName
     * @param string $password
     * @param array $categories
     * @return bool
     * @throws \Typecho\Db\Exception
     */
    public function mtSetPostCategories(int $postId, string $userName, string $password, array $categories): bool
    {
        PostEdit::alloc(null, ['cid' => $postId], function (PostEdit $post) use ($postId, $categories) {
            $post->setCategories($postId, array_column($categories, 'categoryId'), 'publish' == $post->status);
        });

        return true;
    }

    /**
     * 发布(重建)数据
     *
     * @param int $postId
     * @param string $userName
     * @param string $password
     * @return bool
     */
    public function mtPublishPost(int $postId, string $userName, string $password): bool
    {
        PostEdit::alloc(null, ['cid' => $postId, 'status' => 'publish'], function (PostEdit $post) {
            $post->markPost();
        });

        return true;
    }

    /**
     * 取得当前用户的所有blog
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function bloggerGetUsersBlogs(int $blogId, string $userName, string $password): array
    {
        return [
            [
                'isAdmin'  => $this->user->pass('administrator', true),
                'url'      => $this->options->siteUrl,
                'blogid'   => 1,
                'blogName' => $this->options->title,
                'xmlrpc'   => $this->options->xmlRpcUrl
            ]
        ];
    }

    /**
     * 返回当前用户的信息
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function bloggerGetUserInfo(int $blogId, string $userName, string $password): array
    {
        return [
            'nickname'  => $this->user->screenName,
            'userid'    => $this->user->uid,
            'url'       => $this->user->url,
            'email'     => $this->user->mail,
            'lastname'  => '',
            'firstname' => ''
        ];
    }

    /**
     * 获取当前作者的一个指定id的post的详细信息
     *
     * @param int $blogId
     * @param int $postId
     * @param string $userName
     * @param string $password
     * @return array
     */
    public function bloggerGetPost(int $blogId, int $postId, string $userName, string $password): array
    {
        $post = PostEdit::alloc(null, ['cid' => $postId]);
        $categories = array_column($post->categories, 'name');

        $content = '<title>' . $post->title . '</title>';
        $content .= '<category>' . implode(',', $categories) . '</category>';
        $content .= stripslashes($post->text);

        return [
            'userid'      => $post->authorId,
            'dateCreated' => new Date($this->options->timezone + $post->created),
            'content'     => $content,
            'postid'      => $post->cid
        ];
    }

    /**
     * bloggerDeletePost
     * 删除文章
     *
     * @param int $blogId
     * @param int $postId
     * @param string $userName
     * @param string $password
     * @param mixed $publish
     * @return bool
     */
    public function bloggerDeletePost(int $blogId, int $postId, string $userName, string $password, $publish): bool
    {
        PostEdit::alloc(null, ['cid' => $postId], function (PostEdit $post) {
            $post->deletePost();
        });
        return true;
    }

    /**
     * 获取当前作者前postsNum个post
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param int $postsNum
     * @return array
     */
    public function bloggerGetRecentPosts(int $blogId, string $userName, string $password, int $postsNum): array
    {
        $posts = PostAdmin::alloc('pageSize=' . $postsNum, 'status=all');

        $postStructs = [];
        while ($posts->next()) {
            $categories = array_column($posts->categories, 'name');

            $content = '<title>' . $posts->title . '</title>';
            $content .= '<category>' . implode(',', $categories) . '</category>';
            $content .= stripslashes($posts->text);

            $struct = [
                'userid'      => $posts->authorId,
                'dateCreated' => new Date($this->options->timezone + $posts->created),
                'content'     => $content,
                'postid'      => $posts->cid,
            ];
            $postStructs[] = $struct;
        }

        return $postStructs;
    }

    /**
     * bloggerGetTemplate
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param mixed $template
     * @return bool
     */
    public function bloggerGetTemplate(int $blogId, string $userName, string $password, $template): bool
    {
        /** todo:暂时先返回true*/
        return true;
    }

    /**
     * bloggerSetTemplate
     *
     * @param int $blogId
     * @param string $userName
     * @param string $password
     * @param mixed $content
     * @param mixed $template
     * @return bool
     */
    public function bloggerSetTemplate(int $blogId, string $userName, string $password, $content, $template): bool
    {
        /** todo:暂时先返回true*/
        return true;
    }

    /**
     * pingbackPing
     *
     * @param string $source
     * @param string $target
     * @return int
     * @throws \Exception
     */
    public function pingbackPing(string $source, string $target): int
    {
        /** 检查目标地址是否正确*/
        $pathInfo = Common::url(substr($target, strlen($this->options->index)), '/');
        $post = Router::match($pathInfo);

        /** 检查源地址是否合法 */
        $params = parse_url($source);
        if (false === $params || !in_array($params['scheme'], ['http', 'https'])) {
            throw new Exception(_t('源地址服务器错误'), 16);
        }

        if (!Common::checkSafeHost($params['host'])) {
            throw new Exception(_t('源地址服务器错误'), 16);
        }

        /** 这样可以得到cid或者slug*/
        if (!($post instanceof Archive) || !$post->have() || !$post->is('single')) {
            throw new Exception(_t('这个目标地址不存在'), 33);
        }

        if ($post) {
            /** 检查是否可以ping*/
            if ($post->allowPing) {

                /** 现在可以ping了，但是还得检查下这个pingback是否已经存在了*/
                $pingNum = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
                    ->from('table.comments')
                    ->where(
                        'table.comments.cid = ? AND table.comments.url = ? AND table.comments.type <> ?',
                        $post->cid,
                        $source,
                        'comment'
                    ))->num;

                if ($pingNum <= 0) {
                    try {
                        $pingbackRequest = new Pingback($source, $target);

                        $pingback = [
                            'cid'     => $post->cid,
                            'created' => $this->options->time,
                            'agent'   => $this->request->getAgent(),
                            'ip'      => $this->request->getIp(),
                            'author'  => $pingbackRequest->getTitle(),
                            'url'     => Common::safeUrl($source),
                            'text'    => $pingbackRequest->getContent(),
                            'ownerId' => $post->author->uid,
                            'type'    => 'pingback',
                            'status'  => $this->options->commentsRequireModeration ? 'waiting' : 'approved'
                        ];

                        /** 加入plugin */
                        $pingback = self::pluginHandle()->pingback($pingback, $post);

                        /** 执行插入*/
                        $insertId = Comments::alloc()->insert($pingback);

                        /** 评论完成接口 */
                        self::pluginHandle()->finishPingback($this);

                        return $insertId;
                    } catch (WidgetException $e) {
                        throw new Exception(_t('源地址服务器错误'), 16);
                    }
                } else {
                    throw new Exception(_t('PingBack已经存在'), 48);
                }
            } else {
                throw new Exception(_t('目标地址禁止Ping'), 49);
            }
        } else {
            throw new Exception(_t('这个目标地址不存在'), 33);
        }
    }

    /**
     * 入口执行方法
     *
     * @throws Exception
     */
    public function action()
    {
        if (0 == $this->options->allowXmlRpc) {
            throw new Exception(_t('请求的地址不存在'), 404);
        }

        if (isset($this->request->rsd)) {
            echo
            <<<EOF
<?xml version="1.0" encoding="{$this->options->charset}"?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
    <service>
        <engineName>Typecho</engineName>
        <engineLink>http://www.typecho.org/</engineLink>
        <homePageLink>{$this->options->siteUrl}</homePageLink>
        <apis>
            <api name="WordPress" blogID="1" preferred="true" apiLink="{$this->options->xmlRpcUrl}" />
            <api name="Movable Type" blogID="1" preferred="false" apiLink="{$this->options->xmlRpcUrl}" />
            <api name="MetaWeblog" blogID="1" preferred="false" apiLink="{$this->options->xmlRpcUrl}" />
            <api name="Blogger" blogID="1" preferred="false" apiLink="{$this->options->xmlRpcUrl}" />
        </apis>
    </service>
</rsd>
EOF;
        } elseif (isset($this->request->wlw)) {
            echo
            <<<EOF
<?xml version="1.0" encoding="{$this->options->charset}"?>
<manifest xmlns="http://schemas.microsoft.com/wlw/manifest/weblog">
    <options>
        <supportsKeywords>Yes</supportsKeywords>
        <supportsFileUpload>Yes</supportsFileUpload>
        <supportsExtendedEntries>Yes</supportsExtendedEntries>
        <supportsCustomDate>Yes</supportsCustomDate>
        <supportsCategories>Yes</supportsCategories>

        <supportsCategoriesInline>Yes</supportsCategoriesInline>
        <supportsMultipleCategories>Yes</supportsMultipleCategories>
        <supportsHierarchicalCategories>Yes</supportsHierarchicalCategories>
        <supportsNewCategories>Yes</supportsNewCategories>
        <supportsNewCategoriesInline>Yes</supportsNewCategoriesInline>
        <supportsCommentPolicy>Yes</supportsCommentPolicy>

        <supportsPingPolicy>Yes</supportsPingPolicy>
        <supportsAuthor>Yes</supportsAuthor>
        <supportsSlug>Yes</supportsSlug>
        <supportsPassword>Yes</supportsPassword>
        <supportsExcerpt>Yes</supportsExcerpt>
        <supportsTrackbacks>Yes</supportsTrackbacks>

        <supportsPostAsDraft>Yes</supportsPostAsDraft>

        <supportsPages>Yes</supportsPages>
        <supportsPageParent>No</supportsPageParent>
        <supportsPageOrder>Yes</supportsPageOrder>
        <requiresXHTML>True</requiresXHTML>
        <supportsAutoUpdate>No</supportsAutoUpdate>

    </options>
</manifest>
EOF;
        } else {
            $api = [
                /** WordPress API */
                'wp.getPage'                => [$this, 'wpGetPage'],
                'wp.getPages'               => [$this, 'wpGetPages'],
                'wp.newPage'                => [$this, 'wpNewPage'],
                'wp.deletePage'             => [$this, 'wpDeletePage'],
                'wp.editPage'               => [$this, 'wpEditPage'],
                'wp.getPageList'            => [$this, 'wpGetPageList'],
                'wp.getAuthors'             => [$this, 'wpGetAuthors'],
                'wp.getCategories'          => [$this, 'mwGetCategories'],
                'wp.newCategory'            => [$this, 'wpNewCategory'],
                'wp.suggestCategories'      => [$this, 'wpSuggestCategories'],
                'wp.uploadFile'             => [$this, 'mwNewMediaObject'],

                /** New WordPress API since 2.9.2 */
                'wp.getUsersBlogs'          => [$this, 'wpGetUsersBlogs'],
                'wp.getTags'                => [$this, 'wpGetTags'],
                'wp.deleteCategory'         => [$this, 'wpDeleteCategory'],
                'wp.getCommentCount'        => [$this, 'wpGetCommentCount'],
                'wp.getPostStatusList'      => [$this, 'wpGetPostStatusList'],
                'wp.getPageStatusList'      => [$this, 'wpGetPageStatusList'],
                'wp.getPageTemplates'       => [$this, 'wpGetPageTemplates'],
                'wp.getOptions'             => [$this, 'wpGetOptions'],
                'wp.setOptions'             => [$this, 'wpSetOptions'],
                'wp.getComment'             => [$this, 'wpGetComment'],
                'wp.getComments'            => [$this, 'wpGetComments'],
                'wp.deleteComment'          => [$this, 'wpDeleteComment'],
                'wp.editComment'            => [$this, 'wpEditComment'],
                'wp.newComment'             => [$this, 'wpNewComment'],
                'wp.getCommentStatusList'   => [$this, 'wpGetCommentStatusList'],

                /** New Wordpress API after 2.9.2 */
                'wp.getProfile'             => [$this, 'wpGetProfile'],
                'wp.getPostFormats'         => [$this, 'wpGetPostFormats'],
                'wp.getMediaLibrary'        => [$this, 'wpGetMediaLibrary'],
                'wp.getMediaItem'           => [$this, 'wpGetMediaItem'],
                'wp.editPost'               => [$this, 'wpEditPost'],

                /** Blogger API */
                'blogger.getUsersBlogs'     => [$this, 'bloggerGetUsersBlogs'],
                'blogger.getUserInfo'       => [$this, 'bloggerGetUserInfo'],
                'blogger.getPost'           => [$this, 'bloggerGetPost'],
                'blogger.getRecentPosts'    => [$this, 'bloggerGetRecentPosts'],
                'blogger.getTemplate'       => [$this, 'bloggerGetTemplate'],
                'blogger.setTemplate'       => [$this, 'bloggerSetTemplate'],
                'blogger.deletePost'        => [$this, 'bloggerDeletePost'],

                /** MetaWeblog API (with MT extensions to structs) */
                'metaWeblog.newPost'        => [$this, 'mwNewPost'],
                'metaWeblog.editPost'       => [$this, 'mwEditPost'],
                'metaWeblog.getPost'        => [$this, 'mwGetPost'],
                'metaWeblog.getRecentPosts' => [$this, 'mwGetRecentPosts'],
                'metaWeblog.getCategories'  => [$this, 'mwGetCategories'],
                'metaWeblog.newMediaObject' => [$this, 'mwNewMediaObject'],

                /** MetaWeblog API aliases for Blogger API */
                'metaWeblog.deletePost'     => [$this, 'bloggerDeletePost'],
                'metaWeblog.getTemplate'    => [$this, 'bloggerGetTemplate'],
                'metaWeblog.setTemplate'    => [$this, 'bloggerSetTemplate'],
                'metaWeblog.getUsersBlogs'  => [$this, 'bloggerGetUsersBlogs'],

                /** MovableType API */
                'mt.getCategoryList'        => [$this, 'mtGetCategoryList'],
                'mt.getRecentPostTitles'    => [$this, 'mtGetRecentPostTitles'],
                'mt.getPostCategories'      => [$this, 'mtGetPostCategories'],
                'mt.setPostCategories'      => [$this, 'mtSetPostCategories'],
                'mt.publishPost'            => [$this, 'mtPublishPost'],

                /** PingBack */
                'pingback.ping'             => [$this, 'pingbackPing'],
                // 'pingback.extensions.getPingbacks' => array($this,'pingbackExtensionsGetPingbacks'),
            ];

            if (1 == $this->options->allowXmlRpc) {
                unset($api['pingback.ping']);
            }

            /** 直接把初始化放到这里 */
            $server = new Server($api);
            $server->setHook($this);
            $server->serve();
        }
    }

    /**
     * 获取扩展字段
     *
     * @param Contents $content
     * @return array
     */
    private function getPostExtended(Contents $content): array
    {
        //根据客户端显示来判断是否显示html代码
        $agent = $this->request->getAgent();

        switch (true) {
            case false !== strpos($agent, 'wp-iphone'):   // wordpress iphone客户端
            case false !== strpos($agent, 'wp-blackberry'):  // 黑莓
            case false !== strpos($agent, 'wp-andriod'):  // andriod
            case false !== strpos($agent, 'plain-text'):  // 这是预留给第三方开发者的接口, 用于强行调用非所见即所得数据
            case $this->options->xmlrpcMarkdown:
                $text = $content->text;
                break;
            default:
                $text = $content->content;
                break;
        }

        $post = explode('<!--more-->', $text, 2);
        return [
            $this->options->xmlrpcMarkdown ? $post[0] : Common::fixHtml($post[0]),
            isset($post[1]) ? Common::fixHtml($post[1]) : null
        ];
    }

    /**
     * 将typecho的状态类型转换为wordperss的风格
     *
     * @param string $status typecho的状态
     * @param string $type 内容类型
     * @return string
     */
    private function typechoToWordpressStatus(string $status, string $type = 'post'): string
    {
        if ('post' == $type) {
            /** 文章状态 */
            switch ($status) {
                case 'waiting':
                    return 'pending';
                case 'publish':
                case 'draft':
                case 'private':
                    return $status;
                default:
                    return 'publish';
            }
        } elseif ('page' == $type) {
            switch ($status) {
                case 'publish':
                case 'draft':
                case 'private':
                    return $status;
                default:
                    return 'publish';
            }
        } elseif ('comment' == $type) {
            switch ($status) {
                case 'waiting':
                    return 'hold';
                case 'spam':
                    return $status;
                case 'publish':
                case 'approved':
                default:
                    return 'approve';
            }
        }

        return '';
    }

    /**
     * 将wordpress的状态类型转换为typecho的风格
     *
     * @access private
     * @param string $status wordpress的状态
     * @param string $type 内容类型
     * @return string
     */
    private function wordpressToTypechoStatus(string $status, string $type = 'post'): string
    {
        if ('post' == $type) {
            /** 文章状态 */
            switch ($status) {
                case 'pending':
                    return 'waiting';
                case 'publish':
                case 'draft':
                case 'private':
                case 'waiting':
                    return $status;
                default:
                    return 'publish';
            }
        } elseif ('page' == $type) {
            switch ($status) {
                case 'publish':
                case 'draft':
                case 'private':
                    return $status;
                default:
                    return 'publish';
            }
        } elseif ('comment' == $type) {
            switch ($status) {
                case 'hold':
                case 'waiting':
                    return 'waiting';
                case 'spam':
                    return $status;
                case 'approve':
                case 'publish':
                case 'approved':
                default:
                    return 'approved';
            }
        }

        return '';
    }
}
