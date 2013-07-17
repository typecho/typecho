<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Revise rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Revise.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders revision marks in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Revise extends Text_Wiki_Render {

    var $conf = array(
        'css_ins' => null,
        'css_del' => null
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
        if ($options['type'] == 'del_start') {
            $css = $this->formatConf(' class="%s"', 'css_del');
            return "<del$css>";
        }

        if ($options['type'] == 'del_end') {
            return "</del>";
        }

        if ($options['type'] == 'ins_start') {
            $css = $this->formatConf(' class="%s"', 'css_ins');
            return "<ins$css>";
        }

        if ($options['type'] == 'ins_end') {
            return "</ins>";
        }
    }
}
?>
