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

ob_start();

//判断是否已经安装
if (!isset($_GET['finish']) && file_exists(__TYPECHO_ROOT_DIR__ . '/config.inc.php')) {
    exit;
}

/**
 * 获取传递参数
 *
 * @param string $name 参数名称
 * @param string $default 默认值
 * @return string
 */
function _r($name, $default = NULL)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}

/**
 * 获取多个传递参数
 * 
 * @return array
 */
function _rFrom()
{
    $result = array();
    $params = func_get_args();
    
    foreach ($params as $param) {
        $result[$param] = isset($_REQUEST[$param]) ? $_REQUEST[$param] : NULL;
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
function _v($name, $default = '')
{
    echo _r($name, $default);
}

/**
 * 判断是否兼容某个环境(perform)
 *
 * @param string $adapter 适配器
 * @return boolean
 */
function _p($adapter)
{
    switch ($adapter) {
        case 'Mysql':
            return Typecho_Db_Adapter_Mysql::isAvailable();
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
function _u()
{
    $url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    if (isset($_SERVER["QUERY_STRING"])) {
        $url = str_replace("?" . $_SERVER["QUERY_STRING"], "", $url);
    }

    return dirname($url);
}

$options = new stdClass();
$options->generator = 'Typecho ' . Typecho_Common::VERSION;
list($soft, $currentVersion) = explode(' ', $options->generator);

$options->software = $soft;
$options->version = $currentVersion;

list($prefixVersion, $suffixVersion) = explode('/', $currentVersion);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php _e('UTF-8'); ?>" />
	<title><?php _e('Typecho安装程序'); ?></title>
    <link rel="stylesheet" type="text/css" href="admin/css/reset.css" />
    <link rel="stylesheet" type="text/css" href="admin/css/grid.css" />
    <link rel="stylesheet" type="text/css" href="admin/css/style.css" />
</head>
<body>
<div class="typecho-install-patch">
    <ol class="path">
        <li<?php if (!isset($_GET['finish']) && !isset($_GET['config'])) : ?> class="current"<?php endif; ?>><?php _e('欢迎使用'); ?></li>
        <li<?php if (isset($_GET['config'])) : ?> class="current"<?php endif; ?>><?php _e('初始化您的配置'); ?></li>
        <li<?php if (isset($_GET['finish'])) : ?> class="current"<?php endif; ?>><?php _e('安装成功'); ?></li>
    </ol>
</div>
<div class="main">
    <div class="body body-950">
        <div class="container">
            <div class="column-14 start-06 typecho-install">
            <?php if (isset($_GET['finish'])) : ?>
                <?php if (!@file_exists(__TYPECHO_ROOT_DIR__ . '/config.inc.php')) : ?>
                <h1 class="typecho-install-title"><?php _e('安装失败!'); ?></h1>
                <div class="typecho-install-body">
                    <form method="post" action="?config" name="config">
                    <p class="message error"><?php _e('您没有上传 config.inc.php 文件，请您重新安装！'); ?> <button type="submit"><?php _e('重新安装 &raquo;'); ?></button></p>
                    </form>
                </div>
                <?php else : ?>
                <h1 class="typecho-install-title"><?php _e('安装成功!'); ?></h1>
                <div class="typecho-install-body">
                    <div class="message success">
                    <?php if(isset($_GET['use_old']) ) : ?>
                    <?php _e('您选择了使用原有的数据, 您的用户名和密码和原来的一致'); ?>
                    <?php else : ?>
                        <ul>
                        <?php if (isset($_REQUEST['user']) && isset($_REQUEST['password'])): ?>
                            <li><?php _e('您的用户名是'); ?>:<strong><?php echo htmlspecialchars(_r('user')); ?></strong></li>
                            <li><?php _e('您的密码是'); ?>:<strong><?php echo htmlspecialchars(_r('password')); ?></strong></li>
                        <?php endif;?>
                    </ul>
                    <?php endif;?>
                    </div>

                    <div class="message notice">
                    <a target="_blank" href="http://spreadsheets.google.com/viewform?key=pd1Gl4Ur_pbniqgebs5JRIg&hl=en">参与用户调查, 帮助我们完善产品</a>
                    </div>

                    <div class="session">
                    <p><?php _e('您可以将下面两个链接保存到您的收藏夹'); ?>:</p>
                    <ul>
                    <?php
                        if (isset($_REQUEST['user']) && isset($_REQUEST['password'])) {
                            $loginUrl = _u() . '/index.php/action/login?name=' . urlencode(_r('user')) . '&password=' 
                            . urlencode(_r('password')) . '&referer=' . _u() . '/admin/index.php';
                        } else {
                            $loginUrl = _u() . '/admin/index.php';
                        }
                    ?>
                        <li><a href="<?php echo $loginUrl; ?>"><?php _e('点击这里访问您的控制面板'); ?></a></li>
                        <li><a href="<?php echo _u(); ?>/index.php"><?php _e('点击这里查看您的 Blog'); ?></a></li>
                    </ul>
                    </div>

                    <p><?php _e('希望你能尽情享用 Typecho 带来的乐趣!'); ?></p>
                </div>
                <?php endif;?>
            <?php  elseif (isset($_GET['config'])) : ?>
            <?php
                    $adapter = _r('dbAdapter', 'Mysql');
                    $type = explode('_', $adapter);
                    $type = array_pop($type);
            ?>
                <form method="post" action="?config" name="config">
                    <h1 class="typecho-install-title"><?php _e('确认您的配置'); ?></h1>
                    <div class="typecho-install-body">
                        <h2><?php _e('数据库配置'); ?></h2>
                        <?php
                            if ('config' == _r('action')) {
                                $success = true;
                                
                                if (_r('created')) {
                                    if (file_exists('./config.inc.php')) {
                                        require_once './config.inc.php';
                                        $installDb = Typecho_Db::get();
                                    } else {
                                        echo '<p class="message error">' . _t('没有检测到您手动创建的配置文件, 请检查后再次创建') . '</p>';
                                        $success = false;
                                    }
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
                                        echo '<p class="message error">' . _t('用户名长度超过限制, 请不要超过32个字符') . '</p>';
                                    } else if (200 < strlen(_r('userMail'))) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('邮箱长度超过限制, 请不要超过200个字符') . '</p>';
                                    }
                                }


                                if ($success) {
                                    if (!isset($installDb)) {
                                        $installDb = new Typecho_Db ($adapter, _r('dbPrefix'));
                                        $_dbConfig = _rFrom('dbHost', 'dbUser', 'dbPassword', 'dbCharset', 'dbPort', 'dbDatabase', 'dbFile', 'dbDsn');

                                        $_dbConfig = array_filter($_dbConfig);
                                        $dbConfig = array();
                                        foreach ($_dbConfig as $key => $val) {
                                            $dbConfig[strtolower (substr($key, 2))] = $val;
                                        }

                                        $installDb->addServer($dbConfig, Typecho_Db::READ | Typecho_Db::WRITE);
                                    }


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
                                        . _t('安装程序捕捉到以下错误: "%s". 程序被终止, 请检查您的配置信息.',$e->getMessage()) . '</p>';
                                    }

                                }

                                if($success) {
                                    if (_r('created')) {
                                        header('Location: ./install.php?finish');
                                        exit;
                                    }

                                    /** 初始化配置文件 */
                                    $lines = array_slice(file(__FILE__), 0, 52);
                                    $lines[] = "
/** 定义数据库参数 */
\$db = new Typecho_Db('{$adapter}', '" . _r('dbPrefix') . "');
\$db->addServer(" . var_export($dbConfig, true) . ", Typecho_Db::READ | Typecho_Db::WRITE);
Typecho_Db::set(\$db);
";
                                    $contents = implode('', $lines);

                                    if (true !== @file_put_contents('./config.inc.php', $contents)) {
                                    ?>
<p class="message notice"><?php _e('安装程序无法自动创建 config.inc.php 文件'); ?><br /><?php _e('您可以在网站根目录下手动创建 config.inc.php 文件, 并复制如下代码至其中'); ?><br/><br />
<textarea style="width: 520px; height: 100px" onmouseover="this.select();" readonly><?php echo htmlspecialchars($contents); ?></textarea><br /><br />
<button name="created" value="1" type="submit">创建完毕, 继续安装 &raquo;</button></p>
                                    <?php
                                    }
                                }

                                // 安装不成功删除配置文件
                                if($success != true && file_exists(__TYPECHO_ROOT_DIR__ . '/config.inc.php')) {
                                    unlink(__TYPECHO_ROOT_DIR__ . '/config.inc.php');
                                }
                            }
                        ?>
                        <ul class="typecho-option">
                            <li>
                            <label class="typecho-label"><?php _e('数据库适配器'); ?></label>
                            <select name="dbAdapter">
                                <?php if (_p('Mysql')): ?><option value="Mysql"<?php if('Mysql' == $adapter): ?> selected=true<?php endif; ?>><?php _e('Mysql原生函数适配器') ?></option><?php endif; ?>
                                <?php if (_p('SQLite')): ?><option value="SQLite"<?php if('SQLite' == $adapter): ?> selected=true<?php endif; ?>><?php _e('SQLite原生函数适配器(SQLite 2.x)') ?></option><?php endif; ?>
                                <?php if (_p('Pgsql')): ?><option value="Pgsql"<?php if('Pgsql' == $adapter): ?> selected=true<?php endif; ?>><?php _e('Pgsql原生函数适配器') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_Mysql')): ?><option value="Pdo_Mysql"<?php if('Pdo_Mysql' == $adapter): ?> selected=true<?php endif; ?>><?php _e('Pdo驱动Mysql适配器') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_SQLite')): ?><option value="Pdo_SQLite"<?php if('Pdo_SQLite' == $adapter): ?> selected=true<?php endif; ?>><?php _e('Pdo驱动SQLite适配器(SQLite 3.x)') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_Pgsql')): ?><option value="Pdo_Pgsql"<?php if('Pdo_Pgsql' == $adapter): ?> selected=true<?php endif; ?>><?php _e('Pdo驱动PostgreSql适配器') ?></option><?php endif; ?>
                            </select>
                            <p class="description"><?php _e('请根据你的数据库类型选择合适的适配器'); ?></p>
                            </li>
                            <?php require_once './install/' . $type . '.php'; ?>
                            <li>
                            <label class="typecho-label"><?php _e('数据库前缀'); ?></label>
                            <input type="text" class="text mini" name="dbPrefix" value="<?php _v('dbPrefix', 'typecho_'); ?>" />
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
                            <label class="typecho-label"><?php _e('网站地址'); ?></label>
                            <input type="text" name="userUrl" class="text" value="<?php _v('userUrl', _u()); ?>" />
                            <p class="description"><?php _e('这是程序自动匹配的网站路径, 如果不正确请修改它'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label"><?php _e('用户名'); ?></label>
                            <input type="text" name="userName" class="text" value="<?php _v('userName', 'admin'); ?>" />
                            <p class="description"><?php _e('请填写您的用户名'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label"><?php _e('邮件地址'); ?></label>
                            <input type="text" name="userMail" class="text" value="<?php _v('userMail', 'webmaster@yourdomain.com'); ?>" />
                            <p class="description"><?php _e('请填写一个您的常用邮箱'); ?></p>
                            </li>
                        </ul>
                    </div>
                    <input type="hidden" name="action" value="config" />
                    <p class="submit"><button type="submit"><?php _e('确认, 开始安装 &raquo;'); ?></button></p>
                </form>
            <?php  else: ?>
                <form method="post" action="?config">
                <h1 class="typecho-install-title"><?php _e('欢迎使用Typecho'); ?></h1>
                <div class="typecho-install-body">
                <?php
                $success = true;
                if (false === ($handle = @fopen('./config.inc.php', 'ab'))) {
                    echo '<p class="message error">' . _t('安装目录不可写, 请设置你的目录权限为可写。<br /><br />或者在安装过程中手动上传config.inc.php文件.') . '</p>';
                } else {
                    fclose($handle);
                    unlink('./config.inc.php');
                }
                ?>
                <h2><?php _e('安装说明'); ?></h2>
                <p><strong><?php _e('本安装程序将自动检测服务器环境是否符合最低配置需求.如果不符合,将在上方出现提示信息,
请按照提示信息检查你的主机配置.如果服务器环境符合要求,将在下方出现"同意并安装"的按钮,点击此按钮即可一步完成安装.'); ?></strong></p>
                <h2><?php _e('许可及协议'); ?></h2>
                <p><?php _e('Typecho基于GPL协议发布,我们允许用户在GPL协议许可的范围内使用,拷贝,修改和分发此程序.
你可以自由地将其用于商业以及非商业用途.'); ?></p>
                <p><?php _e('Typecho软件由其社区提供支持,核心开发团队负责维护程序日常开发工作以及新特性的制定.如果你遇到使用上的问题,
程序中的BUG,以及期许的新功能,欢迎你在社区中交流或者直接向我们贡献代码.对于贡献突出者,他的名字将出现在贡献者名单中.'); ?></p>
                <h2><?php _e('此版本贡献者(排名不分先后)'); ?></h2>
                <ol>

                </ol>
                <p><a href="http://typecho.org"><?php _e('查看所有贡献者'); ?></a></p>
                </div>
                <?php
                if ($success) {
                    echo '<p class="submit"><button type="submit">' . _t('我准备好了, 开始下一步 &raquo;') . '</button></p>';
                }
                ?>
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
