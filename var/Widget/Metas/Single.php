<?php

namespace Widget\Metas;

use Typecho\Db\Exception;
use Widget\Base\Metas;
use Widget\Base\TreeTrait;
use Widget\Metas\Category\InitTreeRowsTrait;

class Single extends Metas
{
    use InitTreeRowsTrait;

    use TreeTrait {
        initParameter as initTreeParameter;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $query = null;

        if (isset($this->parameter->mid)) {
            $query = $this->select()->where('mid = ?', $this->parameter->mid);
        } elseif (isset($this->parameter->query)) {
            $query = $this->parameter->query;
        }

        if ($query) {
            $this->push($this->db->fetchRow($query));
        }
    }

    /**
     * @param array $row
     * @return array
     * @throws Exception
     */
    public function filter(array $row): array
    {
        if ($row['type'] == 'category') {
            $this->initTreeParameter($this->parameter);
        }

        return parent::filter($row);
    }
}
