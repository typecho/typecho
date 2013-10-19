<?php
include 'common.php';
include 'header.php';
include 'menu.php';

Typecho_Widget::widget('Widget_Contents_Attachment_Edit')->to($attachment);
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 col-tb-8">
                <?php if ($attachment->attachment->isImage): ?>
                <p><img src="<?php $attachment->attachment->url(); ?>" alt="<?php $attachment->attachment->name(); ?>" class="typecho-attachment-photo" /></p>
                <?php endif; ?>
                
                <p>
                    <?php $mime = Typecho_Common::mimeIconType($attachment->attachment->mime); ?>
                    <i class="mime-<?php echo $mime; ?>"></i>
                    <a href=""><strong><?php $attachment->attachment->name(); ?></strong></a>
                    <span><?php echo number_format(ceil($attachment->attachment->size / 1024)); ?> Kb</span>
                </p>

                <p>
                    <input id="attachment-url" type="text" class="mono w-100" value="<?php $attachment->attachment->url(); ?>" readonly />
                </p>

                <div id="upload-panel" class="p">
                    将要替换的文件拖放到这里 或者 <a href="###" class="upload-file">选择替换文件</a>
                    <ul id="file-list"></ul>
                </div>
            </div>
            <div class="col-mb-12 col-tb-4 edit-media">
                <?php $attachment->form()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<script src="<?php $options->adminUrl('js/filedrop.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript">
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

    $('#attachment-url').click(function () {
        $(this).select();
    });
    
    $('.operate-delete').click(function () {
        var t = $(this), href = t.attr('href');

        if (confirm(t.attr('lang'))) {
            window.location.href = href;
        }

        return false;
    });

    function fileUploadStart (file, id) {
        $('<li id="' + id + '" class="loading">'
            + file + '</li>').prependTo('#file-list');
    }

    function fileUploadComplete (id, url, data) {
        $('#' + id).html('<?php _e('文件 %s 已经替换'); ?>'.replace('%s', data.title))
        .effect('highlight', 1000, function () {
            $(this).remove();
            window.location.reload();
        });
    }

    $('.upload-file').fileUpload({
        url         :   '<?php $options->index('/action/upload?do=modify&cid=' . $attachment->cid); ?>',
        types       :   ['.<?php $attachment->attachment->type(); ?>'],
        typesError  :   '<?php _e('文件 %s 的类型与要替换的原文件不一致'); ?>',
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
        allowedfileextensions   :   ['.<?php $attachment->attachment->type(); ?>'],

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

        maxfiles        :   1,

        error: function(err, file) {
            switch(err) {
                case 'BrowserNotSupported':
                    alert('<?php _e('浏览器不支持拖拽上传'); ?>');
                    break;
                case 'TooManyFiles':
                    alert('<?php _e('一次只能上传一个文件'); ?>');
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
});
</script>
<?php
include 'footer.php';
?>
