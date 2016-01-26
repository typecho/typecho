<?php

// transfer [] to array()

$file = $argv[1];
$text = file_get_contents($file);

$text = preg_replace("/= \[([^;]*)\];/s", "= array(\\1);", $text);
$text = preg_replace("/(\(| )\[([^\n]*?)\]\)/", "\\1array(\\2))", $text);
$text = preg_replace("/(\(| )\[([^\n]*?)\],/", "\\1array(\\2),", $text);

file_put_contents($file, $text);

