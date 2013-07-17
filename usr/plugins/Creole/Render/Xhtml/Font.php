<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: extra Font rules renderer to size the text
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Font.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Font rule render class (used for BBCode)
 * 
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki::Text_Wiki_Render()
 */
class Text_Wiki_Render_Xhtml_Font extends Text_Wiki_Render {
    
/*    var $size = array(
        'xx-small',
        'x-small',
        'small',
        'medium',
        'large',
        'x-large',
        'xx-large',
        'larger',
        'smaller'
    );
    var $units = array(
        'em',
        'ex',
        'px',
        'in',
        'cm',
        'mm',
        'pt',
        'pc'
    );
*/    
    
    /**
      * Renders a token into text matching the requested format.
      * process the font size option 
      *
      * @access public
      * @param array $options The "options" portion of the token (second element).
      * @return string The text rendered from the token options.
      */
    function token($options)
    {
        if ($options['type'] == 'end') {
            return '</span>';
        }
        if ($options['type'] != 'start') {
            return '';
        }

        $ret = '<span style="';
        if (isset($options['size'])) {
            $size = trim($options['size']);
            if (is_numeric($size)) {
                $size .= 'px';
            }
            $ret .= "font-size: $size;";
        }
        
        return $ret.'">';
    }
}
?>
