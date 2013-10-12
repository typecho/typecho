<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 插件列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Plugins_List extends Typecho_Widget
{
    /**
     * 已启用插件
     *
     * @access public
     * @var array
     */
    public $activatedPlugins = array();

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 列出插件目录 */
        $pluginDirs = glob(__TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__ . '/*');
        $this->parameter->setDefault(array('activated' => NULL));

        /** 获取已启用插件 */
        $plugins = Typecho_Plugin::export();
        $this->activatedPlugins = $plugins['activated'];

        if (!empty($pluginDirs)) {
            foreach ($pluginDirs as $pluginDir) {
                if (is_dir($pluginDir)) {
                    /** 获取插件名称 */
                    $pluginName = basename($pluginDir);

                    /** 获取插件主文件 */
                    $pluginFileName = $pluginDir . '/Plugin.php';
                } else if (file_exists($pluginDir) && 'index.php' != basename($pluginDir)) {
                    $pluginFileName = $pluginDir;
                    $part = explode('.', basename($pluginDir));
                    if (2 == count($part) && 'php' == $part[1]) {
                        $pluginName = $part[0];
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                if (file_exists($pluginFileName)) {
                    $info = Typecho_Plugin::parseInfo($pluginFileName);
                    $info['name'] = $pluginName;

                    list ($version, $build) = explode('/', Typecho_Common::VERSION);
                    $info['dependence'] = Typecho_Plugin::checkDependence($build, $info['dependence']);

                    /** 默认即插即用 */
                    $info['activated'] = true;

                    if ($info['activate'] || $info['deactivate'] || $info['config'] || $info['personalConfig']) {
                        $info['activated'] = isset($this->activatedPlugins[$pluginName]);

                        if (isset($this->activatedPlugins[$pluginName])) {
                            unset($this->activatedPlugins[$pluginName]);
                        }
                    }

                    if (!is_bool($this->parameter->activated) || $info['activated']  == $this->parameter->activated) {
                        $this->push($info);
                    }
                }
            }
        }
    }
}
