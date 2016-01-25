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
 * Inline element
 */
class CommonMark_Element_InlineElement implements CommonMark_Element_InlineElementInterface
{
    const TYPE_CODE = 'Code';
    const TYPE_EMPH = 'Emph';
    const TYPE_ENTITY = 'Entity';
    const TYPE_HARDBREAK = 'Hardbreak';
    const TYPE_HTML = 'Html';
    const TYPE_IMAGE = 'Image';
    const TYPE_LINK = 'Link';
    const TYPE_SOFTBREAK = 'Softbreak';
    const TYPE_STRING = 'Str';
    const TYPE_STRONG = 'Strong';

    /**
     * @var mixed
     */
    protected $type;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param mixed $type
     * @param array $attributes
     */
    public function __construct($type, array $attributes = array())
    {
        $this->type = $type;
        $this->attributes = $attributes;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getContents()
    {
        return $this->getAttribute('c');
    }

    /**
     * @param mixed $contents
     *
     * @return $this
     */
    public function setContents($contents)
    {
        $this->setAttribute('c', $contents);

        return $this;
    }

    /**
     * @param string $attrName
     *
     * @return mixed|null
     */
    public function getAttribute($attrName)
    {
        return isset($this->attributes[$attrName]) ? $this->attributes[$attrName] : null;
    }

    /**
     * @param string $attrName
     * @param mixed  $value
     *
     * @return $this
     */
    public function setAttribute($attrName, $value)
    {
        $this->attributes[$attrName] = $value;

        return $this;
    }
}
