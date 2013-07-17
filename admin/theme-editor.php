<?php
include 'common.php';
include 'header.php';
include 'menu.php';

Typecho_Widget::widget('Widget_Themes_Files')->to($files);
?>

<div class="main">
    <div class="body body-950">
        <?php include 'page-title.php'; ?>
        <div class="container typecho-page-main">
            <div class="column-24">
                <ul class="typecho-option-tabs">
                    <li><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('可以使用的外观'); ?></a></li>
                    <li class="current"><a href="<?php $options->adminUrl('theme-editor.php'); ?>">
                    <?php if ($options->theme == $files->theme): ?>
                    <?php _e('编辑当前外观'); ?>
                    <?php else: ?>
                    <?php _e('编辑%s外观', ' <cite>' . $files->theme . '</cite> '); ?>
                    <?php endif; ?>
                    </a></li>
                    <?php if (Widget_Themes_Config::isExists()): ?>
                    <li><a href="<?php $options->adminUrl('options-theme.php'); ?>"><?php _e('设置外观'); ?></a></li>
                    <?php endif; ?>
                </ul>
                
                <div class="typecho-edit-theme">
                    <div>
                        <ul>
                            <?php while($files->next()): ?>
                            <li<?php if($files->current): ?> class="current"<?php endif; ?>>
                            <a href="<?php $options->adminUrl('theme-editor.php?theme=' . $files->currentTheme() . '&file=' . $files->file); ?>"><?php $files->file(); ?></a></li>
                            <?php endwhile; ?>
                        </ul>
                        <div class="content">
                        <form method="post" name="theme" id="theme" action="<?php $options->index('/action/themes-edit'); ?>">
                            <textarea name="content" id="content" <?php if(!$files->currentIsWriteable()): ?>readonly<?php endif; ?>><?php echo $files->currentContent(); ?></textarea>
                            <div class="submit">
                                <?php if($files->currentIsWriteable()): ?>
                                <input type="hidden" name="theme" value="<?php echo $files->currentTheme(); ?>" />
                                <input type="hidden" name="edit" value="<?php echo $files->currentFile(); ?>" />
                                <button type="submit"><?php _e('保存文件'); ?></button>
                                <?php else: ?>
                                    <h6 class="typecho-list-table-title"><?php _e('此文件无法写入'); ?></h6>
                                <?php endif; ?>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
Typecho_Plugin::factory('admin/theme-editor.php')->bottom($files);
include 'footer.php';
?>
