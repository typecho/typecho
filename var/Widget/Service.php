<?php

namespace Widget;

use Typecho\Common;
use Typecho\Http\Client;
use Typecho\Plugin;
use Typecho\Response;
use Typecho\Widget\Exception;
use Widget\Base\Options as BaseOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 通用异步服务组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Service extends BaseOptions implements ActionInterface
{
    /**
     * 异步请求
     *
     * @var array
     */
    public $asyncRequests = [];

    /**
     * 发送pingback实现
     *
     * @throws Exception|Client\Exception
     */
    public function sendPingHandle()
    {
        /** 验证权限 */
        $token = $this->request->token;

        if (!Common::timeTokenValidate($token, $this->options->secret, 3)) {
            throw new Exception(_t('禁止访问'), 403);
        }

        /** 忽略超时 */
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(30);
        }

        /** 获取post */
        $post = Archive::alloc("type=post", "cid={$this->request->cid}", false);

        if ($post->have() && preg_match_all("|<a[^>]*href=[\"'](.*?)[\"'][^>]*>(.*?)</a>|", $post->text, $matches)) {
            $links = array_unique($matches[1]);
            $permalinkPart = parse_url($post->permalink);

            /** 发送pingback */
            foreach ($links as $url) {
                $urlPart = parse_url($url);

                if (isset($urlPart['scheme'])) {
                    if ('http' != $urlPart['scheme'] && 'https' != $urlPart['scheme']) {
                        continue;
                    }
                } else {
                    $urlPart['scheme'] = 'http';
                    $url = Common::buildUrl($urlPart);
                }

                if ($permalinkPart['host'] == $urlPart['host'] && $permalinkPart['path'] == $urlPart['path']) {
                    continue;
                }

                $spider = Client::get();

                if ($spider) {
                    $spider->setTimeout(10)
                        ->send($url);

                    if (!($xmlrpcUrl = $spider->getResponseHeader('x-pingback'))) {
                        if (
                            preg_match(
                                "/<link[^>]*rel=[\"']pingback[\"'][^>]*href=[\"']([^\"']+)[\"'][^>]*>/i",
                                $spider->getResponseBody(),
                                $out
                            )
                        ) {
                            $xmlrpcUrl = $out[1];
                        }
                    }

                    if (!empty($xmlrpcUrl)) {
                        try {
                            $xmlrpc = new \IXR\Client($xmlrpcUrl);
                            $xmlrpc->pingback->ping($post->permalink, $url);
                            unset($xmlrpc);
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                }

                unset($spider);
            }
        }

        /** 发送trackback */
        if ($post->have() && !empty($this->request->trackback)) {
            $links = array_filter(array_map('trim', explode("\n", $this->request->trackback)));

            foreach ($links as $url) {
                $client = Client::get();

                if ($client) {
                    try {
                        $client->setTimeout(5)
                            ->setData([
                                'blog_name' => $this->options->title . ' &raquo ' . $post->title,
                                'url' => $post->permalink,
                                'excerpt' => $post->excerpt
                            ])
                            ->send($url);

                        unset($client);
                    } catch (Client\Exception $e) {
                        continue;
                    }
                }

            }
        }
    }

    /**
     * 发送pingback
     * <code>
     * $this->sendPingbacks(365);
     * </code>
     *
     * @param integer $cid 内容id
     * @param array|null $trackback trackback的url
     */
    public function sendPing($cid, ?array $trackback = null)
    {
        $this->user->pass('contributor');

        if ($client = Client::get()) {
            try {
                $input = [
                    'do' => 'ping',
                    'cid' => $cid,
                    'token' => Common::timeToken($this->options->secret)
                ];

                if (!empty($trackback)) {
                    $input['trackback'] = $trackback;
                }

                $client->setHeader('User-Agent', $this->options->generator)
                    ->setTimeout(2)
                    ->setData($input)
                    ->setMethod(Client::METHOD_POST)
                    ->send($this->getServiceUrl());
            } catch (Client\Exception $e) {
                return;
            }
        }
    }

    /**
     * 获取真实的 URL
     *
     * @return string
     */
    private function getServiceUrl(): string
    {
        $url = Common::url('/action/service', $this->options->index);

        if (defined('__TYPECHO_SERVICE_URL__')) {
            $rootPath = rtrim(parse_url($this->options->rootUrl, PHP_URL_PATH), '/');
            $path = parse_url($url, PHP_URL_PATH);
            $parts = parse_url(__TYPECHO_SERVICE_URL__);

            if (
                !empty($parts['path'])
                && $parts['path'] != '/'
                && rtrim($parts['path'], '/') != $rootPath
            ) {
                $path = Common::url($path, $parts['path']);
            }

            $parts['path'] = $path;
            $url = Common::buildUrl($parts);
        }

        return $url;
    }

    /**
     * 请求异步服务
     *
     * @param $method
     * @param mixed $params
     */
    public function requestService($method, $params = null)
    {
        static $called;

        if (!$called) {
            Response::getInstance()->addResponder(function () {
                if (!empty($self->asyncRequests) && $client = Client::get()) {
                    try {
                        $client->setHeader('User-Agent', $this->options->generator)
                            ->setTimeout(2)
                            ->setData([
                                'do' => 'async',
                                'requests' => json_encode($self->asyncRequests),
                                'token' => Common::timeToken($this->options->secret)
                            ])
                            ->setMethod(Client::METHOD_POST)
                            ->send($this->getServiceUrl());
                    } catch (Client\Exception $e) {
                        return;
                    }
                }
            });

            $called = true;
        }

        $this->asyncRequests[] = [$method, $params];
    }

    /**
     * 执行回调
     *
     * @throws Exception
     */
    public function asyncHandle()
    {
        /** 验证权限 */
        $token = $this->request->token;

        if (!Common::timeTokenValidate($token, $this->options->secret, 3)) {
            throw new Exception(_t('禁止访问'), 403);
        }

        /** 忽略超时 */
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(30);
        }

        $requests = json_decode($this->request->requests, true);
        $plugin = $this->pluginHandle();

        if (!empty($requests)) {
            foreach ($requests as $request) {
                [$method, $params] = $request;
                $plugin->{$method}($params);
            }
        }
    }

    /**
     * 异步请求入口
     */
    public function action()
    {
        $this->on($this->request->isPost() && $this->request->is('do=ping'))->sendPingHandle();
        $this->on($this->request->isPost() && $this->request->is('do=async'))->asyncHandle();
    }
}
