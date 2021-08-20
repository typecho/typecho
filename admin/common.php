<?php
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

define('__TYPECHO_ADMIN__', true);

/** 载入配置文件 */
if (!defined('__TYPECHO_ROOT_DIR__') && !@include_once __DIR__ . '/../config.inc.php') {
    file_exists(__DIR__ . '/../install.php') ? header('Location: ../install.php') : print('Missing Config File');
    exit;
}

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');

/** 注册一个初始化插件 */
Typecho_Plugin::factory('admin/common.php')->begin();

Typecho_Widget::widget('Widget_Options')->to($options);
Typecho_Widget::widget('Widget_User')->to($user);
Typecho_Widget::widget('Widget_Security')->to($security);
Typecho_Widget::widget('Widget_Menu')->to($menu);

/** 初始化上下文 */
$request = Typecho_Request::getInstance();
$response = Typecho_Response::getInstance();

/** 检测是否是第一次登录 */
$currentMenu = $menu->getCurrentMenu();

if (!empty($currentMenu)) {
    $params = parse_url($currentMenu[2]);
    $adminFile = basename($params['path']);

    if (!$user->logged && !Typecho_Cookie::get('__typecho_first_run')) {
        
        if ('welcome.php' != $adminFile) {
            $response->redirect(Typecho_Common::url('welcome.php', $options->adminUrl));
        } else {
            Typecho_Cookie::set('__typecho_first_run', 1);
        }
    } elseif ($user->pass('administrator', true)) {
        /** 检测版本是否升级 */
        $mustUpgrade = version_compare(Typecho_Common::VERSION, $options->version, '>');

        if ($mustUpgrade && 'upgrade.php' != $adminFile && 'backup.php' != $adminFile) {
            $response->redirect(Typecho_Common::url('upgrade.php', $options->adminUrl));
        } else if (!$mustUpgrade && 'upgrade.php' == $adminFile) {
            $response->redirect($options->adminUrl);
        } else if (!$mustUpgrade && 'welcome.php' == $adminFile && $user->logged) {
            $response->redirect($options->adminUrl);
        }
    }
}
