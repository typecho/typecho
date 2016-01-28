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
     * convert 
     * 
     * @param string $text 
     * @return string
     */
    public static function convert($text)
    {
        static $parser;

        if (empty($parser)) {
            $parser = new HyperDown();
            $parser->hook('afterParseCode', array('Markdown', 'transerCodeClass'));
        }

        return $parser->makeHtml($text);
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
}

