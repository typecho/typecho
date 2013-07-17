<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: DbException.php 52 2008-03-18 08:04:01Z magike.net $
 */

/** 异常基类 */
require_once 'Typecho/Exception.php';

/**
 * Http客户端异常类
 *
 * @package Http
 */
class Typecho_Http_Client_Exception extends Typecho_Exception
{}
