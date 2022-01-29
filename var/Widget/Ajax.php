<?php

namespace Widget;

use Typecho\Http\Client;
use Typecho\Widget\Exception;
use Widget\Base\Options as BaseOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 异步调用组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Ajax extends BaseOptions implements ActionInterface
{
    /**
     * 针对rewrite验证的请求返回
     *
     * @access public
     * @return void
     */
    public function remoteCallback()
    {
        if ($this->options->generator == $this->request->getAgent()) {
            echo 'OK';
        }
    }

    /**
     * 获取最新版本
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function checkVersion()
    {
        $this->user->pass('editor');
        $client = Client::get();
        if ($client) {
            $client->setHeader('User-Agent', $this->options->generator)
                ->setTimeout(10);
            $result = ['available' => 0];

            try {
                $client->send('http://typecho.org/version.json');

                /** 匹配内容体 */
                $response = $client->getResponseBody();
                $json = json_decode($response, true);

                if (!empty($json)) {
                    $version = $this->options->version;

                    if (
                        isset($json['release'])
                        && preg_match("/^[0-9\.]+$/", $json['release'])
                        && version_compare($json['release'], $version, '>=')
                    ) {
                        $result = [
                            'available' => 1,
                            'latest'    => $json['release'],
                            'current'   => $version,
                            'link'      => 'http://typecho.org/download'
                        ];
                    }
                }
            } catch (\Exception $e) {
                // do nothing
            }

            $this->response->throwJson($result);
        }

        throw new Exception(_t('禁止访问'), 403);
    }

    /**
     * 远程请求代理
     *
     * @throws Exception
     * @throws Client\Exception|\Typecho\Db\Exception
     */
    public function feed()
    {
        $this->user->pass('subscriber');
        $client = Client::get();
        if ($client) {
            $client->setHeader('User-Agent', $this->options->generator)
                ->setTimeout(10)
                ->send('http://typecho.org/feed/');

            /** 匹配内容体 */
            $response = $client->getResponseBody();
            preg_match_all(
                "/<item>\s*<title>([^>]*)<\/title>\s*<link>([^>]*)<\/link>\s*<guid>[^>]*<\/guid>\s*<pubDate>([^>]*)<\/pubDate>/is",
                $response,
                $matches
            );

            $data = [];

            if ($matches) {
                foreach ($matches[0] as $key => $val) {
                    $data[] = [
                        'title' => $matches[1][$key],
                        'link'  => $matches[2][$key],
                        'date'  => date('n.j', strtotime($matches[3][$key]))
                    ];

                    if ($key > 8) {
                        break;
                    }
                }
            }

            $this->response->throwJson($data);
        }

        throw new Exception(_t('禁止访问'), 403);
    }

    /**
     * 自定义编辑器大小
     *
     * @throws \Typecho\Db\Exception|Exception
     */
    public function editorResize()
    {
        $this->user->pass('contributor');
        if (
            $this->db->fetchObject($this->db->select(['COUNT(*)' => 'num'])
                ->from('table.options')->where('name = ? AND user = ?', 'editorSize', $this->user->uid))->num > 0
        ) {
            parent::update(
                ['value' => $this->request->size],
                $this->db->sql()->where('name = ? AND user = ?', 'editorSize', $this->user->uid)
            );
        } else {
            parent::insert([
                'name'  => 'editorSize',
                'value' => $this->request->size,
                'user'  => $this->user->uid
            ]);
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
        if (!$this->request->isAjax()) {
            $this->response->goBack();
        }

        $this->on($this->request->is('do=remoteCallback'))->remoteCallback();
        $this->on($this->request->is('do=feed'))->feed();
        $this->on($this->request->is('do=checkVersion'))->checkVersion();
        $this->on($this->request->is('do=editorResize'))->editorResize();
    }
}
