<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Function rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Function.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders a function description in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Function extends Text_Wiki_Render {

    var $conf = array(
    	// list separator for params and throws
        'list_sep' => ', ',

        // the "main" format string
        'format_main' => '%access %return <b>%name</b> ( %params ) %throws',

        // the looped format string for required params
        'format_param' => '%type <i>%descr</i>',

        // the looped format string for params with default values
        'format_paramd' => '[%type <i>%descr</i> default %default]',

        // the looped format string for throws
        'format_throws' => '<b>throws</b> %type <i>%descr</i>'
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
        extract($options); // name, access, return, params, throws

        // build the baseline output
        $output = $this->conf['format_main'];
        $output = str_replace('%access', $this->textEncode($access), $output);
        $output = str_replace('%return', $this->textEncode($return), $output);
        $output = str_replace('%name', $this->textEncode($name), $output);

        // build the set of params
        $list = array();
        foreach ($params as $key => $val) {

            // is there a default value?
            if ($val['default']) {
                $tmp = $this->conf['format_paramd'];
            } else {
                $tmp = $this->conf['format_param'];
            }

            // add the param elements
            $tmp = str_replace('%type', $this->textEncode($val['type']), $tmp);
            $tmp = str_replace('%descr', $this->textEncode($val['descr']), $tmp);
            $tmp = str_replace('%default', $this->textEncode($val['default']), $tmp);
            $list[] = $tmp;
        }

        // insert params into output
        $tmp = implode($this->conf['list_sep'], $list);
        $output = str_replace('%params', $tmp, $output);

        // build the set of throws
        $list = array();
        foreach ($throws as $key => $val) {
               $tmp = $this->conf['format_throws'];
            $tmp = str_replace('%type', $this->textEncode($val['type']), $tmp);
            $tmp = str_replace('%descr', $this->textEncode($val['descr']), $tmp);
            $list[] = $tmp;
        }

        // insert throws into output
        $tmp = implode($this->conf['list_sep'], $list);
        $output = str_replace('%throws', $tmp, $output);

        // close the div and return the output
        $output .= '</div>';
        return "\n$output\n\n";
    }
}
?>
