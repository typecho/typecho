<?php

namespace Widget\Base;

use Typecho\Db\Exception;
use Typecho\Db\Query;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 全局选项组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Options extends Base implements QueryInterface
{
    /**
     * 获取原始查询对象
     *
     * @access public
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select()->from('table.options');
    }

    /**
     * 插入一条记录
     *
     * @param array $rows 记录插入值
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        return $this->db->query($this->db->insert('table.options')->rows($rows));
    }

    /**
     * 更新记录
     *
     * @param array $rows 记录更新值
     * @param Query $condition 更新条件
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        return $this->db->query($condition->update('table.options')->rows($rows));
    }

    /**
     * 删除记录
     *
     * @param Query $condition 删除条件
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.options'));
    }

    /**
     * 获取记录总数
     *
     * @param Query $condition 计算条件
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(name)' => 'num'])->from('table.options'))->num;
    }

    /**
     * 以checkbox选项判断是否某个值被启用
     *
     * @param mixed $settings 选项集合
     * @param string $name 选项名称
     * @return integer
     */
    protected function isEnableByCheckbox($settings, string $name): int
    {
        return is_array($settings) && in_array($name, $settings) ? 1 : 0;
    }
}
