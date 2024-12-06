<?php
include 'common.php';

if ($user->hasLogin()) {
    $response->redirect($options->adminUrl);
}
$rememberName = htmlspecialchars(\Typecho\Cookie::get('__typecho_remember_name', ''));
\Typecho\Cookie::delete('__typecho_remember_name');

include 'header.php';
?>
<div class="typecho-login-wrap d-flex align-items-center justify-content-center">
    <div class="typecho-login">
        <h1><a href="https://typecho.org" class="i-logo">Typecho</a></h1>
        <form action="<?php $options->loginAction(); ?>" method="post" name="login" role="form">
            <p>
                <label for="name" class="sr-only"><?php _e('用户名或邮箱'); ?></label>
                <input type="text" id="name" name="name" value="<?php echo $rememberName; ?>" placeholder="<?php _e('用户名或邮箱'); ?>" class="form-lg w-100" autofocus />
            </p>
            <p>
                <label for="password" class="sr-only"><?php _e('密码'); ?></label>
                <input type="password" id="password" name="password" class="form-lg w-100" placeholder="<?php _e('密码'); ?>" required />
            </p>
            <p class="submit">
                <button type="submit" class="btn btn-primary btn-lg w-100"><?php _e('登录'); ?></button>
                <input type="hidden" name="referer" value="<?php echo $request->filter('html')->get('referer'); ?>" />
            </p>
            <p>
                <label for="remember">
                    <input<?php if(\Typecho\Cookie::get('__typecho_remember_remember')): ?> checked<?php endif; ?> type="checkbox" name="remember" class="checkbox" value="1" id="remember" /> <?php _e('下次自动登录'); ?>
                </label>
            </p>
        </form>
        
        <div class="more-link">
            <a href="<?php $options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
            <?php if($options->allowRegister): ?>
            &bull;
            <a href="<?php $options->registerUrl(); ?>"><?php _e('用户注册'); ?></a>
            <?php endif; ?>
        </div>
    </div>
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
