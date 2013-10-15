<?php
include 'common.php';
include 'header.php';
include 'menu.php';
Typecho_Widget::widget('Widget_Contents_Page_Edit')->to($page);
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main typecho-post-option typecho-post-area">
            <form action="<?php $options->index('/action/contents-page-edit'); ?>" method="post" name="write_page">
                <div class="col-9 suffix">
                    <div>
                        <label for="title" class="typecho-label"><?php _e('标题'); ?>
                        <?php if ($page->draft && $page->draft['cid'] != $page->cid): ?>
                        <?php $pageModifyDate = new Typecho_Date($page->draft['modified']); ?>
                        <cite><?php _e('当前正在编辑的是保存于%s的草稿, 你可以<a href="%s">删除它</a>', $pageModifyDate->word(), 
                        Typecho_Common::url('/action/contents-page-edit?do=deleteDraft&cid=' . $page->cid, $options->index)); ?></cite>
                        <?php endif; ?>
                        </label>
                        <p class="title"><input type="text" id="title" name="title" value="<?php echo htmlspecialchars($page->title); ?>" class="text title" /></p>
                        <label for="text" class="typecho-label"><?php _e('内容'); ?><cite id="auto-save-message"></cite></label>
                        <p><textarea style="height: <?php $options->editorSize(); ?>px" autocomplete="off" id="text" name="text"><?php echo htmlspecialchars($page->text); ?></textarea></p>
                        <?php Typecho_Plugin::factory('admin/write-page.php')->content($page); ?>
                        <p class="submit">
                            <span class="left">
                            <span class="typecho-preview-label"><input type="checkbox" name="preview" id="btn-preview" /> <label for="btn-preview"><?php _e('预览内容'); ?></label></span>
                                <span class="advance close" tabindex="0"><?php _e('展开高级选项'); ?></span>
                                <span class="attach" tabindex="0"><?php _e('展开附件'); ?></span>
                            </span>
                            <span class="right">
                                <input type="hidden" name="cid" value="<?php $page->cid(); ?>" />
                                <button type="submit" name="do" value="save" id="btn-save"><?php _e('保存草稿'); ?></button>
                                <button type="submit" name="do" value="publish" class="primary" id="btn-submit"><?php _e('发布页面'); ?></button>
                            </span>
                        </p>
                    </div>
                        
                    <ul id="advance-panel" class="typecho-post-option col-9">
                        <li class="col-9">
                            <div class="col-12">
                                <label for="order" class="typecho-label"><?php _e('页面顺序'); ?></label>
                                <p><input type="text" id="order" name="order" value="<?php $page->order(); ?>" class="mini" /></p>
                                <p class="description"><?php _e('为你的自定义页面设定一个序列值以后, 能够使得它们按此值从小到大排列'); ?></p>
                                <br />
                                <label for="template" class="typecho-label"><?php _e('自定义模板'); ?></label>
                                <p>
                                    <select name="template" id="template">
                                        <option value=""><?php _e('不选择'); ?></option>
                                        <?php $templates = $page->getTemplates(); foreach ($templates as $template => $name): ?>
                                        <option value="<?php echo $template; ?>"<?php if($template == $page->template): ?> selected="true"<?php endif; ?>><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <p class="description"><?php _e('如果你为此页面选择了一个自定义模板, 系统将按照你选择的模板文件展现它'); ?></p>
                                <?php Typecho_Plugin::factory('admin/write-page.php')->advanceOptionLeft($page); ?>
                            </div>
                            <div class="col-3">
                                <label class="typecho-label"><?php _e('权限控制'); ?></label>
                                <ul>
                                    <li><input id="allowComment" name="allowComment" type="checkbox" value="1" <?php if($page->allow('comment')): ?>checked="true"<?php endif; ?> />
                                    <label for="allowComment"><?php _e('允许评论'); ?></label></li>
                                    <li><input id="allowPing" name="allowPing" type="checkbox" value="1" <?php if($page->allow('ping')): ?>checked="true"<?php endif; ?> />
                                    <label for="allowPing"><?php _e('允许被引用'); ?></label></li>
                                    <li><input id="allowFeed" name="allowFeed" type="checkbox" value="1" <?php if($page->allow('feed')): ?>checked="true"<?php endif; ?> />
                                    <label for="allowFeed"><?php _e('允许在聚合中出现'); ?></label></li>
                                    <?php Typecho_Plugin::factory('admin/write-page.php')->advanceOptionRight($page); ?>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    <ul id="upload-panel" class="col-9">
                        <li class="col-9">
                            <?php include 'file-upload.php'; ?>
                        </li>
                    </ul>
                    <div id="typecho-preview-box"></div>
                </div>
                <div class="col-3">
                    <ul class="typecho-post-option">
                        <li>
                            <label for="date" class="typecho-label"><?php _e('日期'); ?></label>
                            <p>
                                <select disabled class="typecho-date" name="month" id="month">
                                    <option value="1" <?php if (1 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('一月'); ?></option>
                                    <option value="2" <?php if (2 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('二月'); ?></option>
                                    <option value="3" <?php if (3 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('三月'); ?></option>
                                    <option value="4" <?php if (4 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('四月'); ?></option>
                                    <option value="5" <?php if (5 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('五月'); ?></option>
                                    <option value="6" <?php if (6 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('六月'); ?></option>
                                    <option value="7" <?php if (7 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('七月'); ?></option>
                                    <option value="8" <?php if (8 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('八月'); ?></option>
                                    <option value="9" <?php if (9 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('九月'); ?></option>
                                    <option value="10" <?php if (10 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('十月'); ?></option>
                                    <option value="11" <?php if (11 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('十一月'); ?></option>
                                    <option value="12" <?php if (12 == $page->date->format('n')): ?>selected="true"<?php endif; ?>><?php _e('十二月'); ?></option>
                                </select>
                                <input disabled class="typecho-date" size="4" maxlength="4" type="text" name="day" id="day" value="<?php $page->date('d'); ?>" />
                                ,
                                <input disabled class="typecho-date" size="4" maxlength="4" type="text" name="year" id="year" value="<?php $page->date('Y'); ?>" />
                                @
                                <input disabled class="typecho-date" size="2" maxlength="2" type="text" name="hour" id="hour" value="<?php $page->date('H'); ?>" />
                                :
                                <input disabled class="typecho-date" size="2" maxlength="2" type="text" name="min" id="min" value="<?php $page->date('i'); ?>" />
                            </p>
                            <p class="description"><?php _e('请选择一个发布日期'); ?></p>
                        </li>
                        <li>
                            <label for="slug" class="typecho-label"><?php _e('缩略名'); ?></label>
                            <p><input type="text" id="slug" name="slug" value="<?php $page->slug(); ?>" class="mini" /></p>
                            <p class="description"><?php _e('为这篇日志自定义链接地址, 有利于搜索引擎收录'); ?></p>
                        </li>
                        <?php Typecho_Plugin::factory('admin/write-page.php')->option($page); ?>
                        <?php if($page->have()): ?>
                        <?php $modified = new Typecho_Date($page->modified); ?>
                        <li>
                            <label class="typecho-label"><?php _e('本页面由 %s 创建', $page->author->screenName); ?></label>
                            <p class="description"><?php _e('最后修改于 %s', $modified->word()); ?></p>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'write-js.php';
include 'file-upload-js.php';

Typecho_Plugin::factory('admin/write-page.php')->bottom($page);
include 'footer.php';
?>
