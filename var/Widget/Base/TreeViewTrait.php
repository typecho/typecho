<?php

namespace Widget\Base;

use Typecho\Config;

trait TreeViewTrait
{
    use TreeTrait;

    /**
     * treeViewRows
     *
     * @param mixed $rowOptions 输出选项
     * @param string $func 回调函数
     * @param int $current 当前项
     */
    protected function listRows(Config $rowOptions, string $func, int $current = 0)
    {
        $this->stack = $this->getRows($this->top);

        if ($this->have()) {
            echo '<' . $rowOptions->wrapTag . (empty($rowOptions->wrapClass)
                    ? '' : ' class="' . $rowOptions->wrapClass . '"') . '>';
            while ($this->next()) {
                $this->treeViewRowsCallback($rowOptions, $func, $current);
            }
            echo '</' . $rowOptions->wrapTag . '>';
        }

        $this->stack = $this->map;
    }

    /**
     * 列出分类回调
     *
     * @param Config $rowOptions 输出选项
     * @param string $func 回调函数
     * @param int $current 当前项
     */
    private function treeViewRowsCallback(Config $rowOptions, string $func, int $current): void
    {
        if (function_exists($func)) {
            call_user_func($func, $this, $rowOptions);
            return;
        }

        $id = $this->{$this->getPrimaryKey()};
        $type = $this->getType();
        $classes = [];

        if ($rowOptions->itemClass) {
            $classes[] = $rowOptions->itemClass;
        }

        $classes[] = $type . '-level-' . $this->levels;

        echo '<' . $rowOptions->itemTag . ' class="'
            . implode(' ', $classes);

        if ($this->levels > 0) {
            echo " {$type}-child";
            $this->levelsAlt(" {$type}-level-odd", " {$type}-level-even");
        } else {
            echo " {$type}-parent";
        }

        if ($id == $current) {
            echo " {$type}-active";
        } elseif (
            isset($this->childNodes[$id]) && in_array($current, $this->childNodes[$id])
        ) {
            echo " {$type}-parent-active";
        }

        echo '"><a href="' . $this->permalink . '">' . $this->title . '</a>';

        if ($rowOptions->showCount) {
            printf($rowOptions->countTemplate, intval($this->count));
        }

        if ($rowOptions->showFeed) {
            printf($rowOptions->feedTemplate, $this->feedUrl);
        }

        if ($this->children) {
            $this->treeViewRows($rowOptions, $func, $current);
        }

        echo '</' . $rowOptions->itemTag . '>';
    }

    /**
     * treeViewRows
     *
     * @param Config $rowOptions 输出选项
     * @param string $func 回调函数
     * @param int $current 当前项
     */
    private function treeViewRows(Config $rowOptions, string $func, int $current)
    {
        $children = $this->children;
        if ($children) {
            //缓存变量便于还原
            $tmp = $this->row;
            $this->sequence++;

            //在子评论之前输出
            echo '<' . $rowOptions->wrapTag . (empty($rowOptions->wrapClass)
                    ? '' : ' class="' . $rowOptions->wrapClass . '"') . '>';

            foreach ($children as $child) {
                $this->row = $child;
                $this->treeViewRowsCallback($rowOptions, $func, $current);
                $this->row = $tmp;
            }

            //在子评论之后输出
            echo '</' . $rowOptions->wrapTag . '>';

            $this->sequence--;
        }
    }
}
