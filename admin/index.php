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
                <?php $feed = Typecho_Cookie::get('__typecho_feed'); ?>
                <div id="typecho-message" class="intro-link">
                    <ul>
                        <?php if (empty($feed)): ?>
                        <li><?php _e('读取中...'); ?></li>
                        <?php else: ?>
                        <?php $feed = Typecho_Json::decode($feed);
                        foreach ($feed as $item): ?>
                        <?php $item = (array) $item; ?>
                        <li>
                            <a href="<?php echo $item['link']; ?>"><?php echo $item['title']; ?></a>
                            <br><span><?php echo $item['date']; ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
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
    (function () {
        window.addEvent('domready', function() {
            <?php if (!Typecho_Cookie::get('__typecho_feed')): ?>
            var _feedRequest = new Request.JSON({url: '<?php $options->index('/action/ajax'); ?>'}).send("do=feed");
            _feedRequest.addEvent('onSuccess', function (responseJSON) {
                $(document).getElement('#typecho-message ul li').destroy();
                
                if (responseJSON) {
                    responseJSON.each(function (item) {
                        var _li = document.createElement('li');
                        $(_li).set('html', '<a target="_blank" href="' + item.link + '">' + item.title + '</a> - <span class="date">' + item.date + '</span>');
                        var _ul = $(document).getElement('#typecho-message ul');
                        _ul.appendChild(_li);
                    });
                }
            });
            <?php endif; ?>
            
            <?php if ($user->pass('editor', true) && !Typecho_Cookie::get('__typecho_check_version')): ?>
            var _checkVersionRequest = new Request.JSON({url: '<?php $options->index('/action/ajax'); ?>'}).send("do=checkVersion");
            _checkVersionRequest.addEvent('onSuccess', function (responseJSON) {
                if (responseJSON && responseJSON.available) {
                    var _div = document.createElement('div', {
                        'class' : 'update-check typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright',
                        'html'  : '<p class="current"><?php _e('您当前使用的版本是'); ?> <em>' + responseJSON.current + '</em></p>' +
                        '<p class="latest"><a target="_blank" href="' + responseJSON.link + '"><?php _e('官方最新版本是'); ?> <em>' + responseJSON.latest + '</em></a></p>'
                    });
                    
                    $(_div).fade('hide');
                    $(document).getElement('.start-19').insertBefore(_div, $(document).getElement('.start-19 h3'));
                    $(_div).fade('in');
                }
            });
            <?php endif; ?>
        });
    })();
</script>
<?php include 'footer.php'; ?>
