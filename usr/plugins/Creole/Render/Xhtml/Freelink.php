<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Freelink rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Freelink.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * The wikilink render class.
 */
require_once 'Text/Wiki/Render/Xhtml/Wikilink.php';

/**
 * This class renders free links in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Freelink extends Text_Wiki_Render_Xhtml_Wikilink {
    // renders identically to wikilinks, only the parsing is different :-)
}

?>
