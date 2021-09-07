<?php

namespace Widget\Plugins;

use Typecho\Common;
use Typecho\Db;
use Typecho\Plugin;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 插件管理组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Options implements ActionInterface
{
    /**
     * @var bool
     */
    private $configNoticed = false;

    /**
     * 启用插件
     *
     * @param $pluginName
     * @throws Exception|Db\Exception|Plugin\Exception
     */
    public function activate($pluginName)
    {
        /** 获取插件入口 */
        [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);
        $info = Plugin::parseInfo($pluginFileName);

        /** 检测依赖信息 */
        if (Plugin::checkDependence($info['since'])) {

            /** 获取已启用插件 */
            $plugins = Plugin::export();
            $activatedPlugins = $plugins['activated'];

            /** 载入插件 */
            require_once $pluginFileName;

            /** 判断实例化是否成功 */
            if (
                isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'activate')
            ) {
                throw new Exception(_t('无法启用插件'), 500);
            }

            try {
                $result = call_user_func([$className, 'activate']);
                Plugin::activate($pluginName);
                $this->update(
                    ['value' => serialize(Plugin::export())],
                    $this->db->sql()->where('name = ?', 'plugins')
                );
            } catch (Plugin\Exception $e) {
                /** 截获异常 */
                Notice::alloc()->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            $form = new Form();
            call_user_func([$className, 'config'], $form);

            $personalForm = new Form();
            call_user_func([$className, 'personalConfig'], $personalForm);

            $options = $form->getValues();
            $personalOptions = $personalForm->getValues();

            if ($options && !$this->configHandle($pluginName, $options, true)) {
                self::configPlugin($pluginName, $options);
            }

            if ($personalOptions && !$this->personalConfigHandle($className, $personalOptions)) {
                self::configPlugin($pluginName, $personalOptions, true);
            }
        } else {
            $result = _t('<a href="%s">%s</a> 无法在此版本的typecho下正常工作', $info['homepage'], $info['title']);
        }

        /** 设置高亮 */
        Notice::alloc()->highlight('plugin-' . $pluginName);

        if (isset($result) && is_string($result)) {
            Notice::alloc()->set($result, 'notice');
        } else {
            Notice::alloc()->set(_t('插件已经被启用'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * 用自有函数处理配置信息
     *
     * @access public
     * @param string $pluginName 插件名称
     * @param array $settings 配置值
     * @param boolean $isInit 是否为初始化
     * @return boolean
     * @throws Plugin\Exception
     */
    public function configHandle(string $pluginName, array $settings, bool $isInit): bool
    {
        /** 获取插件入口 */
        [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);

        if (!$isInit && method_exists($className, 'configCheck')) {
            $result = call_user_func([$className, 'configCheck'], $settings);

            if (!empty($result) && is_string($result)) {
                Notice::alloc()->set($result, 'notice');
                $this->configNoticed = true;
            }
        }

        if (method_exists($className, 'configHandle')) {
            call_user_func([$className, 'configHandle'], $settings, $isInit);
            return true;
        }

        return false;
    }

    /**
     * 手动配置插件变量
     *
     * @param string $pluginName 插件名称
     * @param array $settings 变量键值对
     * @param bool $isPersonal 是否为私人变量
     * @throws Db\Exception
     */
    public static function configPlugin(string $pluginName, array $settings, bool $isPersonal = false)
    {
        $db = Db::get();
        $pluginName = ($isPersonal ? '_' : '') . 'plugin:' . $pluginName;

        $select = $db->select()->from('table.options')
            ->where('name = ?', $pluginName);

        $options = $db->fetchAll($select);

        if (empty($settings)) {
            if (!empty($options)) {
                $db->query($db->delete('table.options')->where('name = ?', $pluginName));
            }
        } else {
            if (empty($options)) {
                $db->query($db->insert('table.options')
                    ->rows([
                        'name'  => $pluginName,
                        'value' => serialize($settings),
                        'user'  => 0
                    ]));
            } else {
                foreach ($options as $option) {
                    $value = unserialize($option['value']);
                    $value = array_merge($value, $settings);

                    $db->query($db->update('table.options')
                        ->rows(['value' => serialize($value)])
                        ->where('name = ?', $pluginName)
                        ->where('user = ?', $option['user']));
                }
            }
        }
    }

    /**
     * 用自有函数处理自定义配置信息
     *
     * @param string $className 类名
     * @param array $settings 配置值
     * @return boolean
     */
    public function personalConfigHandle(string $className, array $settings): bool
    {
        if (method_exists($className, 'personalConfigHandle')) {
            call_user_func([$className, 'personalConfigHandle'], $settings, true);
            return true;
        }

        return false;
    }

    /**
     * 禁用插件
     *
     * @param string $pluginName
     * @throws Db\Exception
     * @throws Exception
     * @throws Plugin\Exception
     */
    public function deactivate(string $pluginName)
    {
        /** 获取已启用插件 */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];
        $pluginFileExist = true;

        try {
            /** 获取插件入口 */
            [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);
        } catch (Plugin\Exception $e) {
            $pluginFileExist = false;

            if (!isset($activatedPlugins[$pluginName])) {
                throw $e;
            }
        }

        /** 判断实例化是否成功 */
        if (!isset($activatedPlugins[$pluginName])) {
            throw new Exception(_t('无法禁用插件'), 500);
        }

        if ($pluginFileExist) {

            /** 载入插件 */
            require_once $pluginFileName;

            /** 判断实例化是否成功 */
            if (
                !isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'deactivate')
            ) {
                throw new Exception(_t('无法禁用插件'), 500);
            }

            try {
                $result = call_user_func([$className, 'deactivate']);
            } catch (Plugin\Exception $e) {
                /** 截获异常 */
                Notice::alloc()->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            /** 设置高亮 */
            Notice::alloc()->highlight('plugin-' . $pluginName);
        }

        Plugin::deactivate($pluginName);
        $this->update(['value' => serialize(Plugin::export())], $this->db->sql()->where('name = ?', 'plugins'));

        $this->delete($this->db->sql()->where('name = ?', 'plugin:' . $pluginName));
        $this->delete($this->db->sql()->where('name = ?', '_plugin:' . $pluginName));

        if (isset($result) && is_string($result)) {
            Notice::alloc()->set($result, 'notice');
        } else {
            Notice::alloc()->set(_t('插件已经被禁用'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * 配置插件
     *
     * @param string $pluginName
     * @throws Db\Exception
     * @throws Exception
     * @throws Plugin\Exception
     */
    public function config(string $pluginName)
    {
        $form = Config::alloc()->config();

        /** 验证表单 */
        if ($form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();

        if (!$this->configHandle($pluginName, $settings, false)) {
            self::configPlugin($pluginName, $settings);
        }

        /** 设置高亮 */
        Notice::alloc()->highlight('plugin-' . $pluginName);

        if (!$this->configNoticed) {
            /** 提示信息 */
            Notice::alloc()->set(_t("插件设置已经保存"), 'success');
        }

        /** 转向原页 */
        $this->response->redirect(Common::url('plugins.php', $this->options->adminUrl));
    }

    /**
     * 绑定动作
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('activate'))->activate($this->request->filter('slug')->activate);
        $this->on($this->request->is('deactivate'))->deactivate($this->request->filter('slug')->deactivate);
        $this->on($this->request->is('config'))->config($this->request->filter('slug')->config);
        $this->response->redirect($this->options->adminUrl);
    }
}
