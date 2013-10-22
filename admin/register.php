<?php
include 'common.php';
include 'header.php';

$rememberName = Typecho_Cookie::get('__typecho_remember_name');
$rememberMail = Typecho_Cookie::get('__typecho_remember_mail');
Typecho_Cookie::delete('__typecho_remember_name');
Typecho_Cookie::delete('__typecho_remember_mail');
?>

<?php if(!$user->hasLogin() && $options->allowRegister): ?>
    <?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
    <div class="message <?php $notice->noticeType(); ?> popup">
        <ul>
            <?php $notice->lists(); ?>
        </ul>
    </div>
    <?php endif; ?>
<?php elseif (!$options->allowRegister): ?>
    <div class="message error popup">
        <ul>
            <li><?php _e('网站注册已关闭'); ?></li>
        </ul>
    </div>
<?php else: ?>
    <?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
    <div class="message <?php $notice->noticeType(); ?> popup">
    <ul>
        <?php $notice->lists(); ?>
    </ul>
    </div>
    <?php else: ?>
    <div class="message notice popup">
        <ul>
            <li><?php _e('您已经登录到 %s', $options->title); ?></li>
        </ul>
    </div>
    <?php endif; ?>
<?php endif; ?>

<div class="typecho-login">
    <h1>Typecho</h2>
    <form action="<?php $options->registerAction(); ?>" method="post" name="register">
        <p><input type="text" id="name" name="name" placeholder="<?php _e('用户名'); ?>" value="<?php echo $rememberName; ?>" class="text-l w-100" /></p>
        <p><input type="email" id="mail" name="mail" placeholder="<?php _e('Email'); ?>" value="<?php echo $rememberMail; ?>" class="text-l w-100" /></p>
        <p class="submit">
        <!-- <label for="remember"><input type="checkbox" name="remember" class="checkbox" value="1" id="remember" /> <?php _e('记住我'); ?></label> -->
            <button type="submit" class="btn-l w-100 primary"><?php _e('注册'); ?></button>
        </p>
    </form>
    
    <p class="more-link">
        <a href="<?php $options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
        <!-- &bull;
        <a href=""><?php _e('忘记密码'); ?></a> -->
        <?php if($user->hasLogin()): ?>
        &bull;
        <a href="<?php $options->adminUrl(); ?>"><?php _e('后台管理'); ?></a>
        <?php else: ?>
        &bull;
        <a href="<?php $options->adminUrl('login.php'); ?>"><?php _e('用户登录'); ?></a>
        <?php endif; ?>
    </p>
</div>
<?php 
include 'common-js.php';
?>
<script>
$(document).ready(function () {
    $('#name').focus();
});
</script>
<?php
include 'footer.php';
?>
