<?php

namespace Widget\Plugins;

use Typecho\Plugin;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\Base\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 插件配置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config extends Options
{
    /**
     * 获取插件信息
     *
     * @var array
     */
    public $info;

    /**
     * 插件文件路径
     *
     * @var string
     */
    private $pluginFileName;

    /**
     * 插件类
     *
     * @var string
     */
    private $className;

    /**
     * 绑定动作
     *
     * @throws Plugin\Exception
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');
        $config = $this->request->filter('slug')->config;
        if (empty($config)) {
            throw new Exception(_t('插件不存在'), 404);
        }

        /** 获取插件入口 */
        [$this->pluginFileName, $this->className] = Plugin::portal($config, $this->options->pluginDir);
        $this->info = Plugin::parseInfo($this->pluginFileName);
    }

    /**
     * 获取菜单标题
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('设置插件 %s', $this->info['title']);
    }

    /**
     * 配置插件
     *
     * @return Form
     * @throws Exception|Plugin\Exception
     */
    public function config()
    {
        /** 获取插件名称 */
        $pluginName = $this->request->filter('slug')->config;

        /** 获取已启用插件 */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];

        /** 判断实例化是否成功 */
        if (!$this->info['config'] || !isset($activatedPlugins[$pluginName])) {
            throw new Exception(_t('无法配置插件'), 500);
        }

        /** 载入插件 */
        require_once $this->pluginFileName;
        $form = new Form($this->security->getIndex('/action/plugins-edit?config=' . $pluginName), Form::POST_METHOD);
        call_user_func([$this->className, 'config'], $form);

        $options = $this->options->plugin($pluginName);

        if (!empty($options)) {
            foreach ($options as $key => $val) {
                $form->getInput($key)->value($val);
            }
        }

        $submit = new Form\Element\Submit(null, null, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }
}
