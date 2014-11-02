<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 下拉选择框帮手
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 下拉选择框帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_Form_Element_Select extends Typecho_Widget_Helper_Form_Element
{
    /**
     * 选择值
     *
     * @access private
     * @var array
     */
    private $_options = array();

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
        $input = new Typecho_Widget_Helper_Layout('select');
        $this->container($input->setAttribute('name', $name)
        ->setAttribute('id', $name . '-0-' . self::$uniqueId));
        $this->label->setAttribute('for', $name . '-0-' . self::$uniqueId);
        $this->inputs[] = $input;

        foreach ($options as $value => $label) {
            $this->_options[$value] = new Typecho_Widget_Helper_Layout('option');
            $input->addItem($this->_options[$value]->setAttribute('value', $value)->html($label));
        }

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
        foreach ($this->_options as $option) {
            $option->removeAttribute('selected');
        }

        if (isset($this->_options[$value])) {
            $this->_options[$value]->setAttribute('selected', 'true');
        }
    }
}
