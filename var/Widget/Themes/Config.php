<?php

namespace Widget\Themes;

use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Submit;
use Widget\Base\Options as BaseOptions;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 皮肤配置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config extends BaseOptions
{
    /**
     * 绑定动作
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');

        if (!self::isExists()) {
            throw new Exception(_t('外观配置功能不存在'), 404);
        }
    }

    /**
     * 配置功能是否存在
     *
     * @return boolean
     */
    public static function isExists(): bool
    {
        $options = Options::alloc();
        $configFile = $options->themeFile($options->theme, 'functions.php');

        if (!$options->missingTheme && file_exists($configFile)) {
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
     * @return Form
     */
    public function config(): Form
    {
        $form = new Form($this->security->getIndex('/action/themes-edit?config'), Form::POST_METHOD);
        themeConfig($form);
        $inputs = $form->getInputs();

        if (!empty($inputs)) {
            foreach ($inputs as $key => $val) {
                $form->getInput($key)->value($this->options->{$key});
            }
        }

        $submit = new Submit(null, null, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }
}
