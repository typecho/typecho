<?php

namespace Typecho\Db\Adapter;

/**
 * SQLite Special Util
 */
trait SQLiteTrait
{
    private $isSQLite2 = false;

    /**
     * 清空数据表
     *
     * @param string $table
     * @param mixed $handle 连接对象
     * @throws Exception
     */
    public function truncate(string $table, $handle)
    {
        $this->query('DELETE FROM ' . $this->quoteColumn($table), $handle);
    }

    /**
     * 对象引号过滤
     *
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        return '"' . $string . '"';
    }

    /**
     * 过滤字段名
     *
     * @access private
     *
     * @param array $result
     *
     * @return array
     */
    private function filterColumnName(array $result): array
    {
        /** 如果结果为空,直接返回 */
        if (empty($result)) {
            return $result;
        }

        $tResult = [];

        /** 遍历数组 */
        foreach ($result as $key => $val) {
            /** 按点分隔 */
            if (false !== ($pos = strpos($key, '.'))) {
                $key = substr($key, $pos + 1);
            }

            $tResult[trim($key, '"')] = $val;
        }

        return $tResult;
    }

    /**
     * 处理sqlite2的distinct count
     *
     * @param string $sql
     *
     * @return string
     */
    private function filterCountQuery(string $sql): string
    {
        if (preg_match("/SELECT\s+COUNT\(DISTINCT\s+([^\)]+)\)\s+(AS\s+[^\s]+)?\s*FROM\s+(.+)/is", $sql, $matches)) {
            return 'SELECT COUNT(' . $matches[1] . ') ' . $matches[2] . ' FROM SELECT DISTINCT '
                . $matches[1] . ' FROM ' . $matches[3];
        }

        return $sql;
    }

    /**
     * 合成查询语句
     *
     * @access public
     * @param array $sql 查询对象词法数组
     * @return string
     */
    public function parseSelect(array $sql): string
    {
        if (!empty($sql['join'])) {
            foreach ($sql['join'] as $val) {
                [$table, $condition, $op] = $val;
                $sql['table'] = "{$sql['table']} {$op} JOIN {$table} ON {$condition}";
            }
        }

        $sql['limit'] = (0 == strlen($sql['limit'])) ? null : ' LIMIT ' . $sql['limit'];
        $sql['offset'] = (0 == strlen($sql['offset'])) ? null : ' OFFSET ' . $sql['offset'];

        $query = $this->filterCountQuery('SELECT ' . $sql['fields'] . ' FROM ' . $sql['table'] .
            $sql['where'] . $sql['group'] . $sql['having'] . $sql['order'] . $sql['limit'] . $sql['offset']);

        if ($this->isSQLite2) {
            $query = $this->filterSQLite2CountQuery($query);
        }

        return $query;
    }
}