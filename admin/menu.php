<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<div class="typecho-head-nav clearfix" role="navigation">
    <a class="menu-bar" href="###" onclick="$(this).toggleClass('focus'); return false;"><?php _e('菜单'); ?></a>
    <nav id="typecho-nav-list">
        <?php $menu->output(); ?>
    </nav>
    <div class="operate">
        <?php Typecho_Plugin::factory('admin/menu.php')->navBar(); ?><a title="<?php
                    if ($user->logged > 0) {
                        $logged = new Typecho_Date($user->logged);
                        _e('最后登录: %s', $logged->word());
                    }
                    ?>" href="<?php $options->adminUrl('profile.php'); ?>" class="author"><?php $user->screenName(); ?></a><a class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('登出'); ?></a><a href="<?php $options->siteUrl(); ?>"><?php _e('网站'); ?></a>
    </div>
</div>

