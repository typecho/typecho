<?php

namespace Widget\Base;

use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Router;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 描述性数据组件
 *
 * @property int $mid
 * @property string $name
 * @property string $title
 * @property string $slug
 * @property string $type
 * @property string $description
 * @property int $count
 * @property int $order
 * @property int $parent
 * @property-read string $theId
 * @property-read string $url
 * @property-read string $permalink
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 */
class Metas extends Base implements QueryInterface, RowFilterInterface, PrimaryKeyInterface
{
    /**
     * @return string 获取主键
     */
    public function getPrimaryKey(): string
    {
        return 'mid';
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
        return $this->db->fetchObject($condition->select(['COUNT(mid)' => 'num'])->from('table.metas'))->num;
    }

    /**
     * 将每行的值压入堆栈
     *
     * @param array $value 每行的值
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * 通用过滤器
     *
     * @param array $row 需要过滤的行数据
     * @return array
     */
    public function filter(array $row): array
    {
        //生成静态链接
        $type = $row['type'];
        $routeExists = (null != Router::get($type));
        $routeParams = [
            'mid' => $row['mid'],
            'slug' => urlencode($row['slug']),
            'directory' => isset($row['directory']) ? implode('/', array_map('urlencode', $row['directory'])) : ''
        ];

        $row['url'] = $row['permalink'] = $routeExists ? Router::url($type, $routeParams, $this->options->index) : '#';

        /** 生成聚合链接 */
        /** RSS 2.0 */
        $row['feedUrl'] = $routeExists ? Router::url($type, $routeParams, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $row['feedRssUrl'] = $routeExists ? Router::url($type, $routeParams, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $row['feedAtomUrl'] = $routeExists ? Router::url($type, $routeParams, $this->options->feedAtomUrl) : '#';

        return Metas::pluginHandle()->call('filter', $row, $this);
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
        return $this->db->query($condition->update('table.metas')->rows($rows));
    }

    /**
     * 获取原始查询对象
     *
     * @param mixed $fields
     * @return Query
     * @throws Exception
     */
    public function select(...$fields): Query
    {
        return $this->db->select(...$fields)->from('table.metas');
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
        return $this->db->query($condition->delete('table.metas'));
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
        return $this->db->query($this->db->insert('table.metas')->rows($rows));
    }

    /**
     * 锚点id
     *
     * @access protected
     * @return string
     */
    protected function ___theId(): string
    {
        return $this->type . '-' . $this->mid;
    }

    /**
     * @return string
     */
    protected function ___title(): string
    {
        return $this->name;
    }
}
