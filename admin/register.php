<?php
include 'common.php';
include 'header.php';

$rememberName = Typecho_Cookie::get('__typecho_remember_name');
$rememberMail = Typecho_Cookie::get('__typecho_remember_mail');
Typecho_Cookie::delete('__typecho_remember_name');
Typecho_Cookie::delete('__typecho_remember_mail');
?>
<div class="body container">
    <div class="col-group">
        <div class="column-07 start-09 typecho-login">
            <h2 class="logo-dark">typecho</h2>
            <form action="<?php $options->registerAction(); ?>" method="post" name="register">
                <fieldset>
                    <?php if(!$user->hasLogin() && $options->allowRegister): ?>
                    <?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
                    <div class="message <?php $notice->noticeType(); ?> typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                    <ul>
                        <?php $notice->lists(); ?>
                    </ul>
                    </div>
                    <?php endif; ?>
                    <p><label for="name"><?php _e('用户名'); ?>:</label> <input type="text" id="name" name="name" value="<?php echo $rememberName; ?>" class="text" /></p>
                    <p><label for="mail"><?php _e('电子邮件'); ?>:</label> <input type="text" id="mail" name="mail" value="<?php echo $rememberMail; ?>" class="text" /></p>
                    <p class="submit">
                    <label for="remember"><input type="checkbox" name="remember" class="checkbox" value="1" id="remember" /> <?php _e('记住我'); ?></label>
                    <button type="submit"><?php _e('注册'); ?></button>
                    </p>
                    <?php elseif (!$options->allowRegister): ?>
                    <div class="message error typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                        <ul>
                            <li><?php _e('网站注册已关闭'); ?></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
                    <div class="message <?php $notice->noticeType(); ?> typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                    <ul>
                        <?php $notice->lists(); ?>
                    </ul>
                    </div>
                    <?php else: ?>
                    <div class="message notice typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                        <ul>
                            <li><?php _e('您已经登录到%s', $options->title); ?></li>
                        </ul>
                    </div>
                    <?php endif; ?>
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
                <?php else: ?>
                <a href="<?php $options->adminUrl('login.php'); ?>"><?php _e('登录 &raquo;'); ?></a>
                <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
(function () {
    var _form = document.register.name;
    _form.focus();
})();
</script>
<?php include 'footer.php'; ?>
