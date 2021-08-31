<?php

namespace IXR;

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
     * 地址
     *
     * @var string
     */
    private $url;

    /**
     * 消息体
     *
     * @var Message
     */
    private $message;

    /**
     * 调试开关
     *
     * @var boolean
     */
    private $debug = false;

    /**
     * 请求前缀
     *
     * @var string|null
     */
    private $prefix;

    /**
     * @var Error
     */
    private $error;

    /**
     * 客户端构造函数
     *
     * @param string $url 服务端地址
     * @param string|null $prefix
     * @return void
     */
    public function __construct(
        string $url,
        ?string $prefix = null
    ) {
        $this->url = $url;
        $this->prefix = $prefix;
    }

    /**
     * 设置调试模式
     * @deprecated
     */
    public function setDebug()
    {
        $this->debug = true;
    }

    /**
     * 执行请求
     *
     * @param string $method
     * @param array $args
     * @return bool
     */
    private function rpcCall(string $method, array $args): bool
    {
        $request = new Request($method, $args);
        $xml = $request->getXml();

        $client = HttpClient::get();
        if (!$client) {
            $this->error = new Error(-32300, 'transport error - could not open socket');
            return false;
        }

        try {
            $client->setHeader('Content-Type', 'text/xml')
                ->setHeader('User-Agent', self::DEFAULT_USERAGENT)
                ->setData($xml)
                ->send($this->url);
        } catch (HttpClient\Exception $e) {
            $this->error = new Error(-32700, $e->getMessage());
            return false;
        }

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
     * @param string $prefix 前缀
     * @return Client
     */
    public function __get(string $prefix): Client
    {
        return new self($this->url, $this->prefix . $prefix . '.');
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
        $return = $this->rpcCall($this->prefix . $method, $args);

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
