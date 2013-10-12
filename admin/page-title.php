<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="col-group">
    <div class="typecho-page-title col-mb-12">
        <h2><?php echo $menu->title; ?><?php 
        if (!empty($menu->addLink)) {
            echo "<a href=\"{$menu->addLink}\">" . _t("新增") . "</a>";
        }
        ?></h2>
    </div>
</div>
