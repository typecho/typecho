<?php

namespace Widget\Options;

/**
 * 编辑选项组件
 */
trait EditTrait
{
    /**
     * 以checkbox选项判断是否某个值被启用
     *
     * @param mixed $settings 选项集合
     * @param string $name 选项名称
     * @return integer
     */
    protected function isEnableByCheckbox($settings, string $name): int
    {
        return is_array($settings) && in_array($name, $settings) ? 1 : 0;
    }
}
