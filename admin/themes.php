<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <?php if(!is_dir($options->themeFile($options->usingTheme))): ?>
                    <div id="typecho-welcome" class="message">
                        <form action="<?php echo $security->getTokenUrl($security->getIndex("/action/themes-edit?change=default")); ?>" method="post">
                            <h3><?php _e('检测到您之前使用的 "%s" 外观文件不存在: ', $options->usingTheme); ?></h3>
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
                <?php else: ?>
                <ul class="typecho-option-tabs fix-tabs clearfix">
                    <li class="current"><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('可以使用的外观'); ?></a>
                    </li>
                    <?php if (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__): ?>
                        <li><a href="<?php $options->adminUrl('theme-editor.php'); ?>"><?php _e('编辑当前外观'); ?></a></li>
                    <?php endif; ?>
                    <?php if (\Widget\Themes\Config::isExists()): ?>
                        <li><a href="<?php $options->adminUrl('options-theme.php'); ?>"><?php _e('设置外观'); ?></a></li>
                    <?php endif; ?>
                </ul>

                <div class="typecho-table-wrap">
                    <table class="typecho-list-table typecho-theme-list">
                        <colgroup>
                            <col width="35%"/>
                            <col/>
                        </colgroup>

                        <thead>
                        <th><?php _e('截图'); ?></th>
                        <th><?php _e('详情'); ?></th>
                        </thead>

                        <tbody>
                        <?php \Widget\Themes\Rows::alloc()->to($themes); ?>
                        <?php while ($themes->next()): ?>
                            <tr id="theme-<?php $themes->name(); ?>"
                                class="<?php if ($themes->activated): ?>current<?php endif; ?>">
                                <td valign="top"><img src="<?php $themes->screen(); ?>"
                                                      alt="<?php $themes->name(); ?>"/></td>
                                <td valign="top">
                                    <h3><?php '' != $themes->title ? $themes->title() : $themes->name(); ?></h3>
                                    <cite>
                                        <?php if ($themes->author): ?><?php _e('作者'); ?>: <?php if ($themes->homepage): ?><a href="<?php $themes->homepage() ?>"><?php endif; ?><?php $themes->author(); ?><?php if ($themes->homepage): ?></a><?php endif; ?> &nbsp;&nbsp;<?php endif; ?>
                                        <?php if ($themes->version): ?><?php _e('版本'); ?>: <?php $themes->version() ?><?php endif; ?>
                                    </cite>
                                    <p><?php echo nl2br($themes->description); ?></p>
                                    <?php if ($options->theme != $themes->name): ?>
                                        <p>
                                            <?php if (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__): ?>
                                                <a class="edit"
                                                   href="<?php $options->adminUrl('theme-editor.php?theme=' . $themes->name); ?>"><?php _e('编辑'); ?></a> &nbsp;
                                            <?php endif; ?>
                                            <a class="activate"
                                               href="<?php $security->index('/action/themes-edit?change=' . $themes->name); ?>"><?php _e('启用'); ?></a>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
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
