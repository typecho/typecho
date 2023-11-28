<?php

namespace Widget\Metas\Category;

use Typecho\Db\Exception;

/**
 * Trait InitTreeRowsTrait
 */
trait InitTreeRowsTrait
{
    /**
     * @return array
     * @throws Exception
     */
    protected function initTreeRows(): array
    {
        return $this->db->fetchAll($this->select()
            ->where('type = ?', 'category'));
    }
}
