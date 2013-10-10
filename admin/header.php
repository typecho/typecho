<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$header = '<link rel="stylesheet" href="' . Typecho_Common::url('css/normalize.css?v=' . $suffixVersion, $options->adminUrl) . '" /> 
<link rel="stylesheet" href="' . Typecho_Common::url('css/grid.css?v=' . $suffixVersion, $options->adminUrl) . '" /> 
<link rel="stylesheet" href="' . Typecho_Common::url('css/style.css?v=' . $suffixVersion, $options->adminUrl) . '" />';

/** 注册一个初始化插件 */
$header = Typecho_Plugin::factory('admin/header.php')->header($header);

?><!DOCTYPE HTML>
<html lang="zh-CN">
    <head>
        <meta charset="<?php $options->charset(); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <title><?php _e('%s - %s - Powered by Typecho', $menu->title, $options->title); ?></title>
        <meta name="robots" content="noindex,nofollow" />
        <?php echo $header; ?>
    </head>
    <body<?php if (isset($bodyClass)) {echo ' class="' . $bodyClass . '"';} ?>>
