<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="typecho-foot">
    <div class="copyright">
        <?php _e('由 <a href="http://typecho.org">%s</a> 强力驱动, 版本 %s (%s)', $options->software, $prefixVersion, $suffixVersion); ?>
    </div>
    <nav class="resource">
        <a href="http://docs.typecho.org"><?php _e('帮助文档'); ?></a> &bull;
        <a href="http://forum.typecho.org"><?php _e('支持论坛'); ?></a> &bull;
        <a href="https://github.com/typecho/typecho-replica/issues"><?php _e('报告错误'); ?></a> &bull;
        <a href="http://extends.typecho.org"><?php _e('资源下载'); ?></a>
    </nav>
</div>
