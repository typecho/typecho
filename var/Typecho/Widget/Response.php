<?php

namespace Typecho\Widget;

use Typecho\Common;
use Typecho\Request as HttpRequest;
use Typecho\Response as HttpResponse;

/**
 * Widget Response Wrapper
 */
class Response
{
    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var HttpResponse
     */
    private $response;

    /**
     * @param HttpRequest $request
     * @param HttpResponse $response
     */
    public function __construct(HttpRequest $request, HttpResponse $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param int $code
     * @return $this
     */
    public function setStatus(int $code): Response
    {
        $this->response->setStatus($code);
        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setHeader(string $name, $value): Response
    {
        $this->response->setHeader($name, (string)$value);
        return $this;
    }

    /**
     * 设置默认回执编码
     *
     * @param string $charset 字符集
     * @return $this
     */
    public function setCharset(string $charset): Response
    {
        $this->response->setCharset($charset);
        return $this;
    }

    /**
     * @param string $contentType
     * @return $this
     */
    public function setContentType(string $contentType = 'text/html'): Response
    {
        $this->response->setContentType($contentType);
        return $this;
    }

    /**
     * @param string $content
     * @param string $contentType
     */
    public function throwContent(string $content, string $contentType = 'text/html')
    {
        $this->response->setContentType($contentType)
            ->addResponder(function () use ($content) {
                echo $content;
            })
            ->respond();
    }

    /**
     * @param mixed $message
     */
    public function throwXml(string $message)
    {
        $this->response->setContentType('text/xml')
            ->addResponder(function () use ($message) {
                echo '<?xml version="1.0" encoding="' . $this->response->getCharset() . '"?>',
                '<response>',
                $this->parseXml($message),
                '</response>';
            })
            ->respond();
    }

    /**
     * 抛出json回执信息
     *
     * @param mixed $message 消息体
     */
    public function throwJson($message)
    {
        /** 设置http头信息 */
        $this->response->setContentType('application/json')
            ->addResponder(function () use ($message) {
                echo json_encode($message);
            })
            ->respond();
    }

    /**
     * @param $file
     * @param string|null $contentType
     */
    public function throwFile($file, ?string $contentType = null)
    {
        if (!empty($contentType)) {
            $this->response->setContentType($contentType);
        }

        $this->response->setHeader('Content-Length', filesize($file))
            ->addResponder(function () use ($file) {
                readfile($file);
            })
            ->respond();
    }

    /**
     * 重定向函数
     *
     * @param string $location 重定向路径
     * @param boolean $isPermanently 是否为永久重定向
     */
    public function redirect(string $location, bool $isPermanently = false)
    {
        $location = Common::safeUrl($location);

        $this->response->setStatus($isPermanently ? 301 : 302)
            ->setHeader('Location', $location)
            ->respond();
    }

    /**
     * 返回来路
     *
     * @param string|null $suffix 附加地址
     * @param string|null $default 默认来路
     */
    public function goBack(string $suffix = null, string $default = null)
    {
        //获取来源
        $referer = $this->request->getReferer();

        //判断来源
        if (!empty($referer)) {
            // ~ fix Issue 38
            if (!empty($suffix)) {
                $parts = parse_url($referer);
                $myParts = parse_url($suffix);

                if (isset($myParts['fragment'])) {
                    $parts['fragment'] = $myParts['fragment'];
                }

                if (isset($myParts['query'])) {
                    $args = [];
                    if (isset($parts['query'])) {
                        parse_str($parts['query'], $args);
                    }

                    parse_str($myParts['query'], $currentArgs);
                    $args = array_merge($args, $currentArgs);
                    $parts['query'] = http_build_query($args);
                }

                $referer = Common::buildUrl($parts);
            }

            $this->redirect($referer);
        } else {
            $this->redirect($default ?: '/');
        }
    }

    /**
     * 解析ajax回执的内部函数
     *
     * @param mixed $message 格式化数据
     * @return string
     */
    private function parseXml($message): string
    {
        /** 对于数组型则继续递归 */
        if (is_array($message)) {
            $result = '';

            foreach ($message as $key => $val) {
                $tagName = is_int($key) ? 'item' : $key;
                $result .= '<' . $tagName . '>' . $this->parseXml($val) . '</' . $tagName . '>';
            }

            return $result;
        } else {
            return preg_match("/^[^<>]+$/is", $message) ? $message : '<![CDATA[' . $message . ']]>';
        }
    }
}
