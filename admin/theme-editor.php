<?php
include 'common.php';
include 'header.php';
include 'menu.php';

\Widget\Themes\Files::alloc()->to($files);
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <?php include 'theme-tabs.php'; ?>
        <div class="row typecho-page-main typecho-edit-theme" role="main">
            <div class="col-mb-12 col-tb-8 col-9 content">
                <form method="post" name="theme" id="theme"
                      action="<?php $security->index('/action/themes-edit'); ?>">
                    <label for="content" class="sr-only"><?php _e('编辑源码'); ?></label>
                    <textarea name="content" id="content" class="w-100 mono"
                              <?php if (!$files->currentIsWriteable()): ?>readonly<?php endif; ?>><?php echo $files->currentContent(); ?></textarea>
                    <p class="typecho-option typecho-option-submit">
                        <?php if ($files->currentIsWriteable()): ?>
                            <input type="hidden" name="theme" value="<?php echo $files->currentTheme(); ?>"/>
                            <input type="hidden" name="edit" value="<?php echo $files->currentFile(); ?>"/>
                            <button type="submit" class="btn primary"><?php _e('保存文件'); ?></button>
                        <?php else: ?>
                            <em><?php _e('此文件无法写入'); ?></em>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            <ul class="col-mb-12 col-tb-4 col-3">
                <li><strong>模板文件</strong></li>
                <?php while ($files->next()): ?>
                    <li<?php if ($files->current): ?> class="current"<?php endif; ?>>
                        <a href="<?php $options->adminUrl('theme-editor.php?theme=' . $files->currentTheme() . '&file=' . $files->file); ?>"><?php $files->file(); ?></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
\Typecho\Plugin::factory('admin/theme-editor.php')->call('bottom', $files);
include 'footer.php';
?>
