<?php

namespace Widget\Metas\Category;

/**
 * Trait InitTreeRowsTrait
 */
trait InitTreeRowsTrait
{
    /**
     * @return array
     */
    protected function initTreeRows(): array
    {
        return $this->db->fetchAll($this->select()
            ->where('type = ?', 'category'));
    }
}
