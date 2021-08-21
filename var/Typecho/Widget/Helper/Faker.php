<?php

namespace Typecho\Widget\Helper;

/**
 * widget对象帮手,用于处理空对象方法
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Faker
{
    /**
     * 单例句柄
     *
     * @access private
     * @var Faker
     */
    private static $instance = null;

    /**
     * 获取单例句柄
     *
     * @access public
     * @return Faker
     */
    public static function getInstance(): Faker
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 所有方法请求直接返回
     *
     * @access public
     * @param string $name 方法名
     * @param array $args 参数列表
     * @return void
     */
    public function __call(string $name, array $args)
    {
    }
}
