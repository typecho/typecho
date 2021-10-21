<?php

namespace Typecho\Db\Adapter;

trait MysqlTrait
{
    use QueryTrait;

    /**
     * 清空数据表
     *
     * @param string $table
     * @param mixed $handle 连接对象
     * @throws SQLException
     */
    public function truncate(string $table, $handle)
    {
        $this->query('TRUNCATE TABLE ' . $this->quoteColumn($table), $handle);
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
        return $this->buildQuery($sql);
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return 'mysql';
    }
}
