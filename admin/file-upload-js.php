<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
if (isset($post) && $post instanceof Typecho_Widget && $post->have()) {
    $fileParentContent = $post;
} else if (isset($page) && $page instanceof Typecho_Widget && $page->have()) {
    $fileParentContent = $page;
}
?>

<script>
$(document).ready(function() {
    $('.upload-file').fileUpload({
        url         :   '<?php $options->index('/action/upload' 
            . (isset($fileParentContent) ? '?cid=' . $fileParentContent->cid : '')); ?>',
        types       :   '<?php
    $attachmentTypes = $options->allowedAttachmentTypes;
    $attachmentTypesCount = count($attachmentTypes);
    for ($i = 0; $i < $attachmentTypesCount; $i ++) {
        echo '*.' . $attachmentTypes[$i];
        if ($i < $attachmentTypesCount - 1) {
            echo ';';
        }
    }
?>',
        typesError  :   '<?php _e('附件 %s 的类型不被支持'); ?>',
        onUpload    :   function (file, id) {
            $('<li id="' + id + '" class="loading">'
                + file + '</li>').prependTo('#file-list');
        },
        onError     :   function (id, word) {
            $('#' + id).remove();
            alert('<?php _e('文件上传失败'); ?>');
        },
        onComplete  :   function (id, url, data) {
            var li = $('#' + id).removeClass('loading').data('cid', data.cid)
                .data('url', data.url)
                .data('image', data.isImage)
                .html('<input type="hidden" name="attachment[]" value="' + data.cid + '" />'
                    + '<a class="file" target="_blank" href="<?php $options->adminUrl('media.php'); ?>?cid=' 
                    + data.cid + '">' + data.title + '</a> ' + data.bits
                    + ' <a class="insert" href="#">' + <?php _e('插入'); ?> + '</a>'
                    + ' <a class="delete" href="#">&times;</a>')
                .effect('highlight', '#AACB36', 1000);
            
            attachInsertEvent(li);
            attachDeleteEvent(li);
        },
    });

    $('#file-list li').each(function () {
        attachInsertEvent(this);
        attachDeleteEvent(this);
    });

    function attachInsertEvent (el) {
        $('.insert', el).click(function () {
            var p = $(this).parents('li');
            Typecho.insertFileToEditor(p.data('url'), p.data('image'));
            return false;
        });
    }

    function attachDeleteEvent (el) {
        var file = $('a.file', el).text();
        $('.delete', el).click(function () {
            if (confirm('<?php _e('确认要删除附件 %s 吗?'); ?>'.replace('%s', file))) {
                var cid = $(this).parents('li').data('cid');
                $.post('<?php $options->index('/action/contents-attachment-edit'); ?>',
                    {'do' : 'delete', 'cid' : cid},
                    function () {
                        el.remove();
                    });
            }

            return false;
        });
    }
});
</script>

