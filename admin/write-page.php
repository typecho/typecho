<?php
require 'common.php';
require 'header.php';
require 'menu.php';

use Widget\Contents\Page\Edit as PageEdit;

$pageEdit = PageEdit::alloc()->to($page);
?>

<div class="main">
    <div class="body container">
        <?php require 'page-title.php'; ?>
        <div class="row typecho-page-main typecho-post-area" role="form">
            <form action="<?= $security->index('/action/contents-page-edit'); ?>" method="post" name="write_page">
                <div class="col-mb-12 col-tb-9" role="main">
                    <?php if ($page->draft): ?>
                        <?php if ($page->draft['cid'] != $page->cid): ?>
                            <?php $pageModifyDate = new \Typecho\Date($page->draft['modified']); ?>
                            <cite class="edit-draft-notice">
                                <?php printf(
                                    '当前正在编辑的是保存于%s的草稿, 你可以<a href="%s">删除它</a>',
                                    $pageModifyDate->word(),
                                    $security->getIndex('/action/contents-page-edit?do=deleteDraft&cid=' . $page->cid)
                                ); ?>
                            </cite>
                        <?php else: ?>
                            <cite class="edit-draft-notice"><?= '当前正在编辑的是未发布的草稿'; ?></cite>
                        <?php endif; ?>
                        <input name="draft" type="hidden" value="<?= $page->draft['cid']; ?>"/>
                    <?php endif; ?>

                    <p class="title">
                        <label for="title" class="sr-only"><?= _e('标题'); ?></label>
                        <input type="text" id="title" name="title" autocomplete="off" value="<?= $page->title(); ?>"
                               placeholder="<?= _e('标题'); ?>" class="w-100 text title"/>
                    </p>
                    <?php
                    $permalink = \Typecho\Common::url($options->routingTable['page']['url'], $options->index);
                    [$scheme, $permalink] = explode(':', $permalink, 2);
                    $permalink = ltrim($permalink, '/');
                    $permalink = preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $permalink);
                    if ($page->have()) {
                        $permalink = str_replace('{cid}', $page->cid, $permalink);
                    }
                    $input = '<input type="text" id="slug" name="slug" autocomplete="off" value="' . htmlspecialchars($page->slug ?? '') . '" class="mono" />';
                    ?>
                    <p class="mono url-slug">
                        <label for="slug" class="sr-only"><?= _e('网址缩略名'); ?></label>
                        <?= preg_replace("/\{slug\}/i", $input, $permalink); ?>
                    </p>
                    <p>
                        <label for="text" class="sr-only"><?= _e('页面内容'); ?></label>
                        <textarea style="height: <?= $options->editorSize(); ?>px" autocomplete="off" id="text"
                                  name="text" class="w-100 mono"><?= htmlspecialchars($page->text ?? ''); ?></textarea>
                    </p>

                    <?php require 'custom-fields.php'; ?>
                    <p class="submit clearfix">
                        <span class="left">
                            <button type="button" id="btn-cancel-preview" class="btn"><i
                                    class="i-caret-left"></i> <?= _e('取消预览'); ?></button>
                        </span>
                        <span class="right">
                            <input type="hidden" name="cid" value="<?= $page->cid(); ?>"/>
                            <button type="button" id="btn-preview" class="btn"><i
                                    class="i-exlink"></i> <?= _e('预览页面'); ?></button>
                            <button type="submit" name="do" value="save" id="btn-save"
                                    class="btn"><?= _e('保存草稿'); ?></button>
                            <button type="submit" name="do" value="publish" class="btn primary"
                                    id="btn-submit"><?= _e('发布页面'); ?></button>
                            <?php if ($options->markdown && (!$page->have() || $page->isMarkdown)): ?>
                                <input type="hidden" name="markdown" value="1"/>
                            <?php endif; ?>
                        </span>
                    </p>

                    <?php \Typecho\Plugin::factory('admin/write-page.php')->content($page); ?>
                </div>
                <div id="edit-secondary" class="col-mb-12 col-tb-3" role="complementary">
                    <!-- ... Remaining code ... -->
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require 'copyright.php';
require 'common-js.php';
require 'form-js.php';
require 'write-js.php';

\Typecho\Plugin::factory('admin/write-page.php')->trigger($plugged)->richEditor($page);
if (!$plugged) {
    require 'editor-js.php';
}

require 'file-upload-js.php';
require 'custom-fields-js.php';
\Typecho\Plugin::factory('admin/write-page.php')->bottom($page);
require 'footer.php';
?>
