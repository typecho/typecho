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

interface CommonMark_Element_InlineElementInterface
{
    /**
     * @return $string
     */
    public function getType();

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type);

    /**
     * @return mixed
     */
    public function getContents();

    /**
     * @param mixed $contents
     *
     * @return $this
     */
    public function setContents($contents);

    /**
     * @param string $attrName
     *
     * @return mixed
     */
    public function getAttribute($attrName);

    /**
     * @param string $attrName
     * @param mixed  $value
     *
     * @return $this
     */
    public function setAttribute($attrName, $value);
}
