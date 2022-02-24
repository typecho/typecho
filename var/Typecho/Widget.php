<?php

namespace Typecho;

use Typecho\Widget\Helper\EmptyClass;
use Typecho\Widget\Request as WidgetRequest;
use Typecho\Widget\Response as WidgetResponse;
use Typecho\Widget\Terminal;

/**
 * Typecho组件基类
 *
 * @property $sequence
 * @property $length
 * @property-read $request
 * @property-read $response
 * @property-read $parameter
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
    protected $request;

    /**
     * response对象
     *
     * @var WidgetResponse
     */
    protected $response;

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
     * @param WidgetRequest $request request对象
     * @param WidgetResponse $response response对象
     * @param mixed $params 参数列表
     */
    public function __construct(WidgetRequest $request, WidgetResponse $response, $params = null)
    {
        //设置函数内部对象
        $this->request = $request;
        $this->response = $response;
        $this->parameter = Config::factory($params);

        $this->init();
    }

    /**
     * init method
     */
    protected function init()
    {
    }

    /**
     * widget别名
     *
     * @param string $widgetClass
     * @param string $aliasClass
     */
    public static function alias(string $widgetClass, string $aliasClass)
    {
        self::$widgetAlias[$widgetClass] = $aliasClass;
    }

    /**
     * 工厂方法,将类静态化放置到列表中
     *
     * @param class-string $alias 组件别名
     * @param mixed $params 传递的参数
     * @param mixed $request 前端参数
     * @param bool|callable $disableSandboxOrCallback 回调
     * @return Widget
     */
    public static function widget(
        string $alias,
        $params = null,
        $request = null,
        $disableSandboxOrCallback = true
    ): Widget {
        [$className] = explode('@', $alias);
        $key = Common::nativeClassName($alias);

        if (isset(self::$widgetAlias[$className])) {
            $className = self::$widgetAlias[$className];
        }

        $sandbox = false;

        if ($disableSandboxOrCallback === false || is_callable($disableSandboxOrCallback)) {
            $sandbox = true;
            Request::getInstance()->beginSandbox(new Config($request));
            Response::getInstance()->beginSandbox();
        }

        if ($sandbox || !isset(self::$widgetPool[$key])) {
            $requestObject = new WidgetRequest(Request::getInstance(), isset($request) ? new Config($request) : null);
            $responseObject = new WidgetResponse(Request::getInstance(), Response::getInstance());

            try {
                $widget = new $className($requestObject, $responseObject, $params);
                $widget->execute();

                if ($sandbox && is_callable($disableSandboxOrCallback)) {
                    call_user_func($disableSandboxOrCallback, $widget);
                }
            } catch (Terminal $e) {
                $widget = $widget ?? null;
            } finally {
                if ($sandbox) {
                    Response::getInstance()->endSandbox();
                    Request::getInstance()->endSandbox();

                    return $widget;
                }
            }

            self::$widgetPool[$key] = $widget;
        }

        return self::$widgetPool[$key];
    }

    /**
     * alloc widget instance
     *
     * @param mixed $params
     * @param mixed $request
     * @param bool|callable $disableSandboxOrCallback
     * @return $this
     */
    public static function alloc($params = null, $request = null, $disableSandboxOrCallback = true): Widget
    {
        return self::widget(static::class, $params, $request, $disableSandboxOrCallback);
    }

    /**
     * alloc widget instance with alias
     *
     * @param string|null $alias
     * @param mixed $params
     * @param mixed $request
     * @param bool|callable $disableSandboxOrCallback
     * @return $this
     */
    public static function allocWithAlias(
        ?string $alias,
        $params = null,
        $request = null,
        $disableSandboxOrCallback = true
    ): Widget {
        return self::widget(
            static::class . (isset($alias) ? '@' . $alias : ''),
            $params,
            $request,
            $disableSandboxOrCallback
        );
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
     * @param string|null $alias 组件名称
     */
    public static function destroy(?string $alias = null)
    {
        if (Common::nativeClassName(static::class) == 'Typecho_Widget') {
            if (isset($alias)) {
                unset(self::$widgetPool[$alias]);
            } else {
                self::$widgetPool = [];
            }
        } else {
            $alias = static::class . (isset($alias) ? '@' . $alias : '');
            unset(self::$widgetPool[$alias]);
        }
    }

    /**
     * execute function.
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
     * @return $this
     */
    public function to(&$variable): Widget
    {
        return $variable = $this;
    }

    /**
     * 格式化解析堆栈内的所有数据
     *
     * @param string $format 数据格式
     */
    public function parse(string $format)
    {
        while ($this->next()) {
            echo preg_replace_callback(
                "/\{([_a-z0-9]+)\}/i",
                function (array $matches) {
                    return $this->{$matches[1]};
                },
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
     * @param string $name 函数名
     * @param array $args 函数参数
     */
    public function __call(string $name, array $args)
    {
        $method = 'call' . ucfirst($name);
        self::pluginHandle()->trigger($plugged)->{$method}($this, $args);

        if (!$plugged) {
            echo $this->{$name};
        }
    }

    /**
     * 获取对象插件句柄
     *
     * @return Plugin
     */
    public static function pluginHandle(): Plugin
    {
        return Plugin::factory(static::class);
    }

    /**
     * 魔术函数,用于获取内部变量
     *
     * @param string $name 变量名
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
                $return = self::pluginHandle()->trigger($plugged)->{$method}($this);
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
     */
    public function __set(string $name, $value)
    {
        $this->row[$name] = $value;
    }

    /**
     * 验证堆栈值是否存在
     *
     * @param string $name
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
     * @return WidgetRequest
     */
    public function ___request(): WidgetRequest
    {
        return $this->request;
    }

    /**
     * @return WidgetResponse
     */
    public function ___response(): WidgetResponse
    {
        return $this->response;
    }

    /**
     * @return Config
     */
    public function ___parameter(): Config
    {
        return $this->parameter;
    }
}
