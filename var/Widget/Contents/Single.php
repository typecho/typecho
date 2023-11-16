<?php

namespace Widget\Contents;

use Typecho\Config;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 单个内容组件
 */
class Single extends Contents
{
    /**
     * @param Config $parameter
     * @return void
     */
    public function initParameter(Config $parameter)
    {
        $parameter->setDefault('cid=0');
    }

    /**
     * @return void
     */
    public function execute()
    {
        if ($this->parameter->cid) {
            $this->db->fetchRow(
                $this->select()->where('table.contents.cid = ?', $this->parameter->cid),
                [$this, 'push']
            );
        }
    }
}
