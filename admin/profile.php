<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-3">
                <p><a href="https://gravatar.com/"
                      title="<?php _e('在 Gravatar 上修改头像'); ?>"><?php echo '<img class="profile-avatar" src="' . \Typecho\Common::gravatarUrl($user->mail, 220, 'X', 'mm', $request->isSecure()) . '" alt="' . $user->screenName . '" />'; ?></a>
                </p>
                <h2><?php $user->screenName(); ?></h2>
                <p><?php $user->name(); ?></p>
                <p><?php _e('权限组: '); ?><?php switch ($user->group) {
                    case 'administrator':
                        _e('管理员');
                        break;
                    case 'editor':
                        _e('编辑');
                        break;
                    case 'contributor':
                        _e('贡献者');
                        break;
                    case 'subscriber':
                        _e('关注者');
                        break;
                    case 'visitor':
                        _e('访问者');
                        break;
                    default:
                        _e('游客');
                        break;
                    } ?></p>
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
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
\Typecho\Plugin::factory('admin/profile.php')->call('bottom');
include 'footer.php';
?>
