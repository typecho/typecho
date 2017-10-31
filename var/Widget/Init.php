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
 * 初始化模块
 *
 * @package Widget
 */
class Widget_Init extends Typecho_Widget
{
    /**
     * 入口函数,初始化路由器
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 对变量赋值 */
        $options = $this->widget('Widget_Options');

        /** 检查安装状态 */
        if (!$options->installed) {
            $options->update(array('value' => 1), Typecho_Db::get()->sql()->where('name = ?', 'installed'));
        }

        /** 语言包初始化 */
        if ($options->lang && $options->lang != 'zh_CN') {
            $dir = defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
            Typecho_I18n::setLang($dir . '/' . $options->lang . '.mo');
        }

        /** 备份文件目录初始化 */
        if (!defined('__TYPECHO_BACKUP_DIR__')) {
            define('__TYPECHO_BACKUP_DIR__', __TYPECHO_ROOT_DIR__ . '/usr/backups');
        }

        /** cookie初始化 */
        Typecho_Cookie::setPrefix($options->rootUrl);

        /** 初始化charset */
        Typecho_Common::$charset = $options->charset;

        /** 初始化exception */
        Typecho_Common::$exceptionHandle = 'Widget_ExceptionHandle';

        /** 设置路径 */
        if (defined('__TYPECHO_PATHINFO_ENCODING__')) {
            $pathInfo = $this->request->getPathInfo(__TYPECHO_PATHINFO_ENCODING__, $options->charset);
        } else {
            $pathInfo = $this->request->getPathInfo();
        }

        Typecho_Router::setPathInfo($pathInfo);

        /** 初始化路由器 */
        Typecho_Router::setRoutes($options->routingTable);

        /** 初始化插件 */
        Typecho_Plugin::init($options->plugins);

        /** 初始化回执 */
        $this->response->setCharset($options->charset);
        $this->response->setContentType($options->contentType);

        /** 初始化时区 */
        Typecho_Date::setTimezoneOffset($options->timezone);

        /** 开始会话, 减小负载只针对后台打开session支持 */
        if ($this->widget('Widget_User')->hasLogin()) {
            @session_start();
        }

        /** 监听缓冲区 */
        ob_start();
    }
}
