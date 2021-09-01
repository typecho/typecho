<?php

namespace Widget\Metas\Category;

use Typecho\Common;
use Typecho\Db;
use Typecho\Widget\Exception;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Category Admin
 */
class Admin extends Rows
{
    /**
     * 执行函数
     *
     * @throws Db\Exception
     */
    public function execute()
    {
        $select = $this->db->select('mid')->from('table.metas')->where('type = ?', 'category');
        $select->where('parent = ?', $this->request->parent ? $this->request->parent : 0);

        $this->stack = $this->getCategories(array_column(
            $this->db->fetchAll($select->order('table.metas.order', Db::SORT_ASC)),
            'mid'
        ));
    }

    /**
     * 向上的返回链接
     *
     * @throws Db\Exception
     */
    public function backLink()
    {
        if (isset($this->request->parent)) {
            $category = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'category', $this->request->parent));

            if (!empty($category)) {
                $parent = $this->db->fetchRow($this->select()
                    ->where('type = ? AND mid = ?', 'category', $category['parent']));

                if ($parent) {
                    echo '<a href="'
                        . Common::url('manage-categories.php?parent=' . $parent['mid'], $this->options->adminUrl)
                        . '">';
                } else {
                    echo '<a href="' . Common::url('manage-categories.php', $this->options->adminUrl) . '">';
                }

                echo '&laquo; ';
                _e('返回父级分类');
                echo '</a>';
            }
        }
    }

    /**
     * 获取菜单标题
     *
     * @return string|null
     * @throws Db\Exception|Exception
     */
    public function getMenuTitle(): ?string
    {
        if (isset($this->request->parent)) {
            $category = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'category', $this->request->parent));

            if (!empty($category)) {
                return _t('管理 %s 的子分类', $category['name']);
            }
        } else {
            return null;
        }

        throw new Exception(_t('分类不存在'), 404);
    }

    /**
     * 获取菜单标题
     *
     * @return string
     */
    public function getAddLink(): string
    {
        if (isset($this->request->parent)) {
            return 'category.php?parent=' . $this->request->filter('int')->parent;
        } else {
            return 'category.php';
        }
    }
}
