<?php

namespace Widget\Metas\Tag;

use Widget\Base\Metas;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 相关信息输出组件
 */
class Related extends Metas
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->db->fetchAll($this->select()
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $this->parameter->cid)
            ->where('table.metas.type = ?', 'tag'), [$this, 'push']);
    }
}
