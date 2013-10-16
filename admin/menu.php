<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="typecho-head-nav clearfix">
    <nav id="typecho-nav-list">
        <?php $menu->output(); ?>
    </nav>
    <div class="operate">
        <!-- <?php Typecho_Plugin::factory('admin/menu.php')->navBar(); _e('欢迎'); ?>, -->
        <a href="<?php $options->adminUrl('profile.php'); ?>" class="author"><?php $user->screenName(); ?></a><!--
        --><a class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('登出'); ?></a><!--
        --><a href="<?php $options->siteUrl(); ?>">网站</a>
    </div>
</div>

<?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
<div class="message <?php $notice->noticeType(); ?> popup">
<ul>
    <?php $notice->lists(); ?>
</ul>
</div>
<?php endif; ?>
