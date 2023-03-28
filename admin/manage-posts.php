<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
$posts = \Widget\Contents\Post\Admin::alloc();
$isAllPosts = ('on' == $request->get('__typecho_all_posts') || 'on' == \Typecho\Cookie::get('__typecho_all_posts'));
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <div class="clearfix">
                    <ul class="typecho-option-tabs right">
                        <?php if ($user->pass('editor', true) && !isset($request->uid)): ?>
                            <li class="<?php if ($isAllPosts): ?> current<?php endif; ?>"><a
                                    href="<?php echo $request->makeUriByRequest('__typecho_all_posts=on&page=1'); ?>"><?php _e('所有'); ?></a>
                            </li>
                            <li class="<?php if (!$isAllPosts): ?> current<?php endif; ?>"><a
                                    href="<?php echo $request->makeUriByRequest('__typecho_all_posts=off&page=1'); ?>"><?php _e('我的'); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="typecho-option-tabs">
                        <li<?php if (!isset($request->status) || 'all' == $request->get('status')): ?> class="current"<?php endif; ?>>
                            <a href="<?php $options->adminUrl('manage-posts.php'
                                . (isset($request->uid) ? '?uid=' . $request->filter('encode')->uid : '')); ?>"><?php _e('可用'); ?></a>
                        </li>
                        <li<?php if ('waiting' == $request->get('status')): ?> class="current"<?php endif; ?>><a
                                href="<?php $options->adminUrl('manage-posts.php?status=waiting'
                                    . (isset($request->uid) ? '&uid=' . $request->filter('encode')->uid : '')); ?>"><?php _e('待审核'); ?>
                                <?php if (!$isAllPosts && $stat->myWaitingPostsNum > 0 && !isset($request->uid)): ?>
                                    <span class="balloon"><?php $stat->myWaitingPostsNum(); ?></span>
                                <?php elseif ($isAllPosts && $stat->waitingPostsNum > 0 && !isset($request->uid)): ?>
                                    <span class="balloon"><?php $stat->waitingPostsNum(); ?></span>
                                <?php elseif (isset($request->uid) && $stat->currentWaitingPostsNum > 0): ?>
                                    <span class="balloon"><?php $stat->currentWaitingPostsNum(); ?></span>
                                <?php endif; ?>
                            </a></li>
                        <li<?php if ('draft' == $request->get('status')): ?> class="current"<?php endif; ?>><a
                                href="<?php $options->adminUrl('manage-posts.php?status=draft'
                                    . (isset($request->uid) ? '&uid=' . $request->filter('encode')->uid : '')); ?>"><?php _e('草稿'); ?>
                                <?php if (!$isAllPosts && $stat->myDraftPostsNum > 0 && !isset($request->uid)): ?>
                                    <span class="balloon"><?php $stat->myDraftPostsNum(); ?></span>
                                <?php elseif ($isAllPosts && $stat->draftPostsNum > 0 && !isset($request->uid)): ?>
                                    <span class="balloon"><?php $stat->draftPostsNum(); ?></span>
                                <?php elseif (isset($request->uid) && $stat->currentDraftPostsNum > 0): ?>
                                    <span class="balloon"><?php $stat->currentDraftPostsNum(); ?></span>
                                <?php endif; ?>
                            </a></li>
                    </ul>
                </div>

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox"
                                                                                   class="typecho-table-select-all"/></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i
                                        class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i
                                        class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些文章吗?'); ?>"
                                           href="<?php $security->index('/action/contents-post-edit?do=delete'); ?>"><?php _e('删除'); ?></a>
                                    </li>
                                    <?php if ($user->pass('editor', true)): ?>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=publish'); ?>"><?php _e('标记为<strong>%s</strong>', _t('公开')); ?></a>
                                        </li>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=waiting'); ?>"><?php _e('标记为<strong>%s</strong>', _t('待审核')); ?></a>
                                        </li>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=hidden'); ?>"><?php _e('标记为<strong>%s</strong>', _t('隐藏')); ?></a>
                                        </li>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=private'); ?>"><?php _e('标记为<strong>%s</strong>', _t('私密')); ?></a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="search" role="search">
                            <?php if ('' != $request->keywords || '' != $request->category): ?>
                                <a href="<?php $options->adminUrl('manage-posts.php'
                                    . (isset($request->status) || isset($request->uid) ? '?' .
                                        (isset($request->status) ? 'status=' . $request->filter('encode')->status : '') .
                                        (isset($request->uid) ? (isset($request->status) ? '&' : '') . 'uid=' . $request->filter('encode')->uid : '') : '')); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                            <?php endif; ?>
                            <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>"
                                   value="<?php echo $request->filter('html')->keywords; ?>" name="keywords"/>
                            <select name="category">
                                <option value=""><?php _e('所有分类'); ?></option>
                                <?php \Widget\Metas\Category\Rows::alloc()->to($category); ?>
                                <?php while ($category->next()): ?>
                                    <option
                                        value="<?php $category->mid(); ?>"<?php if ($request->get('category') == $category->mid): ?> selected="true"<?php endif; ?>><?php $category->name(); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                            <?php if (isset($request->uid)): ?>
                                <input type="hidden" value="<?php echo $request->filter('html')->uid; ?>"
                                       name="uid"/>
                            <?php endif; ?>
                            <?php if (isset($request->status)): ?>
                                <input type="hidden" value="<?php echo $request->filter('html')->status; ?>"
                                       name="status"/>
                            <?php endif; ?>
                        </div>
                    </form>
                </div><!-- end .typecho-list-operate -->

                <form method="post" name="manage_posts" class="operate-form">
                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="20" class="kit-hidden-mb"/>
                                <col width="6%" class="kit-hidden-mb"/>
                                <col width="45%"/>
                                <col width="" class="kit-hidden-mb"/>
                                <col width="18%" class="kit-hidden-mb"/>
                                <col width="16%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th class="kit-hidden-mb"></th>
                                <th class="kit-hidden-mb"></th>
                                <th><?php _e('标题'); ?></th>
                                <th class="kit-hidden-mb"><?php _e('作者'); ?></th>
                                <th class="kit-hidden-mb"><?php _e('分类'); ?></th>
                                <th><?php _e('日期'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($posts->have()): ?>
                                <?php while ($posts->next()): ?>
                                    <tr id="<?php $posts->theId(); ?>">
                                        <td class="kit-hidden-mb"><input type="checkbox" value="<?php $posts->cid(); ?>"
                                                                         name="cid[]"/></td>
                                        <td class="kit-hidden-mb"><a
                                                href="<?php $options->adminUrl('manage-comments.php?cid=' . ($posts->parentId ? $posts->parentId : $posts->cid)); ?>"
                                                class="balloon-button size-<?php echo \Typecho\Common::splitByCount($posts->commentsNum, 1, 10, 20, 50, 100); ?>"
                                                title="<?php $posts->commentsNum(); ?> <?php _e('评论'); ?>"><?php $posts->commentsNum(); ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php $options->adminUrl('write-post.php?cid=' . $posts->cid); ?>"><?php $posts->title(); ?></a>
                                            <?php
                                            if ($posts->hasSaved || 'post_draft' == $posts->type) {
                                                echo '<em class="status">' . _t('草稿') . '</em>';
                                            }

                                            if ('hidden' == $posts->status) {
                                                echo '<em class="status">' . _t('隐藏') . '</em>';
                                            } elseif ('waiting' == $posts->status) {
                                                echo '<em class="status">' . _t('待审核') . '</em>';
                                            } elseif ('private' == $posts->status) {
                                                echo '<em class="status">' . _t('私密') . '</em>';
                                            } elseif ($posts->password) {
                                                echo '<em class="status">' . _t('密码保护') . '</em>';
                                            }
                                            ?>
                                            <a href="<?php $options->adminUrl('write-post.php?cid=' . $posts->cid); ?>"
                                               title="<?php _e('编辑 %s', htmlspecialchars($posts->title)); ?>"><i
                                                    class="i-edit"></i></a>
                                            <?php if ('post_draft' != $posts->type): ?>
                                                <a href="<?php $posts->permalink(); ?>"
                                                   title="<?php _e('浏览 %s', htmlspecialchars($posts->title)); ?>"><i
                                                        class="i-exlink"></i></a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="kit-hidden-mb"><a
                                                href="<?php $options->adminUrl('manage-posts.php?__typecho_all_posts=off&uid=' . $posts->author->uid); ?>"><?php $posts->author(); ?></a>
                                        </td>
                                        <td class="kit-hidden-mb"><?php $categories = $posts->categories;
                                            $length = count($categories); ?>
                                            <?php foreach ($categories as $key => $val): ?>
                                                <?php echo '<a href="';
                                                $options->adminUrl('manage-posts.php?category=' . $val['mid']
                                                    . (isset($request->uid) ? '&uid=' . $request->filter('encode')->uid : '')
                                                    . (isset($request->status) ? '&status=' . $request->filter('encode')->status : ''));
                                                echo '">' . $val['name'] . '</a>' . ($key < $length - 1 ? ', ' : ''); ?>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <?php if ($posts->hasSaved): ?>
                                                <span class="description">
                                <?php $modifyDate = new \Typecho\Date($posts->modified); ?>
                                <?php _e('保存于 %s', $modifyDate->word()); ?>
                                </span>
                                            <?php else: ?>
                                                <?php $posts->dateWord(); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何文章'); ?></h6>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form><!-- end .operate-form -->

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox"
                                                                                   class="typecho-table-select-all"/></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i
                                        class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i
                                        class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些文章吗?'); ?>"
                                           href="<?php $security->index('/action/contents-post-edit?do=delete'); ?>"><?php _e('删除'); ?></a>
                                    </li>
                                    <?php if ($user->pass('editor', true)): ?>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=publish'); ?>"><?php _e('标记为<strong>%s</strong>', _t('公开')); ?></a>
                                        </li>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=waiting'); ?>"><?php _e('标记为<strong>%s</strong>', _t('待审核')); ?></a>
                                        </li>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=hidden'); ?>"><?php _e('标记为<strong>%s</strong>', _t('隐藏')); ?></a>
                                        </li>
                                        <li>
                                            <a href="<?php $security->index('/action/contents-post-edit?do=mark&status=private'); ?>"><?php _e('标记为<strong>%s</strong>', _t('私密')); ?></a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <?php if ($posts->have()): ?>
                            <ul class="typecho-pager">
                                <?php $posts->pageNav(); ?>
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
