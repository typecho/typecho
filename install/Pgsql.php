<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('数据库地址'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="localhost"/>
        <p class="description"><?php _e('您可能会使用 "%s"', 'localhost'); ?></p>
    </li>
</ul>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('数据库用户名'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="postgres" />
        <p class="description"><?php _e('您可能会使用 "%s"', 'postgres'); ?></p>
    </li>
</ul>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('数据库密码'); ?></label>
        <input type="password" class="text" name="dbPassword" id="dbPassword" value="" />
    </li
</ul>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('数据库名'); ?></label>
        <input type="text" class="text" name="dbDatabase" id="dbDatabase" value="" />
        <p class="description"><?php _e('请您指定数据库名称'); ?></p>
    </li
</ul>


<details>
    <summary>
        <strong><?php _e('高级选项'); ?></strong>
    </summary>
    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbPort"><?php _e('数据库端口'); ?></label>
            <input type="text" class="text" name="dbPort" id="dbPort" value="5432"/>
            <p class="description"><?php _e('如果您不知道此选项的意义, 请保留默认设置'); ?></p>
        </li>
    </ul>

    <input type="hidden" name="dbCharset" value="utf8" />

    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbSslVerify"><?php _e('启用数据库 SSL 服务端证书验证'); ?></label>
            <select name="dbSslVerify" id="dbSslVerify">
                <option value="off"><?php _e('不启用'); ?></option>
                <option value="on"><?php _e('启用'); ?></option>
            </select>
        </li>
    </ul>
</details>
