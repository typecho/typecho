<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <div id="typecho-welcome" class="message">
                    <form action="<?php $options->adminUrl(); ?>" method="get">
                    <h3><?php _e('欢迎您使用 "%s" 管理后台: ', $options->title); ?></h3>
                    <ol>
                        <li><a class="operate-delete" href="<?php $options->adminUrl('profile.php#change-password'); ?>"><?php _e('强烈建议更改你的默认密码'); ?></a></li>
                        <?php if($user->pass('contributor', true)): ?>
                        <li><a href="<?php $options->adminUrl('write-post.php'); ?>"><?php _e('撰写第一篇日志'); ?></a></li>
                        <li><a href="<?php $options->siteUrl(); ?>"><?php _e('查看我的站点'); ?></a></li>
                        <?php else: ?>
                        <li><a href="<?php $options->siteUrl(); ?>"><?php _e('查看我的站点'); ?></a></li>
                        <?php endif; ?>
                    </ol>
                    <p><button type="submit" class="btn primary"><?php _e('让我直接开始使用吧 &raquo;'); ?></button></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
