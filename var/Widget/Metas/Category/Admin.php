<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Widget_Metas_Category_Admin  
 * 
 * @uses Widget_Metas_Category_List
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com> 
 * @license GNU General Public License 2.0
 */
class Widget_Metas_Category_Admin extends Widget_Metas_Category_List
{
   /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $select = $this->db->select('mid')->from('table.metas')->where('type = ?', 'category');
        $select->where('parent = ?', $this->request->parent ? $this->request->parent : 0);

        $this->stack = $this->getCategories(Typecho_Common::arrayFlatten(
            $this->db->fetchAll($select->order('table.metas.order', Typecho_Db::SORT_ASC)), 'mid'));
    }

    /**
     * 向上的返回链接 
     * 
     * @access public
     * @return void
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
                    echo '<a href="' . Typecho_Common::url('manage-categories.php?parent=' . $parent['mid'], $this->options->adminUrl) . '">';
                } else {
                    echo '<a href="' . Typecho_Common::url('manage-categories.php', $this->options->adminUrl) . '">';
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
     * @access public
     * @return string
     */
    public function getMenuTitle()
    {
        if (isset($this->request->parent)) {
            $category = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'category', $this->request->parent));

            if (!empty($category)) {
                return _t('管理 %s 的子分类', $category['name']);
            }
        } else {
            return;
        }

        throw new Typecho_Widget_Exception(_t('分类不存在'), 404);
    }

    /**
     * 获取菜单标题
     *
     * @access public
     * @return string
     */
    public function getAddLink()
    {
        if (isset($this->request->parent)) {
            return 'category.php?parent=' . $this->request->filter('int')->parent;
        } else {
            return 'category.php';
        }
    }
}

