<?php

/**
 *
 * Trim lines in the source text and compress 3 or more newlines to
 * 2 newlines.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Paul M. Jones <pmjones@php.net>
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 *
 */

class Text_Wiki_Parse_Trim extends Text_Wiki_Parse {


    /**
     *
     * Simple parsing method.
     *
     * @access public
     *
     */

    function parse()
    {
        // trim lines
        $find = "/ *\n */";
        $replace = "\n";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // trim lines with only one dash or star
        $find = "/\n[\-\*]\n/";
        $replace = "\n\n";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // finally, compress all instances of 3 or more newlines
        // down to two newlines.
        $find = "/\n{3,}/m";
        $replace = "\n\n";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);
            
        // numbered lists
        $find = "/(\n[\*\#]*)([\d]+[\.\)]|[\w]\))/s";
        $replace = "$1#";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // make ordinal numbers superscripted
        $find = "/([\d])(st|nd|rd|th|er|e|re|ers|res|nds|de|des|ère|ème|ères|èmes|o|a)([\W])/";
        $replace = "$1^^$2^^$3";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // numbers in parentesis are footnotes and references
        $find = "/\(([\d][\d]?)\)/";
        $replace = "[$1]";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // add hr before footnotes
        $find = "/(\n+\-\-\-\-+\n*)?(\n\[[\d]+\].*)/s";
        $replace = "\n\n----\n\n$2";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

    }

}
?>