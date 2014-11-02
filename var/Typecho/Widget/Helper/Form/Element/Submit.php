<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 提交按钮表单项帮手
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 提交按钮表单项帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_Form_Element_Submit extends Typecho_Widget_Helper_Form_Element
{
    /**
     * 初始化当前输入项
     *
     * @access public
     * @param string $name 表单元素名称
     * @param array $options 选择项
     * @return Typecho_Widget_Helper_Layout
     */
    public function input($name = NULL, array $options = NULL)
    {
        $this->setAttribute('class', 'typecho-option typecho-option-submit');
        $input = new Typecho_Widget_Helper_Layout('button', array('type' => 'submit'));
        $this->container($input);
        $this->inputs[] = $input;

        return $input;
    }

    /**
     * 设置表单元素值
     *
     * @access protected
     * @param mixed $value 表单元素值
     * @return void
     */
    protected function _value($value)
    {
        $this->input->html($value);
    }
}
