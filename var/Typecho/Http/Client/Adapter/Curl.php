<?php

namespace Typecho\Http\Client\Adapter;

use Typecho\Http\Client;
use Typecho\Http\Client\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * CURL适配器
 *
 * @author qining
 * @category typecho
 * @package Http
 */
class Curl extends Adapter
{
    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return function_exists('curl_version');
    }

    /**
     * 发送请求
     *
     * @access public
     * @param string $url 请求地址
     * @return string
     * @throws Client\Exception
     */
    protected function httpSend(string $url): string
    {
        $ch = curl_init();

        if ($this->ip) {
            $url = $this->scheme . '://' . $this->ip . $this->path;
            $this->headers['Rfc'] = $this->method . ' ' . $this->path . ' ' . $this->rfc;
            $this->headers['Host'] = $this->host;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        /** 设置HTTP版本 */
        switch ($this->rfc) {
            case 'HTTP/1.0':
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                break;
            case 'HTTP/1.1':
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE);
                break;
        }

        /** 设置header信息 */
        if (!empty($this->headers)) {
            if (isset($this->headers['User-Agent'])) {
                curl_setopt($ch, CURLOPT_USERAGENT, $this->headers['User-Agent']);
                unset($this->headers['User-Agent']);
            }

            $headers = [];

            if (isset($this->headers['Rfc'])) {
                $headers[] = $this->headers['Rfc'];
                unset($this->headers['Rfc']);
            }

            foreach ($this->headers as $key => $val) {
                $headers[] = $key . ': ' . $val;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        /** POST模式 */
        if (Client::METHOD_POST == $this->method) {
            if (!isset($this->headers['content-type'])) {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            if (!empty($this->data)) {
                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    is_array($this->data) ? http_build_query($this->data) : $this->data
                );
            }

            if (!empty($this->files)) {
                foreach ($this->files as &$file) {
                    $file = '@' . $file;
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->files);
            }
        }

        $response = curl_exec($ch);
        if (false === $response) {
            throw new Client\Exception(curl_error($ch), 500);
        }

        curl_close($ch);
        return $response;
    }
}
