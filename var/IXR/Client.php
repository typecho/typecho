<?php

namespace IXR;

use Typecho\Common;
use Typecho\Http\Client as HttpClient;

/**
 * IXR客户端
 * reload by typecho team(http://www.typecho.org)
 *
 * @package IXR
 */
class Client
{
    /** 默认客户端 */
    private const DEFAULT_USERAGENT = 'Typecho XML-RPC PHP Library';

    /**
     * 服务端地址
     *
     * @access private
     * @var string
     */
    private $server;

    /**
     * 端口名称
     *
     * @access private
     * @var integer
     */
    private $port;

    /**
     * 路径名称
     *
     * @access private
     * @var string
     */
    private $path;

    /**
     * 地址
     *
     * @access private
     * @var string
     */
    private $url;

    /**
     * 客户端
     *
     * @access private
     * @var string
     */
    private $useragent;

    /**
     * 消息体
     *
     * @var Message
     */
    private $message;

    /**
     * 调试开关
     *
     * @access private
     * @var boolean
     */
    private $debug = false;

    /**
     * 请求前缀
     *
     * @access private
     * @var string|null
     */
    private $prefix = null;

    /**
     * @var Error
     */
    private $error;

    /**
     * 客户端构造函数
     *
     * @access public
     * @param string $server 服务端地址
     * @param string|null $path 路径名称
     * @param integer $port 端口名称
     * @param string|null $useragent 客户端
     * @param string|null $prefix
     * @return void
     */
    public function construct(
        string $server,
        ?string $path = null,
        int $port = 80,
        string $useragent = self::DEFAULT_USERAGENT,
        ?string $prefix = null
    ) {
        if (!$path) {
            $this->url = $server;

            // Assume we have been given a Url instead
            $bits = parse_url($server);
            $this->server = $bits['host'];
            $this->port = $bits['port'] ?? 80;
            $this->path = $bits['path'] ?? '/';

            // Make absolutely sure we have a path
            if (isset($bits['query'])) {
                $this->path .= '?' . $bits['query'];
            }
        } else {
            $this->url = Common::buildUrl([
                'scheme' => 'http',
                'host'   => $server,
                'path'   => $path,
                'port'   => $port
            ]);

            $this->server = $server;
            $this->path = $path;
            $this->port = $port;
        }

        $this->prefix = $prefix;
        $this->useragent = $useragent;
    }

    /**
     * 设置调试模式
     *
     * @access public
     * @return void
     */
    public function setDebug()
    {
        $this->debug = true;
    }

    /**
     * 执行请求
     *
     * @param string $method
     * @param ...$args
     * @return bool
     * @throws HttpClient\Exception
     */
    private function rpcCall(string $method, ...$args): bool
    {
        $request = new Request($method, $args);
        $xml = $request->getXml();

        $client = HttpClient::get();
        if (!$client) {
            $this->error = new Error(-32300, 'transport error - could not open socket');
            return false;
        }

        $client->setHeader('Content-Type', 'text/xml')
            ->setHeader('User-Agent', $this->useragent)
            ->setData($xml)
            ->send($this->url);

        $contents = $client->getResponseBody();

        if ($this->debug) {
            echo '<pre>' . htmlspecialchars($contents) . "\n</pre>\n\n";
        }

        // Now parse what we've got back
        $this->message = new Message($contents);
        if (!$this->message->parse()) {
            // XML error
            $this->error = new Error(-32700, 'parse error. not well formed');
            return false;
        }

        // Is the message a fault?
        if ($this->message->messageType == 'fault') {
            $this->error = new Error($this->message->faultCode, $this->message->faultString);
            return false;
        }

        // Message must be OK
        return true;
    }

    /**
     * 增加前缀
     * <code>
     * $rpc->metaWeblog->newPost();
     * </code>
     *
     * @access public
     * @param string $prefix 前缀
     * @return Client
     */
    public function get(string $prefix): Client
    {
        return new self($this->server, $this->path, $this->port, $this->useragent, $this->prefix . $prefix . '.');
    }

    /**
     * 增加魔术特性
     * by 70
     *
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        array_unshift($args, $this->prefix . $method);
        $return = call_user_func_array([$this, 'rpcCall'], $args);

        if ($return) {
            return $this->getResponse();
        } else {
            throw new Exception($this->getErrorMessage(), $this->getErrorCode());
        }
    }

    /**
     * 获得返回值
     *
     * @return mixed
     */
    public function getResponse()
    {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }

    /**
     * 是否为错误
     *
     * @return bool
     */
    public function isError(): bool
    {
        return isset($this->error);
    }

    /**
     * 获取错误代码
     *
     * @return int
     */
    private function getErrorCode(): int
    {
        return $this->error->code;
    }

    /**
     * 获取错误消息
     *
     * @return string
     */
    private function getErrorMessage(): string
    {
        return $this->error->message;
    }
}
