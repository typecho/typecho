<?php
include 'common.php';
include 'header.php';

$rememberName = Typecho_Cookie::get('__typecho_remember_name');
Typecho_Cookie::delete('__typecho_remember_name');
?>
<div class="body container">
    <div class="col-group">
        <div class="col-3 start-09 typecho-login">
            <h2 class="logo-dark">typecho</h2>
            <form action="<?php $options->loginAction(); ?>" method="post" name="login">
                <fieldset>
                    <?php if(!$user->hasLogin()): ?>
                    <?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
                    <div class="message <?php $notice->noticeType(); ?> typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                    <ul>
                        <?php $notice->lists(); ?>
                    </ul>
                    </div>
                    <?php endif; ?>
                    <p><label for="name"><?php _e('用户名'); ?>:</label> <input type="text" id="name" name="name" value="<?php echo $rememberName; ?>" class="text" /></p>
                    <p><label for="password"><?php _e('密码'); ?>:</label> <input type="password" id="password" name="password" class="text" /></p>
                    <p class="submit">
                    <label for="remember"><input type="checkbox" name="remember" class="checkbox" value="1" id="remember" /> <?php _e('记住我'); ?></label>
                    <button type="submit"><?php _e('登录'); ?></button>
                    <input type="hidden" name="referer" value="<?php echo htmlspecialchars($request->get('referer')); ?>" />
                    </p>
                    <?php else: ?>
                    <div class="message notice typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                        <ul>
                            <li><?php _e('您已经登录到%s', $options->title); ?></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </fieldset>
            </form>
            
            <div class="more-link">
                <p class="back-to-site">
                <a href="<?php $options->siteUrl(); ?>" class="important"><?php _e('&laquo; 返回%s', $options->title); ?></a>
                </p>
                <p class="forgot-password">
                <?php if($user->hasLogin()): ?>
                <a href="<?php $options->adminUrl(); ?>"><?php _e('进入后台 &raquo;'); ?></a>
                <?php elseif($options->allowRegister): ?>
                <a href="<?php $options->registerUrl(); ?>"><?php _e('注册 &raquo;'); ?></a>
                <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
(function () {
    var _form = document.login.name;
    _form.focus();
})();
</script>
<?php include 'footer.php'; ?>
