<?php

namespace Typecho\Widget\Helper\Form\Element;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Number extends Text
{
    /**
     * @return string
     */
    protected function getType(): string
    {
        return 'number';
    }
}
