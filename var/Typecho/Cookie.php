<?php

namespace Typecho;

/**
 * cookie支持
 *
 * @author qining
 * @category typecho
 * @package Cookie
 */
class Cookie
{
    /**
     * 前缀
     *
     * @var string
     * @access private
     */
    private static $prefix = '';

    /**
     * 路径
     *
     * @var string
     * @access private
     */
    private static $path = '/';

    /**
     * @var string
     * @access private
     */
    private static $domain = '';

    /**
     * @var bool
     * @access private
     */
    private static $secure = false;

    /**
     * @var bool
     * @access private
     */
    private static $httponly = false;

    /**
     * 获取前缀
     *
     * @access public
     * @return string
     */
    public static function getPrefix(): string
    {
        return self::$prefix;
    }

    /**
     * 设置前缀
     *
     * @param string $url
     *
     * @access public
     * @return void
     */
    public static function setPrefix(string $url)
    {
        self::$prefix = md5($url);
        $parsed = parse_url($url);

        self::$domain = $parsed['host'];
        /** 在路径后面强制加上斜杠 */
        self::$path = empty($parsed['path']) ? '/' : Common::url(null, $parsed['path']);
    }

    /**
     * 获取目录
     *
     * @access public
     * @return string
     */
    public static function getPath(): string
    {
        return self::$path;
    }

    /**
     * @access public
     * @return string
     */
    public static function getDomain(): string
    {
        return self::$domain;
    }

    /**
     * @access public
     * @return bool
     */
    public static function getSecure(): bool
    {
        return self::$secure ?: false;
    }

    /**
     * 设置额外的选项
     *
     * @param array $options
     * @return void
     */
    public static function setOptions(array $options)
    {
        self::$domain = $options['domain'] ?: self::$domain;
        self::$secure = $options['secure'] ? (bool) $options['secure'] : false;
        self::$httponly = $options['httponly'] ? (bool) $options['httponly'] : false;
    }

    /**
     * 获取指定的COOKIE值
     *
     * @param string $key 指定的参数
     * @param string|null $default 默认的参数
     * @return mixed
     */
    public static function get(string $key, ?string $default = null)
    {
        $key = self::$prefix . $key;
        $value = $_COOKIE[$key] ?? $default;
        return is_array($value) ? $default : $value;
    }

    /**
     * 设置指定的COOKIE值
     *
     * @param string $key 指定的参数
     * @param mixed $value 设置的值
     * @param integer $expire 过期时间,默认为0,表示随会话时间结束
     */
    public static function set(string $key, $value, int $expire = 0)
    {
        $key = self::$prefix . $key;
        $_COOKIE[$key] = $value;
        Response::getInstance()->setCookie($key, $value, $expire, self::$path, self::$domain, self::$secure, self::$httponly);
    }

    /**
     * 删除指定的COOKIE值
     *
     * @param string $key 指定的参数
     */
    public static function delete(string $key)
    {
        $key = self::$prefix . $key;
        if (!isset($_COOKIE[$key])) {
            return;
        }

        Response::getInstance()->setCookie($key, '', -1, self::$path, self::$domain, self::$secure, self::$httponly);
        unset($_COOKIE[$key]);
    }
}

