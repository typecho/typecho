<?php

namespace IXR;

/**
 * IXR Base64编码
 *
 * @package IXR
 */
class Base64
{
    /**
     * 编码数据
     *
     * @var string
     */
    private $data;

    /**
     * 初始化数据
     *
     * @param string $data
     */
    public function __construct(string $data)
    {
        $this->data = $data;
    }

    /**
     * 获取XML数据
     *
     * @return string
     */
    public function getXml()
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}
