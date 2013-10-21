<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php if (defined('SAE_MYSQL_DB')): ?>
<!-- SAE -->
<h3 class="warning"><?php _e('系统将为您自动匹配 %s 环境的安装选项', 'SAE'); ?></h3>
<input type="hidden" name="config" value="array (
    'host'      =>  SAE_MYSQL_HOST_M,
    'user'      =>  SAE_MYSQL_USER,
    'password'  =>  SAE_MYSQL_PASS,
    'charset'   =>  '<?php _e('utf8'); ?>',
    'port'      =>  SAE_MYSQL_PORT,
    'database'  =>  SAE_MYSQL_DB
)" />
<input type="hidden" name="dbHost" value="<?php echo SAE_MYSQL_HOST_M; ?>" />
<input type="hidden" name="dbPort" value="<?php echo SAE_MYSQL_PORT; ?>" />
<input type="hidden" name="dbUser" value="<?php echo SAE_MYSQL_USER; ?>" />
<input type="hidden" name="dbPassword" value="<?php echo SAE_MYSQL_PASS; ?>" />
<input type="hidden" name="dbDatabase" value="<?php echo SAE_MYSQL_DB; ?>" />
<?php elseif (!!getenv('HTTP_BAE_ENV_ADDR_SQL_IP')): ?>
<!-- BAE -->
<h3 class="warning"><?php _e('系统将为您自动匹配 %s 环境的安装选项', 'BAE'); ?></h3>
<li>
<label class="typecho-label" for="dbDatabase"><?php _e('数据库名'); ?></label>
<input type="text" class="text" id="dbDatabase" name="dbDatabase" value="<?php _v('dbDatabase'); ?>" />
<p class="description"><?php _e('可以在MySQL服务的管理页面看到您创建的数据库名称'); ?></p>
</li>
<input type="hidden" name="config" value="array (
    'host'      =>  getenv('HTTP_BAE_ENV_ADDR_SQL_IP'),
    'user'      =>  getenv('HTTP_BAE_ENV_AK'),
    'password'  =>  getenv('HTTP_BAE_ENV_SK'),
    'charset'   =>  '<?php _e('utf8'); ?>',
    'port'      =>  getenv('HTTP_BAE_ENV_ADDR_SQL_PORT'),
    'database'  =>  '{database}'
)" />
<input type="hidden" name="dbHost" value="<?php echo getenv('HTTP_BAE_ENV_ADDR_SQL_IP'); ?>" />
<input type="hidden" name="dbPort" value="<?php echo getenv('HTTP_BAE_ENV_ADDR_SQL_PORT'); ?>" />
<input type="hidden" name="dbUser" value="<?php echo getenv('HTTP_BAE_ENV_AK'); ?>" />
<input type="hidden" name="dbPassword" value="<?php echo getenv('HTTP_BAE_ENV_SK'); ?>" />
<?php elseif (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false): ?>
<!-- GAE -->
<h3 class="warning"><?php _e('系统将为您自动匹配 %s 环境的安装选项', 'GAE'); ?></h3>
<li>
<label class="typecho-label" for="dbPort"><?php _e('数据库实例名'); ?></label>
<input type="text" class="text" name="dbPort" id="dbPort" value="<?php _v('dbPort'); ?>"/>
<p class="description"><?php _e('请填入您在Cloud SQL面板中创建的数据库实例名称'); ?></p>
</li>
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
<input type="hidden" name="config" value="array (
    'dsn'       =>  'mysql:dbname={database};unix_socket=/cloudsql/{host}:{port};charset=<?php _e('utf8'); ?>',
    'user'      =>  '{user}',
    'password'  =>  '{password}'
)" />
<?php else: ?>
<input type="hidden" name="config" value="array (
    'host'      =>  ':/cloudsql/{host}:{port}',
    'database'  =>  '{database}',
    'user'      =>  '{user}',
    'password'  =>  '{password}'
)" />
<?php endif; ?>
<input type="hidden" name="dbHost" value="<?php echo $_SERVER['APPLICATION_ID'] ?>" />
<?php  else: ?>
<li>
<label class="typecho-label" for="dbHost"><?php _e('数据库地址'); ?></label>
<input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost', 'localhost'); ?>"/>
<p class="description"><?php _e('您可能会使用 "localhost"'); ?></p>
</li>
<li>
<label class="typecho-label" for="dbPort"><?php _e('数据库端口'); ?></label>
<input type="text" class="text" name="dbPort" id="dbPort" value="<?php _v('dbPort', '3306'); ?>"/>
<p class="description"><?php _e('如果您不知道此选项的意义, 请保留默认设置'); ?></p>
</li>
<li>
<label class="typecho-label" for="dbUser"><?php _e('数据库用户名'); ?></label>
<input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser', 'root'); ?>" />
<p class="description"><?php _e('您可能会使用 "root"'); ?></p>
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

