<?php

/**
 *
 * Parses for implied line breaks indicated by newlines.
 * Newlines are not considered if followed by another newline
 * or by one of these chars: * | - # = {
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Newline.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Newline extends Text_Wiki_Parse {


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

    //var $regex = '/(?<!\n)\n(?![\n\#\=\|\-\>\:]|\*[^\*\#]|\*+ )/m';
    var $regex = '/(?<!\n)\n(?!\n|\#|\*|\=|\||\>|\:|\!|\-\D)/m';


    /**
     *
     * Generates a replacement token for the matched text.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A delimited token to be used as a placeholder in
     * the source text.
     *
     */

    function process(&$matches)
    {
        return ' '; // $this->wiki->addToken($this->rule);
    }
}

?>