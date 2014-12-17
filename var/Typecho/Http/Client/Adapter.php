<?php
/**
 * 客户端适配器
 *
 * @author qining
 * @category typecho
 * @package Http
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 客户端适配器
 *
 * @author qining
 * @category typecho
 * @package Http
 */
abstract class Typecho_Http_Client_Adapter
{
    /**
     * 方法名
     *
     * @access protected
     * @var string
     */
    protected $method = Typecho_Http_Client::METHOD_GET;

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
     * @var array
     */
    protected $data = array();

    /**
     * 文件列表
     *
     * @access protected
     * @var array
     */
    protected $files = array();

    /**
     * 头信息参数
     *
     * @access protected
     * @var array
     */
    protected $headers = array();

    /**
     * cookies
     *
     * @access protected
     * @var array
     */
    protected $cookies = array();

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
    protected $responseHeader = array();

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
    public static function isAvailable()
    {
        return true;
    }

    /**
     * 设置方法名
     *
     * @access public
     * @param string $method
     * @return Typecho_Http_Client_Adapter
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置指定的COOKIE值
     *
     * @access public
     * @param string $key 指定的参数
     * @param mixed $value 设置的值
     * @return Typecho_Http_Client_Adapter
     */
    public function setCookie($key, $value)
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * 设置传递参数
     *
     * @access public
     * @param mixed $query 传递参数
     * @return Typecho_Http_Client_Adapter
     */
    public function setQuery($query)
    {
        $query = is_array($query) ? http_build_query($query) : $query;
        $this->query = empty($this->query) ? $query : $this->query . '&' . $query;
        return $this;
    }

    /**
     * 设置需要POST的数据
     *
     * @access public
     * @param array $data 需要POST的数据
     * @return Typecho_Http_Client_Adapter
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->setMethod(Typecho_Http_Client::METHOD_POST);
        return $this;
    }

    /**
     * 设置需要POST的文件
     *
     * @access public
     * @param array $files 需要POST的文件
     * @return Typecho_Http_Client_Adapter
     */
    public function setFiles(array $files)
    {
        $this->files = empty($this->files) ? $files : array_merge($this->files, $files);
        $this->setMethod(Typecho_Http_Client::METHOD_POST);
        return $this;
    }

    /**
     * 设置超时时间
     *
     * @access public
     * @param integer $timeout 超时时间
     * @return Typecho_Http_Client_Adapter
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 设置http协议
     *
     * @access public
     * @param string $rfc http协议
     * @return Typecho_Http_Client_Adapter
     */
    public function setRfc($rfc)
    {
        $this->rfc = $rfc;
        return $this;
    }

    /**
     * 设置ip地址
     *
     * @access public
     * @param string $ip ip地址
     * @return Typecho_Http_Client_Adapter
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * 设置头信息参数
     *
     * @access public
     * @param string $key 参数名称
     * @param string $value 参数值
     * @return Typecho_Http_Client_Adapter
     */
    public function setHeader($key, $value)
    {
        $key = str_replace(' ', '-', ucwords(str_replace('-', ' ', $key)));
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * 发送请求
     *
     * @access public
     * @param string $url 请求地址
     * @param string $rfc 请求协议
     * @return string
     */
    public function send($url)
    {
        $params = parse_url($url);

        if (!empty($params['host'])) {
            $this->host = $params['host'];
        } else {
            throw new Typecho_Http_Client_Exception('Unknown Host', 500);
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
        $url = Typecho_Common::buildUrl($params);

        if (!empty($params['port'])) {
            $this->port = $params['port'];
        }

        /** 整理cookie */
        if (!empty($this->cookies)) {
            $this->setHeader('Cookie', str_replace('&', '; ', http_build_query($this->cookies)));
        }

        $response = $this->httpSend($url);

        if (!$response) {
            return;
        }

        str_replace("\r", '', $response);
        $rows = explode("\n", $response);

        $foundStatus = false;
        $foundInfo = false;
        $lines = array();

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
     * 获取回执的头部信息
     *
     * @access public
     * @param string $key 头信息名称
     * @return string
     */
    public function getResponseHeader($key)
    {
        $key = strtolower($key);
        return isset($this->responseHeader[$key]) ? $this->responseHeader[$key] : NULL;
    }

    /**
     * 获取回执代码
     *
     * @access public
     * @return integer
     */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    /**
     * 获取回执身体
     *
     * @access public
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * 需要实现的请求方法
     *
     * @access public
     * @param string $url 请求地址
     * @return string
     */
    abstract public function httpSend($url);
}
