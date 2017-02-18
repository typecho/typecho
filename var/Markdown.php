<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Markdown解析
 *
 * @package Markdown
 * @copyright Copyright (c) 2014 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Markdown
{
    /**
     * @var HyperDown
     */
    public static $parser;

    /**
     * convert 
     * 
     * @param string $text 
     * @return string
     */
    public static function convert($text)
    {
        if (empty(self::$parser)) {
            self::$parser = new HyperDown();
            self::$parser->hook('afterParseCode', array('Markdown', 'transerCodeClass'));
            self::$parser->hook('beforeParseInline', array('Markdown', 'transerComment'));

            self::$parser->_commonWhiteList .= '|img|cite|embed|iframe';
            self::$parser->_specialWhiteList = array_merge(self::$parser->_specialWhiteList, array(
                'ol'            =>  'ol|li',
                'ul'            =>  'ul|li',
                'blockquote'    =>  'blockquote',
                'pre'           =>  'pre|code'
            ));
        }

        return self::$parser->makeHtml($text);
    }

    /**
     * transerCodeClass
     * 
     * @param string $html
     * @return string
     */
    public static function transerCodeClass($html)
    {
        return preg_replace("/<code class=\"([_a-z0-9-]+)\">/i", "<code class=\"lang-\\1\">", $html);
    }

    /**
     * @param $html
     * @return mixed
     */
    public static function transerComment($html)
    {
        return preg_replace_callback("/<!\-\-(.+?)\-\->/s", array('Markdown', 'transerCommentCallback'), $html);
    }

    /**
     * @param $matches
     * @return string
     */
    public static function transerCommentCallback($matches)
    {
        return self::$parser->makeHolder($matches[0]);
    }
}

