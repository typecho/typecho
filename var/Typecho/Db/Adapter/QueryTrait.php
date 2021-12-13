<?php

namespace Typecho\Db\Adapter;

/**
 * Build Sql
 */
trait QueryTrait
{
    /**
     * @param array $sql
     * @return string
     */
    private function buildQuery(array $sql): string
    {
        if (!empty($sql['join'])) {
            foreach ($sql['join'] as $val) {
                [$table, $condition, $op] = $val;
                $sql['table'] = "{$sql['table']} {$op} JOIN {$table} ON {$condition}";
            }
        }

        $sql['limit'] = isset($sql['limit']) ? ' LIMIT ' . $sql['limit'] : '';
        $sql['offset'] = isset($sql['offset']) ? ' OFFSET ' . $sql['offset'] : '';

        return 'SELECT ' . $sql['fields'] . ' FROM ' . $sql['table'] .
            $sql['where'] . $sql['group'] . $sql['having'] . $sql['order'] . $sql['limit'] . $sql['offset'];
    }
}
