<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
if (isset($post) && $post instanceof Typecho_Widget && $post->have()) {
    $fileParentContent = $post;
} else if (isset($page) && $page instanceof Typecho_Widget && $page->have()) {
    $fileParentContent = $page;
}
?>

<script src="<?php $options->adminUrl('js/filedrop.js?v=' . $suffixVersion); ?>"></script>
<script>
$(document).ready(function() {
    var errorWord = '<?php $val = function_exists('ini_get') ? trim(ini_get('upload_max_filesize')) : 0;
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        $val = number_format(ceil($val / (1024 *1024)));
        _e('文件上传失败, 请确认文件尺寸没有超过 %s 并且服务器文件目录可以写入', "{$val}Mb"); ?>',
        loading = $('<img src="<?php $options->adminUrl('img/ajax-loader.gif'); ?>" style="display:none" />')
            .appendTo(document.body);

    function fileUploadStart (file, id) {
        $('<li id="' + id + '" class="loading">'
            + file + '</li>').prependTo('#file-list');
    }

    function fileUploadComplete (id, url, data) {
        var li = $('#' + id).removeClass('loading').data('cid', data.cid)
            .data('url', data.url)
            .data('image', data.isImage)
            .html('<input type="hidden" name="attachment[]" value="' + data.cid + '" />'
                + '<a class="insert" target="_blank" href="###" title="<?php _e('点击插入文件'); ?>">' + data.title + '</a> ' + data.bytes
                + ' <a class="file" target="_blank" href="<?php $options->adminUrl('media.php'); ?>?cid=' 
                + data.cid + '" title="<?php _e('编辑'); ?>"><i class="i-edit"></i></a>'
                + ' <a class="delete" href="###" title="<?php _e('删除'); ?>"><i class="i-delete"></i></a>')
            .effect('highlight', 1000);
            
        attachInsertEvent(li);
        attachDeleteEvent(li);
    }

    $('.upload-file').fileUpload({
        url         :   '<?php $options->index('/action/upload' 
            . (isset($fileParentContent) ? '?cid=' . $fileParentContent->cid : '')); ?>',
        types       :   <?php
    $attachmenttypes = $options->allowedattachmenttypes;
    $attachmenttypescount = count($attachmenttypes);
    $types = array();

    for ($i = 0; $i < $attachmenttypescount; $i ++) {
        $types[] = '.' . $attachmenttypes[$i];
    }

    echo json_encode($types);
?>,
        typesError  :   '<?php _e('文件 %s 的类型不被支持'); ?>',
        onUpload    :   fileUploadStart,
        onError     :   function (id) {
            $('#' + id).remove();
            alert(errorWord);
        },
        onComplete  :   fileUploadComplete
    });

    $('#upload-panel').filedrop({
        url             :   '<?php $options->index('/action/upload' 
            . (isset($fileParentContent) ? '?cid=' . $fileParentContent->cid : '')); ?>',
        allowedfileextensions   :   <?php
    $attachmenttypes = $options->allowedattachmenttypes;
    $attachmenttypescount = count($attachmenttypes);
    $types = array();

    for ($i = 0; $i < $attachmenttypescount; $i ++) {
        $types[] = '.' . $attachmenttypes[$i];
    }

    echo json_encode($types);
?>,

        maxfilesize     :   <?php 
        $val = function_exists('ini_get') ? trim(ini_get('upload_max_filesize')) : 0;
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        echo ceil($val / (1024 * 1024));
        ?>,

        queuefiles      :   5,

        error: function(err, file) {
            switch(err) {
                case 'BrowserNotSupported':
                    alert('<?php _e('浏览器不支持拖拽上传'); ?>');
                    break;
                case 'TooManyFiles':
                    alert('<?php _e('一次上传的文件不能多于%d个', 25); ?>');
                    break;
                case 'FileTooLarge':
                    alert('<?php $val = function_exists('ini_get') ? trim(ini_get('upload_max_filesize')) : 0;
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        $val = number_format(ceil($val / (1024 *1024)));
        _e('文件尺寸不能超过 %s', "{$val}Mb"); ?>');
                    break;
                case 'FileTypeNotAllowed':
                    // The file type is not in the specified list 'allowedfiletypes'
                    break;
                case 'FileExtensionNotAllowed':
                    alert('<?php _e('文件 %s 的类型不被支持'); ?>'.replace('%s', file.name));
                    break;
                default:
                    break;
            }
        },
        
        
        dragOver : function () {
            $(this).addClass('drag');
        },

        dragLeave : function () {
            $(this).removeClass('drag');
        },

        drop : function () {
            $(this).removeClass('drag');
        },

        uploadOpened   :   function (i, file, len) {
            fileUploadStart(file.name, 'drag-' + i);
        },

        uploadFinished  :   function (i, file, response) {
            fileUploadComplete('drag-' + i, response[0], response[1]);
        }
    });

    function attachInsertEvent (el) {
        $('.insert', el).click(function () {
            var t = $(this), p = t.parents('li');
            Typecho.insertFileToEditor(t.text(), p.data('url'), p.data('image'));
            return false;
        });
    }

    function attachDeleteEvent (el) {
        var file = $('a.insert', el).text();
        $('.delete', el).click(function () {
            if (confirm('<?php _e('确认要删除文件 %s 吗?'); ?>'.replace('%s', file))) {
                var cid = $(this).parents('li').data('cid');
                $.post('<?php $options->index('/action/contents-attachment-edit'); ?>',
                    {'do' : 'delete', 'cid' : cid},
                    function () {
                        $(el).fadeOut(function () {
                            $(this).remove();
                        });
                    });
            }

            return false;
        });
    }

    $('#file-list li').each(function () {
        attachInsertEvent(this);
        attachDeleteEvent(this);
    });
});
</script>

