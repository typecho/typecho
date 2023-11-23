<?php

namespace Widget\Metas;

use Typecho\Db\Exception;

trait EditTrait
{

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
        return $this->db->fetchObject($this->select(['MAX(order)' => 'maxOrder'])
            ->where('type = ? AND parent = ?', $type, $parent))->maxOrder ?? 0;
    }

    /**
     * 对数据按照sort字段排序
     *
     * @param array $metas
     * @param string $type
     * @throws Exception
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
     * 合并数据
     *
     * @param integer $mid 数据主键
     * @param string $type 数据类型
     * @param array $metas 需要合并的数据集
     * @throws Exception
     */
    public function merge(int $mid, string $type, array $metas)
    {
        $contents = array_column($this->db->fetchAll($this->db->select('cid')
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
     * 根据内容的指定类别和状态更新相关meta的计数信息
     *
     * @param int $mid meta id
     * @param string $type 类别
     * @param string $status 状态
     * @throws Exception
     */
    protected function refreshCountByTypeAndStatus(int $mid, string $type, string $status = 'publish')
    {
        $num = $this->db->fetchObject($this->db->select(['COUNT(table.contents.cid)' => 'num'])->from('table.contents')
            ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid = ?', $mid)
            ->where('table.contents.type = ?', $type)
            ->where('table.contents.status = ?', $status))->num;

        $this->db->query($this->db->update('table.metas')->rows(['count' => $num])
            ->where('mid = ?', $mid));
    }
}
