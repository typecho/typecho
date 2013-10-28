<?php
include 'common.php';
include 'header.php';
include 'menu.php';
Typecho_Widget::widget('Widget_Contents_Post_Edit')->to($post);
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main typecho-post-area">
            <form action="<?php $options->index('/action/contents-post-edit'); ?>" method="post" name="write_post">
                <div class="col-mb-12 col-tb-9">
                    <?php if ($post->draft && $post->draft['cid'] != $post->cid): ?>
                    <?php $postModifyDate = new Typecho_Date($post->draft['modified']); ?>
                    <cite class="edit-draft-notice"><?php _e('你正在编辑的是保存于 %s 的草稿, 你也可以 <a href="%s">删除它</a>', $postModifyDate->word(), 
                    Typecho_Common::url('/action/contents-post-edit?do=deleteDraft&cid=' . $post->cid, $options->index)); ?></cite>
                    <?php endif; ?>

                    <p class="title"><input type="text" id="title" name="title" autocomplete="off" value="<?php echo htmlspecialchars($post->title); ?>" placeholder="<?php _e('标题'); ?>" class="w-100 text title" /></p>
                    <?php $permalink = Typecho_Common::url($options->routingTable['post']['url'], $options->index);
                    list ($scheme, $permalink) = explode(':', $permalink, 2);
                    $permalink = ltrim($permalink, '/');
                    $permalink = preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $permalink);
                    if ($post->have()) {
                        $permalink = str_replace(array(
                            '{cid}', '{category}', '{year}', '{month}', '{day}'
                        ), array(
                            $post->cid, $post->category, $post->year, $post->month, $post->day
                        ), $permalink);
                    }
                    $input = '<input type="text" id="slug" name="slug" autocomplete="off" value="' . htmlspecialchars($post->slug) . '" class="mono" />';
                    ?>
                    <p class="mono url-slug"><?php echo preg_replace("/\{slug\}/i", $input, $permalink); ?></p>

                    <p>
                        <textarea style="height: <?php $options->editorSize(); ?>px" autocomplete="off" id="text" name="text" class="w-100 mono"><?php echo htmlspecialchars($post->text); ?></textarea>
                    </p>

                    <?php include 'file-upload.php'; ?>

                    <?php Typecho_Plugin::factory('admin/write-post.php')->content($post); ?>
                    <p class="submit clearfix">
                        <span id="auto-save-message" class="left"></span>
                        <span class="right">
                            <input type="hidden" name="cid" value="<?php $post->cid(); ?>" />
                            <button type="submit" name="do" value="save" id="btn-save"><?php _e('保存草稿'); ?></button>
                            <button type="submit" name="do" value="publish" class="primary" id="btn-submit"><?php _e('发布文章'); ?></button>
                            <?php if ($options->markdown && (!$post->have() || $post->isMarkdown)): ?>
                            <input type="hidden" name="markdown" value="1" />
                            <?php endif; ?>
                        </span>
                    </p>
                </div>
                <div class="col-mb-12 col-tb-3">
                    <section class="typecho-post-option">
                        <label for="date" class="typecho-label"><?php _e('发布日期'); ?></label>
                        <p><input class="typecho-date w-100" type="text" name="date" id="date" value="<?php $post->have() ? $post->date('Y-m-d H:i') : ''; ?>" /></p>
                    </section>

                    <section class="typecho-post-option category-option">
                        <label class="typecho-label"><?php _e('分类'); ?></label>
                        <?php Typecho_Widget::widget('Widget_Metas_Category_List')->to($category); ?>
                        <ul>
                            <?php
                            if ($post->have()) {
                                $categories = Typecho_Common::arrayFlatten($post->categories, 'mid');
                            } else {
                                $categories = array();
                            }
                            ?>
                            <?php while($category->next()): ?>
                            <li><input type="checkbox" id="category-<?php $category->mid(); ?>" value="<?php $category->mid(); ?>" name="category[]" <?php if(in_array($category->mid, $categories)): ?>checked="true"<?php endif; ?>/>
                            <label for="category-<?php $category->mid(); ?>"><?php $category->name(); ?></label></li>
                            <?php endwhile; ?>
                        </ul>
                    </section>

                    <section class="typecho-post-option">
                        <label for="token-input-tags" class="typecho-label"><?php _e('标签'); ?></label>
                        <p><input id="tags" name="tags" type="text" value="<?php $post->tags(',', false); ?>" class="w-100 text" /></p>
                    </section>

                    <?php Typecho_Plugin::factory('admin/write-post.php')->option($post); ?>

                    <div id="advance-panel">
                        <?php if($user->pass('editor', true)): ?>
                        <section class="typecho-post-option visibility-option">
                            <label class="typecho-label"><?php _e('公开度'); ?></label>
                            <ul>
                                <?php if ($user->pass('editor', true)): ?>
                                <li><input id="publish" value="publish" name="visibility" type="radio"<?php if (($post->status == 'publish' && !$post->password) || !$post->status) { ?> checked="true"<?php } ?> /> <label for="publish"><?php _e('公开'); ?></label></li>
                                <li><input id="password" value="password" name="visibility" type="radio"<?php if ($post->password) { ?> checked="true"<?php } ?> /> <label for="password">密码保护 <input type="text" id="post-password" name="password" class="text-s" value="<?php $post->password(); ?>" size="16" /></label></li>
                                <li><input id="private" value="private" name="visibility" type="radio"<?php if ($post->status == 'private') { ?> checked="true"<?php } ?> /> <label for="private">私密</label></li>
                                <?php endif; ?>
                                <li><input id="waiting" value="waiting" name="visibility" type="radio"<?php if (!$user->pass('editor', true) || $post->status == 'waiting') { ?> checked="true"<?php } ?> /> <label for="waiting">待审核</label></li>
                            </ul>
                        </section>
                        <?php endif; ?>

                        <section class="typecho-post-option">
                            <label for="trackback" class="typecho-label"><?php _e('引用通告'); ?></label>
                            <p><textarea id="trackback" class="w-100 mono" name="trackback" rows="3"></textarea></p>
                            <p class="description"><?php _e('每一行一个引用地址, 用回车隔开'); ?></p>
                        </section>

                        <section class="typecho-post-option allow-option">
                            <label class="typecho-label"><?php _e('权限控制'); ?></label>
                            <ul>
                                <li><input id="allowComment" name="allowComment" type="checkbox" value="1" <?php if($post->allow('comment')): ?>checked="true"<?php endif; ?> />
                                <label for="allowComment"><?php _e('允许评论'); ?></label></li>
                                <li><input id="allowPing" name="allowPing" type="checkbox" value="1" <?php if($post->allow('ping')): ?>checked="true"<?php endif; ?> />
                                <label for="allowPing"><?php _e('允许被引用'); ?></label></li>
                                <li><input id="allowFeed" name="allowFeed" type="checkbox" value="1" <?php if($post->allow('feed')): ?>checked="true"<?php endif; ?> />
                                <label for="allowFeed"><?php _e('允许在聚合中出现'); ?></label></li>
                            </ul>
                        </section>
                        <?php Typecho_Plugin::factory('admin/write-post.php')->advanceOption($post); ?>
                    </div><!-- end #advance-panel -->
                    <a href="###" id="advance-panel-btn"><?php _e('高级选项'); ?></a>

                    <?php if($post->have()): ?>
                    <?php $modified = new Typecho_Date($post->modified); ?>
                    <section class="typecho-post-option">
                        <p class="description">
                            <br>&mdash;<br>
                            <?php _e('本文由 <a href="%s">%s</a> 撰写',
                            Typecho_Common::url('manage-posts.php?uid=' . $post->author->uid, $options->adminUrl), $post->author->screenName); ?><br>
                            <?php _e('最后更新于 %s', $modified->word()); ?>
                        </p>
                    </section>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
include 'write-js.php';

Typecho_Plugin::factory('admin/write-post.php')->trigger($plugged)->richEditor($post);
if (!$plugged) {
    include 'editor-js.php';
}

include 'file-upload-js.php';
Typecho_Plugin::factory('admin/write-post.php')->bottom($post);
include 'footer.php';
?>
