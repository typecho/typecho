<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $defaultDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/usr/' . uniqid() . '.db'; ?>
<li>
<label class="typecho-label" for="dbFile"><?php _e('数据库文件路径'); ?></label>
<input type="text" class="text" name="dbFile" id="dbFile" value="<?php _v('dbFile', $defaultDir); ?>"/>
<p class="description"><?php _e('"%s" 是我们为您自动生成的地址', $defaultDir); ?></p>
</li>
