<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

define('__TYPECHO_FILTER_SUPPORTED__', function_exists('filter_var'));

/**
 * 服务器请求处理类
 *
 * @package Request
 */
class Typecho_Request
{
    /**
     * 内部参数
     *
     * @access private
     * @var array
     */
    private $_params = array();

    /**
     * 路径信息
     *
     * @access private
     * @var string
     */
    private $_pathInfo = NULL;

    /**
     * 服务端参数
     *
     * @access private
     * @var array
     */
    private $_server = array();

    /**
     * _requestUri  
     * 
     * @var string
     * @access private
     */
    private $_requestUri = NULL;

    /**
     * _requestRoot  
     * 
     * @var mixed
     * @access private
     */
    private $_requestRoot = NULL;

    /**
     * 获取baseurl
     * 
     * @var string
     * @access private
     */
    private $_baseUrl = NULL;

    /**
     * 客户端ip地址
     *
     * @access private
     * @var string
     */
    private $_ip = NULL;

    /**
     * 客户端字符串
     *
     * @access private
     * @var string
     */
    private $_agent = NULL;

    /**
     * 来源页
     *
     * @access private
     * @var string
     */
    private $_referer = NULL;

    /**
     * 单例句柄
     *
     * @access private
     * @var Typecho_Request
     */
    private static $_instance = NULL;

    /**
     * 全部的http数据
     *
     * @var bool|array
     */
    private static $_httpParams = false;

    
    /**
     * 域名前缀
     *
     * @var string
     */
    private static $_urlPrefix = NULL;

    /**
     * 当前过滤器
     *
     * @access private
     * @var array
     */
    private $_filter = array();

    /**
     * 支持的过滤器列表
     *
     * @access private
     * @var string
     */
    private static $_supportFilters = array(
        'int'       =>  'intval',
        'integer'   =>  'intval',
        'search'    =>  array('Typecho_Common', 'filterSearchQuery'),
        'xss'       =>  array('Typecho_Common', 'removeXSS'),
        'url'       =>  array('Typecho_Common', 'safeUrl'),
        'slug'      =>  array('Typecho_Common', 'slugName')
    );

    /**
     * 获取单例句柄
     *
     * @access public
     * @return Typecho_Request
     */
    public static function getInstance()
    {
        if (NULL === self::$_instance) {
            self::$_instance = new Typecho_Request();
        }

        return self::$_instance;
    }

    /**
     * 应用过滤器
     *
     * @access private
     * @param mixed $value
     * @return mixed
     */
    private function _applyFilter($value)
    {
        if ($this->_filter) {
            foreach ($this->_filter as $filter) {
                $value = is_array($value) ? array_map($filter, $value) :
                call_user_func($filter, $value);
            }

            $this->_filter = array();
        }

        return $value;
    }

    /**
     * 检查ip地址是否合法
     *
     * @param string $ip ip地址
     * @return boolean
     */
    private function _checkIp($ip)
    {
        if (__TYPECHO_FILTER_SUPPORTED__) {
            return false !== (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
        }

        return preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ip)
            || preg_match("/^[0-9a-f:]+$/i", $ip);
    }

    /**
     * 检查ua是否合法
     *
     * @param string $agent ua字符串
     * @return boolean
     */
    private function _checkAgent($agent)
    {
        return preg_match("/^[_a-z0-9- ,:;=#@\.\(\)\/\+\*\?]+$/i", $agent);
    } 

    /**
     * 初始化变量
     */
    public function __construct()
    {
        if (false === self::$_httpParams) {
            self::$_httpParams = array_filter(array_merge($_POST, $_GET),
                array('Typecho_Common', 'checkStrEncoding'));
        }
    }

    /**
     * 获取url前缀 
     * 
     * @access public
     * @return string
     */
    public static function getUrlPrefix()
    {
        if (empty(self::$_urlPrefix)) {
            if (defined('__TYPECHO_URL_PREFIX__')) {
                self::$_urlPrefix == __TYPECHO_URL_PREFIX__;
            } else if (!defined('__TYPECHO_CLI__')) {
                self::$_urlPrefix = (self::isSecure() ? 'https' : 'http') . '://' 
                    . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
            }
        }

        return self::$_urlPrefix;
    }

    /**
     * 判断是否为https
     *
     * @access public
     * @return boolean
     */
    public static function isSecure()
    {
        return (!empty($_SERVER['HTTPS']) && 'off' != strtolower($_SERVER['HTTPS'])) 
            || (!empty($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT'])
            || (defined('__TYPECHO_SECURE__') && __TYPECHO_SECURE__);
    }

    /**
     * 设置过滤器
     *
     * @access public
     * @return Typecho_Request
     */
    public function filter()
    {
        $filters = func_get_args();

        foreach ($filters as $filter) {
            $this->_filter[] = is_string($filter) && isset(self::$_supportFilters[$filter])
            ? self::$_supportFilters[$filter] : $filter;
        }

        return $this;
    }

    /**
     * 获取实际传递参数(magic)
     *
     * @access public
     * @param string $key 指定参数
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * 判断参数是否存在
     *
     * @access public
     * @param string $key 指定参数
     * @return boolean
     */
    public function __isset($key)
    {
        return isset(self::$_httpParams[$key])
        || isset($this->_params[$key]);
    }

    /**
     * 获取实际传递参数
     *
     * @access public
     * @param string $key 指定参数
     * @param mixed $default 默认参数 (default: NULL)
     * @return mixed
     */
    public function get($key, $default = NULL)
    {
        switch (true) {
            case isset($this->_params[$key]):
                $value = $this->_params[$key];
                break;
            case isset(self::$_httpParams[$key]):
                $value = self::$_httpParams[$key];
                break;
            default:
                $value = $default;
                break;
        }

        $value = !is_array($value) && strlen($value) > 0 ? $value : $default;
        return $this->_applyFilter($value);
    }

    /**
     * 获取一个数组
     *
     * @param $key
     * @return array
     */
    public function getArray($key)
    {
        $result = isset($this->_params[$key]) ? $this->_params[$key] :
            (isset(self::$_httpParams[$key]) ? self::$_httpParams[$key] : array());

        $result = is_array($result) ? $result
            : (strlen($result) > 0 ? array($result) : array());
        return $this->_applyFilter($result);
    }

    /**
     * 从参数列表指定的值中获取http传递参数
     *
     * @access public
     * @param mixed $params 指定的参数
     * @return array
     */
    public function from($params)
    {
        $result = array();
        $args = is_array($params) ? $params : func_get_args();

        foreach ($args as $arg) {
            $result[$arg] = $this->get($arg);
        }

        return $result;
    }

    /**
     * 设置http传递参数
     *
     * @access public
     * @param string $name 指定的参数
     * @param mixed $value 参数值
     * @return void
     */
    public function setParam($name, $value)
    {
        if (Typecho_Common::checkStrEncoding($value)) {
            $this->_params[$name] = $value;
        }
    }

    /**
     * 设置多个参数
     *
     * @access public
     * @param mixed $params 参数列表
     * @return void
     */
    public function setParams($params)
    {
        //处理字符串
        if (!is_array($params)) {
            parse_str($params, $out);
            $params = $out;
        }

        $this->_params = array_merge($this->_params,
            array_filter($params, array('Typecho_Common', 'checkStrEncoding')));
    }

    /**
     * getRequestRoot 
     * 
     * @access public
     * @return string
     */
    public function getRequestRoot()
    {
        if (NULL === $this->_requestRoot) {
            $root = rtrim(self::getUrlPrefix() . $this->getBaseUrl(), '/') . '/';
            
            $pos = strrpos($root, '.php/');
            if ($pos) {
                $root = dirname(substr($root, 0, $pos));
            }

            $this->_requestRoot = rtrim($root, '/');
        }

        return $this->_requestRoot;
    }

    /**
     * 获取当前请求url
     * 
     * @access public
     * @return string
     */
    public function getRequestUrl()
    {
        return self::getUrlPrefix() . $this->getRequestUri();
    }

    /**
     * 获取请求地址
     * 
     * @access public
     * @return string
     */
    public function getRequestUri()
    {
        if (!empty($this->_requestUri)) {
            return $this->_requestUri;
        }

        //处理requestUri
        $requestUri = '/';

        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (
            // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
            isset($_SERVER['IIS_WasUrlRewritten'])
            && $_SERVER['IIS_WasUrlRewritten'] == '1'
            && isset($_SERVER['UNENCODED_URL'])
            && $_SERVER['UNENCODED_URL'] != ''
            ) {
            $requestUri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            $parts       = @parse_url($requestUri);
            
            if (isset($_SERVER['HTTP_HOST']) && strstr($requestUri, $_SERVER['HTTP_HOST'])) {
                if (false !== $parts) {
                    $requestUri  = (empty($parts['path']) ? '' : $parts['path'])
                                 . ((empty($parts['query'])) ? '' : '?' . $parts['query']);
                }
            } elseif (!empty($_SERVER['QUERY_STRING']) && empty($parts['query'])) {
                // fix query missing
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        return $this->_requestUri = $requestUri;
    }

    /**
     * getBaseUrl  
     * 
     * @access public
     * @return string
     */
    public function getBaseUrl()
    {
        if (NULL !== $this->_baseUrl) {
            return $this->_baseUrl;
        }

        //处理baseUrl
        $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

        if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['SCRIPT_NAME'];
        } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
            $baseUrl = $_SERVER['PHP_SELF'];
        } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path    = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
            $file    = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
            $segs    = explode('/', trim($file, '/'));
            $segs    = array_reverse($segs);
            $index   = 0;
            $last    = count($segs);
            $baseUrl = '';
            do {
                $seg     = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
        }

        // Does the baseUrl have anything in common with the request_uri?
        $finalBaseUrl = NULL;
        $requestUri = $this->getRequestUri();

        if (0 === strpos($requestUri, $baseUrl)) {
            // full $baseUrl matches
            $finalBaseUrl = $baseUrl;
        } else if (0 === strpos($requestUri, dirname($baseUrl))) {
            // directory portion of $baseUrl matches
            $finalBaseUrl = rtrim(dirname($baseUrl), '/');
        } else if (!strpos($requestUri, basename($baseUrl))) {
            // no match whatsoever; set it blank
            $finalBaseUrl = '';
        } else if ((strlen($requestUri) >= strlen($baseUrl))
            && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
        {
            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return ($this->_baseUrl = (NULL === $finalBaseUrl) ? rtrim($baseUrl, '/') : $finalBaseUrl);
    }

    /**
     * 根据当前uri构造指定参数的uri
     *
     * @access public
     * @param mixed $parameter 指定的参数
     * @return string
     */
    public function makeUriByRequest($parameter = NULL)
    {
        /** 初始化地址 */
        $requestUri = $this->getRequestUrl();
        $parts = parse_url($requestUri);

        /** 初始化参数 */
        if (is_string($parameter)) {
            parse_str($parameter, $args);
        } else if (is_array($parameter)) {
            $args = $parameter;
        } else {
            return $requestUri;
        }

        /** 构造query */
        if (isset($parts['query'])) {
            parse_str($parts['query'], $currentArgs);
            $args = array_merge($currentArgs, $args);
        }
        $parts['query'] = http_build_query($args);

        /** 返回地址 */
        return Typecho_Common::buildUrl($parts);
    }

    /**
     * 获取当前pathinfo
     *
     * @access public
     * @param string $inputEncoding 输入编码
     * @param string $outputEncoding 输出编码
     * @return string
     */
    public function getPathInfo($inputEncoding = NULL, $outputEncoding = NULL)
    {
        /** 缓存信息 */
        if (NULL !== $this->_pathInfo) {
            return $this->_pathInfo;
        }

        //参考Zend Framework对pahtinfo的处理, 更好的兼容性
        $pathInfo = NULL;

        //处理requestUri
        $requestUri = $this->getRequestUri();
        $finalBaseUrl = $this->getBaseUrl();

        // Remove the query string from REQUEST_URI
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if ((NULL !== $finalBaseUrl)
            && (false === ($pathInfo = substr($requestUri, strlen($finalBaseUrl)))))
        {
            // If substr() returns false then PATH_INFO is set to an empty string
            $pathInfo = '/';
        } elseif (NULL === $finalBaseUrl) {
            $pathInfo = $requestUri;
        }

        if (!empty($pathInfo)) {
            //针对iis的utf8编码做强制转换
            //参考http://docs.moodle.org/ja/%E5%A4%9A%E8%A8%80%E8%AA%9E%E5%AF%BE%E5%BF%9C%EF%BC%9A%E3%82%B5%E3%83%BC%E3%83%90%E3%81%AE%E8%A8%AD%E5%AE%9A
            if (!empty($inputEncoding) && !empty($outputEncoding) &&
            (stripos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false
            || stripos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false)) {
                if (function_exists('mb_convert_encoding')) {
                    $pathInfo = mb_convert_encoding($pathInfo, $outputEncoding, $inputEncoding);
                } else if (function_exists('iconv')) {
                    $pathInfo = iconv($inputEncoding, $outputEncoding, $pathInfo);
                }
            }
        } else {
            $pathInfo = '/';
        }

        // fix issue 456
        return ($this->_pathInfo = '/' . ltrim(urldecode($pathInfo), '/'));
    }

    /**
     * 设置服务端参数
     *
     * @access public
     * @param string $name 参数名称
     * @param mixed $value 参数值
     * @return void
     */
    public function setServer($name, $value = NULL)
    {
        if (NULL == $value) {
            if (isset($_SERVER[$name])) {
                $value = $_SERVER[$name];
            } else if (isset($_ENV[$name])) {
                $value = $_ENV[$name];
            }
        }

        $this->_server[$name] = $value;
    }

    /**
     * 获取环境变量
     *
     * @access public
     * @param string $name 获取环境变量名
     * @return string
     */
    public function getServer($name)
    {
        if (!isset($this->_server[$name])) {
            $this->setServer($name);
        }

        return $this->_server[$name];
    }

    /**
     * 设置ip地址
     *
     * @access public
     * @param string $ip
     */
    public function setIp($ip = NULL)
    {
        if (!empty($ip)) {
            $this->_ip = $ip;
        } else {
            switch (true) {
                case defined('__TYPECHO_IP_SOURCE__') && NULL !== $this->getServer(__TYPECHO_IP_SOURCE__):
                    list($this->_ip) = array_map('trim', explode(',', $this->getServer(__TYPECHO_IP_SOURCE__)));
                    break;
                case NULL !== $this->getServer('REMOTE_ADDR'):
                    $this->_ip = $this->getServer('REMOTE_ADDR');
                    break;
                case NULL !== $this->getServer('HTTP_CLIENT_IP'):
                    $this->_ip = $this->getServer('HTTP_CLIENT_IP');
                    break;
                default:
                    break;
            }
        }

        if (empty($this->_ip) || !self::_checkIp($this->_ip)) {
            $this->_ip = 'unknown';
        }
    }

    /**
     * 获取ip地址
     *
     * @access public
     * @return string
     */
    public function getIp()
    {
        if (NULL === $this->_ip) {
            $this->setIp();
        }

        return $this->_ip;
    }

    /**
     * 设置客户端
     *
     * @access public
     * @param string $agent 客户端字符串
     * @return void
     */
    public function setAgent($agent = NULL)
    {
        $agent = (NULL === $agent) ? $this->getServer('HTTP_USER_AGENT') : $agent;
        $this->_agent = self::_checkAgent($agent) ? $agent : '';
    }

    /**
     * 获取客户端
     *
     * @access public
     * @return string
     */
    public function getAgent()
    {
        if (NULL === $this->_agent) {
            $this->setAgent();
        }

        return $this->_agent;
    }

    /**
     * 设置来源页
     *
     * @access public
     * @param string $referer 客户端字符串
     * @return void
     */
    public function setReferer($referer = NULL)
    {
        $this->_referer = (NULL === $referer) ? $this->getServer('HTTP_REFERER') : $referer;
    }

    /**
     * 获取客户端
     *
     * @access public
     * @return string
     */
    public function getReferer()
    {
        if (NULL === $this->_referer) {
            $this->setReferer();
        }

        return $this->_referer;
    }

    /**
     * 判断是否为get方法
     *
     * @access public
     * @return boolean
     */
    public function isGet()
    {
        return 'GET' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * 判断是否为post方法
     *
     * @access public
     * @return boolean
     */
    public function isPost()
    {
        return 'POST' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * 判断是否为put方法
     *
     * @access public
     * @return boolean
     */
    public function isPut()
    {
        return 'PUT' == $this->getServer('REQUEST_METHOD');
    } 

    /**
     * 判断是否为ajax
     *
     * @access public
     * @return boolean
     */
    public function isAjax()
    {
        return 'XMLHttpRequest' == $this->getServer('HTTP_X_REQUESTED_WITH');
    }

    /**
     * 判断是否为flash
     *
     * @access public
     * @return boolean
     */
    public function isFlash()
    {
        return 'Shockwave Flash' == $this->getServer('USER_AGENT');
    }

    /**
     * isMobile  
     * 
     * @static
     * @access public
     * @return boolean
     */
    public function isMobile()
    {
        $userAgent = $this->getAgent();
        return preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$userAgent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($userAgent,0,4));
    }

    /**
     * 判断输入是否满足要求
     *
     * @access public
     * @param mixed $query 条件
     * @return boolean
     */
    public function is($query)
    {
        $validated = false;

        /** 解析串 */
        if (is_string($query)) {
            parse_str($query, $params);
        } else if (is_array($query)) {
            $params = $query;
        }

        /** 验证串 */
        if ($params) {
            $validated = true;
            foreach ($params as $key => $val) {
                $validated = empty($val) ? $this->__isset($key) : ($val == $this->get($key));

                if (!$validated) {
                    break;
                }
            }
        }

        return $validated;
    }
}
