<?php

$langs = [];

/**
 * output lang
 *
 * @param string $str
 */
function output_lang(string $str)
{
    global $langs;

    $key = md5($str);
    if (!isset($langs[$key])) {
        echo $str;
        $langs[$key] = true;
    }
}

/**
 * get all files
 *
 * @param string $dir
 * @param string $pattern
 * @return array
 */
function all_files(string $dir, string $pattern = '*'): array
{
    $result = [];

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

/**
 * get msgid
 *
 * @param string $value
 * @return string
 */
function get_msgid(string $value): string
{
    if ($value[0] == '"') {
        return $value;
    } else {
        $value = trim($value, "'");
        return '"' . str_replace('"', '\"', $value) . '"';
    }
}

/**
 * get pot from file
 *
 * @param string $file
 */
function get_pot(string $file)
{
    $source = file_get_contents($file);
    $matched = null;
    $plural = [];

    foreach (token_get_all($source) as $token) {
        if (is_array($token)) {
            [$type, $value] = $token;

            if ($type == T_STRING && in_array($value, ['_t', '_e', '_n'])) {
                $matched = $value;
            } elseif ($type == T_CONSTANT_ENCAPSED_STRING && $matched) {
                $key = md5($value);

                if ($matched == '_n') {
                    $plural[] = $value;
                } else {
                    output_lang('msgid ' . get_msgid($value) . "\nmsgstr \"\"\n\n");
                    $matched = null;
                }
            } elseif ($type != T_WHITESPACE) {
                $matched = null;

                if (!empty($plural)) {
                    $msgstr = '';
                    $lang = '';

                    foreach ($plural as $key => $value) {
                        $lang .= 'msgid' . ($key == 0 ? '' : '_plural') . ' ' . get_msgid($value) . "\n";
                        $msgstr .= "msgstr[{$key}] \"\"\n";
                    }

                    $lang .= $msgstr . "\n";
                    output_lang($lang);
                    $plural = [];
                }
            }
        } elseif ($token != ',' && $token != '(') {
            $matched = null;
            $plural = [];
        }
    }
}

echo <<<EOF
# Copyright (C) Typecho
# This file is distributed under the same license as the Typecho Project.
#
#, fuzzy
msgid ""
msgstr ""
"Report-Msgid-Bugs-To: \\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: Typecho Dev <team@typecho.org>\\n"
"Language: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n"\n\n
EOF;

foreach (all_files(__DIR__ . '/../', "*.php") as $file) {
    get_pot($file);
}
