<?php

/**
 *
 * The rule removes all remaining newlines.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Paul M. Jones <pmjones@php.net>
 *
 * @license LGPL
 *
 * @version $Id: Tighten.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */


class Text_Wiki_Parse_Tighten extends Text_Wiki_Parse {


    /**
     *
     * Apply tightening directly to the source text.
     *
     * @access public
     *
     */

    function parse()
    {
        $this->wiki->source = str_replace("\n", '',
            $this->wiki->source);
    }
}
?>