<?php

namespace Widget\Metas\Category;

use Typecho\Config;
use Typecho\Db;
use Widget\Base\Metas;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 分类输出组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Rows extends Metas
{
    /**
     * 树状分类结构
     *
     * @var array
     * @access private
     */
    private $treeViewCategories = [];

    /**
     * _categoryOptions
     *
     * @var mixed
     * @access private
     */
    private $categoryOptions = null;

    /**
     * 顶层分类
     *
     * @var array
     * @access private
     */
    private $top = [];

    /**
     * 所有分类哈希表
     *
     * @var array
     * @access private
     */
    private $map = [];

    /**
     * 顺序流
     *
     * @var array
     * @access private
     */
    private $orders = [];

    /**
     * 所有子节点列表
     *
     * @var array
     * @access private
     */
    private $childNodes = [];

    /**
     * 所有父节点列表
     *
     * @var array
     * @access private
     */
    private $parents = [];

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('ignore=0&current=');

        $select = $this->select()->where('type = ?', 'category');

        $categories = $this->db->fetchAll($select->order('table.metas.order', Db::SORT_ASC));
        foreach ($categories as $category) {
            $category['levels'] = 0;
            $this->map[$category['mid']] = $category;
        }

        // 读取数据
        foreach ($this->map as $mid => $category) {
            $parent = $category['parent'];

            if (0 != $parent && isset($this->map[$parent])) {
                $this->treeViewCategories[$parent][] = $mid;
            } else {
                $this->top[] = $mid;
            }
        }

        // 预处理深度
        $this->levelWalkCallback($this->top);
        $this->map = array_map([$this, 'filter'], $this->map);
    }

    /**
     * 预处理分类迭代
     *
     * @param array $categories
     * @param array $parents
     */
    private function levelWalkCallback(array $categories, array $parents = [])
    {
        foreach ($parents as $parent) {
            if (!isset($this->childNodes[$parent])) {
                $this->childNodes[$parent] = [];
            }

            $this->childNodes[$parent] = array_merge($this->childNodes[$parent], $categories);
        }

        foreach ($categories as $mid) {
            $this->orders[] = $mid;
            $parent = $this->map[$mid]['parent'];

            if (0 != $parent && isset($this->map[$parent])) {
                $levels = $this->map[$parent]['levels'] + 1;
                $this->map[$mid]['levels'] = $levels;
            }

            $this->parents[$mid] = $parents;

            if (!empty($this->treeViewCategories[$mid])) {
                $new = $parents;
                $new[] = $mid;
                $this->levelWalkCallback($this->treeViewCategories[$mid], $new);
            }
        }
    }

    /**
     * 执行函数
     *
     * @return void
     */
    public function execute()
    {
        $this->stack = $this->getCategories($this->orders);
    }

    /**
     * treeViewCategories
     *
     * @param mixed $categoryOptions 输出选项
     */
    public function listCategories($categoryOptions = null)
    {
        //初始化一些变量
        $this->categoryOptions = Config::factory($categoryOptions);
        $this->categoryOptions->setDefault([
            'wrapTag'       => 'ul',
            'wrapClass'     => '',
            'itemTag'       => 'li',
            'itemClass'     => '',
            'showCount'     => false,
            'showFeed'      => false,
            'countTemplate' => '(%d)',
            'feedTemplate'  => '<a href="%s">RSS</a>'
        ]);

        // 插件插件接口
        self::pluginHandle()->trigger($plugged)->listCategories($this->categoryOptions, $this);

        if (!$plugged) {
            $this->stack = $this->getCategories($this->top);

            if ($this->have()) {
                echo '<' . $this->categoryOptions->wrapTag . (empty($this->categoryOptions->wrapClass)
                        ? '' : ' class="' . $this->categoryOptions->wrapClass . '"') . '>';
                while ($this->next()) {
                    $this->treeViewCategoriesCallback();
                }
                echo '</' . $this->categoryOptions->wrapTag . '>';
            }

            $this->stack = $this->map;
        }
    }

    /**
     * 列出分类回调
     */
    private function treeViewCategoriesCallback(): void
    {
        $categoryOptions = $this->categoryOptions;
        if (function_exists('treeViewCategories')) {
            treeViewCategories($this, $categoryOptions);
            return;
        }

        $classes = [];

        if ($categoryOptions->itemClass) {
            $classes[] = $categoryOptions->itemClass;
        }

        $classes[] = 'category-level-' . $this->levels;

        echo '<' . $categoryOptions->itemTag . ' class="'
            . implode(' ', $classes);

        if ($this->levels > 0) {
            echo ' category-child';
            $this->levelsAlt(' category-level-odd', ' category-level-even');
        } else {
            echo ' category-parent';
        }

        if ($this->mid == $this->parameter->current) {
            echo ' category-active';
        } elseif (
            isset($this->childNodes[$this->mid]) && in_array($this->parameter->current, $this->childNodes[$this->mid])
        ) {
            echo ' category-parent-active';
        }

        echo '"><a href="' . $this->permalink . '">' . $this->name . '</a>';

        if ($categoryOptions->showCount) {
            printf($categoryOptions->countTemplate, intval($this->count));
        }

        if ($categoryOptions->showFeed) {
            printf($categoryOptions->feedTemplate, $this->feedUrl);
        }

        if ($this->children) {
            $this->treeViewCategories();
        }

        echo '</' . $categoryOptions->itemTag . '>';
    }

    /**
     * 根据深度余数输出
     *
     * @param ...$args
     */
    public function levelsAlt(...$args)
    {
        $num = count($args);
        $split = $this->levels % $num;
        echo $args[(0 == $split ? $num : $split) - 1];
    }

    /**
     * treeViewCategories
     *
     * @access public
     * @return void
     */
    public function treeViewCategories()
    {
        $children = $this->children;
        if ($children) {
            //缓存变量便于还原
            $tmp = $this->row;
            $this->sequence++;

            //在子评论之前输出
            echo '<' . $this->categoryOptions->wrapTag . (empty($this->categoryOptions->wrapClass)
                    ? '' : ' class="' . $this->categoryOptions->wrapClass . '"') . '>';

            foreach ($children as $child) {
                $this->row = $child;
                $this->treeViewCategoriesCallback();
                $this->row = $tmp;
            }

            //在子评论之后输出
            echo '</' . $this->categoryOptions->wrapTag . '>';

            $this->sequence--;
        }
    }

    /**
     * 将每行的值压入堆栈
     *
     * @access public
     * @param array $value 每行的值
     * @return array
     */
    public function filter(array $value): array
    {
        $value['directory'] = $this->getAllParentsSlug($value['mid']);
        $value['directory'][] = $value['slug'];

        $tmpCategoryTree = $value['directory'];
        $value['directory'] = implode('/', array_map('urlencode', $value['directory']));

        $value = parent::filter($value);
        $value['directory'] = $tmpCategoryTree;

        return $value;
    }

    /**
     * 获取某个分类所有父级节点缩略名
     *
     * @param mixed $mid
     * @access public
     * @return array
     */
    public function getAllParentsSlug($mid): array
    {
        $parents = [];

        if (isset($this->parents[$mid])) {
            foreach ($this->parents[$mid] as $parent) {
                $parents[] = $this->map[$parent]['slug'];
            }
        }

        return $parents;
    }

    /**
     * 获取某个分类下的所有子节点
     *
     * @param mixed $mid
     * @access public
     * @return array
     */
    public function getAllChildren($mid): array
    {
        return $this->childNodes[$mid] ?? [];
    }

    /**
     * 获取某个分类所有父级节点
     *
     * @param mixed $mid
     * @access public
     * @return array
     */
    public function getAllParents($mid): array
    {
        $parents = [];

        if (isset($this->parents[$mid])) {
            foreach ($this->parents[$mid] as $parent) {
                $parents[] = $this->map[$parent];
            }
        }

        return $parents;
    }

    /**
     * 获取单个分类
     *
     * @param integer $mid
     * @return mixed
     */
    public function getCategory(int $mid)
    {
        return $this->map[$mid] ?? null;
    }

    /**
     * 子评论
     *
     * @return array
     */
    protected function ___children(): array
    {
        return isset($this->treeViewCategories[$this->mid]) ?
            $this->getCategories($this->treeViewCategories[$this->mid]) : [];
    }

    /**
     * 获取多个分类
     *
     * @param mixed $mids
     * @return array
     */
    public function getCategories($mids): array
    {
        $result = [];

        if (!empty($mids)) {
            foreach ($mids as $mid) {
                if (
                    !$this->parameter->ignore
                    || ($this->parameter->ignore != $mid
                        && !$this->hasParent($mid, $this->parameter->ignore))
                ) {
                    $result[] = $this->map[$mid];
                }
            }
        }

        return $result;
    }

    /**
     * 是否拥有某个父级分类
     *
     * @param mixed $mid
     * @param mixed $parentId
     * @return bool
     */
    public function hasParent($mid, $parentId): bool
    {
        if (isset($this->parents[$mid])) {
            foreach ($this->parents[$mid] as $parent) {
                if ($parent == $parentId) {
                    return true;
                }
            }
        }

        return false;
    }
}
