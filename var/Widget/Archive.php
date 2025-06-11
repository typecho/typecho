<?php

namespace Widget;

use Typecho\Common;
use Typecho\Config;
use Typecho\Cookie;
use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Router;
use Typecho\Widget\Exception as WidgetException;
use Typecho\Widget\Helper\PageNavigator\Classic;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Contents;
use Widget\Comments\Ping;
use Widget\Contents\Attachment\Related as AttachmentRelated;
use Widget\Contents\Related\Author as AuthorRelated;
use Widget\Contents\From as ContentsFrom;
use Widget\Contents\Related as ContentsRelated;
use Widget\Metas\From as MetasFrom;
use Widget\Contents\Page\Rows as PageRows;
use Widget\Users\Author;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 内容的文章基类
 * 定义的css类
 * p.more:阅读全文链接所属段落
 *
 * @package Widget
 */
class Archive extends Contents
{
    /**
     * 调用的风格文件
     *
     * @var string
     */
    private string $themeFile;

    /**
     * 风格目录
     *
     * @var string
     */
    private string $themeDir;

    /**
     * 分页计算对象
     *
     * @var Query
     */
    private Query $countSql;

    /**
     * 所有文章个数
     *
     * @var int|null
     */
    private ?int $total = null;

    /**
     * 标记是否为从外部调用
     *
     * @var boolean
     */
    private bool $invokeFromOutside = false;

    /**
     * 是否由聚合调用
     *
     * @var boolean
     */
    private bool $invokeByFeed = false;

    /**
     * 当前页
     *
     * @var integer
     */
    private int $currentPage;

    /**
     * 生成分页的内容
     *
     * @var Router\ParamsDelegateInterface
     */
    private Router\ParamsDelegateInterface $pageRow;

    /**
     * RSS 2.0聚合地址
     *
     * @var string
     */
    private string $archiveFeedUrl;

    /**
     * RSS 1.0聚合地址
     *
     * @var string
     */
    private string $archiveFeedRssUrl;

    /**
     * ATOM 聚合地址
     *
     * @var string
     */
    private string $archiveFeedAtomUrl;

    /**
     * 本页关键字
     *
     * @var string|null
     */
    private ?string $archiveKeywords = null;

    /**
     * 本页描述
     *
     * @var string|null
     */
    private ?string $archiveDescription = null;

    /**
     * 归档标题
     *
     * @var string|null
     */
    private ?string $archiveTitle = null;

    /**
     * 归档地址
     *
     * @var string|null
     */
    private ?string $archiveUrl = null;

    /**
     * 归档类型
     *
     * @var string
     */
    private string $archiveType = 'index';

    /**
     * 是否为单一归档
     *
     * @var boolean
     */
    private bool $archiveSingle = false;

    /**
     * 是否为自定义首页, 主要为了标记自定义首页的情况
     *
     * (default value: false)
     *
     * @var boolean
     * @access private
     */
    private bool $makeSinglePageAsFrontPage = false;

    /**
     * 归档缩略名
     *
     * @access private
     * @var string
     */
    private string $archiveSlug;

    /**
     * @param Config $parameter
     * @throws \Exception
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault([
            'pageSize'       => $this->options->pageSize,
            'type'           => null,
            'checkPermalink' => true,
            'preview'        => false,
            'commentPage'    => 0
        ]);

        /** 用于判断是路由调用还是外部调用 */
        if (null == $parameter->type) {
            if (!isset(Router::$current)) {
                throw new WidgetException('Archive type is not set', 500);
            }

            $parameter->type = Router::$current;
        } else {
            $this->invokeFromOutside = true;
        }

        /** 用于判断是否为feed调用 */
        if ($parameter->isFeed) {
            $this->invokeByFeed = true;
        }

        /** 初始化皮肤路径 */
        $this->themeDir = rtrim($this->options->themeFile($this->options->theme), '/') . '/';
    }

    /**
     * 增加标题
     * @param string $archiveTitle 标题
     */
    public function addArchiveTitle(string $archiveTitle)
    {
        $current = $this->getArchiveTitle();
        $current[] = $archiveTitle;
        $this->setArchiveTitle($current);
    }

    /**
     * @return string
     */
    public function getArchiveTitle(): ?string
    {
        return $this->archiveTitle;
    }

    /**
     * @param string $archiveTitle the $archiveTitle to set
     */
    public function setArchiveTitle(string $archiveTitle)
    {
        $this->archiveTitle = $archiveTitle;
    }

    /**
     * @return string|null
     */
    public function getArchiveSlug(): ?string
    {
        return $this->archiveSlug;
    }

    /**
     * @param string $archiveSlug the $archiveSlug to set
     */
    public function setArchiveSlug(string $archiveSlug)
    {
        $this->archiveSlug = $archiveSlug;
    }

    /**
     * @return string|null
     */
    public function getArchiveType(): ?string
    {
        return $this->archiveType;
    }

    /**
     * @param string $archiveType the $archiveType to set
     */
    public function setArchiveType(string $archiveType)
    {
        $this->archiveType = $archiveType;
    }

    /**
     * @return string|null
     */
    public function getArchiveUrl(): ?string
    {
        return $this->archiveUrl;
    }

    /**
     * @param string|null $archiveUrl
     */
    public function setArchiveUrl(?string $archiveUrl): void
    {
        $this->archiveUrl = $archiveUrl;
    }

    /**
     * @return string|null
     */
    public function getArchiveDescription(): ?string
    {
        return $this->archiveDescription;
    }

    /**
     * @deprecated 1.3.0
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getArchiveDescription();
    }

    /**
     * @param string $archiveDescription the $description to set
     */
    public function setArchiveDescription(string $archiveDescription)
    {
        $this->archiveDescription = $archiveDescription;
    }

    /**
     * @return string|null
     */
    public function getArchiveKeywords(): ?string
    {
        return $this->archiveKeywords;
    }

    /**
     * @deprecated 1.3.0
     * @return string|null
     */
    public function getKeywords(): ?string
    {
        return $this->getArchiveKeywords();
    }

    /**
     * @param string $archiveKeywords the $keywords to set
     */
    public function setArchiveKeywords(string $archiveKeywords)
    {
        $this->archiveKeywords = $archiveKeywords;
    }

    /**
     * @return string
     */
    public function getArchiveFeedAtomUrl(): string
    {
        return $this->archiveFeedAtomUrl;
    }

    /**
     * @deprecated 1.3.0
     * @return string
     */
    public function getFeedAtomUrl(): string
    {
        return $this->getArchiveFeedAtomUrl();
    }

    /**
     * @param string $archiveFeedAtomUrl the $feedAtomUrl to set
     */
    public function setArchiveFeedAtomUrl(string $archiveFeedAtomUrl)
    {
        $this->archiveFeedAtomUrl = $archiveFeedAtomUrl;
    }

    /**
     * @return string
     */
    public function getArchiveFeedRssUrl(): string
    {
        return $this->archiveFeedRssUrl;
    }

    /**
     * @deprecated 1.3.0
     * @return string
     */
    public function getFeedRssUrl(): string
    {
        return $this->getArchiveFeedRssUrl();
    }

    /**
     * @param string $archiveFeedRssUrl the $feedRssUrl to set
     */
    public function setArchiveFeedRssUrl(string $archiveFeedRssUrl)
    {
        $this->archiveFeedRssUrl = $archiveFeedRssUrl;
    }

    /**
     * @return string
     */
    public function getArchiveFeedUrl(): string
    {
        return $this->archiveFeedUrl;
    }

    /**
     * @deprecated 1.3.0
     * @return string
     */
    public function getFeedUrl(): string
    {
        return $this->getArchiveFeedUrl();
    }

    /**
     * @param string $archiveFeedUrl the $feedUrl to set
     */
    public function setArchiveFeedUrl(string $archiveFeedUrl)
    {
        $this->archiveFeedUrl = $archiveFeedUrl;
    }

    /**
     * Get the value of feed
     * Deprecated since 1.3.0
     *
     * @deprecated 1.3.0
     * @return null
     */
    public function getFeed()
    {
        return null;
    }

    /**
     * Set the value of feed
     * Deprecated since 1.3.0
     *
     * @deprecated 1.3.0
     * @param null $feed
     */
    public function setFeed($feed)
    {
    }

    /**
     * @return Query|null
     */
    public function getCountSql(): ?Query
    {
        return $this->countSql;
    }

    /**
     * @param Query $countSql the $countSql to set
     */
    public function setCountSql($countSql)
    {
        $this->countSql = $countSql;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * _currentPage
     *
     * @return int
     */
    public function ____currentPage(): int
    {
        return $this->getCurrentPage();
    }

    /**
     * 获取页数
     *
     * @return integer
     */
    public function getTotalPage(): int
    {
        return ceil($this->getTotal() / $this->parameter->pageSize);
    }

    /**
     * @return int
     * @throws Db\Exception
     */
    public function getTotal(): int
    {
        if (!isset($this->total)) {
            $this->total = $this->size($this->countSql);
        }

        return $this->total;
    }

    /**
     * @param int $total the $total to set
     */
    public function setTotal(int $total)
    {
        $this->total = $total;
    }

    /**
     * @return string|null
     */
    public function getThemeFile(): ?string
    {
        return $this->themeFile;
    }

    /**
     * @param string $themeFile the $themeFile to set
     */
    public function setThemeFile(string $themeFile)
    {
        $this->themeFile = $themeFile;
    }

    /**
     * @return string|null
     */
    public function getThemeDir(): ?string
    {
        return $this->themeDir;
    }

    /**
     * @param string $themeDir the $themeDir to set
     */
    public function setThemeDir(string $themeDir)
    {
        $this->themeDir = $themeDir;
    }

    /**
     * 执行函数
     */
    public function execute()
    {
        /** 避免重复取数据 */
        if ($this->have()) {
            return;
        }

        $handles = [
            'index'              => 'indexHandle',
            'index_page'         => 'indexHandle',
            'archive'            => 'archiveEmptyHandle',
            'archive_page'       => 'archiveEmptyHandle',
            404                  => 'error404Handle',
            'single'             => 'singleHandle',
            'page'               => 'singleHandle',
            'post'               => 'singleHandle',
            'attachment'         => 'singleHandle',
            'category'           => 'categoryHandle',
            'category_page'      => 'categoryHandle',
            'tag'                => 'tagHandle',
            'tag_page'           => 'tagHandle',
            'author'             => 'authorHandle',
            'author_page'        => 'authorHandle',
            'archive_year'       => 'dateHandle',
            'archive_year_page'  => 'dateHandle',
            'archive_month'      => 'dateHandle',
            'archive_month_page' => 'dateHandle',
            'archive_day'        => 'dateHandle',
            'archive_day_page'   => 'dateHandle',
            'search'             => 'searchHandle',
            'search_page'        => 'searchHandle'
        ];

        /** 处理搜索结果跳转 */
        if ($this->request->is('s')) {
            $filterKeywords = $this->request->filter('search')->get('s');

            /** 跳转到搜索页 */
            if (null != $filterKeywords) {
                $this->response->redirect(
                    Router::url('search', ['keywords' => urlencode($filterKeywords)], $this->options->index)
                );
            }
        }

        /** 自定义首页功能 */
        $frontPage = $this->options->frontPage;
        if (!$this->invokeByFeed && ('index' == $this->parameter->type || 'index_page' == $this->parameter->type)) {
            //显示某个页面
            if (0 === strpos($frontPage, 'page:')) {
                // 对某些变量做hack
                $this->request->setParam('cid', intval(substr($frontPage, 5)));
                $this->parameter->type = 'page';
                $this->makeSinglePageAsFrontPage = true;
            } elseif (0 === strpos($frontPage, 'file:')) {
                // 显示某个文件
                $this->setThemeFile(substr($frontPage, 5));
                return;
            }
        }

        if ('recent' != $frontPage && $this->options->frontArchive) {
            $handles['archive'] = 'indexHandle';
            $handles['archive_page'] = 'indexHandle';
            $this->archiveType = 'front';
        }

        /** 初始化分页变量 */
        $this->currentPage = $this->request->filter('int')->get('page', 1);
        $hasPushed = false;
        $this->pageRow = new class implements Router\ParamsDelegateInterface
        {
            public function getRouterParam(string $key): string
            {
                return '{' . $key . '}';
            }
        };

        /** select初始化 */
        $select = self::pluginHandle()->trigger($selectPlugged)->call('select', $this);

        /** 定时发布功能 */
        if (!$selectPlugged) {
            $select = $this->select('table.contents.*');

            if (!$this->parameter->preview) {
                if ('post' == $this->parameter->type || 'page' == $this->parameter->type) {
                    if ($this->user->hasLogin()) {
                        $select->where(
                            'table.contents.status = ? OR table.contents.status = ? 
                                OR (table.contents.status = ? AND table.contents.authorId = ?)',
                            'publish',
                            'hidden',
                            'private',
                            $this->user->uid
                        );
                    } else {
                        $select->where(
                            'table.contents.status = ? OR table.contents.status = ?',
                            'publish',
                            'hidden'
                        );
                    }
                } else {
                    if ($this->user->hasLogin()) {
                        $select->where(
                            'table.contents.status = ? OR (table.contents.status = ? AND table.contents.authorId = ?)',
                            'publish',
                            'private',
                            $this->user->uid
                        );
                    } else {
                        $select->where('table.contents.status = ?', 'publish');
                    }
                }
                $select->where('table.contents.created < ?', $this->options->time);
            }
        }

        /** handle初始化 */
        self::pluginHandle()->call('handleInit', $this, $select);

        /** 初始化其它变量 */
        $this->archiveFeedUrl = $this->options->feedUrl;
        $this->archiveFeedRssUrl = $this->options->feedRssUrl;
        $this->archiveFeedAtomUrl = $this->options->feedAtomUrl;
        $this->archiveKeywords = $this->options->keywords;
        $this->archiveDescription = $this->options->description;
        $this->archiveUrl = $this->options->siteUrl;

        if (isset($handles[$this->parameter->type])) {
            $handle = $handles[$this->parameter->type];
            $this->{$handle}($select, $hasPushed);
        } else {
            $hasPushed = self::pluginHandle()->call('handle', $this->parameter->type, $this, $select);
        }

        /** 初始化皮肤函数 */
        $functionsFile = $this->themeDir . 'functions.php';
        if (
            (!$this->invokeFromOutside || $this->parameter->type == 404 || $this->parameter->preview)
            && file_exists($functionsFile)
        ) {
            require_once $functionsFile;
            if (function_exists('themeInit')) {
                themeInit($this);
            }
        }

        /** 如果已经提前压入则直接返回 */
        if ($hasPushed) {
            return;
        }

        /** 仅输出文章 */
        $this->countSql = clone $select;

        $select->order('table.contents.created', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);
        $this->query($select);

        /** 处理超出分页的情况 */
        if ($this->currentPage > 1 && !$this->have()) {
            throw new WidgetException(_t('请求的地址不存在'), 404);
        }
    }

    /**
     * 重载select
     *
     * @param mixed $fields
     * @return Query
     * @throws Db\Exception
     */
    public function select(...$fields): Query
    {
        if ($this->invokeByFeed) {
            // 对feed输出加入限制条件
            return parent::select(...$fields)->where('table.contents.allowFeed = ?', 1)
                ->where("table.contents.password IS NULL OR table.contents.password = ''");
        } else {
            return parent::select(...$fields);
        }
    }

    /**
     * 输出文章内容
     *
     * @param string $more 文章截取后缀
     */
    public function content($more = null)
    {
        parent::content($this->is('single') ? false : $more);
    }

    /**
     * 输出分页
     *
     * @param string $prev 上一页文字
     * @param string $next 下一页文字
     * @param int $splitPage 分割范围
     * @param string $splitWord 分割字符
     * @param string|array $template 展现配置信息
     * @throws Db\Exception|WidgetException
     */
    public function pageNav(
        string $prev = '&laquo;',
        string $next = '&raquo;',
        int $splitPage = 3,
        string $splitWord = '...',
        $template = ''
    ) {
        if ($this->have()) {
            $hasNav = false;
            $default = [
                'wrapTag'   => 'ol',
                'wrapClass' => 'page-navigator'
            ];

            if (is_string($template)) {
                parse_str($template, $config);
            } else {
                $config = $template ?: [];
            }

            $template = array_merge($default, $config);
            $total = $this->getTotal();
            $query = Router::url(
                $this->parameter->type .
                (false === strpos($this->parameter->type, '_page') ? '_page' : null),
                $this->pageRow,
                $this->options->index
            );

            self::pluginHandle()->trigger($hasNav)->call(
                'pageNav',
                $this->currentPage,
                $total,
                $this->parameter->pageSize,
                $prev,
                $next,
                $splitPage,
                $splitWord,
                $template,
                $query
            );

            if (!$hasNav && $total > $this->parameter->pageSize) {
                /** 使用盒状分页 */
                $nav = new Box(
                    $total,
                    $this->currentPage,
                    $this->parameter->pageSize,
                    $query
                );

                echo '<' . $template['wrapTag'] . (empty($template['wrapClass'])
                        ? '' : ' class="' . $template['wrapClass'] . '"') . '>';
                $nav->render($prev, $next, $splitPage, $splitWord, $template);
                echo '</' . $template['wrapTag'] . '>';
            }
        }
    }

    /**
     * 前一页
     *
     * @param string $word 链接标题
     * @param string $page 页面链接
     * @throws Db\Exception|WidgetException
     */
    public function pageLink(string $word = '&laquo; Previous Entries', string $page = 'prev')
    {
        static $nav;

        if ($this->have()) {
            if (!isset($nav)) {
                $query = Router::url(
                    $this->parameter->type .
                    (false === strpos($this->parameter->type, '_page') ? '_page' : null),
                    $this->pageRow,
                    $this->options->index
                );

                /** 使用盒状分页 */
                $nav = new Classic(
                    $this->getTotal(),
                    $this->currentPage,
                    $this->parameter->pageSize,
                    $query
                );
            }

            $nav->{$page}($word);
        }
    }

    /**
     * 获取评论归档对象
     *
     * @access public
     * @return \Widget\Comments\Archive
     */
    public function comments(): \Widget\Comments\Archive
    {
        $parameter = [
            'parentId'      => $this->hidden ? 0 : $this->cid,
            'parentContent' => $this,
            'respondId'     => $this->respondId,
            'commentPage'   => $this->parameter->commentPage,
            'allowComment'  => $this->allow('comment')
        ];

        return \Widget\Comments\Archive::alloc($parameter);
    }

    /**
     * 获取回响归档对象
     *
     * @return Ping
     */
    public function pings(): Ping
    {
        return Ping::alloc([
            'parentId'      => $this->hidden ? 0 : $this->cid,
            'parentContent' => $this->row,
            'allowPing'     => $this->allow('ping')
        ]);
    }

    /**
     * 获取附件对象
     *
     * @param integer $limit 最大个数
     * @param integer $offset 重新
     * @return AttachmentRelated
     */
    public function attachments(int $limit = 0, int $offset = 0): AttachmentRelated
    {
        return AttachmentRelated::allocWithAlias($this->cid . '-' . uniqid(), [
            'parentId' => $this->cid,
            'limit'    => $limit,
            'offset'   => $offset
        ]);
    }

    /**
     * 显示下一个内容的标题链接
     *
     * @param string $format 格式
     * @param string|null $default 如果没有下一篇,显示的默认文字
     * @param array $custom 定制化样式
     */
    public function theNext(string $format = '%s', ?string $default = null, array $custom = [])
    {
        $query = $this->select()->where(
            'table.contents.created > ? AND table.contents.created < ?',
            $this->created,
            $this->options->time
        )
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', $this->type)
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->order('table.contents.created', Db::SORT_ASC)
            ->limit(1);

        $this->theLink(
            ContentsFrom::allocWithAlias('next:' . $this->cid, ['query' => $query]),
            $format,
            $default,
            $custom
        );
    }

    /**
     * 显示上一个内容的标题链接
     *
     * @access public
     * @param string $format 格式
     * @param string|null $default 如果没有上一篇,显示的默认文字
     * @param array $custom 定制化样式
     * @return void
     */
    public function thePrev(string $format = '%s', ?string $default = null, array $custom = [])
    {
        $query = $this->select()->where('table.contents.created < ?', $this->created)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', $this->type)
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->order('table.contents.created', Db::SORT_DESC)
            ->limit(1);

        $this->theLink(
            ContentsFrom::allocWithAlias('prev:' . $this->cid, ['query' => $query]),
            $format,
            $default,
            $custom
        );
    }

    /**
     * @param Contents $content
     * @param string $format
     * @param string|null $default
     * @param array $custom
     * @return void
     */
    public function theLink(Contents $content, string $format = '%s', ?string $default = null, array $custom = [])
    {
        if ($content->have()) {
            $default = [
                'title'    => null,
                'tagClass' => null
            ];
            $custom = array_merge($default, $custom);

            $linkText = $custom['title'] ?? $content->title;
            $linkClass = empty($custom['tagClass']) ? '' : 'class="' . $custom['tagClass'] . '" ';
            $link = '<a ' . $linkClass . 'href="' . $content->permalink
                . '" title="' . $content->title . '">' . $linkText . '</a>';

            printf($format, $link);
        } else {
            echo $default;
        }
    }

    /**
     * 获取关联内容组件
     *
     * @param integer $limit 输出数量
     * @param string|null $type 关联类型
     * @return Contents
     */
    public function related(int $limit = 5, ?string $type = null): Contents
    {
        $type = strtolower($type ?? '');

        switch ($type) {
            case 'author':
                /** 如果访问权限被设置为禁止,则tag会被置为空 */
                return AuthorRelated::alloc(
                    ['cid' => $this->cid, 'type' => $this->type, 'author' => $this->author->uid, 'limit' => $limit]
                );
            default:
                /** 如果访问权限被设置为禁止,则tag会被置为空 */
                return ContentsRelated::alloc(
                    ['cid' => $this->cid, 'type' => $this->type, 'tags' => $this->tags, 'limit' => $limit]
                );
        }
    }

    /**
     * 输出头部元数据
     *
     * @param string|null $rule 规则
     */
    public function header(?string $rule = null)
    {
        $rules = [];
        $allows = [
            'description'  => htmlspecialchars($this->archiveDescription ?? ''),
            'keywords'     => htmlspecialchars($this->archiveKeywords ?? ''),
            'generator'    => $this->options->generator,
            'template'     => $this->options->theme,
            'pingback'     => $this->options->xmlRpcUrl,
            'xmlrpc'       => $this->options->xmlRpcUrl . '?rsd',
            'wlw'          => $this->options->xmlRpcUrl . '?wlw',
            'rss2'         => $this->archiveFeedUrl,
            'rss1'         => $this->archiveFeedRssUrl,
            'commentReply' => 1,
            'antiSpam'     => 1,
            'social'       => 1,
            'atom'         => $this->archiveFeedAtomUrl
        ];

        /** 头部是否输出聚合 */
        $allowFeed = !$this->is('single') || $this->allow('feed') || $this->makeSinglePageAsFrontPage;

        if (!empty($rule)) {
            parse_str($rule, $rules);
            $allows = array_merge($allows, $rules);
        }

        $allows = self::pluginHandle()->filter('headerOptions', $allows, $this);
        $title = (empty($this->archiveTitle) ? '' : $this->archiveTitle . ' &raquo; ') . $this->options->title;

        $header = ($this->is('single') && !$this->is('index')) ? '<link rel="canonical" href="' . $this->archiveUrl . '" />' . "\n" : '';

        if (!empty($allows['pingback']) && 2 == $this->options->allowXmlRpc) {
            $header .= '<link rel="pingback" href="' . $allows['pingback'] . '" />' . "\n";
        }

        if (!empty($allows['xmlrpc']) && 0 < $this->options->allowXmlRpc) {
            $header .= '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'
                . $allows['xmlrpc'] . '" />' . "\n";
        }

        if (!empty($allows['wlw']) && 0 < $this->options->allowXmlRpc) {
            $header .= '<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="'
                . $allows['wlw'] . '" />' . "\n";
        }

        if (!empty($allows['rss2']) && $allowFeed) {
            $header .= '<link rel="alternate" type="application/rss+xml" title="'
                . $title . ' &raquo; RSS 2.0" href="' . $allows['rss2'] . '" />' . "\n";
        }

        if (!empty($allows['rss1']) && $allowFeed) {
            $header .= '<link rel="alternate" type="application/rdf+xml" title="'
                . $title . ' &raquo; RSS 1.0" href="' . $allows['rss1'] . '" />' . "\n";
        }

        if (!empty($allows['atom']) && $allowFeed) {
            $header .= '<link rel="alternate" type="application/atom+xml" title="'
                . $title . ' &raquo; ATOM 1.0" href="' . $allows['atom'] . '" />' . "\n";
        }

        if (!empty($allows['description'])) {
            $header .= '<meta name="description" content="' . $allows['description'] . '" />' . "\n";
        }

        if (!empty($allows['keywords'])) {
            $header .= '<meta name="keywords" content="' . $allows['keywords'] . '" />' . "\n";
        }

        if (!empty($allows['generator'])) {
            $header .= '<meta name="generator" content="' . $allows['generator'] . '" />' . "\n";
        }

        if (!empty($allows['template'])) {
            $header .= '<meta name="template" content="' . $allows['template'] . '" />' . "\n";
        }

        if (!empty($allows['social'])) {
            $header .= '<meta property="og:type" content="' . ($this->is('single') ? 'article' : 'website') . '" />' . "\n";
            $header .= '<meta property="og:url" content="' . $this->archiveUrl . '" />' . "\n";
            $header .= '<meta name="twitter:title" property="og:title" itemprop="name" content="'
                . htmlspecialchars($this->archiveTitle ?? $this->options->title) . '" />' . "\n";
            $header .= '<meta name="twitter:description" property="og:description" itemprop="description" content="'
                . htmlspecialchars($this->archiveDescription ?? ($this->options->description ?? '')) . '" />' . "\n";
            $header .= '<meta property="og:site_name" content="' . htmlspecialchars($this->options->title) . '" />' . "\n";
            $header .= '<meta name="twitter:card" content="summary" />' . "\n";
            $header .= '<meta name="twitter:domain" content="' . $this->options->siteDomain . '" />' . "\n";
        }

        if ($this->options->commentsThreaded && $this->is('single')) {
            if ('' != $allows['commentReply']) {
                if (1 == $allows['commentReply']) {
                    $header .= <<<EOF
<script type="text/javascript">
(function () {
    window.TypechoComment = {
        dom : function (sel) {
            return document.querySelector(sel);
        },
        
        visiable: function (el, show) {
            el.style.display = show ? '' : 'none';
        },
    
        create : function (tag, attr) {
            const el = document.createElement(tag);
        
            for (const key in attr) {
                el.setAttribute(key, attr[key]);
            }
        
            return el;
        },
        
        inputParent: function (response, coid) {
            const form = 'form' === response.tagName ? response : response.querySelector('form');
            let input = form.querySelector('input[name=parent]');
            
            if (null == input && coid) {
                input = this.create('input', {
                    'type' : 'hidden',
                    'name' : 'parent'
                });

                form.appendChild(input);
            }
            
            if (coid) {
                input.setAttribute('value', coid);
            } else if (input) {
                input.parentNode.removeChild(input);
            }
        },
        
        getChild: function (root, node) {
            const parentNode = node.parentNode;
            
            if (parentNode === null) {
                return null;
            } else if (parentNode === root) {
                return node;
            } else {
                return this.getChild(root, parentNode);
            }
        },

        reply : function (htmlId, coid, btn) {
            const response = this.dom('#{$this->respondId}'),
                textarea = response.querySelector('textarea[name=text]'),
                comment = this.dom('#' + htmlId),
                child = this.getChild(comment, btn);

            this.inputParent(response, coid);

            if (this.dom('#{$this->respondId}-holder') === null) {
                const holder = this.create('div', {
                    'id' : '{$this->respondId}-holder'
                });

                response.parentNode.insertBefore(holder, response);
            }
            
            if (child) {
                comment.insertBefore(response, child.nextSibling);
            } else {
                comment.appendChild(response);
            }

            this.visiable(this.dom('#cancel-comment-reply-link'), true);

            if (null != textarea) {
                textarea.focus();
            }

            return false;
        },

        cancelReply : function () {
            const response = this.dom('#{$this->respondId}'),
                holder = this.dom('#{$this->respondId}-holder');

            this.inputParent(response, false);

            if (null === holder) {
                return true;
            }

            this.visiable(this.dom('#cancel-comment-reply-link'), false);
            holder.parentNode.insertBefore(response, holder);
            return false;
        }
    };
})();
</script>
EOF;
                } else {
                    $header .= '<script src="' . $allows['commentReply'] . '" type="text/javascript"></script>';
                }
            }
        }

        /** 反垃圾设置 */
        if ($this->options->commentsAntiSpam && $this->is('single')) {
            if ('' != $allows['antiSpam']) {
                if (1 == $allows['antiSpam']) {
                    $shuffled = Common::shuffleScriptVar($this->security->getToken($this->request->getRequestUrl()));
                    $header .= <<<EOF
<script type="text/javascript">
(function () {
    const events = ['scroll', 'mousemove', 'keyup', 'touchstart'];
    let added = false;

    document.addEventListener('DOMContentLoaded', function () {
        const response = document.querySelector('#{$this->respondId}');

        if (null != response) {
            const form = 'form' === response.tagName ? response : response.querySelector('form');
            const input = document.createElement('input');
            
            input.type = 'hidden';
            input.name = '_';
            input.value = {$shuffled};
 
            if (form) {
                function append() {
                    if (!added) {
                        form.appendChild(input);
                        added = true;
                    }
                }
            
                for (const event of events) {
                    window.addEventListener(event, append);
                }
            }
        }
    });
})();
</script>
EOF;
                } else {
                    $header .= '<script src="' . $allows['antiSpam'] . '" type="text/javascript"></script>';
                }
            }
        }

        /** 输出header */
        echo $header;

        /** 插件支持 */
        self::pluginHandle()->call('header', $header, $this);
    }

    /**
     * 支持页脚自定义
     */
    public function footer()
    {
        self::pluginHandle()->call('footer', $this);
    }

    /**
     * 输出cookie记忆别名
     *
     * @param string $cookieName 已经记忆的cookie名称
     * @param boolean $return 是否返回
     * @return string|void
     */
    public function remember(string $cookieName, bool $return = false)
    {
        $cookieName = strtolower($cookieName);
        if (!in_array($cookieName, ['author', 'mail', 'url'])) {
            return '';
        }

        $value = Cookie::get('__typecho_remember_' . $cookieName);
        if ($return) {
            return $value;
        } else {
            echo htmlspecialchars($value ?? '');
        }
    }

    /**
     * 输出归档标题
     *
     * @param mixed $defines
     * @param string $before
     * @param string $end
     */
    public function archiveTitle($defines = null, string $before = ' &raquo; ', string $end = '')
    {
        if ($this->archiveTitle) {
            $define = '%s';
            if (is_array($defines) && !empty($defines[$this->archiveType])) {
                $define = $defines[$this->archiveType];
            }

            echo $before . sprintf($define, $this->archiveTitle) . $end;
        }
    }

    /**
     * 输出关键字
     *
     * @param string $split
     * @param string $default
     */
    public function keywords(string $split = ',', string $default = '')
    {
        echo empty($this->archiveKeywords) ? $default : str_replace(',', $split, htmlspecialchars($this->archiveKeywords ?? ''));
    }

    /**
     * 获取主题文件
     *
     * @param string $fileName 主题文件
     */
    public function need(string $fileName)
    {
        require $this->themeDir . $fileName;
    }

    /**
     * 输出视图
     * @throws WidgetException
     */
    public function render()
    {
        /** 处理静态链接跳转 */
        $this->checkPermalink();

        /** 添加Pingback */
        if (2 == $this->options->allowXmlRpc) {
            $this->response->setHeader('X-Pingback', $this->options->xmlRpcUrl);
        }
        $valid = false;

        //~ 自定义模板
        if (!empty($this->themeFile)) {
            if (file_exists($this->themeDir . $this->themeFile)) {
                $valid = true;
            }
        }

        if (!$valid && !empty($this->archiveType)) {
            //~ 首先找具体路径, 比如 category/default.php
            if (!empty($this->archiveSlug)) {
                $themeFile = $this->archiveType . '/' . $this->archiveSlug . '.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $valid = true;
                }
            }

            //~ 然后找归档类型路径, 比如 category.php
            if (!$valid) {
                $themeFile = $this->archiveType . '.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $valid = true;
                }
            }

            //针对attachment的hook
            if (!$valid && 'attachment' == $this->archiveType) {
                if (file_exists($this->themeDir . 'page.php')) {
                    $this->themeFile = 'page.php';
                    $valid = true;
                } elseif (file_exists($this->themeDir . 'post.php')) {
                    $this->themeFile = 'post.php';
                    $valid = true;
                }
            }

            //~ 最后找归档路径, 比如 archive.php 或者 single.php
            if (!$valid && 'index' != $this->archiveType && 'front' != $this->archiveType) {
                $themeFile = $this->archiveSingle ? 'single.php' : 'archive.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $valid = true;
                }
            }

            if (!$valid) {
                $themeFile = 'index.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $valid = true;
                }
            }
        }

        /** 文件不存在 */
        if (!$valid) {
            throw new WidgetException(_t('文件不存在'), 500);
        }

        /** 挂接插件 */
        self::pluginHandle()->call('beforeRender', $this);

        /** 输出模板 */
        require_once $this->themeDir . $this->themeFile;

        /** 挂接插件 */
        self::pluginHandle()->call('afterRender', $this);
    }

    /**
     * 判断归档类型和名称
     *
     * @access public
     * @param string $archiveType 归档类型
     * @param string|null $archiveSlug 归档名称
     * @return boolean
     */
    public function is(string $archiveType, ?string $archiveSlug = null): bool
    {
        return ($archiveType == $this->archiveType ||
                (($this->archiveSingle ? 'single' : 'archive') == $archiveType && 'index' != $this->archiveType) ||
                ('index' == $archiveType && $this->makeSinglePageAsFrontPage) ||
                ('feed' == $archiveType && $this->invokeByFeed))
            && (empty($archiveSlug) || $archiveSlug == $this->archiveSlug);
    }

    /**
     * 提交查询
     *
     * @param mixed $select 查询对象
     * @throws Db\Exception
     */
    public function query($select)
    {
        self::pluginHandle()->trigger($queryPlugged)->call('query', $this, $select);
        if (!$queryPlugged) {
            $this->db->fetchAll($select, [$this, 'push']);
        }
    }

    /**
     * @return array
     */
    protected function ___directory(): array
    {
        if ('page' == $this->type) {
            $page = PageRows::alloc('current=' . $this->cid);
            $directory = $page->getAllParentsSlug($this->cid);
            $directory[] = $this->slug;

            return $directory;
        }

        return parent::___directory();
    }

    /**
     * 评论地址
     *
     * @return string
     */
    protected function ___commentUrl(): string
    {
        /** 生成反馈地址 */
        /** 评论 */
        $commentUrl = parent::___commentUrl();

        //不依赖js的父级评论
        $reply = $this->request->filter('int')->get('replyTo');
        if ($reply && $this->is('single')) {
            $commentUrl .= '?parent=' . $reply;
        }

        return $commentUrl;
    }

    /**
     * 检查链接是否正确
     */
    private function checkPermalink()
    {
        $type = $this->parameter->type;

        if (
            in_array($type, ['index', 404])
            || $this->makeSinglePageAsFrontPage    // 自定义首页不处理
            || !$this->parameter->checkPermalink
        ) { // 强制关闭
            return;
        }

        if ($this->archiveSingle) {
            $permalink = $this->permalink;
        } else {
            $path = Router::url(
                $type,
                new class ($this->currentPage, $this->pageRow) implements Router\ParamsDelegateInterface {
                    private Router\ParamsDelegateInterface $pageRow;
                    private int $currentPage;

                    public function __construct(int $currentPage, Router\ParamsDelegateInterface $pageRow)
                    {
                        $this->pageRow = $pageRow;
                        $this->currentPage = $currentPage;
                    }

                    public function getRouterParam(string $key): string
                    {
                        switch ($key) {
                            case 'page':
                                return $this->currentPage;
                            default:
                                return $this->pageRow->getRouterParam($key);
                        }
                    }
                }
            );

            $permalink = Common::url($path, $this->options->index);
        }

        $requestUrl = $this->request->getRequestUrl();

        $src = parse_url($permalink);
        $target = parse_url($requestUrl);

        if ($src['host'] != $target['host'] || urldecode($src['path']) != urldecode($target['path'])) {
            $this->response->redirect($permalink, true);
        }
    }

    /**
     * 处理index
     *
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     */
    private function indexHandle(Query $select, bool &$hasPushed)
    {
        $select->where('table.contents.type = ?', 'post');

        /** 插件接口 */
        self::pluginHandle()->call('indexHandle', $this, $select);
    }

    /**
     * 默认的非首页归档处理
     *
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @throws WidgetException
     */
    private function archiveEmptyHandle(Query $select, bool &$hasPushed)
    {
        throw new WidgetException(_t('请求的地址不存在'), 404);
    }

    /**
     * 404页面处理
     *
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     */
    private function error404Handle(Query $select, bool &$hasPushed)
    {
        /** 设置header */
        $this->response->setStatus(404);

        /** 设置标题 */
        $this->archiveTitle = _t('页面没找到');

        /** 设置归档类型 */
        $this->archiveType = 'archive';

        /** 设置归档缩略名 */
        $this->archiveSlug = 404;

        /** 设置归档模板 */
        $this->themeFile = '404.php';

        /** 设置单一归档类型 */
        $this->archiveSingle = false;

        $hasPushed = true;

        /** 插件接口 */
        self::pluginHandle()->call('error404Handle', $this, $select);
    }

    /**
     * 独立页处理
     *
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @throws WidgetException|Db\Exception
     */
    private function singleHandle(Query $select, bool &$hasPushed)
    {
        /** 将这两个设置提前是为了保证在调用query的plugin时可以在插件中使用is判断初步归档类型 */
        /** 如果需要更细判断，则可以使用singleHandle来实现 */
        $this->archiveSingle = true;

        /** 默认归档类型 */
        $this->archiveType = 'single';

        /** 匹配类型 */

        if ('single' != $this->parameter->type) {
            $select->where('table.contents.type = ?', $this->parameter->type);
        }

        /** 如果是单篇文章或独立页面 */
        if ($this->request->is('cid')) {
            $select->where('table.contents.cid = ?', $this->request->filter('int')->get('cid'));
        }

        /** 匹配缩略名 */
        if ($this->request->is('slug')) {
            $select->where('table.contents.slug = ?', $this->request->get('slug'));
        }

        if ($this->request->is('directory') && 'page' == $this->parameter->type) {
            $directory = explode('/', $this->request->get('directory'));
            $select->where('slug = ?', $directory[count($directory) - 1]);
        }

        /** 匹配时间 */
        if ($this->request->is('year')) {
            $year = $this->request->filter('int')->get('year');

            $fromMonth = 1;
            $toMonth = 12;

            $fromDay = 1;
            $toDay = 31;

            if ($this->request->is('month')) {
                $fromMonth = $this->request->filter('int')->get('month');
                $toMonth = $fromMonth;

                $toDay = date('t', mktime(0, 0, 0, $toMonth, 1, $year));

                if ($this->request->is('day')) {
                    $fromDay = $this->request->filter('int')->get('day');
                    $toDay = $fromDay;
                }
            }

            /** 获取起始GMT时间的unix时间戳 */
            $from = mktime(0, 0, 0, $fromMonth, $fromDay, $year)
                - $this->options->timezone + $this->options->serverTimezone;
            $to = mktime(23, 59, 59, $toMonth, $toDay, $year)
                - $this->options->timezone + $this->options->serverTimezone;
            $select->where('table.contents.created >= ? AND table.contents.created < ?', $from, $to);
        }

        /** 保存密码至cookie */
        $isPasswordPosted = false;

        if (
            $this->request->isPost()
            && $this->request->is('protectPassword')
            && !$this->parameter->preview
        ) {
            $this->security->protect();
            Cookie::set(
                'protectPassword_' . $this->request->filter('int')->get('protectCID'),
                $this->request->get('protectPassword')
            );

            $isPasswordPosted = true;
        }

        /** 匹配类型 */
        $select->limit(1);
        $this->query($select);

        if (!$this->have()) {
            if (!$this->invokeFromOutside) {
                /** 对没有索引情况下的判断 */
                throw new WidgetException(_t('请求的地址不存在'), 404);
            } else {
                $hasPushed = true;
                return;
            }
        }

        /** 密码表单判断逻辑 */
        if ($isPasswordPosted && $this->hidden) {
            throw new WidgetException(_t('对不起,您输入的密码错误'), 403);
        }

        /** 设置模板 */
        if ($this->template) {
            /** 应用自定义模板 */
            $this->themeFile = $this->template;
        }

        /** 设置头部feed */
        /** RSS 2.0 */

        //对自定义首页使用全局变量
        if (!$this->makeSinglePageAsFrontPage) {
            $this->archiveFeedUrl = $this->feedUrl;

            /** RSS 1.0 */
            $this->archiveFeedRssUrl = $this->feedRssUrl;

            /** ATOM 1.0 */
            $this->archiveFeedAtomUrl = $this->feedAtomUrl;

            /** 设置标题 */
            $this->archiveTitle = $this->title;

            /** 设置关键词 */
            $this->archiveKeywords = implode(',', array_column($this->tags, 'name'));

            /** 设置描述 */
            $this->archiveDescription = $this->plainExcerpt;
        }

        /** 设置归档类型 */
        if ($this->parameter->preview && $this->type === 'revision') {
            $parent = ContentsFrom::allocWithAlias($this->parent, ['cid' => $this->parent]);
            $this->archiveType = $parent->type;
        } else {
            [$this->archiveType] = explode('_', $this->type);
        }

        /** 设置归档缩略名 */
        $this->archiveSlug = ('post' == $this->archiveType || 'attachment' == $this->archiveType)
            ? $this->cid : $this->slug;

        /** 设置归档地址 */
        $this->archiveUrl = $this->permalink;

        /** 设置403头 */
        if ($this->hidden) {
            $this->response->setStatus(403);
        }

        $hasPushed = true;

        /** 插件接口 */
        self::pluginHandle()->call('singleHandle', $this, $select);
    }

    /**
     * 处理分类
     *
     * @param Query $select 查询对象
     * @throws WidgetException|Db\Exception
     */
    private function categoryHandle(Query $select)
    {
        /** 如果是分类 */
        $categorySelect = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->limit(1);

        $alias = 'category';

        if ($this->request->is('mid')) {
            $mid = $this->request->filter('int')->get('mid');
            $categorySelect->where('mid = ?', $mid);
            $alias .= ':' . $mid;
        }

        if ($this->request->is('slug')) {
            $slug = $this->request->get('slug');
            $categorySelect->where('slug = ?', $slug);
            $alias .= ':' . $slug;
        }

        if ($this->request->is('directory')) {
            $directory = explode('/', $this->request->get('directory'));
            $slug = $directory[count($directory) - 1];
            $categorySelect->where('slug = ?', $slug);
            $alias .= ':' . $slug;
        }

        $category = MetasFrom::allocWithAlias($alias, [
            'query' => $categorySelect
        ]);

        if (!$category->have()) {
            throw new WidgetException(_t('分类不存在'), 404);
        }

        if (isset($directory) && (implode('/', $directory) != implode('/', $category->directory))) {
            throw new WidgetException(_t('父级分类不存在'), 404);
        }

        $children = $category->getAllChildIds($category->mid);
        $children[] = $category->mid;

        /** fix sql92 by 70 */
        $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid IN ?', $children)
            ->where('table.contents.type = ?', 'post')
            ->group('table.contents.cid');

        /** 设置分页 */
        $this->pageRow = $category;

        /** 设置关键词 */
        $this->archiveKeywords = $category->name;

        /** 设置描述 */
        $this->archiveDescription = $category->description;

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->archiveFeedUrl = $category->feedUrl;

        /** RSS 1.0 */
        $this->archiveFeedRssUrl = $category->feedRssUrl;

        /** ATOM 1.0 */
        $this->archiveFeedAtomUrl = $category->feedAtomUrl;

        /** 设置标题 */
        $this->archiveTitle = $category->name;

        /** 设置归档类型 */
        $this->archiveType = 'category';

        /** 设置归档缩略名 */
        $this->archiveSlug = $category->slug;

        /** 设置归档地址 */
        $this->archiveUrl = $category->permalink;

        /** 插件接口 */
        self::pluginHandle()->call('categoryHandle', $this, $select);
    }

    /**
     * 处理标签
     *
     * @param Query $select 查询对象
     * @throws WidgetException|Db\Exception
     */
    private function tagHandle(Query $select)
    {
        $tagSelect = $this->db->select()->from('table.metas')
            ->where('type = ?', 'tag')->limit(1);

        $alias = 'tag';

        if ($this->request->is('mid')) {
            $mid = $this->request->filter('int')->get('mid');
            $tagSelect->where('mid = ?', $mid);
            $alias .= ':' . $mid;
        }

        if ($this->request->is('slug')) {
            $slug = $this->request->get('slug');
            $tagSelect->where('slug = ?', $slug);
            $alias .= ':' . $slug;
        }

        /** 如果是标签 */
        $tag = MetasFrom::allocWithAlias($alias, [
            'query' => $tagSelect
        ]);

        if (!$tag->have()) {
            throw new WidgetException(_t('标签不存在'), 404);
        }

        /** fix sql92 by 70 */
        $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid = ?', $tag->mid)
            ->where('table.contents.type = ?', 'post');

        /** 设置分页 */
        $this->pageRow = $tag;

        /** 设置关键词 */
        $this->archiveKeywords = $tag->name;

        /** 设置描述 */
        $this->archiveDescription = $tag->description;

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->archiveFeedUrl = $tag->feedUrl;

        /** RSS 1.0 */
        $this->archiveFeedRssUrl = $tag->feedRssUrl;

        /** ATOM 1.0 */
        $this->archiveFeedAtomUrl = $tag->feedAtomUrl;

        /** 设置标题 */
        $this->archiveTitle = $tag->name;

        /** 设置归档类型 */
        $this->archiveType = 'tag';

        /** 设置归档缩略名 */
        $this->archiveSlug = $tag->slug;

        /** 设置归档地址 */
        $this->archiveUrl = $tag->permalink;

        /** 插件接口 */
        self::pluginHandle()->call('tagHandle', $this, $select);
    }

    /**
     * 处理作者
     *
     * @param Query $select 查询对象
     * @throws WidgetException|Db\Exception
     */
    private function authorHandle(Query $select)
    {
        $uid = $this->request->filter('int')->get('uid');

        $author = Author::allocWithAlias('user:' . $uid, [
            'uid' => $uid
        ]);

        if (!$author->have()) {
            throw new WidgetException(_t('作者不存在'), 404);
        }

        $select->where('table.contents.authorId = ?', $uid)
            ->where('table.contents.type = ?', 'post');

        /** 设置分页 */
        $this->pageRow = $author;

        /** 设置关键词 */
        $this->archiveKeywords = $author->screenName;

        /** 设置描述 */
        $this->archiveDescription = $author->screenName;

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->archiveFeedUrl = $author->feedUrl;

        /** RSS 1.0 */
        $this->archiveFeedRssUrl = $author->feedRssUrl;

        /** ATOM 1.0 */
        $this->archiveFeedAtomUrl = $author->feedAtomUrl;

        /** 设置标题 */
        $this->archiveTitle = $author->screenName;

        /** 设置归档类型 */
        $this->archiveType = 'author';

        /** 设置归档缩略名 */
        $this->archiveSlug = $author->uid;

        /** 设置归档地址 */
        $this->archiveUrl = $author->permalink;

        /** 插件接口 */
        self::pluginHandle()->call('authorHandle', $this, $select);
    }

    /**
     * 处理日期
     *
     * @access private
     * @param Query $select 查询对象
     * @return void
     */
    private function dateHandle(Query $select)
    {
        /** 如果是按日期归档 */
        $year = $this->request->filter('int')->get('year');
        $month = $this->request->filter('int')->get('month');
        $day = $this->request->filter('int')->get('day');

        if (!empty($year) && !empty($month) && !empty($day)) {

            /** 如果按日归档 */
            $from = mktime(0, 0, 0, $month, $day, $year);
            $to = mktime(23, 59, 59, $month, $day, $year);

            /** 归档缩略名 */
            $this->archiveSlug = 'day';

            /** 设置标题 */
            $this->archiveTitle = _t('%d年%d月%d日', $year, $month, $day);
        } elseif (!empty($year) && !empty($month)) {

            /** 如果按月归档 */
            $from = mktime(0, 0, 0, $month, 1, $year);
            $to = mktime(23, 59, 59, $month, date('t', $from), $year);

            /** 归档缩略名 */
            $this->archiveSlug = 'month';

            /** 设置标题 */
            $this->archiveTitle = _t('%d年%d月', $year, $month);
        } elseif (!empty($year)) {

            /** 如果按年归档 */
            $from = mktime(0, 0, 0, 1, 1, $year);
            $to = mktime(23, 59, 59, 12, 31, $year);

            /** 归档缩略名 */
            $this->archiveSlug = 'year';

            /** 设置标题 */
            $this->archiveTitle = _t('%d年', $year);
        }

        $select->where('table.contents.created >= ?', $from - $this->options->timezone + $this->options->serverTimezone)
            ->where('table.contents.created <= ?', $to - $this->options->timezone + $this->options->serverTimezone)
            ->where('table.contents.type = ?', 'post');

        /** 设置归档类型 */
        $this->archiveType = 'date';

        /** 设置分页 */
        $this->pageRow = new class ($year, $month, $day) implements Router\ParamsDelegateInterface {
            private int $year;
            private int $month;
            private int $day;

            public function __construct(int $year, int $month, int $day)
            {
                $this->year = $year;
                $this->month = $month;
                $this->day = $day;
            }

            public function getRouterParam(string $key): string
            {
                switch ($key) {
                    case 'year':
                        return $this->year;
                    case 'month':
                        return str_pad($this->month, 2, '0', STR_PAD_LEFT);
                    case 'day':
                        return str_pad($this->day, 2, '0', STR_PAD_LEFT);
                    default:
                        return '{' . $key . '}';
                }
            }
        };

        /** 获取当前路由,过滤掉翻页情况 */
        $currentRoute = str_replace('_page', '', $this->parameter->type);

        /** RSS 2.0 */
        $this->archiveFeedUrl = Router::url($currentRoute, $this->pageRow, $this->options->feedUrl);

        /** RSS 1.0 */
        $this->archiveFeedRssUrl = Router::url($currentRoute, $this->pageRow, $this->options->feedRssUrl);

        /** ATOM 1.0 */
        $this->archiveFeedAtomUrl = Router::url($currentRoute, $this->pageRow, $this->options->feedAtomUrl);

        /** 设置归档地址 */
        $this->archiveUrl = Router::url($currentRoute, $this->pageRow, $this->options->index);

        /** 插件接口 */
        self::pluginHandle()->call('dateHandle', $this, $select);
    }

    /**
     * 处理搜索
     *
     * @access private
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @return void
     */
    private function searchHandle(Query $select, bool &$hasPushed)
    {
        /** 增加自定义搜索引擎接口 */
        //~ fix issue 40
        $keywords = $this->request->filter('url', 'search')->get('keywords');
        self::pluginHandle()->trigger($hasPushed)->call('search', $keywords, $this);

        if (!$hasPushed) {
            $searchQuery = '%' . str_replace(' ', '%', $keywords) . '%';

            /** 搜索无法进入隐私项保护归档 */
            if ($this->user->hasLogin()) {
                //~ fix issue 941
                $select->where("table.contents.password IS NULL
                 OR table.contents.password = '' OR table.contents.authorId = ?", $this->user->uid);
            } else {
                $select->where("table.contents.password IS NULL OR table.contents.password = ''");
            }

            $op = $this->db->getAdapter()->getDriver() == 'pgsql' ? 'ILIKE' : 'LIKE';

            $select->where("table.contents.title {$op} ? OR table.contents.text {$op} ?", $searchQuery, $searchQuery)
                ->where('table.contents.type = ?', 'post');
        }

        /** 设置关键词 */
        $this->archiveKeywords = $keywords;

        /** 设置分页 */
        $this->pageRow = new class ($keywords) implements Router\ParamsDelegateInterface {
            private string $keywords;

            public function __construct(string $keywords)
            {
                $this->keywords = $keywords;
            }

            public function getRouterParam(string $key): string
            {
                switch ($key) {
                    case 'keywords':
                        return urlencode($this->keywords);
                    default:
                        return '{' . $key . '}';
                }
            }
        };

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->archiveFeedUrl = Router::url('search', $this->pageRow, $this->options->feedUrl);

        /** RSS 1.0 */
        $this->archiveFeedRssUrl = Router::url('search', $this->pageRow, $this->options->feedAtomUrl);

        /** ATOM 1.0 */
        $this->archiveFeedAtomUrl = Router::url('search', $this->pageRow, $this->options->feedAtomUrl);

        /** 设置标题 */
        $this->archiveTitle = $keywords;

        /** 设置归档类型 */
        $this->archiveType = 'search';

        /** 设置归档缩略名 */
        $this->archiveSlug = $keywords;

        /** 设置归档地址 */
        $this->archiveUrl = Router::url('search', $this->pageRow, $this->options->index);

        /** 插件接口 */
        self::pluginHandle()->call('searchHandle', $this, $select);
    }
}
