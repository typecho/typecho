<?php

/**
 * Widget_Metas_Category_Admin  
 * 
 * @uses Widget_Abstract_Metas
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com> 
 * @license GNU General Public License 2.0
 */
class Widget_Metas_Category_Admin extends Widget_Abstract_Metas
{
    /**
     * 子评论
     *
     * @access protected
     * @return array
     */
    protected function ___children()
    {
        return $this->size($this->db->sql()
            ->where('type = ? AND parent = ?', 'category', $this->mid));
    }

   /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $select = $this->select()->where('type = ?', 'category');
        $select->where('parent = ?', $this->request->parent ? $this->request->parent : 0);

        $this->db->fetchAll($select->order('table.metas.order', Typecho_Db::SORT_ASC),
            array($this, 'push'));
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
}

