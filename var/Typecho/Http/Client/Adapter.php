<?php

namespace Typecho\Http\Client;

use Typecho\Common;
use Typecho\Http\Client;

/**
 * 客户端适配器
 *
 * @author qining
 * @category typecho
 * @package Http
 */
abstract class Adapter
{
    /**
     * 方法名
     *
     * @access protected
     * @var string
     */
    protected $method = Client::METHOD_GET;

    /**
     * 传递参数
     *
     * @access protected
     * @var string
     */
    protected $query;

    /**
     * 设置超时
     *
     * @access protected
     * @var string
     */
    protected $timeout = 3;

    /**
     * 需要在body中传递的值
     *
     * @access protected
     * @var array|string
     */
    protected $data = [];

    /**
     * 文件列表
     *
     * @access protected
     * @var array
     */
    protected $files = [];

    /**
     * 头信息参数
     *
     * @access protected
     * @var array
     */
    protected $headers = [];

    /**
     * cookies
     *
     * @access protected
     * @var array
     */
    protected $cookies = [];

    /**
     * 协议名称及版本
     *
     * @access protected
     * @var string
     */
    protected $rfc = 'HTTP/1.1';

    /**
     * 请求地址
     *
     * @access protected
     * @var string
     */
    protected $url;

    /**
     * 主机名
     *
     * @access protected
     * @var string
     */
    protected $host;

    /**
     * 前缀
     *
     * @access protected
     * @var string
     */
    protected $scheme = 'http';

    /**
     * 路径
     *
     * @access protected
     * @var string
     */
    protected $path = '/';

    /**
     * 设置ip
     *
     * @access protected
     * @var string
     */
    protected $ip;

    /**
     * 端口
     *
     * @access protected
     * @var integer
     */
    protected $port = 80;

    /**
     * 回执头部信息
     *
     * @access protected
     * @var array
     */
    protected $responseHeader = [];

    /**
     * 回执代码
     *
     * @access protected
     * @var integer
     */
    protected $responseStatus;

    /**
     * 回执身体
     *
     * @access protected
     * @var string
     */
    protected $responseBody;

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    abstract public static function isAvailable(): bool;

    /**
     * 设置指定的COOKIE值
     *
     * @access public
     * @param string $key 指定的参数
     * @param mixed $value 设置的值
     * @return $this
     */
    public function setCookie(string $key, $value): Adapter
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * 设置传递参数
     *
     * @access public
     * @param mixed $query 传递参数
     * @return $this
     */
    public function setQuery($query): Adapter
    {
        $query = is_array($query) ? http_build_query($query) : $query;
        $this->query = empty($this->query) ? $query : $this->query . '&' . $query;
        return $this;
    }

    /**
     * 设置需要POST的数据
     *
     * @access public
     * @param array|string $data 需要POST的数据
     * @return $this
     */
    public function setData($data): Adapter
    {
        $this->data = $data;
        $this->setMethod(Client::METHOD_POST);
        return $this;
    }

    /**
     * 设置方法名
     *
     * @access public
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): Adapter
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置需要POST的文件
     *
     * @access public
     * @param array $files 需要POST的文件
     * @return $this
     */
    public function setFiles(array $files): Adapter
    {
        $this->files = empty($this->files) ? $files : array_merge($this->files, $files);
        $this->setMethod(Client::METHOD_POST);
        return $this;
    }

    /**
     * 设置超时时间
     *
     * @access public
     * @param integer $timeout 超时时间
     * @return $this
     */
    public function setTimeout(int $timeout): Adapter
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 设置http协议
     *
     * @access public
     * @param string $rfc http协议
     * @return $this
     */
    public function setRfc(string $rfc): Adapter
    {
        $this->rfc = $rfc;
        return $this;
    }

    /**
     * 设置ip地址
     *
     * @access public
     * @param string $ip ip地址
     * @return $this
     */
    public function setIp(string $ip): Adapter
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * 发送请求
     *
     * @access public
     * @param string $url 请求地址
     * @return string|null
     * @throws Exception
     */
    public function send(string $url): ?string
    {
        $params = parse_url($url);

        if (!empty($params['host'])) {
            $this->host = $params['host'];
        } else {
            throw new Exception('Unknown Host', 500);
        }

        if (!in_array($params['scheme'], ['http', 'https'])) {
            throw new Exception('Unknown Scheme', 500);
        }

        if (!empty($params['path'])) {
            $this->path = $params['path'];
        }

        $query = empty($params['query']) ? '' : $params['query'];

        if (!empty($this->query)) {
            $query = empty($query) ? $this->query : '&' . $this->query;
        }

        if (!empty($query)) {
            $this->path .= '?' . $query;
            $params['query'] = $query;
        }

        $this->scheme = $params['scheme'];
        $this->port = ('https' == $params['scheme']) ? 443 : 80;
        $url = Common::buildUrl($params);

        if (!empty($params['port'])) {
            $this->port = $params['port'];
        }

        /** 整理cookie */
        if (!empty($this->cookies)) {
            $this->setHeader('Cookie', str_replace('&', '; ', http_build_query($this->cookies)));
        }

        $response = $this->httpSend($url);

        if (!$response) {
            return null;
        }

        str_replace("\r", '', $response);
        $rows = explode("\n", $response);

        $foundStatus = false;
        $foundInfo = false;
        $lines = [];

        foreach ($rows as $key => $line) {
            if (!$foundStatus) {
                if (0 === strpos($line, "HTTP/")) {
                    if ('' == trim($rows[$key + 1])) {
                        continue;
                    } else {
                        $status = explode(' ', str_replace('  ', ' ', $line));
                        $this->responseStatus = intval($status[1]);
                        $foundStatus = true;
                    }
                }
            } else {
                if (!$foundInfo) {
                    if ('' != trim($line)) {
                        $status = explode(':', $line);
                        $name = strtolower(array_shift($status));
                        $data = implode(':', $status);
                        $this->responseHeader[trim($name)] = trim($data);
                    } else {
                        $foundInfo = true;
                    }
                } else {
                    $lines[] = $line;
                }
            }
        }

        $this->responseBody = implode("\n", $lines);
        return $this->responseBody;
    }

    /**
     * 设置头信息参数
     *
     * @access public
     * @param string $key 参数名称
     * @param string $value 参数值
     * @return $this
     */
    public function setHeader(string $key, string $value): Adapter
    {
        $key = str_replace(' ', '-', ucwords(str_replace('-', ' ', $key)));
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * 需要实现的请求方法
     *
     * @access public
     * @param string $url 请求地址
     * @return string
     */
    abstract protected function httpSend(string $url): string;

    /**
     * 获取回执的头部信息
     *
     * @access public
     * @param string $key 头信息名称
     * @return string
     */
    public function getResponseHeader(string $key): ?string
    {
        $key = strtolower($key);
        return $this->responseHeader[$key] ?? null;
    }

    /**
     * 获取回执代码
     *
     * @access public
     * @return integer
     */
    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    /**
     * 获取回执身体
     *
     * @access public
     * @return string
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
