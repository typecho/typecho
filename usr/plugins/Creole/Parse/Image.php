<?php

/**
 *
 * Parse for images in the source text.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Tomaiuolo Michele <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id: Image.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */


class Text_Wiki_Parse_Image extends Text_Wiki_Parse {

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

    var $regex = '/{{(.*)(\|(.*))?}}/U';


    /**
     *
     * Generates a replacement token for the matched text.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A token marking the horizontal rule.
     *
     */

    function process(&$matches)
    {
        $src = trim($matches[1]);
		$src = ltrim($src, '/');
        $alt = isset($matches[3]) ? trim($matches[3]) : null;
        if (!$alt) $alt = $src;

        return $this->wiki->addToken(
            $this->rule,
            array(
                'src' => $src,
                'attr' => array('alt' => $alt, 'title' => $alt)
            )
        );
    }
}
?>
