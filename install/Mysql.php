<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

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

<li>
    <label class="typecho-label" for="dbCharset"><?php _e('数据库编码'); ?></label>
    <select name="dbCharset" id="dbCharset">
        <option value="utf8mb4"<?php if (_r('dbCharset') == 'utf8mb4'): ?> selected<?php endif; ?>>utf8mb4</option>
        <option value="utf8"<?php if (_r('dbCharset') == 'utf8'): ?> selected<?php endif; ?>>utf8</option>
    </select>
    <p class="description"><?php _e('选择 utf8mb4 编码至少需要 MySQL 5.5.3 版本'); ?></p>
</li>

<li>
    <label class="typecho-label" for="dbEngine"><?php _e('数据库引擎'); ?></label>
    <select name="dbEngine" id="dbEngine">
        <option value="InnoDB"<?php if (_r('dbEngine') == 'InnoDB'): ?> selected<?php endif; ?>>InnoDB</option>
        <option value="MyISAM"<?php if (_r('dbEngine') == 'MyISAM'): ?> selected<?php endif; ?>>MyISAM</option>
    </select>
</li>
