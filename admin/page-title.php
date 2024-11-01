<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<div class="typecho-page-title d-flex align-items-center">
    <h2><?php echo $menu->title; ?></h2>
    <?php 
    if (!empty($menu->addLink)) {
        echo "<a class=\"btn btn-sm btn-outline-primary ms-2\" href=\"{$menu->addLink}\">" . _t("新增") . "</a>";
    }
    ?>
</div>
