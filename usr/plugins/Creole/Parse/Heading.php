<?php

/**
 *
 * Parses for heading text.
 *
 * This class implements a Text_Wiki_Parse to find source text marked to
 * be a heading element, as defined by text on a line by itself prefixed
 * with a number of equasl signs (=), determining the heading level.
 * Equal signs at the end of the line are silently removed.
 * The heading text itself is left in the source, but is prefixed and
 * suffixed with delimited tokens marking the start and end of the heading.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Paul M. Jones <pmjones@php.net>
 * @author Tomaiuolo Michele <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Heading.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Heading extends Text_Wiki_Parse {


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

    var $regex = '/^(={1,6}) *(.*?) *=*$/m';

    var $conf = array(
        'id_prefix' => 'toc'
    );

    /**
     *
     * Generates a replacement for the matched text.  Token options are:
     *
     * 'type' => ['start'|'end'] The starting or ending point of the
     * heading text.  The text itself is left in the source.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A pair of delimited tokens to be used as a
     * placeholder in the source text surrounding the heading text.
     *
     */

    function process(&$matches)
    {
        // keep a running count for header IDs.  we use this later
        // when constructing TOC entries, etc.
        static $id;
        if (! isset($id)) {
            $id = 0;
        }

        $prefix = htmlspecialchars($this->getConf('id_prefix'));

        $start = $this->wiki->addToken(
            $this->rule,
            array(
                'type' => 'start',
                'level' => strlen($matches[1]),
                'text' => trim($matches[2]),
                'id' => $prefix . $id ++
            )
        );

        $end = $this->wiki->addToken(
            $this->rule,
            array(
                'type' => 'end',
                'level' => strlen($matches[1])
            )
        );

        return $start . trim($matches[2]) . $end . "\n\n";
    }
}
?>
