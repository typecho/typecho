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
 * 执行模块
 *
 * @package Widget
 */
class Widget_Do extends Typecho_Widget
{
    /**
     * 路由映射
     *
     * @access private
     * @var array
     */
    private $_map = array(
        'ajax'                      =>  'Widget_Ajax',
        'login'                     =>  'Widget_Login',
        'logout'                    =>  'Widget_Logout',
        'register'                  =>  'Widget_Register',
        'upgrade'                   =>  'Widget_Upgrade',
        'upload'                    =>  'Widget_Upload',
        'service'                   =>  'Widget_Service',
        'xmlrpc'                    =>  'Widget_XmlRpc',
        'comments-edit'             =>  'Widget_Comments_Edit',
        'contents-page-edit'        =>  'Widget_Contents_Page_Edit',
        'contents-post-edit'        =>  'Widget_Contents_Post_Edit',
        'contents-attachment-edit'  =>  'Widget_Contents_Attachment_Edit',
        'metas-category-edit'       =>  'Widget_Metas_Category_Edit',
        'metas-tag-edit'            =>  'Widget_Metas_Tag_Edit',
        'options-discussion'        =>  'Widget_Options_Discussion',
        'options-general'           =>  'Widget_Options_General',
        'options-permalink'         =>  'Widget_Options_Permalink',
        'options-reading'           =>  'Widget_Options_Reading',
        'plugins-edit'              =>  'Widget_Plugins_Edit',
        'themes-edit'               =>  'Widget_Themes_Edit',
        'users-edit'                =>  'Widget_Users_Edit',
        'users-profile'             =>  'Widget_Users_Profile',
        'backup'                    =>  'Widget_Backup'
    );

    /**
     * 入口函数,初始化路由器
     *
     * @access public
     * @return void
     * @throws Typecho_Widget_Exception
     */
    public function execute()
    {
        /** 验证路由地址 **/
        $action = $this->request->action;

        //兼容老版本
        if (empty($action)) {
            $widget = trim($this->request->widget, '/');
            $objectName = 'Widget_' . str_replace('/', '_', $widget);

            if (preg_match("/^[_a-z0-9]$/i", $objectName) && Typecho_Common::isAvailableClass($objectName)) {
                $widgetName = $objectName;
            }
        } else {
            /** 判断是否为plugin */
            $actionTable = array_merge($this->_map, unserialize($this->widget('Widget_Options')->actionTable));

            if (isset($actionTable[$action])) {
                $widgetName = $actionTable[$action];
            }
        }

        if (isset($widgetName) && class_exists($widgetName)) {
            $reflectionWidget =  new ReflectionClass($widgetName);
            if ($reflectionWidget->implementsInterface('Widget_Interface_Do')) {
                $this->widget($widgetName)->action();
                return;
            }
        }

        throw new Typecho_Widget_Exception(_t('请求的地址不存在'), 404);
    }
}
