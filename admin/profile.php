<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-3">
                <p><a href="https://gravatar.com/emails/"
                      title="<?php _e('在 Gravatar 上修改头像'); ?>"><?php echo '<img class="profile-avatar" src="' . \Typecho\Common::gravatarUrl($user->mail, 220, 'X', 'mm', $request->isSecure()) . '" alt="' . $user->screenName . '" />'; ?></a>
                </p>
                <h2><?php $user->screenName(); ?></h2>
                <p><?php $user->name(); ?></p>
                <p><?php _e('目前有 <em>%s</em> 篇日志, 并有 <em>%s</em> 条关于你的评论在 <em>%s</em> 个分类中.',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?></p>
                <p><?php
                    if ($user->logged > 0) {
                        $logged = new \Typecho\Date($user->logged);
                        _e('最后登录: %s', $logged->word());
                    }
                    ?></p>
            </div>

            <div class="col-mb-12 col-tb-6 col-tb-offset-1 typecho-content-panel" role="form">
                <section>
                    <h3><?php _e('个人资料'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->profileForm()->render(); ?>
                </section>

                <?php if ($user->pass('contributor', true)): ?>
                    <br>
                    <section id="writing-option">
                        <h3><?php _e('撰写设置'); ?></h3>
                        <?php \Widget\Users\Profile::alloc()->optionsForm()->render(); ?>
                    </section>
                <?php endif; ?>

                <br>

                <section id="change-password">
                    <h3><?php _e('密码修改'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->passwordForm()->render(); ?>
                </section>

                <?php \Widget\Users\Profile::alloc()->personalFormList(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
\Typecho\Plugin::factory('admin/profile.php')->bottom();
include 'footer.php';
?>
