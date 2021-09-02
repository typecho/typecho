<?php

namespace Widget\Plugins;

use Typecho\Common;
use Typecho\Plugin;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 插件列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Rows extends Widget
{
    /**
     * 已启用插件
     *
     * @access public
     * @var array
     */
    public $activatedPlugins = [];

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 列出插件目录 */
        $pluginDirs = $this->getPlugins();
        $this->parameter->setDefault(['activated' => null]);

        /** 获取已启用插件 */
        $plugins = Plugin::export();
        $this->activatedPlugins = $plugins['activated'];

        if (!empty($pluginDirs)) {
            foreach ($pluginDirs as $key => $pluginDir) {
                $parts = $this->getPlugin($pluginDir, $key);
                if (empty($parts)) {
                    continue;
                }

                [$pluginName, $pluginFileName] = $parts;

                if (file_exists($pluginFileName)) {
                    $info = Plugin::parseInfo($pluginFileName);
                    $info['name'] = $pluginName;

                    $info['dependence'] = Plugin::checkDependence($info['since']);

                    /** 默认即插即用 */
                    $info['activated'] = true;

                    if ($info['activate'] || $info['deactivate'] || $info['config'] || $info['personalConfig']) {
                        $info['activated'] = isset($this->activatedPlugins[$pluginName]);

                        if (isset($this->activatedPlugins[$pluginName])) {
                            unset($this->activatedPlugins[$pluginName]);
                        }
                    }

                    if ($info['activated'] == $this->parameter->activated) {
                        $this->push($info);
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getPlugins(): array
    {
        return glob(__TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__ . '/*');
    }

    /**
     * @param string $plugin
     * @param string $index
     * @return array|null
     */
    protected function getPlugin(string $plugin, string $index): ?array
    {
        if (is_dir($plugin)) {
            /** 获取插件名称 */
            $pluginName = basename($plugin);

            /** 获取插件主文件 */
            $pluginFileName = $plugin . '/Plugin.php';
        } elseif (file_exists($plugin) && 'index.php' != basename($plugin)) {
            $pluginFileName = $plugin;
            $part = explode('.', basename($plugin));
            if (2 == count($part) && 'php' == $part[1]) {
                $pluginName = $part[0];
            } else {
                return null;
            }
        } else {
            return null;
        }

        return [$pluginName, $pluginFileName];
    }
}
