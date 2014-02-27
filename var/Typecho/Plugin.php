<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 插件处理类
 *
 * @category typecho
 * @package Plugin
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Plugin
{
    /**
     * 所有启用的插件
     *
     * @access private
     * @var array
     */
    private static $_plugins = array();

    /**
     * 已经加载的文件
     *
     * @access private
     * @var array
     */
    private static $_required = array();

    /**
     * 实例化的插件对象
     *
     * @access private
     * @var array
     */
    private static $_instances;

    /**
     * 临时存储变量
     *
     * @access private
     * @var array
     */
    private static $_tmp = array();

    /**
     * 唯一句柄
     *
     * @access private
     * @var string
     */
    private $_handle;

    /**
     * 组件
     *
     * @access private
     * @var string
     */
    private $_component;

    /**
     * 是否触发插件的信号
     *
     * @access private
     * @var boolean
     */
    private $_signal;

    /**
     * 插件初始化
     *
     * @access public
     * @param string $handle 插件
     * @return void
     */
    public function __construct($handle)
    {
        /** 初始化变量 */
        $this->_handle = $handle;
    }

    /**
     * 插件handle比对
     *
     * @access private
     * @param array $pluginHandles
     * @param array $otherPluginHandles
     * @return void
     */
    private static function pluginHandlesDiff(array $pluginHandles, array $otherPluginHandles)
    {
        foreach ($otherPluginHandles as $handle) {
            while (false !== ($index = array_search($handle, $pluginHandles))) {
                unset($pluginHandles[$index]);
            }
        }

        return $pluginHandles;
    }

    /**
     * 插件初始化
     *
     * @access public
     * @param array $plugins 插件列表
     * @param mixed $callback 获取插件系统变量的代理函数
     * @return void
     */
    public static function init(array $plugins)
    {
        $plugins['activated'] = array_key_exists('activated', $plugins) ? $plugins['activated'] : array();
        $plugins['handles'] = array_key_exists('handles', $plugins) ? $plugins['handles'] : array();

        /** 初始化变量 */
        self::$_plugins = $plugins;
    }

    /**
     * 获取实例化插件对象
     *
     * @access public
     * @return Typecho_Plugin
     */
    public static function factory($handle)
    {
        return isset(self::$_instances[$handle]) ? self::$_instances[$handle] :
        (self::$_instances[$handle] = new Typecho_Plugin($handle));
    }

    /**
     * 启用插件
     *
     * @access public
     * @param string $pluginName 插件名称
     * @return void
     */
    public static function activate($pluginName)
    {
        self::$_plugins['activated'][$pluginName] = self::$_tmp;
        self::$_tmp = array();
    }

    /**
     * 禁用插件
     *
     * @access public
     * @param string $pluginName 插件名称
     * @return void
     */
    public static function deactivate($pluginName)
    {
        /** 去掉所有相关回调函数 */
        if (isset(self::$_plugins['activated'][$pluginName]['handles']) && is_array(self::$_plugins['activated'][$pluginName]['handles'])) {
            foreach (self::$_plugins['activated'][$pluginName]['handles'] as $handle => $handles) {
                self::$_plugins['handles'][$handle] = self::pluginHandlesDiff(
                empty(self::$_plugins['handles'][$handle]) ? array() : self::$_plugins['handles'][$handle],
                empty($handles) ? array() : $handles);
                if (empty(self::$_plugins['handles'][$handle])) {
                    unset(self::$_plugins['handles'][$handle]);
                }
            }
        }

        /** 禁用当前插件 */
        unset(self::$_plugins['activated'][$pluginName]);
    }

    /**
     * 导出当前插件设置
     *
     * @access public
     * @return array
     */
    public static function export()
    {
        return self::$_plugins;
    }

    /**
     * 获取插件文件的头信息
     *
     * @access public
     * @param string $pluginFile 插件文件路径
     * @return array
     */
    public static function parseInfo($pluginFile)
    {
        $tokens = token_get_all(file_get_contents($pluginFile));
        $isDoc = false;
        $isFunction = false;
        $isClass = false;
        $isInClass = false;
        $isInFunction = false;
        $isDefined = false;
        $current = NULL;

        /** 初始信息 */
        $info = array(
            'description'       => '',
            'title'             => '',
            'author'            => '',
            'homepage'          => '',
            'version'           => '',
            'dependence'        => '',
            'activate'          => false,
            'deactivate'        => false,
            'config'            => false,
            'personalConfig'    => false
        );

        $map = array(
            'package'   =>  'title',
            'author'    =>  'author',
            'link'      =>  'homepage',
            'dependence'=>  'dependence',
            'version'   =>  'version'
        );

        foreach ($tokens as $token) {
            /** 获取doc comment */
            if (!$isDoc && is_array($token) && T_DOC_COMMENT == $token[0]) {

                /** 分行读取 */
                $described = false;
                $lines = preg_split("(\r|\n)", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));
                        if (!$described && !empty($line) && '@' == $line[0]) {
                            $described = true;
                        }

                        if (!$described && !empty($line)) {
                            $info['description'] .= $line . "\n";
                        } else if ($described && !empty($line) && '@' == $line[0]) {
                            $info['description'] = trim($info['description']);
                            $line = trim(substr($line, 1));
                            $args = explode(' ', $line);
                            $key = array_shift($args);

                            if (isset($map[$key])) {
                                $info[$map[$key]] = trim(implode(' ', $args));
                            }
                        }
                    }
                }

                $isDoc = true;
            }

            if (is_array($token)) {
                switch ($token[0]) {
                    case T_FUNCTION:
                        $isFunction = true;
                        break;
                    case T_IMPLEMENTS:
                        $isClass = true;
                        break;
                    case T_WHITESPACE:
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    case T_STRING:
                        $string = strtolower($token[1]);
                        switch ($string) {
                            case 'typecho_plugin_interface':
                                $isInClass = $isClass;
                                break;
                            case 'activate':
                            case 'deactivate':
                            case 'config':
                            case 'personalconfig':
                                if ($isFunction) {
                                    $current = ('personalconfig' == $string ? 'personalConfig' : $string);
                                }
                                break;
                            default:
                                if (!empty($current) && $isInFunction && $isInClass) {
                                    $info[$current] = true;
                                }
                                break;
                        }
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            } else {
                $token = strtolower($token);
                switch ($token) {
                    case '{':
                        if ($isDefined) {
                            $isInFunction = true;
                        }
                        break;
                    case '(':
                        if ($isFunction && !$isDefined) {
                            $isDefined = true;
                        }
                        break;
                    case '}':
                    case ';':
                        $isDefined = false;
                        $isFunction = false;
                        $isInFunction = false;
                        $current = NULL;
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            }
        }

        return $info;
    }

    /**
     * 获取插件路径和类名
     * 返回值为一个数组
     * 第一项为插件路径,第二项为类名
     *
     * @access public
     * @param string $pluginName 插件名
     * @param string $path 插件目录
     * @return array
     */
    public static function portal($pluginName, $path)
    {
        switch (true) {
            case file_exists($pluginFileName = $path . '/' . $pluginName . '/Plugin.php'):
                $className = $pluginName . '_Plugin';
                break;
            case file_exists($pluginFileName = $path . '/' . $pluginName . '.php'):
                $className = $pluginName;
                break;
            default:
                throw new Typecho_Plugin_Exception('Missing Plugin ' . $pluginName, 404);
        }

        return array($pluginFileName, $className);
    }

    /**
     * 版本依赖性检测
     *
     * @access public
     * @param string $version 程序版本
     * @param string $versionRange 依赖的版本规则
     * @return boolean
     */
    public static function checkDependence($version, $versionRange)
    {
        //如果没有检测规则,直接掠过
        if (empty($versionRange)) {
            return true;
        }

        $items = array_map('trim', explode('-', $versionRange));
        if (count($items) < 2) {
            $items[1] = $items[0];
        }

        list ($minVersion, $maxVersion) = $items;

        //对*和?的支持,4个9是最大版本
        $minVersion = str_replace(array('*', '?'), array('9999', '9'), $minVersion);
        $maxVersion = str_replace(array('*', '?'), array('9999', '9'), $maxVersion);

        if (version_compare($version, $minVersion, '>=') && version_compare($version, $maxVersion, '<=')) {
            return true;
        }

        return false;
    }

    /**
     * 插件调用后的触发器
     *
     * @access public
     * @param boolean $signal 触发器
     * @return Typecho_Plugin
     */
    public function trigger(&$signal)
    {
        $signal = false;
        $this->_signal = &$signal;
        return $this;
    }

    /**
     * 判断插件是否存在
     *
     * @access public
     * @param string $pluginName 插件名称
     * @return void
     */
    public function exists($pluginName) {
        return array_search($pluginName, self::$_plugins['activated']);
    }

    /**
     * 设置回调函数
     *
     * @access public
     * @param string $component 当前组件
     * @param mixed $value 回调函数
     * @return void
     */
    public function __set($component, $value)
    {
        $weight = 0;

        if (strpos($component, '_') > 0) {
            $parts = explode('_', $component, 2);
            list($component, $weight) = $parts;
            $weight = intval($weight) - 10;
        }
        
        $component = $this->_handle . ':' . $component;

        if (!isset(self::$_plugins['handles'][$component])) {
            self::$_plugins['handles'][$component] = array();
        }

        if (!isset(self::$_tmp['handles'][$component])) {
            self::$_tmp['handles'][$component] = array();
        }

        foreach (self::$_plugins['handles'][$component] as $key => $val) {
            $key = floatval($key);

            if ($weight > $key) {
                break;
            } else if ($weight == $key) {
                $weight += 0.001;
            }
        }

        self::$_plugins['handles'][$component][strval($weight)] = $value;
        self::$_tmp['handles'][$component][] = $value;

        ksort(self::$_plugins['handles'][$component], SORT_NUMERIC);
    }

    /**
     * 通过魔术函数设置当前组件位置
     *
     * @access public
     * @param string $component 当前组件
     * @return Typecho_Plugin
     */
    public function __get($component)
    {
        $this->_component = $component;
        return $this;
    }

    /**
     * 回调处理函数
     *
     * @access public
     * @param string $component 当前组件
     * @param string $args 参数
     * @return mixed
     */
    public function __call($component, $args)
    {
        $component = $this->_handle . ':' . $component;
        $last = count($args);
        $args[$last] = $last > 0 ? $args[0] : false;

        if (isset(self::$_plugins['handles'][$component])) {
            $args[$last] = NULL;
            $this->_signal = true;
            foreach (self::$_plugins['handles'][$component] as $callback) {
                $args[$last] = call_user_func_array($callback, $args);
            }
        }

        return $args[$last];
    }
}
