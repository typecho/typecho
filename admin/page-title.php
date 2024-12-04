<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<div class="typecho-page-title">
    <h2><?php echo $menu->title; ?></h2>
    <?php
    if (!empty($menu->addLink)) {
        echo "<a class=\"btn btn-outline-primary btn-sm rounded-pill ms-3 px-3\" href=\"{$menu->addLink}\">" . _t("新增") . "</a>";
    }
    ?>
</div>
