<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Stat');
?>

<?php Typecho_Widget::widget('Widget_Contents_Attachment_Admin')->to($attachments); ?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                
                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                            <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                                <li><a lang="<?php _e('你确认要删除这些文件吗?'); ?>" href="<?php $security->index('/action/contents-attachment-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            </ul>
                            <button class="btn btn-s btn-warn btn-operate" href="<?php $security->index('/action/contents-attachment-edit?do=clear'); ?>" lang="<?php _e('您确认要清理未归档的文件吗?'); ?>"><?php _e('清理未归档文件'); ?></button>
                            </div>
                        </div>
                        <div class="search" role="search">
                            <?php if ('' != $request->keywords): ?>
                            <a href="<?php $options->adminUrl('manage-medias.php'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                            <?php endif; ?>
                            <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>"<?php if ('' == $request->keywords): ?> onclick="value='';name='keywords';" <?php else: ?> name="keywords"<?php endif; ?>/>
                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                        </div>
                    </form>
                </div><!-- end .typecho-list-operate -->
            
                <form method="post" name="manage_medias" class="operate-form">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table draggable">
                        <colgroup>
                            <col width="20"/>
                            <col width="6%"/>
                            <col width="30%"/>
                            <col width=""/>
                            <col width="30%"/>
                            <col width="16%"/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th> </th>
                                <th> </th>
                                <th><?php _e('文件名'); ?></th>
                                <th><?php _e('上传者'); ?></th>
                                <th><?php _e('所属文章'); ?></th>
                                <th><?php _e('发布日期'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php if($attachments->have()): ?>
                            <?php while($attachments->next()): ?>
                            <?php $mime = Typecho_Common::mimeIconType($attachments->attachment->mime); ?>
                            <tr id="<?php $attachments->theId(); ?>">
                                <td><input type="checkbox" value="<?php $attachments->cid(); ?>" name="cid[]"/></td>
                                <td><a href="<?php $options->adminUrl('manage-comments.php?cid=' . $attachments->cid); ?>" class="balloon-button size-<?php echo Typecho_Common::splitByCount($attachments->commentsNum, 1, 10, 20, 50, 100); ?>"><?php $attachments->commentsNum(); ?></a></td>
                                <td>
                                <i class="mime-<?php echo $mime; ?>"></i>
                                <a href="<?php $options->adminUrl('media.php?cid=' . $attachments->cid); ?>"><?php $attachments->title(); ?></a>
                                <a href="<?php $attachments->permalink(); ?>" title="<?php _e('浏览 %s', $attachments->title); ?>"><i class="i-exlink"></i></a>
                                </td>
                                <td><?php $attachments->author(); ?></td>
                                <td>
                                <?php if ($attachments->parentPost->cid): ?>
                                <a href="<?php $options->adminUrl('write-' . (0 === strpos($attachments->parentPost->type, 'post') ? 'post' : 'page') . '.php?cid=' . $attachments->parentPost->cid); ?>"><?php $attachments->parentPost->title(); ?></a>
                                <?php else: ?>
                                <span class="description"><?php _e('未归档'); ?></span>
                                <?php endif; ?>
                                </td>
                                <td><?php $attachments->dateWord(); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                            	<td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何文件'); ?></h6></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table><!-- end .typecho-list-table -->
                </div><!-- end .typecho-table-wrap -->
                </form><!-- end .operate-form -->

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些文件吗?'); ?>" href="<?php $security->index('/action/contents-attachment-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                            <button class="btn btn-s btn-warn btn-operate" href="<?php $security->index('/action/contents-attachment-edit?do=clear'); ?>" lang="<?php _e('您确认要清理未归档的文件吗?'); ?>"><?php _e('清理未归档文件'); ?></button>
                        </div>
                        <?php if($attachments->have()): ?>
                        <ul class="typecho-pager">
                            <?php $attachments->pageNav(); ?>
                        </ul>
                        <?php endif; ?>  
                    </form>
                </div><!-- end .typecho-list-operate -->
                
            </div>
        </div><!-- end .typecho-page-main -->
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
include 'footer.php';
?>
