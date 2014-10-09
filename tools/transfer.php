<?php

// transfer namespace based php class to dashed
$dir = $argv[1];
$ns = $argv[2];
$fake = $argv[3];

$files = $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,
        FilesystemIterator::KEY_AS_PATHNAME
        | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS));

$map = [];
$lists = [];
$offset = strlen($dir);

foreach ($files as $file) {
    $path = $file->getPathname();
    $file = $file->getFilename();
    $lists[] = $path;

    $dir = dirname($path);

    if ($file[0] == '.') {
        continue;
    }

    $path = ltrim(substr($path, $offset), '/\\');
    list($class) = explode('.', $path);
    
    $name = str_replace(['/', '\\'], '\\', $fake . '\\' . $class);
    $class = str_replace(['/', '\\'], '_', $ns . '_' . $class);
    
    $map[$name] = $class;
}

foreach ($lists as $file) {
    $dir = dirname($file);
    $source = file_get_contents($file);
    $replace = [];
    $current = '';

    $source = preg_replace_callback("/\nnamespace\s*([a-z_\\\]+);/is", function ($matches) use ($map, $file, &$replace, &$current) {
        $matches[1] .= '\\' . pathinfo($file, PATHINFO_FILENAME);
        $parts = explode('\\', $matches[1]);
        $last = array_pop($parts);

        if (isset($map[$matches[1]])) {
            $replace[$last] = $map[$matches[1]];
            $current = $matches[1];
        }

        return "if (!defined('__TYPECHO_ROOT_DIR__')) exit;";
    }, $source);

    $source = preg_replace_callback("/\nuse\s*([a-z_\\\]+)(?:\s+as\s+([a-z_\\\]+))?;/is", function ($matches) use ($map, &$replace) {
        $parts = explode('\\', $matches[1]);
        $last = array_pop($parts);

        if (isset($map[$matches[1]])) {
            $replace[$last] = $map[$matches[1]];
        }

        return '';
    }, $source);

    foreach ($map as $key => $val) {
        if (count(explode('\\', $key)) == count(explode('\\', $current))) {
            $parts = explode('_', $val);
            $last = array_pop($parts);

            if (!isset($replace[$last])) {
                $replace[$last] = $val;
            }
        }
    }

    $source = str_replace(array('mb_strtoupper', 'mb_strlen'), 
        array('Typecho_Common::strToUpper', 'Typecho_Common::strLen'), $source);

    $tokens = token_get_all($source);
    $source = '';

    $last = false;
    foreach ($tokens as $key => $token) {
        if (!is_array($token)) {
            $source .= $token;
            $last = false;
            continue;
        }

        list ($name, $str) = $token;

        if (T_STRING == $name) {
            $str = isset($replace[$str]) ? $replace[$str] : $str;
        } else if (T_NS_SEPARATOR == $name) {
            if (T_STRING == $last) {
                $source = substr($source, 0, - strlen($tokens[$key - 1][1]));
            }
            
            $str = '';
        }

        $last = $name;
        $source .= $str;
    }

    file_put_contents($file, $source);
}


