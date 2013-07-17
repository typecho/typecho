<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<li>
<label class="typecho-label"><?php _e('数据库地址'); ?></label>
<input type="text" class="text" name="dbHost" value="<?php _v('dbHost', 'localhost'); ?>"/>
<p class="description"><?php _e('您可能会使用 "localhost"'); ?></p>
</li>
<li>
<label class="typecho-label"><?php _e('数据库端口'); ?></label>
<input type="text" class="text" name="dbPort" value="<?php _v('dbPort', '5432'); ?>"/>
<p class="description"><?php _e('如果您不知道此选项的意义, 请保留默认设置'); ?></p>
</li>
<li>
<label class="typecho-label"><?php _e('数据库用户名'); ?></label>
<input type="text" class="text" name="dbUser" value="<?php _v('dbUser', 'postgres'); ?>" />
<p class="description"><?php _e('您可能会使用 "postgres"'); ?></p>
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
<input type="hidden" name="dbCharset" value="<?php _e('utf8'); ?>" />
