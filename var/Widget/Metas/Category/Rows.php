<?php

namespace Widget\Metas\Category;

use Typecho\Config;
use Typecho\Db;
use Widget\Base\Metas;
use Widget\Base\TreeTrait;

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
 * @property-read int $levels
 * @property-read array $children
 */
class Rows extends Metas
{
    use TreeTrait;

    /**
     * _categoryOptions
     *
     * @var Config|null
     * @access private
     */
    private ?Config $categoryOptions = null;

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('ignore=0&current=');

        $select = $this->select()->where('type = ?', 'category');
        $categories = $this->db->fetchAll($select->order('table.metas.order', Db::SORT_ASC));

        $this->initTree('mid', $categories);
    }

    /**
     * 执行函数
     *
     * @return void
     */
    public function execute()
    {
        $this->stack = $this->getRows($this->orders, $this->parameter->ignore);
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
        self::pluginHandle()->trigger($plugged)->call('listCategories', $this->categoryOptions, $this);

        if (!$plugged) {
            $this->stack = $this->getRows($this->top);

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
     * 根据深度余数输出
     *
     * @param ...$args
     */
    public function levelsAlt(...$args)
    {
        $this->altBy($this->levels, ...$args);
    }

    /**
     * 将每行的值压入堆栈
     *
     * @access public
     * @param array $row 每行的值
     * @return array
     */
    public function filter(array $row): array
    {
        [$directory, $path] = $this->getDirectory($row['mid'], $row['slug']);

        $row['directory'] = $path;
        $row = parent::filter($row);
        $row['directory'] = $directory;

        return $row;
    }

    /**
     * 子评论
     *
     * @return array
     */
    protected function ___children(): array
    {
        return $this->getChildren($this->mid);
    }
}
