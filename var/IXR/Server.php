<?php

namespace IXR;

/**
 * IXR服务器
 *
 * @package IXR
 */
class Server
{
    /**
     * 回调函数
     *
     * @var array
     */
    private $callbacks;

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
     * @param array $callbacks 回调函数
     */
    public function __construct(array $callbacks = [])
    {
        $this->setCapabilities();
        $this->callbacks = $callbacks;
        $this->setCallbacks();
        $this->serve();
    }

    /**
     * 获取默认参数
     *
     * @access public
     * @return array
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * 列出所有方法
     *
     * @access public
     * @return array
     */
    public function listMethods(): array
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
        $return = [];
        foreach ($methodcalls as $call) {
            $method = $call['methodName'];
            $params = $call['params'];
            if ($method == 'system.multicall') {
                $result = new Error(-32600, 'Recursive calls to system.multicall are forbidden');
            } else {
                $result = $this->call($method, $params);
            }
            if (is_a($result, 'Error')) {
                $return[] = [
                    'faultCode'   => $result->code,
                    'faultString' => $result->message
                ];
            } else {
                $return[] = [$result];
            }
        }
        return $return;
    }

    /**
     * 呼叫内部方法
     *
     * @param string $methodName 方法名
     * @param array $args 参数
     * @return mixed
     */
    private function call(string $methodName, array $args)
    {
        if (!$this->hasMethod($methodName)) {
            return new Error(-32601, 'server error. requested method ' . $methodName . ' does not exist.');
        }
        $method = $this->callbacks[$methodName];

        if (!is_callable($method)) {
            return new Error(
                -32601,
                'server error. requested class method "' . $methodName . '" does not exist.'
            );
        }

        return call_user_func_array($method, $args);
    }

    /**
     * 抛出错误
     *
     * @access private
     * @param integer|Error $error 错误代码
     * @param string|null $message 错误消息
     * @return void
     */
    private function error($error, ?string $message = null)
    {
        // Accepts either an error object or an error code and message
        if (!$error instanceof Error) {
            $error = new Error($error, $message);
        }

        $this->output($error->getXml());
    }

    /**
     * 输出xml
     *
     * @access private
     * @param string $xml 输出xml
     */
    private function output(string $xml)
    {
        $xml = '<?xml version="1.0"?>' . "\n" . $xml;
        $length = strlen($xml);
        header('Connection: close');
        header('Content-Length: ' . $length);
        header('Content-Type: text/xml');
        header('Date: ' . date('r'));
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
    private function hasMethod(string $method)
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
        $this->capabilities = [
            'xmlrpc'           => [
                'specUrl'     => 'http://www.xmlrpc.com/spec',
                'specVersion' => 1
            ],
            'faults_interop'   => [
                'specUrl'     => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
                'specVersion' => 20010516
            ],
            'system.multicall' => [
                'specUrl'     => 'http://www.xmlrpc.com/discuss/msgReader$1208',
                'specVersion' => 1
            ],
        ];
    }

    /**
     * 设置默认方法
     *
     * @access private
     * @return void
     */
    private function setCallbacks()
    {
        $this->callbacks['system.getCapabilities'] = [$this, 'getCapabilities'];
        $this->callbacks['system.listMethods'] = [$this, 'listMethods'];
        $this->callbacks['system.multicall'] = [$this, 'multiCall'];
    }

    /**
     * 服务入口
     */
    private function serve()
    {
        $message = new Message(file_get_contents('php://input') ?: '');

        if (!$message->parse()) {
            $this->error(-32700, 'parse error. not well formed');
        } elseif ($message->messageType != 'methodCall') {
            $this->error(-32600, 'server error. invalid xml-rpc. not conforming to spec. Request must be a methodCall');
        }

        $result = $this->call($message->methodName, $message->params);
        // Is the result an error?
        if ($result instanceof Error) {
            $this->error($result);
        }

        // Encode the result
        $r = new Value($result);
        $resultXml = $r->getXml();

        // Create the XML
        $xml = <<<EOD
<methodResponse>
  <params>
    <param>
      <value>
        $resultXml
      </value>
    </param>
  </params>
</methodResponse>

EOD;

        // Send it
        $this->output($xml);
    }
}
