<?php

namespace Widget\Metas\Category;

use Typecho\Db\Exception;
use Widget\Base\Metas;
use Widget\Base\TreeTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Related extends Metas
{
    use InitTreeRowsTrait;
    use TreeTrait;

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $ids = array_column($this->db->fetchAll($this->select('table.metas.mid')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $this->parameter->cid)
            ->where('table.metas.type = ?', 'category')), 'mid');

        usort($ids, function ($a, $b) {
            $orderA = array_search($a, $this->orders);
            $orderB = array_search($b, $this->orders);

            return $orderA <=> $orderB;
        });

        $this->pushAll($this->getRows($ids));
    }
}
