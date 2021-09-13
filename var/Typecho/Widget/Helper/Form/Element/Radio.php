<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 单选框帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Radio extends Element
{
    /**
     * 选择值
     *
     * @var array
     */
    private $options = [];

    /**
     * 初始化当前输入项
     *
     * @param string|null $name 表单元素名称
     * @param array|null $options 选择项
     * @return Layout|null
     */
    public function input(?string $name = null, ?array $options = null): ?Layout
    {
        foreach ($options as $value => $label) {
            $this->options[$value] = new Layout('input');
            $item = $this->multiline();
            $id = $this->name . '-' . $this->filterValue($value);
            $this->inputs[] = $this->options[$value];

            $item->addItem($this->options[$value]->setAttribute('name', $this->name)
                ->setAttribute('type', 'radio')
                ->setAttribute('value', $value)
                ->setAttribute('id', $id));

            $labelItem = new Layout('label');
            $item->addItem($labelItem->setAttribute('for', $id)->html($label));
            $this->container($item);
        }

        return current($this->options) ?: null;
    }

    /**
     * 设置表单元素值
     *
     * @param mixed $value 表单元素值
     */
    protected function inputValue($value)
    {
        foreach ($this->options as $option) {
            $option->removeAttribute('checked');
        }

        if (isset($this->options[$value])) {
            $this->value = $value;
            $this->options[$value]->setAttribute('checked', 'true');
            $this->input = $this->options[$value];
        }
    }
}
