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
 * A collection of references, indexed by label
 */
class CommonMark_Reference_ReferenceMap
{
    /**
     * @var Reference[]
     */
    protected $references = array();

    /**
     * @param Reference $reference
     *
     * @return $this
     */
    public function addReference(CommonMark_Reference_Reference $reference)
    {
        $key = CommonMark_Reference_Reference::normalizeReference($reference->getLabel());
        $this->references[$key] = $reference;

        return $this;
    }

    /**
     * @param string $label
     *
     * @return bool
     */
    public function contains($label)
    {
        $label = CommonMark_Reference_Reference::normalizeReference($label);

        return isset($this->references[$label]);
    }

    /**
     * @param string $label
     *
     * @return Reference|null
     */
    public function getReference($label)
    {
        $label = CommonMark_Reference_Reference::normalizeReference($label);

        if (isset($this->references[$label])) {
            return $this->references[$label];
        } else {
            return null;
        }
    }
}
