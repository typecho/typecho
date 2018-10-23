<?php
/**
 * API方法,Typecho命名空间
 *
 * @category typecho
 * @package Common
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

define('__TYPECHO_MB_SUPPORTED__', function_exists('mb_get_info') && function_exists('mb_regex_encoding'));

/**
 * I18n function
 *
 * @param string $string 需要翻译的文字
 * @return string
 */
function _t($string) {
    if (func_num_args() <= 1) {
        return Typecho_I18n::translate($string);
    } else {
        $args = func_get_args();
        array_shift($args);
        return vsprintf(Typecho_I18n::translate($string), $args);
    }
}

/**
 * I18n function, translate and echo
 *
 * @param string $string 需要翻译并输出的文字
 * @return void
 */
function _e() {
    $args = func_get_args();
    echo call_user_func_array('_t', $args);
}

/**
 * 针对复数形式的翻译函数
 *
 * @param string $single 单数形式的翻译
 * @param string $plural 复数形式的翻译
 * @param integer $number 数字
 * @return string
 */
function _n($single, $plural, $number) {
    return str_replace('%d', $number, Typecho_I18n::ngettext($single, $plural, $number));
}

/**
 * Typecho公用方法
 *
 * @category typecho
 * @package Common
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Common
{
    /** 程序版本 */
    const VERSION = '1.2/18.10.23';

    /**
     * 允许的属性
     * 
     * @access private
     * @var array
     */
    private static $_allowableAttributes = array();

    /**
     * 默认编码
     *
     * @access public
     * @var string
     */
    public static $charset = 'UTF-8';

    /**
     * 异常处理类
     *
     * @access public
     * @var string
     */
    public static $exceptionHandle;

    /**
     * 将字符串变成大写的回调函数
     * 
     * @param array $matches 
     * @access public
     * @return string
     */
    public static function __strToUpper($matches)
    {
        return strtoupper($matches[0]);
    }

    /**
     * 将url中的非法xss去掉时的数组回调过滤函数
     *
     * @access private
     * @param string $string 需要过滤的字符串
     * @return string
     */
    public static function __removeUrlXss($string)
    {
        $string = str_replace(array('%0d', '%0a'), '', strip_tags($string));
        return preg_replace(array(
            "/\(\s*(\"|')/i",           //函数开头
            "/(\"|')\s*\)/i",           //函数结尾
        ), '', $string);
    }

    /**
     * 检查是否为安全路径
     *
     * @access public
     * @param string $path 检查是否为安全路径
     * @return boolean
     */
    public static function __safePath($path)
    {
        $safePath = rtrim(__TYPECHO_ROOT_DIR__, '/');
        return 0 === strpos($path, $safePath);
    }

    /**
     * __filterAttrs  
     * 
     * @param mixed $matches 
     * @static
     * @access public
     * @return bool
     */
    public static function __filterAttrs($matches)
    {
        if (!isset($matches[2])) {
            return $matches[0];
        }

        $str = trim($matches[2]);

        if (empty($str)) {
            return $matches[0];
        }

        $attrs = self::__parseAttrs($str);
        $parsedAttrs = array();
        $tag = strtolower($matches[1]);

        foreach ($attrs as $key => $val) {
            if (in_array($key, self::$_allowableAttributes[$tag])) {
                $parsedAttrs[] = " {$key}" . (empty($val) ? '' : "={$val}");
            }
        }

        return '<' . $tag . implode('', $parsedAttrs) . '>';
    }
    
    /**
     * 解析属性
     * 
     * @access public
     * @param string $attrs 属性字符串
     * @return array
     */
    public static function __parseAttrs($attrs)
    {
        $attrs = trim($attrs);
        $len = strlen($attrs);
        $pos = -1;
        $result = array();
        $quote = '';
        $key = '';
        $value = '';
        
        for ($i = 0; $i < $len; $i ++) {
            if ('=' != $attrs[$i] && !ctype_space($attrs[$i]) && -1 == $pos) {
                $key .= $attrs[$i];
                
                /** 最后一个 */
                if ($i == $len - 1) {
                    if ('' != ($key = trim($key))) {
                        $result[$key] = '';
                        $key = '';
                        $value = '';
                    }
                }
                
            } else if (ctype_space($attrs[$i]) && -1 == $pos) {
                $pos = -2;
            } else if ('=' == $attrs[$i] && 0 > $pos) {
                $pos = 0;
            } else if (('"' == $attrs[$i] || "'" == $attrs[$i]) && 0 == $pos) {
                $quote = $attrs[$i];
                $value .= $attrs[$i];
                $pos = 1;
            } else if ($quote != $attrs[$i] && 1 == $pos) {
                $value .= $attrs[$i];
            } else if ($quote == $attrs[$i] && 1 == $pos) {
                $pos = -1;
                $value .= $attrs[$i];
                $result[trim($key)] = $value;
                $key = '';
                $value = '';
            } else if ('=' != $attrs[$i] && !ctype_space($attrs[$i]) && -2 == $pos) {
                if ('' != ($key = trim($key))) {
                    $result[$key] = '';
                }
                
                $key = '';
                $value = '';
                $pos = -1;
                $key .= $attrs[$i];
            }
        }
        
        return $result;
    }

    /**
     * 自动载入类
     *
     * @param $className
     */
    public static function __autoLoad($className)
    {
        @include_once str_replace(array('\\', '_'), '/', $className) . '.php';
    }

    /**
     * 程序初始化方法
     *
     * @access public
     * @return void
     */
    public static function init()
    {
        /** 设置自动载入函数 */
        spl_autoload_register(array('Typecho_Common', '__autoLoad'));

        /** 兼容php6 */
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
            $_GET = self::stripslashesDeep($_GET);
            $_POST = self::stripslashesDeep($_POST);
            $_COOKIE = self::stripslashesDeep($_COOKIE);

            reset($_GET);
            reset($_POST);
            reset($_COOKIE);
        }

        /** 设置异常截获函数 */
        set_exception_handler(array('Typecho_Common', 'exceptionHandle'));
    }

    /**
     * 异常截获函数
     *
     * @access public
     * @param $exception 截获的异常
     * @return void
     */
    public static function exceptionHandle($exception)
    {
        if (defined('__TYPECHO_DEBUG__')) {
            echo '<pre><code>';
            echo '<h1>' . htmlspecialchars($exception->getMessage()) . '</h1>';
            echo htmlspecialchars($exception->__toString());
            echo '</code></pre>';
        } else {
            @ob_end_clean();
            if (404 == $exception->getCode() && !empty(self::$exceptionHandle)) {
                $handleClass = self::$exceptionHandle;
                new $handleClass($exception);
            } else {
                self::error($exception);
            }
        }

        exit;
    }

    /**
     * 输出错误页面
     *
     * @access public
     * @param mixed $exception 错误信息
     * @return void
     */
    public static function error($exception)
    {
        $isException = is_object($exception);
        $message = '';

        if ($isException) {
            $code = $exception->getCode();
            $message = $exception->getMessage();
        } else {
            $code = $exception;
        }

        $charset = self::$charset;

        if ($isException && $exception instanceof Typecho_Db_Exception) {
            $code = 500;
            @error_log($message);

            //覆盖原始错误信息
            $message = 'Database Server Error';

            if ($exception instanceof Typecho_Db_Adapter_Exception) {
                $code = 503;
                $message = 'Error establishing a database connection';
            } else if ($exception instanceof Typecho_Db_Query_Exception) {
                $message = 'Database Query Error';
            }
        } else {
            switch ($code) {
                case 500:
                    $message = 'Server Error';
                    break;

                case 404:
                    $message = 'Page Not Found';
                    break;

                default:
                    $code = 'Error';
                    break;
            }
        }


        /** 设置http code */
        if (is_numeric($code) && $code > 200) {
            Typecho_Response::setStatus($code);
        }

        $message = nl2br($message);

        if (defined('__TYPECHO_EXCEPTION_FILE__')) {
            require_once __TYPECHO_EXCEPTION_FILE__;
        } else {
            echo
<<<EOF
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="{$charset}">
        <title>{$code}</title>
        <style>
            html {
                padding: 50px 10px;
                font-size: 16px;
                line-height: 1.4;
                color: #666;
                background: #F6F6F3;
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }

            html,
            input { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
            body {
                max-width: 500px;
                _width: 500px;
                padding: 30px 20px;
                margin: 0 auto;
                background: #FFF;
            }
            ul {
                padding: 0 0 0 40px;
            }
            .container {
                max-width: 380px;
                _width: 380px;
                margin: 0 auto;
            }
        </style>
    </head>
    <body>
        <div class="container">
            {$message}
        </div>
    </body>
</html>
EOF;
        }

        exit;
    }

    /**
     * 判断类是否能被加载
     * 此函数会遍历所有的include目录, 所以会有一定的性能消耗, 但是不会很大
     * 可是我们依然建议你在必须检测一个类能否被加载时使用它, 它通常表现为以下两种情况
     * 1. 当需要被加载的类不存在时, 系统不会停止运行 (如果你不判断, 系统会因抛出严重错误而停止)
     * 2. 你需要知道哪些类无法被加载, 以提示使用者
     * 除了以上情况, 你无需关注那些类无法被加载, 因为当它们不存在时系统会自动停止并报错
     *
     * @access public
     * @param string $className 类名
     * @param string $path 指定的路径名称
     * @return boolean
     */
    public static function isAvailableClass($className, $path = NULL)
    {
        /** 获取所有include目录 */
        //增加安全目录检测 fix issue 106
        $dirs = array_map('realpath', array_filter(explode(PATH_SEPARATOR, get_include_path()),
        array('Typecho_Common', '__safePath')));

        $file = str_replace('_', '/', $className) . '.php';

        if (!empty($path)) {
            $path = realpath($path);
            if (in_array($path, $dirs)) {
                $dirs = array($path);
            } else {
                return false;
            }
        }

        foreach ($dirs as $dir) {
            if (!empty($dir)) {
                if (file_exists($dir . '/' . $file)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 检测是否在app engine上运行，屏蔽某些功能 
     * 
     * @static
     * @access public
     * @return boolean
     */
    public static function isAppEngine()
    {
        return !empty($_SERVER['HTTP_APPNAME'])                     // SAE
            || !!getenv('HTTP_BAE_ENV_APPID')                       // BAE
            || !!getenv('HTTP_BAE_LOGID')                           // BAE 3.0
            || (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false) // GAE
            ;
    }

    /**
     * 递归去掉数组反斜线
     *
     * @access public
     * @param mixed $value
     * @return mixed
     */
    public static function stripslashesDeep($value)
    {
        return is_array($value) ? array_map(array('Typecho_Common', 'stripslashesDeep'), $value) : stripslashes($value);
    }

    /**
     * 抽取多维数组的某个元素,组成一个新数组,使这个数组变成一个扁平数组
     * 使用方法:
     * <code>
     * <?php
     * $fruit = array(array('apple' => 2, 'banana' => 3), array('apple' => 10, 'banana' => 12));
     * $banana = Typecho_Common::arrayFlatten($fruit, 'banana');
     * print_r($banana);
     * //outputs: array(0 => 3, 1 => 12);
     * ?>
     * </code>
     *
     * @access public
     * @param array $value 被处理的数组
     * @param string $key 需要抽取的键值
     * @return array
     */
    public static function arrayFlatten(array $value, $key)
    {
        $result = array();

        if ($value) {
            foreach ($value as $inval) {
                if (is_array($inval) && isset($inval[$key])) {
                    $result[] = $inval[$key];
                } else {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * 根据parse_url的结果重新组合url
     *
     * @access public
     * @param array $params 解析后的参数
     * @return string
     */
    public static function buildUrl($params)
    {
        return (isset($params['scheme']) ? $params['scheme'] . '://' : NULL)
        . (isset($params['user']) ? $params['user'] . (isset($params['pass']) ? ':' . $params['pass'] : NULL) . '@' : NULL)
        . (isset($params['host']) ? $params['host'] : NULL)
        . (isset($params['port']) ? ':' . $params['port'] : NULL)
        . (isset($params['path']) ? $params['path'] : NULL)
        . (isset($params['query']) ? '?' . $params['query'] : NULL)
        . (isset($params['fragment']) ? '#' . $params['fragment'] : NULL);
    }

    /**
     * 根据count数目来输出字符
     * <code>
     * echo splitByCount(20, 10, 20, 30, 40, 50);
     * </code>
     *
     * @access public
     * @param int $count
     * @return string
     */
    public static function splitByCount($count)
    {
        $sizes = func_get_args();
        array_shift($sizes);

        foreach ($sizes as $size) {
            if ($count < $size) {
                return $size;
            }
        }

        return 0;
    }

    /**
     * 自闭合html修复函数
     * 使用方法:
     * <code>
     * $input = '这是一段被截断的html文本<a href="#"';
     * echo Typecho_Common::fixHtml($input);
     * //output: 这是一段被截断的html文本
     * </code>
     *
     * @access public
     * @param string $string 需要修复处理的字符串
     * @return string
     */
    public static function fixHtml($string)
    {
        //关闭自闭合标签
        $startPos = strrpos($string, "<");

        if (false == $startPos) {
            return $string;
        }

        $trimString = substr($string, $startPos);

        if (false === strpos($trimString, ">")) {
            $string = substr($string, 0, $startPos);
        }

        //非自闭合html标签列表
        preg_match_all("/<([_0-9a-zA-Z-\:]+)\s*([^>]*)>/is", $string, $startTags);
        preg_match_all("/<\/([_0-9a-zA-Z-\:]+)>/is", $string, $closeTags);

        if (!empty($startTags[1]) && is_array($startTags[1])) {
            krsort($startTags[1]);
            $closeTagsIsArray = is_array($closeTags[1]);
            foreach ($startTags[1] as $key => $tag) {
                $attrLength = strlen($startTags[2][$key]);
                if ($attrLength > 0 && "/" == trim($startTags[2][$key][$attrLength - 1])) {
                    continue;
                }

                // 白名单
                if (preg_match("/^(area|base|br|col|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/i", $tag)) {
                    continue;
                }

                if (!empty($closeTags[1]) && $closeTagsIsArray) {
                    if (false !== ($index = array_search($tag, $closeTags[1]))) {
                        unset($closeTags[1][$index]);
                        continue;
                    }
                }
                $string .= "</{$tag}>";
            }
        }

        return preg_replace("/\<br\s*\/\>\s*\<\/p\>/is", '</p>', $string);
    }

    /**
     * 去掉字符串中的html标签
     * 使用方法:
     * <code>
     * $input = '<a href="http://test/test.php" title="example">hello</a>';
     * $output = Typecho_Common::stripTags($input, <a href="">);
     * echo $output;
     * //display: '<a href="http://test/test.php">hello</a>'
     * </code>
     *
     * @access public
     * @param string $html 需要处理的字符串
     * @param string $allowableTags 需要忽略的html标签
     * @return string
     */
    public static function stripTags($html, $allowableTags = NULL)
    {
        $normalizeTags = '';
        $allowableAttributes = array();

        if (!empty($allowableTags) && preg_match_all("/\<([_a-z0-9-]+)([^>]*)\>/is", $allowableTags, $tags)) {
            $normalizeTags = '<' . implode('><', array_map('strtolower', $tags[1])) . '>';
            $attributes = array_map('trim', $tags[2]);
            foreach ($attributes as $key => $val) {
                $allowableAttributes[strtolower($tags[1][$key])] = 
                    array_map('strtolower', array_keys(self::__parseAttrs($val)));
            }
        }

        self::$_allowableAttributes = $allowableAttributes;
        $html = strip_tags($html, $normalizeTags);
        $html = preg_replace_callback("/<([_a-z0-9-]+)(\s+[^>]+)?>/is",
            array('Typecho_Common', '__filterAttrs'), $html);

        return $html;
    }

    /**
     * 过滤用于搜索的字符串
     *
     * @access public
     * @param string $query 搜索字符串
     * @return string
     */
    public static function filterSearchQuery($query)
    {
        return str_replace('-', ' ', self::slugName($query));
    }

    /**
     * 将url中的非法字符串
     *
     * @param string $url 需要过滤的url
     * @return string
     */
    public static function safeUrl($url)
    {
        //~ 针对location的xss过滤, 因为其特殊性无法使用removeXSS函数
        //~ fix issue 66
        $params = parse_url(str_replace(array("\r", "\n", "\t", ' '), '', $url));

        /** 禁止非法的协议跳转 */
        if (isset($params['scheme'])) {
            if (!in_array($params['scheme'], array('http', 'https'))) {
                return '/';
            }
        }

        /** 过滤解析串 */
        $params = array_map(array('Typecho_Common', '__removeUrlXss'), $params);
        return self::buildUrl($params);
    }

    /**
     * 处理XSS跨站攻击的过滤函数
     *
     * @author kallahar@kallahar.com
     * @link http://kallahar.com/smallprojects/php_xss_filter_function.php
     * @access public
     * @param string $val 需要处理的字符串
     * @return string
     */
    public static function removeXSS($val)
    {
       // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
       // this prevents some character re-spacing such as <java\0script>
       // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
       $val = preg_replace('/([\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19])/', '', $val);

       // straight replacements, the user should never need these since they're normal characters
       // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
       $search = 'abcdefghijklmnopqrstuvwxyz';
       $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
       $search .= '1234567890!@#$%^&*()';
       $search .= '~`";:?+/={}[]-_|\'\\';

       for ($i = 0; $i < strlen($search); $i++) {
          // ;? matches the ;, which is optional
          // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

          // &#x0040 @ search for the hex values
          $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
          // &#00064 @ 0{0,7} matches '0' zero to seven times
          $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
       }

       // now the only remaining whitespace attacks are \t, \n, and \r
       $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
       $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
       $ra = array_merge($ra1, $ra2);

       $found = true; // keep replacing as long as the previous round replaced something
       while ($found == true) {
          $val_before = $val;
          for ($i = 0; $i < sizeof($ra); $i++) {
             $pattern = '/';
             for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                   $pattern .= '(';
                   $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                   $pattern .= '|';
                   $pattern .= '|(&#0{0,8}([9|10|13]);)';
                   $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
             }
             $pattern .= '/i';
             $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
             $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags

             if ($val_before == $val) {
                // no replacements were made, so exit the loop
                $found = false;
             }
          }
       }

       return $val;
    }

    /**
     * 宽字符串截字函数
     *
     * @access public
     * @param string $str 需要截取的字符串
     * @param integer $start 开始截取的位置
     * @param integer $length 需要截取的长度
     * @param string $trim 截取后的截断标示符
     * @return string
     */
    public static function subStr($str, $start, $length, $trim = "...")
    {
        if (!strlen($str)) {
            return '';
        }

        $iLength = self::strLen($str) - $start;
        $tLength = $length < $iLength ? ($length - self::strLen($trim)) : $length;

        if (__TYPECHO_MB_SUPPORTED__) {
            $str = mb_substr($str, $start, $tLength, self::$charset);
        } else {
            if ('UTF-8' == strtoupper(self::$charset)) {
                if (preg_match_all("/./u", $str, $matches)) {
                    $str = implode('', array_slice($matches[0], $start, $tLength));
                }
            } else {
                $str = substr($str, $start, $tLength);
            }
        }
        
        return $length < $iLength ? ($str . $trim) : $str;
    }

    /**
     * 获取宽字符串长度函数
     *
     * @access public
     * @param string $str 需要获取长度的字符串
     * @return integer
     */
    public static function strLen($str)
    {
        if (__TYPECHO_MB_SUPPORTED__) {
            return mb_strlen($str, self::$charset);
        } else {
            return 'UTF-8' == strtoupper(self::$charset) 
                ? strlen(utf8_decode($str)) : strlen($str);
        }
    }

    /**
     * 获取大写字符串
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public static function strToUpper($str)
    {
        if (__TYPECHO_MB_SUPPORTED__) {
            return mb_strtoupper($str, self::$charset);
        } else {
            return 'UTF-8' == strtoupper(self::$charset)
                ? preg_replace_callback("/[a-z]+/u", array('Typecho_Common', '__strToUpper'), $str) : strtoupper($str);
        }
    }

    /**
     * 检查是否为合法的编码数据
     *
     * @param string|array $str
     * @return boolean
     */
    public static function checkStrEncoding($str)
    {
        if (is_array($str)) {
            return array_map(array('Typecho_Common', 'checkStrEncoding'), $str);
        }

        if (__TYPECHO_MB_SUPPORTED__) {
            return mb_check_encoding($str, self::$charset);
        } else {
            // just support utf-8
            return preg_match('//u', $str);
        }
    }

    /**
     * 生成缩略名
     *
     * @access public
     * @param string $str 需要生成缩略名的字符串
     * @param string $default 默认的缩略名
     * @param integer $maxLength 缩略名最大长度
     * @return string
     */
    public static function slugName($str, $default = NULL, $maxLength = 128)
    {
        $str = trim($str);

        if (!strlen($str)) {
            return $default;
        }
        
        if (__TYPECHO_MB_SUPPORTED__) {
            mb_regex_encoding(self::$charset);
            mb_ereg_search_init($str, "[\w" . preg_quote('_-') . "]+");
            $result = mb_ereg_search();
            $return = '';

            if ($result) {
                $regs = mb_ereg_search_getregs();
                $pos = 0;
                do {
                    $return .= ($pos > 0 ? '-' : '') . $regs[0];
                    $pos ++;
                } while ($regs = mb_ereg_search_regs());
            }

            $str = $return;
        } else if ('UTF-8' == strtoupper(self::$charset)) {
            if (preg_match_all("/[\w" . preg_quote('_-') . "]+/u", $str, $matches)) {
                $str = implode('-', $matches[0]);
            }
        } else {
            $str = str_replace(array("'", ":", "\\", "/", '"'), "", $str);
            $str = str_replace(array("+", ",", ' ', '，', ' ', ".", "?", "=", "&", "!", "<", ">", "(", ")", "[", "]", "{", "}"), "-", $str);
        }

        $str = trim($str, '-_');
        $str = !strlen($str) ? $default : $str;
        return substr($str, 0, $maxLength);
    }

    /**
     * 生成随机字符串
     *
     * @access public
     * @param integer $length 字符串长度
     * @param boolean $specialChars 是否有特殊字符
     * @return string
     */
    public static function randString($length, $specialChars = false)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($specialChars) {
            $chars .= '!@#$%^&*()';
        }

        $result = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, $max)];
        }
        return $result;
    }

    /**
     * 对字符串进行hash加密
     *
     * @access public
     * @param string $string 需要hash的字符串
     * @param string $salt 扰码
     * @return string
     */
    public static function hash($string, $salt = NULL)
    {
        /** 生成随机字符串 */
        $salt = empty($salt) ? self::randString(9) : $salt;
        $length = strlen($string);
        $hash = '';
        $last = ord($string[$length - 1]);
        $pos = 0;

        /** 判断扰码长度 */
        if (strlen($salt) != 9) {
            /** 如果不是9直接返回 */
            return;
        }

        while ($pos < $length) {
            $asc = ord($string[$pos]);
            $last = ($last * ord($salt[($last % $asc) % 9]) + $asc) % 95 + 32;
            $hash .= chr($last);
            $pos ++;
        }

        return '$T$' . $salt . md5($hash);
    }

    /**
     * 判断hash值是否相等
     *
     * @access public
     * @param string $from 源字符串
     * @param string $to 目标字符串
     * @return boolean
     */
    public static function hashValidate($from, $to)
    {
        if ('$T$' == substr($to, 0, 3)) {
            $salt = substr($to, 3, 9);
            return self::hash($from, $salt) === $to;
        } else {
            return md5($from) === $to;
        }
    }

    /**
     * 创建一个会过期的Token
     *
     * @param $secret
     * @return string
     */
    public static function timeToken($secret)
    {
        return sha1($secret . '&' . time());
    }

    /**
     * 在时间范围内验证token
     *
     * @param $token
     * @param $secret
     * @param int $timeout
     * @return bool
     */
    public static function timeTokenValidate($token, $secret, $timeout = 5)
    {
        $now = time();
        $from = $now - $timeout;

        for ($i = $now; $i >= $from; $i --) {
            if (sha1($secret . '&' . $i) == $token) {
                return true;
            }
        }

        return false;
    }

    /**
     * 将路径转化为链接
     *
     * @access public
     * @param string $path 路径
     * @param string $prefix 前缀
     * @return string
     */
    public static function url($path, $prefix)
    {
        $path = (0 === strpos($path, './')) ? substr($path, 2) : $path;
        return rtrim($prefix, '/') . '/' . str_replace('//', '/', ltrim($path, '/'));
    }

    /**
     * 获取gravatar头像地址 
     * 
     * @param string $mail 
     * @param int $size 
     * @param string $rating 
     * @param string $default 
     * @param bool $isSecure 
     * @return string
     */
    public static function gravatarUrl($mail, $size, $rating, $default, $isSecure = false)
    {
        if (defined('__TYPECHO_GRAVATAR_PREFIX__')) {
            $url = __TYPECHO_GRAVATAR_PREFIX__;
        } else {
            $url = $isSecure ? 'https://secure.gravatar.com' : 'http://www.gravatar.com';
            $url .= '/avatar/';
        }

        if (!empty($mail)) {
            $url .= md5(strtolower(trim($mail)));
        }

        $url .= '?s=' . $size;
        $url .= '&amp;r=' . $rating;
        $url .= '&amp;d=' . $default;

        return $url;
    }

    /**
     * 给javascript赋值加入扰码设计 
     * 
     * @param string $value 
     * @return string
     */
    public static function shuffleScriptVar($value)
    {
        $length = strlen($value);
        $max = 3;
        $offset = 0;
        $result = array();
        $cut = array();

        while ($length > 0) {
            $len = rand(0, min($max, $length));
            $rand = "'" . self::randString(rand(1, $max)) . "'";

            if ($len > 0) {
                $val = "'" . substr($value, $offset, $len) . "'";
                $result[] = rand(0, 1) ? "//{$rand}\n{$val}" : "{$val}//{$rand}\n";
            } else {
                if (rand(0, 1)) {
                    $result[] = rand(0, 1) ? "''///*{$rand}*/{$rand}\n" : "/* {$rand}//{$rand} */''";
                } else {
                    $result[] = rand(0, 1) ? "//{$rand}\n{$rand}" : "{$rand}//{$rand}\n";
                    $cut[] = array($offset, strlen($rand) - 2 + $offset);
                }
            }

            $offset += $len;
            $length -= $len;
        }

        $name = '_' . self::randString(rand(3, 7));
        $cutName = '_' . self::randString(rand(3, 7));
        $var = implode('+', $result);
        $cutVar = Json::encode($cut);
        return "(function () {
    var {$name} = {$var}, {$cutName} = {$cutVar};
    
    for (var i = 0; i < {$cutName}.length; i ++) {
        {$name} = {$name}.substring(0, {$cutName}[i][0]) + {$name}.substring({$cutName}[i][1]);
    }

    return {$name};
})();";
    }

    /**
     * 过滤字段名
     *
     * @access private
     * @param mixed $result
     * @return array
     */
    public static function filterSQLite2ColumnName($result)
    {
        /** 如果结果为空,直接返回 */
        if (empty($result)) {
            return $result;
        }

        $tResult = array();

        /** 遍历数组 */
        foreach ($result as $key => $val) {
            /** 按点分隔 */
            if (false !== ($pos = strpos($key, '.'))) {
                $key = substr($key, $pos + 1);
            }

            $tResult[trim($key, '"')] = $val;
        }

        return $tResult;
    }

    /**
     * 处理sqlite2的distinct count
     *
     * @param $sql
     * @return string
     */
    public static function filterSQLite2CountQuery($sql)
    {
        if (preg_match("/SELECT\s+COUNT\(DISTINCT\s+([^\)]+)\)\s+(AS\s+[^\s]+)?\s*FROM\s+(.+)/is", $sql, $matches)) {
            return 'SELECT COUNT(' . $matches[1] . ') ' . $matches[2] . ' FROM SELECT DISTINCT '
                . $matches[1] . ' FROM ' . $matches[3];
        }

        return $sql;
    }

    /**
     * 创建备份文件缓冲
     *
     * @param $type
     * @param $header
     * @param $body
     * @return string
     */
    public static function buildBackupBuffer($type, $header, $body)
    {
        $buffer = '';

        $buffer .= pack('vvV', $type, strlen($header), strlen($body));
        $buffer .= $header . $body;
        $buffer .= md5($buffer);

        return $buffer;
    }

    /**
     * 从备份文件中解压
     *
     * @param $fp
     * @param bool $offset
     * @param string $version
     * @return array|bool
     */
    public static function extractBackupBuffer($fp, &$offset, $version)
    {
        $realMetaLen = $version == 'FILE' ? 6 : 8;

        $meta = fread($fp, $realMetaLen);
        $offset += $realMetaLen;
        $metaLen = strlen($meta);

        if (false === $meta || $metaLen != $realMetaLen) {
            return false;
        }

        list ($type, $headerLen, $bodyLen) = array_values(unpack($version == 'FILE' ? 'v3' : 'v1type/v1headerLen/V1bodyLen', $meta));

        $header = @fread($fp, $headerLen);
        $offset += $headerLen;

        if (false === $header || strlen($header) != $headerLen) {
            return false;
        }

        if ('FILE' == $version) {
            $bodyLen = array_reduce(json_decode($header, true), function ($carry, $len) {
                return NULL === $len ? $carry : $carry + $len;
            }, 0);
        }

        $body = @fread($fp, $bodyLen);
        $offset += $bodyLen;

        if (false === $body || strlen($body) != $bodyLen) {
            return false;
        }

        $md5 = @fread($fp, 32);
        $offset += 32;

        if (false === $md5 || $md5 != md5($meta . $header . $body)) {
            return false;
        }

        return array($type, $header, $body);
    }

    /**
     * 检查是否是一个安全的主机名
     *
     * @param $host
     * @return bool
     */
    public static function checkSafeHost($host)
    {
        if ('localhost' == $host) {
            return false;
        }

        $address = gethostbyname($host);
        $inet = inet_pton($address);

        if (false === $inet) {
            // 有可能是ipv6的地址
            $records = dns_get_record($host, DNS_AAAA);

            if (empty($records)) {
                return false;
            }

            $address = $records[0]['ipv6'];
            $inet = inet_pton($address);
        }

        if (strpos($address, '.')) {
            // ipv4
            // 非公网地址
            $privateNetworks = array(
                '10.0.0.0|10.255.255.255',
                '172.16.0.0|172.31.255.255',
                '192.168.0.0|192.168.255.255',
                '169.254.0.0|169.254.255.255',
                '127.0.0.0|127.255.255.255'
            );

            $long = ip2long($address);

            foreach ($privateNetworks as $network) {
                list ($from, $to) = explode('|', $network);

                if ($long >= ip2long($from) && $long <= ip2long($to)) {
                    return false;
                }
            }
        } else {
            // ipv6
            // https://en.wikipedia.org/wiki/Private_network
            $from = inet_pton('fd00::');
            $to = inet_pton('fdff:ffff:ffff:ffff:ffff:ffff:ffff:ffff');

            if ($inet >= $from && $inet <= $to) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取图片
     *
     * @access public
     * @param string $fileName 文件名
     * @return string
     */
    public static function mimeContentType($fileName)
    {
        //改为并列判断
        if (function_exists('mime_content_type')) {
            return mime_content_type($fileName);
        }

        if (function_exists('finfo_open')) {
            $fInfo = @finfo_open(FILEINFO_MIME_TYPE);

            if (false !== $fInfo) {
                $mimeType = finfo_file($fInfo, $fileName);
                finfo_close($fInfo);
                return $mimeType;
            }
        }

        $mimeTypes = array(
          'ez' => 'application/andrew-inset',
          'csm' => 'application/cu-seeme',
          'cu' => 'application/cu-seeme',
          'tsp' => 'application/dsptype',
          'spl' => 'application/x-futuresplash',
          'hta' => 'application/hta',
          'cpt' => 'image/x-corelphotopaint',
          'hqx' => 'application/mac-binhex40',
          'nb' => 'application/mathematica',
          'mdb' => 'application/msaccess',
          'doc' => 'application/msword',
          'dot' => 'application/msword',
          'bin' => 'application/octet-stream',
          'oda' => 'application/oda',
          'ogg' => 'application/ogg',
          'prf' => 'application/pics-rules',
          'key' => 'application/pgp-keys',
          'pdf' => 'application/pdf',
          'pgp' => 'application/pgp-signature',
          'ps' => 'application/postscript',
          'ai' => 'application/postscript',
          'eps' => 'application/postscript',
          'rss' => 'application/rss+xml',
          'rtf' => 'text/rtf',
          'smi' => 'application/smil',
          'smil' => 'application/smil',
          'wp5' => 'application/wordperfect5.1',
          'xht' => 'application/xhtml+xml',
          'xhtml' => 'application/xhtml+xml',
          'zip' => 'application/zip',
          'cdy' => 'application/vnd.cinderella',
          'mif' => 'application/x-mif',
          'xls' => 'application/vnd.ms-excel',
          'xlb' => 'application/vnd.ms-excel',
          'cat' => 'application/vnd.ms-pki.seccat',
          'stl' => 'application/vnd.ms-pki.stl',
          'ppt' => 'application/vnd.ms-powerpoint',
          'pps' => 'application/vnd.ms-powerpoint',
          'pot' => 'application/vnd.ms-powerpoint',
          'sdc' => 'application/vnd.stardivision.calc',
          'sda' => 'application/vnd.stardivision.draw',
          'sdd' => 'application/vnd.stardivision.impress',
          'sdp' => 'application/vnd.stardivision.impress',
          'smf' => 'application/vnd.stardivision.math',
          'sdw' => 'application/vnd.stardivision.writer',
          'vor' => 'application/vnd.stardivision.writer',
          'sgl' => 'application/vnd.stardivision.writer-global',
          'sxc' => 'application/vnd.sun.xml.calc',
          'stc' => 'application/vnd.sun.xml.calc.template',
          'sxd' => 'application/vnd.sun.xml.draw',
          'std' => 'application/vnd.sun.xml.draw.template',
          'sxi' => 'application/vnd.sun.xml.impress',
          'sti' => 'application/vnd.sun.xml.impress.template',
          'sxm' => 'application/vnd.sun.xml.math',
          'sxw' => 'application/vnd.sun.xml.writer',
          'sxg' => 'application/vnd.sun.xml.writer.global',
          'stw' => 'application/vnd.sun.xml.writer.template',
          'sis' => 'application/vnd.symbian.install',
          'wbxml' => 'application/vnd.wap.wbxml',
          'wmlc' => 'application/vnd.wap.wmlc',
          'wmlsc' => 'application/vnd.wap.wmlscriptc',
          'wk' => 'application/x-123',
          'dmg' => 'application/x-apple-diskimage',
          'bcpio' => 'application/x-bcpio',
          'torrent' => 'application/x-bittorrent',
          'cdf' => 'application/x-cdf',
          'vcd' => 'application/x-cdlink',
          'pgn' => 'application/x-chess-pgn',
          'cpio' => 'application/x-cpio',
          'csh' => 'text/x-csh',
          'deb' => 'application/x-debian-package',
          'dcr' => 'application/x-director',
          'dir' => 'application/x-director',
          'dxr' => 'application/x-director',
          'wad' => 'application/x-doom',
          'dms' => 'application/x-dms',
          'dvi' => 'application/x-dvi',
          'pfa' => 'application/x-font',
          'pfb' => 'application/x-font',
          'gsf' => 'application/x-font',
          'pcf' => 'application/x-font',
          'pcf.Z' => 'application/x-font',
          'gnumeric' => 'application/x-gnumeric',
          'sgf' => 'application/x-go-sgf',
          'gcf' => 'application/x-graphing-calculator',
          'gtar' => 'application/x-gtar',
          'tgz' => 'application/x-gtar',
          'taz' => 'application/x-gtar',
          'gz'  => 'application/x-gtar',
          'hdf' => 'application/x-hdf',
          'phtml' => 'application/x-httpd-php',
          'pht' => 'application/x-httpd-php',
          'php' => 'application/x-httpd-php',
          'phps' => 'application/x-httpd-php-source',
          'php3' => 'application/x-httpd-php3',
          'php3p' => 'application/x-httpd-php3-preprocessed',
          'php4' => 'application/x-httpd-php4',
          'ica' => 'application/x-ica',
          'ins' => 'application/x-internet-signup',
          'isp' => 'application/x-internet-signup',
          'iii' => 'application/x-iphone',
          'jar' => 'application/x-java-archive',
          'jnlp' => 'application/x-java-jnlp-file',
          'ser' => 'application/x-java-serialized-object',
          'class' => 'application/x-java-vm',
          'js' => 'application/x-javascript',
          'chrt' => 'application/x-kchart',
          'kil' => 'application/x-killustrator',
          'kpr' => 'application/x-kpresenter',
          'kpt' => 'application/x-kpresenter',
          'skp' => 'application/x-koan',
          'skd' => 'application/x-koan',
          'skt' => 'application/x-koan',
          'skm' => 'application/x-koan',
          'ksp' => 'application/x-kspread',
          'kwd' => 'application/x-kword',
          'kwt' => 'application/x-kword',
          'latex' => 'application/x-latex',
          'lha' => 'application/x-lha',
          'lzh' => 'application/x-lzh',
          'lzx' => 'application/x-lzx',
          'frm' => 'application/x-maker',
          'maker' => 'application/x-maker',
          'frame' => 'application/x-maker',
          'fm' => 'application/x-maker',
          'fb' => 'application/x-maker',
          'book' => 'application/x-maker',
          'fbdoc' => 'application/x-maker',
          'wmz' => 'application/x-ms-wmz',
          'wmd' => 'application/x-ms-wmd',
          'com' => 'application/x-msdos-program',
          'exe' => 'application/x-msdos-program',
          'bat' => 'application/x-msdos-program',
          'dll' => 'application/x-msdos-program',
          'msi' => 'application/x-msi',
          'nc' => 'application/x-netcdf',
          'pac' => 'application/x-ns-proxy-autoconfig',
          'nwc' => 'application/x-nwc',
          'o' => 'application/x-object',
          'oza' => 'application/x-oz-application',
          'pl' => 'application/x-perl',
          'pm' => 'application/x-perl',
          'p7r' => 'application/x-pkcs7-certreqresp',
          'crl' => 'application/x-pkcs7-crl',
          'qtl' => 'application/x-quicktimeplayer',
          'rpm' => 'audio/x-pn-realaudio-plugin',
          'shar' => 'application/x-shar',
          'swf' => 'application/x-shockwave-flash',
          'swfl' => 'application/x-shockwave-flash',
          'sh' => 'text/x-sh',
          'sit' => 'application/x-stuffit',
          'sv4cpio' => 'application/x-sv4cpio',
          'sv4crc' => 'application/x-sv4crc',
          'tar' => 'application/x-tar',
          'tcl' => 'text/x-tcl',
          'tex' => 'text/x-tex',
          'gf' => 'application/x-tex-gf',
          'pk' => 'application/x-tex-pk',
          'texinfo' => 'application/x-texinfo',
          'texi' => 'application/x-texinfo',
          '~' => 'application/x-trash',
          '%' => 'application/x-trash',
          'bak' => 'application/x-trash',
          'old' => 'application/x-trash',
          'sik' => 'application/x-trash',
          't' => 'application/x-troff',
          'tr' => 'application/x-troff',
          'roff' => 'application/x-troff',
          'man' => 'application/x-troff-man',
          'me' => 'application/x-troff-me',
          'ms' => 'application/x-troff-ms',
          'ustar' => 'application/x-ustar',
          'src' => 'application/x-wais-source',
          'wz' => 'application/x-wingz',
          'crt' => 'application/x-x509-ca-cert',
          'fig' => 'application/x-xfig',
          'au' => 'audio/basic',
          'snd' => 'audio/basic',
          'mid' => 'audio/midi',
          'midi' => 'audio/midi',
          'kar' => 'audio/midi',
          'mpga' => 'audio/mpeg',
          'mpega' => 'audio/mpeg',
          'mp2' => 'audio/mpeg',
          'mp3' => 'audio/mpeg',
          'm3u' => 'audio/x-mpegurl',
          'sid' => 'audio/prs.sid',
          'aif' => 'audio/x-aiff',
          'aiff' => 'audio/x-aiff',
          'aifc' => 'audio/x-aiff',
          'gsm' => 'audio/x-gsm',
          'wma' => 'audio/x-ms-wma',
          'wax' => 'audio/x-ms-wax',
          'ra' => 'audio/x-realaudio',
          'rm' => 'audio/x-pn-realaudio',
          'ram' => 'audio/x-pn-realaudio',
          'pls' => 'audio/x-scpls',
          'sd2' => 'audio/x-sd2',
          'wav' => 'audio/x-wav',
          'pdb' => 'chemical/x-pdb',
          'xyz' => 'chemical/x-xyz',
          'bmp' => 'image/x-ms-bmp',
          'gif' => 'image/gif',
          'ief' => 'image/ief',
          'jpeg' => 'image/jpeg',
          'jpg' => 'image/jpeg',
          'jpe' => 'image/jpeg',
          'pcx' => 'image/pcx',
          'png' => 'image/png',
          'svg' => 'image/svg+xml',
          'svgz' => 'image/svg+xml',
          'tiff' => 'image/tiff',
          'tif' => 'image/tiff',
          'wbmp' => 'image/vnd.wap.wbmp',
          'ras' => 'image/x-cmu-raster',
          'cdr' => 'image/x-coreldraw',
          'pat' => 'image/x-coreldrawpattern',
          'cdt' => 'image/x-coreldrawtemplate',
          'djvu' => 'image/x-djvu',
          'djv' => 'image/x-djvu',
          'ico' => 'image/x-icon',
          'art' => 'image/x-jg',
          'jng' => 'image/x-jng',
          'psd' => 'image/x-photoshop',
          'pnm' => 'image/x-portable-anymap',
          'pbm' => 'image/x-portable-bitmap',
          'pgm' => 'image/x-portable-graymap',
          'ppm' => 'image/x-portable-pixmap',
          'rgb' => 'image/x-rgb',
          'xbm' => 'image/x-xbitmap',
          'xpm' => 'image/x-xpixmap',
          'xwd' => 'image/x-xwindowdump',
          'igs' => 'model/iges',
          'iges' => 'model/iges',
          'msh' => 'model/mesh',
          'mesh' => 'model/mesh',
          'silo' => 'model/mesh',
          'wrl' => 'x-world/x-vrml',
          'vrml' => 'x-world/x-vrml',
          'csv' => 'text/comma-separated-values',
          'css' => 'text/css',
          '323' => 'text/h323',
          'htm' => 'text/html',
          'html' => 'text/html',
          'uls' => 'text/iuls',
          'mml' => 'text/mathml',
          'asc' => 'text/plain',
          'txt' => 'text/plain',
          'text' => 'text/plain',
          'diff' => 'text/plain',
          'rtx' => 'text/richtext',
          'sct' => 'text/scriptlet',
          'wsc' => 'text/scriptlet',
          'tm' => 'text/texmacs',
          'ts' => 'text/texmacs',
          'tsv' => 'text/tab-separated-values',
          'jad' => 'text/vnd.sun.j2me.app-descriptor',
          'wml' => 'text/vnd.wap.wml',
          'wmls' => 'text/vnd.wap.wmlscript',
          'xml' => 'text/xml',
          'xsl' => 'text/xml',
          'h++' => 'text/x-c++hdr',
          'hpp' => 'text/x-c++hdr',
          'hxx' => 'text/x-c++hdr',
          'hh' => 'text/x-c++hdr',
          'c++' => 'text/x-c++src',
          'cpp' => 'text/x-c++src',
          'cxx' => 'text/x-c++src',
          'cc' => 'text/x-c++src',
          'h' => 'text/x-chdr',
          'c' => 'text/x-csrc',
          'java' => 'text/x-java',
          'moc' => 'text/x-moc',
          'p' => 'text/x-pascal',
          'pas' => 'text/x-pascal',
          '***' => 'text/x-pcs-***',
          'shtml' => 'text/x-server-parsed-html',
          'etx' => 'text/x-setext',
          'tk' => 'text/x-tcl',
          'ltx' => 'text/x-tex',
          'sty' => 'text/x-tex',
          'cls' => 'text/x-tex',
          'vcs' => 'text/x-vcalendar',
          'vcf' => 'text/x-vcard',
          'dl' => 'video/dl',
          'fli' => 'video/fli',
          'gl' => 'video/gl',
          'mpeg' => 'video/mpeg',
          'mpg' => 'video/mpeg',
          'mpe' => 'video/mpeg',
          'qt' => 'video/quicktime',
          'mov' => 'video/quicktime',
          'mxu' => 'video/vnd.mpegurl',
          'dif' => 'video/x-dv',
          'dv' => 'video/x-dv',
          'lsf' => 'video/x-la-asf',
          'lsx' => 'video/x-la-asf',
          'mng' => 'video/x-mng',
          'asf' => 'video/x-ms-asf',
          'asx' => 'video/x-ms-asf',
          'wm' => 'video/x-ms-wm',
          'wmv' => 'video/x-ms-wmv',
          'wmx' => 'video/x-ms-wmx',
          'wvx' => 'video/x-ms-wvx',
          'avi' => 'video/x-msvideo',
          'movie' => 'video/x-sgi-movie',
          'ice' => 'x-conference/x-cooltalk',
          'vrm' => 'x-world/x-vrml',
          'rar' => 'application/x-rar-compressed',
          'cab' => 'application/vnd.ms-cab-compressed'
        );

        $part = explode('.', $fileName);
        $size = count($part);

        if ($size > 1) {
            $ext = $part[$size - 1];
            if (isset($mimeTypes[$ext])) {
                return $mimeTypes[$ext];
            }
        }

        return 'application/octet-stream';
    }

    /**
     * 寻找匹配的mime图标
     *
     * @access public
     * @param string $mime mime类型
     * @return string
     */
    public static function mimeIconType($mime)
    {
        $parts = explode('/', $mime);

        if (count($parts) < 2) {
            return 'unknown';
        }

        list ($type, $stream) = $parts;

        if (in_array($type, array('image', 'video', 'audio', 'text', 'application'))) {
            switch (true) {
                case in_array($stream, array('msword', 'msaccess', 'ms-powerpoint', 'ms-powerpoint')):
                case 0 === strpos($stream, 'vnd.'):
                    return 'office';
                case false !== strpos($stream, 'html') || false !== strpos($stream, 'xml') || false !== strpos($stream, 'wml'):
                    return 'html';
                case false !== strpos($stream, 'compressed') || false !== strpos($stream, 'zip') ||
                in_array($stream, array('application/x-gtar', 'application/x-tar')):
                    return 'archive';
                case 'text' == $type && 0 === strpos($stream, 'x-'):
                    return 'script';
                default:
                    return $type;
            }
        } else {
            return 'unknown';
        }
    }
}
