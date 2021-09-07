<?php

namespace Typecho\Router;

/**
 * 路由器解析器
 *
 * @category typecho
 * @package Router
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Parser
{
    /**
     * 默认匹配表
     *
     * @access private
     * @var array
     */
    private $defaultRegex;

    /**
     * 路由器映射表
     *
     * @access private
     * @var array
     */
    private $routingTable;

    /**
     * 参数表
     *
     * @access private
     * @var array
     */
    private $params;

    /**
     * 设置路由表
     *
     * @access public
     * @param array $routingTable 路由器映射表
     */
    public function __construct(array $routingTable)
    {
        $this->routingTable = $routingTable;

        $this->defaultRegex = [
            'string' => '(.%s)',
            'char' => '([^/]%s)',
            'digital' => '([0-9]%s)',
            'alpha' => '([_0-9a-zA-Z-]%s)',
            'alphaslash' => '([_0-9a-zA-Z-/]%s)',
            'split' => '((?:[^/]+/)%s[^/]+)',
        ];
    }

    /**
     * 局部匹配并替换正则字符串
     *
     * @access public
     * @param array $matches 匹配部分
     * @return string
     */
    public function match(array $matches): string
    {
        $params = explode(' ', $matches[1]);
        $paramsNum = count($params);
        $this->params[] = $params[0];

        if (1 == $paramsNum) {
            return sprintf($this->defaultRegex['char'], '+');
        } elseif (2 == $paramsNum) {
            return sprintf($this->defaultRegex[$params[1]], '+');
        } elseif (3 == $paramsNum) {
            return sprintf($this->defaultRegex[$params[1]], $params[2] > 0 ? '{' . $params[2] . '}' : '*');
        } elseif (4 == $paramsNum) {
            return sprintf($this->defaultRegex[$params[1]], '{' . $params[2] . ',' . $params[3] . '}');
        }

        return $matches[0];
    }

    /**
     * 解析路由表
     *
     * @access public
     * @return array
     */
    public function parse(): array
    {
        $result = [];

        foreach ($this->routingTable as $key => $route) {
            $this->params = [];
            $route['regx'] = preg_replace_callback(
                "/%([^%]+)%/",
                [$this, 'match'],
                preg_quote(str_replace(['[', ']', ':'], ['%', '%', ' '], $route['url']))
            );

            /** 处理斜线 */
            $route['regx'] = rtrim($route['regx'], '/');
            $route['regx'] = '|^' . $route['regx'] . '[/]?$|';

            $route['format'] = preg_replace("/\[([^\]]+)\]/", "%s", $route['url']);
            $route['params'] = $this->params;

            $result[$key] = $route;
        }

        return $result;
    }
}
