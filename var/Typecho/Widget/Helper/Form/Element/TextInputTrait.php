<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Layout;

trait TextInputTrait
{
    /**
     * 初始化当前输入项
     *
     * @param string|null $name 表单元素名称
     * @param array|null $options 选择项
     * @return Layout|null
     */
    public function input(?string $name = null, ?array $options = null): ?Layout
    {
        $input = new Layout('input', [
            'id' => $name . '-0-' . self::$uniqueId,
            'name' => $name,
            'type' => $this->getType(),
            'class' => 'text'
        ]);

        $this->container($input);
        $this->inputs[] = $input;

        if (isset($this->label)) {
            $this->label->setAttribute('for', $name . '-0-' . self::$uniqueId);
        }

        return $input;
    }

    /**
     * 设置表单项默认值
     *
     * @param mixed $value 表单项默认值
     */
    protected function inputValue($value)
    {
        if (isset($value)) {
            $this->input->setAttribute('value', $this->filterValue($value));
        } else {
            $this->input->removeAttribute('value');
        }
    }

    /**
     * @param string $value
     * @return string
     */
    abstract protected function filterValue(string $value): string;

    /**
     * @return string
     */
    abstract protected function getType(): string;
}
