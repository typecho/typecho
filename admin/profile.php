<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Stat');
?>

<div class="main">
    <div class="body body-950">
        <?php include 'page-title.php'; ?>
        <div class="container typecho-page-main">
            <div class="column-16 suffix typecho-content-panel">
                <h4>
                <?php echo '<img class="avatar" src="http://www.gravatar.com/avatar/' . md5($user->mail) . '?s=50&r=X' .
                '&d=" alt="' . $user->screenName . '" width="50" height="50" />'; ?>
                <?php $user->name(); ?><cite>(<?php $user->screenName(); ?>)</cite>
                </h4>
                <p><?php _e('目前有 <em>%s</em> 篇 Blog,并有 <em>%s</em> 条关于你的评论在已设定的 <em>%s</em> 个分类中.', 
                $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?></p>
                <p><?php
                if ($user->logged > 0) {
                    _e('最后登录: %s', Typecho_I18n::dateWord($user->logged  + $options->timezone, $options->gmtTime + $options->timezone));
                }
                ?></p>
                <?php if($user->pass('contributor', true)): ?>
                <h3 id="writing-option"><?php _e('撰写设置'); ?></h3>
                <?php Typecho_Widget::widget('Widget_Users_Profile')->optionsForm()->render(); ?>
                <?php endif; ?>
                <?php Typecho_Widget::widget('Widget_Users_Profile')->personalFormList(); ?>
                <h3 id="change-password"><?php _e('设置密码'); ?></h3>
                <?php Typecho_Widget::widget('Widget_Users_Profile')->passwordForm()->render(); ?>
            </div>
            <div class="column-08 typecho-mini-panel typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                <?php Typecho_Widget::widget('Widget_Users_Profile')->profileForm()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
Typecho_Plugin::factory('admin/profile.php')->bottom();
include 'footer.php';
?>
