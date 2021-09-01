<?php

namespace Widget;

use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 执行模块
 *
 * @package Widget
 */
class Action extends Widget
{
    /**
     * 路由映射
     *
     * @access private
     * @var array
     */
    private $map = [
        'ajax'                     => '\Widget\Ajax',
        'login'                    => '\Widget\Login',
        'logout'                   => '\Widget\Logout',
        'register'                 => '\Widget\Register',
        'upgrade'                  => '\Widget\Upgrade',
        'upload'                   => '\Widget\Upload',
        'service'                  => '\Widget\Service',
        'xmlrpc'                   => '\Widget\XmlRpc',
        'comments-edit'            => '\Widget\Comments\Edit',
        'contents-page-edit'       => '\Widget\Contents\Page\Edit',
        'contents-post-edit'       => '\Widget\Contents\Post\Edit',
        'contents-attachment-edit' => '\Widget\Contents\Attachment\Edit',
        'metas-category-edit'      => '\Widget\Metas\Category\Edit',
        'metas-tag-edit'           => '\Widget\Metas\Tag\Edit',
        'options-discussion'       => '\Widget\Options\Discussion',
        'options-general'          => '\Widget\Options\General',
        'options-permalink'        => '\Widget\Options\Permalink',
        'options-reading'          => '\Widget\Options\Reading',
        'plugins-edit'             => '\Widget\Plugins\Edit',
        'themes-edit'              => '\Widget\Themes\Edit',
        'users-edit'               => '\Widget\Users\Edit',
        'users-profile'            => '\Widget\Users\Profile',
        'backup'                   => '\Widget\Backup'
    ];

    /**
     * 入口函数,初始化路由器
     *
     * @throws Widget\Exception
     */
    public function execute()
    {
        /** 验证路由地址 **/
        $action = $this->request->action;

        /** 判断是否为plugin */
        $actionTable = array_merge($this->map, unserialize(Options::alloc()->actionTable));

        if (isset($actionTable[$action])) {
            $widgetName = $actionTable[$action];
        }

        if (isset($widgetName) && class_exists($widgetName)) {
            $widget = self::widget($widgetName);

            if ($widget instanceof ActionInterface) {
                $widget->action();
                return;
            }
        }

        throw new Widget\Exception(_t('请求的地址不存在'), 404);
    }
}
