<?php

/**
 *
 * Parses for bold text.
 *
 * This class implements a Text_Wiki_Rule to find source text marked for
 * strong emphasis (bold) as defined by text surrounded by two
 * stars. On parsing, the text itself is left in place, but the
 * starting and ending instances of two stars are replaced with
 * tokens.
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
 * @version $Id: Strong.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Strong extends Text_Wiki_Parse {


    /**
     *
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     *
     * @var string
     *
     * @see parse()
     *
     */

    //var $regex =  "/\*\*(.*?)\*\*/";
    var $regex =  "/(?:\*\*(.+?)\*\*|(?:(?<=[\W_\xFF])\*(?![ \*]))(.+?)(?:(?<![ \*])\*(?=[\W_\xFF])))/";


    /**
     *
     * Generates a replacement for the matched text.  Token options are:
     *
     * 'type' => ['start'|'end'] The starting or ending point of the
     * emphasized text.  The text itself is left in the source.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return A pair of delimited tokens to be used as a placeholder in
     * the source text surrounding the text to be emphasized.
     *
     */

    function process(&$matches)
    {
        $text = $matches[1] ? $matches[1] : $matches[2];
        
        if (! $this->wiki->checkInnerTags($text)) {
            return $matches[0];
        }

        $start = $this->wiki->addToken(
            $this->rule,
            array('type' => 'start')
        );

        $end = $this->wiki->addToken(
            $this->rule,
            array('type' => 'end')
        );

        return $start . $text . $end;
    }
}
?>