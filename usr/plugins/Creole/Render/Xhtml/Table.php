<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Table rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Table.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders tables in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Table extends Text_Wiki_Render {

    var $conf = array(
        'css_table' => null,
        'css_caption' => null,
        'css_tr' => null,
        'css_th' => null,
        'css_td' => null
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
        // make nice variable names (type, attr, span)
        $span = $rowspan = 1;
        extract($options);

        // free format
        $format = isset($format) ? ' '. $format : '';

        $pad = '    ';

        switch ($type) {

        case 'table_start':
            $css = $this->formatConf(' class="%s"', 'css_table');
            return "\n\n<table$css$format>\n";
            break;

        case 'table_end':
            return "</table>\n\n";
            break;

        case 'caption_start':
            $css = $this->formatConf(' class="%s"', 'css_caption');
            return "<caption$css$format>\n";
            break;

        case 'caption_end':
            return "</caption>\n";
            break;

        case 'row_start':
            $css = $this->formatConf(' class="%s"', 'css_tr');
            return "$pad<tr$css$format>\n";
            break;

        case 'row_end':
            return "$pad</tr>\n";
            break;

        case 'cell_start':

            // base html
            $html = $pad . $pad;

            // is this a TH or TD cell?
            if ($attr == 'header') {
                // start a header cell
                $css = $this->formatConf(' class="%s"', 'css_th');
                $html .= "<th$css";
            } else {
                // start a normal cell
                $css = $this->formatConf(' class="%s"', 'css_td');
                $html .= "<td$css";
            }

            // add the column span
            if ($span > 1) {
                $html .= " colspan=\"$span\"";
            }

            // add the row span
            if ($rowspan > 1) {
                $html .= " rowspan=\"$rowspan\"";
            }

            // add alignment
            if ($attr != 'header' && $attr != '') {
                $html .= " style=\"text-align: $attr;\"";
            }

            // done!
            $html .= "$format>";
            return $html;
            break;

        case 'cell_end':
            if ($attr == 'header') {
                return "</th>\n";
            } else {
                return "</td>\n";
            }
            break;

        default:
            return '';

        }
    }
}
?>
