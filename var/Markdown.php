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
            if (class_exists('DOMDocument')) {
                $parser = new ParsedownExtra();
            } else if (class_exists('ParsedownExtra')) {
                $parser = new Parsedown();
            }

            $parser->setBreaksEnabled(true);
        }

        return $parser->text($text);
    }
}

