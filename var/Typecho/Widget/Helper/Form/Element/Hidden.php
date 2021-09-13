<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 隐藏域帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Hidden extends Element
{
    /**
     * 自定义初始函数
     *
     * @return void
     */
    public function init()
    {
        /** 隐藏此行 */
        $this->setAttribute('style', 'display:none');
    }

    /**
     * 初始化当前输入项
     *
     * @access public
     * @param string|null $name 表单元素名称
     * @param array|null $options 选择项
     * @return Layout|null
     */
    public function input(?string $name = null, ?array $options = null): ?Layout
    {
        $input = new Layout('input', ['name' => $name, 'type' => 'hidden']);
        $this->container($input);
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
        $this->input->setAttribute('value', htmlspecialchars($value));
    }
}
