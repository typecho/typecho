<?php

namespace Typecho;

use Typecho\Plugin\Exception as PluginException;

/**
 * 插件处理类
 *
 * @category typecho
 * @package Plugin
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Plugin
{
    /**
     * 所有启用的插件
     *
     * @var array
     */
    private static array $plugin = [];

    /**
     * 实例化的插件对象
     *
     * @var array
     */
    private static array $instances;

    /**
     * 临时存储变量
     *
     * @var array
     */
    private static array $tmp = [];

    /**
     * 唯一句柄
     *
     * @var string
     */
    private string $handle;

    /**
     * 组件
     *
     * @var string
     */
    private $component;

    /**
     * 是否触发插件的信号
     *
     * @var boolean
     */
    private bool $signal = false;

    /**
     * 插件初始化
     *
     * @param string $handle 插件
     */
    public function __construct(string $handle)
    {
        if (defined('__TYPECHO_CLASS_ALIASES__')) {
            $alias = array_search('\\' . ltrim($handle, '\\'), __TYPECHO_CLASS_ALIASES__);
            $handle = $alias ?: $handle;
        }

        $this->handle = Common::nativeClassName($handle);
    }

    /**
     * 插件初始化
     *
     * @param array $plugins 插件列表
     */
    public static function init(array $plugins)
    {
        $plugins['activated'] = array_key_exists('activated', $plugins) ? $plugins['activated'] : [];
        $plugins['handles'] = array_key_exists('handles', $plugins) ? $plugins['handles'] : [];

        /** 初始化变量 */
        self::$plugin = $plugins;
    }

    /**
     * 获取实例化插件对象
     *
     * @param string $handle 插件
     * @return Plugin
     */
    public static function factory(string $handle): Plugin
    {
        return self::$instances[$handle] ?? (self::$instances[$handle] = new self($handle));
    }

    /**
     * 启用插件
     *
     * @param string $pluginName 插件名称
     */
    public static function activate(string $pluginName)
    {
        self::$plugin['activated'][$pluginName] = self::$tmp;
        self::$tmp = [];
    }

    /**
     * 禁用插件
     *
     * @param string $pluginName 插件名称
     */
    public static function deactivate(string $pluginName)
    {
        /** 去掉所有相关回调函数 */
        if (
            isset(self::$plugin['activated'][$pluginName]['handles'])
            && is_array(self::$plugin['activated'][$pluginName]['handles'])
        ) {
            foreach (self::$plugin['activated'][$pluginName]['handles'] as $handle => $handles) {
                self::$plugin['handles'][$handle] = self::pluginHandlesDiff(
                    empty(self::$plugin['handles'][$handle]) ? [] : self::$plugin['handles'][$handle],
                    empty($handles) ? [] : $handles
                );
                if (empty(self::$plugin['handles'][$handle])) {
                    unset(self::$plugin['handles'][$handle]);
                }
            }
        }

        /** 禁用当前插件 */
        unset(self::$plugin['activated'][$pluginName]);
    }

    /**
     * 插件handle比对
     *
     * @param array $pluginHandles
     * @param array $otherPluginHandles
     * @return array
     */
    private static function pluginHandlesDiff(array $pluginHandles, array $otherPluginHandles): array
    {
        foreach ($otherPluginHandles as $handle) {
            while (false !== ($index = array_search($handle, $pluginHandles))) {
                unset($pluginHandles[$index]);
            }
        }

        return $pluginHandles;
    }

    /**
     * 导出当前插件设置
     *
     * @return array
     */
    public static function export(): array
    {
        return self::$plugin;
    }

    /**
     * 获取插件文件的头信息
     *
     * @param string $pluginFile 插件文件路径
     * @return array
     */
    public static function parseInfo(string $pluginFile): array
    {
        $tokens = token_get_all(file_get_contents($pluginFile));
        $isDoc = false;
        $isFunction = false;
        $isClass = false;
        $isInClass = false;
        $isInFunction = false;
        $isDefined = false;
        $current = null;

        /** 初始信息 */
        $info = [
            'description' => '',
            'title' => '',
            'author' => '',
            'homepage' => '',
            'version' => '',
            'since' => '',
            'activate' => false,
            'deactivate' => false,
            'config' => false,
            'personalConfig' => false
        ];

        $map = [
            'package' => 'title',
            'author' => 'author',
            'link' => 'homepage',
            'since' => 'since',
            'version' => 'version'
        ];

        foreach ($tokens as $token) {
            /** 获取doc comment */
            if (!$isDoc && is_array($token) && T_DOC_COMMENT == $token[0]) {

                /** 分行读取 */
                $described = false;
                $lines = preg_split("([\r\n])", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));
                        if (!$described && !empty($line) && '@' == $line[0]) {
                            $described = true;
                        }

                        if (!$described && !empty($line)) {
                            $info['description'] .= $line . "\n";
                        } elseif ($described && !empty($line) && '@' == $line[0]) {
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
                            case 'plugininterface':
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
                        $current = null;
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
     * @param string $pluginName 插件名
     * @param string $path 插件目录
     * @return array
     * @throws PluginException
     */
    public static function portal(string $pluginName, string $path): array
    {
        switch (true) {
            case file_exists($pluginFileName = $path . '/' . $pluginName . '/Plugin.php'):
                $className = "\\" . PLUGIN_NAMESPACE . "\\{$pluginName}\\Plugin";
                break;
            case file_exists($pluginFileName = $path . '/' . $pluginName . '.php'):
                $className = "\\" . PLUGIN_NAMESPACE . "\\" . $pluginName;
                break;
            default:
                throw new PluginException('Missing Plugin ' . $pluginName, 404);
        }

        return [$pluginFileName, $className];
    }

    /**
     * 版本依赖性检测
     *
     * @param string|null $version 插件版本
     * @return boolean
     */
    public static function checkDependence(?string $version): bool
    {
        //如果没有检测规则,直接掠过
        if (empty($version)) {
            return true;
        }

        return version_compare(Common::VERSION, $version, '>=');
    }

    /**
     * 判断插件是否存在
     *
     * @param string $pluginName 插件名称
     * @return bool
     */
    public static function exists(string $pluginName): bool
    {
        return array_key_exists($pluginName, self::$plugin['activated']);
    }

    /**
     * 插件调用后的触发器
     *
     * @param boolean|null $signal 触发器
     * @return Plugin
     */
    public function trigger(?bool &$signal): Plugin
    {
        $signal = false;
        $this->signal = &$signal;
        return $this;
    }

    /**
     * 通过魔术函数设置当前组件位置
     *
     * @param string $component 当前组件
     * @return Plugin
     */
    public function __get(string $component)
    {
        $this->component = $component;
        return $this;
    }

    /**
     * 设置回调函数
     *
     * @param string $component 当前组件
     * @param callable $value 回调函数
     */
    public function __set(string $component, callable $value)
    {
        $weight = 0;

        if (strpos($component, '_') > 0) {
            $parts = explode('_', $component, 2);
            [$component, $weight] = $parts;
            $weight = intval($weight) - 10;
        }

        $component = $this->handle . ':' . $component;

        if (!isset(self::$plugin['handles'][$component])) {
            self::$plugin['handles'][$component] = [];
        }

        if (!isset(self::$tmp['handles'][$component])) {
            self::$tmp['handles'][$component] = [];
        }

        foreach (self::$plugin['handles'][$component] as $key => $val) {
            $key = floatval($key);

            if ($weight > $key) {
                break;
            } elseif ($weight == $key) {
                $weight += 0.001;
            }
        }

        self::$plugin['handles'][$component][strval($weight)] = $value;
        self::$tmp['handles'][$component][] = $value;

        ksort(self::$plugin['handles'][$component], SORT_NUMERIC);
    }

    /**
     * 回调处理函数
     *
     * @param string $component 当前组件
     * @param array $args 参数
     * @return mixed
     */
    public function call(string $component, ...$args)
    {
        $componentKey = $this->handle . ':' . $component;

        if (!isset(self::$plugin['handles'][$componentKey])) {
            return null;
        }

        $return = null;
        $this->signal = true;

        foreach (self::$plugin['handles'][$componentKey] as $callbackKey => $callback) {
            try {
                $return = call_user_func_array($callback, $args);
            } catch (\Error|\Exception $e) {
                $errorMessage = $e->getMessage();
                $pluginName = '';
                
                // 检查错误类型并提取插件名称
                if (strpos($errorMessage, 'must be a valid callback') !== false) {
                    // 处理类不存在的错误
                    if (strpos($errorMessage, 'class "TypechoPlugin') !== false && 
                        strpos($errorMessage, 'not found') !== false) {
                        // 类不存在的情况
                        if (preg_match('/class "TypechoPlugin\\\\([^\\\\]+)\\\\/', $errorMessage, $matches)) {
                            $pluginName = $matches[1];
                        }
                    } 
                    // 处理方法不存在的错误
                    elseif (strpos($errorMessage, 'does not have a method') !== false) {
                        if (preg_match('/class "TypechoPlugin\\\\([^\\\\]+)\\\\/', $errorMessage, $matches)) {
                            $pluginName = $matches[1];
                        }
                    }
                    // 处理无效回调的错误
                    elseif (strpos($errorMessage, 'not a valid class name or object') !== false) {
                        // 通过回调分析插件名称
                        if (is_array($callback) && isset($callback[0])) {
                            $cbClass = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
                            if (preg_match('/TypechoPlugin\\\\([^\\\\]+)/', $cbClass, $matches)) {
                                $pluginName = $matches[1];
                            }
                        }
                    }
                }
                // 处理参数不足的错误
                elseif ($e instanceof \ArgumentCountError || strpos($errorMessage, 'Too few arguments') !== false) {
                    if (is_array($callback) && isset($callback[0])) {
                        $cbClass = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
                        if (preg_match('/TypechoPlugin\\\\([^\\\\]+)/', $cbClass, $matches)) {
                            $pluginName = $matches[1];
                        }
                    }
                }
                // 处理类型错误
                elseif ($e instanceof \TypeError) {
                    if (is_array($callback) && isset($callback[0])) {
                        $cbClass = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
                        if (preg_match('/TypechoPlugin\\\\([^\\\\]+)/', $cbClass, $matches)) {
                            $pluginName = $matches[1];
                        }
                    }
                }
                
                // 如果确定了插件名称，则禁用该插件
                if (!empty($pluginName)) {
                    try {
                        // 从激活列表中移除插件
                        if (isset(self::$plugin['activated'][$pluginName])) {
                            // 禁用出错的插件
                            unset(self::$plugin['activated'][$pluginName]);
                            
                            // 从当前组件的回调列表中移除该回调
                            unset(self::$plugin['handles'][$componentKey][$callbackKey]);
                            
                            // 清理其他相关钩子
                            foreach (self::$plugin['handles'] as $hook => $callbacks) {
                                foreach ($callbacks as $idx => $cb) {
                                    if (is_array($cb) && isset($cb[0])) {
                                        $cbClass = is_object($cb[0]) ? get_class($cb[0]) : $cb[0];
                                        if (strpos($cbClass, $pluginName) !== false) {
                                            unset(self::$plugin['handles'][$hook][$idx]);
                                        }
                                    }
                                }
                                
                                // 如果钩子没有回调函数了，移除整个钩子
                                if (empty(self::$plugin['handles'][$hook])) {
                                    unset(self::$plugin['handles'][$hook]);
                                }
                            }
                            
                            // 更新数据库
                            $db = \Typecho\Db::get();
                            $db->query($db->update('table.options')
                                ->rows(['value' => serialize(self::$plugin)])
                                ->where('name = ?', 'plugins'));
                        }
                    } catch (\Exception $e) {
                        // 简化错误处理，不记录日志
                    }
                }
                
                // 继续处理其他回调，不中断执行
                continue;
            }
        }

        return $return;
    }

    /**
     * 过滤处理函数
     *
     * @param string $component 当前组件
     * @param mixed $value 值
     * @param array $args 参数
     * @return mixed
     */
    public function filter(string $component, $value, ...$args)
    {
        $componentKey = $this->handle . ':' . $component;

        if (!isset(self::$plugin['handles'][$componentKey])) {
            return $value;
        }

        $result = $value;
        $this->signal = true;

        foreach (self::$plugin['handles'][$componentKey] as $callback) {
            $currentArgs = array_merge([$result], $args, [$result]);
            $result = call_user_func_array($callback, $currentArgs);
        }

        return $result;
    }

    /**
     * @deprecated ^1.3.0
     * @param string $component
     * @param array $args
     * @return false|mixed|null
     */
    public function __call(string $component, array $args)
    {
        return $this->call($component, ... $args);
    }
}
