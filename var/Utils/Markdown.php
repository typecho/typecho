<?php

namespace Utils;

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
    public static function convert(string $text): string
    {
        static $parser;

        if (empty($parser)) {
            $parser = new HyperDown();

            $parser->hook('afterParseCode', function ($html) {
                return preg_replace("/<code class=\"([_a-z0-9-]+)\">/i", "<code class=\"lang-\\1\">", $html);
            });

            $parser->enableHtml(true);
        }

        return str_replace('<p><!--more--></p>', '<!--more-->', $parser->makeHtml($text));
    }
}

