<?php

namespace IXR;

/**
 * IXR请求体
 *
 * @package IXR
 */
class Request
{
    /**
     * @var string
     */
    private $xml;

    /**
     * @param string $method
     * @param array $args
     */
    public function __construct(string $method, array $args)
    {
        $this->xml = <<<EOD
<?xml version="1.0"?>
<methodCall>
<methodName>{$method}</methodName>
<params>

EOD;
        foreach ($args as $arg) {
            $this->xml .= '<param><value>';
            $v = new Value($arg);
            $this->xml .= $v->getXml();
            $this->xml .= "</value></param>\n";
        }

        $this->xml .= '</params></methodCall>';
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return strlen($this->xml);
    }

    /**
     * @return string
     */
    public function getXml(): string
    {
        return $this->xml;
    }
}
