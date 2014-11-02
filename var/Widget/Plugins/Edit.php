<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 插件管理
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 插件管理组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Plugins_Edit extends Widget_Abstract_Options implements Widget_Interface_Do
{
    /**
     * 手动配置插件变量
     *
     * @param       $pluginName 插件名称
     * @param array $settings 变量键值对
     * @param bool  $isPersonal 是否为私人变量
     */
    public static function configPlugin($pluginName, array $settings, $isPersonal = false)
    {
        $db = Typecho_Db::get();
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
                ->rows(array(
                    'name'  =>  $pluginName,
                    'value' =>  serialize($settings),
                    'user'  =>  0
                )));
            } else {
                foreach ($options as $option) {
                    $value = unserialize($option['value']);
                    $value = array_merge($value, $settings);
                    
                    $db->query($db->update('table.options')
                    ->rows(array('value' => serialize($value)))
                    ->where('name = ?', $pluginName)
                    ->where('user = ?', $option['user']));
                }
            }
        }
    }

    /**
     * 启用插件
     *
     * @param $pluginName
     * @throws Typecho_Widget_Exception
     */
    public function activate($pluginName)
    {
        /** 获取插件入口 */
        list($pluginFileName, $className) = Typecho_Plugin::portal($pluginName, $this->options->pluginDir($pluginName));
        $info = Typecho_Plugin::parseInfo($pluginFileName);

        /** 检测依赖信息 */
        list ($version, $build) = explode('/', Typecho_Common::VERSION);
        if (Typecho_Plugin::checkDependence($build, $info['dependence'])) {

            /** 获取已启用插件 */
            $plugins = Typecho_Plugin::export();
            $activatedPlugins = $plugins['activated'];

            /** 载入插件 */
            require_once $pluginFileName;

            /** 判断实例化是否成功 */
            if (isset($activatedPlugins[$pluginName]) || !class_exists($className)
            || !method_exists($className, 'activate')) {
                throw new Typecho_Widget_Exception(_t('无法启用插件'), 500);
            }

            try {
                $result = call_user_func(array($className, 'activate'));
                Typecho_Plugin::activate($pluginName);
                $this->update(array('value' => serialize(Typecho_Plugin::export())),
                $this->db->sql()->where('name = ?', 'plugins'));
            } catch (Typecho_Plugin_Exception $e) {
                /** 截获异常 */
                $this->widget('Widget_Notice')->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            $form = new Typecho_Widget_Helper_Form();
            call_user_func(array($className, 'config'), $form);

            $personalForm = new Typecho_Widget_Helper_Form();
            call_user_func(array($className, 'personalConfig'), $personalForm);

            $options = $form->getValues();
            $personalOptions = $personalForm->getValues();

            if ($options && !$this->configHandle($pluginName, $options, true)) {
                self::configPlugin($pluginName, $options);
            }

            if ($personalOptions && !$this->personalConfigHandle($className, $personalOptions)) {
                self::configPlugin($pluginName, $personalOptions, true);
            }

        } else {

            $result = _t('<a href="%s">%s</a> 无法在此版本的typecho下正常工作', $info['link'], $info['title']);

        }

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('plugin-' . $pluginName);

        if (isset($result) && is_string($result)) {
            $this->widget('Widget_Notice')->set($result, 'notice');
        } else {
            $this->widget('Widget_Notice')->set(_t('插件已经被启用'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * 禁用插件
     *
     * @param $pluginName
     * @throws Typecho_Widget_Exception
     * @throws Exception
     * @throws Typecho_Plugin_Exception
     */
    public function deactivate($pluginName)
    {
        /** 获取已启用插件 */
        $plugins = Typecho_Plugin::export();
        $activatedPlugins = $plugins['activated'];
        $pluginFileExist = true;

        try {
            /** 获取插件入口 */
            list($pluginFileName, $className) = Typecho_Plugin::portal($pluginName, $this->options->pluginDir($pluginName));
        } catch (Typecho_Plugin_Exception $e) {
            $pluginFileExist = false;

            if (!isset($activatedPlugins[$pluginName])) {
                throw $e;
            }
        }

        /** 判断实例化是否成功 */
        if (!isset($activatedPlugins[$pluginName])) {
            throw new Typecho_Widget_Exception(_t('无法禁用插件'), 500);
        }

        if ($pluginFileExist) {

            /** 载入插件 */
            require_once $pluginFileName;

            /** 判断实例化是否成功 */
            if (!isset($activatedPlugins[$pluginName]) || !class_exists($className)
            || !method_exists($className, 'deactivate')) {
                throw new Typecho_Widget_Exception(_t('无法禁用插件'), 500);
            }

            try {
                $result = call_user_func(array($className, 'deactivate'));
            } catch (Typecho_Plugin_Exception $e) {
                /** 截获异常 */
                $this->widget('Widget_Notice')->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            /** 设置高亮 */
            $this->widget('Widget_Notice')->highlight('plugin-' . $pluginName);
        }

        Typecho_Plugin::deactivate($pluginName);
        $this->update(array('value' => serialize(Typecho_Plugin::export())),
        $this->db->sql()->where('name = ?', 'plugins'));

        $this->delete($this->db->sql()->where('name = ?', 'plugin:' . $pluginName));
        $this->delete($this->db->sql()->where('name = ?', '_plugin:' . $pluginName));

        if (isset($result) && is_string($result)) {
            $this->widget('Widget_Notice')->set($result, 'notice');
        } else {
            $this->widget('Widget_Notice')->set(_t('插件已经被禁用'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * 配置插件
     *
     * @param $pluginName
     * @access public
     * @return void
     */
    public function config($pluginName)
    {
        $form = $this->widget('Widget_Plugins_Config')->config();

        /** 验证表单 */
        if ($form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();

        if (!$this->configHandle($pluginName, $settings, false)) {
            self::configPlugin($pluginName, $settings);
        }

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('plugin-' . $pluginName);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t("插件设置已经保存"), 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('plugins.php', $this->options->adminUrl));
    }

    /**
     * 用自有函数处理配置信息
     *
     * @access public
     * @param string $pluginName 插件名称
     * @param array $settings 配置值
     * @param boolean $isInit 是否为初始化
     * @return boolean
     */
    public function configHandle($pluginName, array $settings, $isInit)
    {
        /** 获取插件入口 */
        list($pluginFileName, $className) = Typecho_Plugin::portal($pluginName, $this->options->pluginDir($pluginName));

        if (method_exists($className, 'configHandle')) {
            call_user_func(array($className, 'configHandle'), $settings, $isInit);
            return true;
        }

        return false;
    }

    /**
     * 用自有函数处理自定义配置信息
     *
     * @access public
     * @param string $className 类名
     * @param array $settings 配置值
     * @return boolean
     */
    public function personalConfigHandle($className, array $settings)
    {
        if (method_exists($className, 'personalConfigHandle')) {
            call_user_func(array($className, 'personalConfigHandle'), $settings, true);
            return true;
        }

        return false;
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
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
