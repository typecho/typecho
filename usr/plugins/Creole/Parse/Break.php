<?php

/**
* 
* Parses for explicit line breaks.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Break.php 182 2008-09-14 15:56:00Z i.feelinglucky $
* 
*/

/**
* 
* Parses for explicit line breaks.
* 
* This class implements a Text_Wiki_Parse to mark forced line breaks in the
* source text.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Break extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    * 
    * @access public
    * 
    * @var string
    * 
    * @see parse()
    * 
    */
    
    //var $regex = "/[ \n]*([\\\][\\\]|\%\%\%)[ \n]*/";
    var $regex = "/ *([\\\][\\\]|\%\%\%)\n?/";
    
    
    /**
    * 
    * Generates a replacement token for the matched text.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A delimited token to be used as a placeholder in
    * the source text.
    *
    */
    
    function process(&$matches)
    {    
        return $this->wiki->addToken($this->rule);
    }
}

?>