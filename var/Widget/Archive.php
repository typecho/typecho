<?php

namespace Widget;

use Typecho\Common;
use Typecho\Config;
use Typecho\Cookie;
use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Feed;
use Typecho\Router;
use Typecho\Widget\Exception as WidgetException;
use Typecho\Widget\Helper\PageNavigator;
use Typecho\Widget\Helper\PageNavigator\Classic;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\Comments\Ping;
use Widget\Comments\Recent;
use Widget\Contents\Attachment\Related;
use Widget\Contents\Related\Author;
use Widget\Metas\Category\Rows;

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
    private $themeFile;

    /**
     * 风格目录
     *
     * @var string
     */
    private $themeDir;

    /**
     * 分页计算对象
     *
     * @var Query
     */
    private $countSql;

    /**
     * 所有文章个数
     *
     * @var integer
     */
    private $total = false;

    /**
     * 标记是否为从外部调用
     *
     * @var boolean
     */
    private $invokeFromOutside = false;

    /**
     * 是否由聚合调用
     *
     * @var boolean
     */
    private $invokeByFeed = false;

    /**
     * 当前页
     *
     * @var integer
     */
    private $currentPage;

    /**
     * 生成分页的内容
     *
     * @var array
     */
    private $pageRow = [];

    /**
     * 聚合器对象
     *
     * @var Feed
     */
    private $feed;

    /**
     * RSS 2.0聚合地址
     *
     * @var string
     */
    private $feedUrl;

    /**
     * RSS 1.0聚合地址
     *
     * @var string
     */
    private $feedRssUrl;

    /**
     * ATOM 聚合地址
     *
     * @var string
     */
    private $feedAtomUrl;

    /**
     * 本页关键字
     *
     * @var string
     */
    private $keywords;

    /**
     * 本页描述
     *
     * @var string
     */
    private $description;

    /**
     * 聚合类型
     *
     * @var string
     */
    private $feedType;

    /**
     * 聚合类型
     *
     * @var string
     */
    private $feedContentType;

    /**
     * 当前feed地址
     *
     * @var string
     */
    private $currentFeedUrl;

    /**
     * 归档标题
     *
     * @var string
     */
    private $archiveTitle = null;

    /**
     * 归档地址
     *
     * @var string|null
     */
    private $archiveUrl = null;

    /**
     * 归档类型
     *
     * @var string
     */
    private $archiveType = 'index';

    /**
     * 是否为单一归档
     *
     * @var string
     */
    private $archiveSingle = false;

    /**
     * 是否为自定义首页, 主要为了标记自定义首页的情况
     *
     * (default value: false)
     *
     * @var boolean
     * @access private
     */
    private $makeSinglePageAsFrontPage = false;

    /**
     * 归档缩略名
     *
     * @access private
     * @var string
     */
    private $archiveSlug;

    /**
     * 设置分页对象
     *
     * @access private
     * @var PageNavigator
     */
    private $pageNav;

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
            'preview'        => false
        ]);

        /** 用于判断是路由调用还是外部调用 */
        if (null == $parameter->type) {
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

        /** 处理feed模式 **/
        if ('feed' == $parameter->type) {
            $this->currentFeedUrl = '';

            /** 判断聚合类型 */
            switch (true) {
                case 0 === strpos($this->request->feed, '/rss/') || '/rss' == $this->request->feed:
                    /** 如果是RSS1标准 */
                    $this->request->feed = substr($this->request->feed, 4);
                    $this->feedType = Feed::RSS1;
                    $this->currentFeedUrl = $this->options->feedRssUrl;
                    $this->feedContentType = 'application/rdf+xml';
                    break;
                case 0 === strpos($this->request->feed, '/atom/') || '/atom' == $this->request->feed:
                    /** 如果是ATOM标准 */
                    $this->request->feed = substr($this->request->feed, 5);
                    $this->feedType = Feed::ATOM1;
                    $this->currentFeedUrl = $this->options->feedAtomUrl;
                    $this->feedContentType = 'application/atom+xml';
                    break;
                default:
                    $this->feedType = Feed::RSS2;
                    $this->currentFeedUrl = $this->options->feedUrl;
                    $this->feedContentType = 'application/rss+xml';
                    break;
            }

            $feedQuery = $this->request->feed;
            //$parameter->type = Router::$current;
            //$this->request->setParams($params);

            if ('/comments/' == $feedQuery || '/comments' == $feedQuery) {
                /** 专为feed使用的hack */
                $parameter->type = 'comments';
                $this->options->feedUrl = $this->options->commentsFeedUrl;
                $this->options->feedRssUrl = $this->options->commentsFeedRssUrl;
                $this->options->feedAtomUrl = $this->options->commentsFeedAtomUrl;
            } else {
                $matched = Router::match($this->request->feed, 'pageSize=10&isFeed=1');
                if ($matched instanceof Archive) {
                    $this->import($matched);
                } else {
                    throw new WidgetException(_t('聚合页不存在'), 404);
                }
            }

            /** 初始化聚合器 */
            $this->setFeed(new Feed(Common::VERSION, $this->feedType, $this->options->charset, _t('zh-CN')));

            /** 默认输出10则文章 **/
            $parameter->pageSize = 10;
        }
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
     * 获取分页对象
     * @return array
     */
    public function getPageRow(): array
    {
        return $this->pageRow;
    }

    /**
     * 设置分页对象
     * @param array $pageRow
     */
    public function setPageRow(array $pageRow)
    {
        $this->pageRow = $pageRow;
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
    public function getArchiveSingle(): ?string
    {
        return $this->archiveSingle;
    }

    /**
     * @param string $archiveSingle the $archiveSingle to set
     */
    public function setArchiveSingle(string $archiveSingle)
    {
        $this->archiveSingle = $archiveSingle;
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
    public function getFeedType(): ?string
    {
        return $this->feedType;
    }

    /**
     * @param string $feedType the $feedType to set
     */
    public function setFeedType(string $feedType)
    {
        $this->feedType = $feedType;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description the $description to set
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords the $keywords to set
     */
    public function setKeywords(string $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string
     */
    public function getFeedAtomUrl(): string
    {
        return $this->feedAtomUrl;
    }

    /**
     * @param string $feedAtomUrl the $feedAtomUrl to set
     */
    public function setFeedAtomUrl(string $feedAtomUrl)
    {
        $this->feedAtomUrl = $feedAtomUrl;
    }

    /**
     * @return string
     */
    public function getFeedRssUrl(): string
    {
        return $this->feedRssUrl;
    }

    /**
     * @param string $feedRssUrl the $feedRssUrl to set
     */
    public function setFeedRssUrl(string $feedRssUrl)
    {
        $this->feedRssUrl = $feedRssUrl;
    }

    /**
     * @return string
     */
    public function getFeedUrl(): string
    {
        return $this->feedUrl;
    }

    /**
     * @param string $feedUrl the $feedUrl to set
     */
    public function setFeedUrl(string $feedUrl)
    {
        $this->feedUrl = $feedUrl;
    }

    /**
     * @return Feed
     */
    public function getFeed(): Feed
    {
        return $this->feed;
    }

    /**
     * @param Feed $feed the $feed to set
     */
    public function setFeed(Feed $feed)
    {
        $this->feed = $feed;
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
        if (false === $this->total) {
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
            'comment_page'       => 'singleHandle',
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
        if (isset($this->request->s)) {
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
        $this->currentPage = $this->request->filter('int')->page ?? 1;
        $hasPushed = false;

        /** select初始化 */
        $select = self::pluginHandle()->trigger($selectPlugged)->select($this);

        /** 定时发布功能 */
        if (!$selectPlugged) {
            if ($this->parameter->preview) {
                $select = $this->select();
            } else {
                if ('post' == $this->parameter->type || 'page' == $this->parameter->type) {
                    if ($this->user->hasLogin()) {
                        $select = $this->select()->where(
                            'table.contents.status = ? OR table.contents.status = ? 
                                OR (table.contents.status = ? AND table.contents.authorId = ?)',
                            'publish',
                            'hidden',
                            'private',
                            $this->user->uid
                        );
                    } else {
                        $select = $this->select()->where(
                            'table.contents.status = ? OR table.contents.status = ?',
                            'publish',
                            'hidden'
                        );
                    }
                } else {
                    if ($this->user->hasLogin()) {
                        $select = $this->select()->where(
                            'table.contents.status = ? OR (table.contents.status = ? AND table.contents.authorId = ?)',
                            'publish',
                            'private',
                            $this->user->uid
                        );
                    } else {
                        $select = $this->select()->where('table.contents.status = ?', 'publish');
                    }
                }
                $select->where('table.contents.created < ?', $this->options->time);
            }
        }

        /** handle初始化 */
        self::pluginHandle()->handleInit($this, $select);

        /** 初始化其它变量 */
        $this->feedUrl = $this->options->feedUrl;
        $this->feedRssUrl = $this->options->feedRssUrl;
        $this->feedAtomUrl = $this->options->feedAtomUrl;
        $this->keywords = $this->options->keywords;
        $this->description = $this->options->description;
        $this->archiveUrl = $this->options->siteUrl;

        if (isset($handles[$this->parameter->type])) {
            $handle = $handles[$this->parameter->type];
            $this->{$handle}($select, $hasPushed);
        } else {
            $hasPushed = self::pluginHandle()->handle($this->parameter->type, $this, $select);
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
     * @return Query
     * @throws Db\Exception
     */
    public function select(): Query
    {
        if ($this->invokeByFeed) {
            // 对feed输出加入限制条件
            return parent::select()->where('table.contents.allowFeed = ?', 1)
                ->where("table.contents.password IS NULL OR table.contents.password = ''");
        } else {
            return parent::select();
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

            self::pluginHandle()->trigger($hasNav)->pageNav(
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
        if ($this->have()) {
            if (empty($this->pageNav)) {
                $query = Router::url(
                    $this->parameter->type .
                    (false === strpos($this->parameter->type, '_page') ? '_page' : null),
                    $this->pageRow,
                    $this->options->index
                );

                /** 使用盒状分页 */
                $this->pageNav = new Classic(
                    $this->getTotal(),
                    $this->currentPage,
                    $this->parameter->pageSize,
                    $query
                );
            }

            $this->pageNav->{$page}($word);
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
            'parentContent' => $this->row,
            'respondId'     => $this->respondId,
            'commentPage'   => $this->request->filter('int')->commentPage,
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
     * @return Related
     */
    public function attachments(int $limit = 0, int $offset = 0): Related
    {
        return Related::allocWithAlias($this->cid . '-' . uniqid(), [
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
        $content = $this->db->fetchRow($this->select()->where(
            'table.contents.created > ? AND table.contents.created < ?',
            $this->created,
            $this->options->time
        )
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', $this->type)
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->order('table.contents.created', Db::SORT_ASC)
            ->limit(1));

        if ($content) {
            $content = $this->filter($content);
            $default = [
                'title'    => null,
                'tagClass' => null
            ];
            $custom = array_merge($default, $custom);
            extract($custom);

            $linkText = empty($title) ? $content['title'] : $title;
            $linkClass = empty($tagClass) ? '' : 'class="' . $tagClass . '" ';
            $link = '<a ' . $linkClass . 'href="' . $content['permalink']
                . '" title="' . $content['title'] . '">' . $linkText . '</a>';

            printf($format, $link);
        } else {
            echo $default;
        }
    }

    /**
     * 显示上一个内容的标题链接
     *
     * @access public
     * @param string $format 格式
     * @param string $default 如果没有上一篇,显示的默认文字
     * @param array $custom 定制化样式
     * @return void
     */
    public function thePrev($format = '%s', $default = null, $custom = [])
    {
        $content = $this->db->fetchRow($this->select()->where('table.contents.created < ?', $this->created)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', $this->type)
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->order('table.contents.created', Db::SORT_DESC)
            ->limit(1));

        if ($content) {
            $content = $this->filter($content);
            $default = [
                'title'    => null,
                'tagClass' => null
            ];
            $custom = array_merge($default, $custom);
            extract($custom);

            $linkText = empty($title) ? $content['title'] : $title;
            $linkClass = empty($tagClass) ? '' : 'class="' . $tagClass . '" ';
            $link = '<a ' . $linkClass . 'href="' . $content['permalink'] . '" title="' . $content['title'] . '">' . $linkText . '</a>';

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
                return Author::alloc(
                    ['cid' => $this->cid, 'type' => $this->type, 'author' => $this->author->uid, 'limit' => $limit]
                );
            default:
                /** 如果访问权限被设置为禁止,则tag会被置为空 */
                return \Widget\Contents\Related::alloc(
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
            'description'  => htmlspecialchars($this->description ?? ''),
            'keywords'     => htmlspecialchars($this->keywords ?? ''),
            'generator'    => $this->options->generator,
            'template'     => $this->options->theme,
            'pingback'     => $this->options->xmlRpcUrl,
            'xmlrpc'       => $this->options->xmlRpcUrl . '?rsd',
            'wlw'          => $this->options->xmlRpcUrl . '?wlw',
            'rss2'         => $this->feedUrl,
            'rss1'         => $this->feedRssUrl,
            'commentReply' => 1,
            'antiSpam'     => 1,
            'atom'         => $this->feedAtomUrl
        ];

        /** 头部是否输出聚合 */
        $allowFeed = !$this->is('single') || $this->allow('feed') || $this->makeSinglePageAsFrontPage;

        if (!empty($rule)) {
            parse_str($rule, $rules);
            $allows = array_merge($allows, $rules);
        }

        $allows = self::pluginHandle()->headerOptions($allows, $this);
        $title = (empty($this->archiveTitle) ? '' : $this->archiveTitle . ' &raquo; ') . $this->options->title;

        $header = '';
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

        if ($this->options->commentsThreaded && $this->is('single')) {
            if ('' != $allows['commentReply']) {
                if (1 == $allows['commentReply']) {
                    $header .= "<script type=\"text/javascript\">
(function () {
    window.TypechoComment = {
        dom : function (id) {
            return document.getElementById(id);
        },
    
        create : function (tag, attr) {
            var el = document.createElement(tag);
        
            for (var key in attr) {
                el.setAttribute(key, attr[key]);
            }
        
            return el;
        },

        reply : function (cid, coid) {
            var comment = this.dom(cid), parent = comment.parentNode,
                response = this.dom('" . $this->respondId . "'), input = this.dom('comment-parent'),
                form = 'form' == response.tagName ? response : response.getElementsByTagName('form')[0],
                textarea = response.getElementsByTagName('textarea')[0];

            if (null == input) {
                input = this.create('input', {
                    'type' : 'hidden',
                    'name' : 'parent',
                    'id'   : 'comment-parent'
                });

                form.appendChild(input);
            }

            input.setAttribute('value', coid);

            if (null == this.dom('comment-form-place-holder')) {
                var holder = this.create('div', {
                    'id' : 'comment-form-place-holder'
                });

                response.parentNode.insertBefore(holder, response);
            }

            comment.appendChild(response);
            this.dom('cancel-comment-reply-link').style.display = '';

            if (null != textarea && 'text' == textarea.name) {
                textarea.focus();
            }

            return false;
        },

        cancelReply : function () {
            var response = this.dom('{$this->respondId}'),
            holder = this.dom('comment-form-place-holder'), input = this.dom('comment-parent');

            if (null != input) {
                input.parentNode.removeChild(input);
            }

            if (null == holder) {
                return true;
            }

            this.dom('cancel-comment-reply-link').style.display = 'none';
            holder.parentNode.insertBefore(response, holder);
            return false;
        }
    };
})();
</script>
";
                } else {
                    $header .= '<script src="' . $allows['commentReply'] . '" type="text/javascript"></script>';
                }
            }
        }

        /** 反垃圾设置 */
        if ($this->options->commentsAntiSpam && $this->is('single')) {
            if ('' != $allows['antiSpam']) {
                if (1 == $allows['antiSpam']) {
                    $header .= "<script type=\"text/javascript\">
(function () {
    var event = document.addEventListener ? {
        add: 'addEventListener',
        triggers: ['scroll', 'mousemove', 'keyup', 'touchstart'],
        load: 'DOMContentLoaded'
    } : {
        add: 'attachEvent',
        triggers: ['onfocus', 'onmousemove', 'onkeyup', 'ontouchstart'],
        load: 'onload'
    }, added = false;

    document[event.add](event.load, function () {
        var r = document.getElementById('{$this->respondId}'),
            input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_';
        input.value = " . Common::shuffleScriptVar($this->security->getToken($this->request->getRequestUrl())) . "

        if (null != r) {
            var forms = r.getElementsByTagName('form');
            if (forms.length > 0) {
                function append() {
                    if (!added) {
                        forms[0].appendChild(input);
                        added = true;
                    }
                }
            
                for (var i = 0; i < event.triggers.length; i ++) {
                    var trigger = event.triggers[i];
                    document[event.add](trigger, append);
                    window[event.add](trigger, append);
                }
            }
        }
    });
})();
</script>";
                } else {
                    $header .= '<script src="' . $allows['antiSpam'] . '" type="text/javascript"></script>';
                }
            }
        }

        /** 输出header */
        echo $header;

        /** 插件支持 */
        self::pluginHandle()->header($header, $this);
    }

    /**
     * 支持页脚自定义
     */
    public function footer()
    {
        self::pluginHandle()->footer($this);
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
        echo empty($this->keywords) ? $default : str_replace(',', $split, htmlspecialchars($this->keywords ?? ''));
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
        $validated = false;

        //~ 自定义模板
        if (!empty($this->themeFile)) {
            if (file_exists($this->themeDir . $this->themeFile)) {
                $validated = true;
            }
        }

        if (!$validated && !empty($this->archiveType)) {
            //~ 首先找具体路径, 比如 category/default.php
            if (!$validated && !empty($this->archiveSlug)) {
                $themeFile = $this->archiveType . '/' . $this->archiveSlug . '.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }

            //~ 然后找归档类型路径, 比如 category.php
            if (!$validated) {
                $themeFile = $this->archiveType . '.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }

            //针对attachment的hook
            if (!$validated && 'attachment' == $this->archiveType) {
                if (file_exists($this->themeDir . 'page.php')) {
                    $this->themeFile = 'page.php';
                    $validated = true;
                } elseif (file_exists($this->themeDir . 'post.php')) {
                    $this->themeFile = 'post.php';
                    $validated = true;
                }
            }

            //~ 最后找归档路径, 比如 archive.php 或者 single.php
            if (!$validated && 'index' != $this->archiveType && 'front' != $this->archiveType) {
                $themeFile = $this->archiveSingle ? 'single.php' : 'archive.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }

            if (!$validated) {
                $themeFile = 'index.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }
        }

        /** 文件不存在 */
        if (!$validated) {
            throw new WidgetException(_t('文件不存在'), 500);
        }

        /** 挂接插件 */
        self::pluginHandle()->beforeRender($this);

        /** 输出模板 */
        require_once $this->themeDir . $this->themeFile;

        /** 挂接插件 */
        self::pluginHandle()->afterRender($this);
    }

    /**
     * 输出feed
     *
     * @throws WidgetException
     */
    public function feed()
    {
        if ($this->feedType == Feed::RSS1) {
            $feedUrl = $this->feedRssUrl;
        } elseif ($this->feedType == Feed::ATOM1) {
            $feedUrl = $this->feedAtomUrl;
        } else {
            $feedUrl = $this->feedUrl;
        }

        $this->checkPermalink($feedUrl);

        $this->feed->setSubTitle($this->description);
        $this->feed->setFeedUrl($feedUrl);
        $this->feed->setBaseUrl($this->archiveUrl);

        if ($this->is('single') || 'comments' == $this->parameter->type) {
            $this->feed->setTitle(_t(
                '%s 的评论',
                $this->options->title . ($this->archiveTitle ? ' - ' . $this->archiveTitle : null)
            ));

            if ('comments' == $this->parameter->type) {
                $comments = Recent::alloc('pageSize=10');
            } else {
                $comments = Recent::alloc('pageSize=10&parentId=' . $this->cid);
            }

            while ($comments->next()) {
                $suffix = self::pluginHandle()->trigger($plugged)->commentFeedItem($this->feedType, $comments);
                if (!$plugged) {
                    $suffix = null;
                }

                $this->feed->addItem([
                    'title'   => $comments->author,
                    'content' => $comments->content,
                    'date'    => $comments->created,
                    'link'    => $comments->permalink,
                    'author'  => (object)[
                        'screenName' => $comments->author,
                        'url'        => $comments->url,
                        'mail'       => $comments->mail
                    ],
                    'excerpt' => strip_tags($comments->content),
                    'suffix'  => $suffix
                ]);
            }
        } else {
            $this->feed->setTitle($this->options->title . ($this->archiveTitle ? ' - ' . $this->archiveTitle : null));

            while ($this->next()) {
                $suffix = self::pluginHandle()->trigger($plugged)->feedItem($this->feedType, $this);
                if (!$plugged) {
                    $suffix = null;
                }

                $feedUrl = '';
                if (Feed::RSS2 == $this->feedType) {
                    $feedUrl = $this->feedUrl;
                } elseif (Feed::RSS1 == $this->feedType) {
                    $feedUrl = $this->feedRssUrl;
                } elseif (Feed::ATOM1 == $this->feedType) {
                    $feedUrl = $this->feedAtomUrl;
                }

                $this->feed->addItem([
                    'title'           => $this->title,
                    'content'         => $this->options->feedFullText ? $this->content
                        : (false !== strpos($this->text, '<!--more-->') ? $this->excerpt .
                            "<p class=\"more\"><a href=\"{$this->permalink}\" title=\"{$this->title}\">[...]</a></p>"
                            : $this->content),
                    'date'            => $this->created,
                    'link'            => $this->permalink,
                    'author'          => $this->author,
                    'excerpt'         => $this->___description(),
                    'comments'        => $this->commentsNum,
                    'commentsFeedUrl' => $feedUrl,
                    'suffix'          => $suffix
                ]);
            }
        }

        $this->response->setContentType($this->feedContentType);
        echo (string) $this->feed;
    }

    /**
     * 判断归档类型和名称
     *
     * @access public
     * @param string $archiveType 归档类型
     * @param string|null $archiveSlug 归档名称
     * @return boolean
     */
    public function is(string $archiveType, ?string $archiveSlug = null)
    {
        return ($archiveType == $this->archiveType ||
                (($this->archiveSingle ? 'single' : 'archive') == $archiveType && 'index' != $this->archiveType) ||
                ('index' == $archiveType && $this->makeSinglePageAsFrontPage))
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
        self::pluginHandle()->trigger($queryPlugged)->query($this, $select);
        if (!$queryPlugged) {
            $this->db->fetchAll($select, [$this, 'push']);
        }
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
        $reply = $this->request->filter('int')->replyTo;
        if ($reply && $this->is('single')) {
            $commentUrl .= '?parent=' . $reply;
        }

        return $commentUrl;
    }

    /**
     * 导入对象
     *
     * @param Archive $widget 需要导入的对象
     */
    private function import(Archive $widget)
    {
        $currentProperties = get_object_vars($this);

        foreach ($currentProperties as $name => $value) {
            if (false !== strpos('|request|response|parameter|feed|feedType|currentFeedUrl|', '|' . $name . '|')) {
                continue;
            }

            if (isset($widget->{$name})) {
                $this->{$name} = $widget->{$name};
            } else {
                $method = ucfirst($name);
                $setMethod = 'set' . $method;
                $getMethod = 'get' . $method;

                if (
                    method_exists($this, $setMethod)
                    && method_exists($widget, $getMethod)
                ) {
                    $value = $widget->{$getMethod}();

                    if ($value !== null) {
                        $this->{$setMethod}($widget->{$getMethod}());
                    }
                }
            }
        }
    }

    /**
     * 检查链接是否正确
     *
     * @param string|null $permalink
     */
    private function checkPermalink(?string $permalink = null)
    {
        if (!isset($permalink)) {
            $type = $this->parameter->type;

            if (
                in_array($type, ['index', 'comment_page', 404])
                || $this->makeSinglePageAsFrontPage    // 自定义首页不处理
                || !$this->parameter->checkPermalink
            ) { // 强制关闭
                return;
            }

            if ($this->archiveSingle) {
                $permalink = $this->permalink;
            } else {
                $value = array_merge($this->pageRow, [
                    'page' => $this->currentPage
                ]);

                $path = Router::url($type, $value);
                $permalink = Common::url($path, $this->options->index);
            }
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
        self::pluginHandle()->indexHandle($this, $select);
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
        self::pluginHandle()->error404Handle($this, $select);
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
        if ('comment_page' == $this->parameter->type) {
            $params = [];
            $matched = Router::match($this->request->permalink);

            if ($matched && $matched instanceof Archive && $matched->is('single')) {
                $this->import($matched);
                $hasPushed = true;
                return;
            }
        }

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
        if (isset($this->request->cid)) {
            $select->where('table.contents.cid = ?', $this->request->filter('int')->cid);
        }

        /** 匹配缩略名 */
        if (isset($this->request->slug) && !$this->parameter->preview) {
            $select->where('table.contents.slug = ?', $this->request->slug);
        }

        /** 匹配时间 */
        if (isset($this->request->year) && !$this->parameter->preview) {
            $year = $this->request->filter('int')->year;

            $fromMonth = 1;
            $toMonth = 12;

            $fromDay = 1;
            $toDay = 31;

            if (isset($this->request->month)) {
                $fromMonth = $this->request->filter('int')->month;
                $toMonth = $fromMonth;

                $fromDay = 1;
                $toDay = date('t', mktime(0, 0, 0, $toMonth, 1, $year));

                if (isset($this->request->day)) {
                    $fromDay = $this->request->filter('int')->day;
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
            && isset($this->request->protectPassword)
            && !$this->parameter->preview
        ) {
            $this->security->protect();
            Cookie::set(
                'protectPassword_' . $this->request->filter('int')->protectCID,
                $this->request->protectPassword
            );

            $isPasswordPosted = true;
        }

        /** 匹配类型 */
        $select->limit(1);
        $this->query($select);

        if (
            !$this->have()
            || (isset($this->request->category)
                && $this->category != $this->request->category && !$this->parameter->preview)
            || (isset($this->request->directory)
                && $this->request->directory != implode('/', $this->directory) && !$this->parameter->preview)
        ) {
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
            $this->feedUrl = $this->row['feedUrl'];

            /** RSS 1.0 */
            $this->feedRssUrl = $this->row['feedRssUrl'];

            /** ATOM 1.0 */
            $this->feedAtomUrl = $this->row['feedAtomUrl'];

            /** 设置标题 */
            $this->archiveTitle = $this->title;

            /** 设置关键词 */
            $this->keywords = implode(',', array_column($this->tags, 'name'));

            /** 设置描述 */
            $this->description = $this->___description();
        }

        /** 设置归档类型 */
        [$this->archiveType] = explode('_', $this->type);

        /** 设置归档缩略名 */
        $this->archiveSlug = ('post' == $this->type || 'attachment' == $this->type) ? $this->cid : $this->slug;

        /** 设置归档地址 */
        $this->archiveUrl = $this->permalink;

        /** 设置403头 */
        if ($this->hidden) {
            $this->response->setStatus(403);
        }

        $hasPushed = true;

        /** 插件接口 */
        self::pluginHandle()->singleHandle($this, $select);
    }

    /**
     * 处理分类
     *
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @throws WidgetException|Db\Exception
     */
    private function categoryHandle(Query $select, bool &$hasPushed)
    {
        /** 如果是分类 */
        $categorySelect = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->limit(1);

        if (isset($this->request->mid)) {
            $categorySelect->where('mid = ?', $this->request->filter('int')->mid);
        }

        if (isset($this->request->slug)) {
            $categorySelect->where('slug = ?', $this->request->slug);
        }

        if (isset($this->request->directory)) {
            $directory = explode('/', $this->request->directory);
            $categorySelect->where('slug = ?', $directory[count($directory) - 1]);
        }

        $category = $this->db->fetchRow($categorySelect);
        if (empty($category)) {
            throw new WidgetException(_t('分类不存在'), 404);
        }

        $categoryListWidget = Rows::alloc('current=' . $category['mid']);
        $category = $categoryListWidget->filter($category);

        if (isset($directory) && ($this->request->directory != implode('/', $category['directory']))) {
            throw new WidgetException(_t('父级分类不存在'), 404);
        }

        $children = $categoryListWidget->getAllChildren($category['mid']);
        $children[] = $category['mid'];

        /** fix sql92 by 70 */
        $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid IN ?', $children)
            ->where('table.contents.type = ?', 'post')
            ->group('table.contents.cid');

        /** 设置分页 */
        $this->pageRow = array_merge($category, [
            'slug'      => urlencode($category['slug']),
            'directory' => implode('/', array_map('urlencode', $category['directory']))
        ]);

        /** 设置关键词 */
        $this->keywords = $category['name'];

        /** 设置描述 */
        $this->description = $category['description'];

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->feedUrl = $category['feedUrl'];

        /** RSS 1.0 */
        $this->feedRssUrl = $category['feedRssUrl'];

        /** ATOM 1.0 */
        $this->feedAtomUrl = $category['feedAtomUrl'];

        /** 设置标题 */
        $this->archiveTitle = $category['name'];

        /** 设置归档类型 */
        $this->archiveType = 'category';

        /** 设置归档缩略名 */
        $this->archiveSlug = $category['slug'];

        /** 设置归档地址 */
        $this->archiveUrl = $category['permalink'];

        /** 插件接口 */
        self::pluginHandle()->categoryHandle($this, $select);
    }

    /**
     * 处理标签
     *
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @throws WidgetException|Db\Exception
     */
    private function tagHandle(Query $select, bool &$hasPushed)
    {
        $tagSelect = $this->db->select()->from('table.metas')
            ->where('type = ?', 'tag')->limit(1);

        if (isset($this->request->mid)) {
            $tagSelect->where('mid = ?', $this->request->filter('int')->mid);
        }

        if (isset($this->request->slug)) {
            $tagSelect->where('slug = ?', $this->request->slug);
        }

        /** 如果是标签 */
        $tag = $this->db->fetchRow(
            $tagSelect,
            [Metas::alloc(), 'filter']
        );

        if (!$tag) {
            throw new WidgetException(_t('标签不存在'), 404);
        }

        /** fix sql92 by 70 */
        $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid = ?', $tag['mid'])
            ->where('table.contents.type = ?', 'post');

        /** 设置分页 */
        $this->pageRow = array_merge($tag, [
            'slug' => urlencode($tag['slug'])
        ]);

        /** 设置关键词 */
        $this->keywords = $tag['name'];

        /** 设置描述 */
        $this->description = $tag['description'];

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->feedUrl = $tag['feedUrl'];

        /** RSS 1.0 */
        $this->feedRssUrl = $tag['feedRssUrl'];

        /** ATOM 1.0 */
        $this->feedAtomUrl = $tag['feedAtomUrl'];

        /** 设置标题 */
        $this->archiveTitle = $tag['name'];

        /** 设置归档类型 */
        $this->archiveType = 'tag';

        /** 设置归档缩略名 */
        $this->archiveSlug = $tag['slug'];

        /** 设置归档地址 */
        $this->archiveUrl = $tag['permalink'];

        /** 插件接口 */
        self::pluginHandle()->tagHandle($this, $select);
    }

    /**
     * 处理作者
     *
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @throws WidgetException|Db\Exception
     */
    private function authorHandle(Query $select, bool &$hasPushed)
    {
        $uid = $this->request->filter('int')->uid;

        $author = $this->db->fetchRow(
            $this->db->select()->from('table.users')
            ->where('uid = ?', $uid),
            [User::alloc(), 'filter']
        );

        if (!$author) {
            throw new WidgetException(_t('作者不存在'), 404);
        }

        $select->where('table.contents.authorId = ?', $uid)
            ->where('table.contents.type = ?', 'post');

        /** 设置分页 */
        $this->pageRow = $author;

        /** 设置关键词 */
        $this->keywords = $author['screenName'];

        /** 设置描述 */
        $this->description = $author['screenName'];

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->feedUrl = $author['feedUrl'];

        /** RSS 1.0 */
        $this->feedRssUrl = $author['feedRssUrl'];

        /** ATOM 1.0 */
        $this->feedAtomUrl = $author['feedAtomUrl'];

        /** 设置标题 */
        $this->archiveTitle = $author['screenName'];

        /** 设置归档类型 */
        $this->archiveType = 'author';

        /** 设置归档缩略名 */
        $this->archiveSlug = $author['uid'];

        /** 设置归档地址 */
        $this->archiveUrl = $author['permalink'];

        /** 插件接口 */
        self::pluginHandle()->authorHandle($this, $select);
    }

    /**
     * 处理日期
     *
     * @access private
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @return void
     */
    private function dateHandle(Query $select, &$hasPushed)
    {
        /** 如果是按日期归档 */
        $year = $this->request->filter('int')->year;
        $month = $this->request->filter('int')->month;
        $day = $this->request->filter('int')->day;

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

        /** 设置头部feed */
        $value = [
            'year' => $year,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'day' => str_pad($day, 2, '0', STR_PAD_LEFT)
        ];

        /** 设置分页 */
        $this->pageRow = $value;

        /** 获取当前路由,过滤掉翻页情况 */
        $currentRoute = str_replace('_page', '', $this->parameter->type);

        /** RSS 2.0 */
        $this->feedUrl = Router::url($currentRoute, $value, $this->options->feedUrl);

        /** RSS 1.0 */
        $this->feedRssUrl = Router::url($currentRoute, $value, $this->options->feedRssUrl);

        /** ATOM 1.0 */
        $this->feedAtomUrl = Router::url($currentRoute, $value, $this->options->feedAtomUrl);

        /** 设置归档地址 */
        $this->archiveUrl = Router::url($currentRoute, $value, $this->options->index);

        /** 插件接口 */
        self::pluginHandle()->dateHandle($this, $select);
    }

    /**
     * 处理搜索
     *
     * @access private
     * @param Query $select 查询对象
     * @param boolean $hasPushed 是否已经压入队列
     * @return void
     */
    private function searchHandle(Query $select, &$hasPushed)
    {
        /** 增加自定义搜索引擎接口 */
        //~ fix issue 40
        $keywords = $this->request->filter('url', 'search')->keywords;
        self::pluginHandle()->trigger($hasPushed)->search($keywords, $this);

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
        $this->keywords = $keywords;

        /** 设置分页 */
        $this->pageRow = ['keywords' => urlencode($keywords)];

        /** 设置头部feed */
        /** RSS 2.0 */
        $this->feedUrl = Router::url('search', ['keywords' => $keywords], $this->options->feedUrl);

        /** RSS 1.0 */
        $this->feedRssUrl = Router::url('search', ['keywords' => $keywords], $this->options->feedAtomUrl);

        /** ATOM 1.0 */
        $this->feedAtomUrl = Router::url('search', ['keywords' => $keywords], $this->options->feedAtomUrl);

        /** 设置标题 */
        $this->archiveTitle = $keywords;

        /** 设置归档类型 */
        $this->archiveType = 'search';

        /** 设置归档缩略名 */
        $this->archiveSlug = $keywords;

        /** 设置归档地址 */
        $this->archiveUrl = Router::url('search', ['keywords' => $keywords], $this->options->index);

        /** 插件接口 */
        self::pluginHandle()->searchHandle($this, $select);
    }
}
