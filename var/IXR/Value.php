<?php

namespace IXR;

/**
 * IXR值
 *
 * @package IXR
 */
class Value
{
    private $data;

    private $type;

    /**
     * @param mixed $data
     * @param bool|string $type
     */
    public function __construct($data, $type = false)
    {
        $this->data = $data;
        if (!$type) {
            $type = $this->calculateType();
        }
        $this->type = $type;
        if ($type == 'struct') {
            /* Turn all the values in the array in to new IXR_Value objects */
            foreach ($this->data as $key => $value) {
                $this->data[$key] = new Value($value);
            }
        }
        if ($type == 'array') {
            for ($i = 0, $j = count($this->data); $i < $j; $i++) {
                $this->data[$i] = new Value($this->data[$i]);
            }
        }
    }

    public function getXml(): string
    {
        /* Return XML for this value */
        switch ($this->type) {
            case 'boolean':
                return '<boolean>' . (($this->data) ? '1' : '0') . '</boolean>';
            case 'int':
                return '<int>' . $this->data . '</int>';
            case 'double':
                return '<double>' . $this->data . '</double>';
            case 'string':
                return '<string>' . htmlspecialchars($this->data) . '</string>';
            case 'array':
                $return = '<array><data>' . "\n";
                foreach ($this->data as $item) {
                    $return .= '  <value>' . $item->getXml() . "</value>\n";
                }
                $return .= '</data></array>';
                return $return;
            case 'struct':
                $return = '<struct>' . "\n";
                foreach ($this->data as $name => $value) {
                    $return .= "  <member><name>$name</name><value>";
                    $return .= $value->getXml() . "</value></member>\n";
                }
                $return .= '</struct>';
                return $return;
            case 'date':
            case 'base64':
                return $this->data->getXml();
        }
        return false;
    }

    /**
     * @return string
     */
    private function calculateType(): string
    {
        if ($this->data === true || $this->data === false) {
            return 'boolean';
        }
        if (is_integer($this->data)) {
            return 'int';
        }
        if (is_double($this->data)) {
            return 'double';
        }
        // Deal with IXR object types base64 and date
        if (is_object($this->data) && is_a($this->data, 'IXR_Date')) {
            return 'date';
        }
        if (is_object($this->data) && is_a($this->data, 'IXR_Base64')) {
            return 'base64';
        }
        // If it is a normal PHP object convert it in to a struct
        if (is_object($this->data)) {
            $this->data = get_object_vars($this->data);
            return 'struct';
        }
        if (!is_array($this->data)) {
            return 'string';
        }
        /* We have an array - is it an array or a struct ? */
        if ($this->isStruct($this->data)) {
            return 'struct';
        } else {
            return 'array';
        }
    }

    private function isStruct($array): bool
    {
        /* Nasty function to check if an array is a struct or not */
        $expected = 0;
        foreach ($array as $key => $value) {
            if ((string)$key != (string)$expected) {
                return true;
            }
            $expected++;
        }
        return false;
    }
}
