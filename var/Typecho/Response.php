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
     * 字符编码
     *
     * @var string
     */
    private $charset = 'UTF-8';

    /**
     * @var string
     */
    private $contentType = 'text/html';

    /**
     * @var callable[]
     */
    private $responders = [];

    /**
     * @var array
     */
    private $cookies = [];

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var int
     */
    private $status = 200;

    /**
     * @var bool
     */
    private $enableAutoSendHeaders = true;

    /**
     * init responder
     */
    public function __construct()
    {
        $this->clean();
    }

    /**
     * 获取单例句柄
     *
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
     * @param bool $enable
     */
    public function enableAutoSendHeaders(bool $enable = true)
    {
        $this->enableAutoSendHeaders = $enable;
    }

    /**
     * clean all
     */
    public function clean()
    {
        $this->headers = [];
        $this->cookies = [];
        $this->status = 200;
        $this->responders = [];
        $this->setContentType('text/html');
    }

    /**
     * send all headers
     */
    public function sendHeaders()
    {
        header('HTTP/1.1 ' . $this->status . ' ' . self::HTTP_CODE[$this->status], true, $this->status);

        // set header
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        // set cookie
        foreach ($this->cookies as $cookie) {
            [$key, $value, $timeout, $path, $domain] = $cookie;

            if ($timeout > 0) {
                $timeout += time();
            } elseif ($timeout < 0) {
                $timeout = 1;
            }

            setrawcookie($key, rawurlencode($value), $timeout, $path, $domain);
        }
    }

    /**
     * respond data
     */
    public function respond()
    {
        if ($this->enableAutoSendHeaders) {
            $this->sendHeaders();
        }

        foreach ($this->responders as $responder) {
            call_user_func($responder, $this);
        }

        exit;
    }

    /**
     * 设置HTTP状态
     *
     * @access public
     * @param integer $code http代码
     * @return $this
     */
    public function setStatus(int $code): Response
    {
        $this->status = $code;
        return $this;
    }

    /**
     * 设置http头
     *
     * @param string $name 名称
     * @param string $value 对应值
     * @return $this
     */
    public function setHeader(string $name, string $value): Response
    {
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * 设置指定的COOKIE值
     *
     * @param string $key 指定的参数
     * @param mixed $value 设置的值
     * @param integer $timeout 过期时间,默认为0,表示随会话时间结束
     * @param string $path 路径信息
     * @param string|null $domain 域名信息
     * @return $this
     */
    public function setCookie(
        string $key,
        $value,
        int $timeout = 0,
        string $path = '/',
        string $domain = null
    ): Response {
        $this->cookies[] = [$key, $value, $timeout, $path, $domain];
        return $this;
    }

    /**
     * 在http头部请求中声明类型和字符集
     *
     * @param string $contentType 文档类型
     * @return $this
     */
    public function setContentType(string $contentType = 'text/html'): Response
    {
        $this->contentType = $contentType;
        $this->setHeader('Content-Type', $this->contentType . '; charset=' . $this->charset);
        return $this;
    }

    /**
     * 获取字符集
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * 设置默认回执编码
     *
     * @param string $charset 字符集
     * @return $this
     */
    public function setCharset(string $charset): Response
    {
        $this->charset = $charset;
        $this->setHeader('Content-Type', $this->contentType . '; charset=' . $this->charset);
        return $this;
    }

    /**
     * add responder
     *
     * @param callable $responder
     * @return $this
     */
    public function addResponder(callable $responder): Response
    {
        $this->responders[] = $responder;
        return $this;
    }
}
