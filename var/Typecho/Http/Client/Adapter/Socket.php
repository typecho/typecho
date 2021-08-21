<?php

namespace Typecho\Http\Client\Adapter;

use Typecho\Http\Client;
use Typecho\Http\Client\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Socket适配器
 *
 * @author qining
 * @category typecho
 * @package Http
 */
class Socket extends Adapter
{
    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return function_exists("fsockopen");
    }

    /**
     * 获取回执身体
     *
     * @access public
     * @return string
     */
    public function getResponseBody(): string
    {
        /** 支持chunked编码 */
        if ('chunked' == $this->getResponseHeader('Transfer-Encoding')) {
            $parts = explode("\r\n", $this->responseBody, 2);
            $counter = hexdec($parts[0]);
            $this->responseBody = substr($parts[1], 0, $counter);
        }

        return $this->responseBody;
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
        $eol = Client::EOL;
        $request = $this->method . ' ' . $this->path . ' ' . $this->rfc . $eol;
        $request .= 'Host: ' . $this->host . $eol;
        $request .= 'Accept: */*' . $eol;
        $request .= 'Cache-Control: no-cache' . $eol;
        $request .= 'Connection: Close' . $eol;

        /** 设置header信息 */
        if (!empty($this->headers)) {
            foreach ($this->headers as $key => $val) {
                $request .= $key . ': ' . $val . $eol;
            }
        }

        /** 发送POST信息 */
        if (Client::METHOD_POST == $this->method) {
            if (empty($this->files)) {
                $content = is_array($this->data) ? http_build_query($this->data) : $this->data;
                $request .= 'Content-Length: ' . strlen($content) . $eol;

                if (!isset($this->headers['content-type'])) {
                    $request .= 'Content-Type: application/x-www-form-urlencoded' . $eol;
                }
            } else {
                $boundary = '---------------------------' . substr(md5(uniqid()), 0, 16);
                $content = $eol . $boundary;

                if (!empty($this->data)) {
                    foreach ($this->data as $key => $val) {
                        $content .= $eol . 'Content-Disposition: form-data; name="' . $key . '"' . $eol . $eol;
                        $content .= $val . $eol;
                        $content .= $boundary;
                    }
                }

                foreach ($this->files as $key => $file) {
                    $content .= $eol . 'Content-Disposition: form-data; name="' . $key
                        . '"; filename="' . $file . '"' . $eol;
                    $content .= 'Content-Type: ' . mime_content_type($file) . $eol . $eol;
                    $content .= file_get_contents($file) . $eol;
                    $content .= $boundary;
                }

                $content .= '--' . $eol;
                $request .= 'Content-Length: ' . strlen($content) . $eol;
                $request .= 'Content-Type: multipart/form-data; boundary=' . $boundary;
            }

            $request .= $eol;
            $request .= $content;
        } else {
            $request .= $eol;
        }

        /** 打开连接 */
        $socket = @fsockopen($this->ip ?: $this->host, $this->port, $errno, $errstr, $this->timeout);
        if (false === $socket) {
            throw new Client\Exception($errno . ':' . $errstr, 500);
        }

        /** 发送数据 */
        fwrite($socket, $request);
        stream_set_timeout($socket, $this->timeout);
        $response = '';

        //facebook code
        while (!feof($socket)) {
            $buf = fgets($socket, 4096);

            if (false === $buf || '' === $buf) {
                $info = stream_get_meta_data($socket);

                //超时判断
                if ($info['timed_out']) {
                    throw new Client\Exception(
                        __CLASS__ . ': timeout reading from ' . $this->host . ':' . $this->port,
                        500
                    );
                } else {
                    throw new Client\Exception(
                        __CLASS__ . ': could not read from ' . $this->host . ':' . $this->port,
                        500
                    );
                }
            } elseif (strlen($buf) < 4096) {
                $info = stream_get_meta_data($socket);

                if ($info['timed_out']) {
                    throw new Client\Exception(
                        __CLASS__ . ': timeout reading from ' . $this->host . ':' . $this->port,
                        500
                    );
                }
            }

            $response .= $buf;
        }

        fclose($socket);
        return $response;
    }
}
