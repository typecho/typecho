<?php
require 'common.php';

if ($user->hasLogin()) {
    $response->redirect($options->adminUrl);
}

$rememberName = htmlspecialchars(\Typecho\Cookie::get('__typecho_remember_name', ''));
\Typecho\Cookie::delete('__typecho_remember_name');

include 'header.php';
?>

<div class="typecho-login-wrap">
    <div class="typecho-login">
        <h1><a href="https://typecho.org" class="i-logo">Typecho</a></h1>
        <form action="<?= $options->loginAction(); ?>" method="post" name="login" role="form">
            <p>
                <input type="text" id="name" name="name" value="<?= $rememberName; ?>" placeholder="<?= _e('用户名'); ?>" class="text-l w-100" autofocus />
            </p>
            <p>
                <input type="password" id="password" name="password" class="text-l w-100" placeholder="<?= _e('密码'); ?>" />
            </p>
            <p class="submit">
                <button type="submit" class="btn btn-l w-100 primary"><?= _e('登录'); ?></button>
                <input type="hidden" name="referer" value="<?= $request->filter('html')->get('referer'); ?>" />
            </p>
            <p>
                <label for="remember">
                    <input<?= \Typecho\Cookie::get('__typecho_remember_remember') ? ' checked' : ''; ?> type="checkbox" name="remember" class="checkbox" value="1" id="remember" /> <?= _e('下次自动登录'); ?>
                </label>
            </p>
        </form>
        
        <p class="more-link">
            <a href="<?= $options->siteUrl(); ?>"><?= _e('返回首页'); ?></a>
            <?php if ($options->allowRegister): ?>
                &bull;
                <a href="<?= $options->registerUrl(); ?>"><?= _e('用户注册'); ?></a>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php require 'common-js.php'; ?>

<script>
    $(document).ready(function () {
        $('#name').focus();
    });
</script>

<?php include 'footer.php'; ?>
