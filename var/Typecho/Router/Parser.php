<?php
/**
 * 路由器解析器
 *
 * @category typecho
 * @package Router
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 路由器解析器
 *
 * @category typecho
 * @package Router
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Router_Parser
{
    /**
     * 默认匹配表
     *
     * @access private
     * @var array
     */
    private $_defaultRegx;

    /**
     * 路由器映射表
     *
     * @access private
     * @var array
     */
    private $_routingTable;

    /**
     * 参数表
     *
     * @access private
     * @var array
     */
    private $_params;

    /**
     * 设置路由表
     *
     * @access public
     * @param array $routingTable 路由器映射表
     */
    public function __construct(array $routingTable)
    {
        $this->_routingTable = $routingTable;

        $this->_defaultRegx = array(
            'string' => '(.%s)',
            'char'   => '([^/]%s)',
            'digital'=> '([0-9]%s)',
            'alpha'  => '([_0-9a-zA-Z-]%s)',
            'alphaslash'  => '([_0-9a-zA-Z-/]%s)',
            'split'  => '((?:[^/]+/)%s[^/]+)',
        );
    }

    /**
     * 局部匹配并替换正则字符串
     *
     * @access public
     * @param array $matches 匹配部分
     * @return string
     */
    public function _match(array $matches)
    {
        $params = explode(' ', $matches[1]);
        $paramsNum = count($params);
        $this->_params[] = $params[0];

        if (1 == $paramsNum) {
            return sprintf($this->_defaultRegx['char'], '+');
        } else if (2 == $paramsNum) {
            return sprintf($this->_defaultRegx[$params[1]], '+');
        } else if (3 == $paramsNum) {
            return sprintf($this->_defaultRegx[$params[1]], $params[2] > 0 ? '{' . $params[2] . '}' : '*');
        } else if (4 == $paramsNum) {
            return sprintf($this->_defaultRegx[$params[1]], '{' . $params[2] . ',' . $params[3] . '}');
        }
    }

    /**
     * 解析路由表
     *
     * @access public
     * @return array
     */
    public function parse()
    {
        $result = array();

        foreach ($this->_routingTable as $key => $route) {
            $this->_params = array();
            $route['regx'] = preg_replace_callback("/%([^%]+)%/", array($this, '_match'),
            preg_quote(str_replace(array('[', ']', ':'), array('%', '%', ' '), $route['url'])));

            /** 处理斜线 */
            $route['regx'] = rtrim($route['regx'], '/');
            $route['regx'] = '|^' . $route['regx'] . '[/]?$|';

            $route['format'] = preg_replace("/\[([^\]]+)\]/", "%s", $route['url']);
            $route['params'] = $this->_params;

            $result[$key] = $route;
        }

        return $result;
    }
}
