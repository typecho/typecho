<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 皮肤配置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Themes_Config extends Widget_Abstract_Options
{
    /**
     * 绑定动作
     *
     * @access public
     * @return void
     * @throws Typecho_Widget_Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');
        
        if (!self::isExists()) {
            throw new Typecho_Widget_Exception(_t('外观配置功能不存在'), 404);
        }
    }

    /**
     * 配置功能是否存在
     * 
     * @access public
     * @return boolean
     */
    public static function isExists()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $configFile = $options->themeFile($options->theme, 'functions.php');

        if (file_exists($configFile)) {
            require_once $configFile;
            
            if (function_exists('themeConfig')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 配置外观
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function config()
    {
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/themes-edit?config'),
            Typecho_Widget_Helper_Form::POST_METHOD);
        themeConfig($form);
        $inputs = $form->getInputs();
        
        if (!empty($inputs)) {
            foreach ($inputs as $key => $val) {
                $form->getInput($key)->value($this->options->{$key});
            }
        }

        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }
}
