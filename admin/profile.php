<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Stat');
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 col-tb-9 typecho-content-panel">
                <fieldset>
                    <legend><?php _e('个人资料'); ?></legend>
                    <?php Typecho_Widget::widget('Widget_Users_Profile')->profileForm()->render(); ?>
                </fieldset>

                <?php if($user->pass('contributor', true)): ?>
                <fieldset id="writing-option">
                    <legend><?php _e('撰写设置'); ?></legend>
                    <?php Typecho_Widget::widget('Widget_Users_Profile')->optionsForm()->render(); ?>
                </fieldset>
                <?php endif; ?>

                <fieldset id="change-password">
                    <legend><?php _e('密码修改'); ?></legend>
                    <?php Typecho_Widget::widget('Widget_Users_Profile')->personalFormList(); ?>
                    <?php Typecho_Widget::widget('Widget_Users_Profile')->passwordForm()->render(); ?>
                </fieldset>
                
            </div>
            <div class="col-tb-3">
                <?php echo '<img class="avatar" src="http://www.gravatar.com/avatar/' . md5($user->mail) . '?s=128&r=X' .
                '&d=" alt="' . $user->screenName . '" />'; ?>
                <h2><?php $user->screenName(); ?><br><small><?php $user->name(); ?></small></h2>
                <p><?php _e('目前有 <em>%s</em> 篇 Blog,并有 <em>%s</em> 条关于你的评论在已设定的 <em>%s</em> 个分类中.', 
                $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?></p>
                <p><?php
                if ($user->logged > 0) {
                    _e('最后登录: %s', Typecho_I18n::dateWord($user->logged  + $options->timezone, $options->gmtTime + $options->timezone));
                }
                ?></p>
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
