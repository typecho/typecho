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
            <div class="column-24 start-01 typecho-list">
                <div class="typecho-list-operate">
                <form method="get">
                    <div class="operate">
                        <input type="checkbox" class="typecho-table-select-all" />
                    <div class="btn-group btn-drop">
                    <button class="dropdown-toggle" type="button" href="">选中项 &nbsp;<i class="icon-caret-down"></i></button>
                    <ul class="dropdown-menu">
                        <li><a lang="<?php _e('你确认要删除这些页面吗?'); ?>" href="<?php $options->index('/action/contents-post-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                    </ul>
                    </div>
                    </div>
                    <div class="search">
                    <?php if ('' != $request->keywords): ?>
                    <a href="<?php $options->adminUrl('manage-pages.php'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                    <?php endif; ?>
                    <input type="text" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>" name="keywords" />
                    <button type="submit"><?php _e('筛选'); ?></button>
                    </div>
                </form>
                </div>
            
                <form method="post" name="manage_pages" class="operate-form" action="<?php $options->index('/action/contents-page-edit'); ?>">
                <table class="typecho-list-table draggable">
                    <colgroup>
                        <col width="25"/>
                        <col width="50"/>
                        <col width="295"/>
                        <col width="60"/>
                        <col width="30"/>
                        <col width="180"/>
                        <col width="120"/>
                        <col width="150"/>
                    </colgroup>
                    <thead>
                        <tr>
                            <th> </th>
                            <th> </th>
                            <th><?php _e('标题'); ?></th>
                            <th> </th>
                            <th> </th>
                            <th><?php _e('缩略名'); ?></th>
                            <th><?php _e('作者'); ?></th>
                            <th><?php _e('日期'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    	<?php Typecho_Widget::widget('Widget_Contents_Page_Admin')->to($pages); ?>
                    	<?php if($pages->have()): ?>
                        <?php while($pages->next()): ?>
                        <tr<?php $pages->alt(' class="even"', ''); ?> id="<?php $pages->theId(); ?>">
                            <td><input type="checkbox" value="<?php $pages->cid(); ?>" name="cid[]"/></td>
                            <td><a href="<?php $options->adminUrl('manage-comments.php?cid=' . $pages->cid); ?>" class="balloon-button right size-<?php echo Typecho_Common::splitByCount($pages->commentsNum, 1, 10, 20, 50, 100); ?>"><?php $pages->commentsNum(); ?></a></td>
                            <td<?php if ('draft' != $pages->status): ?> colspan="2"<?php endif; ?>><a href="<?php $options->adminUrl('write-page.php?cid=' . $pages->cid); ?>"><?php $pages->title(); ?></a>
                            <?php if ('draft' == $pages->status): ?>
                            </td>
                            <td>
                            <span class="balloon right"><?php _e('草稿'); ?></span>
                            <?php endif; ?></td>
                            </td>
                            <td>
                            <?php if ('publish' == $pages->status): ?>
                            <a class="right hidden-by-mouse" href="<?php $pages->permalink(); ?>"><img src="<?php $options->adminUrl('images/link.png'); ?>" title="<?php _e('浏览 %s', $pages->title); ?>" width="16" height="16" alt="view" /></a>
                            <?php endif; ?>
                            </td>
                            <td><?php $pages->slug(); ?></td>
                            <td><?php $pages->author(); ?></td>
                            <td>
                            <?php if ($pages->hasSaved): ?>
                            <span class="description">
                            <?php $modifyDate = new Typecho_Date($pages->modified); ?>
                            <?php _e('保存于 %s', $modifyDate->word()); ?>
                            </span>
                            <?php else: ?>
                            <?php $pages->dateWord(); ?>
                            <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr class="even">
                        	<td colspan="8"><h6 class="typecho-list-table-title"><?php _e('没有任何页面'); ?></h6></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <input type="hidden" name="do" value="delete" />
                </form>
            
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>

<?php if(!isset($request->status) || 'publish' == $request->get('status')): ?>
<script type="text/javascript">
    (function () {
        window.addEvent('domready', function() {
            Typecho.Table.dragStop = function (item, result) {
                var _r = new Request.JSON({
                    url: '<?php $options->index('/action/contents-page-edit'); ?>'
                }).send(result + '&do=sort');
            };
        });
    })();
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
