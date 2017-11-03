<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php
$engine = '';

if (defined('SAE_MYSQL_DB') && SAE_MYSQL_DB != "app_") {
    $engine = 'SAE';
} else if (!!getenv('HTTP_BAE_ENV_ADDR_SQL_IP')) {
    $engine = 'BAE';
} else if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false) {
    $engine = 'GAE';
}
?>

<?php if (!empty($engine)): ?>
<h3 class="warning"><?php _e('系统将为您自动匹配 %s 环境的安装选项', $engine); ?></h3>
<?php endif; ?>

<?php if ('SAE' == $engine): ?>
<!-- SAE -->
    <input type="hidden" name="config" value="array (
    'host'      =>  SAE_MYSQL_HOST_M,
    'user'      =>  SAE_MYSQL_USER,
    'password'  =>  SAE_MYSQL_PASS,
    'charset'   =>  '{charset}',
    'port'      =>  SAE_MYSQL_PORT,
    'database'  =>  SAE_MYSQL_DB
)" />
    <input type="hidden" name="dbHost" value="<?php echo SAE_MYSQL_HOST_M; ?>" />
    <input type="hidden" name="dbPort" value="<?php echo SAE_MYSQL_PORT; ?>" />
    <input type="hidden" name="dbUser" value="<?php echo SAE_MYSQL_USER; ?>" />
    <input type="hidden" name="dbPassword" value="<?php echo SAE_MYSQL_PASS; ?>" />
    <input type="hidden" name="dbDatabase" value="<?php echo SAE_MYSQL_DB; ?>" />
<?php elseif ('BAE' == $engine):
$baeDbUser = "getenv('HTTP_BAE_ENV_AK')";
$baeDbPassword = "getenv('HTTP_BAE_ENV_SK')";
?>
<!-- BAE -->
    <?php if (!getenv('HTTP_BAE_ENV_AK')): $baeDbUser = "'{user}'"; ?>
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('应用API Key'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser'); ?>" />
    </li>
    <?php else: ?>
    <input type="hidden" name="dbUser" value="<?php echo getenv('HTTP_BAE_ENV_AK'); ?>" />
    <?php endif; ?>

    <?php if (!getenv('HTTP_BAE_ENV_SK')): $baeDbPassword = "'{password}'"; ?>
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('应用Secret Key'); ?></label>
        <input type="text" class="text" name="dbPassword" id="dbPassword" value="<?php _v('dbPassword'); ?>" />
    </li>
    <?php else: ?>
    <input type="hidden" name="dbPassword" value="<?php echo getenv('HTTP_BAE_ENV_SK'); ?>" />
    <?php endif; ?>

    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('数据库名'); ?></label>
        <input type="text" class="text" id="dbDatabase" name="dbDatabase" value="<?php _v('dbDatabase'); ?>" />
        <p class="description"><?php _e('可以在MySQL服务的管理页面看到您创建的数据库名称'); ?></p>
    </li>
    <input type="hidden" name="config" value="array (
    'host'      =>  getenv('HTTP_BAE_ENV_ADDR_SQL_IP'),
    'user'      =>  <?php echo $baeDbUser; ?>,
    'password'  =>  <?php echo $baeDbPassword; ?>,
    'charset'   =>  '{charset}',
    'port'      =>  getenv('HTTP_BAE_ENV_ADDR_SQL_PORT'),
    'database'  =>  '{database}'
)" />
    <input type="hidden" name="dbHost" value="<?php echo getenv('HTTP_BAE_ENV_ADDR_SQL_IP'); ?>" />
    <input type="hidden" name="dbPort" value="<?php echo getenv('HTTP_BAE_ENV_ADDR_SQL_PORT'); ?>" />

<?php elseif ('GAE' == $engine): ?>
<!-- GAE -->
    <h3 class="warning"><?php _e('系统将为您自动匹配 %s 环境的安装选项', 'GAE'); ?></h3>
<?php if (0 === strpos($adapter, 'Pdo_')): ?>
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('数据库实例名'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost'); ?>"/>
        <p class="description"><?php _e('请填入您在Cloud SQL面板中创建的数据库实例名称, 示例: %s', '<em class="warning">/cloudsql/typecho-gae:typecho</em>'); ?></p>
    </li>
<?php else: ?>
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('数据库实例名'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost'); ?>"/>
        <p class="description"><?php _e('请填入您在Cloud SQL面板中创建的数据库实例名称, 示例: %s', '<em class="warning">:/cloudsql/typecho-gae:typecho</em>'); ?></p>
    </li>
<?php endif; ?>

    <li>
        <label class="typecho-label" for="dbUser"><?php _e('数据库用户名'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser'); ?>" />
    </li>
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('数据库密码'); ?></label>
        <input type="password" class="text" name="dbPassword" id="dbPassword" value="<?php _v('dbPassword'); ?>" />
    </li>
    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('数据库名'); ?></label>
        <input type="text" class="text" name="dbDatabase" id="dbDatabase" value="<?php _v('dbDatabase', 'typecho'); ?>" />
        <p class="description"><?php _e('请填入您在Cloud SQL的实例中创建的数据库名称'); ?></p>
    </li>

<?php if (0 === strpos($adapter, 'Pdo_')): ?>
    <input type="hidden" name="dbDsn" value="mysql:dbname={database};unix_socket={host};charset={charset}" />
    <input type="hidden" name="config" value="array (
    'dsn'       =>  '{dsn}',
    'user'      =>  '{user}',
    'password'  =>  '{password}'
)" />
<?php else: ?>
    <input type="hidden" name="config" value="array (
    'host'      =>  '{host}',
    'database'  =>  '{database}',
    'user'      =>  '{user}',
    'password'  =>  '{password}'
)" />
<?php endif; ?>


<?php  else: ?>
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('数据库地址'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost', 'localhost'); ?>"/>
        <p class="description"><?php _e('您可能会使用 "%s"', 'localhost'); ?></p>
    </li>
    <li>
        <label class="typecho-label" for="dbPort"><?php _e('数据库端口'); ?></label>
        <input type="text" class="text" name="dbPort" id="dbPort" value="<?php _v('dbPort', '3306'); ?>"/>
        <p class="description"><?php _e('如果您不知道此选项的意义, 请保留默认设置'); ?></p>
    </li>
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('数据库用户名'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser', 'root'); ?>" />
        <p class="description"><?php _e('您可能会使用 "%s"', 'root'); ?></p>
    </li>
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('数据库密码'); ?></label>
        <input type="password" class="text" name="dbPassword" id="dbPassword" value="<?php _v('dbPassword'); ?>" />
    </li>
    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('数据库名'); ?></label>
        <input type="text" class="text" name="dbDatabase" id="dbDatabase" value="<?php _v('dbDatabase', 'typecho'); ?>" />
        <p class="description"><?php _e('请您指定数据库名称'); ?></p>
    </li>

<?php  endif; ?>
<input type="hidden" name="dbCharset" value="<?php _e('utf8'); ?>" />

    <li>
        <label class="typecho-label" for="dbCharset"><?php _e('数据库编码'); ?></label>
        <select name="dbCharset" id="dbCharset">
            <option value="utf8"<?php if (_r('dbCharset') == 'utf8'): ?> selected<?php endif; ?>>utf8</option>
            <option value="utf8mb4"<?php if (_r('dbCharset') == 'utf8mb4'): ?> selected<?php endif; ?>>utf8mb4</option>
        </select>
        <p class="description"><?php _e('选择 utf8mb4 编码至少需要 MySQL 5.5.3 版本'); ?></p>
    </li>

    <li>
        <label class="typecho-label" for="dbEngine"><?php _e('数据库引擎'); ?></label>
        <select name="dbEngine" id="dbEngine">
            <option value="MyISAM"<?php if (_r('dbEngine') == 'MyISAM'): ?> selected<?php endif; ?>>MyISAM</option>
            <option value="InnoDB"<?php if (_r('dbEngine') == 'InnoDB'): ?> selected<?php endif; ?>>InnoDB</option>
        </select>
    </li>
