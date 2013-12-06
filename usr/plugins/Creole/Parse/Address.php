<?php

/**
 *
 * Parses for signatures.
 * This class implements a Text_Wiki rule to find sections of the source
 * text that are signatures. A signature is any line starting with exactly
 * two - signs.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Address.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */

class Text_Wiki_Parse_Address extends Text_Wiki_Parse {

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

    var $regex = '/^--([^-].*)$/m';

    /**
     *
     * Generates a token entry for the matched text. Token options are:
     *
     * 'start' => The starting point of the signature.
     *
     * 'end' => The ending point of the signature.
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
        $start = $this->wiki->addToken(
            $this->rule, array('type' => 'start')
        );

        $end = $this->wiki->addToken(
            $this->rule, array('type' => 'end')
        );

        return "\n" . $start . trim($matches[1]) . $end;
    }
}
?>