<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Smiley rule Xhtml renderer
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Smiley.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Smiley rule Xhtml render class
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
class Text_Wiki_Render_Xhtml_Smiley extends Text_Wiki_Render {

    /**
     * Configuration keys for this rule
     * 'prefix' => the path to smileys images inclusive file name prefix,
     *             starts with '/' ==> abolute reference
     *             if no file names prefix but some folder, terminates with '/'
     * 'extension' => the file extension (inclusive '.'), e.g. :
     *       if prefix 'smileys/icon_' and extension '.gif'
     *       ':)' whose name is 'smile' will give relative file 'smileys/icon_smile.gif'
     *       if prefix '/image/smileys/' and extension '.png': absolute '/image/smileys/smile.gif'
     * 'css' => optional style applied to smileys
     *
     * @access public
     * @var array 'config-key' => mixed config-value
     */
    var $conf = array(
        'prefix' => 'images/smiles/icon_',
        'extension' => '.gif',
        'css' => null
    );

    /**
      * Renders a token into text matching the requested format.
      * process the Smileys
      *
      * @access public
      * @param array $options The "options" portion of the token (second element).
      * @return string The text rendered from the token options.
      */
    function token($options)
    {
        $imageFile = $this->getConf('prefix') . $options['name'] . $this->getConf('extension');

        // attempt to get the image size
        $imageSize = @getimagesize($imageFile);

        // return the HTML output
        return '<img src="' . $this->textEncode($imageFile) . '"' .
            (is_array($imageSize) ?
                ' width="' . $imageSize[0] . '" height="' . $imageSize[1] .'"' : '') .
            ' alt="' . $options['desc'] . '"' .
            $this->formatConf(' class="%s"', 'css') . ' />';
    }
}
?>
