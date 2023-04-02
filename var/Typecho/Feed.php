<?php

namespace Typecho;

/**
 * Feed
 *
 * @package Feed
 */
class Feed
{
    /** 定义RSS 1.0类型 */
    public const RSS1 = 'RSS 1.0';

    /** 定义RSS 2.0类型 */
    public const RSS2 = 'RSS 2.0';

    /** 定义ATOM 1.0类型 */
    public const ATOM1 = 'ATOM 1.0';

    /** 定义RSS时间格式 */
    public const DATE_RFC822 = 'r';

    /** 定义ATOM时间格式 */
    public const DATE_W3CDTF = 'c';

    /** 定义行结束符 */
    public const EOL = "\n";

    /**
     * feed状态
     *
     * @access private
     * @var string
     */
    private $type;

    /**
     * 字符集编码
     *
     * @access private
     * @var string
     */
    private $charset;

    /**
     * 语言状态
     *
     * @access private
     * @var string
     */
    private $lang;

    /**
     * 聚合地址
     *
     * @access private
     * @var string
     */
    private $feedUrl;

    /**
     * 基本地址
     *
     * @access private
     * @var string
     */
    private $baseUrl;

    /**
     * 聚合标题
     *
     * @access private
     * @var string
     */
    private $title;

    /**
     * 聚合副标题
     *
     * @access private
     * @var string
     */
    private $subTitle;

    /**
     * 版本信息
     *
     * @access private
     * @var string
     */
    private $version;

    /**
     * 所有的items
     *
     * @access private
     * @var array
     */
    private $items = [];

    /**
     * 创建Feed对象
     *
     * @param $version
     * @param string $type
     * @param string $charset
     * @param string $lang
     */
    public function __construct($version, string $type = self::RSS2, string $charset = 'UTF-8', string $lang = 'en')
    {
        $this->version = $version;
        $this->type = $type;
        $this->charset = $charset;
        $this->lang = $lang;
    }

    /**
     * 设置标题
     *
     * @param string $title 标题
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * 设置副标题
     *
     * @param string|null $subTitle 副标题
     */
    public function setSubTitle(?string $subTitle)
    {
        $this->subTitle = $subTitle;
    }

    /**
     * 设置聚合地址
     *
     * @param string $feedUrl 聚合地址
     */
    public function setFeedUrl(string $feedUrl)
    {
        $this->feedUrl = $feedUrl;
    }

    /**
     * 设置主页
     *
     * @param string $baseUrl 主页地址
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * $item的格式为
     * <code>
     * array (
     *     'title'      =>  'xxx',
     *     'content'    =>  'xxx',
     *     'excerpt'    =>  'xxx',
     *     'date'       =>  'xxx',
     *     'link'       =>  'xxx',
     *     'author'     =>  'xxx',
     *     'comments'   =>  'xxx',
     *     'commentsUrl'=>  'xxx',
     *     'commentsFeedUrl' => 'xxx',
     * )
     * </code>
     *
     * @param array $item
     */
    public function addItem(array $item)
    {
        $this->items[] = $item;
    }

    /**
     * 输出字符串
     *
     * @return string
     */
    public function __toString(): string
    {
        $result = '<?xml version="1.0" encoding="' . $this->charset . '"?>' . self::EOL;

        if (self::RSS1 == $this->type) {
            $result .= '<rdf:RDF
xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
xmlns="http://purl.org/rss/1.0/"
xmlns:dc="http://purl.org/dc/elements/1.1/">' . self::EOL;

            $content = '';
            $links = [];
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<item rdf:about="' . $item['link'] . '">' . self::EOL;
                $content .= '<title>' . htmlspecialchars($item['title']) . '</title>' . self::EOL;
                $content .= '<link>' . $item['link'] . '</link>' . self::EOL;
                $content .= '<dc:date>' . $this->dateFormat($item['date']) . '</dc:date>' . self::EOL;
                $content .= '<description>' . strip_tags($item['content']) . '</description>' . self::EOL;
                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }
                $content .= '</item>' . self::EOL;

                $links[] = $item['link'];

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<channel rdf:about="' . $this->feedUrl . '">
<title>' . htmlspecialchars($this->title) . '</title>
<link>' . $this->baseUrl . '</link>
<description>' . htmlspecialchars($this->subTitle ?? '') . '</description>
<items>
<rdf:Seq>' . self::EOL;

            foreach ($links as $link) {
                $result .= '<rdf:li resource="' . $link . '"/>' . self::EOL;
            }

            $result .= '</rdf:Seq>
</items>
</channel>' . self::EOL;

            $result .= $content . '</rdf:RDF>';
        } elseif (self::RSS2 == $this->type) {
            $result .= '<rss version="2.0"
xmlns:content="http://purl.org/rss/1.0/modules/content/"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
xmlns:atom="http://www.w3.org/2005/Atom"
xmlns:wfw="http://wellformedweb.org/CommentAPI/">
<channel>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<item>' . self::EOL;
                $content .= '<title>' . htmlspecialchars($item['title']) . '</title>' . self::EOL;
                $content .= '<link>' . $item['link'] . '</link>' . self::EOL;
                $content .= '<guid>' . $item['link'] . '</guid>' . self::EOL;
                $content .= '<pubDate>' . $this->dateFormat($item['date']) . '</pubDate>' . self::EOL;
                $content .= '<dc:creator>' . htmlspecialchars($item['author']->screenName)
                    . '</dc:creator>' . self::EOL;

                if (!empty($item['category']) && is_array($item['category'])) {
                    foreach ($item['category'] as $category) {
                        $content .= '<category><![CDATA[' . $category['name'] . ']]></category>' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<description><![CDATA[' . strip_tags($item['excerpt'])
                        . ']]></description>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content:encoded xml:lang="' . $this->lang . '"><![CDATA['
                        . self::EOL .
                        $item['content'] . self::EOL .
                        ']]></content:encoded>' . self::EOL;
                }

                if (isset($item['comments']) && strlen($item['comments']) > 0) {
                    $content .= '<slash:comments>' . $item['comments'] . '</slash:comments>' . self::EOL;
                }

                $content .= '<comments>' . $item['link'] . '#comments</comments>' . self::EOL;
                if (!empty($item['commentsFeedUrl'])) {
                    $content .= '<wfw:commentRss>' . $item['commentsFeedUrl'] . '</wfw:commentRss>' . self::EOL;
                }

                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }

                $content .= '</item>' . self::EOL;

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<title>' . htmlspecialchars($this->title) . '</title>
<link>' . $this->baseUrl . '</link>
<atom:link href="' . $this->feedUrl . '" rel="self" type="application/rss+xml" />
<language>' . $this->lang . '</language>
<description>' . htmlspecialchars($this->subTitle ?? '') . '</description>
<lastBuildDate>' . $this->dateFormat($lastUpdate) . '</lastBuildDate>
<pubDate>' . $this->dateFormat($lastUpdate) . '</pubDate>' . self::EOL;

            $result .= $content . '</channel>
</rss>';
        } elseif (self::ATOM1 == $this->type) {
            $result .= '<feed xmlns="http://www.w3.org/2005/Atom"
xmlns:thr="http://purl.org/syndication/thread/1.0"
xml:lang="' . $this->lang . '"
xml:base="' . $this->baseUrl . '"
>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<entry>' . self::EOL;
                $content .= '<title type="html"><![CDATA[' . $item['title'] . ']]></title>' . self::EOL;
                $content .= '<link rel="alternate" type="text/html" href="' . $item['link'] . '" />' . self::EOL;
                $content .= '<id>' . $item['link'] . '</id>' . self::EOL;
                $content .= '<updated>' . $this->dateFormat($item['date']) . '</updated>' . self::EOL;
                $content .= '<published>' . $this->dateFormat($item['date']) . '</published>' . self::EOL;
                $content .= '<author>
    <name>' . $item['author']->screenName . '</name>
    <uri>' . $item['author']->url . '</uri>
</author>' . self::EOL;

                if (!empty($item['category']) && is_array($item['category'])) {
                    foreach ($item['category'] as $category) {
                        $content .= '<category scheme="' . $category['permalink'] . '" term="'
                            . $category['name'] . '" />' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<summary type="html"><![CDATA[' . htmlspecialchars($item['excerpt'])
                        . ']]></summary>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content type="html" xml:base="' . $item['link']
                        . '" xml:lang="' . $this->lang . '"><![CDATA['
                        . self::EOL .
                        $item['content'] . self::EOL .
                        ']]></content>' . self::EOL;
                }

                if (isset($item['comments']) && strlen($item['comments']) > 0) {
                    $content .= '<link rel="replies" type="text/html" href="' . $item['link']
                        . '#comments" thr:count="' . $item['comments'] . '" />' . self::EOL;

                    if (!empty($item['commentsFeedUrl'])) {
                        $content .= '<link rel="replies" type="application/atom+xml" href="'
                            . $item['commentsFeedUrl'] . '" thr:count="' . $item['comments'] . '"/>' . self::EOL;
                    }
                }

                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }

                $content .= '</entry>' . self::EOL;

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<title type="text">' . htmlspecialchars($this->title) . '</title>
<subtitle type="text">' . htmlspecialchars($this->subTitle ?? '') . '</subtitle>
<updated>' . $this->dateFormat($lastUpdate) . '</updated>
<generator uri="http://typecho.org/" version="' . $this->version . '">Typecho</generator>
<link rel="alternate" type="text/html" href="' . $this->baseUrl . '" />
<id>' . $this->feedUrl . '</id>
<link rel="self" type="application/atom+xml" href="' . $this->feedUrl . '" />
';
            $result .= $content . '</feed>';
        }

        return $result;
    }

    /**
     * 获取Feed时间格式
     *
     * @param integer $stamp 时间戳
     * @return string
     */
    public function dateFormat(int $stamp): string
    {
        if (self::RSS2 == $this->type) {
            return date(self::DATE_RFC822, $stamp);
        } elseif (self::RSS1 == $this->type || self::ATOM1 == $this->type) {
            return date(self::DATE_W3CDTF, $stamp);
        }

        return '';
    }
}
