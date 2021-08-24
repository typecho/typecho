<?php

namespace Typecho;

use Typecho\Widget\Exception as WidgetException;
use Typecho\Widget\Helper\EmptyClass;
use Typecho\Widget\Request as WidgetRequest;

/**
 * Typecho组件基类
 *
 * @package Widget
 */
abstract class Widget
{
    /**
     * widget对象池
     *
     * @var array
     */
    private static $widgetPool = [];

    /**
     * widget别名
     *
     * @var array
     */
    private static $widgetAlias = [];

    /**
     * request对象
     *
     * @var WidgetRequest
     */
    public $request;

    /**
     * response对象
     *
     * @var Response
     */
    public $response;

    /**
     * 数据堆栈
     *
     * @var array
     */
    protected $stack = [];

    /**
     * 当前队列指针顺序值,从1开始
     *
     * @var integer
     */
    protected $sequence = 0;

    /**
     * 队列长度
     *
     * @access public
     * @var integer
     */
    protected $length = 0;

    /**
     * config对象
     *
     * @var Config
     */
    protected $parameter;

    /**
     * 数据堆栈每一行
     *
     * @var array
     */
    protected $row = [];

    /**
     * 构造函数,初始化组件
     *
     * @access public
     *
     * @param WidgetRequest $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     */
    public function __construct(WidgetRequest $request, $response, $params = null)
    {
        //设置函数内部对象
        $this->request = $request;
        $this->response = $response;
        $this->parameter = Config::factory($params);
    }

    /**
     * widget别名
     *
     * @param string $widgetClass
     * @param string $aliasClass
     *
     * @static
     * @access public
     * @return void
     */
    public static function alias(string $widgetClass, string $aliasClass)
    {
        self::$widgetAlias[$widgetClass] = $aliasClass;
    }

    /**
     * 工厂方法,将类静态化放置到列表中
     *
     * @access public
     *
     * @param string $alias 组件别名
     * @param mixed $params 传递的参数
     * @param mixed $request 前端参数
     * @param boolean $enableResponse 是否允许http回执
     *
     * @return Widget
     * @throws WidgetException
     */
    public static function widget(
        string $alias,
        $params = null,
        $request = null,
        bool $enableResponse = true
    ): Widget {
        $parts = explode('@', $alias);
        $className = $parts[0];
        $alias = empty($parts[1]) ? $className : $parts[1];

        if (isset(self::$widgetAlias[$className])) {
            $className = self::$widgetAlias[$className];
        }

        if (!isset(self::$widgetPool[$alias])) {
            /** 如果类不存在 */
            if (!class_exists($className)) {
                throw new WidgetException($className);
            }

            /** 初始化request */
            $requestObject = new WidgetRequest(Request::getInstance(), Config::factory($request));

            /** 初始化response */
            $responseObject = $enableResponse ? Response::getInstance()
                : EmptyClass::getInstance();

            /** 初始化组件 */
            $widget = new $className($requestObject, $responseObject, $params);

            $widget->execute();
            self::$widgetPool[$alias] = $widget;
        }

        return self::$widgetPool[$alias];
    }

    /**
     * 释放组件
     *
     * @param string $alias 组件名称
     * @deprecated alias for destroy
     */
    public static function destory(string $alias)
    {
        self::destroy($alias);
    }

    /**
     * 释放组件
     *
     * @param string $alias 组件名称
     */
    public static function destroy(string $alias)
    {
        if (isset(self::$widgetPool[$alias])) {
            unset(self::$widgetPool[$alias]);
        }
    }

    /**
     * execute function.
     *
     * @access public
     * @return void
     */
    public function execute()
    {
    }

    /**
     * post事件触发
     *
     * @param boolean $condition 触发条件
     *
     * @return $this|EmptyClass
     */
    public function on(bool $condition)
    {
        if ($condition) {
            return $this;
        } else {
            return new EmptyClass();
        }
    }

    /**
     * 将类本身赋值
     *
     * @param mixed $variable 变量名
     *
     * @return Widget
     */
    public function to(&$variable): Widget
    {
        return $variable = $this;
    }

    /**
     * 格式化解析堆栈内的所有数据
     *
     * @param string $format 数据格式
     *
     * @return void
     */
    public function parse(string $format)
    {
        while ($this->next()) {
            echo preg_replace_callback(
                "/\{([_a-z0-9]+)\}/i",
                [$this, '__parseCallback'],
                $format
            );
        }
    }

    /**
     * 返回堆栈每一行的值
     *
     * @return mixed
     */
    public function next()
    {
        $key = key($this->stack);

        if ($key !== null && isset($this->stack[$key])) {
            $this->row = current($this->stack);
            next($this->stack);
            $this->sequence++;
        } else {
            reset($this->stack);
            $this->sequence = 0;
            return false;
        }

        return $this->row;
    }

    /**
     * 将每一行的值压入堆栈
     *
     * @param array $value 每一行的值
     *
     * @return mixed
     */
    public function push(array $value)
    {
        //将行数据按顺序置位
        $this->row = $value;
        $this->length++;

        $this->stack[] = $value;
        return $value;
    }

    /**
     * 根据余数输出
     *
     * @param mixed ...$args
     * @return void
     */
    public function alt(...$args)
    {
        $num = count($args);
        $split = $this->sequence % $num;
        echo $args[(0 == $split ? $num : $split) - 1];
    }

    /**
     * 返回堆栈是否为空
     *
     * @return boolean
     */
    public function have(): bool
    {
        return !empty($this->stack);
    }

    /**
     * 魔术函数,用于挂接其它函数
     *
     * @access public
     *
     * @param string $name 函数名
     * @param array $args 函数参数
     *
     * @return void
     */
    public function __call(string $name, array $args)
    {
        $method = 'call' . ucfirst($name);
        $this->pluginHandle()->trigger($plugged)->{$method}($this, $args);

        if (!$plugged) {
            echo $this->{$name};
        }
    }

    /**
     * 获取对象插件句柄
     *
     * @access public
     *
     * @param string|null $handle 句柄
     *
     * @return Plugin
     */
    public function pluginHandle(?string $handle = null): Plugin
    {
        return Plugin::factory(empty($handle) ? get_class($this) : $handle);
    }

    /**
     * 魔术函数,用于获取内部变量
     *
     * @access public
     *
     * @param string $name 变量名
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->row)) {
            return $this->row[$name];
        } else {
            $method = '___' . $name;

            if (method_exists($this, $method)) {
                return $this->$method();
            } else {
                $return = $this->pluginHandle()->trigger($plugged)->{$method}($this);
                if ($plugged) {
                    return $return;
                }
            }
        }

        return null;
    }

    /**
     * 设定堆栈每一行的值
     *
     * @param string $name 值对应的键值
     * @param mixed $value 相应的值
     *
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->row[$name] = $value;
    }

    /**
     * 验证堆栈值是否存在
     *
     * @access public
     *
     * @param string $name
     *
     * @return boolean
     */
    public function __isSet(string $name)
    {
        return isset($this->row[$name]);
    }

    /**
     * 输出顺序值
     *
     * @return int
     */
    public function ___sequence(): int
    {
        return $this->sequence;
    }

    /**
     * 输出数据长度
     *
     * @return int
     */
    public function ___length(): int
    {
        return $this->length;
    }

    /**
     * 解析回调
     *
     * @param array $matches
     *
     * @access protected
     * @return string
     */
    protected function __parseCallback(array $matches): string
    {
        return $this->{$matches[1]};
    }
}
