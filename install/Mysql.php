<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php if (defined('SAE_MYSQL_DB')): ?>
<!-- SAE -->
<h3><?php _e('系统将为你自动匹配 %s 环境的安装选项', 'SAE'); ?></h3>
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
<h3><?php _e('系统将为你自动匹配 %s 环境的安装选项', 'BAE'); ?></h3>
<li>
<label class="typecho-label"><?php _e('数据库名'); ?></label>
<input type="text" class="text" name="dbDatabase" value="<?php _v('dbDatabase', 'typecho'); ?>" />
<p class="description"><?php _e('请您指定数据库名称'); ?></p>
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
<?php  else: ?>
<li>
<label class="typecho-label"><?php _e('数据库地址'); ?></label>
<input type="text" class="text" name="dbHost" value="<?php _v('dbHost', 'localhost'); ?>"/>
<p class="description"><?php _e('您可能会使用 "localhost"'); ?></p>
</li>
<li>
<label class="typecho-label"><?php _e('数据库端口'); ?></label>
<input type="text" class="text" name="dbPort" value="<?php _v('dbPort', '3306'); ?>"/>
<p class="description"><?php _e('如果您不知道此选项的意义, 请保留默认设置'); ?></p>
</li>
<li>
<label class="typecho-label"><?php _e('数据库用户名'); ?></label>
<input type="text" class="text" name="dbUser" value="<?php _v('dbUser', 'root'); ?>" />
<p class="description"><?php _e('您可能会使用 "root"'); ?></p>
</li>
<li>
<label class="typecho-label"><?php _e('数据库密码'); ?></label>
<input type="password" class="text" name="dbPassword" value="<?php _v('dbPassword'); ?>" />
</li>
<li>
<label class="typecho-label"><?php _e('数据库名'); ?></label>
<input type="text" class="text" name="dbDatabase" value="<?php _v('dbDatabase', 'typecho'); ?>" />
<p class="description"><?php _e('请您指定数据库名称'); ?></p>
</li>

<?php  endif; ?>
<input type="hidden" name="dbCharset" value="<?php _e('utf8'); ?>" />

