<?php

namespace Typecho\Http\Client;

use Typecho\Exception as TypechoException;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Http客户端异常类
 *
 * @package Http
 */
class Exception extends TypechoException
{
}
