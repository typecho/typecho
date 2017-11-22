<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 风格列表
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 风格列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Themes_List extends Typecho_Widget
{
    /**
     * @return array
     */
    protected function getThemes()
    {
        return glob(__TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . '/*', GLOB_ONLYDIR);
    }

    /**
     * get theme
     *
     * @param string $theme
     * @param mixed $index
     * @return string
     */
    protected function getTheme($theme, $index)
    {
        return basename($theme);
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $themes = $this->getThemes();

        if ($themes) {
            $options = $this->widget('Widget_Options');
            $activated  = 0;
            $result = array();

            foreach ($themes as $key => $theme) {
                $themeFile = $theme . '/index.php';
                if (file_exists($themeFile)) {
                    $info = Typecho_Plugin::parseInfo($themeFile);
                    $info['name'] = $this->getTheme($theme, $key);

                    if ($info['activated'] = ($options->theme == $info['name'])) {
                        $activated = $key;
                    }

                    $screen = array_filter(glob($theme . '/*'), function ($path) {
                        return preg_match("/screenshot\.(jpg|png|gif|bmp|jpeg)$/i", $path);
                    });

                    if ($screen) {
                        $info['screen'] = $options->themeUrl(basename(current($screen)), $info['name']);
                    } else {
                        $info['screen'] = Typecho_Common::url('noscreen.png', $options->adminStaticUrl('img'));
                    }

                    $result[$key] = $info;
                }
            }

            $clone = $result[$activated];
            unset($result[$activated]);
            array_unshift($result, $clone);
            array_filter($result, array($this, 'push'));
        }
    }
}
