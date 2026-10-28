<?php
// 执行模板中真实的配置块，不复制其时区选择逻辑。
$root = dirname(__DIR__, 2);
define('__TYPECHO_ROOT_DIR__', $root);
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');
require $root . '/var/Typecho/Common.php';
Typecho\Date::$serverTimeStamp = (int) $argv[2];
$options = (new ReflectionClass(Widget\Options::class))->newInstanceWithoutConstructor();
$options->push(['timezone' => $argv[1]]);
$source = file_get_contents($root . '/admin/write-js.php');
$block = '';
foreach (token_get_all($source) as $token) {
    if (is_array($token) && $token[0] === T_OPEN_TAG) {
        $block = '';
    } elseif (is_array($token) && $token[0] === T_CLOSE_TAG) {
        if (strpos($block, '$editorNowServerNow =') !== false) {
            eval($block);
            echo $editorNowConfigJson;
            exit(0);
        }
    } else {
        $block .= is_array($token) ? $token[1] : $token;
    }
}
fwrite(STDERR, "Editor configuration block was not found\n");
exit(1);
