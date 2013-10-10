<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="typecho-head-nav container">
    <div id="typecho-nav-list">
        <?php $menu->output(); ?>
    </div>
    <p class="operate"><?php Typecho_Plugin::factory('admin/menu.php')->navBar(); _e('欢迎'); ?>, <a href="<?php $options->adminUrl('profile.php'); ?>" class="author important"><?php $user->screenName(); ?></a>
            <a class="exit" href="<?php $options->logoutUrl(); ?>" title="<?php _e('登出'); ?>"><?php _e('登出'); ?></a></p>
</div>
