<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="typecho-foot">
    <h4><a href="http://typecho.org" class="logo-dark">typecho</a></h4>
    <div class="copyright"><?php _e('基于 <em>%s %s</em> <small> | %s</small> 构建', $options->software, $prefixVersion, $suffixVersion); ?></div>
    <div class="resource">
        <ul>
            <li><a href="http://docs.typecho.org"><?php _e('文档'); ?></a></li>
            <li><a href="http://forum.typecho.org"><?php _e('支持论坛'); ?></a></li>
            <li><a href="http://code.google.com/p/typecho/issues/entry"><?php _e('报告错误'); ?></a></li>
            <li><a href="http://extends.typecho.org"><?php _e('其他资源'); ?></a></li>
        </ul>
    </div>
</div>
