<?php

namespace Typecho\Widget;

use Typecho\Config;
use Typecho\Request as HttpRequest;

/**
 * Widget Request Wrapper
 */
class Request
{
    /**
     * 支持的过滤器列表
     *
     * @access private
     * @var string
     */
    private const FILTERS = [
        'int'     => 'intval',
        'integer' => 'intval',
        'encode'  => 'urlencode',
        'html'    => 'htmlspecialchars',
        'search'  => ['\Typecho\Common', 'filterSearchQuery'],
        'xss'     => ['\Typecho\Common', 'removeXSS'],
        'url'     => ['\Typecho\Common', 'safeUrl'],
        'slug'    => ['\Typecho\Common', 'slugName']
    ];

    /**
     * 当前过滤器
     *
     * @access private
     * @var array
     */
    private $filter = [];

    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var Config
     */
    private $params;

    /**
     * @param HttpRequest $request
     * @param Config|null $params
     */
    public function __construct(HttpRequest $request, ?Config $params = null)
    {
        $this->request = $request;
        $this->params = $params ?? new Config();
    }

    /**
     * 设置http传递参数
     *
     * @access public
     *
     * @param string $name 指定的参数
     * @param mixed $value 参数值
     *
     * @return void
     */
    public function setParam(string $name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * 设置多个参数
     *
     * @access public
     *
     * @param mixed $params 参数列表
     *
     * @return void
     */
    public function setParams($params)
    {
        $this->params->setDefault($params);
    }

    /**
     * Add filter to request
     *
     * @param string|callable ...$filters
     * @return $this
     */
    public function filter(...$filters): Request
    {
        foreach ($filters as $filter) {
            $this->filter[] = $this->wrapFilter(
                is_string($filter) && isset(self::FILTERS[$filter])
                ? self::FILTERS[$filter] : $filter
            );
        }

        return $this;
    }

    /**
     * 获取实际传递参数(magic)
     *
     * @param string $key 指定参数
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * 判断参数是否存在
     *
     * @param string $key 指定参数
     * @return boolean
     */
    public function __isset(string $key)
    {
        $this->get($key, null, $exists);
        return $exists;
    }

    /**
     * @param string $key
     * @param null $default
     * @param bool|null $exists detect exists
     * @return mixed
     */
    public function get(string $key, $default = null, ?bool &$exists = true)
    {
        return $this->applyFilter($this->request->proxy($this->params)->get($key, $default, $exists));
    }

    /**
     * @param $key
     * @return array
     */
    public function getArray($key): array
    {
        return $this->applyFilter($this->request->proxy($this->params)->getArray($key));
    }

    /**
     * @param ...$params
     * @return array
     */
    public function from(...$params): array
    {
        return $this->applyFilter(call_user_func_array([$this->request->proxy($this->params), 'from'], $params));
    }

    /**
     * @return string
     */
    public function getRequestRoot(): string
    {
        return $this->request->getRequestRoot();
    }

    /**
     * 获取当前完整的请求url
     *
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->request->getRequestUrl();
    }

    /**
     * 获取请求资源地址
     *
     * @return string|null
     */
    public function getRequestUri(): ?string
    {
        return $this->request->getRequestUri();
    }

    /**
     * 获取当前pathinfo
     *
     * @return string|null
     */
    public function getPathInfo(): ?string
    {
        return $this->request->getPathInfo();
    }

    /**
     * 获取url前缀
     *
     * @return string|null
     */
    public function getUrlPrefix(): ?string
    {
        return $this->request->getUrlPrefix();
    }

    /**
     * 根据当前uri构造指定参数的uri
     *
     * @param mixed $parameter 指定的参数
     * @return string
     */
    public function makeUriByRequest($parameter = null): string
    {
        return $this->request->makeUriByRequest($parameter);
    }

    /**
     * 获取环境变量
     *
     * @param string $name 获取环境变量名
     * @param string|null $default
     * @return string|null
     */
    public function getServer(string $name, string $default = null): ?string
    {
        return $this->request->getServer($name, $default);
    }

    /**
     * 获取ip地址
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->request->getIp();
    }

    /**
     * get header value
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $key, ?string $default = null): ?string
    {
        return $this->request->getHeader($key, $default);
    }

    /**
     * 获取客户端
     *
     * @return string
     */
    public function getAgent(): ?string
    {
        return $this->request->getAgent();
    }

    /**
     * 获取客户端
     *
     * @return string|null
     */
    public function getReferer(): ?string
    {
        return $this->request->getReferer();
    }

    /**
     * 判断是否为https
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->request->isSecure();
    }

    /**
     * 判断是否为get方法
     *
     * @return boolean
     */
    public function isGet(): bool
    {
        return $this->request->isGet();
    }

    /**
     * 判断是否为post方法
     *
     * @return boolean
     */
    public function isPost(): bool
    {
        return $this->request->isPost();
    }

    /**
     * 判断是否为put方法
     *
     * @return boolean
     */
    public function isPut(): bool
    {
        return $this->request->isPut();
    }

    /**
     * 判断是否为ajax
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return $this->request->isAjax();
    }

    /**
     * 判断输入是否满足要求
     *
     * @param mixed $query 条件
     * @return boolean
     */
    public function is($query): bool
    {
        return $this->request->is($query);
    }

    /**
     * 应用过滤器
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function applyFilter($value)
    {
        if ($this->filter) {
            foreach ($this->filter as $filter) {
                $value = is_array($value) ? array_map($filter, $value) :
                    call_user_func($filter, $value);
            }

            $this->filter = [];
        }

        return $value;
    }

    /**
     * Wrap a filter to make sure it always receives a string.
     *
     * @param callable $filter
     *
     * @return callable
     */
    private function wrapFilter(callable $filter): callable
    {
        return function ($value) use ($filter) {
            return call_user_func($filter, $value ?? '');
        };
    }
}
