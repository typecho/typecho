<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$actionUrl = $security->getTokenUrl($security->getIndex("/action/themes-edit?change=default"));
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <div id="typecho-welcome" class="message">
                    <form action="<?php echo $actionUrl; ?>" method="post">
                        <h3><?php _e('检测到您之前使用的 "%s" 外观文件不存在: ', $options->theme); ?></h3>
                        <ul>
                            <li><?php _e('您可以切换为<strong>默认外观</strong>，或者<strong>重新上传</strong>之前的外观文件后<strong>刷新</strong>本页面'); ?></li>
                            <li><strong class="warning"><?php _e('切换为默认外观后，您之前的外观设置将被移除'); ?></strong></li>
                        </ul>
                        <p>
                            <button type="submit" class="btn btn-warn"><?php _e('使用默认外观'); ?></button>
                            <button type="button" class="btn primary" onclick="window.location.reload();"><?php _e('刷新'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<script>
    $('#typecho-welcome form').submit(function (e) {
        if (!confirm('<?php _e('使用默认主题将移除之前的外观设置，是否继续?'); ?>')) {
            return false;
        }
    });
</script>
<?php include 'footer.php'; ?>
