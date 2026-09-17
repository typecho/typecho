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
    private string $type;

    /**
     * 字符集编码
     *
     * @access private
     * @var string
     */
    private string $charset;

    /**
     * 语言状态
     *
     * @access private
     * @var string
     */
    private string $lang;

    /**
     * 聚合地址
     *
     * @access private
     * @var string
     */
    private string $feedUrl;

    /**
     * 基本地址
     *
     * @access private
     * @var string
     */
    private string $baseUrl;

    /**
     * 聚合标题
     *
     * @access private
     * @var string
     */
    private string $title;

    /**
     * 聚合副标题
     *
     * @access private
     * @var string|null
     */
    private ?string $subTitle;

    /**
     * 版本信息
     *
     * @access private
     * @var string
     */
    private string $version;

    /**
     * 所有的items
     *
     * @access private
     * @var array
     */
    private array $items = [];

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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
     * @return string
     */
    public function getFeedUrl(): string
    {
        return $this->feedUrl;
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
                $content .= '<item rdf:about="' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '">' . self::EOL;
                $content .= '<title>' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</title>' . self::EOL;
                $content .= '<link>' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '</link>' . self::EOL;
                $content .= '<dc:date>' . $this->dateFormat($item['date']) . '</dc:date>' . self::EOL;
                $content .= '<description>' . htmlspecialchars(strip_tags($item['content']), ENT_QUOTES, 'UTF-8') . '</description>' . self::EOL;
                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }
                $content .= '</item>' . self::EOL;

                $links[] = $item['link'];

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<channel rdf:about="' . htmlspecialchars($this->feedUrl, ENT_QUOTES, 'UTF-8') . '">
<title>' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '</title>
<link>' . htmlspecialchars($this->baseUrl, ENT_QUOTES, 'UTF-8') . '</link>
<description>' . htmlspecialchars($this->subTitle ?? '', ENT_QUOTES, 'UTF-8') . '</description>
<items>
<rdf:Seq>' . self::EOL;

            foreach ($links as $link) {
                $result .= '<rdf:li resource="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"/>' . self::EOL;
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
xmlns:atom="http://www.w3.org/2005/Atom">
<channel>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<item>' . self::EOL;
                $content .= '<title>' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</title>' . self::EOL;
                $content .= '<link>' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '</link>' . self::EOL;
                $content .= '<guid>' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '</guid>' . self::EOL;
                $content .= '<pubDate>' . $this->dateFormat($item['date']) . '</pubDate>' . self::EOL;
                $content .= '<dc:creator>' . htmlspecialchars($item['author']->screenName, ENT_QUOTES, 'UTF-8')
                    . '</dc:creator>' . self::EOL;

                if (!empty($item['category']) && is_array($item['category'])) {
                    foreach ($item['category'] as $category) {
                        $content .= '<category>' . htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') . '</category>' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<description>' . htmlspecialchars(strip_tags($item['excerpt']), ENT_QUOTES, 'UTF-8')
                        . '</description>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content:encoded xml:lang="' . htmlspecialchars($this->lang, ENT_QUOTES, 'UTF-8') . '"><![CDATA['
                        . self::EOL .
                        self::cdataEscape($item['content']) . self::EOL .
                        ']]></content:encoded>' . self::EOL;
                }

                if (isset($item['comments']) && strlen($item['comments']) > 0) {
                    $content .= '<slash:comments>' . intval($item['comments']) . '</slash:comments>' . self::EOL;
                }

                $content .= '<comments>' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '#comments</comments>' . self::EOL;

                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }

                $content .= '</item>' . self::EOL;

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<title>' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '</title>
<link>' . htmlspecialchars($this->baseUrl, ENT_QUOTES, 'UTF-8') . '</link>
<atom:link href="' . htmlspecialchars($this->feedUrl, ENT_QUOTES, 'UTF-8') . '" rel="self" type="application/rss+xml" />
<language>' . htmlspecialchars($this->lang, ENT_QUOTES, 'UTF-8') . '</language>
<description>' . htmlspecialchars($this->subTitle ?? '', ENT_QUOTES, 'UTF-8') . '</description>
<lastBuildDate>' . $this->dateFormat($lastUpdate) . '</lastBuildDate>
<pubDate>' . $this->dateFormat($lastUpdate) . '</pubDate>' . self::EOL;

            $result .= $content . '</channel>
</rss>';
        } elseif (self::ATOM1 == $this->type) {
            $result .= '<feed xmlns="http://www.w3.org/2005/Atom"
xmlns:thr="http://purl.org/syndication/thread/1.0"
xml:lang="' . htmlspecialchars($this->lang, ENT_QUOTES, 'UTF-8') . '"
xml:base="' . htmlspecialchars($this->baseUrl, ENT_QUOTES, 'UTF-8') . '"
>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<entry>' . self::EOL;
                $content .= '<title type="html">' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</title>' . self::EOL;
                $content .= '<link rel="alternate" type="text/html" href="' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '" />' . self::EOL;
                $content .= '<id>' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '</id>' . self::EOL;
                $content .= '<updated>' . $this->dateFormat($item['date']) . '</updated>' . self::EOL;
                $content .= '<published>' . $this->dateFormat($item['date']) . '</published>' . self::EOL;
                $content .= '<author>
    <name>' . htmlspecialchars($item['author']->screenName, ENT_QUOTES, 'UTF-8') . '</name>
    <uri>' . htmlspecialchars($item['author']->url, ENT_QUOTES, 'UTF-8') . '</uri>
</author>' . self::EOL;

                if (!empty($item['category']) && is_array($item['category'])) {
                    foreach ($item['category'] as $category) {
                        $content .= '<category scheme="' . htmlspecialchars($category['permalink'], ENT_QUOTES, 'UTF-8') . '" term="'
                            . htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') . '" />' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<summary type="html">' . htmlspecialchars($item['excerpt'], ENT_QUOTES, 'UTF-8')
                        . '</summary>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content type="html" xml:base="' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8')
                        . '" xml:lang="' . htmlspecialchars($this->lang, ENT_QUOTES, 'UTF-8') . '"><![CDATA['
                        . self::EOL .
                        self::cdataEscape($item['content']) . self::EOL .
                        ']]></content>' . self::EOL;
                }

                if (isset($item['comments']) && strlen($item['comments']) > 0) {
                    $content .= '<link rel="replies" type="text/html" href="' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8')
                        . '#comments" thr:count="' . intval($item['comments']) . '" />' . self::EOL;

                    if (!empty($item['commentsFeedUrl'])) {
                        $content .= '<link rel="replies" type="application/atom+xml" href="'
                            . htmlspecialchars($item['commentsFeedUrl'], ENT_QUOTES, 'UTF-8') . '" thr:count="' . intval($item['comments']) . '"/>' . self::EOL;
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

            $result .= '<title type="text">' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '</title>
<subtitle type="text">' . htmlspecialchars($this->subTitle ?? '', ENT_QUOTES, 'UTF-8') . '</subtitle>
<updated>' . $this->dateFormat($lastUpdate) . '</updated>
<generator uri="https://typecho.org/" version="' . htmlspecialchars($this->version, ENT_QUOTES, 'UTF-8') . '">Typecho</generator>
<link rel="alternate" type="text/html" href="' . htmlspecialchars($this->baseUrl, ENT_QUOTES, 'UTF-8') . '" />
<id>' . htmlspecialchars($this->feedUrl, ENT_QUOTES, 'UTF-8') . '</id>
<link rel="self" type="application/atom+xml" href="' . htmlspecialchars($this->feedUrl, ENT_QUOTES, 'UTF-8') . '" />
';
            $result .= $content . '</feed>';
        }

        return $result;
    }

    private static function cdataEscape(string $content): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $content);
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
