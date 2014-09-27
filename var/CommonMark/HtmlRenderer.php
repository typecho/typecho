<?php

/*
 * This file is part of the commonmark-php package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on stmd.js
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;


/**
 * Renders a parsed AST to HTML
 */
class CommonMark_HtmlRenderer
{
    protected $blockSeparator = "\n";
    protected $innerSeparator = "\n";
    protected $softBreak = "\n";

    /**
     * @param string $string
     * @param bool   $preserveEntities
     *
     * @return string
     *
     * @todo: Can we use simple find/replace instead?
     */
    protected function escape($string, $preserveEntities = false)
    {
        if ($preserveEntities) {
            $string = preg_replace('/[&](?![#](x[a-f0-9]{1,8}|[0-9]{1,8});|[a-z][a-z0-9]{1,31};)/i', '&amp;', $string);
        } else {
            $string = preg_replace('/[&]/', '&amp;', $string);
        }

        $string = preg_replace('/[<]/', '&lt;', $string);
        $string = preg_replace('/[>]/', '&gt;', $string);
        $string = preg_replace('/["]/', '&quot;', $string);

        return $string;
    }

    /**
     * Helper function to produce content in a pair of HTML tags.
     *
     * @param string      $tag
     * @param array       $attribs
     * @param string|null $contents
     * @param bool        $selfClosing
     *
     * @return string
     */
    protected function inTags($tag, $attribs = array(), $contents = null, $selfClosing = false)
    {
        $result = '<' . $tag;

        foreach ($attribs as $key => $value) {
            $result .= ' ' . $key . '="' . $value . '"';

        }

        if ($contents) {
            $result .= '>' . $contents . '</' . $tag . '>';
        } elseif ($selfClosing) {
            $result .= ' />';
        } else {
            $result .= '></' . $tag . '>';
        }

        return $result;
    }

    /**
     * @param InlineElementInterface $inline
     *
     * @return mixed|string
     *
     * @throws \InvalidArgumentException
     */
    public function renderInline(CommonMark_Element_InlineElementInterface $inline)
    {
        $attrs = array();
        switch ($inline->getType()) {
            case CommonMark_Element_InlineElement::TYPE_STRING:
                return $this->escape($inline->getContents());
            case CommonMark_Element_InlineElement::TYPE_SOFTBREAK:
                return $this->softBreak;
            case CommonMark_Element_InlineElement::TYPE_HARDBREAK:
                return $this->inTags('br', array(), '', true) . "\n";
            case CommonMark_Element_InlineElement::TYPE_EMPH:
                return $this->inTags('em', array(), $this->renderInlines($inline->getContents()));
            case CommonMark_Element_InlineElement::TYPE_STRONG:
                return $this->inTags('strong', array(), $this->renderInlines($inline->getContents()));
            case CommonMark_Element_InlineElement::TYPE_HTML:
                return $inline->getContents();
            case CommonMark_Element_InlineElement::TYPE_ENTITY:
                return $inline->getContents();
            case CommonMark_Element_InlineElement::TYPE_LINK:
                $attrs['href'] = $this->escape($inline->getAttribute('destination'), true);
                if ($title = $inline->getAttribute('title')) {
                    $attrs['title'] = $this->escape($title, true);
                }

                return $this->inTags('a', $attrs, $this->renderInlines($inline->getAttribute('label')));
            case CommonMark_Element_InlineElement::TYPE_IMAGE:
                $attrs['src'] = $this->escape($inline->getAttribute('destination'), true);
                $attrs['alt'] = $this->escape($this->renderInlines($inline->getAttribute('label')));
                if ($title = $inline->getAttribute('title')) {
                    $attrs['title'] = $this->escape($title, true);
                }

                return $this->inTags('img', $attrs, '', true);
            case CommonMark_Element_InlineElement::TYPE_CODE:
                return $this->inTags('code', array(), $this->escape($inline->getContents()));
            default:
                throw new InvalidArgumentException('Unknown inline type: ' . $inline->getType());
        }
    }

    /**
     * @param InlineElement[] $inlines
     *
     * @return string
     */
    public function renderInlines($inlines)
    {
        $result = array();
        foreach ($inlines as $inline) {
            $result[] = $this->renderInline($inline);
        }

        return implode('', $result);
    }

    /**
     * @param BlockElement $block
     * @param bool         $inTightList
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function renderBlock(CommonMark_Element_BlockElement $block, $inTightList = false)
    {
        switch ($block->getType()) {
            case CommonMark_Element_BlockElement::TYPE_DOCUMENT:
                $wholeDoc = $this->renderBlocks($block->getChildren());

                return $wholeDoc === '' ? '' : $wholeDoc . "\n";
            case CommonMark_Element_BlockElement::TYPE_PARAGRAPH:
                if ($inTightList) {
                    return $this->renderInlines($block->getInlineContent());
                } else {
                    return $this->inTags('p', array(), $this->renderInlines($block->getInlineContent()));
                }
                break;
            case CommonMark_Element_BlockElement::TYPE_BLOCK_QUOTE:
                $filling = $this->renderBlocks($block->getChildren());
                if ($filling === '') {
                    return $this->inTags('blockquote', array(), $this->innerSeparator);
                } else {
                    return $this->inTags(
                        'blockquote',
                        array(),
                        $this->innerSeparator . $filling . $this->innerSeparator
                    );
                }
            case CommonMark_Element_BlockElement::TYPE_LIST_ITEM:
                return trim($this->inTags('li', array(), $this->renderBlocks($block->getChildren(), $inTightList)));
            case CommonMark_Element_BlockElement::TYPE_LIST:
                $listData = $block->getExtra('list_data');
                $start = isset($listData['start']) ? $listData['start'] : null;

                $tag = $listData['type'] == CommonMark_Element_BlockElement::LIST_TYPE_UNORDERED ? 'ul' : 'ol';
                $attr = (!$start || $start == 1) ?
                    array() : array('start' => (string)$start);

                return $this->inTags(
                    $tag,
                    $attr,
                    $this->innerSeparator . $this->renderBlocks(
                        $block->getChildren(),
                        $block->getExtra('tight')
                    ) . $this->innerSeparator
                );
            case CommonMark_Element_BlockElement::TYPE_ATX_HEADER:
            case CommonMark_Element_BlockElement::TYPE_SETEXT_HEADER:
                $tag = 'h' . $block->getExtra('level');

                return $this->inTags($tag, array(), $this->renderInlines($block->getInlineContent()));

            case CommonMark_Element_BlockElement::TYPE_INDENTED_CODE:
                return $this->inTags(
                    'pre',
                    array(),
                    $this->inTags('code', array(), $this->escape($block->getStringContent()))
                );

            case CommonMark_Element_BlockElement::TYPE_FENCED_CODE:
                $infoWords = preg_split('/ +/', $block->getExtra('info'));
                $attr = count($infoWords) === 0 || strlen(
                    $infoWords[0]
                ) === 0 ? array() : array('class' => 'language-' . $this->escape($infoWords[0], true));
                return $this->inTags(
                    'pre',
                    array(),
                    $this->inTags('code', $attr, $this->escape($block->getStringContent()))
                );

            case CommonMark_Element_BlockElement::TYPE_HTML_BLOCK:
                return $block->getStringContent();

            case CommonMark_Element_BlockElement::TYPE_REFERENCE_DEF:
                return '';

            case CommonMark_Element_BlockElement::TYPE_HORIZONTAL_RULE:
                return $this->inTags('hr', array(), '', true);

            default:
                throw new RuntimeException('Unknown block type: ' . $block->getType());
        }
    }

    /**
     * @param BlockElement[] $blocks
     * @param bool           $inTightList
     *
     * @return string
     */
    public function renderBlocks($blocks, $inTightList = false)
    {
        $result = array();
        foreach ($blocks as $block) {
            if ($block->getType() !== 'ReferenceDef') {
                $result[] = $this->renderBlock($block, $inTightList);
            }
        }

        return implode($this->blockSeparator, $result);
    }

    /**
     * @param BlockElement $block
     * @param bool         $inTightList
     *
     * @return string
     *
     * @api
     */
    public function render(CommonMark_Element_BlockElement $block, $inTightList = false)
    {
        return $this->renderBlock($block, $inTightList);
    }
}

