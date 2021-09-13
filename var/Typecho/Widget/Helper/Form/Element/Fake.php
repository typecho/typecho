<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 虚拟域帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Fake extends Element
{
    /**
     * 构造函数
     *
     * @param string $name 表单输入项名称
     * @param mixed $value 表单默认值
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        self::$uniqueId++;

        /** 运行自定义初始函数 */
        $this->init();

        /** 初始化表单项 */
        $this->input = $this->input($name);

        /** 初始化表单值 */
        if (null !== $value) {
            $this->value($value);
        }
    }

    /**
     * 初始化当前输入项
     *
     * @param string|null $name 表单元素名称
     * @param array|null $options 选择项
     * @return Layout|null
     */
    public function input(?string $name = null, ?array $options = null): ?Layout
    {
        $input = new Layout('input');
        $this->inputs[] = $input;
        return $input;
    }

    /**
     * 设置表单项默认值
     *
     * @param mixed $value 表单项默认值
     */
    protected function inputValue($value)
    {
        $this->input->setAttribute('value', $value);
    }
}
