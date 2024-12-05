<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row justify-content-between typecho-page-main">
            <div class="col-lg-3">
                <p><a href="https://gravatar.com/"
                      title="<?php _e('在 Gravatar 上修改头像'); ?>"><?php echo '<img class="profile-avatar rounded-3" src="' . \Typecho\Common::gravatarUrl($user->mail, 220, 'X', 'mm', $request->isSecure()) . '" alt="' . $user->screenName . '" />'; ?></a>
                </p>
                <h2 class="fs-4 mb-0"><?php $user->screenName(); ?></h2>
                <p class="mt-0"><?php $user->name(); ?></p>
                <p><?php _e('目前有 <strong>%s</strong> 篇日志, 并有 <strong>%s</strong> 条关于你的评论在 <strong>%s</strong> 个分类中.',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?></p>
                <p><?php
                    if ($user->logged > 0) {
                        $logged = new \Typecho\Date($user->logged);
                        _e('最后登录: %s', $logged->word());
                    }
                    ?></p>
            </div>

            <div class="col-lg-8 typecho-content-panel" role="form">
                <section>
                    <h3 class="fs-5"><?php _e('个人资料'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->profileForm()->render(); ?>
                </section>

                <?php if ($user->pass('contributor', true)): ?>
                    <br>
                    <section id="writing-option">
                        <h3 class="fs-5"><?php _e('撰写设置'); ?></h3>
                        <?php \Widget\Users\Profile::alloc()->optionsForm()->render(); ?>
                    </section>
                <?php endif; ?>

                <br>

                <section id="change-password">
                    <h3 class="fs-5"><?php _e('密码修改'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->passwordForm()->render(); ?>
                </section>

                <?php \Widget\Users\Profile::alloc()->personalFormList(); ?>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
\Typecho\Plugin::factory('admin/profile.php')->call('bottom');
include 'footer.php';
?>
