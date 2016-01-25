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
 * Provides static methods to simplify and standardize the creation of inline elements
 */
class CommonMark_Element_InlineCreator
{
    /**
     * @param string $code
     *
     * @return InlineElement
     */
    public static function createCode($code)
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_CODE, array('c' => $code));
    }

    /**
     * @param string $contents
     *
     * @return InlineElement
     */
    public static function createEmph($contents)
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_EMPH, array('c' => $contents));
    }

    /**
     * @param string $contents
     *
     * @return InlineElement
     */
    public static function createEntity($contents)
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_ENTITY, array('c' => $contents));
    }

    /**
     * @return InlineElement
     */
    public static function createHardbreak()
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_HARDBREAK);
    }

    /**
     * @param string $html
     *
     * @return InlineElement
     */
    public static function createHtml($html)
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_HTML, array('c' => $html));
    }

    /**
     * @param string                      $destination
     * @param string|ArrayCollection|null $label
     * @param string|null                 $title
     *
     * @return InlineElement
     */
    public static function createLink($destination, $label = null, $title = null)
    {
        $attr = array('destination' => $destination);

        if (is_string($label)) {
            $attr['label'] = array(self::createString($label));
        } elseif (is_object($label) && $label instanceof CommonMark_Util_ArrayCollection) {
            $attr['label'] = $label->toArray();
        } elseif (empty($label)) {
            $attr['label'] = array(self::createString($destination));
        } else {
            $attr['label'] = $label;
        }

        if ($title) {
            $attr['title'] = $title;
        }

        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_LINK, $attr);
    }

    /**
     * @return InlineElement
     */
    public static function createSoftbreak()
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_SOFTBREAK);
    }

    /**
     * @param string $contents
     *
     * @return InlineElement
     */
    public static function createString($contents)
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_STRING, array('c' => $contents));
    }

    /**
     * @param string $contents
     *
     * @return InlineElement
     */
    public static function createStrong($contents)
    {
        return new CommonMark_Element_InlineElement(CommonMark_Element_InlineElement::TYPE_STRONG, array('c' => $contents));
    }
}
