<?php
include 'common.php';
include 'header.php';
include 'menu.php';

Typecho_Widget::widget('Widget_Themes_Files')->to($files);
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs fix-tabs clearfix">
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
            </div>
                
            <div class="typecho-edit-theme">
                <div class="col-mb-12 col-tb-8 col-9 content">
                    <form method="post" name="theme" id="theme" action="<?php $security->index('/action/themes-edit'); ?>">
                        <label for="content" class="sr-only"><?php _e('编辑源码'); ?></label>
                        <textarea name="content" id="content" class="w-100 mono" <?php if(!$files->currentIsWriteable()): ?>readonly<?php endif; ?>><?php echo $files->currentContent(); ?></textarea>
                        <p class="submit">
                            <?php if($files->currentIsWriteable()): ?>
                            <input type="hidden" name="theme" value="<?php echo $files->currentTheme(); ?>" />
                            <input type="hidden" name="edit" value="<?php echo $files->currentFile(); ?>" />
                            <button type="submit" class="btn primary"><?php _e('保存文件'); ?></button>
                            <?php else: ?>
                                <em><?php _e('此文件无法写入'); ?></em>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
                <ul class="col-mb-12 col-tb-4 col-3">
                    <li><strong>模板文件</strong></li>
                    <?php while($files->next()): ?>
                    <li<?php if($files->current): ?> class="current"<?php endif; ?>>
                    <a href="<?php $options->adminUrl('theme-editor.php?theme=' . $files->currentTheme() . '&file=' . $files->file); ?>"><?php $files->file(); ?></a></li>
                    <?php endwhile; ?>
                </ul>
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
