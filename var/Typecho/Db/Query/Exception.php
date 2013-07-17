<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: DbException.php 52 2008-03-18 08:04:01Z magike.net $
 */

/** 数据库异常基类 */
require_once 'Typecho/Db/Exception.php';

/**
 * 数据库查询异常类
 *
 * @package Db
 */
class Typecho_Db_Query_Exception extends Typecho_Db_Exception
{}
