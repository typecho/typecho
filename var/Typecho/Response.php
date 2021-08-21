<?php

namespace Typecho;

/**
 * Typecho公用方法
 *
 * @category typecho
 * @package Response
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Response
{
    public const CHARSET = 'UTF-8';

    /**
     * http code
     *
     * @access private
     * @var array
     */
    private const HTTP_CODE = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    //默认的字符编码
    /**
     * 单例句柄
     *
     * @access private
     * @var Response
     */
    private static $instance;

    /**
     * 结束前回调函数
     *
     * @var array
     */
    private static $callback = [];

    /**
     * 字符编码
     *
     * @var mixed
     * @access private
     */
    private $charset;

    /**
     * 获取单例句柄
     *
     * @access public
     * @return Response
     */
    public static function getInstance(): Response
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 新增回调
     *
     * @param $callback
     */
    public static function addCallback($callback)
    {
        self::$callback[] = $callback;
    }

    /**
     * 设置HTTP状态
     *
     * @access public
     * @param integer $code http代码
     * @return void
     */
    public static function setStatus(int $code)
    {
        if (isset(self::HTTP_CODE[$code])) {
            header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1')
                . ' ' . $code . ' ' . self::HTTP_CODE[$code], true, $code);
        }
    }

    /**
     * 设置http头
     *
     * @access public
     * @param string $name 名称
     * @param string $value 对应值
     * @return void
     */
    public function setHeader(string $name, string $value)
    {
        header($name . ': ' . $value, true);
    }

    /**
     * 抛出ajax的回执信息
     *
     * @access public
     * @param string $message 消息体
     * @return void
     */
    public function throwXml(string $message)
    {
        /** 设置http头信息 */
        $this->setContentType('text/xml');

        /** 构建消息体 */
        echo '<?xml version="1.0" encoding="' . $this->getCharset() . '"?>',
        '<response>',
        $this->parseXml($message),
        '</response>';

        /** 终止后续输出 */
        self::callback();
        exit;
    }

    /**
     * 在http头部请求中声明类型和字符集
     *
     * @access public
     * @param string $contentType 文档类型
     * @return void
     */
    public function setContentType(string $contentType = 'text/html')
    {
        header('Content-Type: ' . $contentType . '; charset=' . $this->getCharset(), true);
    }

    /**
     * 获取字符集
     *
     * @access public
     * @return string
     */
    public function getCharset(): string
    {
        if (empty($this->charset)) {
            $this->setCharset();
        }

        return $this->charset;
    }

    /**
     * 设置默认回执编码
     *
     * @access public
     * @param string|null $charset 字符集
     * @return void
     */
    public function setCharset(string $charset = null)
    {
        $this->charset = empty($charset) ? self::CHARSET : $charset;
    }

    /**
     * 解析ajax回执的内部函数
     *
     * @access private
     * @param mixed $message 格式化数据
     * @return string
     */
    private function parseXml($message): string
    {
        /** 对于数组型则继续递归 */
        if (is_array($message)) {
            $result = '';

            foreach ($message as $key => $val) {
                $tagName = is_int($key) ? 'item' : $key;
                $result .= '<' . $tagName . '>' . $this->parseXml($val) . '</' . $tagName . '>';
            }

            return $result;
        } else {
            return preg_match("/^[^<>]+$/is", $message) ? $message : '<![CDATA[' . $message . ']]>';
        }
    }

    /**
     * 结束前的统一回调函数
     */
    public static function callback()
    {
        static $called;

        if ($called) {
            return;
        }

        $called = true;
        foreach (self::$callback as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * 抛出json回执信息
     *
     * @access public
     * @param mixed $message 消息体
     * @return void
     */
    public function throwJson($message)
    {
        /** 设置http头信息 */
        $this->setContentType('application/json');

        echo \Json::encode($message);

        /** 终止后续输出 */
        self::callback();
        exit;
    }

    /**
     * 返回来路
     *
     * @access public
     * @param string|null $suffix 附加地址
     * @param string|null $default 默认来路
     */
    public function goBack(string $suffix = null, string $default = null)
    {
        //获取来源
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        //判断来源
        if (!empty($referer)) {
            // ~ fix Issue 38
            if (!empty($suffix)) {
                $parts = parse_url($referer);
                $myParts = parse_url($suffix);

                if (isset($myParts['fragment'])) {
                    $parts['fragment'] = $myParts['fragment'];
                }

                if (isset($myParts['query'])) {
                    $args = [];
                    if (isset($parts['query'])) {
                        parse_str($parts['query'], $args);
                    }

                    parse_str($myParts['query'], $currentArgs);
                    $args = array_merge($args, $currentArgs);
                    $parts['query'] = http_build_query($args);
                }

                $referer = Common::buildUrl($parts);
            }

            $this->redirect($referer, false);
        } elseif (!empty($default)) {
            $this->redirect($default);
        }

        self::callback();
        exit;
    }

    /**
     * 重定向函数
     *
     * @access public
     * @param string $location 重定向路径
     * @param boolean $isPermanently 是否为永久重定向
     * @return void
     */
    public function redirect(string $location, bool $isPermanently = false)
    {
        /** Typecho_Common */
        $location = Common::safeUrl($location);

        self::callback();

        if ($isPermanently) {
            header('Location: ' . $location, false, 301);
        } else {
            header('Location: ' . $location, false, 302);
        }

        exit;
    }
}
