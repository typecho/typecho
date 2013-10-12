<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="typecho-head-nav clearfix">
    <div id="typecho-nav-list">
        <?php $menu->output(); ?>
    </div>
    <a href="">Typecho</a>
    <a href="<?php $options->siteUrl(); ?>"><?php _e('我的站点'); ?></a>
    <div class="operate">
        <?php Typecho_Plugin::factory('admin/menu.php')->navBar(); _e('欢迎'); ?>,
        <a href="<?php $options->adminUrl('profile.php'); ?>" class="author important"><?php $user->screenName(); ?></a>
        <a class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('登出'); ?></a>
    </div>
</div>
