<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Stat');
?>
<div class="main">
    <div class="container typecho-dashboard">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 col-tb-3 typecho-dashboard-nav">
                <p class="intro"><?php _e('欢迎使用 Typecho, 您可以使用下面的链接开始您的 Blog 之旅:'); ?></p>
            
                <ul class="intro-link">
                    <?php if($user->pass('contributor', true)): ?>
                    <li><a href="<?php $options->adminUrl('write-post.php'); ?>"><?php _e('撰写新文章'); ?></a></li>
                    <?php if($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->waitingCommentsNum > 0): ?> 
                        <li><a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核的评论'); ?></a>
                        <span class="balloon"><?php $stat->waitingCommentsNum(); ?></span>
                        </li>
                    <?php elseif($stat->myWaitingCommentsNum > 0): ?>
                        <li><a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核的评论'); ?></a>
                        <span class="balloon"><?php $stat->myWaitingCommentsNum(); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->spamCommentsNum > 0): ?> 
                        <li><a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                        <span class="balloon"><?php $stat->spamCommentsNum(); ?></span>
                        </li>
                    <?php elseif($stat->mySpamCommentsNum > 0): ?>
                        <li><a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                        <span class="balloon"><?php $stat->mySpamCommentsNum(); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if($user->pass('administrator', true)): ?>
                    <li><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('更换外观模板'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('options-general.php'); ?>"><?php _e('修改系统设置'); ?></a></li>
                    <?php endif; ?>
                    <?php endif; ?>
                    <!--<li><a href="<?php $options->adminUrl('profile.php'); ?>"><?php _e('更新我的资料'); ?></a></li>-->
                </ul>
            
                <h3><?php _e('统计信息'); ?></h3>
                <div class="status">
                    <p><?php _e('目前有 <em>%s</em> 篇 Blog, 并有 <em>%s</em> 条关于你的评论在已设定的 <em>%s</em> 个分类中.', 
                    $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?></p>
                    
                    <p><?php 
                    if ($user->logged > 0) {
                        _e('最后登录: %s', Typecho_I18n::dateWord($user->logged  + $options->timezone, $options->gmtTime + $options->timezone));
                    }
                    ?></p>
                </div>
            </div>

            <div class="col-mb-12 col-tb-6 typecho-dashboard-main">
                <section>
                    <h3><?php _e('最近发表的文章'); ?></h3>
                    <?php Typecho_Widget::widget('Widget_Contents_Post_Recent', 'pageSize=7')->to($posts); ?>
                    <ul>
                    <?php if($posts->have()): ?>
                    <?php while($posts->next()): ?>
                        <li>
                            <a href="<?php $posts->permalink(); ?>" class="title"><?php $posts->title(); ?></a>
                            <span>- <?php $posts->dateWord(); ?></span>
                        </li>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <li><em><?php _e('暂时没有文章'); ?></em></li>
                    <?php endif; ?>
                    </ul>
                </section>

            	<section>
                    <h3><?php _e('最新得到的回复'); ?></h3>
                    <ul>
                        <?php Typecho_Widget::widget('Widget_Comments_Recent', 'pageSize=7')->to($comments); ?>
                        <?php if($comments->have()): ?>
                        <?php while($comments->next()): ?>
                        <li>
                            <a href="<?php $comments->permalink(); ?>" class="title"><?php $comments->title(); ?></a>
                            <span>- <?php $comments->dateWord(); ?>, <?php $comments->author(true); ?></span>
                        </li>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <li><em><?php _e('暂时没有回复'); ?></em></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-mb-12 col-tb-3 typecho-dashboard-nav">
                <?php $version = Typecho_Cookie::get('__typecho_check_version'); ?>
                <?php if ($version && $version['available']): ?>
                <div class="update-check">
                    <p>
                        <?php _e('您当前使用的版本是'); ?> <?php echo $version['current']; ?><br>
                        <strong><a href="<?php echo $version['link']; ?>"><?php _e('官方最新版本是'); ?> <?php echo $version['latest']; ?></a></strong>
                    </p>
                </div>
                <?php endif; ?>
                <h3><?php _e('官方消息'); ?></h3>
                <div id="typecho-message" class="intro-link">
                    <ul>
                        <li><?php _e('读取中...'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script>
$(document).ready(function () {
    var ul = $('.intro-link ul'), html = $.cookie('__typecho_feed'), update = $.cookie('__typecho_update');

    if (!!html) {
        ul.html(html);
    } else {
        html = '';
        $.get('<?php $options->index('/action/ajax?do=feed'); ?>', function (o) {
            for (var i = 0; i < o.length; i ++) {
                var item = o[i];
                html += '<li><a href="' + item.link + '" target="_blank">' + item.title 
                    + '</a> <span>' + item.date + '</span></li>';
            }

            ul.html(html);
            $.cookie('__typecho_feed', html);
        });
    }

    function applyUpdate(update) {
        if (update.available) {
            $('<div class="update-check"><p>' 
                + '<?php _e('您当前使用的版本是 %s'); ?>'.replace('%s', update.current) + '<br />'
                + '<strong><a href="' + update.link + '" target="_blank">' 
                + '<?php _e('官方最新版本是 %s'); ?>'.replace('%s', update.latest) + '</a></strong></p></div>')
            .prependTo('.typecho-dashboard-nav').effect('highlight');
        }
    }

    if (!!update) {
        applyUpdate($.parseJSON(update));
    } else {
        update = '';
        $.get('<?php $options->index('/action/ajax?do=checkVersion'); ?>', function (o, status, resp) {
            applyUpdate(o);
            $.cookie('__typecho_update', resp.responseText);
        });
    }
});

</script>
<?php include 'footer.php'; ?>
