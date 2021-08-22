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
    public $message;

    /**
     * @var string
     */
    public $messageType;  // methodCall / methodResponse / fault

    public $faultCode;

    public $faultString;

    /**
     * @var string
     */
    public $methodName;

    /**
     * @var array
     */
    public $params = [];

    // Current variable stacks
    private $arrayStructs = [];   // The stack used to keep track of the current array/struct

    private $arrayStructsTypes = []; // Stack keeping track of if things are structs or array

    private $currentStructName = [];  // A stack as well

    private $currentTagContents;

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
        $parser = xml_parser_create();
        // Set XML parser to take the case of tags in to account
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        // Set XML parser callback functions
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, [$this, 'tagOpen'], [$this, 'tagClose']);
        xml_set_character_data_handler($parser, [$this, 'cdata']);
        if (!xml_parse($parser, $this->message)) {
            /* die(sprintf('XML error: %s at line %d',
                xml_error_string(xml_get_error_code($this->parser)),
                xml_get_current_line_number($this->parser))); */
            return false;
        }
        xml_parser_free($parser);
        // Grab the error messages, if any
        if ($this->messageType == 'fault') {
            $this->faultCode = $this->params[0]['faultCode'];
            $this->faultString = $this->params[0]['faultString'];
        }
        return true;
    }

    /**
     * @param $parser
     * @param $tag
     * @param $attr
     */
    private function tagOpen($parser, string $tag, $attr)
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
     * @param $parser
     * @param string $cdata
     */
    private function cdata($parser, string $cdata)
    {
        $this->currentTagContents .= $cdata;
    }

    /**
     * @param $parser
     * @param string $tag
     */
    private function tagClose($parser, string $tag)
    {
        switch ($tag) {
            case 'int':
            case 'i4':
                $value = (int) trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            case 'double':
                $value = (double) trim($this->currentTagContents);
                $this->currentTagContents = '';
                break;
            case 'string':
                $value = (string)trim($this->currentTagContents);
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
                    $value = (string) $this->currentTagContents;
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
