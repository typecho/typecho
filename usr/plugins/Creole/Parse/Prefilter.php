<?php

/**
 *
 * "Pre-filter" the source text.
 *
 * Convert DOS and Mac line endings to Unix, convert tabs to 4-spaces,
 * add newlines to the top and end of the source text.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Paul M. Jones <pmjones@php.net>
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Prefilter.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Prefilter extends Text_Wiki_Parse {


    /**
     *
     * Simple parsing method.
     *
     * @access public
     *
     */

    function parse()
    {
        // convert DOS line endings
        $this->wiki->source = str_replace("\r\n", "\n",
            $this->wiki->source);

        // convert Macintosh line endings
        $this->wiki->source = str_replace("\r", "\n",
            $this->wiki->source);

        // convert tabs to four-spaces
        $this->wiki->source = str_replace("\t", "    ",
            $this->wiki->source);

        // add extra newlines at the top and end; this
        // seems to help many rules.
        $this->wiki->source = "\n\n" . $this->wiki->source . "\n\n";
    }

}
?>