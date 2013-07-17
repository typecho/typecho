<?php
/**
 * 独立页面管理列表
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 独立页面管理列表组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Contents_Page_Admin extends Widget_Contents_Post_Admin
{
    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 构建基础查询 */
        $select = $this->select()->where('table.contents.type = ?', 'page');

        /** 过滤状态 */
        $select->where('table.contents.status = ? OR table.contents.status = ? OR (table.contents.status = ? AND table.contents.parent = 0)',
            'publish', 'waiting', 'draft');

        /** 过滤标题 */
        if (NULL != ($keywords = $this->request->keywords)) {
            $args = array();
            $keywordsList = explode(' ', $keywords);
            $args[] = implode(' OR ', array_fill(0, count($keywordsList), 'table.contents.title LIKE ?'));

            foreach ($keywordsList as $keyword) {
                $args[] = '%' . Typecho_Common::filterSearchQuery($keyword) . '%';
            }

            call_user_func_array(array($select, 'where'), $args);
        }

        /** 提交查询 */
        $select->order('table.contents.order', Typecho_Db::SORT_ASC);

        $this->db->fetchAll($select, array($this, 'push'));
    }
}
