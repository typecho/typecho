<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
?>
<main class="main">
    <div class="container typecho-dashboard">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="welcome-board col-12 fs-6 mb-4" role="main">
                <p><?php _e('目前有 <strong>%s</strong> 篇文章, 并有 <strong>%s</strong> 条关于你的评论在 <strong>%s</strong> 个分类中.',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?>
                    <br><?php _e('点击下面的链接快速开始:'); ?></p>

                <ul id="start-link" class="list-inline">
                    <?php if ($user->pass('contributor', true)): ?>
                        <li class="list-inline-item me-3"><a href="<?php $options->adminUrl('write-post.php'); ?>"><?php _e('撰写新文章'); ?></a></li>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->waitingCommentsNum > 0): ?>
                            <li class="list-inline-item me-3">
                                <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核的评论'); ?></a>
                                <span class="balloon"><?php $stat->waitingCommentsNum(); ?></span>
                            </li>
                        <?php elseif ($stat->myWaitingCommentsNum > 0): ?>
                            <li class="list-inline-item me-3">
                                <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核评论'); ?></a>
                                <span class="balloon"><?php $stat->myWaitingCommentsNum(); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->spamCommentsNum > 0): ?>
                            <li class="list-inline-item me-3">
                                <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                                <span class="balloon"><?php $stat->spamCommentsNum(); ?></span>
                            </li>
                        <?php elseif ($stat->mySpamCommentsNum > 0): ?>
                            <li class="list-inline-item me-3">
                                <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                                <span class="balloon"><?php $stat->mySpamCommentsNum(); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($user->pass('administrator', true)): ?>
                            <li class="list-inline-item me-3"><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('更换外观'); ?></a></li>
                            <li class="list-inline-item me-3"><a href="<?php $options->adminUrl('plugins.php'); ?>"><?php _e('插件管理'); ?></a></li>
                            <li class="list-inline-item"><a href="<?php $options->adminUrl('options-general.php'); ?>"><?php _e('系统设置'); ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-lg-4 mb-3" role="complementary">
                <section class="latest-link">
                    <h3 class="fs-6"><?php _e('最近发布的文章'); ?></h3>
                    <?php \Widget\Contents\Post\Recent::alloc('pageSize=6')->to($posts); ?>
                    <ul>
                        <?php if ($posts->have()): ?>
                            <?php while ($posts->next()): ?>
                                <li>
                                    <a href="<?php $posts->permalink(); ?>" class="title"><?php $posts->title(); ?></a>
                                    <span><?php $posts->date(); ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><em><?php _e('暂时没有文章'); ?></em></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-lg-4 mb-3" role="complementary">
                <section class="latest-link">
                    <h3 class="fs-6"><?php _e('最近得到的回复'); ?></h3>
                    <ul>
                        <?php \Widget\Comments\Recent::alloc('pageSize=6')->to($comments); ?>
                        <?php if ($comments->have()): ?>
                            <?php while ($comments->next()): ?>
                                <li>
                                    <a href="<?php $comments->permalink(); ?>"
                                       class="title"><?php $comments->author(false); ?></a>:
                                    <?php $comments->excerpt(50, '...'); ?>
                                    <span><?php $comments->date(); ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><?php _e('暂时没有回复'); ?></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-lg-4" role="complementary">
                <section class="latest-link">
                    <h3 class="fs-6"><?php _e('官方最新日志'); ?></h3>
                    <div id="typecho-message">
                        <ul>
                            <li><?php _e('读取中...'); ?></li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script>
    $(document).ready(function () {
        var ul = $('#typecho-message ul'), cache = window.sessionStorage,
            html = cache ? cache.getItem('feed') : '',
            update = cache ? cache.getItem('update') : '';

        if (!!html) {
            ul.html(html);
        } else {
            html = '';
            $.get('<?php $options->index('/action/ajax?do=feed'); ?>', function (o) {
                o = o.slice(0, 6);
                for (var i = 0; i < o.length; i++) {
                    var item = o[i];
                    html += '<li><a href="' + item.link + '" target="_blank">' + item.title
                        + '</a><span>' + item.date + '</span></li>';
                }

                ul.html(html);
                cache.setItem('feed', html);
            }, 'json');
        }

        function applyUpdate(update) {
            if (update.available) {
                $('<div class="update-check message error"><p>'
                    + '<?php _e('您当前使用的版本是 %s'); ?>'.replace('%s', update.current) + '<br />'
                    + '<strong><a href="' + update.link + '" target="_blank">'
                    + '<?php _e('官方最新版本是 %s'); ?>'.replace('%s', update.latest) + '</a></strong></p></div>')
                    .insertAfter('.typecho-page-title').effect('highlight');
            }
        }

        if (!!update) {
            applyUpdate($.parseJSON(update));
        } else {
            $.get('<?php $options->index('/action/ajax?do=checkVersion'); ?>', function (o, status, resp) {
                applyUpdate(o);
                cache.setItem('update', resp.responseText);
            }, 'json');
        }
    });

</script>
<?php include 'footer.php'; ?>
