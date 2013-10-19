<?php
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
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $themes = glob(__TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . '/*');

        if ($themes) {
            $options = $this->widget('Widget_Options');
            $siteUrl = $options->siteUrl;
            $adminUrl = $options->adminUrl;
            $activated  = 0;
            $result = array();

            foreach ($themes as $key => $theme) {
                $themeFile = $theme . '/index.php';
                if (file_exists($themeFile)) {
                    $info = Typecho_Plugin::parseInfo($themeFile);
                    $info['name'] = basename($theme);

                    if ($info['activated'] = ($options->theme == $info['name'])) {
                        $activated = $key;
                    }

                    $screen = glob($theme . '/screen*.{jpg,png,gif,bmp,jpeg,JPG,PNG,GIF,BMG,JPEG}', GLOB_BRACE);
                    if ($screen) {
                        $info['screen'] = Typecho_Common::url(trim(__TYPECHO_THEME_DIR__, '/') .
                        '/' . $info['name'] . '/' . basename(current($screen)), $siteUrl);
                    } else {
                        $info['screen'] = Typecho_Common::url('/img/noscreen.png', $adminUrl);
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
