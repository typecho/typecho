<?php

namespace Widget\Base;

use Typecho\Config;

/**
 * 处理树状数据结构
 */
trait TreeTrait
{
    /**
     * 树状数据结构
     *
     * @var array
     * @access private
     */
    private array $treeRows = [];

    /**
     * 顶层节点
     *
     * @var array
     * @access private
     */
    private array $top = [];

    /**
     * 所有节点哈希表
     *
     * @var array
     * @access private
     */
    private array $map = [];

    /**
     * 顺序流
     *
     * @var array
     * @access private
     */
    private array $orders = [];

    /**
     * 所有子节点列表
     *
     * @var array
     * @access private
     */
    private array $childNodes = [];

    /**
     * 所有父节点列表
     *
     * @var array
     * @access private
     */
    private array $parents = [];

    /**
     * 初始化
     *
     * @param string $pk
     * @param array $rows
     * @return void
     */
    protected function initTree(string $pk, array $rows)
    {
        foreach ($rows as $row) {
            $row['levels'] = 0;
            $this->map[$row[$pk]] = $row;
        }

        // 读取数据
        foreach ($this->map as $id => $row) {
            $parent = $row['parent'];

            if (0 != $parent && isset($this->map[$parent])) {
                $this->treeRows[$parent][] = $id;
            } else {
                $this->top[] = $id;
            }
        }

        // 预处理深度
        $this->levelWalkCallback($this->top);
        $this->map = array_map([$this, 'filter'], $this->map);
    }

    /**
     * 预处理节点迭代
     *
     * @param array $rows
     * @param array $parents
     */
    private function levelWalkCallback(array $rows, array $parents = [])
    {
        foreach ($parents as $parent) {
            if (!isset($this->childNodes[$parent])) {
                $this->childNodes[$parent] = [];
            }

            $this->childNodes[$parent] = array_merge($this->childNodes[$parent], $rows);
        }

        foreach ($rows as $id) {
            $this->orders[] = $id;
            $parent = $this->map[$id]['parent'];

            if (0 != $parent && isset($this->map[$parent])) {
                $levels = $this->map[$parent]['levels'] + 1;
                $this->map[$id]['levels'] = $levels;
            }

            $this->parents[$id] = $parents;

            if (!empty($this->treeRows[$id])) {
                $new = $parents;
                $new[] = $id;
                $this->levelWalkCallback($this->treeRows[$id], $new);
            }
        }
    }

    /**
     * 获取目录
     *
     * @param int $id
     * @param string $slug
     * @return array
     */
    public function getDirectory(int $id, string $slug): array
    {
        $directory = $this->getAllParentsSlug($id);
        $directory[] = $slug;
        $path = implode('/', array_map('urlencode', $directory));

        return [$directory, $path];
    }

    /**
     * 获取某个节点所有父级节点缩略名
     *
     * @param int $id
     * @return array
     */
    public function getAllParentsSlug(int $id): array
    {
        $parents = [];

        if (isset($this->parents[$id])) {
            foreach ($this->parents[$id] as $parent) {
                $parents[] = $this->map[$parent]['slug'];
            }
        }

        return $parents;
    }

    /**
     * 获取某个节点下的所有子节点
     *
     * @param int $id
     * @return array
     */
    public function getAllChildIds(int $id): array
    {
        return $this->childNodes[$id] ?? [];
    }

    /**
     * 获取某个节点所有父级节点
     *
     * @param int $id
     * @return array
     */
    public function getAllParents(int $id): array
    {
        $parents = [];

        if (isset($this->parents[$id])) {
            foreach ($this->parents[$id] as $parent) {
                $parents[] = $this->map[$parent];
            }
        }

        return $parents;
    }

    /**
     * 获取所有子节点
     *
     * @param int $id
     * @return array
     */
    public function getChildren(int $id): array
    {
        return isset($this->treeRows[$id]) ?
            $this->getRows($this->treeRows[$id]) : [];
    }

    /**
     * 获取多个节点
     *
     * @param array $ids
     * @param integer $ignore
     * @return array
     */
    public function getRows(array $ids, int $ignore = 0): array
    {
        $result = [];

        if (!empty($ids)) {
            foreach ($ids as $id) {
                if (!$ignore || ($ignore != $id && !$this->hasParent($id, $ignore))) {
                    $result[] = $this->map[$id];
                }
            }
        }

        return $result;
    }

    /**
     * 是否拥有某个父级节点
     *
     * @param mixed $id
     * @param mixed $parentId
     * @return bool
     */
    public function hasParent($id, $parentId): bool
    {
        if (isset($this->parents[$id])) {
            foreach ($this->parents[$id] as $parent) {
                if ($parent == $parentId) {
                    return true;
                }
            }
        }

        return false;
    }
}
