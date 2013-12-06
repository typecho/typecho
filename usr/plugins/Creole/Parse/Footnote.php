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
 * @version $Id: Footnote.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Footnote extends Text_Wiki_Parse {


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

    var $regex =  "/(\n)*\[([0-9]+)\]/";


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
        $id = $matches[2];
        
        if ($matches[1] == "\n") {
            $matches[1] = "\n\n";
            $name = "fn$id";
            $href = "#ref$id";
        }
        else {
            $name = "ref$id";
            $href = "#fn$id";
        }
        
        $token = $this->wiki->addToken(
            'Url',
            array('text' => "[$id]", 'href' => $href, 'name' => $name, 'type' => 'inline')
        );

        return $matches[1] . $token;
    }
}
?>