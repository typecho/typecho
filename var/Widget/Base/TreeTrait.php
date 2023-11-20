<?php

namespace Widget\Base;

use Typecho\Config;
use Typecho\Db\Exception;

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
     * 根据深度余数输出
     *
     * @param ...$args
     */
    public function levelsAlt(...$args)
    {
        $this->altBy($this->levels, ...$args);
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
     * 获取某个节点下的子节点
     *
     * @param int $id
     * @return array
     */
    public function getChildIds(int $id): array
    {
        return $id > 0 ? ($this->treeRows[$id] ?? []) : $this->top;
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
     * @param int $id
     * @return array|null
     */
    public function getRow(int $id): ?array
    {
        return $this->map[$id] ?? null;
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

    /**
     * @return array
     */
    abstract protected function initTreeRows(): array;

    /**
     * @param Config $parameter
     * @throws Exception
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('ignore=0&current=');

        $rows = $this->initTreeRows();
        $pk = $this->getPrimaryKey();

        // Sort by order asc
        usort($rows, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

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
     * @return array
     */
    protected function ___directory(): array
    {
        $directory = $this->getAllParentsSlug($this->{$this->getPrimaryKey()});
        $directory[] = $this->slug;
        return $directory;
    }

    /**
     * 获取所有子节点
     *
     * @return array
     */
    protected function ___children(): array
    {
        $id = $this->{$this->getPrimaryKey()};
        return $this->getRows($this->getChildIds($id));
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
}
