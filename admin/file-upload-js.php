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
    // 文件上传控件
    $(".upload-file").click(function() {
        if($('input[type=file]').val()) {
            return true;
        }
        $("input[type=file]").click();
        return false;
    });
});
</script>

<script type="text/javascript" src="<?php $options->adminUrl('javascript/swfupload/swfupload.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript" src="<?php $options->adminUrl('javascript/swfupload/swfupload.queue.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript" src="<?php $options->adminUrl('javascript/swfupload/swfupload.cookies.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript">
    var deleteAttachment = function (cid, el) {
    
        var _title = $(el).getParent('li').getElement('strong');
        
        if (!confirm("<?php _e('你确认删除附件 %s 吗?'); ?>".replace("%s", _title.get('text').trim()))) {
            return;
        }

        _title.addClass('delete');
        
        new Request.JSON({
            method : 'post',
            url : '<?php $options->index('/action/contents-attachment-edit'); ?>',
            onComplete : function (result) {
                if (200 == result.code) {
                    $(el).getParent('li').destroy();
                } else {
                    _title.removeClass('delete');
                    alert('<?php _e('删除失败'); ?>');
                }
            }
        }).send('do=delete&cid=' + cid);
    };

    (function () {

        window.addEvent('domready', function() {
            var _inited = false;
            
            //begin parent tabshow
            $(document).getElement('#upload-panel').addEvent('tabShow', function () {
            
                if (_inited) {
                    return;
                }
                _inited = true;
                
                var swfuploadLoaded = function () {
                    $(document).getElement('#upload-panel .button')
                    .set('html', '<?php _e('上传文件'); ?> <small style="font-weight:normal">(<?php echo function_exists('ini_get') ? ini_get('upload_max_filesize') : 0 ; ?>)</small>');
                };
            
                var fileDialogComplete = function (numFilesSelected, numFilesQueued) {
                    try {
                        this.startUpload();
                    } catch (ex)  {
                        this.debug(ex);
                    }
                };
            
                var uploadStart = function (file) {
                    var _el = new Element('li', {
                        'class' : 'upload-progress-item clearfix',
                        'id'    : file.id,
                        'text'  : file.name
                    });
                    
                    _el.inject($(document).getElement('ul.upload-progress'), 'top');
                };
                
                var uploadSuccess = function (file, serverData) {
                    var _el = $(document).getElement('#' + file.id);
                    var _result = JSON.decode(serverData);
                    
                    _el.set('html', '<strong>' + file.name + 
                    '<input type="hidden" name="attachment[]" value="' + _result.cid + '" /></strong>' + 
                    '<small><span class="insert"><?php _e('插入'); ?></span>' +
                    ' , <span class="delete"><?php _e('删除'); ?></span></small>');
                    _el.set('tween', {duration: 1500});
                    
                    _el.setStyles({
                        'background-image' : 'none',
                        'background-color' : '#D3DBB3'
                    });
                    
                    _el.tween('background-color', '#D3DBB3', '#FFFFFF');
                    
                    var _insertBtn = _el.getElement('.insert');
                    if (_result.isImage) {
                        _insertBtn.addEvent('click', function () {
                            insertImageToEditor(_result.title, _result.url, _result.permalink);
                        });
                    } else {
                        _insertBtn.addEvent('click', function () {
                            insertLinkToEditor(_result.title, _result.url, _result.permalink);
                        });
                    }
                    
                    var _deleteBtn = _el.getElement('.delete');
                    _deleteBtn.addEvent('click', function () {
                        deleteAttachment(_result.cid, this);
                    });
                };
                
                var uploadComplete = function (file) {
                    //console.dir(file);
                };
                
                var uploadError = function (file, errorCode, message) {
                    var _el = $(document).getElement('#' + file.id);
                    var _fx = new Fx.Morph(_el, {
                        duration: 3000,
                        transition: Fx.Transitions.Sine.easeOut
                    });
                    _el.set('html', '<strong>' + file.name + ' <?php _e('上传失败'); ?></strong>');
                    _el.setStyles({
                        'background-image' : 'none',
                        'color' : '#FFFFFF',
                        'background-color' : '#CC0000'
                    });
                    
                    _fx.addEvent('complete', function () {
                        _el.destroy();
                    });
                    
                    _fx.start({'opacity': [1, 0]});
                };
                
                var uploadProgress = function (file, bytesLoaded, bytesTotal) {
                    var _el = $(document).getElement('#' + file.id);
                    var percent = Math.ceil((1 - (bytesLoaded / bytesTotal)) * _el.getSize().x);
                    _el.setStyle('background-position', '-' + percent + 'px 0');
                };
            
                var swfu, _size = $(document).getElement('.typecho-list-operate a.button').getCoordinates(),
                settings = {
                    flash_url : "<?php $options->adminUrl('javascript/swfupload/swfupload.swf'); ?>",
                    upload_url: "<?php $options->index('/action/upload'); ?>",
                    <?php if (isset($fileParentContent)): ?>
                    post_params: {"cid" : <?php $fileParentContent->cid(); ?>},
                    <?php endif; ?>
                    file_size_limit : "<?php $val = function_exists('ini_get') ? trim(ini_get('upload_max_filesize')) : 0;
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

        echo $val;
                    ?> byte",
                    file_types : "<?php
    $attachmentTypes = $options->allowedAttachmentTypes;
    $attachmentTypesCount = count($attachmentTypes);
    for ($i = 0; $i < $attachmentTypesCount; $i ++) {
        echo '*.' . $attachmentTypes[$i];
        if ($i < $attachmentTypesCount - 1) {
            echo ';';
        }
    }
?>",
                    file_types_description : "<?php _e('所有文件'); ?>",
                    file_upload_limit : 0,
                    file_queue_limit : 0,
                    debug: false,
                    
                    //Handle Settings
                    file_dialog_complete_handler : fileDialogComplete,
                    upload_start_handler : uploadStart,
                    upload_progress_handler : uploadProgress,
                    upload_success_handler : uploadSuccess,
                    queue_complete_handler : uploadComplete,
                    upload_error_handler : uploadError,
                    swfupload_loaded_handler : swfuploadLoaded,
                    
                    // Button Settings
                    button_placeholder_id : "swfu-placeholder",
                    button_height: 25,
                    button_text: '',
                    button_text_style: '',
                    button_text_left_padding: 14,
                    button_text_top_padding: 0,
                    button_width: _size.width,
                    button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
                    button_cursor: SWFUpload.CURSOR.HAND
                };

                swfu = new SWFUpload(settings);
                
            });
            //end parent tabshow
        });
    })();
</script>
