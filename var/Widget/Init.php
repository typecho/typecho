<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Date;
use Typecho\Db;
use Typecho\I18n;
use Typecho\Plugin;
use Typecho\Response;
use Typecho\Router;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 初始化模块
 *
 * @package Widget
 */
class Init extends Widget
{
    /**
     * 入口函数,初始化路由器
     *
     * @access public
     * @return void
     * @throws Widget\Exception|Db\Exception
     */
    public function execute()
    {
        // init class
        define('__TYPECHO_REWRITE_CLASS__', [
            'Typecho_Plugin_Interface'    => '\Typecho\Plugin\PluginInterface',
            'Typecho_Widget_Helper_Empty' => '\Typecho\Widget\Helper\EmptyClass',
            'Widget_Abstract'             => '\Widget\Base',
            'Widget_Abstract_Contents'    => '\Widget\Base\Contents',
            'Widget_Abstract_Comments'    => '\Widget\Base\Comments',
            'Widget_Abstract_Metas'       => '\Widget\Base\Metas',
            'Widget_Abstract_Options'     => '\Widget\Base\Options',
            'Widget_Abstract_Users'       => '\Widget\Base\Users',
        ]);

        /** 对变量赋值 */
        $options = $this->widget('Widget_Options');

        /** 检查安装状态 */
        if (!defined('__TYPECHO_INSTALL__') && !$options->installed) {
            $options->update(['value' => 1], Db::get()->sql()->where('name = ?', 'installed'));
        }

        /** 语言包初始化 */
        if ($options->lang && $options->lang != 'zh_CN') {
            $dir = defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
            I18n::setLang($dir . '/' . $options->lang . '.mo');
        }

        /** 备份文件目录初始化 */
        if (!defined('__TYPECHO_BACKUP_DIR__')) {
            define('__TYPECHO_BACKUP_DIR__', __TYPECHO_ROOT_DIR__ . '/usr/backups');
        }

        /** cookie初始化 */
        Cookie::setPrefix($options->rootUrl);

        /** 初始化exception */
        if (!defined('__TYPECHO_DEBUG__') || !__TYPECHO_DEBUG__) {
            set_exception_handler(function (\Throwable $exception) {
                Response::getInstance()->clean();
                ob_end_clean();

                ob_start(function ($content) {
                    Response::getInstance()->sendHeaders();
                    return $content;
                });

                if (404 == $exception->getCode()) {
                    new ExceptionHandle($exception);
                } else {
                    Common::error($exception);
                }

                exit;
            });
        }

        /** 初始化路由器 */
        Router::setRoutes($options->routingTable);

        /** 初始化插件 */
        Plugin::init($options->plugins);

        /** 初始化回执 */
        $this->response->setCharset($options->charset);
        $this->response->setContentType($options->contentType);

        /** 初始化时区 */
        Date::setTimezoneOffset($options->timezone);

        /** 开始会话, 减小负载只针对后台打开session支持 */
        if (!defined('__TYPECHO_INSTALL__') && $this->widget('Widget_User')->hasLogin()) {
            @session_start();
        }
    }
}
