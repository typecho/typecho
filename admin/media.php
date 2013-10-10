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
            <div class="col-8 suffix">
                <div class="typecho-attachment-photo-box">
                    <?php if ($attachment->attachment->isImage): ?>
                    <img src="<?php $attachment->attachment->url(); ?>" alt="<?php $attachment->attachment->name(); ?>" />
                    <?php endif; ?>
                    
                    <div class="description">
                        <ul>
                            <?php $mime = Typecho_Common::mimeIconType($attachment->attachment->mime); ?>
                            <li><span class="typecho-mime typecho-mime-<?php echo $mime; ?>"></span><strong><?php $attachment->attachment->name(); ?></strong> <small><?php echo number_format(ceil($attachment->attachment->size / 1024)); ?> Kb</small></li>
                            <li><input id="attachment-url" type="text" readonly class="text" value="<?php $attachment->attachment->url(); ?>" />
                            <button id="exchange" disabled><?php _e('替换'); ?></button>
                            <span id="swfu"><span id="swfu-placeholder"></span></span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-4 typecho-mini-panel typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                <?php $attachment->form()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<script type="text/javascript" src="<?php $options->adminUrl('javascript/swfupload/swfupload.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript" src="<?php $options->adminUrl('javascript/swfupload/swfupload.queue.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript">
    (function () {
        window.addEvent('domready', function() {
            
            $(document).getElement('.typecho-attachment-photo-box .description input').addEvent('click', function () {
                this.select();
            });
            
            var swfuploadLoaded = function () {
                var btn = $(document)
                .getElement('.typecho-attachment-photo-box button#exchange');
                
                var obj = $(document)
                .getElement('.typecho-attachment-photo-box .description ul li #swfu');
                
                offset = obj.getCoordinates(btn);
                obj.setStyles({
                    'width': btn.getSize().x,
                    'height': btn.getSize().y,
                    'left': 0 - offset.left,
                    'top': 0 - offset.top
                });
                
                btn.removeAttribute('disabled');
            };
        
            var fileDialogComplete = function (numFilesSelected, numFilesQueued) {
                try {
                    this.startUpload();
                } catch (ex)  {
                    this.debug(ex);
                }
            };
        
            var uploadStart = function (file) {
                $(document)
                .getElement('.typecho-attachment-photo-box button#exchange')
                .set('html', '<?php _e('上传中'); ?>')
                .setAttribute('disabled', '');
            };
            
            var uploadSuccess = function (file, serverData) {
                var _el = $(document).getElement('#attachment-url');
                var _result = JSON.decode(serverData);
                
                _el.set('tween', {duration: 1500});
                
                _el.setStyles({
                    'background-position' : '-1000px 0',
                    'background-color' : '#D3DBB3'
                });
                
                <?php if ($attachment->attachment->isImage): ?>
                var _img = new Image(), _date = new Date();
                
                _img.src = _result.url + (_result.url.indexOf('?') > 0 ? '&' : '?') + '__rds=' + _date.toUTCString();
                _img.alt = _result.title;
                
                $(document).getElement('.typecho-attachment-photo-box img').destroy();
                $(_img).inject($(document).getElement('.typecho-attachment-photo-box'), 'top');
                <?php endif; ?>
                
                $(document).getElement('.typecho-attachment-photo-box .description small')
                .set('html', Math.ceil(_result.size / 1024) + ' Kb');
                
                _el.tween('background-color', '#D3DBB3', '#EEEEEE');
            };
            
            var uploadComplete = function (file) {
                $(document)
                .getElement('.typecho-attachment-photo-box button#exchange')
                .set('html', '<?php _e('替换'); ?>')
                .removeAttribute('disabled');
            };
            
            var uploadError = function (file, errorCode, message) {
                var _el = $(document).getElement('#attachment-url');
                var _fx = new Fx.Tween(_el, {duration: 3000});
                
                _fx.start('background-color', '#CC0000', '#EEEEEE');
            };
            
            var uploadProgress = function (file, bytesLoaded, bytesTotal) {
                var _el = $(document).getElement('#attachment-url');
                var percent = Math.ceil((1 - (bytesLoaded / bytesTotal)) * _el.getSize().x);
                _el.setStyle('background-position', '-' + percent + 'px 0');
            };
            
            var swfu, _size = $(document).getElement('.typecho-attachment-photo-box button#exchange').getCoordinates(),
            settings = {
                flash_url : "<?php $options->adminUrl('javascript/swfupload/swfupload.swf'); ?>",
                upload_url: "<?php $options->index('/action/upload?do=modify&cid=' . $attachment->cid); ?>",
                post_params: {"__typecho_uid" : "<?php echo Typecho_Cookie::get('__typecho_uid'); ?>", 
                "__typecho_authCode" : "<?php echo addslashes(Typecho_Cookie::get('__typecho_authCode')); ?>"},
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
                file_types : "<?php echo '' == $attachment->attachment->type ? $attachment->attachment->name :
                '*.' . $attachment->attachment->type; ?>",
                file_types_description : "<?php _e('所有文件'); ?>",
                file_upload_limit : 0,
                file_queue_limit : 1,
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
                button_height: _size.height,
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
    })();
</script>
<?php
include 'footer.php';
?>
