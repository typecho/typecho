<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Plugin;
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
class Metas extends Base implements QueryInterface
{
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
     * @param array $value 需要过滤的行数据
     * @return array
     */
    public function filter(array $value): array
    {
        //生成静态链接
        $type = $value['type'];
        $routeExists = (null != Router::get($type));
        $tmpSlug = $value['slug'];
        $value['slug'] = urlencode($value['slug']);

        $value['url'] = $value['permalink'] = $routeExists ? Router::url($type, $value, $this->options->index) : '#';

        /** 生成聚合链接 */
        /** RSS 2.0 */
        $value['feedUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $value['feedRssUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $value['feedAtomUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedAtomUrl) : '#';

        $value['slug'] = $tmpSlug;
        $value = Metas::pluginHandle()->filter($value, $this);
        return $value;
    }

    /**
     * 获取最大排序
     *
     * @param string $type
     * @param int $parent
     * @return integer
     * @throws Exception
     */
    public function getMaxOrder(string $type, int $parent = 0): int
    {
        return $this->db->fetchObject($this->db->select(['MAX(order)' => 'maxOrder'])
            ->from('table.metas')
            ->where('type = ? AND parent = ?', $type, $parent))->maxOrder ?? 0;
    }

    /**
     * 对数据按照sort字段排序
     *
     * @param array $metas
     * @param string $type
     */
    public function sort(array $metas, string $type)
    {
        foreach ($metas as $sort => $mid) {
            $this->update(
                ['order' => $sort + 1],
                $this->db->sql()->where('mid = ?', $mid)->where('type = ?', $type)
            );
        }
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
     * 合并数据
     *
     * @param integer $mid 数据主键
     * @param string $type 数据类型
     * @param array $metas 需要合并的数据集
     * @throws Exception
     */
    public function merge(int $mid, string $type, array $metas)
    {
        $contents = array_column($this->db->fetchAll($this->select('cid')
            ->from('table.relationships')
            ->where('mid = ?', $mid)), 'cid');

        foreach ($metas as $meta) {
            if ($mid != $meta) {
                $existsContents = array_column($this->db->fetchAll($this->db
                    ->select('cid')->from('table.relationships')
                    ->where('mid = ?', $meta)), 'cid');

                $where = $this->db->sql()->where('mid = ? AND type = ?', $meta, $type);
                $this->delete($where);
                $diffContents = array_diff($existsContents, $contents);
                $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $meta));

                foreach ($diffContents as $content) {
                    $this->db->query($this->db->insert('table.relationships')
                        ->rows(['mid' => $mid, 'cid' => $content]));
                    $contents[] = $content;
                }

                $this->update(['parent' => $mid], $this->db->sql()->where('parent = ?', $meta));
                unset($existsContents);
            }
        }

        $num = $this->db->fetchObject($this->db
            ->select(['COUNT(mid)' => 'num'])->from('table.relationships')
            ->where('table.relationships.mid = ?', $mid))->num;

        $this->update(['count' => $num], $this->db->sql()->where('mid = ?', $mid));
    }

    /**
     * 获取原始查询对象
     *
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select()->from('table.metas');
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
     * 根据tag获取ID
     *
     * @param mixed $inputTags 标签名
     * @return array|int
     * @throws Exception
     */
    public function scanTags($inputTags)
    {
        $tags = is_array($inputTags) ? $inputTags : [$inputTags];
        $result = [];

        foreach ($tags as $tag) {
            if (empty($tag)) {
                continue;
            }

            $row = $this->db->fetchRow($this->select()
                ->where('type = ?', 'tag')
                ->where('name = ?', $tag)->limit(1));

            if ($row) {
                $result[] = $row['mid'];
            } else {
                $slug = Common::slugName($tag);

                if ($slug) {
                    $result[] = $this->insert([
                        'name'  => $tag,
                        'slug'  => $slug,
                        'type'  => 'tag',
                        'count' => 0,
                        'order' => 0,
                    ]);
                }
            }
        }

        return is_array($inputTags) ? $result : current($result);
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
     * 清理没有任何内容的标签
     *
     * @throws Exception
     */
    public function clearTags()
    {
        // 取出count为0的标签
        $tags = array_column($this->db->fetchAll($this->db->select('mid')
            ->from('table.metas')->where('type = ? AND count = ?', 'tags', 0)), 'mid');

        foreach ($tags as $tag) {
            // 确认是否已经没有关联了
            $content = $this->db->fetchRow($this->db->select('cid')
                ->from('table.relationships')->where('mid = ?', $tag)
                ->limit(1));

            if (empty($content)) {
                $this->db->query($this->db->delete('table.metas')
                    ->where('mid = ?', $tag));
            }
        }
    }

    /**
     * 根据内容的指定类别和状态更新相关meta的计数信息
     *
     * @param int $mid meta id
     * @param string $type 类别
     * @param string $status 状态
     * @throws Exception
     */
    public function refreshCountByTypeAndStatus(int $mid, string $type, string $status = 'publish')
    {
        $num = $this->db->fetchObject($this->db->select(['COUNT(table.contents.cid)' => 'num'])->from('table.contents')
            ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid = ?', $mid)
            ->where('table.contents.type = ?', $type)
            ->where('table.contents.status = ?', $status))->num;

        $this->db->query($this->db->update('table.metas')->rows(['count' => $num])
            ->where('mid = ?', $mid));
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
}
