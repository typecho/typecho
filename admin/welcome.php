<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="column-22 start-02">
                <div class="message success typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                    <form action="<?php $options->adminUrl(); ?>" method="get">
                    <h6><?php _e('欢迎您使用 "%s" 管理后台!', $options->title); ?></h6>
                    <blockquote>
                    <ul>
                        <li><strong><?php _e('快速导航'); ?></strong></li>
                        <li><strong>1.</strong> <a class="operate-delete" href="<?php $options->adminUrl('profile.php#change-password'); ?>"><?php _e('强烈建议更改你的默认密码'); ?></a></li>
                        <?php if($user->pass('contributor', true)): ?>
                        <li><strong>2.</strong> <a href="<?php $options->adminUrl('write-post.php'); ?>"><?php _e('撰写第一篇日志'); ?></a></li>
                        <li><strong>3.</strong> <a href="<?php $options->siteUrl(); ?>"><?php _e('查看我的站点'); ?></a></li>
                        <?php else: ?>
                        <li><strong>2.</strong> <a href="<?php $options->siteUrl(); ?>"><?php _e('查看我的站点'); ?></a></li>
                        <?php endif; ?>
                    </ul>
                    </blockquote>
                    <br />
                    <p><button type="submit"><?php _e('让我直接开始使用吧 &raquo;'); ?></button></p>
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
