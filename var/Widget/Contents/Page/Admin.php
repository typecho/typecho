<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
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
        /** 过滤状态 */
        $select = $this->select();
		if ($this->request->status == "publish") {
			// 显示已发布
			$select->where('table.contents.type = ? AND table.contents.status = ?', 'page', 'publish');
		}
		else if ($this->request->status == "draft") {
			// 显示草稿
			$select->where('table.contents.type = ? AND table.contents.status = ?', 'page_draft', 'publish');
		}
		else if ($this->request->status == "trash") {
			// 显示回收站
			$select->where('table.contents.type = ? OR (table.contents.type = ? AND table.contents.parent = ?)', 'page', 'page_draft', 0);
			$select->where('table.contents.status = ?', 'trash');
		}
		else {
			// 显示所有（除回收站）
			$select->where('table.contents.type = ? OR (table.contents.type = ? AND table.contents.parent = ?)', 'page', 'page_draft', 0);
			$select->where('table.contents.status != ?', 'trash');
		}
		
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
