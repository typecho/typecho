<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Interwiki rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Interwiki.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders inter wikis links in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Interwiki extends Text_Wiki_Render {

    var $conf = array(
        'sites' => array(
            'MeatBall' => 'http://www.usemod.com/cgi-bin/mb.pl?%s',
            'Advogato' => 'http://advogato.org/%s',
            'Wiki'       => 'http://c2.com/cgi/wiki?%s'
        ),
        'target' => '_blank',
        'css' => null
    );


    /**
    *
    * Renders a token into text matching the requested format.
    *
    * @access public
    *
    * @param array $options The "options" portion of the token (second
    * element).
    *
    * @return string The text rendered from the token options.
    *
    */

    function token($options)
    {
        $text = $options['text'];
        if (isset($options['url'])) {
            // calculated by the parser (e.g. Mediawiki)
            $href = $options['url'];
        } else {
            $site = $options['site'];
            // toggg 2006/02/05 page name must be url encoded (e.g. may contain spaces)
            $page = $this->urlEncode($options['page']);

            if (isset($this->conf['sites'][$site])) {
                $href = $this->conf['sites'][$site];
            } else {
                return $text;
            }

            // old form where page is at end,
            // or new form with %s placeholder for sprintf()?
            if (strpos($href, '%s') === false) {
                // use the old form
                $href = $href . $page;
            } else {
                // use the new form
                $href = sprintf($href, $page);
            }
        }

        // allow for alternative targets
        $target = $this->getConf('target');

        // build base link
        $css = $this->formatConf(' class="%s"', 'css');
        $text = $this->textEncode($text);
        $output = "<a$css href=\"$href\"";

        // are we targeting a specific window?
        if ($target && $target != '_self') {
            // this is XHTML compliant, suggested by Aaron Kalin.
            // code tip is actually from youngpup.net, and it
            // uses the $target as the new window name.
            $target = $this->textEncode($target);
            $output .= " onclick=\"window.open(this.href, '$target');";
            $output .= " return false;\"";
        }

        $output .= ">$text</a>";

        return $output;
    }
}
?>
