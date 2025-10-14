<?php
/**
 * 获取一言
 * 缓存：由浏览器 5 分钟
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Link: https://v1.hitokoto.cn; rel="preconnect"');

// 自举 Typecho
if (!defined('__TYPECHO_ROOT_DIR__')) {
    $root = dirname(__DIR__, 3);
    if (file_exists($root . '/config.inc.php')) {
        require_once $root . '/config.inc.php';
        if (file_exists($root . '/var/Typecho/Common.php')) {
            require_once $root . '/var/Typecho/Common.php';
            if (class_exists('Typecho_Common')) {
                Typecho_Common::init();
            }
        }
    }
}

$defaultUrl = 'https://v1.hitokoto.cn/?encode=json';
$defaultUA  = 'FoxmoeTheme/1.1 (+https://foxmoe.top)';
if (class_exists('Helper')) {
    $opt = Helper::options();
    $remoteUrl = isset($opt->hitokotoRemote) && $opt->hitokotoRemote ? $opt->hitokotoRemote : $defaultUrl;
    $ua        = isset($opt->hitokotoUA) && $opt->hitokotoUA ? $opt->hitokotoUA : $defaultUA;
} else {
    $remoteUrl = $defaultUrl;
    $ua        = $defaultUA;
}

$timeout   = 3;

function foxmoe_fetch($url, $ua, $timeout) {
    // 优先 cURL
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $data   = curl_exec($ch);
        $errNo  = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($errNo || $status >= 400 || !$data) return false;
        return $data;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'header'  => "User-Agent: $ua\r\n"
        ]
    ]);
    $data = @file_get_contents($url, false, $ctx);
    return $data ?: false;
}

$data = foxmoe_fetch($remoteUrl, $ua, $timeout);
if ($data) {
    $json = json_decode($data, true);
    if (is_array($json) && isset($json['hitokoto'])) {
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        exit;    
    }
}
http_response_code(502);
echo json_encode([
    'hitokoto' => 'HAVE FUN!',
    'error'    => 1
], JSON_UNESCAPED_UNICODE);
