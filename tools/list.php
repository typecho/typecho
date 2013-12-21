<?php

/** 参数不存在则退出 */
if (!isset($argv[1])) {
    echo 'no args';
    exit(1);
}

/**
 * 获取所有文件
 * 
 * @param string $dir
 * @param string $pattern
 * @return array
 */
function all_files($dir, $pattern = '*') {
    $result = array();

    $items = glob($dir . '/' . $pattern, GLOB_BRACE);
    foreach ($items as $item) {
        if (is_file($item)) {
            $result[] = $item;
        } 
    }

    $items = glob($dir . '/*', GLOB_ONLYDIR);
    foreach ($items as $item) {
        if (is_dir($item)) {
            $result = array_merge($result, all_files($item, $pattern));
        }
    }

    return $result;
}

echo implode("\n", all_files($argv[1], '*.php'));
