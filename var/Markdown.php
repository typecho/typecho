<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 扩展htmlrender，使得其支持行内换行 
 * 
 * @package Markdown
 * @copyright Copyright (c) 2014 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class HtmlRendererExtra extends CommonMark_HtmlRenderer
{
    /**
     * renderInline  
     * 
     * @param CommonMark_Element_InlineElementInterface $inline 
     * @access public
     * @return void
     */
    public function renderInline(CommonMark_Element_InlineElementInterface $inline)
    {
        if ($inline->getType() == CommonMark_Element_InlineElement::TYPE_SOFTBREAK) {
            $inline->setType(CommonMark_Element_InlineElement::TYPE_HARDBREAK);
        }

        return parent::renderInline($inline);
    }
}

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
        static $docParser, $renderer;

        if (empty($docParser)) {
            $docParser = new CommonMark_DocParser();
        }


        if (empty($renderer)) {
            $renderer = new HtmlRendererExtra();
        }

        $doc = $docParser->parse($text);
        return $renderer->render($doc);
    }
}

