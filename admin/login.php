<?php
include 'common.php';
include 'header.php';

$rememberName = Typecho_Cookie::get('__typecho_remember_name');
Typecho_Cookie::delete('__typecho_remember_name');
?>

<?php if(!$user->hasLogin()): ?>
    <?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
    <div class="message <?php $notice->noticeType(); ?> popup">
        <ul>
            <?php $notice->lists(); ?>
        </ul>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="message notice popup">
        <ul>
            <li><?php _e('您已经登录到%s', $options->title); ?></li>
        </ul>
    </div>
<?php endif; ?>

<div class="typecho-login">
    <h1>Typecho</h1>
    <form action="<?php $options->loginAction(); ?>" method="post" name="login">
        <p><input type="text" id="name" name="name" value="<?php echo $rememberName; ?>" placeholder="<?php _e('用户名'); ?>" class="text-l w-100" /></p>
        <p><input type="password" id="password" name="password" class="text-l w-100" placeholder="<?php _e('密码'); ?>" /></p>
        <p class="submit">
            <!-- <label for="remember"><input type="checkbox" name="remember" class="checkbox" value="1" id="remember" /> <?php _e('记住我'); ?></label> -->
            <button type="submit" class="btn-l w-100 primary"><?php _e('登录'); ?></button>
            <input type="hidden" name="referer" value="<?php echo htmlspecialchars($request->get('referer')); ?>" />
        </p>
    </form>
    
    <p class="more-link">
        <a href="<?php $options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
        <?php if($user->hasLogin()): ?>
        <!-- &bull;
        <a href=""><?php _e('忘记密码'); ?></a> -->
        &bull;
        <a href="<?php $options->adminUrl(); ?>"><?php _e('后台管理'); ?></a>
        <?php elseif($options->allowRegister): ?>
        &bull;
        <a href="<?php $options->registerUrl(); ?>"><?php _e('用户注册'); ?></a>
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
