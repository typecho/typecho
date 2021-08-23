<?php

namespace Typecho\Db\Query;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho\Db\Exception as DbException;

/**
 * 数据库查询异常类
 *
 * @package Db
 */
class Exception extends DbException
{
}
