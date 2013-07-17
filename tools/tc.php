<?php

/** 参数不存在则退出 */
if (!isset($argv[1])) {
    echo 'no args';
    exit(1);
}

/** 解析所有的参数 */
parse_str($argv[1], $options);

/** 必要参数检测 */
if (!isset($options['in']) || !isset($options['out'])) {
    echo 'no input or output file';
    exit(1);
}

$str = php_strip_whitespace($options['in']);
$str = preg_replace("/require_once\s+('|\")[_0-9a-z-\/\.]+\\1\s*;/is", '', $str);
$str = trim(ltrim($str, '<?php'));

if (file_exists($options['out'])) {
    $str = file_get_contents($options['out']) . $str;
} else {
    $str = '<?php ' . $str;
}

file_put_contents($options['out'], $str);
