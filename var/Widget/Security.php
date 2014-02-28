<?php

/**
 * 安全选项组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2014 Typecho team (http://typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Security extends Typecho_Widget
{
    /**
     * @var string
     */
    private $_token;

    /**
     * @var Widget_Options
     */
    private $_options;

    /**
     * 初始化函数
     *
     */
    public function execute()
    {
        $this->_options = $this->widget('Widget_Options');
        $user = $this->widget('Widget_User');

        $token = uniqid();
        if ($user->hasLogin()) {
            $token = $user->authCode . '&' . $user->uid
                . '&' . $this->request->getRequestUrl()
                . '&' . $this->request->getIp();
        }

        $this->_token = md5($token);
    }

    /**
     * 生成带token的路径
     *
     * @param $path
     * @return string
     */
    public function getTokenUrl($path)
    {
        $parts = parse_url($path);
        $params = array();

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        $params['_'] = $this->_token;
        $parts['query'] = http_build_query($params);

        return Typecho_Common::buildUrl($parts);
    }

    /**
     * 保护提交数据
     *
     */
    public function protect()
    {
        $user = $this->widget('Widget_User');
        $token = uniqid();
        if ($user->hasLogin()) {
            $token = $user->authCode . '&' . $user->uid
                . '&' . $this->request->getReferer()
                . '&' . $this->request->getIp();
        }

        if ($this->request->get('_') != md5($token)) {
            $this->widget('Widget_Notice')->set(_t('一次不安全的跳转已经被阻止'));
            $this->response->redirect($this->_options->adminUrl);
        }
    }

    /**
     * 获取安全的后台路径
     *
     * @param string $path
     * @return string
     */
    public function getAdminUrl($path)
    {
        return Typecho_Common::url($this->getTokenUrl($path), $this->_options->adminUrl);
    }

    /**
     * 获取安全的路由路径
     *
     * @param $path
     * @return string
     */
    public function getIndex($path)
    {
        return Typecho_Common::url($this->getTokenUrl($path), $this->_options->index);
    }

    /**
     * 获取绝对路由路径
     *
     * @param $path
     * @return string
     */
    public function getRootUrl($path)
    {
        return Typecho_Common::url($this->getTokenUrl($path), $this->_options->rootUrl);
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
     * 输出安全的路由路径
     *
     * @param $path
     */
    public function index($path)
    {
        echo $this->getIndex($path);
    }
}
 