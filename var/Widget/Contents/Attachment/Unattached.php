<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 没有关联的文件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 没有关联的文件组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Contents_Attachment_Unattached extends Widget_Abstract_Contents
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
        $select = $this->select()->where('table.contents.type = ? AND
        (table.contents.parent = 0 OR table.contents.parent IS NULL)', 'attachment');
        
        /** 加上对用户的判断 */
        $this->where('table.contents.authorId = ?', $this->user->uid);

        /** 提交查询 */
        $select->order('table.contents.created', Typecho_Db::SORT_DESC);

        $this->db->fetchAll($select, array($this, 'push'));
    }
}
