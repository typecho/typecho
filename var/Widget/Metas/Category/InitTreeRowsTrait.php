<?php

namespace Widget\Metas\Category;

use Typecho\Config;
use Typecho\Db\Exception;

/**
 * Trait InitTreeRowsTrait
 */
trait InitTreeRowsTrait
{
    /**
     * @param Config $parameter
     * @throws Exception
     */
    protected function initParameter(Config $parameter)
    {
        $this->initTreeParameter($parameter);
    }

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
