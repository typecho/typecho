<?php

/**
 *
 * Parses for monospaced inline text.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Tomaiuolo Michele <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Raw.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Raw extends Text_Wiki_Parse {


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

    var $regex = '/~~([^ \n])/';

    /**
     *
     * Generates a replacement for the matched text.  Token options are:
     *
     * 'type' => ['start'|'end'] The starting or ending point of the
     * monospaced text.  The text itself is encapsulated into a Raw token.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A token to be used as a placeholder
     * in the source text for the preformatted text.
     *
     */

    function process(&$matches)
    {
        return $this->wiki->addToken(
            $this->rule,
            array('text' => $matches[1], 'type' => 'escape')
        );
    }
}
?>
