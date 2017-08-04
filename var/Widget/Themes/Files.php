<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 风格文件列表
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 风格文件列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Themes_Files extends Typecho_Widget
{
    /**
     * 当前风格
     *
     * @access private
     * @var string
     */
    private $_currentTheme;

    /**
     * 当前文件
     *
     * @access private
     * @var string
     */
    private $_currentFile;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Typecho_Widget_Exception
     */
    public function execute()
    {
        /** 管理员权限 */
        $this->widget('Widget_User')->pass('administrator');
        $this->_currentTheme = $this->request->filter('slug')->get('theme', $this->widget('Widget_Options')->theme);

        if (preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->_currentTheme)
            && is_dir($dir = $this->widget('Widget_Options')->themeFile($this->_currentTheme))
            && (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__)) {

            $files = array_filter(glob($dir . '/*'), function ($path) {
                return preg_match("/\.(php|js|css|vbs)$/i", $path);
            });

            $this->_currentFile = $this->request->get('file', 'index.php');

            if (preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->_currentFile)
            && file_exists($dir . '/' . $this->_currentFile)) {
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        $file = basename($file);
                        $this->push(array(
                            'file'      =>  $file,
                            'theme'     =>  $this->_currentTheme,
                            'current'   =>  ($file == $this->_currentFile)
                        ));
                    }
                }

                return;
            }
        }

        throw new Typecho_Widget_Exception('风格文件不存在', 404);
    }

    /**
     * 获取菜单标题
     *
     * @access public
     * @return string
     */
    public function getMenuTitle()
    {
        return _t('编辑文件 %s', $this->_currentFile);
    }

    /**
     * 获取文件内容
     *
     * @access public
     * @return string
     */
    public function currentContent()
    {
        return htmlspecialchars(file_get_contents($this->widget('Widget_Options')
            ->themeFile($this->_currentTheme, $this->_currentFile)));
    }

    /**
     * 获取文件是否可读
     *
     * @access public
     * @return string
     */
    public function currentIsWriteable()
    {
        return is_writeable($this->widget('Widget_Options')
            ->themeFile($this->_currentTheme, $this->_currentFile)) && !Typecho_Common::isAppEngine()
        && (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__);
    }

    /**
     * 获取当前文件
     *
     * @access public
     * @return string
     */
    public function currentFile()
    {
        return $this->_currentFile;
    }

    /**
     * 获取当前风格
     *
     * @access public
     * @return string
     */
    public function currentTheme()
    {
        return $this->_currentTheme;
    }
}
