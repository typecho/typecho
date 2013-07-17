<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Specialchar rule end renderer for Plain
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Specialchar.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders special characters in Plain.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Plain_SpecialChar extends Text_Wiki_Render {

    var $types = array('~bs~' => '\\',
                       '~hs~' => ' ',
                       '~amp~' => '&',
                       '~ldq~' => '"',
                       '~rdq~' => '"',
                       '~lsq~' => "'",
                       '~rsq~' => "'",
                       '~c~' => '©',
                       '~--~' => '-',
                       '" -- "' => '-',
                       '&quot; -- &quot;' => '-',
                       '~lt~' => '<',
                       '~gt~' => '>');

    function token($options)
    {
        if (isset($this->types[$options['char']])) {
            return $this->types[$options['char']];
        } else {
            return $options['char'];
        }
    }
}

?>
