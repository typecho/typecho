<?php

namespace Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Special exception to break executor
 */
class Terminal extends Exception
{
}
