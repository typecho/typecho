<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 下拉选择框帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Select extends Element
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
        $input = new Layout('select');
        $this->container($input->setAttribute('name', $name)
            ->setAttribute('id', $name . '-0-' . self::$uniqueId));
        $this->label->setAttribute('for', $name . '-0-' . self::$uniqueId);
        $this->inputs[] = $input;

        foreach ($options as $value => $label) {
            $this->options[$value] = new Layout('option');
            $input->addItem($this->options[$value]->setAttribute('value', $value)->html($label));
        }

        return $input;
    }

    /**
     * 设置表单元素值
     *
     * @param mixed $value 表单元素值
     */
    protected function inputValue($value)
    {
        foreach ($this->options as $option) {
            $option->removeAttribute('selected');
        }

        if (isset($this->options[$value])) {
            $this->options[$value]->setAttribute('selected', 'true');
        }
    }
}
