<?php

namespace Typecho\Http;

use Typecho\Common;
use Typecho\Http\Client\Exception;

/**
 * Http客户端
 *
 * @category typecho
 * @package Http
 */
class Client
{
    /** POST方法 */
    public const METHOD_POST = 'POST';

    /** GET方法 */
    public const METHOD_GET = 'GET';

    /** PUT方法 */
    public const METHOD_PUT = 'PUT';

    /** DELETE方法 */
    public const METHOD_DELETE = 'DELETE';

    /**
     * 方法名
     *
     * @var string
     */
    private $method = self::METHOD_GET;

    /**
     * 传递参数
     *
     * @var string
     */
    private $query;

    /**
     * User Agent
     *
     * @var string
     */
    private $agent;

    /**
     * 设置超时
     *
     * @var string
     */
    private $timeout = 3;

    /**
     * @var bool
     */
    private $multipart = true;

    /**
     * 需要在body中传递的值
     *
     * @var array|string
     */
    private $data = [];

    /**
     * 头信息参数
     *
     * @access private
     * @var array
     */
    private $headers = [];

    /**
     * cookies
     *
     * @var array
     */
    private $cookies = [];

    /**
     * @var array
     */
    private $options = [];

    /**
     * 回执头部信息
     *
     * @var array
     */
    private $responseHeader = [];

    /**
     * 回执代码
     *
     * @var integer
     */
    private $responseStatus;

    /**
     * 回执身体
     *
     * @var string
     */
    private $responseBody;

    /**
     * 设置指定的COOKIE值
     *
     * @param string $key 指定的参数
     * @param mixed $value 设置的值
     * @return $this
     */
    public function setCookie(string $key, $value): Client
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * 设置传递参数
     *
     * @param mixed $query 传递参数
     * @return $this
     */
    public function setQuery($query): Client
    {
        $query = is_array($query) ? http_build_query($query) : $query;
        $this->query = empty($this->query) ? $query : $this->query . '&' . $query;
        return $this;
    }

    /**
     * 设置需要POST的数据
     *
     * @param array|string $data 需要POST的数据
     * @param string $method
     * @return $this
     */
    public function setData($data, string $method = self::METHOD_POST): Client
    {
        if (is_array($data) && is_array($this->data)) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data = $data;
        }

        $this->setMethod($method);
        return $this;
    }

    /**
     * 设置方法名
     *
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): Client
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置需要POST的文件
     *
     * @param array $files 需要POST的文件
     * @param string $method
     * @return $this
     */
    public function setFiles(array $files, string $method = self::METHOD_POST): Client
    {
        if (is_array($this->data)) {
            foreach ($files as $name => $file) {
                $this->data[$name] = new \CURLFile($file);
            }
        }

        $this->setMethod($method);
        return $this;
    }

    /**
     * 设置超时时间
     *
     * @param integer $timeout 超时时间
     * @return $this
     */
    public function setTimeout(int $timeout): Client
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * setAgent
     *
     * @param string $agent
     * @return $this
     */
    public function setAgent(string $agent): Client
    {
        $this->agent = $agent;
        return $this;
    }

    /**
     * @param bool $multipart
     * @return $this
     */
    public function setMultipart(bool $multipart): Client
    {
        $this->multipart = $multipart;
        return $this;
    }

    /**
     * @param int $key
     * @param mixed $value
     * @return $this
     */
    public function setOption(int $key, $value): Client
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * 设置头信息参数
     *
     * @param string $key 参数名称
     * @param string $value 参数值
     * @return $this
     */
    public function setHeader(string $key, string $value): Client
    {
        $key = str_replace(' ', '-', ucwords(str_replace('-', ' ', $key)));

        if ($key == 'User-Agent') {
            $this->setAgent($value);
        } else {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * 发送请求
     *
     * @param string $url 请求地址
     * @throws Exception
     */
    public function send(string $url)
    {
        $params = parse_url($url);
        $query = empty($params['query']) ? '' : $params['query'];

        if (!empty($this->query)) {
            $query = empty($query) ? $this->query : '&' . $this->query;
        }

        if (!empty($query)) {
            $params['query'] = $query;
        }

        $url = Common::buildUrl($params);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);

        if (isset($this->agent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        }

        /** 设置header信息 */
        if (!empty($this->headers)) {
            $headers = [];

            foreach ($this->headers as $key => $val) {
                $headers[] = $key . ': ' . $val;
            }

            if (!empty($this->cookies)) {
                $headers[] = 'Cookie: ' . str_replace('&', '; ', http_build_query($this->cookies));
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($this->data)) {
            $data = $this->data;

            if (!$this->multipart) {
                curl_setopt($ch, CURLOPT_POST, true);
                $data = is_array($data) ? http_build_query($data) : $data;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) {
            $parts = explode(':', $header, 2);

            if (count($parts) == 2) {
                [$key, $value] = $parts;
                $this->responseHeader[strtolower(trim($key))] = trim($value);
            }

            return strlen($header);
        });

        foreach ($this->options as $key => $val) {
            curl_setopt($ch, $key, $val);
        }

        $response = curl_exec($ch);
        if (false === $response) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error, 500);
        }

        $this->responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->responseBody = $response;
        curl_close($ch);
    }

    /**
     * 获取回执的头部信息
     *
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
     * @return integer
     */
    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    /**
     * 获取回执身体
     *
     * @return string
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * 获取可用的连接
     *
     * @return ?Client
     */
    public static function get(): ?Client
    {
        return extension_loaded('curl') ? new static() : null;
    }
}
