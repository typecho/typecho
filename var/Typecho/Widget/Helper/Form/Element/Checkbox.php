<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 多选框帮手
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 多选框帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_Form_Element_Checkbox extends Typecho_Widget_Helper_Form_Element
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
        foreach ($options as $value => $label) {
            $this->_options[$value] = new Typecho_Widget_Helper_Layout('input');
            $item = $this->multiline();
            $id = $this->name . '-' . $this->filterValue($value);
            $this->inputs[] = $this->_options[$value];

            $item->addItem($this->_options[$value]->setAttribute('name', $this->name . '[]')
            ->setAttribute('type', 'checkbox')
            ->setAttribute('value', $value)
            ->setAttribute('id', $id));

            $labelItem = new Typecho_Widget_Helper_Layout('label');
            $item->addItem($labelItem->setAttribute('for', $id)->html($label));
            $this->container($item);
        }

        return current($this->_options);
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
        $values = is_array($value) ? $value : array($value);

        foreach ($this->_options as $option) {
            $option->removeAttribute('checked');
        }

        foreach ($values as $value) {
            if (isset($this->_options[$value])) {
                $this->_options[$value]->setAttribute('checked', 'true');
            }
        }
    }
}
