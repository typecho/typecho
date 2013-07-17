<?php
/**
 * widget对象帮手
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * widget对象帮手,用于处理空对象方法
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_Empty
{
    /**
     * 单例句柄
     *
     * @access private
     * @var Typecho_Widget_Helper_Empty
     */
    private static $_instance = null;

    /**
     * 获取单例句柄
     *
     * @access public
     * @return Typecho_Widget_Helper_Empty
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new Typecho_Widget_Helper_Empty();
        }

        return self::$_instance;
    }

    /**
     * 所有方法请求直接返回
     *
     * @access public
     * @param string $name 方法名
     * @param array $args 参数列表
     * @return void
     */
    public function __call($name, $args)
    {
        return;
    }
}
