<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: Widget.php 107 2008-04-11 07:14:43Z magike.net $
 */

/**
 * Typecho组件基类
 *
 * @package Widget
 */
abstract class Typecho_Widget
{
    /**
     * widget对象池
     *
     * @access private
     * @var array
     */
    private static $_widgetPool = array();


    /**
     * widget别名
     *
     * @access private
     * @var array
     */
    private static $_widgetAlias = array();

    /**
     * 帮手列表
     *
     * @access private
     * @var array
     */
    private $_helpers = array();

    /**
     * 数据堆栈每一行
     *
     * @access protected
     * @var array
     */
    protected $row = array();

    /**
     * 数据堆栈
     *
     * @access public
     * @var array
     */
    public $stack = array();

    /**
     * 当前队列指针顺序值,从1开始
     *
     * @access public
     * @var integer
     */
    public $sequence = 0;

    /**
     * 队列长度
     *
     * @access public
     * @var integer
     */
    public $length = 0;

    /**
     * request对象
     *
     * @var Typecho_Request
     * @access public
     */
    public $request;

    /**
     * response对象
     *
     * @var Typecho_Response
     * @access public
     */
    public $response;

    /**
     * config对象
     *
     * @access public
     * @var Typecho_Config
     */
    public $parameter;

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     */
    public function __construct($request, $response, $params = NULL)
    {
        //设置函数内部对象
        $this->request = $request;
        $this->response = $response;
        $this->parameter = new Typecho_Config();

        if (!empty($params)) {
            $this->parameter->setDefault($params);
        }
    }

    /**
     * 解析回调
     * 
     * @param array $matches 
     * @access protected
     * @return string
     */
    protected function __parseCallback($matches)
    {
        return $this->{$matches[1]};
    }

    /**
     * execute function.
     *
     * @access public
     * @return void
     */
    public function execute(){}

    /**
     * post事件触发
     *
     * @param boolean $condition 触发条件
     * @return mixed
     */
    public function on($condition)
    {
        if ($condition) {
            return $this;
        } else {
            return new Typecho_Widget_Helper_Empty();
        }
    }

    /**
     * 获取对象插件句柄
     *
     * @access public
     * @param string $handle 句柄
     * @return Typecho_Plugin
     */
    public function pluginHandle($handle = NULL)
    {
        return Typecho_Plugin::factory(empty($handle) ? get_class($this) : $handle);
    }

    /**
     * widget别名 
     * 
     * @param string $widgetClass 
     * @param string $aliasClass 
     * @static
     * @access public
     * @return void
     */
    public static function alias($widgetClass, $aliasClass)
    {
        self::$_widgetAlias[$widgetClass] = $aliasClass;
    }

    /**
     * 工厂方法,将类静态化放置到列表中
     *
     * @access public
     * @param string $alias 组件别名
     * @param mixed $params 传递的参数
     * @param mixed $request 前端参数
     * @param boolean $enableResponse 是否允许http回执
     * @return Typecho_Widget
     * @throws Typecho_Exception
     */
    public static function widget($alias, $params = NULL, $request = NULL, $enableResponse = true)
    {
        $parts = explode('@', $alias);
        $className = $parts[0];
        $alias = empty($parts[1]) ? $className : $parts[1];

        if (isset(self::$_widgetAlias[$className])) {
            $className = self::$_widgetAlias[$className];
        }

        if (!isset(self::$_widgetPool[$alias])) {
            /** 如果类不存在 */
            if (!class_exists($className)) {
                throw new Typecho_Widget_Exception($className);
            }

            /** 初始化request */
            if (!empty($request)) {
                $requestObject = new Typecho_Request();
                $requestObject->setParams($request);
            } else {
                $requestObject = Typecho_Request::getInstance();
            }

            /** 初始化response */
            $responseObject = $enableResponse ? Typecho_Response::getInstance()
            : Typecho_Widget_Helper_Empty::getInstance();

            /** 初始化组件 */
            $widget = new $className($requestObject, $responseObject, $params);

            $widget->execute();
            self::$_widgetPool[$alias] = $widget;
        }

        return self::$_widgetPool[$alias];
    }

    /**
     * 释放组件
     *
     * @access public
     * @param string $alias 组件名称
     * @return void
     */
    public static function destory($alias)
    {
        if (isset(self::$_widgetPool[$alias])) {
            unset(self::$_widgetPool[$alias]);
        }
    }

    /**
     * 将类本身赋值
     *
     * @param string $variable 变量名
     * @return self
     */
    public function to(&$variable)
    {
        return $variable = $this;
    }

    /**
     * 格式化解析堆栈内的所有数据
     *
     * @param string $format 数据格式
     * @return void
     */
    public function parse($format)
    {
        while ($this->next()) {
            echo preg_replace_callback("/\{([_a-z0-9]+)\}/i", 
                array($this, '__parseCallback'), $format);
        }
    }

    /**
     * 将每一行的值压入堆栈
     *
     * @param array $value 每一行的值
     * @return array
     */
    public function push(array $value)
    {
        //将行数据按顺序置位
        $this->row = $value;
        $this->length ++;

        $this->stack[] = $value;
        return $value;
    }

    /**
     * 根据余数输出
     *
     * @access public
     * @return void
     */
    public function alt()
    {
        $args = func_get_args();
        $num = func_num_args();
        $split = $this->sequence % $num;
        echo $args[(0 == $split ? $num : $split) -1];
    }

    /**
     * 输出顺序值
     *
     * @access public
     * @return void
     */
    public function sequence()
    {
        echo $this->sequence;
    }

    /**
     * 输出数据长度
     *
     * @access public
     * @return void
     */
    public function length()
    {
        echo $this->length;
    }

    /**
     * 返回堆栈是否为空
     *
     * @return boolean
     */
    public function have()
    {
        return !empty($this->stack);
    }

    /**
     * 返回堆栈每一行的值
     *
     * @return array
     */
    public function next()
    {
        if ($this->stack) {
            $this->row = @$this->stack[key($this->stack)];
            next($this->stack);
            $this->sequence ++;
        }

        if (!$this->row) {
            reset($this->stack);
            if ($this->stack) {
                $this->row = $this->stack[key($this->stack)];
            }
            
            $this->sequence = 0;
            return false;
        }

        return $this->row;
    }

    /**
     * 魔术函数,用于挂接其它函数
     *
     * @access public
     * @param string $name 函数名
     * @param array $args 函数参数
     * @return void
     */
    public function __call($name, $args)
    {
        $method = 'call' . ucfirst($name);
        $this->pluginHandle()->trigger($plugged)->{$method}($this, $args);

        if (!$plugged) {
            echo $this->{$name};
        }
    }

    /**
     * 魔术函数,用于获取内部变量
     *
     * @access public
     * @param string $name 变量名
     * @return mixed
     */
    public function __get($name)
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

        return NULL;
    }

    /**
     * 设定堆栈每一行的值
     *
     * @param string $name 值对应的键值
     * @param mixed $value 相应的值
     * @return void
     */
    public function __set($name, $value)
    {
        $this->row[$name] = $value;
    }

    /**
     * 验证堆栈值是否存在
     *
     * @access public
     * @param string $name
     * @return boolean
     */
    public function __isSet($name)
    {
        return isset($this->row[$name]);
    }
}
