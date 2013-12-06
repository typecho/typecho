<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Base rendering class for parsed and tokenized text.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Render.inc.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Base rendering class for parsed and tokenized text.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render {


    /**
    *
    * Configuration options for this render rule.
    *
    * @access public
    *
    * @var string
    *
    */

    var $conf = array();


    /**
    *
    * The name of this rule's format.
    *
    * @access public
    *
    * @var string
    *
    */

    var $format = null;


    /**
    *
    * The name of this rule's token array elements.
    *
    * @access public
    *
    * @var string
    *
    */

    var $rule = null;


    /**
    *
    * A reference to the calling Text_Wiki object.
    *
    * This is needed so that each rule has access to the same source
    * text, token set, URLs, interwiki maps, page names, etc.
    *
    * @access public
    *
    * @var object
    */

    var $wiki = null;


    /**
    *
    * Constructor for this render format or rule.
    *
    * @access public
    *
    * @param object &$obj The calling "parent" Text_Wiki object.
    *
    */

    function Text_Wiki_Render(&$obj)
    {
        // keep a reference to the calling Text_Wiki object
        $this->wiki =& $obj;

        // get the config-key-name for this object,
        // strip the Text_Wiki_Render_ part
        //           01234567890123456
        $tmp = get_class($this);
        $tmp = substr($tmp, 17);

        // split into pieces at the _ mark.
        // first part is format, second part is rule.
        $part   = explode('_', $tmp);
        $this->format = isset($part[0]) ? ucwords(strtolower($part[0])) : null;
        $this->rule   = isset($part[1]) ? ucwords(strtolower($part[1])) : null;

        // is there a format but no rule?
        // then this is the "main" render object, with
        // pre() and post() methods.
        if ($this->format && ! $this->rule &&
            isset($this->wiki->formatConf[$this->format]) &&
            is_array($this->wiki->formatConf[$this->format])) {

            // this is a format render object
            $this->conf = array_merge(
                $this->conf,
                $this->wiki->formatConf[$this->format]
            );

        }

        // is there a format and a rule?
        if ($this->format && $this->rule &&
            isset($this->wiki->renderConf[$this->format][$this->rule]) &&
            is_array($this->wiki->renderConf[$this->format][$this->rule])) {

            // this is a rule render object
            $this->conf = array_merge(
                $this->conf,
                $this->wiki->renderConf[$this->format][$this->rule]
            );
        }
    }


    /**
    *
    * Simple method to safely get configuration key values.
    *
    * @access public
    *
    * @param string $key The configuration key.
    *
    * @param mixed $default If the key does not exist, return this value
    * instead.
    *
    * @return mixed The configuration key value (if it exists) or the
    * default value (if not).
    *
    */

    function getConf($key, $default = null)
    {
        if (isset($this->conf[$key])) {
            return $this->conf[$key];
        } else {
            return $default;
        }
    }


    /**
    *
    * Simple method to wrap a configuration in an sprintf() format.
    *
    * @access public
    *
    * @param string $key The configuration key.
    *
    * @param string $format The sprintf() format string.
    *
    * @return mixed The formatted configuration key value (if it exists)
    * or null (if it does not).
    *
    */

    function formatConf($format, $key)
    {
        if (isset($this->conf[$key])) {
            //$this->conf[$key] needs a textEncode....at least for Xhtml output...
            return sprintf($format, $this->conf[$key]);
        } else {
            return null;
        }
    }

    /**
    * Default method to render url
    *
    * @access public
    * @param string $urlChunk a part of an url to render
    * @return rendered url
    *
    */

    function urlEncode($urlChunk)
    {
        return rawurlencode($urlChunk);
    }

    /**
    * Default method to render text (htmlspecialchars)
    *
    * @access public
    * @param string $text the text to render
    * @return rendered text
    *
    */

    function textEncode($text)
    {
        return htmlspecialchars($text);
    }
}
?>
