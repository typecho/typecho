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
     * 发送pingback实现
     *
     * @access public
     * @return void
     */
    public function sendPingHandle()
    {
        /** 验证权限 */
        $this->user->pass('contributor');

        /** 忽略超时 */
        ignore_user_abort(true);

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

                $input = array('do' => 'ping', 'cid' => $cid);
                if (!empty($trackback)) {
                    $input['trackback'] = $trackback;
                }

                $client->setCookie('__typecho_uid', Typecho_Cookie::get('__typecho_uid'))
                ->setCookie('__typecho_authCode', Typecho_Cookie::get('__typecho_authCode'))
                ->setHeader('User-Agent', $this->options->generator)
                ->setTimeout(3)
                ->setData($input)
                ->setIp('127.0.0.1')
                ->send(Typecho_Common::url('/action/service', $this->options->index));

            } catch (Typecho_Http_Client_Exception $e) {
                return;
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
        $this->on($this->request->is('do=ping'))->sendPingHandle();
    }
}
