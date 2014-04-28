<?php
include 'common.php';

$panel = $request->get('panel');
$panelTable = unserialize($options->panelTable);

if (!isset($panelTable['file']) || !in_array(urlencode($panel), $panelTable['file'])) {
    throw new Typecho_Plugin_Exception(_t('页面不存在'), 404);
}

list ($pluginName, $file) = explode('/', trim($panel, '/'), 2);

require_once $options->pluginDir($pluginName) . '/' . $panel;
