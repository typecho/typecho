<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
<div class="message <?php $notice->noticeType(); ?> popup">
<ul>
    <?php $notice->lists(); ?>
</ul>
</div>
<?php endif; ?>
<div class="col-group">
    <div class="col-12">
        <div class="typecho-page-title">
            <h2><?php echo $menu->title; ?><?php 
            if (!empty($menu->addLink)) {
                echo "<a href=\"{$menu->addLink}\">" . _t("新增") . "</a>";
            }
            ?></h2>
        </div>
    </div>
</div>