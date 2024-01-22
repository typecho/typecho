<?php

namespace Widget;

use Typecho\Common;
use Typecho\Config;
use Typecho\Db;
use Typecho\Router;
use Typecho\Router\Parser;
use Typecho\Widget;
use Typecho\Plugin\Exception as PluginException;
use Typecho\Db\Exception as DbException;
use Typecho\Date;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 全局选项组件
 *
 * @property string $feedUrl
 * @property string $feedRssUrl
 * @property string $feedAtomUrl
 * @property string $commentsFeedUrl
 * @property string $commentsFeedRssUrl
 * @property string $commentsFeedAtomUrl
 * @property string $themeUrl
 * @property string $xmlRpcUrl
 * @property string $index
 * @property string $siteUrl
 * @property string $siteDomain
 * @property array $routingTable
 * @property string $rootUrl
 * @property string $pluginUrl
 * @property string $pluginDir
 * @property string $adminUrl
 * @property string $loginUrl
 * @property string $originalSiteUrl
 * @property string $loginAction
 * @property string $registerUrl
 * @property string $registerAction
 * @property string $profileUrl
 * @property string $logoutUrl
 * @property string $title
 * @property string $description
 * @property string $keywords
 * @property string $lang
 * @property string $theme
 * @property string|null $missingTheme
 * @property int $pageSize
 * @property int $serverTimezone
 * @property int $timezone
 * @property string $charset
 * @property string $contentType
 * @property string $generator
 * @property string $software
 * @property string $version
 * @property bool $markdown
 * @property bool $xmlrpcMarkdown
 * @property array $allowedAttachmentTypes
 * @property string $attachmentTypes
 * @property int $time
 * @property string $frontPage
 * @property int $commentsListSize
 * @property bool $commentsShowCommentOnly
 * @property array $actionTable
 * @property array $panelTable
 * @property bool $commentsThreaded
 * @property bool $defaultAllowComment
 * @property bool $defaultAllowPing
 * @property bool $defaultAllowFeed
 * @property string $commentDateFormat
 * @property string $commentsAvatarRating
 * @property string $commentsPageDisplay
 * @property int $commentsPageSize
 * @property string $commentsOrder
 * @property bool $commentsMarkdown
 * @property bool $commentsShowUrl
 * @property bool $commentsUrlNofollow
 * @property bool $commentsAvatar
 * @property bool $commentsPageBreak
 * @property bool $commentsRequireModeration
 * @property bool $commentsWhitelist
 * @property bool $commentsRequireMail
 * @property bool $commentsRequireUrl
 * @property bool $commentsCheckReferer
 * @property bool $commentsAntiSpam
 * @property bool $commentsAutoClose
 * @property bool $commentsPostIntervalEnable
 * @property int $commentsMaxNestingLevels
 * @property int $commentsPostTimeout
 * @property int $commentsPostInterval
 * @property string $commentsHTMLTagAllowed
 * @property bool $allowRegister
 * @property int $allowXmlRpc
 * @property int $postsListSize
 * @property bool $feedFullText
 * @property int $defaultCategory
 * @property bool $frontArchive
 * @property array $plugins
 * @property string $secret
 * @property bool $installed
 * @property bool $rewrite
 * @property string $postDateFormat
 */
class Options extends Base
{
    /**
     * 缓存的插件配置
     *
     * @access private
     * @var array
     */
    private array $pluginConfig = [];

    /**
     * 缓存的个人插件配置
     *
     * @access private
     * @var array
     */
    private array $personalPluginConfig = [];

    /**
     * @param int $components
     */
    protected function initComponents(int &$components)
    {
        $components = self::INIT_NONE;
    }

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        if (!$parameter->isEmpty()) {
            $this->row = $this->parameter->toArray();
        } else {
            $this->db = Db::get();
        }
    }

    /**
     * 执行函数
     *
     * @throws DbException
     */
    public function execute()
    {
        $options = [];

        if (isset($this->db)) {
            $values = $this->db->fetchAll($this->db->select()->from('table.options')
                ->where('user = 0'));

            // finish install
            if (empty($values)) {
                $this->response->redirect(defined('__TYPECHO_ADMIN__')
                    ? '../install.php?step=3' : 'install.php?step=3');
            }

            $options = array_column($values, 'value', 'name');

            /** 支持皮肤变量重载 */
            $themeOptionsKey = 'theme:' . $options['theme'];
            if (!empty($options[$themeOptionsKey])) {
                $themeOptions = $this->tryDeserialize($options[$themeOptionsKey]);
                $options = array_merge($options, $themeOptions);
            }
        } elseif (function_exists('install_get_default_options')) {
            $defaultOptions = install_get_default_options();
            $initOptionKeys = ['routingTable', 'plugins', 'charset', 'contentType', 'timezone', 'installed', 'generator', 'siteUrl', 'lang', 'secret'];

            foreach ($initOptionKeys as $option) {
                $options[$option] = $defaultOptions[$option];
            }
        }

        $this->push($options);
    }

    /**
     * 获取皮肤文件
     *
     * @param string $theme
     * @param string $file
     * @return string
     */
    public function themeFile(string $theme, string $file = ''): string
    {
        return __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . '/' . trim($theme, './') . '/' . trim($file, './');
    }

    /**
     * 输出网站路径
     *
     * @param string|null $path 子路径
     */
    public function siteUrl(?string $path = null)
    {
        echo Common::url($path, $this->siteUrl);
    }

    /**
     * 输出解析地址
     *
     * @param string|null $path 子路径
     */
    public function index(?string $path = null)
    {
        echo Common::url($path, $this->index);
    }

    /**
     * 输出模板路径
     *
     * @param string|null $path 子路径
     * @param string|null $theme 模版名称
     * @return string | void
     */
    public function themeUrl(?string $path = null, ?string $theme = null)
    {
        if (!isset($theme)) {
            echo Common::url($path, $this->themeUrl);
        } else {
            $url = defined('__TYPECHO_THEME_URL__') ? __TYPECHO_THEME_URL__ :
                Common::url(__TYPECHO_THEME_DIR__ . '/' . $theme, $this->siteUrl);

            return isset($path) ? Common::url($path, $url) : $url;
        }
    }

    /**
     * 输出插件路径
     *
     * @param string|null $path 子路径
     */
    public function pluginUrl(?string $path = null)
    {
        echo Common::url($path, $this->pluginUrl);
    }

    /**
     * 获取插件目录
     *
     * @param string|null $plugin
     * @return string
     */
    public function pluginDir(?string $plugin = null): string
    {
        return Common::url($plugin, $this->pluginDir);
    }

    /**
     * 输出后台路径
     *
     * @param string|null $path 子路径
     * @param bool $return
     * @return void|string
     */
    public function adminUrl(?string $path = null, bool $return = false)
    {
        $url = Common::url($path, $this->adminUrl);

        if ($return) {
            return $url;
        }

        echo $url;
    }

    /**
     * 获取或输出后台静态文件路径
     *
     * @param string $type
     * @param string|null $file
     * @param bool $return
     * @return void|string
     */
    public function adminStaticUrl(string $type, ?string $file = null, bool $return = false)
    {
        $url = Common::url($type, $this->adminUrl);

        if (empty($file)) {
            return $url;
        }

        $url = Common::url($file, $url) . '?v=' . $this->version;

        if ($return) {
            return $url;
        }

        echo $url;
    }

    /**
     * 编码输出允许出现在评论中的html标签
     */
    public function commentsHTMLTagAllowed()
    {
        echo htmlspecialchars($this->commentsHTMLTagAllowed);
    }

    /**
     * 获取插件系统参数
     *
     * @param mixed $pluginName 插件名称
     * @return mixed
     * @throws PluginException
     */
    public function plugin($pluginName)
    {
        if (!isset($this->pluginConfig[$pluginName])) {
            if (
                !empty($this->row['plugin:' . $pluginName])
                && false !== ($options = $this->tryDeserialize($this->row['plugin:' . $pluginName]))
            ) {
                $this->pluginConfig[$pluginName] = new Config($options);
            } else {
                throw new PluginException(_t('插件%s的配置信息没有找到', $pluginName), 500);
            }
        }

        return $this->pluginConfig[$pluginName];
    }

    /**
     * 获取个人插件系统参数
     *
     * @param mixed $pluginName 插件名称
     *
     * @return mixed
     * @throws PluginException
     */
    public function personalPlugin($pluginName)
    {
        if (!isset($this->personalPluginConfig[$pluginName])) {
            if (
                !empty($this->row['_plugin:' . $pluginName])
                && false !== ($options = $this->tryDeserialize($this->row['_plugin:' . $pluginName]))
            ) {
                $this->personalPluginConfig[$pluginName] = new Config($options);
            } else {
                throw new PluginException(_t('插件%s的配置信息没有找到', $pluginName), 500);
            }
        }

        return $this->personalPluginConfig[$pluginName];
    }

    /**
     * @return array
     */
    protected function ___routingTable(): array
    {
        $routingTable = $this->tryDeserialize($this->row['routingTable']);

        if (isset($this->db) && !isset($routingTable[0])) {
            /** 解析路由并缓存 */
            $parser = new Parser($routingTable);
            $parsedRoutingTable = $parser->parse();
            $routingTable = array_merge([$parsedRoutingTable], $routingTable);
            $this->db->query($this->db->update('table.options')->rows(['value' => json_encode($routingTable)])
                ->where('name = ?', 'routingTable'));
        }

        return $routingTable;
    }

    /**
     * @return array
     */
    protected function ___actionTable(): array
    {
        return $this->tryDeserialize($this->row['actionTable']);
    }

    /**
     * @return array
     */
    protected function ___panelTable(): array
    {
        return $this->tryDeserialize($this->row['panelTable']);
    }

    /**
     * @return array
     */
    protected function ___plugins(): array
    {
        return $this->tryDeserialize($this->row['plugins']);
    }

    /**
     * 动态判断皮肤目录
     *
     * @return string|null
     */
    protected function ___missingTheme(): ?string
    {
        return !is_dir($this->themeFile($this->row['theme'])) ? $this->row['theme'] : null;
    }

    /**
     * @return string
     */
    protected function ___theme(): string
    {
        return $this->missingTheme ? 'default' : $this->row['theme'];
    }

    /**
     * 动态获取根目录
     *
     * @return string
     */
    protected function ___rootUrl(): string
    {
        $rootUrl = defined('__TYPECHO_ROOT_URL__') ? __TYPECHO_ROOT_URL__ : $this->request->getRequestRoot();

        if (defined('__TYPECHO_ADMIN__')) {
            /** 识别在admin目录中的情况 */
            $adminDir = '/' . trim(defined('__TYPECHO_ADMIN_DIR__') ? __TYPECHO_ADMIN_DIR__ : '/admin/', '/');
            $rootUrl = substr($rootUrl, 0, - strlen($adminDir));
        }

        return $rootUrl;
    }

    /**
     * @return string
     */
    protected function ___originalSiteUrl(): string
    {
        $siteUrl = $this->row['siteUrl'];

        if (defined('__TYPECHO_SITE_URL__')) {
            $siteUrl = __TYPECHO_SITE_URL__;
        } elseif (defined('__TYPECHO_DYNAMIC_SITE_URL__') && __TYPECHO_DYNAMIC_SITE_URL__) {
            $siteUrl = $this->rootUrl;
        }

        return $siteUrl;
    }

    /**
     * @return string
     */
    protected function ___siteUrl(): string
    {
        $siteUrl = Common::url(null, $this->originalSiteUrl);

        /** 增加对SSL连接的支持 */
        if ($this->request->isSecure() && 0 === strpos($siteUrl, 'http://')) {
            $siteUrl = substr_replace($siteUrl, 'https', 0, 4);
        }

        return $siteUrl;
    }

    /**
     * @return string
     */
    protected function ___siteDomain(): string
    {
        return parse_url($this->siteUrl, PHP_URL_HOST);
    }

    /**
     * RSS2.0
     *
     * @return string
     */
    protected function ___feedUrl(): string
    {
        return Router::url('feed', ['feed' => '/'], $this->index);
    }

    /**
     * RSS1.0
     *
     * @return string
     */
    protected function ___feedRssUrl(): string
    {
        return Router::url('feed', ['feed' => '/rss/'], $this->index);
    }

    /**
     * ATOM1.O
     *
     * @return string
     */
    protected function ___feedAtomUrl(): string
    {
        return Router::url('feed', ['feed' => '/atom/'], $this->index);
    }

    /**
     * 评论RSS2.0聚合
     *
     * @return string
     */
    protected function ___commentsFeedUrl(): string
    {
        return Router::url('feed', ['feed' => '/comments/'], $this->index);
    }

    /**
     * 评论RSS1.0聚合
     *
     * @return string
     */
    protected function ___commentsFeedRssUrl(): string
    {
        return Router::url('feed', ['feed' => '/rss/comments/'], $this->index);
    }

    /**
     * 评论ATOM1.0聚合
     *
     * @return string
     */
    protected function ___commentsFeedAtomUrl(): string
    {
        return Router::url('feed', ['feed' => '/atom/comments/'], $this->index);
    }

    /**
     * xmlrpc api地址
     *
     * @return string
     */
    protected function ___xmlRpcUrl(): string
    {
        return Router::url('do', ['action' => 'xmlrpc'], $this->index);
    }

    /**
     * 获取解析路径前缀
     *
     * @return string
     */
    protected function ___index(): string
    {
        return ($this->rewrite || (defined('__TYPECHO_REWRITE__') && __TYPECHO_REWRITE__))
            ? $this->rootUrl : Common::url('index.php', $this->rootUrl);
    }

    /**
     * 获取模板路径
     *
     * @return string
     */
    protected function ___themeUrl(): string
    {
        return $this->themeUrl(null, $this->theme);
    }

    /**
     * 获取插件路径
     *
     * @return string
     */
    protected function ___pluginUrl(): string
    {
        return defined('__TYPECHO_PLUGIN_URL__') ? __TYPECHO_PLUGIN_URL__ :
            Common::url(__TYPECHO_PLUGIN_DIR__, $this->siteUrl);
    }

    /**
     * @return string
     */
    protected function ___pluginDir(): string
    {
        return Common::url(__TYPECHO_PLUGIN_DIR__, __TYPECHO_ROOT_DIR__);
    }

    /**
     * 获取后台路径
     *
     * @return string
     */
    protected function ___adminUrl(): string
    {
        return Common::url(defined('__TYPECHO_ADMIN_DIR__') ?
            __TYPECHO_ADMIN_DIR__ : '/admin/', $this->rootUrl);
    }

    /**
     * 获取登录地址
     *
     * @return string
     */
    protected function ___loginUrl(): string
    {
        return Common::url('login.php', $this->adminUrl);
    }

    /**
     * 获取登录提交地址
     *
     * @return string
     */
    protected function ___loginAction(): string
    {
        return Security::alloc()->getTokenUrl(
            Router::url(
                'do',
                ['action' => 'login', 'widget' => 'Login'],
                Common::url('index.php', $this->rootUrl)
            )
        );
    }

    /**
     * 获取注册地址
     *
     * @return string
     */
    protected function ___registerUrl(): string
    {
        return Common::url('register.php', $this->adminUrl);
    }

    /**
     * 获取登录提交地址
     *
     * @return string
     * @throws Widget\Exception
     */
    protected function ___registerAction(): string
    {
        return Security::alloc()->getTokenUrl(
            Router::url('do', ['action' => 'register', 'widget' => 'Register'], $this->index)
        );
    }

    /**
     * 获取个人档案地址
     *
     * @return string
     */
    protected function ___profileUrl(): string
    {
        return Common::url('profile.php', $this->adminUrl);
    }

    /**
     * 获取登出地址
     *
     * @return string
     */
    protected function ___logoutUrl(): string
    {
        return Security::alloc()->getTokenUrl(
            Common::url('/action/logout', $this->index)
        );
    }

    /**
     * 获取系统时区
     *
     * @return integer
     */
    protected function ___serverTimezone(): int
    {
        return Date::$serverTimezoneOffset;
    }

    /**
     * 获取GMT标准时间
     *
     * @return integer
     * @deprecated
     */
    protected function ___gmtTime(): int
    {
        return Date::gmtTime();
    }

    /**
     * 获取时间
     *
     * @return integer
     * @deprecated
     */
    protected function ___time(): int
    {
        return Date::time();
    }

    /**
     * 获取格式
     *
     * @return string
     */
    protected function ___contentType(): string
    {
        return $this->contentType ?? 'text/html';
    }

    /**
     * 软件名称
     *
     * @return string
     */
    protected function ___software(): string
    {
        [$software] = explode(' ', $this->generator);
        return $software;
    }

    /**
     * 软件版本
     *
     * @return string
     */
    protected function ___version(): string
    {
        [, $version] = explode(' ', $this->generator);
        $pos = strpos($version, '/');

        // fix for old version
        if ($pos !== false) {
            $version = substr($version, 0, $pos) . '.0';
        }

        return $version;
    }

    /**
     * 允许上传的文件类型
     *
     * @return array
     */
    protected function ___allowedAttachmentTypes(): array
    {
        $attachmentTypesResult = [];

        if (null != $this->attachmentTypes) {
            $attachmentTypes = str_replace(
                ['@image@', '@media@', '@doc@'],
                [
                    'gif,jpg,jpeg,png,tiff,bmp,webp', 'mp3,mp4,mov,wmv,wma,rmvb,rm,avi,flv,ogg,oga,ogv',
                    'txt,doc,docx,xls,xlsx,ppt,pptx,zip,rar,pdf'
                ],
                $this->attachmentTypes
            );

            $attachmentTypesResult = array_unique(array_map('trim', preg_split("/([,.])/", $attachmentTypes)));
        }

        return $attachmentTypesResult;
    }

    /**
     * Try to deserialize a value
     *
     * @param string $value
     * @return mixed
     */
    private function tryDeserialize(string $value)
    {
        $isSerialized = strpos($value, 'a:') === 0 || $value === 'b:0;';
        return $isSerialized ? @unserialize($value) : json_decode($value, true);
    }
}
