<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 多行文字域帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Textarea extends Element
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
        $input = new Layout('textarea', ['id' => $name . '-0-' . self::$uniqueId, 'name' => $name]);
        $this->label->setAttribute('for', $name . '-0-' . self::$uniqueId);
        $this->container($input->setClose(false));
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
        $this->input->html(htmlspecialchars($value));
    }
}
