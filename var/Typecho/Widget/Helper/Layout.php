<?php

namespace Typecho\Widget\Helper;

/**
 * HTML布局帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Layout
{
    /**
     * 元素列表
     *
     * @access private
     * @var array
     */
    private $items = [];

    /**
     * 表单属性列表
     *
     * @access private
     * @var array
     */
    private $attributes = [];

    /**
     * 标签名称
     *
     * @access private
     * @var string
     */
    private $tagName = 'div';

    /**
     * 是否自闭合
     *
     * @access private
     * @var boolean
     */
    private $close = false;

    /**
     * 是否强制自闭合
     *
     * @access private
     * @var boolean
     */
    private $forceClose = null;

    /**
     * 内部数据
     *
     * @access private
     * @var string
     */
    private $html;

    /**
     * 父节点
     *
     * @access private
     * @var Layout
     */
    private $parent;

    /**
     * 构造函数,设置标签名称
     *
     * @param string $tagName 标签名称
     * @param array|null $attributes 属性列表
     *
     */
    public function __construct(string $tagName = 'div', ?array $attributes = null)
    {
        $this->setTagName($tagName);

        if (!empty($attributes)) {
            foreach ($attributes as $attributeName => $attributeValue) {
                $this->setAttribute($attributeName, (string)$attributeValue);
            }
        }
    }

    /**
     * 设置表单属性
     *
     * @param string $attributeName 属性名称
     * @param mixed $attributeValue 属性值
     * @return $this
     */
    public function setAttribute(string $attributeName, $attributeValue): Layout
    {
        $this->attributes[$attributeName] = (string) $attributeValue;
        return $this;
    }

    /**
     * 删除元素
     *
     * @param Layout $item 元素
     * @return $this
     */
    public function removeItem(Layout $item): Layout
    {
        unset($this->items[array_search($item, $this->items)]);
        return $this;
    }

    /**
     * getItems
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * getTagName
     *
     * @return string
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }

    /**
     * 设置标签名
     *
     * @param string $tagName 标签名
     */
    public function setTagName(string $tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * 移除某个属性
     *
     * @param string $attributeName 属性名称
     * @return $this
     */
    public function removeAttribute(string $attributeName): Layout
    {
        if (isset($this->attributes[$attributeName])) {
            unset($this->attributes[$attributeName]);
        }

        return $this;
    }

    /**
     * 获取属性
     *
     * @access public
     *
     * @param string $attributeName 属性名
     * @return string|null
     */
    public function getAttribute(string $attributeName): ?string
    {
        return $this->attributes[$attributeName] ?? null;
    }

    /**
     * 设置是否自闭合
     *
     * @param boolean $close 是否自闭合
     * @return $this
     */
    public function setClose(bool $close): Layout
    {
        $this->forceClose = $close;
        return $this;
    }

    /**
     * 获取父节点
     *
     * @return Layout
     */
    public function getParent(): Layout
    {
        return $this->parent;
    }

    /**
     * 设置父节点
     *
     * @param Layout $parent 父节点
     * @return $this
     */
    public function setParent(Layout $parent): Layout
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * 增加到某布局元素集合中
     *
     * @param Layout $parent 布局对象
     * @return $this
     */
    public function appendTo(Layout $parent): Layout
    {
        $parent->addItem($this);
        return $this;
    }

    /**
     * 增加元素
     *
     * @param Layout $item 元素
     * @return $this
     */
    public function addItem(Layout $item): Layout
    {
        $item->setParent($this);
        $this->items[] = $item;
        return $this;
    }

    /**
     * 获取属性
     *
     * @param string $name 属性名称
     * @return string|null
     */
    public function __get(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * 设置属性
     *
     * @param string $name 属性名称
     * @param string $value 属性值
     */
    public function __set(string $name, string $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * 输出所有元素
     */
    public function render()
    {
        if (empty($this->items) && empty($this->html)) {
            $this->close = true;
        }

        if (null !== $this->forceClose) {
            $this->close = $this->forceClose;
        }

        $this->start();
        $this->html();
        $this->end();
    }

    /**
     * 开始标签
     */
    public function start()
    {
        /** 输出标签 */
        echo $this->tagName ? "<{$this->tagName}" : null;

        /** 输出属性 */
        foreach ($this->attributes as $attributeName => $attributeValue) {
            echo " {$attributeName}=\"{$attributeValue}\"";
        }

        /** 支持自闭合 */
        if (!$this->close && $this->tagName) {
            echo ">\n";
        }
    }

    /**
     * 设置内部数据
     *
     * @param string|null $html 内部数据
     * @return void|$this
     */
    public function html(?string $html = null)
    {
        if (null === $html) {
            if (empty($this->html)) {
                foreach ($this->items as $item) {
                    $item->render();
                }
            } else {
                echo $this->html;
            }
        } else {
            $this->html = $html;
            return $this;
        }
    }

    /**
     * 结束标签
     *
     * @return void
     */
    public function end()
    {
        if ($this->tagName) {
            echo $this->close ? " />\n" : "</{$this->tagName}>\n";
        }
    }
}
