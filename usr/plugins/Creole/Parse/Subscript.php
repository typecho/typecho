<?php

/**
 *
 * Parses for italic text.
 *
 * This class implements a Text_Wiki_Parse to find source text marked for
 * superscript as defined by text surrounded by two '^'.
 * On parsing, the text itself is left in place, but the starting and ending
 * instances of two '^' are replaced with tokens.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Paul M. Jones <pmjones@php.net>
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 *
 */

class Text_Wiki_Parse_Subscript extends Text_Wiki_Parse {


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

    var $regex =  "/\,\,(.*?)\,\,/";

    /**
     *
     * Generates a replacement for the matched text.  Token options are:
     *
     * 'type' => ['start'|'end'] The starting or ending point of the
     * superscript text.  The text itself is left in the source.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A pair of delimited tokens to be used as a
     * placeholder in the source text surrounding the text to be
     * superscripted.
     *
     */

    function process(&$matches)
    {
        if (! $this->wiki->checkInnerTags($matches[1])) {
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

        return $start . $matches[1] . $end;
    }
}
?>