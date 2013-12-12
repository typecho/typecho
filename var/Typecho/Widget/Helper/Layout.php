<?php
/**
 * HTML布局帮手
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * HTML布局帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_Layout
{
    /**
     * 元素列表
     *
     * @access private
     * @var array
     */
    private $_items = array();

    /**
     * 表单属性列表
     *
     * @access private
     * @var array
     */
    private $_attributes = array();

    /**
     * 标签名称
     *
     * @access private
     * @var string
     */
    private $_tagName = 'div';

    /**
     * 是否自闭合
     *
     * @access private
     * @var boolean
     */
    private $_close = false;

    /**
     * 是否强制自闭合
     *
     * @access private
     * @var boolean
     */
    private $_forceClose = NULL;

    /**
     * 内部数据
     *
     * @access private
     * @var string
     */
    private $_html;

    /**
     * 父节点
     *
     * @access private
     * @var Typecho_Widget_Helper_Layout
     */
    private $_parent;

    /**
     * 构造函数,设置标签名称
     *
     * @access public
     * @param string $tagName 标签名称
     * @param array $attributes 属性列表
     * @return void
     */
    public function __construct($tagName = 'div', array $attributes = NULL)
    {
        $this->setTagName($tagName);

        if (!empty($attributes)) {
            foreach ($attributes as $attributeName => $attributeValue) {
                $this->setAttribute($attributeName, $attributeValue);
            }
        }
    }

    /**
     * 增加元素
     *
     * @access public
     * @param Typecho_Widget_Helper_Layout $item 元素
     * @return Typecho_Widget_Helper_Layout
     */
    public function addItem(Typecho_Widget_Helper_Layout $item)
    {
        $item->setParent($this);
        $this->_items[] = $item;
        return $this;
    }

    /**
     * 删除元素
     *
     * @access public
     * @param Typecho_Widget_Helper_Layout $item 元素
     * @return Typecho_Widget_Helper_Layout
     */
    public function removeItem(Typecho_Widget_Helper_Layout $item)
    {
        unset($this->_items[array_search($item, $this->_items)]);
        return $this;
    }

    /**
     * getItems  
     * 
     * @access public
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * 设置内部数据
     *
     * @access public
     * @param mixed $html 内部数据
     * @return unknown
     */
    public function html($html = false)
    {
        if (false === $html) {
            if (empty($this->_html)) {
                foreach ($this->_items as $item) {
                    $item->render();
                }
            } else {
                echo $this->_html;
            }
        } else {
            $this->_html = $html;
            return $this;
        }
    }

    /**
     * 设置标签名
     *
     * @access public
     * @param string $tagName 标签名
     * @return void
     */
    public function setTagName($tagName)
    {
        $this->_tagName = $tagName;
    }
    
    /**
     * getTagName  
     * 
     * @param mixed $tagName 
     * @access public
     * @return void
     */
    public function getTagName($tagName)
    {}

    /**
     * 设置表单属性
     *
     * @access public
     * @param string $attributeName 属性名称
     * @param string $attributeValue 属性值
     * @return Typecho_Widget_Helper_Layout
     */
    public function setAttribute($attributeName, $attributeValue)
    {
        $this->_attributes[$attributeName] = $attributeValue;
        return $this;
    }

    /**
     * 移除某个属性
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return Typecho_Widget_Helper_Layout
     */
    public function removeAttribute($attributeName)
    {
        if (isset($this->_attributes[$attributeName])) {
            unset($this->_attributes[$attributeName]);
        }

        return $this;
    }

    /**
     * 获取属性
     *
     * @access public
     * @param string $attributeName 属性名
     * @return string
     */
    public function getAttribute($attributeName)
    {
        return isset($this->_attributes[$attributeName]) ? $this->_attributes[$attributeName] : NULL;
    }

    /**
     * 设置是否自闭合
     *
     * @access public
     * @param boolean $close 是否自闭合
     * @return Typecho_Widget_Helper_Layout
     */
    public function setClose($close)
    {
        $this->_forceClose = $close;
        return $this;
    }

    /**
     * 设置父节点
     *
     * @access public
     * @param Typecho_Widget_Helper_Layout $parent 父节点
     * @return Typecho_Widget_Helper_Layout
     */
    public function setParent(Typecho_Widget_Helper_Layout $parent)
    {
        $this->_parent = $parent;
        return $this;
    }

    /**
     * 获取父节点
     *
     * @access public
     * @return Typecho_Widget_Helper_Layout
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * 增加到某布局元素集合中
     *
     * @access public
     * @param Typecho_Widget_Helper_Layout $parent 布局对象
     * @return Typecho_Widget_Helper_Layout
     */
    public function appendTo(Typecho_Widget_Helper_Layout $parent)
    {
        $parent->addItem($this);
        return $this;
    }

    /**
     * 开始标签
     *
     * @access public
     * @return void
     */
    public function start()
    {
        /** 输出标签 */
        echo $this->_tagName ? "<{$this->_tagName}" : NULL;

        /** 输出属性 */
        foreach ($this->_attributes as $attributeName => $attributeValue) {
            echo " {$attributeName}=\"{$attributeValue}\"";
        }

        /** 支持自闭合 */
        if (!$this->_close && $this->_tagName) {
            echo ">\n";
        }
    }

    /**
     * 结束标签
     *
     * @access public
     * @return void
     */
    public function end()
    {
        if ($this->_tagName) {
            echo $this->_close ? " />\n" : "</{$this->_tagName}>\n";
        }
    }

    /**
     * 设置属性
     *
     * @access public
     * @param string $attributeName 属性名称
     * @param string $attributeValue 属性值
     * @return void
     */
    public function __set($name, $value)
    {
        $this->_attributes[$name] = $value;
    }

    /**
     * 获取属性
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return void
     */
    public function __get($name)
    {
        return isset($this->_attributes[$name]) ? $this->_attributes[$name] : NULL;
    }

    /**
     * 输出所有元素
     *
     * @access public
     * @return void
     */
    public function render()
    {
        if (empty($this->_items) && empty($this->_html)) {
            $this->_close = true;
        }

        if (NULL !== $this->_forceClose) {
            $this->_close = $this->_forceClose;
        }

        $this->start();
        $this->html();
        $this->end();
    }
}
