<?php
/*
   IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
   Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
   Site:   http://scripts.incutio.com/xmlrpc/
   Manual: http://scripts.incutio.com/xmlrpc/manual.php
   Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php
*/

/**
 * IXR服务器
 *
 * @package IXR
 */
class IXR_Server
{
    /**
     * 输入参数
     *
     * @access private
     * @var array
     */
    private $data;

    /**
     * 回调函数
     *
     * @access private
     * @var array
     */
    private $callbacks = array();

    /**
     * 消息体
     *
     * @access private
     * @var IXR_Message
     */
    private $message;

    /**
     * 默认参数
     *
     * @access private
     * @var array
     */
    private $capabilities;

    /**
     * 构造函数
     *
     * @access public
     * @param mixed $callbacks 回调函数
     * @param mixed $data 输入参数
     * @return void
     */
    public function __construct($callbacks = false, $data = false)
    {
        $this->setCapabilities();
        if ($callbacks) {
            $this->callbacks = $callbacks;
        }
        $this->setCallbacks();
        $this->serve($data);
    }

    /**
     * 呼叫内部方法
     *
     * @access private
     * @param string $methodname 方法名
     * @param mixed $args 参数
     * @return mixed
     */
    private function call($methodname, $args)
    {
        // hook
        if (0 !== strpos($methodname, 'hook.') && $this->hasMethod('hook.beforeCall')) {
            $this->call('hook.beforeCall', array($methodname));
        }
        
        if (!$this->hasMethod($methodname)) {
            return new IXR_Error(-32601, 'server error. requested method '.$methodname.' does not exist.');
        }
        $method = $this->callbacks[$methodname];

        // Are we dealing with a function or a method?
        if (is_string($method) && substr($method, 0, 5) == 'this:') {
            // It's a class method - check it exists
            $method = substr($method, 5);
            if (!method_exists($this, $method)) {
                return new IXR_Error(-32601, 'server error. requested class method "'.$method.'" does not exist.');
            }
            // Call the method
            $result = $this->$method($args);
        } else {
            if (is_array($method)) {
                list($object, $func) = $method;
                if (!is_callable($method)) {
                    return new IXR_Error(-32601, 'server error. requested class method "'.$object . '.' . $func.'" does not exist.');
                }
                
                $result = call_user_func_array(array($object, $func), $args);
            } elseif (!function_exists($method)) {
                // It's a function - does it exist?
                return new IXR_Error(-32601, 'server error. requested function "'.$method.'" does not exist.');
            } else {
                // Call the function
                $result = $method($args);
            }
        }
        
        // hook
        if (0 !== strpos($methodname, 'hook.') && $this->hasMethod('hook.afterCall')) {
            $this->call('hook.afterCall', array($methodname));
        }
        
        return $result;
    }

    /**
     * 抛出错误
     *
     * @access private
     * @param integer $error 错误代码
     * @param string $message 错误消息
     * @return void
     */
    private function error($error, $message = false)
    {
        // Accepts either an error object or an error code and message
        if ($message && !is_object($error)) {
            $error = new IXR_Error($error, $message);
        }
        $this->output($error->getXml());
    }

    /**
     * 输出xml
     *
     * @access private
     * @param string $xml 输出xml
     * @return 输出xml
     */
    private function output($xml)
    {
        $xml = '<?xml version="1.0"?>'."\n".$xml;
        $length = strlen($xml);
        header('Connection: close');
        header('Content-Length: '.$length);
        header('Content-Type: text/xml');
        header('Date: '.date('r'));
        echo $xml;
        exit;
    }

    /**
     * 是否存在方法
     *
     * @access private
     * @param string $method 方法名
     * @return mixed
     */
    private function hasMethod($method)
    {
        return in_array($method, array_keys($this->callbacks));
    }

    /**
     * 设置默认参数
     *
     * @access public
     * @return void
     */
    private function setCapabilities()
    {
        // Initialises capabilities array
        $this->capabilities = array(
            'xmlrpc' => array(
                'specUrl' => 'http://www.xmlrpc.com/spec',
                'specVersion' => 1
            ),
            'faults_interop' => array(
                'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
                'specVersion' => 20010516
            ),
            'system.multicall' => array(
                'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
                'specVersion' => 1
            ),
        );
    }

    /**
     * 设置默认方法
     *
     * @access private
     * @return void
     */
    private function setCallbacks()
    {
        $this->callbacks['system.getCapabilities'] = 'this:getCapabilities';
        $this->callbacks['system.listMethods'] = 'this:listMethods';
        $this->callbacks['system.multicall'] = 'this:multiCall';
    }

    /**
     * 服务入口
     *
     * @access private
     * @param mixed $data 输入参数
     * @return void
     */
    private function serve($data = false)
    {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");
        }
        if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $GLOBALS['HTTP_RAW_POST_DATA'] = trim($GLOBALS['HTTP_RAW_POST_DATA']);
        }

        if (!$data) {
            global $HTTP_RAW_POST_DATA;
            if (!$HTTP_RAW_POST_DATA) {
               die('XML-RPC server accepts POST requests only.');
            }
            $data = $HTTP_RAW_POST_DATA;
        }
        $this->message = new IXR_Message($data);
        if (!$this->message->parse()) {
            $this->error(-32700, 'parse error. not well formed');
        }
        if ($this->message->messageType != 'methodCall') {
            $this->error(-32600, 'server error. invalid xml-rpc. not conforming to spec. Request must be a methodCall');
        }
        
        if (0 === strpos($this->message->methodName, 'hook.')) {
            die('THIS METHOD MUST BE CALLED INSIDE.');
        }
        
        $result = $this->call($this->message->methodName, $this->message->params);
        // Is the result an error?
        if (is_a($result, 'IXR_Error')) {
            $this->error($result);
        }
        // Encode the result
        $r = new IXR_Value($result);
        $resultxml = $r->getXml();
        // Create the XML
        $xml = <<<EOD
<methodResponse>
  <params>
    <param>
      <value>
        $resultxml
      </value>
    </param>
  </params>
</methodResponse>

EOD;
        // hook
        if ($this->hasMethod('hook.beforeOutput')) {
            $this->call('hook.beforeOutput', array());
        }
        
        // Send it
        $this->output($xml);
    }

    /**
     * 获取默认参数
     *
     * @access public
     * @param mixed $args 输入参数
     * @return array
     */
    public function getCapabilities($args)
    {
        return $this->capabilities;
    }

    /**
     * 列出所有方法
     *
     * @access public
     * @param mixed $args 输入参数
     * @return mixed
     */
    public function listMethods($args)
    {
        // Returns a list of methods - uses array_reverse to ensure user defined
        // methods are listed before server defined methods
        return array_reverse(array_keys($this->callbacks));
    }

    /**
     * 一次处理多个请求
     *
     * @access public
     * @param void $methodcalls
     * @return array
     */
    public function multiCall($methodcalls)
    {
        // See http://www.xmlrpc.com/discuss/msgReader$1208
        $return = array();
        foreach ($methodcalls as $call) {
            $method = $call['methodName'];
            $params = $call['params'];
            if ($method == 'system.multicall') {
                $result = new IXR_Error(-32600, 'Recursive calls to system.multicall are forbidden');
            } else {
                $result = $this->call($method, $params);
            }
            if (is_a($result, 'IXR_Error')) {
                $return[] = array(
                    'faultCode' => $result->code,
                    'faultString' => $result->message
                );
            } else {
                $return[] = array($result);
            }
        }
        return $return;
    }
}
