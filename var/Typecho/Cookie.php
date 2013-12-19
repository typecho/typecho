<?php
/**
 * cookie支持
 *
 * @category typecho
 * @package Cookie
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/** 载入api支持 */
require_once 'Typecho/Common.php';

/**
 * cookie支持
 *
 * @author qining
 * @category typecho
 * @package Cookie
 */
class Typecho_Cookie
{
    /**
     * 前缀
     * 
     * @var string
     * @access private
     */
    private static $_prefix = '';

    /**
     * 路径
     * 
     * @var string
     * @access private
     */
    private static $_path = '/';

    /**
     * 设置前缀 
     * 
     * @param string $url
     * @access public
     * @return void
     */
    public static function setPrefix($url)
    {
        self::$_prefix = md5($url);
        $parsed = parse_url($url);

        /** 在路径后面强制加上斜杠 */
        self::$_path = empty($parsed['path']) ? '/' : Typecho_Common::url(NULL, $parsed['path']);
    }

    /**
     * 获取前缀 
     * 
     * @access public
     * @return void
     */
    public static function getPrefix()
    {
        return self::$_prefix;
    }

    /**
     * 获取指定的COOKIE值
     *
     * @access public
     * @param string $key 指定的参数
     * @param string $default 默认的参数
     * @return mixed
     */
    public static function get($key, $default = NULL)
    {
        $key = self::$_prefix . $key;
        $value = isset($_COOKIE[$key]) ? $_COOKIE[$key] : (isset($_POST[$key]) ? $_POST[$key] : $default);
        return $value;
    }

    /**
     * 设置指定的COOKIE值
     *
     * @access public
     * @param string $key 指定的参数
     * @param mixed $value 设置的值
     * @param integer $expire 过期时间,默认为0,表示随会话时间结束
     * @return void
     */
    public static function set($key, $value, $expire = 0)
    {
        $key = self::$_prefix . $key;

        /** 对数组型COOKIE的写入支持 */
        if (is_array($value)) {
            foreach ($value as $name => $val) {
                setrawcookie("{$key}[{$name}]", rawurlencode($val), $expire, self::$_path);
            }
        } else {
            setrawcookie($key, rawurlencode($value), $expire, self::$_path);
        }
        
        $_COOKIE[$key] = $value;
    }

    /**
     * 删除指定的COOKIE值
     *
     * @access public
     * @param string $key 指定的参数
     * @return void
     */
    public static function delete($key)
    {
        $key = self::$_prefix . $key;
        if (!isset($_COOKIE[$key])) {
            return;
        }

        /** 对数组型COOKIE的删除支持 */
        if (is_array($_COOKIE[$key])) {
            foreach ($_COOKIE[$key] as $name => $val) {
                setcookie("{$key}[{$name}]", '', time() - 2592000, self::$_path);
            }
        } else {
            setcookie($key, '', time() - 2592000, self::$_path);
        }

        unset($_COOKIE[$key]);
    }
}

