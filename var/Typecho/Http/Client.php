<?php

namespace Typecho\Http;

use Typecho\Http\Client\Adapter;

/**
 * Http客户端
 *
 * @author qining
 * @category typecho
 * @package Http
 */
class Client
{
    /** POST方法 */
    public const METHOD_POST = 'POST';

    /** GET方法 */
    public const METHOD_GET = 'GET';

    /** 定义行结束符 */
    public const EOL = "\r\n";

    private const ADAPTERS = [Adapter\Curl::class, Adapter\Socket::class];

    /**
     * 获取可用的连接
     *
     * @return ?Adapter
     */
    public static function get(): ?Adapter
    {
        foreach (self::ADAPTERS as $adapter) {
            if (call_user_func([$adapter, 'isAvailable'])) {
                return new $adapter();
            }
        }

        return null;
    }
}
