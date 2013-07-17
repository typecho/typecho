<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Format class for the Xhtml rendering
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Xhtml.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Format class for the Xhtml rendering
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml extends Text_Wiki_Render {

    var $conf = array(
        'translate' => HTML_ENTITIES,
        'quotes'    => ENT_COMPAT,
        //'charset'   => 'ISO-8859-1'
        'charset'   => 'UTF-8'
    );

    function pre()
    {
        $this->wiki->source = $this->textEncode($this->wiki->source);
    }

    function post()
    {
        return;
    }


    /**
     * Method to render text
     *
     * @access public
     * @param string $text the text to render
     * @return rendered text
     *
     */

    function textEncode($text)
    {
        // attempt to translate HTML entities in the source.
        // get the config options.
        $type = $this->getConf('translate', HTML_ENTITIES);
        $quotes = $this->getConf('quotes', ENT_COMPAT);
        //$charset = $this->getConf('charset', 'ISO-8859-1');
        $charset = $this->getConf('charset', 'UTF-8');

        // have to check null and false because HTML_ENTITIES is a zero
        if ($type === HTML_ENTITIES) {

            // keep a copy of the translated version of the delimiter
            // so we can convert it back.
            $new_delim = htmlentities($this->wiki->delim, $quotes, $charset);

            // convert the entities.  we silence the call here so that
            // errors about charsets don't pop up, per counsel from
            // Jan at Horde.  (http://pear.php.net/bugs/bug.php?id=4474)
            $text = @htmlentities(
                $text,
                $quotes,
                $charset
            );

            // re-convert the delimiter
            $text = str_replace(
                $new_delim, $this->wiki->delim, $text
            );

        } elseif ($type === HTML_SPECIALCHARS) {

            // keep a copy of the translated version of the delimiter
            // so we can convert it back.
            $new_delim = htmlspecialchars($this->wiki->delim, $quotes,
                $charset);

            // convert the entities.  we silence the call here so that
            // errors about charsets don't pop up, per counsel from
            // Jan at Horde.  (http://pear.php.net/bugs/bug.php?id=4474)
            $text = @htmlspecialchars(
                $text,
                $quotes,
                $charset
            );

            // re-convert the delimiter
            $text = str_replace(
                $new_delim, $this->wiki->delim, $text
            );
        }
        return $text;
    }
}
?>
