<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 表单元素抽象帮手
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 表单元素抽象类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
abstract class Typecho_Widget_Helper_Form_Element extends Typecho_Widget_Helper_Layout
{
    /**
     * 表单描述
     *
     * @access private
     * @var string
     */
    protected $description;

    /**
     * 表单消息
     *
     * @access protected
     * @var string
     */
    protected $message;

    /**
     * 多行输入
     *
     * @access public
     * @var array()
     */
    protected $multiline = array();

    /**
     * 单例唯一id
     *
     * @access protected
     * @var integer
     */
    protected static $uniqueId = 0;

    /**
     * 表单元素容器
     *
     * @access public
     * @var Typecho_Widget_Helper_Layout
     */
    public $container;

    /**
     * 输入栏
     *
     * @access public
     * @var Typecho_Widget_Helper_Layout
     */
    public $input;

    /**
     * inputs  
     * 
     * @var array
     * @access public
     */
    public $inputs = array();

    /**
     * 表单标题
     *
     * @access public
     * @var Typecho_Widget_Helper_Layout
     */
    public $label;

    /**
     * 表单验证器
     *
     * @access public
     * @var array
     */
    public $rules = array();

    /**
     * 表单名称
     *
     * @access public
     * @var string
     */
    public $name;

    /**
     * 表单值
     *
     * @access public
     * @var mixed
     */
    public $value;

    /**
     * 构造函数
     *
     * @access public
     * @param string $name 表单输入项名称
     * @param array $options 选择项
     * @param mixed $value 表单默认值
     * @param string $label 表单标题
     * @param string $description 表单描述
     * @return void
     */
    public function __construct($name = NULL, array $options = NULL, $value = NULL, $label = NULL, $description = NULL)
    {
        /** 创建html元素,并设置class */
        parent::__construct('ul', array('class' => 'typecho-option', 'id' => 'typecho-option-item-' . $name . '-' . self::$uniqueId));
        $this->name = $name;
        self::$uniqueId ++;

        /** 运行自定义初始函数 */
        $this->init();

        /** 初始化表单标题 */
        if (NULL !== $label) {
            $this->label($label);
        }

        /** 初始化表单项 */
        $this->input = $this->input($name, $options);

        /** 初始化表单值 */
        if (NULL !== $value) {
            $this->value($value);
        }

        /** 初始化表单描述 */
        if (NULL !== $description) {
            $this->description($description);
        }
    }

    /**
     * filterValue  
     * 
     * @param mixed $value 
     * @access protected
     * @return string
     */
    protected function filterValue($value)
    {
        if (preg_match_all('/[_0-9a-z-]+/i', $value, $matches)) {
            return implode('-', $matches[0]);
        }

        return '';
    }

    /**
     * 自定义初始函数
     *
     * @access public
     * @return void
     */
    public function init(){}

    /**
     * 创建表单标题
     *
     * @access public
     * @param string $value 标题字符串
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function label($value)
    {
        /** 创建标题元素 */
        if (empty($this->label)) {
            $this->label = new Typecho_Widget_Helper_Layout('label', array('class' => 'typecho-label'));
            $this->container($this->label);
        }

        $this->label->html($value);
        return $this;
    }

    /**
     * 在容器里增加元素
     *
     * @access public
     * @param Typecho_Widget_Helper_Layout $item 表单元素
     * @return $this
     */
    public function container(Typecho_Widget_Helper_Layout $item)
    {
        /** 创建表单容器 */
        if (empty($this->container)) {
            $this->container = new Typecho_Widget_Helper_Layout('li');
            $this->addItem($this->container);
        }

        $this->container->addItem($item);
        return $this;
    }

    /**
     * 设置提示信息
     *
     * @access public
     * @param string $message 提示信息
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function message($message)
    {
        if (empty($this->message)) {
            $this->message =  new Typecho_Widget_Helper_Layout('p', array('class' => 'message error'));
            $this->container($this->message);
        }

        $this->message->html($message);
        return $this;
    }

    /**
     * 设置描述信息
     *
     * @access public
     * @param string $description 描述信息
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function description($description)
    {
        /** 创建描述元素 */
        if (empty($this->description)) {
            $this->description = new Typecho_Widget_Helper_Layout('p', array('class' => 'description'));
            $this->container($this->description);
        }

        $this->description->html($description);
        return $this;
    }

    /**
     * 设置表单元素值
     *
     * @access public
     * @param mixed $value 表单元素值
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function value($value)
    {
        $this->value = $value;
        $this->_value($value);
        return $this;
    }

    /**
     * 多行输出模式
     *
     * @access public
     * @return Typecho_Widget_Helper_Layout
     */
    public function multiline()
    {
        $item = new Typecho_Widget_Helper_Layout('span');
        $this->multiline[] = $item;
        return $item;
    }

    /**
     * 多行输出模式
     *
     * @access public
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function multiMode()
    {
        foreach ($this->multiline as $item) {
            $item->setAttribute('class', 'multiline');
        }
        return $this;
    }

    /**
     * 初始化当前输入项
     *
     * @access public
     * @param Typecho_Widget_Helper_Layout $container 容器对象
     * @param string $name 表单元素名称
     * @param array $options 选择项
     * @return Typecho_Widget_Helper_Form_Element
     */
    abstract public function input($name = NULL, array $options = NULL);

    /**
     * 设置表单元素值
     *
     * @access protected
     * @param mixed $value 表单元素值
     * @return void
     */
    abstract protected function _value($value);

    /**
     * 增加验证器
     *
     * @access public
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function addRule($name)
    {
        $this->rules[] = func_get_args();
        return $this;
    }
}
