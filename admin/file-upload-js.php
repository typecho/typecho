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
        }
    });
});
</script>

