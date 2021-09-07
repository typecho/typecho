<?php

namespace Typecho;

/**
 * Typecho异常基类
 * 主要重载异常打印函数
 *
 * @package Exception
 */
class Exception extends \Exception
{

    public function __construct($message, $code = 0)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
