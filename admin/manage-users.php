<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                            <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                                <li><a lang="<?php _e('你确认要删除这些用户吗?'); ?>" href="<?php $security->index('/action/users-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            </ul>
                            </div>  
                        </div>
                        <div class="search" role="search">
                            <?php if ('' != $request->keywords): ?>
                            <a href="<?php $options->adminUrl('manage-users.php'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                            <?php endif; ?>
                            <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>" name="keywords" />
                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                        </div>
                    </form>
                </div><!-- end .typecho-list-operate -->

                <form method="post" name="manage_users" class="operate-form">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="20"/>
                            <col width="6%"/>
                            <col width="30%"/>
                            <col width=""/>
                            <col width="25%"/>
                            <col width="15%"/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th> </th>
                                <th> </th>
                                <th><?php _e('用户名'); ?></th>
                                <th><?php _e('昵称'); ?></th>
                                <th><?php _e('电子邮件'); ?></th>
                                <th><?php _e('用户组'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php Typecho_Widget::widget('Widget_Users_Admin')->to($users); ?>
                            <?php while($users->next()): ?>
                            <tr id="user-<?php $users->uid(); ?>">
                                <td><input type="checkbox" value="<?php $users->uid(); ?>" name="uid[]"/></td>
                                <td><a href="<?php $options->adminUrl('manage-posts.php?uid=' . $users->uid); ?>" class="balloon-button left size-<?php echo Typecho_Common::splitByCount($users->postsNum, 1, 10, 20, 50, 100); ?>"><?php $users->postsNum(); ?></a></td>
                                <td><a href="<?php $options->adminUrl('user.php?uid=' . $users->uid); ?>"><?php $users->name(); ?></a>
                                <a href="<?php $users->permalink(); ?>" title="<?php _e('浏览 %s', $users->screenName); ?>"><i class="i-exlink"></i></a>
                                </td>
                                <td><?php $users->screenName(); ?></td>
                                <td><?php if($users->mail): ?><a href="mailto:<?php $users->mail(); ?>"><?php $users->mail(); ?></a><?php else: _e('暂无'); endif; ?></td>
                                <td><?php switch ($users->group) {
                                    case 'administrator':
                                        _e('管理员');
                                        break;
                                    case 'editor':
                                        _e('编辑');
                                        break;
                                    case 'contributor':
                                        _e('贡献者');
                                        break;
                                    case 'subscriber':
                                        _e('关注者');
                                        break;
                                    case 'visitor':
                                        _e('访问者');
                                        break;
                                    default:
                                        break;
                                } ?></td>
                            </tr>
                            <?php endwhile; ?>
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
                                <li><a lang="<?php _e('你确认要删除这些用户吗?'); ?>" href="<?php $security->index('/action/users-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            </ul>
                            </div>  
                        </div>
                        <?php if($users->have()): ?>
                        <ul class="typecho-pager">
                            <?php $users->pageNav(); ?>
                        </ul>
                        <?php endif; ?>
                    </form>
                </div><!-- end .typecho-list-operate -->
            </div><!-- end .typecho-list -->
        </div><!-- end .typecho-page-main -->
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
include 'footer.php';
?>
