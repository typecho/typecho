<?php
/*
   IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
   Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
   Site:   http://scripts.incutio.com/xmlrpc/
   Manual: http://scripts.incutio.com/xmlrpc/manual.php
   Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php
*/

/**
 * IXR错误
 *
 * @package IXR
 */
class IXR_Error
{
    /**
     * 错误代码
     *
     * @access public
     * @var integer
     */
    public $code;

    /**
     * 错误消息
     *
     * @access public
     * @var string
     */
    public $message;

    /**
     * 构造函数
     *
     * @access public
     * @param integer $code 错误代码
     * @param string $message 错误消息
     * @return void
     */
    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * 获取xml
     *
     * @access public
     * @return string
     */
    public function getXml()
    {
        $xml = <<<EOD
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>{$this->code}</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>{$this->message}</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>

EOD;
        return $xml;
    }
}
