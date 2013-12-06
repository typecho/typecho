<?php

/**
 *
 * Parses for centered text.
 *
 * This class implements a Text_Wiki_Parse to find source text marked to
 * be a center element, as defined by text on a line by itself prefixed
 * with an exclamation mark (!).
 * The centered text itself is left in the source, but is prefixed and
 * suffixed with delimited tokens marking its start and end.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Tomaiuolo Michele <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Center.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Center extends Text_Wiki_Parse {


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

    var $regex = '/^! *(.*?)$/m';

    /**
     *
     * Generates a replacement for the matched text.  Token options are:
     *
     * 'type' => ['start'|'end'] The starting or ending point of the
     * centered text.  The text itself is left in the source.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A pair of delimited tokens to be used as a
     * placeholder in the source text surrounding the centered text.
     *
     */

    function process(&$matches)
    {
        $start = $this->wiki->addToken(
            $this->rule,
            array(
                'type' => 'start'
            )
        );

        $end = $this->wiki->addToken(
            $this->rule,
            array(
                'type' => 'end'
            )
        );

        return $start . trim($matches[1]) . $end . "\n\n";
    }
}
?>
