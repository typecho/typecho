<?php

namespace Typecho\Widget;

use Typecho\Config;
use Typecho\Request as HttpRequest;
use Typecho\Response as HttpResponse;

/**
 * sandbox env
 */
class Sandbox
{
    /**
     * @var Config
     */
    private $params;

    /**
     * @param Config $params
     */
    public function __construct(Config $params)
    {
        $this->params = $params;
    }

    /**
     * @param mixed $params
     * @return Sandbox
     */
    public static function factory($params = null): Sandbox
    {
        return new self(new Config($params));
    }

    /**
     * run function in a sandbox
     *
     * @param callable $call
     * @return mixed
     */
    public function run(callable $call)
    {
        HttpRequest::getInstance()->beginSandbox($this->params);
        HttpResponse::getInstance()->beginSandbox();

        try {
            $result = call_user_func($call);
        } catch (Terminal $e) {
            $result = null;
        } finally {
            HttpResponse::getInstance()->endSandbox();
            HttpRequest::getInstance()->endSandbox();
        }

        return $result;
    }
}
