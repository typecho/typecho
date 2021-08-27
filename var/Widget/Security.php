<?php

namespace Widget;

use Typecho\Common;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 安全选项组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2014 Typecho team (http://typecho.org)
 * @license GNU General Public License 2.0
 */
class Security extends Widget
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var boolean
     */
    private $enabled = true;

    /**
     * 初始化函数
     */
    public function execute()
    {
        $this->options = self::widget(Options::class);
        $user = self::widget(User::class);

        $this->token = $this->options->secret;
        if ($user->hasLogin()) {
            $this->token .= '&' . $user->authCode . '&' . $user->uid;
        }
    }

    /**
     * @param bool $enabled
     */
    public function enable(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * 保护提交数据
     */
    public function protect()
    {
        if ($this->enabled && $this->request->get('_') != $this->getToken($this->request->getReferer())) {
            $this->response->goBack();
        }
    }

    /**
     * 获取token
     *
     * @param string $suffix 后缀
     * @return string
     */
    public function getToken(string $suffix): string
    {
        return md5($this->token . '&' . $suffix);
    }

    /**
     * 获取绝对路由路径
     *
     * @param $path
     * @return string
     */
    public function getRootUrl($path): string
    {
        return Common::url($this->getTokenUrl($path), $this->options->rootUrl);
    }

    /**
     * 生成带token的路径
     *
     * @param $path
     * @param string|null $url
     * @return string
     */
    public function getTokenUrl($path, ?string $url = null): string
    {
        $parts = parse_url($path);
        $params = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        $params['_'] = $this->getToken($url ?: $this->request->getRequestUrl());
        $parts['query'] = http_build_query($params);

        return Common::buildUrl($parts);
    }

    /**
     * 输出后台安全路径
     *
     * @param $path
     */
    public function adminUrl($path)
    {
        echo $this->getAdminUrl($path);
    }

    /**
     * 获取安全的后台路径
     *
     * @param string $path
     * @return string
     */
    public function getAdminUrl(string $path): string
    {
        return Common::url($this->getTokenUrl($path), $this->options->adminUrl);
    }

    /**
     * 输出安全的路由路径
     *
     * @param $path
     */
    public function index($path)
    {
        echo $this->getIndex($path);
    }

    /**
     * 获取安全的路由路径
     *
     * @param $path
     * @return string
     */
    public function getIndex($path): string
    {
        return Common::url($this->getTokenUrl($path), $this->options->index);
    }
}
 
