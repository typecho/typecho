<?php

namespace Typecho;

use Typecho\Router\Parser;
use Typecho\Router\Exception as RouterException;

/**
 * Typecho组件基类
 *
 * @package Router
 */
class Router
{
    /**
     * 当前路由名称
     *
     * @access public
     * @var string
     */
    public static $current;

    /**
     * 已经解析完毕的路由表配置
     *
     * @access private
     * @var mixed
     */
    private static $routingTable = [];

    /**
     * 解析路径
     *
     * @access public
     *
     * @param string|null $pathInfo 全路径
     * @param mixed $parameter 输入参数
     *
     * @return false|Widget
     * @throws \Exception
     */
    public static function match(?string $pathInfo, $parameter = null)
    {
        foreach (self::$routingTable as $key => $route) {
            if (preg_match($route['regx'], $pathInfo, $matches)) {
                self::$current = $key;

                try {
                    /** 载入参数 */
                    $params = null;

                    if (!empty($route['params'])) {
                        unset($matches[0]);
                        $params = array_combine($route['params'], $matches);
                    }

                    return Widget::widget($route['widget'], $parameter, $params);

                } catch (\Exception $e) {
                    if (404 == $e->getCode()) {
                        Widget::destroy($route['widget']);
                        continue;
                    }

                    throw $e;
                }
            }
        }

        return false;
    }

    /**
     * 路由分发函数
     *
     * @throws RouterException|\Exception
     */
    public static function dispatch()
    {
        /** 获取PATHINFO */
        $pathInfo = Request::getInstance()->getPathInfo();

        foreach (self::$routingTable as $key => $route) {
            if (preg_match($route['regx'], $pathInfo, $matches)) {
                self::$current = $key;

                try {
                    /** 载入参数 */
                    $params = null;

                    if (!empty($route['params'])) {
                        unset($matches[0]);
                        $params = array_combine($route['params'], $matches);
                    }

                    $widget = Widget::widget($route['widget'], null, $params);

                    if (isset($route['action'])) {
                        $widget->{$route['action']}();
                    }

                    return;

                } catch (\Exception $e) {
                    if (404 == $e->getCode()) {
                        Widget::destroy($route['widget']);
                        continue;
                    }

                    throw $e;
                }
            }
        }

        /** 载入路由异常支持 */
        throw new RouterException("Path '{$pathInfo}' not found", 404);
    }

    /**
     * 路由反解析函数
     *
     * @param string $name 路由配置表名称
     * @param array|null $value 路由填充值
     * @param string|null $prefix 最终合成路径的前缀
     *
     * @return string
     */
    public static function url(string $name, ?array $value = null, ?string $prefix = null): string
    {
        $route = self::$routingTable[$name];

        //交换数组键值
        $pattern = [];
        foreach ($route['params'] as $row) {
            $pattern[$row] = $value[$row] ?? '{' . $row . '}';
        }

        return Common::url(vsprintf($route['format'], $pattern), $prefix);
    }

    /**
     * 设置路由器默认配置
     *
     * @access public
     *
     * @param mixed $routes 配置信息
     *
     * @return void
     */
    public static function setRoutes($routes)
    {
        if (isset($routes[0])) {
            self::$routingTable = $routes[0];
        } else {
            /** 解析路由配置 */
            $parser = new Parser($routes);
            self::$routingTable = $parser->parse();
        }
    }

    /**
     * 获取路由信息
     *
     * @param string $routeName 路由名称
     *
     * @static
     * @access public
     * @return mixed
     */
    public static function get(string $routeName)
    {
        return self::$routingTable[$routeName] ?? null;
    }
}
