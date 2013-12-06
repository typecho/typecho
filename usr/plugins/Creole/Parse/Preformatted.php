<?php

/**
 *
 * Parses for preformatted text.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Tomaiuolo Michele <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Preformatted.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Preformatted extends Text_Wiki_Parse {


    /**
     *
     * The regular expression used to parse the source text and find
     * matches conforming to this rule. Used by the parse() method.
     *
     * @access public
     *
     * @var string
     *
     * @see parse()
     *
     */

    var $regex = '/\n{{{\n(.*)\n}}}\n/Us';

    /**
     *
     * Generates a replacement for the matched text. Token options are:
     *
     * 'text' => The preformatted text.
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
        // > any line consisting of only indented three closing curly braces
        // > will have one space removed from the indentation
        // > -- http://www.wikicreole.org/wiki/AddNoWikiEscapeProposal
        $find = "/\n( *) }}}/";
        $replace = "\n$1}}}";
        $matches[1] = preg_replace($find, $replace, $matches[1]);
    
        $token = $this->wiki->addToken(
            $this->rule,
            array('text' => $matches[1])
        );
        return "\n\n" . $token . "\n\n";
    }
}
?>
