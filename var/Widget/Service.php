<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 通用异步服务
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 通用异步服务组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Widget_Service extends Widget_Abstract_Options implements Widget_Interface_Do
{
    /**
     * 异步请求
     *
     * @var array
     */
    public $asyncRequests = array();

    /**
     * 获取真实的 URL
     *
     * @return string
     */
    private function getServiceUrl()
    {
        $url = Typecho_Common::url('/action/service', $this->options->index);

        if (defined('__TYPECHO_SERVICE_URL__')) {
            $rootPath = rtrim(parse_url($this->options->rootUrl, PHP_URL_PATH), '/');
            $path = parse_url($url, PHP_URL_PATH);
            $parts = parse_url(__TYPECHO_SERVICE_URL__);

            if (!empty($parts['path'])
                && $parts['path'] != '/'
                && rtrim($parts['path'], '/') != $rootPath) {
                $path = Typecho_Common::url($path, $parts['path']);
            }

            $parts['path'] = $path;
            $url = Typecho_Common::buildUrl($parts);
        }

        return $url;
    }

    /**
     * 发送pingback实现
     *
     * @access public
     * @return void
     * @throws Typecho_Widget_Exception
     */
    public function sendPingHandle()
    {
        /** 验证权限 */
        $token = $this->request->token;

        if (!Typecho_Common::timeTokenValidate($token, $this->options->secret, 3)) {
            throw new Typecho_Widget_Exception(_t('禁止访问'), 403);
        }

        /** 忽略超时 */
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(30);
        }

        /** 获取post */
        $post = $this->widget('Widget_Archive', "type=post", "cid={$this->request->cid}");

        if ($post->have() && preg_match_all("|<a[^>]*href=[\"'](.*?)[\"'][^>]*>(.*?)</a>|", $post->text, $matches)) {
            $links = array_unique($matches[1]);
            $permalinkPart = parse_url($post->permalink);

            /** 发送pingback */
            foreach ($links as $url) {
                $urlPart = parse_url($url);

                if (isset($urlPart['scheme'])) {
                    if ('http' != $urlPart['scheme'] || 'https' != $urlPart['scheme']) {
                        continue;
                    }
                } else {
                    $urlPart['scheme'] = 'http';
                    $url = Typecho_Common::buildUrl($urlPart);
                }

                if ($permalinkPart['host'] == $urlPart['host'] && $permalinkPart['path'] == $urlPart['path']) {
                    continue;
                }

                $spider = Typecho_Http_Client::get();

                if ($spider) {
                    $spider->setTimeout(10)
                    ->send($url);

                    if (!($xmlrpcUrl = $spider->getResponseHeader('x-pingback'))) {
                        if (preg_match("/<link[^>]*rel=[\"']pingback[\"'][^>]*href=[\"']([^\"']+)[\"'][^>]*>/i",
                        $spider->getResponseBody(), $out)) {
                            $xmlrpcUrl = $out[1];
                        }
                    }

                    if (!empty($xmlrpcUrl)) {
                        try {
                            $xmlrpc = new IXR_Client($xmlrpcUrl);
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
            $links = $this->request->trackback;
            foreach ($links as $url) {

                $client = Typecho_Http_Client::get();

                if ($client) {
                    try {
                        $client->setTimeout(5)
                        ->setData(array(
                            'blog_name' => $this->options->title . ' &raquo ' . $post->title,
                            'url'       => $post->permalink,
                            'excerpt'   => $post->excerpt
                        ))
                        ->send($url);

                        unset($client);
                    } catch (Typecho_Http_Client_Exception $e) {
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
     * @access public
     * @param integer $cid 内容id
     * @param array $trackback trackback的url
     * @return void
     */
    public function sendPing($cid, array $trackback = NULL)
    {
        $this->user->pass('contributor');

        if ($client = Typecho_Http_Client::get()) {
            try {

                $input = array(
                    'do' => 'ping',
                    'cid' => $cid,
                    'token' => Typecho_Common::timeToken($this->options->secret)
                );

                if (!empty($trackback)) {
                    $input['trackback'] = $trackback;
                }

                $client->setHeader('User-Agent', $this->options->generator)
                    ->setTimeout(2)
                    ->setData($input)
                    ->setMethod(Typecho_Http_Client::METHOD_POST)
                    ->send($this->getServiceUrl());

            } catch (Typecho_Http_Client_Exception $e) {
                return;
            }
        }
    }

    /**
     * 请求异步服务
     *
     * @param $method
     * @param mixed $params
     */
    public function requestService($method, $params = NULL)
    {
        static $called;

        if (!$called) {
            $self = $this;

            Typecho_Response::addCallback(function () use ($self) {
                if (!empty($self->asyncRequests) && $client = Typecho_Http_Client::get()) {
                    try {
                        $client->setHeader('User-Agent', $this->options->generator)
                            ->setTimeout(2)
                            ->setData(array(
                                'do'        =>  'async',
                                'requests'  =>  Json::encode($self->asyncRequests),
                                'token'     =>  Typecho_Common::timeToken($this->options->secret)
                            ))
                            ->setMethod(Typecho_Http_Client::METHOD_POST)
                            ->send($this->getServiceUrl());

                    } catch (Typecho_Http_Client_Exception $e) {
                        return;
                    }
                }
            });

            $called = true;
        }

        $this->asyncRequests[] = array($method, $params);
    }

    /**
     * 执行回调
     *
     * @throws Typecho_Widget_Exception
     */
    public function asyncHandle()
    {
        /** 验证权限 */
        $token = $this->request->token;

        if (!Typecho_Common::timeTokenValidate($token, $this->options->secret, 3)) {
            throw new Typecho_Widget_Exception(_t('禁止访问'), 403);
        }

        /** 忽略超时 */
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(30);
        }

        $requests = Json::decode($this->request->requests, true);
        $plugin = Typecho_Plugin::factory(__CLASS__);

        if (!empty($requests)) {
            foreach ($requests as $request) {
                list ($method, $params) = $request;
                $plugin->{$method}($params);
            }
        }
    }

    /**
     * 异步请求入口
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->on($this->request->isPost() && $this->request->is('do=ping'))->sendPingHandle();
        $this->on($this->request->isPost() && $this->request->is('do=async'))->asyncHandle();
    }
}
