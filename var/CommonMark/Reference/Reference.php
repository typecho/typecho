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
 * Link reference
 */
class CommonMark_Reference_Reference
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var string
     */
    protected $title;

    /**
     * Constructor
     *
     * @param string $label
     * @param string $destination
     * @param string $title
     */
    public function __construct($label, $destination, $title)
    {
        $this->label = self::normalizeReference($label);
        $this->destination = $destination;
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Normalize reference label
     *
     * This enables case-insensitive label matching
     *
     * @param string $string
     *
     * @return string
     */
    public static function normalizeReference($string)
    {
        // Collapse internal whitespace to single space and remove
        // leading/trailing whitespace
        $string = preg_replace('/\s+/', '', trim($string));

        return Typecho_Common::strToUpper($string, 'UTF-8');
    }
}
