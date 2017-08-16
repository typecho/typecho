<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 col-tb-8">
                <div id="typecho-welcome">
                    <form action="<?php echo $security->getTokenUrl(
                        Typecho_Router::url('do', array('action' => 'backup', 'widget' => 'Backup'),
                        Typecho_Common::url('index.php', $options->rootUrl))); ?>" method="post">
                    <h3><?php _e('备份您的数据'); ?></h3>
                    <ul>
                        <li><?php _e('此备份操作仅包含<strong>内容数据</strong>, 并不会涉及任何<strong>设置信息</strong>'); ?></li>
                        <li><?php _e('如果您的数据量过大, 为了避免操作超时, 建议您直接使用数据库提供的备份工具备份数据'); ?></li>
                        <li><strong class="warning"><?php _e('为了缩小备份文件体积, 建议您在备份前删除不必要的数据'); ?></strong></li>
                    </ul>
                    <p><button class="btn primary" type="submit"><?php _e('开始备份 &raquo;'); ?></button></p>
                        <input type="hidden" name="do" value="export">
                    </form>
                </div>
            </div>

            <div class="col-mb-12 col-tb-4" role="form">
                <h4><?php _e('上传一个备份文件恢复数据'); ?></h4>
                <div id="upload-panel" class="p">
                    <div class="upload-area" draggable="true"><?php _e('拖放文件到这里<br>或者 %s选择文件上传%s', '<a href="###" class="upload-file">', '</a>'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
