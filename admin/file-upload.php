<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php
if (isset($post) || isset($page)) {
    $cid = isset($post) ? $post->cid : $page->cid;
    
    if ($cid) {
        Typecho_Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $cid)->to($attachment);
    } else {
        Typecho_Widget::widget('Widget_Contents_Attachment_Unattached')->to($attachment);
    }
}
?>

<div id="upload-panel" class="p">
    <?php _e('将要上传的文件拖放到这里 或者 %s选择文件上传%s', '<a href="###" class="upload-file">', '</a>'); ?>
    <ul id="file-list">
    <?php while ($attachment->next()): ?>
        <li data-cid="<?php $attachment->cid(); ?>" data-url="<?php echo $attachment->attachment->url; ?>" data-image="<?php echo $attachment->attachment->isImage ? 1 : 0; ?>"><input type="hidden" name="attachment[]" value="<?php $attachment->cid(); ?>" />
            <a class="insert" title="<?php _e('点击插入文件'); ?>" href="###"><?php $attachment->title(); ?></a>
            <?php echo number_format(ceil($attachment->attachment->size / 1024)); ?> Kb
            <a class="file" target="_blank" href="<?php $options->adminUrl('media.php?cid=' . $attachment->cid); ?>" title="<?php _e('编辑'); ?>"><i class="i-edit"></i></a>
            <a href="###" class="delete" title="<?php _e('删除'); ?>"><i class="i-delete"></i></a></li>
    <?php endwhile; ?>
    </ul>
</div>

