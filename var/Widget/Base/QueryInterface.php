<?php

namespace Widget\Base;

use Typecho\Db\Query;

/**
 * Base Query Interface
 */
interface QueryInterface
{
    /**
     * 查询方法
     *
     * @return Query
     */
    public function select(): Query;

    /**
     * 获得所有记录数
     *
     * @access public
     * @param Query $condition 查询对象
     * @return integer
     */
    public function size(Query $condition): int;

    /**
     * 增加记录方法
     *
     * @access public
     * @param array $rows 字段对应值
     * @return integer
     */
    public function insert(array $rows): int;

    /**
     * 更新记录方法
     *
     * @access public
     * @param array $rows 字段对应值
     * @param Query $condition 查询对象
     * @return integer
     */
    public function update(array $rows, Query $condition): int;

    /**
     * 删除记录方法
     *
     * @access public
     * @param Query $condition 查询对象
     * @return integer
     */
    public function delete(Query $condition): int;
}
