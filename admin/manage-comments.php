<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Stat');
$comments = Typecho_Widget::widget('Widget_Comments_Admin');
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 typecho-list">
                <ul class="typecho-option-tabs clearfix">
                    <li<?php if(!isset($request->status) || 'approved' == $request->get('status')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-comments.php'
                    . (isset($request->cid) ? '?cid=' . $request->cid : '')); ?>"><?php _e('已通过'); ?></a></li>
                    <li<?php if('waiting' == $request->get('status')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-comments.php?status=waiting'
                    . (isset($request->cid) ? '&cid=' . $request->cid : '')); ?>"><?php _e('待审核'); ?>
                    <?php if('on' != $request->get('__typecho_all_comments') && $stat->myWaitingCommentsNum > 0 && !isset($request->cid)): ?> 
                        <span class="balloon"><?php $stat->myWaitingCommentsNum(); ?></span>
                    <?php elseif('on' == $request->get('__typecho_all_comments') && $stat->waitingCommentsNum > 0 && !isset($request->cid)): ?>
                        <span class="balloon"><?php $stat->waitingCommentsNum(); ?></span>
                    <?php elseif(isset($request->cid) && $stat->currentWaitingCommentsNum > 0): ?>
                        <span class="balloon"><?php $stat->currentWaitingCommentsNum(); ?></span>
                    <?php endif; ?>
                    </a></li>
                    <li<?php if('spam' == $request->get('status')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-comments.php?status=spam'
                    . (isset($request->cid) ? '&cid=' . $request->cid : '')); ?>"><?php _e('垃圾'); ?>
                    <?php if('on' != $request->get('__typecho_all_comments') && $stat->mySpamCommentsNum > 0 && !isset($request->cid)): ?> 
                        <span class="balloon"><?php $stat->mySpamCommentsNum(); ?></span>
                    <?php elseif('on' == $request->get('__typecho_all_comments') && $stat->spamCommentsNum > 0 && !isset($request->cid)): ?>
                        <span class="balloon"><?php $stat->spamCommentsNum(); ?></span>
                    <?php elseif(isset($request->cid) && $stat->currentSpamCommentsNum > 0): ?>
                        <span class="balloon"><?php $stat->currentSpamCommentsNum(); ?></span>
                    <?php endif; ?>
                    </a></li>
                    <?php if($user->pass('editor', true) && !isset($request->cid)): ?>
                        <li class="right<?php if('on' == $request->get('__typecho_all_comments')): ?> current<?php endif; ?>"><a href="<?php echo $request->makeUriByRequest('__typecho_all_comments=on'); ?>"><?php _e('所有'); ?></a></li>
                        <li class="right<?php if('on' != $request->get('__typecho_all_comments')): ?> current<?php endif; ?>"><a href="<?php echo $request->makeUriByRequest('__typecho_all_comments=off'); ?>"><?php _e('我的'); ?></a></li>
                    <?php endif; ?>
                </ul>
            
                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <input type="checkbox" class="typecho-table-select-all" />
                        <div class="btn-group btn-drop">
                        <button class="dropdown-toggle btn-s" type="button" href="">选中项 &nbsp;<i class="icon-caret-down"></i></button>
                        <ul class="dropdown-menu">
                            <li><a href="<?php $options->index('/action/comments-edit?do=approved'); ?>"><?php _e('通过'); ?></a></li>
                            <li><a href="<?php $options->index('/action/comments-edit?do=waiting'); ?>"><?php _e('待审核'); ?></a></li>
                            <li><a href="<?php $options->index('/action/comments-edit?do=spam'); ?>"><?php _e('标记垃圾'); ?></a></li>
                            <li><a lang="<?php _e('你确认要删除这些评论吗?'); ?>" href="<?php $options->index('/action/comments-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            <?php if('spam' == $request->get('status')): ?>
                            <li><a lang="<?php _e('你确认要删除所有垃圾评论吗?'); ?>" href="<?php $options->index('/action/comments-edit?do=delete-spam'); ?>"><?php _e('删除所有垃圾评论'); ?></a></li>
                            <?php endif; ?>
                        </ul>
                        </div>
                        </div>
                        <div class="search">
                        <?php if ('' != $request->keywords || '' != $request->category): ?>
                        <a href="<?php $options->adminUrl('manage-comments.php' 
                        . (isset($request->status) || isset($request->cid) ? '?' .
                        (isset($request->status) ? 'status=' . htmlspecialchars($request->get('status')) : '') .
                        (isset($request->cid) ? (isset($request->status) ? '&' : '') . 'cid=' . htmlspecialchars($request->get('cid')) : '') : '')); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                        <?php endif; ?>
                        <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>"<?php if ('' == $request->keywords): ?> onclick="value='';name='keywords';" <?php else: ?> name="keywords"<?php endif; ?>/>
                        <?php if(isset($request->status)): ?>
                            <input type="hidden" value="<?php echo htmlspecialchars($request->get('status')); ?>" name="status" />
                        <?php endif; ?>
                        <?php if(isset($request->cid)): ?>
                            <input type="hidden" value="<?php echo htmlspecialchars($request->get('cid')); ?>" name="cid" />
                        <?php endif; ?>
                        <button type="submit" class="btn-s"><?php _e('筛选'); ?></button>
                        </div>
                    </form>
                </div>
                
                <form method="post" name="manage_comments" class="operate-form">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="3%"/>
                            <col width="6%" />
                            <col width="20%"/>
                            <col width="71%"/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th> </th>
                                <th><?php _e('作者'); ?></th>
                                <th> </th>
                                <th><?php _e('内容'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($comments->have()): ?>
                        <?php while($comments->next()): ?>
                        <tr id="<?php $comments->theId(); ?>">
                            <td valign="top">
                                <input type="checkbox" value="<?php $comments->coid(); ?>" name="coid[]"/>
                            </td>
                            <td valign="top">
                                <div class="comment-avatar">
                                    <?php $comments->gravatar(40); ?>
                                </div>
                            </td>
                            <td valign="top" class="comment-head">
                                <div class="comment-meta">
                                    <span class="<?php $comments->type(); ?>"></span>
                                    <strong class="comment-author"><?php $comments->author(true); ?></strong>
                                    <?php if($comments->mail): ?>
                                    <br><span><a href="mailto:<?php $comments->mail(); ?>"><?php $comments->mail(); ?></a></span>
                                    <?php endif; ?>
                                    <?php if($comments->ip): ?>
                                    <br><span><?php $comments->ip(); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td valign="top" class="comment-body">
                                <div class="comment-date"><?php $comments->dateWord(); ?> 于 <a href="<?php $comments->permalink(); ?>"><?php $comments->title(); ?></a></div>
                                <div class="comment-content">
                                    <?php $comments->content(); ?>
                                </div> 
                                <div class="comment-action hidden-by-mouse">
                                    <?php if('approved' == $comments->status): ?>
                                    <span class="weak"><?php _e('通过'); ?></span>
                                    <?php else: ?>
                                    <a href="<?php $options->index('/action/comments-edit?do=approved&coid=' . $comments->coid); ?>"><?php _e('通过'); ?></a>
                                    <?php endif; ?>
                                    
                                    <?php if('waiting' == $comments->status): ?>
                                    <span class="weak"><?php _e('待审核'); ?></span>
                                    <?php else: ?>
                                    <a href="<?php $options->index('/action/comments-edit?do=waiting&coid=' . $comments->coid); ?>"><?php _e('待审核'); ?></a>
                                    <?php endif; ?>
                                    
                                    <?php if('spam' == $comments->status): ?>
                                    <span class="weak"><?php _e('垃圾'); ?></span>
                                    <?php else: ?>
                                    <a href="<?php $options->index('/action/comments-edit?do=spam&coid=' . $comments->coid); ?>"><?php _e('垃圾'); ?></a>
                                    <?php endif; ?>
                                    
                                    <a href="#<?php $comments->theId(); ?>" rel="<?php $options->index('/action/comments-edit?do=get&coid=' . $comments->coid); ?>" class="operate-edit"><?php _e('编辑'); ?></a>

                                    <?php if('approved' == $comments->status && 'comment' == $comments->type): ?>
                                    <a href="#<?php $comments->theId(); ?>" rel="<?php $options->index('/action/comments-edit?do=reply&coid=' . $comments->coid); ?>" class="operate-reply"><?php _e('回复'); ?></a>
                                    <?php endif; ?>
                                    
                                    <a lang="<?php _e('你确认要删除%s的评论吗?', htmlspecialchars($comments->author)); ?>" href="<?php $options->index('/action/comments-edit?do=delete&coid=' . $comments->coid); ?>" class="operate-delete"><?php _e('删除'); ?></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <h6 class="typecho-list-table-title"><?php _e('没有评论') ?></h6>
                        </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if(isset($request->cid)): ?>
                    <input type="hidden" value="<?php echo htmlspecialchars($request->get('cid')); ?>" name="cid" />
                    <?php endif; ?>
                </form>

                <?php if($comments->have()): ?>
                <ul class="typecho-pager">
                    <?php $comments->pageNav(); ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>
<script type="text/javascript">
$(document).ready(function () {
    // 记住滚动条
    function rememberScroll () {
        $(window).bind('beforeunload', function () {
            $.cookie('__typecho_comments_scroll', $('body').scrollTop());
        });
    }

    // 自动滚动
    (function () {
        var scroll = $.cookie('__typecho_comments_scroll');

        if (scroll) {
            $.cookie('__typecho_comments_scroll', null);
            $('html, body').scrollTop(scroll);
        }
    })();

    $('.operate-delete').click(function () {
        var t = $(this), href = t.attr('href'), tr = t.parents('tr');

        if (confirm(t.attr('lang'))) {
            tr.fadeOut(function () {
                rememberScroll();
                window.location.href = href;
            });
        }

        return false;
    });

    $('.operate-reply').click(function () {
        var td = $(this).parents('td'), t = $(this);

        if ($('.comment-reply', td).length > 0) {
            $('.comment-reply').remove();
        } else {
            var form = $('<form action="post" action="'
                + t.attr('rel') + '" class="comment-reply">'
                + '<p><textarea name="text" class="w-90" rows="3"></textarea></p>'
                + '<p><button type="submit" class="btn-s primary"><?php _e('提交回复'); ?></button> <button type="button" class="btn-s cancel"><?php _e('取消'); ?></button></p>'
                + '</form>').insertBefore($('.comment-action', td));

            $('.cancel', form).click(function () {
                $(this).parents('.comment-reply').remove();
            });

            $('textarea', form).focus();

            form.submit(function () {
                var t = $(this);
                return false;
            });
        }

        return false;
    });
});
</script>
<?php
include 'footer.php';
?>
