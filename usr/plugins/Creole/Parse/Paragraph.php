<?php

/**
 *
 * Parses for paragraph blocks.
 * This class implements a Text_Wiki rule to find sections of the source
 * text that are paragraphs. A paragraph is any line not starting with a
 * token delimiter, followed by two newlines.
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
 * @version $Id: Paragraph.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Paragraph extends Text_Wiki_Parse {

    /**
     *
     * The regular expression used to find source text matching this
     * rule.
     *
     * @access public
     *
     * @var string
     *
     */

    var $regex = "/^.+?\n/m"; // (?=[\n\-\|#{=])

    var $conf = array(
        'skip' => array(
            'address',
            'box',
            'blockquote',
            'code',
            'heading',
            'center',
            'horiz',
            'deflist',
            'table',
            'list',
            'paragraph',
            'preformatted',
            'toc'
        )
    );


    /**
     *
     * Generates a token entry for the matched text. Token options are:
     *
     * 'start' => The starting point of the paragraph.
     *
     * 'end' => The ending point of the paragraph.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return A delimited token number to be used as a placeholder in
     * the source text.
     *
     */

    function process(&$matches)
    {
        $delim = $this->wiki->delim;

        // was anything there?
        if (trim($matches[0]) == '') {
            return '';
        }

        // does the match start with a delimiter?
        if (substr($matches[0], 0, 1) != $delim) {
            // no.

            $start = $this->wiki->addToken(
                $this->rule, array('type' => 'start')
            );

            $end = $this->wiki->addToken(
                $this->rule, array('type' => 'end')
            );

            return $start . trim($matches[0]) . $end;
        }

        // the line starts with a delimiter.  read in the delimited
        // token number, check the token, and see if we should
        // skip it.

        // loop starting at the second character (we already know
        // the first is a delimiter) until we find another
        // delimiter; the text between them is a token key number.
        $key = '';
        $len = strlen($matches[0]);
        for ($i = 1; $i < $len; $i++) {
            $char = $matches[0]{$i};
            if ($char == $delim) {
                break;
            } else {
                $key .= $char;
            }
        }

        // look at the token and see if it's skippable (if we skip,
        // it will not be marked as a paragraph)
        $token_type = strtolower($this->wiki->tokens[$key][0]);
        $skip = $this->getConf('skip', array());

        if (in_array($token_type, $skip)) {
            // this type of token should not have paragraphs applied to it.
            // return the entire matched text.
            return $matches[0];
        } else {

            $start = $this->wiki->addToken(
                $this->rule, array('type' => 'start')
            );

            $end = $this->wiki->addToken(
                $this->rule, array('type' => 'end')
            );

            return $start . trim($matches[0]) . $end;
        }
    }
}
?>