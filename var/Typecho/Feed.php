<?php
/**
 * 格式化聚合XML数据,整合自Univarsel Feed Writer
 *
 * @author Anis uddin Ahmad <anisniit@gmail.com>
 * @category typecho
 * @package Feed
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id: Feed.php 219 2008-05-27 09:06:15Z magike.net $
 */

/**
 * Typecho_Feed
 *
 * @author qining
 * @category typecho
 * @package Feed
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Feed
{
    /** 定义RSS 1.0类型 */
    const RSS1 = 'RSS 1.0';

    /** 定义RSS 2.0类型 */
    const RSS2 = 'RSS 2.0';

    /** 定义ATOM 1.0类型 */
    const ATOM1 = 'ATOM 1.0';

    /** 定义RSS时间格式 */
    const DATE_RFC822 = 'r';

    /** 定义ATOM时间格式 */
    const DATE_W3CDTF = 'c';

    /** 定义行结束符 */
    const EOL = "\n";

    /**
     * feed状态
     *
     * @access private
     * @var string
     */
    private $_type;

    /**
     * 字符集编码
     *
     * @access private
     * @var string
     */
    private $_charset;

    /**
     * 语言状态
     *
     * @access private
     * @var string
     */
    private $_lang;

    /**
     * 聚合地址
     *
     * @access private
     * @var string
     */
    private $_feedUrl;

    /**
     * 基本地址
     *
     * @access private
     * @var unknown
     */
    private $_baseUrl;

    /**
     * 聚合标题
     *
     * @access private
     * @var string
     */
    private $_title;

    /**
     * 聚合副标题
     *
     * @access private
     * @var string
     */
    private $_subTitle;

    /**
     * 版本信息
     *
     * @access private
     * @var string
     */
    private $_version;

    /**
     * 所有的items
     *
     * @access private
     * @var array
     */
    private $_items = array();

    /**
     * 创建Feed对象
     *
     * @access public
     * @return void
     */
    public function __construct($version, $type = self::RSS2, $charset = 'UTF-8', $lang = 'en')
    {
        $this->_version = $version;
        $this->_type = $type;
        $this->_charset = $charset;
        $this->_lang = $lang;
    }

    /**
     * 设置标题
     *
     * @access public
     * @param string $title 标题
     * @return void
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * 设置副标题
     *
     * @access public
     * @param string $subTitle 副标题
     * @return void
     */
    public function setSubTitle($subTitle)
    {
        $this->_subTitle = $subTitle;
    }

    /**
     * 设置聚合地址
     *
     * @access public
     * @param string $feedUrl 聚合地址
     * @return void
     */
    public function setFeedUrl($feedUrl)
    {
        $this->_feedUrl = $feedUrl;
    }

    /**
     * 设置主页
     *
     * @access public
     * @param string $baseUrl 主页地址
     * @return void
     */
    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * 获取Feed时间格式
     *
     * @access public
     * @param integer $stamp 时间戳
     * @return string
     */
    public function dateFormat($stamp)
    {
        if (self::RSS2 == $this->_type) {
            return date(self::DATE_RFC822, $stamp);
        } else if (self::RSS1 == $this->_type || self::ATOM1 == $this->_type) {
            return date(self::DATE_W3CDTF, $stamp);
        }
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
     * @access public
     * @param array $item
     * @return unknown
     */
    public function addItem(array $item)
    {
        $this->_items[] = $item;
    }

    /**
     * 输出字符串
     *
     * @access public
     * @return string
     */
    public function __toString()
    {
        $result = '<?xml version="1.0" encoding="' . $this->_charset . '"?>' . self::EOL;

        if (self::RSS1 == $this->_type) {
            $result .= '<rdf:RDF
xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
xmlns="http://purl.org/rss/1.0/"
xmlns:dc="http://purl.org/dc/elements/1.1/">' . self::EOL;

            $content = '';
            $links = array();
            $lastUpdate = 0;

            foreach ($this->_items as $item) {
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

            $result .= '<channel rdf:about="' . $this->_feedUrl . '">
<title>' . htmlspecialchars($this->_title) . '</title>
<link>' . $this->_baseUrl . '</link>
<description>' . htmlspecialchars($this->_subTitle) . '</description>
<items>
<rdf:Seq>' . self::EOL;

            foreach ($links as $link) {
                $result .= '<rdf:li resource="' . $link . '"/>' . self::EOL;
            }

            $result .= '</rdf:Seq>
</items>
</channel>' . self::EOL;

            $result .= $content . '</rdf:RDF>';

        } else if (self::RSS2 == $this->_type) {
            $result .= '<rss version="2.0"
xmlns:content="http://purl.org/rss/1.0/modules/content/"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
xmlns:atom="http://www.w3.org/2005/Atom"
xmlns:wfw="http://wellformedweb.org/CommentAPI/">
<channel>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->_items as $item) {
                $content .= '<item>' . self::EOL;
                $content .= '<title>' . htmlspecialchars($item['title']) . '</title>' . self::EOL;
                $content .= '<link>' . $item['link'] . '</link>' . self::EOL;
                $content .= '<guid>' . $item['link'] . '</guid>' . self::EOL;
                $content .= '<pubDate>' . $this->dateFormat($item['date']) . '</pubDate>' . self::EOL;
                $content .= '<dc:creator>' . htmlspecialchars($item['author']->screenName) . '</dc:creator>' . self::EOL;

                if (!empty($item['category']) && is_array($item['category'])) {
                    foreach ($item['category'] as $category) {
                        $content .= '<category><![CDATA[' . $category['name'] . ']]></category>' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<description><![CDATA[' . strip_tags($item['excerpt']) . ']]></description>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content:encoded xml:lang="' . $this->_lang . '"><![CDATA['
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

            $result .= '<title>' . htmlspecialchars($this->_title) . '</title>
<link>' . $this->_baseUrl . '</link>
<atom:link href="' . $this->_feedUrl . '" rel="self" type="application/rss+xml" />
<language>' . $this->_lang . '</language>
<description>' . htmlspecialchars($this->_subTitle) . '</description>
<lastBuildDate>' . $this->dateFormat($lastUpdate) . '</lastBuildDate>
<pubDate>' . $this->dateFormat($lastUpdate) . '</pubDate>' . self::EOL;

            $result .= $content . '</channel>
</rss>';

        } else if (self::ATOM1 == $this->_type) {
            $result .= '<feed xmlns="http://www.w3.org/2005/Atom"
xmlns:thr="http://purl.org/syndication/thread/1.0"
xml:lang="' . $this->_lang . '"
xml:base="' . $this->_baseUrl . '"
>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->_items as $item) {
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
                        $content .= '<category scheme="' . $category['permalink'] . '" term="' . $category['name'] . '" />' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<summary type="html"><![CDATA[' . htmlspecialchars($item['excerpt']) . ']]></summary>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content type="html" xml:base="' . $item['link'] . '" xml:lang="' . $this->_lang . '"><![CDATA['
                    . self::EOL .
                    $item['content'] . self::EOL .
                    ']]></content>' . self::EOL;
                }

                if (isset($item['comments']) && strlen($item['comments']) > 0) {
                    $content .= '<link rel="replies" type="text/html" href="' . $item['link'] . '#comments" thr:count="' . $item['comments'] . '" />' . self::EOL;

                    if (!empty($item['commentsFeedUrl'])) {
                        $content .= '<link rel="replies" type="application/atom+xml" href="' . $item['commentsFeedUrl'] . '" thr:count="' . $item['comments'] . '"/>' . self::EOL;
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

            $result .= '<title type="text">' . htmlspecialchars($this->_title) . '</title>
<subtitle type="text">' . htmlspecialchars($this->_subTitle) . '</subtitle>
<updated>' . $this->dateFormat($lastUpdate) . '</updated>
<generator uri="http://typecho.org/" version="' . $this->_version . '">Typecho</generator>
<link rel="alternate" type="text/html" href="' . $this->_baseUrl . '" />
<id>' . $this->_feedUrl . '</id>
<link rel="self" type="application/atom+xml" href="' . $this->_feedUrl . '" />
';
            $result .= $content . '</feed>';
        }

        return $result;
    }
}
