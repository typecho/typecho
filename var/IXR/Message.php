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

    // Current variable stacks
    private array $arrayStructs = [];   // The stack used to keep track of the current array/struct

    private array $arrayStructsTypes = []; // Stack keeping track of if things are structs or array

    private array $currentStructName = [];  // A stack as well

    private string $currentTagContents = '';

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

        // remove the DOCTYPE, avoid using a regexp, so we can save memory
        $count = 0;
        while (true) {
            // Fail if there is an endless loop
            if ($count >= 10) {
                return false;
            }

            $pos = strpos($this->message, '<!DOCTYPE');
            if ($pos !== false) {
                $this->message = substr($this->message, 0, $pos)
                    . substr($this->message, strpos($this->message, '>', $pos) + 1);
                $count ++;
            } else {
                break;
            }
        }

        $document = new \DOMDocument();
        $useErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($this->message, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        if (!$loaded) {
            return false;
        }

        $this->walk($document->documentElement);

        // Grab the error messages, if any
        if ($this->messageType == 'fault') {
            $this->faultCode = intval($this->params[0]['faultCode']);
            $this->faultString = $this->params[0]['faultString'];
        }
        return true;
    }

    /**
     * Replay the tree as the open/cdata/close events the old SAX handlers consumed
     *
     * @param \DOMElement $element
     */
    private function walk(\DOMElement $element)
    {
        $this->tagOpen($element->nodeName);

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->walk($child);
            } elseif ($child instanceof \DOMText) {
                // DOMCdataSection is a DOMText too
                $this->cdata($child->data);
            }
        }

        $this->tagClose($element->nodeName);
    }

    /**
     * @param string $tag
     */
    private function tagOpen(string $tag)
    {
        switch ($tag) {
            case 'methodCall':
            case 'methodResponse':
            case 'fault':
                $this->messageType = $tag;
                break;
            /* Deal with stacks of arrays and structs */
            case 'data':    // data is to all intents and puposes more interesting than array
                $this->arrayStructsTypes[] = 'array';
                $this->arrayStructs[] = [];
                break;
            case 'struct':
                $this->arrayStructsTypes[] = 'struct';
                $this->arrayStructs[] = [];
                break;
        }
    }

    /**
     * @param string $cdata
     */
    private function cdata(string $cdata)
    {
        $this->currentTagContents .= $cdata;
    }

    /**
     * @param string $tag
     */
    private function tagClose(string $tag)
    {
        switch ($tag) {
            case 'int':
            case 'i4':
                $value = (int) trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            case 'double':
                $value = (float) trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            case 'string':
                $value = trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            case 'dateTime.iso8601':
                $value = new Date(trim($this->currentTagContents));
                // $value = $iso->getTimestamp();
                $this->currentTagContents = '';
                break;
            case 'value':
                // "If no type is indicated, the type is string."
                if (trim($this->currentTagContents) != '') {
                    $value = $this->currentTagContents;
                    $this->currentTagContents = '';
                }
                break;
            case 'boolean':
                $value = (bool) trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            case 'base64':
                $value = base64_decode($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            /* Deal with stacks of arrays and structs */
            case 'data':
            case 'struct':
                $value = array_pop($this->arrayStructs);
                array_pop($this->arrayStructsTypes);
                break;
            case 'member':
                array_pop($this->currentStructName);
                break;
            case 'name':
                $this->currentStructName[] = trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            case 'methodName':
                $this->methodName = trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
        }
        if (isset($value)) {
            /*
            if (!is_array($value) && !is_object($value)) {
                $value = trim($value);
            }
            */
            if (count($this->arrayStructs) > 0) {
                // Add value to struct or array
                if ($this->arrayStructsTypes[count($this->arrayStructsTypes) - 1] == 'struct') {
                    // Add to struct
                    $this->arrayStructs[count($this->arrayStructs) - 1]
                        [$this->currentStructName[count($this->currentStructName) - 1]] = $value;
                } else {
                    // Add to array
                    $this->arrayStructs[count($this->arrayStructs) - 1][] = $value;
                }
            } else {
                // Just add as a paramater
                $this->params[] = $value;
            }
        }
    }
}
