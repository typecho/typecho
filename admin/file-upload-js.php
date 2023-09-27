<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php
$phpMaxFilesize = function_exists('ini_get') ? trim(ini_get('upload_max_filesize')) : '0';

if (preg_match("/^([0-9]+)([a-z]{1,2})?$/i", $phpMaxFilesize, $matches)) {
    $size = intval($matches[1]);
    $unit = $matches[2] ?? 'b';

    $phpMaxFilesize = round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
}
?>

<script>
$(document).ready(function() {
    function updateAttachmentNumber () {
        var btn = $('#tab-files-btn'),
            balloon = $('.balloon', btn),
            count = $('#file-list li .insert').length;

        if (count > 0) {
            if (!balloon.length) {
                btn.html($.trim(btn.html()) + ' ');
                balloon = $('<span class="balloon"></span>').appendTo(btn);
            }

            balloon.html(count);
        } else if (0 === count && balloon.length > 0) {
            balloon.remove();
        }
    }

    updateAttachmentNumber();

    const uploadUrl = $('.upload-area').bind({
        dragenter   :   function (e) {
            $(this).parent().addClass('drag');
        },

        dragover    :   function (e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).parent().addClass('drag');
        },

        drop        :   function (e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).parent().removeClass('drag');

            const files = e.originalEvent.dataTransfer.files;

            if (files.length === 0) {
                return;
            }

            for (const file of files) {
                Typecho.uploadFile(file);
            }
        },

        dragend     :   function () {
            $(this).parent().removeClass('drag');
        },

        dragleave   :   function () {
            $(this).parent().removeClass('drag');
        }
    }).data('url');

    const btn = $('.upload-file');
    const fileInput = $('<input type="file" name="file" />').hide().insertAfter(btn);

    btn.click(function () {
        fileInput.click();
        return false;
    });

    fileInput.change(function () {
        if (this.files.length === 0) {
            return;
        }

        Typecho.uploadFile(this.files[0]);
    });

    function fileUploadStart (file) {
        $('<li id="' + file.id + '" class="loading">'
            + file.name + '</li>').appendTo('#file-list');
    }

    function fileUploadError (type, file) {
        let word = '<?php _e('上传出现错误'); ?>';
        
        switch (type) {
            case 'size':
                word = '<?php _e('文件大小超过限制'); ?>';
                break;
            case 'type':
                word = '<?php _e('文件扩展名不被支持'); ?>';
                break;
            case 'duplicate':
                word = '<?php _e('文件已经上传过'); ?>';
                break;
            case 'network':
            default:
                break;
        }

        var fileError = '<?php _e('%s 上传失败'); ?>'.replace('%s', file.name),
            li, exist = $('#' + file.id);

        if (exist.length > 0) {
            li = exist.removeClass('loading').html(fileError);
        } else {
            li = $('<li>' + fileError + '<br />' + word + '</li>').appendTo('#file-list');
        }

        li.effect('highlight', {color : '#FBC2C4'}, 2000, function () {
            $(this).remove();
        });
    }

    function fileUploadComplete (file, attachment) {
        const li = $('#' + file.id).removeClass('loading').data('cid', attachment.cid)
            .data('url', attachment.url)
            .data('image', attachment.isImage)
            .html('<input type="hidden" name="attachment[]" value="' + attachment.cid + '" />'
                + '<a class="insert" target="_blank" href="###" title="<?php _e('点击插入文件'); ?>">'
                + attachment.title + '</a><div class="info">' + attachment.bytes
                + ' <a class="file" target="_blank" href="<?php $options->adminUrl('media.php'); ?>?cid=' 
                + attachment.cid + '" title="<?php _e('编辑'); ?>"><i class="i-edit"></i></a>'
                + ' <a class="delete" href="###" title="<?php _e('删除'); ?>"><i class="i-delete"></i></a></div>')
            .effect('highlight', 1000);

        attachInsertEvent(li);
        attachDeleteEvent(li);
        updateAttachmentNumber();

        Typecho.uploadComplete(attachment);
    }

    Typecho.uploadFile = (function () {
        const types = '<?php echo json_encode($options->allowedAttachmentTypes); ?>';
        const maxSize = <?php echo $phpMaxFilesize ?>;
        const queue = [];
        let index = 0;

        const getUrl = function () {
            const url = new URL(uploadUrl);
            const cid = $('input[name=cid]').val();

            url.searchParams.append('cid', cid);
            return url.toString();
        };

        const upload = function () {
            const file = queue.shift();

            if (!file) {
                return;
            }

            const data = new FormData();
            data.append('file', file);

            fetch(getUrl(), {
                method: 'POST',
                body: data
            }).then(function (response) {
                if (response.ok) {
                    return response.json();
                } else {
                    throw new Error(response.statusText);
                }
            }).then(function (data) {
                if (data) {
                    const [_, attachment] = data;
                    fileUploadComplete(file, attachment);
                    upload();
                } else {
                    throw new Error('no data');
                }
            }).catch(function (error) {
                fileUploadError('network', file);
                upload();
            });
        };

        return function (file) {
            file.id = 'upload-' + (index++);

            if (file.size > maxSize) {
                return fileUploadError('size', file);
            }

            const match = file.name.match(/\.([a-z0-9]+)$/i);
            if (!match || types.indexOf(match[1].toLowerCase()) < 0) {
                return fileUploadError('type', file);
            }

            queue.push(file);
            fileUploadStart(file);
            upload();
        };
    })();

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
                $.post('<?php $security->index('/action/contents-attachment-edit'); ?>',
                    {'do' : 'delete', 'cid' : cid},
                    function () {
                        $(el).fadeOut(function () {
                            $(this).remove();
                            updateAttachmentNumber();
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

