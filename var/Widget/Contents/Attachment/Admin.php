<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文件管理列表
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 文件管理列表组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Contents_Attachment_Admin extends Widget_Abstract_Contents
{
    /**
     * 用于计算数值的语句对象
     *
     * @access private
     * @var Typecho_Db_Query
     */
    private $_countSql;

    /**
     * 所有文章个数
     *
     * @access private
     * @var integer
     */
    private $_total = false;

    /**
     * 分页大小
     *
     * @access private
     * @var integer
     */
    private $pageSize;

    /**
     * 当前页
     *
     * @access private
     * @var integer
     */
    private $_currentPage;

    /**
     * 所属文章
     *
     * @access protected
     * @return Typecho_Config
     */
    protected function ___parentPost()
    {
        return new Typecho_Config($this->db->fetchRow(
        $this->select()->where('table.contents.cid = ?', $this->parentId)
        ->limit(1)));
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->parameter->setDefault('pageSize=20');
        $this->_currentPage = $this->request->get('page', 1);

        /** 构建基础查询 */
        $select = $this->select()->where('table.contents.type = ?', 'attachment');

        /** 如果具有编辑以上权限,可以查看所有文件,反之只能查看自己的文件 */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.contents.authorId = ?', $this->user->uid);
        }

        /** 过滤标题 */
        if (NULL != ($keywords = $this->request->filter('search')->keywords)) {
            $args = array();
            $keywordsList = explode(' ', $keywords);
            $args[] = implode(' OR ', array_fill(0, count($keywordsList), 'table.contents.title LIKE ?'));

            foreach ($keywordsList as $keyword) {
                $args[] = '%' . $keyword . '%';
            }

            call_user_func_array(array($select, 'where'), $args);
        }

        /** 给计算数目对象赋值,克隆对象 */
        $this->_countSql = clone $select;

        /** 提交查询 */
        $select->order('table.contents.created', Typecho_Db::SORT_DESC)
        ->page($this->_currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, array($this, 'push'));
    }

    /**
     * 输出分页
     *
     * @access public
     * @return void
     */
    public function pageNav()
    {
        $query = $this->request->makeUriByRequest('page={page}');

        /** 使用盒状分页 */
        $nav = new Typecho_Widget_Helper_PageNavigator_Box(false === $this->_total ? $this->_total = $this->size($this->_countSql) : $this->_total,
        $this->_currentPage, $this->parameter->pageSize, $query);
        $nav->render('&laquo;', '&raquo;');
    }
}
