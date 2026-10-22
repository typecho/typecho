<?php

namespace IXR;

/**
 * IXR消息
 *
 * @package IXR
 */
class Message
{
    /**
     * @var string
     */
    public string $message;

    /**
     * @var string
     */
    public string $messageType;  // methodCall / methodResponse / fault

    public int $faultCode;

    public string $faultString;

    /**
     * @var string
     */
    public string $methodName;

    /**
     * @var array
     */
    public array $params = [];

    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function parse(): bool
    {
        // first remove the XML declaration
        $this->message = preg_replace('/<\?xml(.*)?\?' . '>/', '', $this->message);
        if (trim($this->message) == '') {
            return false;
        }

        $document = new \DOMDocument();
        $useErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($this->message, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        // XML-RPC has no DTDs, and anything but a call or a response is not XML-RPC
        if (
            !$loaded
            || $document->doctype !== null
            || !in_array($document->documentElement->nodeName, ['methodCall', 'methodResponse'])
        ) {
            return false;
        }

        $root = $document->documentElement;
        $this->messageType = $root->nodeName;

        foreach ($this->children($root) as $child) {
            switch ($child->nodeName) {
                case 'methodName':
                    $this->methodName = trim($child->textContent);
                    break;
                case 'params':
                    foreach ($this->children($child, 'param') as $param) {
                        $this->addParam($param);
                    }
                    break;
                case 'fault':
                    $this->messageType = 'fault';
                    $this->addParam($child);
                    break;
            }
        }

        // Grab the error messages, if any
        if ($this->messageType == 'fault') {
            $this->faultCode = intval($this->params[0]['faultCode']);
            $this->faultString = $this->params[0]['faultString'];
        }
        return true;
    }

    /**
     * @param \DOMElement $element <param> or <fault>
     */
    private function addParam(\DOMElement $element)
    {
        $value = $this->children($element, 'value')[0] ?? null;
        if (isset($value)) {
            $this->params[] = $this->parseValue($value);
        }
    }

    /**
     * @param \DOMElement $value
     * @return mixed
     */
    private function parseValue(\DOMElement $value)
    {
        $typed = $this->children($value)[0] ?? null;

        // "If no type is indicated, the type is string."
        if (!isset($typed)) {
            return $value->textContent;
        }

        $text = $typed->textContent;

        // localName also accepts prefixed extensions such as <ex:i8> and <ex:nil>
        switch ($typed->localName) {
            case 'int':
            case 'i4':
            case 'i8':
                return (int) trim($text);
            case 'double':
                return (float) trim($text);
            case 'string':
                return trim($text);
            case 'dateTime.iso8601':
                return new Date(trim($text));
            case 'boolean':
                $text = trim($text);
                return strtolower($text) == 'false' ? false : (bool) $text;
            case 'base64':
                return base64_decode($text);
            case 'nil':
                return null;
            case 'struct':
                $struct = [];
                foreach ($this->children($typed, 'member') as $member) {
                    $name = $this->children($member, 'name')[0] ?? null;
                    $memberValue = $this->children($member, 'value')[0] ?? null;
                    if (isset($name, $memberValue)) {
                        $struct[trim($name->textContent)] = $this->parseValue($memberValue);
                    }
                }
                return $struct;
            case 'array':
                $array = [];
                foreach ($this->children($typed, 'data') as $data) {
                    foreach ($this->children($data, 'value') as $item) {
                        $array[] = $this->parseValue($item);
                    }
                }
                return $array;
            default:
                return $text;
        }
    }

    /**
     * @param \DOMElement $element
     * @param string|null $name only return children with this name
     * @return \DOMElement[]
     */
    private function children(\DOMElement $element, ?string $name = null): array
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement && (!isset($name) || $child->nodeName == $name)) {
                $children[] = $child;
            }
        }

        return $children;
    }
}
