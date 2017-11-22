<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

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
     * @var boolean
     */
    private $_enabled = true;

    /**
     * 初始化函数
     *
     */
    public function execute()
    {
        $this->_options = $this->widget('Widget_Options');
        $user = $this->widget('Widget_User');

        $this->_token = $this->_options->secret;
        if ($user->hasLogin()) {
            $this->_token .= '&' . $user->authCode . '&' . $user->uid;
        }
    }

    /**
     * 在系统升级的时候进行安全性检查
     *
     * @return array
     */
    public function systemCheck()
    {
        $errors = array();

        // 检查安装文件的安全性
        $installFile = __TYPECHO_ROOT_DIR__ . '/install.php';
        if (file_exists($installFile)) {
            $installFileContents = file_get_contents($installFile);

            if (0 !== strpos($installFileContents,
                    '<?php if (!file_exists(dirname(__FILE__) . \'/config.inc.php\')): ?>') ||
                false !== strpos($installFileContents,
                    '!isset($_GET[\'finish\']) && file_exists(__TYPECHO_ROOT_DIR__ . \'/config.inc.php\') && empty($_SESSION[\'typecho\'])')) {
                $errors[] = _t('您正在运行一个不安全的安装脚本 <strong>%s</strong>, 请用新版中的对应文件替代或者直接删除它', $installFile);
            }
        }

        // 验证入口文件
        $indexFile = __TYPECHO_ROOT_DIR__ . '/index.php';
        if (md5_file($indexFile) != 'f4dae7ceb7002cf4f95d380f5ced906b') {
            $errors[] = _t('当前网站的入口文件 <strong>%s</strong> 与最新版中的不一致, 请更新', $indexFile);
        }

        return $errors;
    }

    /**
     * @param $enabled
     */
    public function enable($enabled = true)
    {
        $this->_enabled = $enabled;
    }

    /**
     * 获取token
     *
     * @param string $suffix 后缀
     * @return string
     */
    public function getToken($suffix)
    {
        return md5($this->_token . '&' . $suffix);
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

        $params['_'] = $this->getToken($this->request->getRequestUrl());
        $parts['query'] = http_build_query($params);

        return Typecho_Common::buildUrl($parts);
    }

    /**
     * 保护提交数据
     *
     */
    public function protect()
    {
        if ($this->_enabled && $this->request->get('_') != $this->getToken($this->request->getReferer())) {
            $this->response->goBack();
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
 
