<?php

namespace Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho\Db\Exception as DbException;

/**
 * 数据库连接异常类
 *
 * @package Db
 */
class ConnectionException extends DbException
{
}
