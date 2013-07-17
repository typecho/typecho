<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Paul M. Jones <pmjones@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id: Prefilter.php 182 2008-09-14 15:56:00Z i.feelinglucky $


/**
* 
* This class implements a Text_Wiki_Render_Xhtml to "pre-filter" source text so
* that line endings are consistently \n, lines ending in a backslash \
* are concatenated with the next line, and tabs are converted to spaces.
*
* @author Paul M. Jones <pmjones@php.net>
*
* @package Text_Wiki
*
*/

class Text_Wiki_Render_Plain_Prefilter extends Text_Wiki_Render {
    function token()
    {
        return '';
    }
}
?>