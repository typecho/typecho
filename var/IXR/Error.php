<?php

namespace IXR;

/**
 * IXR错误
 *
 * @package IXR
 */
class Error
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
     * @var string|null
     */
    public $message;

    /**
     * 构造函数
     *
     * @param integer $code 错误代码
     * @param string|null $message 错误消息
     */
    public function __construct(int $code, ?string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * 获取xml
     *
     * @return string
     */
    public function getXml(): string
    {
        return <<<EOD
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
    }
}
