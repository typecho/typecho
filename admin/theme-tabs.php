<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<ul class="typecho-option-tabs fix-tabs">
    <li<?php if ($menu->getCurrentMenuUrl() === 'themes.php'): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('可以使用的外观'); ?></a></li>
    <?php if (\Widget\Themes\Files::isWriteable()): ?>
        <li<?php if ($menu->getCurrentMenuUrl() === 'theme-editor.php'): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('theme-editor.php'); ?>">
                <?php if (!isset($files) || $options->theme == $files->theme): ?>
                    <?php _e('编辑当前外观'); ?>
                <?php else: ?>
                    <?php _e('编辑%s外观', ' <cite>' . $files->theme . '</cite> '); ?>
                <?php endif; ?>
            </a></li>
    <?php endif; ?>
    <?php if (\Widget\Themes\Config::isExists()): ?>
        <li<?php if ($menu->getCurrentMenuUrl() === 'options-theme.php'): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('options-theme.php'); ?>"><?php _e('设置外观'); ?></a></li>
    <?php endif; ?>
</ul>
