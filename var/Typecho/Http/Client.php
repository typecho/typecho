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

    private const ADAPTERS = ['Curl', 'Socket'];

    /**
     * 获取可用的连接
     *
     * @param string ...$adapters
     * @return ?Adapter
     */
    public static function get(string ...$adapters): ?Adapter
    {
        if (empty($adapters)) {
            $adapters = self::ADAPTERS;
        }

        foreach ($adapters as $adapter) {
            $adapterName = 'Typecho_Http_Client_Adapter_' . $adapter;
            if (call_user_func([$adapterName, 'isAvailable'])) {
                return new $adapterName();
            }
        }

        return null;
    }
}
