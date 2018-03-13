<?php
if (!defined('__TYPECHO_ADMIN__')) {
    exit;
}
$header = '<link rel="stylesheet" href="' . Typecho_Common::url('page.min.css?v=' . $suffixVersion, $options->adminStaticUrl('assets/css')) . '">
<link rel="stylesheet" href="' . Typecho_Common::url('style.css?v=' . $suffixVersion, $options->adminStaticUrl('assets/css')) . '">';

/** 注册一个初始化插件 */
$header = Typecho_Plugin::factory('admin/header.php')->header($header);

?><!DOCTYPE HTML>
<html class="no-js">
<head>
	<meta charset="<?php $options->charset(); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="renderer" content="webkit">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php _e('%s - %s - Powered by Typecho', $menu->title, $options->title); ?></title>
	<meta name="robots" content="noindex, nofollow">
    <?php echo $header; ?>
</head>
<body<?php
if (isset($bodyClass)) {
    echo ' class="' . $bodyClass . '"';
}
if (isset($bodyStyle)) {
    echo ' style="' . $bodyStyle . '"';
}
?>>
