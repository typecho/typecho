<?php
include 'common.php';
include 'header.php';
include 'menu.php';

\Widget\Contents\Attachment\Edit::alloc()->prepare()->to($attachment);
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-8" role="main">
                <?php if ($attachment->attachment->isImage): ?>
                    <p><img src="<?php $attachment->attachment->url(); ?>"
                            alt="<?php $attachment->attachment->name(); ?>" class="typecho-attachment-photo"/></p>
                <?php endif; ?>

                <p>
                    <?php $mime = \Typecho\Common::mimeIconType($attachment->attachment->mime); ?>
                    <i class="mime-<?php echo $mime; ?>"></i>
                    <a href=""><strong><?php $attachment->attachment->name(); ?></strong></a>
                    <span><?php echo number_format(ceil($attachment->attachment->size / 1024)); ?> Kb</span>
                </p>

                <p>
                    <input id="attachment-url" type="text" class="mono w-100"
                           value="<?php $attachment->attachment->url(); ?>" readonly/>
                </p>

                <div id="upload-panel" class="p">
                    <div class="upload-area" data-url="<?php $security->index('/action/upload?do=modify'); ?>">
                        <?php _e('拖放文件到这里<br>或者 %s选择文件上传%s', '<a href="###" class="upload-file">', '</a>'); ?>
                    </div>
                    <ul id="file-list"></ul>
                </div>
            </div>
            <div class="col-mb-12 col-tb-4 edit-media" role="form">
                <?php $attachment->form()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'file-upload-js.php';
?>
<script type="text/javascript">
    $(document).ready(function () {
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

        Typecho.uploadComplete = function (attachment) {
            if (attachment.isImage) {
                $('.typecho-attachment-photo').attr('src', attachment.url + '?' + Math.random());
            }

            $('#file-list li').text('<?php _e('文件 %s 已经替换'); ?>'.replace('%s', attachment.title))
                .effect('highlight', 1000, function () {
                    $(this).remove();
                });
        };
    });
</script>
<?php
include 'footer.php';
?>
