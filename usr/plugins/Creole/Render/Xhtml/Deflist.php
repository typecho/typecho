<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Deflist rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Deflist.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders definition lists in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Deflist extends Text_Wiki_Render {

    var $conf = array(
        'css_dl' => null,
        'css_dt' => null,
        'css_dd' => null
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
        $type = $options['type'];
        $pad = "    ";

        switch ($type) {

        case 'list_start':
            $css = $this->formatConf(' class="%s"', 'css_dl');
            return "<dl$css>\n";
            break;

        case 'list_end':
            return "</dl>\n\n";
            break;

        case 'term_start':
            $css = $this->formatConf(' class="%s"', 'css_dt');
            return $pad . "<dt$css>";
            break;

        case 'term_end':
            return "</dt>\n";
            break;

        case 'narr_start':
            $css = $this->formatConf(' class="%s"', 'css_dd');
            return $pad . $pad . "<dd$css>";
            break;

        case 'narr_end':
            return "</dd>\n";
            break;

        default:
            return '';

        }
    }
}
?>
