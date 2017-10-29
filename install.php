<?php if (!file_exists(dirname(__FILE__) . '/config.inc.php')): ?>
<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/** 定义根目录 */
define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));

/** 定义插件目录(相对路径) */
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');

/** 定义模板目录(相对路径) */
define('__TYPECHO_THEME_DIR__', '/usr/themes');

/** 后台路径(相对路径) */
define('__TYPECHO_ADMIN_DIR__', '/admin/');

/** 设置包含路径 */
@set_include_path(get_include_path() . PATH_SEPARATOR .
__TYPECHO_ROOT_DIR__ . '/var' . PATH_SEPARATOR .
__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__);

/** 载入API支持 */
require_once 'Typecho/Common.php';

/** 载入Response支持 */
require_once 'Typecho/Response.php';

/** 载入配置支持 */
require_once 'Typecho/Config.php';

/** 载入异常支持 */
require_once 'Typecho/Exception.php';

/** 载入插件支持 */
require_once 'Typecho/Plugin.php';

/** 载入国际化支持 */
require_once 'Typecho/I18n.php';

/** 载入数据库支持 */
require_once 'Typecho/Db.php';

/** 载入路由器支持 */
require_once 'Typecho/Router.php';

/** 程序初始化 */
Typecho_Common::init();

else:

    require_once dirname(__FILE__) . '/config.inc.php';

    //判断是否已经安装
    $db = Typecho_Db::get();
    try {
        $installed = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'installed'));
        if (empty($installed) || $installed['value'] == 1) {
            Typecho_Response::setStatus(404);
            exit;
        }
    } catch (Exception $e) {
        // do nothing
    }

endif;

// 挡掉可能的跨站请求
if (!empty($_GET) || !empty($_POST)) {
    if (empty($_SERVER['HTTP_REFERER'])) {
        exit;
    }

    $parts = parse_url($_SERVER['HTTP_REFERER']);
	if (!empty($parts['port'])) {
        $parts['host'] = "{$parts['host']}:{$parts['port']}";
    }

    if (empty($parts['host']) || $_SERVER['HTTP_HOST'] != $parts['host']) {
        exit;
    }
}

/**
 * 获取传递参数
 *
 * @param string $name 参数名称
 * @param string $default 默认值
 * @return string
 */
function _r($name, $default = NULL) {
    return isset($_REQUEST[$name]) ?
        (is_array($_REQUEST[$name]) ? $default : $_REQUEST[$name]) : $default;
}

/**
 * 获取多个传递参数
 *
 * @return array
 */
function _rFrom() {
    $result = array();
    $params = func_get_args();

    foreach ($params as $param) {
        $result[$param] = isset($_REQUEST[$param]) ?
            (is_array($_REQUEST[$param]) ? NULL : $_REQUEST[$param]) : NULL;
    }

    return $result;
}

/**
 * 输出传递参数
 *
 * @param string $name 参数名称
 * @param string $default 默认值
 * @return string
 */
function _v($name, $default = '') {
    echo _r($name, $default);
}

/**
 * 判断是否兼容某个环境(perform)
 *
 * @param string $adapter 适配器
 * @return boolean
 */
function _p($adapter) {
    switch ($adapter) {
        case 'Mysql':
            return Typecho_Db_Adapter_Mysql::isAvailable();
        case 'Mysqli':
            return Typecho_Db_Adapter_Mysqli::isAvailable();
        case 'Pdo_Mysql':
            return Typecho_Db_Adapter_Pdo_Mysql::isAvailable();
        case 'SQLite':
            return Typecho_Db_Adapter_SQLite::isAvailable();
        case 'Pdo_SQLite':
            return Typecho_Db_Adapter_Pdo_SQLite::isAvailable();
        case 'Pgsql':
            return Typecho_Db_Adapter_Pgsql::isAvailable();
        case 'Pdo_Pgsql':
            return Typecho_Db_Adapter_Pdo_Pgsql::isAvailable();
        default:
            return false;
    }
}

/**
 * 获取url地址
 *
 * @return string
 */
function _u() {
    $url = Typecho_Request::getUrlPrefix() . $_SERVER['REQUEST_URI'];
    if (isset($_SERVER['QUERY_STRING'])) {
        $url = str_replace('?' . $_SERVER['QUERY_STRING'], '', $url);
    }

    return dirname($url);
}

$options = new stdClass();
$options->generator = 'Typecho ' . Typecho_Common::VERSION;
list($soft, $currentVersion) = explode(' ', $options->generator);

$options->software = $soft;
$options->version = $currentVersion;

list($prefixVersion, $suffixVersion) = explode('/', $currentVersion);

/** 获取语言 */
$lang = _r('lang', Typecho_Cookie::get('__typecho_lang'));
$langs = Widget_Options_General::getLangs();

if (empty($lang) && count($langs) > 1) {
    foreach ($langs as $lang) {
        if ('zh_CN' != $lang) {
            break;
        }
    }
}

if (empty($lang)) {
    $lang = 'zh_CN';
}

if ('zh_CN' != $lang) {
    $dir = defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
    Typecho_I18n::setLang($dir . '/' . $lang . '.mo');
}

Typecho_Cookie::set('__typecho_lang', $lang);

?><!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head lang="zh-CN">
    <meta charset="<?php _e('UTF-8'); ?>" />
	<title><?php _e('Typecho 安装程序'); ?></title>
    <link rel="stylesheet" type="text/css" href="admin/css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="admin/css/grid.css" />
    <link rel="stylesheet" type="text/css" href="admin/css/style.css" />
</head>
<body>
<div class="typecho-install-patch">
    <h1>Typecho</h1>
    <ol class="path">
        <li<?php if (!isset($_GET['finish']) && !isset($_GET['config'])) : ?> class="current"<?php endif; ?>><span>1</span><?php _e('欢迎使用'); ?></li>
        <li<?php if (isset($_GET['config'])) : ?> class="current"<?php endif; ?>><span>2</span><?php _e('初始化配置'); ?></li>
        <li<?php if (isset($_GET['start'])) : ?> class="current"<?php endif; ?>><span>3</span><?php _e('开始安装'); ?></li>
        <li<?php if (isset($_GET['finish'])) : ?> class="current"<?php endif; ?>><span>4</span><?php _e('安装成功'); ?></li>
    </ol>
</div>
<div class="container">
    <div class="row">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="column-14 start-06 typecho-install">
            <?php if (isset($_GET['finish'])) : ?>
                <?php if (!isset($db)) : ?>
                <h1 class="typecho-install-title"><?php _e('安装失败!'); ?></h1>
                <div class="typecho-install-body">
                    <form method="post" action="?config" name="config">
                    <p class="message error"><?php _e('您没有上传 config.inc.php 文件，请您重新安装！'); ?> <button class="btn primary" type="submit"><?php _e('重新安装 &raquo;'); ?></button></p>
                    </form>
                </div>
                <?php elseif (!Typecho_Cookie::get('__typecho_config')): ?>
                    <h1 class="typecho-install-title"><?php _e('没有安装!'); ?></h1>
                    <div class="typecho-install-body">
                        <form method="post" action="?config" name="config">
                            <p class="message error"><?php _e('您没有执行安装步骤，请您重新安装！'); ?> <button class="btn primary" type="submit"><?php _e('重新安装 &raquo;'); ?></button></p>
                        </form>
                    </div>
                <?php else : ?>
                    <?php
                    $db->query($db->update('table.options')->rows(['value' => 1])->where('name = ?', 'installed'));
                    ?>
                <h1 class="typecho-install-title"><?php _e('安装成功!'); ?></h1>
                <div class="typecho-install-body">
                    <div class="message success">
                    <?php if(isset($_GET['use_old']) ) : ?>
                    <?php _e('您选择了使用原有的数据, 您的用户名和密码和原来的一致'); ?>
                    <?php else : ?>
                        <?php if (isset($_REQUEST['user']) && isset($_REQUEST['password'])): ?>
                            <?php _e('您的用户名是'); ?>: <strong class="mono"><?php echo htmlspecialchars(_r('user')); ?></strong><br>
                            <?php _e('您的密码是'); ?>: <strong class="mono"><?php echo htmlspecialchars(_r('password')); ?></strong>
                        <?php endif;?>
                    <?php endif;?>
                    </div>

                    <div class="p message notice">
                    <a target="_blank" href="http://spreadsheets.google.com/viewform?key=pd1Gl4Ur_pbniqgebs5JRIg&hl=en">参与用户调查, 帮助我们完善产品</a>
                    </div>

                    <div class="session">
                    <p><?php _e('您可以将下面两个链接保存到您的收藏夹'); ?>:</p>
                    <ul>
                    <?php
                        if (isset($_REQUEST['user']) && isset($_REQUEST['password'])) {
                            $loginUrl = _u() . '/index.php/action/login?name=' . urlencode(_r('user')) . '&password='
                            . urlencode(_r('password')) . '&referer=' . _u() . '/admin/index.php';
                            $loginUrl = Typecho_Widget::widget('Widget_Security')->getTokenUrl($loginUrl);
                        } else {
                            $loginUrl = _u() . '/admin/index.php';
                        }
                    ?>
                        <li><a href="<?php echo $loginUrl; ?>"><?php _e('点击这里访问您的控制面板'); ?></a></li>
                        <li><a href="<?php echo _u(); ?>/index.php"><?php _e('点击这里查看您的 Blog'); ?></a></li>
                    </ul>
                    </div>

                    <p><?php _e('希望您能尽情享用 Typecho 带来的乐趣!'); ?></p>
                </div>
                <?php endif;?>
            <?php elseif (isset($_GET['start'])): ?>
                <?php if (!isset($db)) : ?>
                <h1 class="typecho-install-title"><?php _e('安装失败!'); ?></h1>
                <div class="typecho-install-body">
                    <form method="post" action="?config" name="config">
                    <p class="message error"><?php _e('您没有上传 config.inc.php 文件，请您重新安装！'); ?> <button class="btn primary" type="submit"><?php _e('重新安装 &raquo;'); ?></button></p>
                    </form>
                </div>
                <?php else : ?>
            <?php
                                    $config = unserialize(base64_decode(Typecho_Cookie::get('__typecho_config')));
                                    $type = explode('_', $config['adapter']);
                                    $type = array_pop($type);
                                    $type = $type == 'Mysqli' ? 'Mysql' : $type;
                                    $installDb = $db;

                                    try {
                                        /** 初始化数据库结构 */
                                        $scripts = file_get_contents ('./install/' . $type . '.sql');
                                        $scripts = str_replace('typecho_', $config['prefix'], $scripts);

                                        if (isset($config['charset'])) {
                                            $scripts = str_replace('%charset%', $config['charset'], $scripts);
                                        }

                                        $scripts = explode(';', $scripts);
                                        foreach ($scripts as $script) {
                                            $script = trim($script);
                                            if ($script) {
                                                $installDb->query($script, Typecho_Db::WRITE);
                                            }
                                        }

                                        /** 全局变量 */
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'theme', 'user' => 0, 'value' => 'default')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'theme:default', 'user' => 0, 'value' => 'a:2:{s:7:"logoUrl";N;s:12:"sidebarBlock";a:5:{i:0;s:15:"ShowRecentPosts";i:1;s:18:"ShowRecentComments";i:2;s:12:"ShowCategory";i:3;s:11:"ShowArchive";i:4;s:9:"ShowOther";}}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'timezone', 'user' => 0, 'value' => _t('28800'))));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'lang', 'user' => 0, 'value' => $lang)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'charset', 'user' => 0, 'value' => _t('UTF-8'))));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'contentType', 'user' => 0, 'value' => 'text/html')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'gzip', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'generator', 'user' => 0, 'value' => $options->generator)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'title', 'user' => 0, 'value' => 'Hello World')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'description', 'user' => 0, 'value' => 'Just So So ...')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'keywords', 'user' => 0, 'value' => 'typecho,php,blog')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'rewrite', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'frontPage', 'user' => 0, 'value' => 'recent')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'frontArchive', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsRequireMail', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsWhitelist', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsRequireURL', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsRequireModeration', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'plugins', 'user' => 0, 'value' => 'a:0:{}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentDateFormat', 'user' => 0, 'value' => 'F jS, Y \a\t h:i a')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'siteUrl', 'user' => 0, 'value' => $config['siteUrl'])));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultCategory', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'allowRegister', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultAllowComment', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultAllowPing', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultAllowFeed', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'pageSize', 'user' => 0, 'value' => 5)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'postsListSize', 'user' => 0, 'value' => 10)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsListSize', 'user' => 0, 'value' => 10)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsHTMLTagAllowed', 'user' => 0, 'value' => NULL)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'postDateFormat', 'user' => 0, 'value' => 'Y-m-d')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'feedFullText', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'editorSize', 'user' => 0, 'value' => 350)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'autoSave', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'markdown', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'xmlrpcMarkdown', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsMaxNestingLevels', 'user' => 0, 'value' => 5)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPostTimeout', 'user' => 0, 'value' => 24 * 3600 * 30)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsUrlNofollow', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsShowUrl', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsMarkdown', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPageBreak', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsThreaded', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPageSize', 'user' => 0, 'value' => 20)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPageDisplay', 'user' => 0, 'value' => 'last')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsOrder', 'user' => 0, 'value' => 'ASC')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsCheckReferer', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAutoClose', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPostIntervalEnable', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPostInterval', 'user' => 0, 'value' => 60)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsShowCommentOnly', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAvatar', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAvatarRating', 'user' => 0, 'value' => 'G')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAntiSpam', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'routingTable', 'user' => 0, 'value' => 'a:25:{s:5:"index";a:3:{s:3:"url";s:1:"/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:7:"archive";a:3:{s:3:"url";s:6:"/blog/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:2:"do";a:3:{s:3:"url";s:22:"/action/[action:alpha]";s:6:"widget";s:9:"Widget_Do";s:6:"action";s:6:"action";}s:4:"post";a:3:{s:3:"url";s:24:"/archives/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"attachment";a:3:{s:3:"url";s:26:"/attachment/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"category";a:3:{s:3:"url";s:17:"/category/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:3:"tag";a:3:{s:3:"url";s:12:"/tag/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"author";a:3:{s:3:"url";s:22:"/author/[uid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"search";a:3:{s:3:"url";s:19:"/search/[keywords]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"index_page";a:3:{s:3:"url";s:21:"/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_page";a:3:{s:3:"url";s:26:"/blog/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"category_page";a:3:{s:3:"url";s:32:"/category/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"tag_page";a:3:{s:3:"url";s:27:"/tag/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"author_page";a:3:{s:3:"url";s:37:"/author/[uid:digital]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"search_page";a:3:{s:3:"url";s:34:"/search/[keywords]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_year";a:3:{s:3:"url";s:18:"/[year:digital:4]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"archive_month";a:3:{s:3:"url";s:36:"/[year:digital:4]/[month:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"archive_day";a:3:{s:3:"url";s:52:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:17:"archive_year_page";a:3:{s:3:"url";s:38:"/[year:digital:4]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:18:"archive_month_page";a:3:{s:3:"url";s:56:"/[year:digital:4]/[month:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:16:"archive_day_page";a:3:{s:3:"url";s:72:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"comment_page";a:3:{s:3:"url";s:53:"[permalink:string]/comment-page-[commentPage:digital]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:4:"feed";a:3:{s:3:"url";s:20:"/feed[feed:string:0]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:4:"feed";}s:8:"feedback";a:3:{s:3:"url";s:31:"[permalink:string]/[type:alpha]";s:6:"widget";s:15:"Widget_Feedback";s:6:"action";s:6:"action";}s:4:"page";a:3:{s:3:"url";s:12:"/[slug].html";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'actionTable', 'user' => 0, 'value' => 'a:0:{}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'panelTable', 'user' => 0, 'value' => 'a:0:{}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'attachmentTypes', 'user' => 0, 'value' => '@image@')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'secret', 'user' => 0, 'value' => Typecho_Common::randString(32, true))));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'installed', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'allowXmlRpc', 'user' => 0, 'value' => 2)));

                                        /** 初始分类 */
                                        $installDb->query($installDb->insert('table.metas')->rows(array('name' => _t('默认分类'), 'slug' => 'default', 'type' => 'category', 'description' => _t('只是一个默认分类'),
                                        'count' => 1, 'order' => 1)));

                                        /** 初始关系 */
                                        $installDb->query($installDb->insert('table.relationships')->rows(array('cid' => 1, 'mid' => 1)));

                                        /** 初始内容 */
                                        $installDb->query($installDb->insert('table.contents')->rows(array('title' => _t('欢迎使用 Typecho'), 'slug' => 'start', 'created' => Typecho_Date::time(), 'modified' => Typecho_Date::time(),
                                        'text' => '<!--markdown-->' . _t('如果您看到这篇文章,表示您的 blog 已经安装成功.'), 'authorId' => 1, 'type' => 'post', 'status' => 'publish', 'commentsNum' => 1, 'allowComment' => 1,
                                        'allowPing' => 1, 'allowFeed' => 1, 'parent' => 0)));

                                        $installDb->query($installDb->insert('table.contents')->rows(array('title' => _t('关于'), 'slug' => 'start-page', 'created' => Typecho_Date::time(), 'modified' => Typecho_Date::time(),
                                        'text' => '<!--markdown-->' . _t('本页面由 Typecho 创建, 这只是个测试页面.'), 'authorId' => 1, 'order' => 0, 'type' => 'page', 'status' => 'publish', 'commentsNum' => 0, 'allowComment' => 1,
                                        'allowPing' => 1, 'allowFeed' => 1, 'parent' => 0)));

                                        /** 初始评论 */
                                        $installDb->query($installDb->insert('table.comments')->rows(array('cid' => 1, 'created' => Typecho_Date::time(), 'author' => 'Typecho', 'ownerId' => 1, 'url' => 'http://typecho.org',
                                        'ip' => '127.0.0.1', 'agent' => $options->generator, 'text' => '欢迎加入 Typecho 大家族', 'type' => 'comment', 'status' => 'approved', 'parent' => 0)));

                                        /** 初始用户 */
                                        $password = empty($config['userPassword']) ? substr(uniqid(), 7) : $config['userPassword'];
                                        $hasher = new PasswordHash(8, true);

                                        $installDb->query($installDb->insert('table.users')->rows(array('name' => $config['userName'], 'password' => $hasher->HashPassword($password), 'mail' => $config['userMail'],
                                        'url' => 'http://www.typecho.org', 'screenName' => $config['userName'], 'group' => 'administrator', 'created' => Typecho_Date::time())));

                                        unset($_SESSION['typecho']);
                                        header('Location: ./install.php?finish&user=' . urlencode($config['userName'])
                                            . '&password=' . urlencode($password));
                                    } catch (Typecho_Db_Exception $e) {
                                        $success = false;
                                        $code = $e->getCode();
?>
<h1 class="typecho-install-title"><?php _e('安装失败!'); ?></h1>
                <div class="typecho-install-body">
                    <form method="post" action="?start" name="check">
<?php
                                        if(('Mysql' == $type && (1050 == $code || '42S01' == $code)) ||
                                        ('SQLite' == $type && ('HY000' == $code || 1 == $code)) ||
                                        ('Pgsql' == $type && '42P07' == $code)) {
                                            if(_r('delete')) {
                                                //删除原有数据
                                                $dbPrefix = $config['prefix'];
                                                $tableArray = array($dbPrefix . 'comments', $dbPrefix . 'contents', $dbPrefix . 'fields', $dbPrefix . 'metas', $dbPrefix . 'options', $dbPrefix . 'relationships', $dbPrefix . 'users',);
                                                foreach($tableArray as $table) {
                                                    if($type == 'Mysql') {
                                                        $installDb->query("DROP TABLE IF EXISTS `{$table}`");
                                                    } elseif($type == 'Pgsql') {
                                                        $installDb->query("DROP TABLE {$table}");
                                                    } elseif($type == 'SQLite') {
                                                        $installDb->query("DROP TABLE {$table}");
                                                    }
                                                }
                                                echo '<p class="message success">' . _t('已经删除完原有数据') . '<br /><br /><button class="btn primary" type="submit" class="primary">'
                                                    . _t('继续安装 &raquo;') . '</button></p>';
                                            } elseif (_r('goahead')) {
                                                //使用原有数据
                                                //但是要更新用户网站
                                                $installDb->query($installDb->update('table.options')->rows(array('value' => $config['siteUrl']))->where('name = ?', 'siteUrl'));
                                                unset($_SESSION['typecho']);
                                                header('Location: ./install.php?finish&use_old');
                                                exit;
                                            } else {
                                                 echo '<p class="message error">' . _t('安装程序检查到原有数据表已经存在.')
                                                    . '<br /><br />' . '<button type="submit" name="delete" value="1" class="btn btn-warn">' . _t('删除原有数据') . '</button> '
                                                    . _t('或者') . ' <button type="submit" name="goahead" value="1" class="btn primary">' . _t('使用原有数据') . '</button></p>';
                                            }
                                        } else {
                                            echo '<p class="message error">' . _t('安装程序捕捉到以下错误: "%s". 程序被终止, 请检查您的配置信息.',$e->getMessage()) . '</p>';
                                        }
                                        ?>
                    </form>
                </div>
                                        <?php
                                    }
            ?>
                <?php endif;?>
            <?php elseif (isset($_GET['config'])): ?>
            <?php
                    $adapters = array('Mysql', 'Mysqli', 'Pdo_Mysql', 'SQLite', 'Pdo_SQLite', 'Pgsql', 'Pdo_Pgsql');
                    foreach ($adapters as $firstAdapter) {
                        if (_p($firstAdapter)) {
                            break;
                        }
                    }
                    $adapter = _r('dbAdapter', $firstAdapter);
                    $parts = explode('_', $adapter);

                    $type = $adapter == 'Mysqli' ? 'Mysql' : array_pop($parts);
            ?>
                <form method="post" action="?config" name="config">
                    <h1 class="typecho-install-title"><?php _e('确认您的配置'); ?></h1>
                    <div class="typecho-install-body">
                        <h2><?php _e('数据库配置'); ?></h2>
                        <?php
                            if ('config' == _r('action')) {
                                $success = true;

                                if (_r('created') && !file_exists('./config.inc.php')) {
                                    echo '<p class="message error">' . _t('没有检测到您手动创建的配置文件, 请检查后再次创建') . '</p>';
                                    $success = false;
                                } else {
                                    if (NULL == _r('userUrl')) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('请填写您的网站地址') . '</p>';
                                    } else if (NULL == _r('userName')) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('请填写您的用户名') . '</p>';
                                    } else if (NULL == _r('userMail')) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('请填写您的邮箱地址') . '</p>';
                                    } else if (32 < strlen(_r('userName'))) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('用户名长度超过限制, 请不要超过 32 个字符') . '</p>';
                                    } else if (200 < strlen(_r('userMail'))) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('邮箱长度超过限制, 请不要超过 200 个字符') . '</p>';
                                    }
                                }

                                $_dbConfig = _rFrom('dbHost', 'dbUser', 'dbPassword', 'dbCharset', 'dbPort', 'dbDatabase', 'dbFile', 'dbDsn');

                                $_dbConfig = array_filter($_dbConfig);
                                $dbConfig = array();
                                foreach ($_dbConfig as $key => $val) {
                                    $dbConfig[strtolower (substr($key, 2))] = $val;
                                }

                                // 在特殊服务器上的特殊安装过程处理
                                if (_r('config')) {
                                    $replace = array_keys($dbConfig);
                                    foreach ($replace as &$key) {
                                        $key = '{' . $key . '}';
                                    }

                                    if (!empty($_dbConfig['dbDsn'])) {
                                        $dbConfig['dsn'] = str_replace($replace, array_values($dbConfig), $dbConfig['dsn']);
                                    }
                                    $config = str_replace($replace, array_values($dbConfig), _r('config'));
                                }

                                if (!isset($config) && $success && !_r('created')) {
                                    $installDb = new Typecho_Db($adapter, _r('dbPrefix'));
                                    $installDb->addServer($dbConfig, Typecho_Db::READ | Typecho_Db::WRITE);


                                    /** 检测数据库配置 */
                                    try {
                                        $installDb->query('SELECT 1=1');
                                    } catch (Typecho_Db_Adapter_Exception $e) {
                                        $success = false;
                                        echo '<p class="message error">'
                                        . _t('对不起，无法连接数据库，请先检查数据库配置再继续进行安装') . '</p>';
                                    } catch (Typecho_Db_Exception $e) {
                                        $success = false;
                                        echo '<p class="message error">'
                                        . _t('安装程序捕捉到以下错误: " %s ". 程序被终止, 请检查您的配置信息.',$e->getMessage()) . '</p>';
                                    }
                                }

                                if($success) {
                                    // 重置原有数据库状态
                                    if (isset($installDb)) {
                                        try {
                                            $installDb->query($installDb->update('table.options')
                                                ->rows(array('value' => 0))->where('name = ?', 'installed'));
                                        } catch (Exception $e) {
                                            // do nothing
                                        }
                                    }

                                    Typecho_Cookie::set('__typecho_config', base64_encode(serialize(array_merge(array(
                                        'prefix'    =>  _r('dbPrefix'),
                                        'userName'  =>  _r('userName'),
                                        'userPassword'  =>  _r('userPassword'),
                                        'userMail'  =>  _r('userMail'),
                                        'adapter'   =>  $adapter,
                                        'siteUrl'   =>  _r('userUrl')
                                    ), $dbConfig))));

                                    if (_r('created')) {
                                        header('Location: ./install.php?start');
                                        exit;
                                    }

                                    /** 初始化配置文件 */
                                    $lines = array_slice(file(__FILE__), 1, 52);
                                    $lines[] = "
/** 定义数据库参数 */
\$db = new Typecho_Db('{$adapter}', '" . _r('dbPrefix') . "');
\$db->addServer(" . (empty($config) ? var_export($dbConfig, true) : $config) . ", Typecho_Db::READ | Typecho_Db::WRITE);
Typecho_Db::set(\$db);
";
                                    $contents = implode('', $lines);
                                    if (!Typecho_Common::isAppEngine()) {
                                        @file_put_contents('./config.inc.php', $contents);
                                    }

                                    if (!file_exists('./config.inc.php')) {
                                    ?>
<div class="message notice"><p><?php _e('安装程序无法自动创建 <strong>config.inc.php</strong> 文件'); ?><br />
<?php _e('您可以在网站根目录下手动创建 <strong>config.inc.php</strong> 文件, 并复制如下代码至其中'); ?></p>
<p><textarea rows="5" onmouseover="this.select();" class="w-100 mono" readonly><?php echo htmlspecialchars($contents); ?></textarea></p>
<p><button name="created" value="1" type="submit" class="btn primary">创建完毕, 继续安装 &raquo;</button></p></div>
                                    <?php
                                    } else {
                                        header('Location: ./install.php?start');
                                        exit;
                                    }
                                }

                                // 安装不成功删除配置文件
                                if($success != true && file_exists(__TYPECHO_ROOT_DIR__ . '/config.inc.php')) {
                                    @unlink(__TYPECHO_ROOT_DIR__ . '/config.inc.php');
                                }
                            }
                        ?>
                        <ul class="typecho-option">
                            <li>
                            <label for="dbAdapter" class="typecho-label"><?php _e('数据库适配器'); ?></label>
                            <select name="dbAdapter" id="dbAdapter">
                                <?php if (_p('Mysql')): ?><option value="Mysql"<?php if('Mysql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Mysql 原生函数适配器') ?></option><?php endif; ?>
                                <?php if (_p('SQLite')): ?><option value="SQLite"<?php if('SQLite' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('SQLite 原生函数适配器 (SQLite 2.x)') ?></option><?php endif; ?>
                                <?php if (_p('Pgsql')): ?><option value="Pgsql"<?php if('Pgsql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pgsql 原生函数适配器') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_Mysql')): ?><option value="Pdo_Mysql"<?php if('Pdo_Mysql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pdo 驱动 Mysql 适配器') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_SQLite')): ?><option value="Pdo_SQLite"<?php if('Pdo_SQLite' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pdo 驱动 SQLite 适配器 (SQLite 3.x)') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_Pgsql')): ?><option value="Pdo_Pgsql"<?php if('Pdo_Pgsql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pdo 驱动 PostgreSql 适配器') ?></option><?php endif; ?>
                            </select>
                            <p class="description"><?php _e('请根据您的数据库类型选择合适的适配器'); ?></p>
                            </li>
                            <?php require_once './install/' . $type . '.php'; ?>
                            <li>
                            <label class="typecho-label" for="dbPrefix"><?php _e('数据库前缀'); ?></label>
                            <input type="text" class="text" name="dbPrefix" id="dbPrefix" value="<?php _v('dbPrefix', 'typecho_'); ?>" />
                            <p class="description"><?php _e('默认前缀是 "typecho_"'); ?></p>
                            </li>
                        </ul>

                        <script>
                        var _select = document.config.dbAdapter;
                        _select.onchange = function() {
                            setTimeout("window.location.href = 'install.php?config&dbAdapter=" + this.value + "'; ",0);
                        }
                        </script>

                        <h2><?php _e('创建您的管理员帐号'); ?></h2>
                        <ul class="typecho-option">
                            <li>
                            <label class="typecho-label" for="userUrl"><?php _e('网站地址'); ?></label>
                            <input type="text" name="userUrl" id="userUrl" class="text" value="<?php _v('userUrl', _u()); ?>" />
                            <p class="description"><?php _e('这是程序自动匹配的网站路径, 如果不正确请修改它'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label" for="userName"><?php _e('用户名'); ?></label>
                            <input type="text" name="userName" id="userName" class="text" value="<?php _v('userName', 'admin'); ?>" />
                            <p class="description"><?php _e('请填写您的用户名'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label" for="userPassword"><?php _e('登录密码'); ?></label>
                            <input type="password" name="userPassword" id="userPassword" class="text" value="<?php _v('userPassword'); ?>" />
                            <p class="description"><?php _e('请填写您的登录密码, 如果留空系统将为您随机生成一个'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label" for="userMail"><?php _e('邮件地址'); ?></label>
                            <input type="text" name="userMail" id="userMail" class="text" value="<?php _v('userMail', 'webmaster@yourdomain.com'); ?>" />
                            <p class="description"><?php _e('请填写一个您的常用邮箱'); ?></p>
                            </li>
                        </ul>
                    </div>
                    <input type="hidden" name="action" value="config" />
                    <p class="submit"><button type="submit" class="btn primary"><?php _e('确认, 开始安装 &raquo;'); ?></button></p>
                </form>
            <?php  else: ?>
                <form method="post" action="?config">
                <h1 class="typecho-install-title"><?php _e('欢迎使用 Typecho'); ?></h1>
                <div class="typecho-install-body">
                <h2><?php _e('安装说明'); ?></h2>
                <p><strong><?php _e('本安装程序将自动检测服务器环境是否符合最低配置需求. 如果不符合, 将在上方出现提示信息, 请按照提示信息检查您的主机配置. 如果服务器环境符合要求, 将在下方出现 "开始下一步" 的按钮, 点击此按钮即可一步完成安装.'); ?></strong></p>
                <h2><?php _e('许可及协议'); ?></h2>
                <p><?php _e('Typecho 基于 <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a> 协议发布, 我们允许用户在 GPL 协议许可的范围内使用, 拷贝, 修改和分发此程序.'); ?>
                <?php _e('在GPL许可的范围内，您可以自由地将其用于商业以及非商业用途.'); ?></p>
                <p><?php _e('Typecho 软件由其社区提供支持, 核心开发团队负责维护程序日常开发工作以及新特性的制定.'); ?>
                <?php _e('如果您遇到使用上的问题, 程序中的 BUG, 以及期许的新功能, 欢迎您在社区中交流或者直接向我们贡献代码.'); ?>
                <?php _e('对于贡献突出者, 他的名字将出现在贡献者名单中.'); ?></p>
                </div>
                <p class="submit">
                    <button type="submit" class="btn primary"><?php _e('我准备好了, 开始下一步 &raquo;'); ?></button>

                    <?php if (count($langs) > 1): ?>
                    <select style="float: right" onchange="window.location.href='install.php?lang=' + this.value">
                        <?php foreach ($langs as $key => $val): ?>
                        <option value="<?php echo $key; ?>"<?php if ($lang == $key): ?> selected<?php endif; ?>><?php echo $val; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </p>
                </form>
            <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<?php
include 'admin/copyright.php';
include 'admin/footer.php';
?>
