<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 后台评论输出组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Comments_Admin extends Widget_Abstract_Comments
{
    /**
     * 分页计算对象
     *
     * @access private
     * @var Typecho_Db_Query
     */
    private $_countSql;

    /**
     * 当前页
     *
     * @access private
     * @var integer
     */
    private $_currentPage;

    /**
     * 所有文章个数
     *
     * @access private
     * @var integer
     */
    private $_total = false;

    /**
     * 获取当前内容结构
     *
     * @return stdClass
     */
    protected function ___parentContent()
    {
        $cid = isset($this->request->cid) ? $this->request->filter('int')->cid : $this->cid;
        return $this->db->fetchRow($this->widget('Widget_Abstract_Contents')->select()
        ->where('table.contents.cid = ?', $cid)
        ->limit(1), array($this->widget('Widget_Abstract_Contents'), 'filter'));
    }

    /**
     * 获取菜单标题
     *
     * @return string
     * @throws Typecho_Widget_Exception
     */
    public function getMenuTitle()
    {
        $content = $this->parentContent;

        if ($content) {
            return _t('%s的评论', $content['title']);
        }

        throw new Typecho_Widget_Exception(_t('内容不存在'), 404);
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $select = $this->select();
        $this->parameter->setDefault('pageSize=20');
        $this->_currentPage = $this->request->get('page', 1);

        /** 过滤标题 */
        if (NULL != ($keywords = $this->request->filter('search')->keywords)) {
            $select->where('table.comments.text LIKE ?', '%' . $keywords . '%');
        }

        /** 如果具有贡献者以上权限,可以查看所有评论,反之只能查看自己的评论 */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.comments.ownerId = ?', $this->user->uid);
        } else if (!isset($this->request->cid)) {
            if ('on' == $this->request->__typecho_all_comments) {
                Typecho_Cookie::set('__typecho_all_comments', 'on');
            } else {
                if ('off' == $this->request->__typecho_all_comments) {
                    Typecho_Cookie::set('__typecho_all_comments', 'off');
                }

                if ('on' != Typecho_Cookie::get('__typecho_all_comments')) {
                    $select->where('table.comments.ownerId = ?', $this->user->uid);
                }
            }
        }

        if (in_array($this->request->status, array('approved', 'waiting', 'spam'))) {
            $select->where('table.comments.status = ?', $this->request->status);
        } else if ('hold' == $this->request->status) {
            $select->where('table.comments.status <> ?', 'approved');
        } else {
            $select->where('table.comments.status = ?', 'approved');
        }

        //增加按文章归档功能
        if (isset($this->request->cid)) {
            $select->where('table.comments.cid = ?', $this->request->filter('int')->cid);
        }

        $this->_countSql = clone $select;

        $select->order('table.comments.coid', Typecho_Db::SORT_DESC)
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
        $nav->render(_t('&laquo;'), _t('&raquo;'));
    }
}
