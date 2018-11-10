<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 全局统计
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 全局统计组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Stat extends Typecho_Widget
{
    /**
     * 用户对象
     *
     * @access protected
     * @var Widget_User
     */
    protected $user;

    /**
     * 数据库对象
     *
     * @access protected
     * @var Typecho_Db
     */
    protected $db;

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     * @return void
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /** 初始化数据库 */
        $this->db = Typecho_Db::get();

        /** 初始化常用组件 */
        $this->user = $this->widget('Widget_User');
    }

    /**
     * 获取已发布的文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___publishedPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish'))->num;
    }

    /**
     * 获取待审核的文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___waitingPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
                    ->where('table.contents.status = ?', 'waiting'))->num;
    }

    /**
     * 获取草稿文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___draftPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post_draft'))->num;
    }

    /**
     * 获取当前用户已发布的文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___myPublishedPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish')
                    ->where('table.contents.authorId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户待审核文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___myWaitingPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
                    ->where('table.contents.status = ?', 'waiting')
                    ->where('table.contents.authorId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户草稿文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___myDraftPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post_draft')
                    ->where('table.contents.authorId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户已发布的文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___currentPublishedPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish')
                    ->where('table.contents.authorId = ?', $this->request->filter('int')->uid))->num;
    }

    /**
     * 获取当前用户待审核文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___currentWaitingPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
                    ->where('table.contents.status = ?', 'waiting')
                    ->where('table.contents.authorId = ?', $this->request->filter('int')->uid))->num;
    }

    /**
     * 获取当前用户草稿文章数目
     *
     * @access protected
     * @return integer
     */
    protected function ___currentDraftPostsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post_draft')
                    ->where('table.contents.authorId = ?', $this->request->filter('int')->uid))->num;
    }

    /**
     * 获取已发布页面数目
     *
     * @access protected
     * @return integer
     */
    protected function ___publishedPagesNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'page')
                    ->where('table.contents.status = ?', 'publish'))->num;
    }

    /**
     * 获取草稿页面数目
     *
     * @access protected
     * @return integer
     */
    protected function ___draftPagesNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'page_draft'))->num;
    }

    /**
     * 获取当前显示的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___publishedCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'approved'))->num;
    }

    /**
     * 获取当前待审核的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___waitingCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'waiting'))->num;
    }

    /**
     * 获取当前垃圾评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___spamCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'spam'))->num;
    }

    /**
     * 获取当前用户显示的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___myPublishedCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'approved')
                    ->where('table.comments.ownerId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户显示的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___myWaitingCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'waiting')
                    ->where('table.comments.ownerId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户显示的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___mySpamCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'spam')
                    ->where('table.comments.ownerId = ?', $this->user->uid))->num;
    }
    
    /**
     * 获取当前文章的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___currentCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取当前文章显示的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___currentPublishedCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'approved')
                    ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取当前文章显示的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___currentWaitingCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'waiting')
                    ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取当前文章显示的评论数目
     *
     * @access protected
     * @return integer
     */
    protected function ___currentSpamCommentsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))
                    ->from('table.comments')
                    ->where('table.comments.status = ?', 'spam')
                    ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取分类数目
     *
     * @access protected
     * @return integer
     */
    protected function ___categoriesNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(mid)' => 'num'))
                    ->from('table.metas')
                    ->where('table.metas.type = ?', 'category'))->num;
    }
    
     /**
     * 获取标签数目
     *
     * @access protected
     * @return integer
     */
    protected function ___tagsNum()
    {
        return $this->db->fetchObject($this->db->select(array('COUNT(mid)' => 'num'))
                    ->from('table.metas')
                    ->where('table.metas.type = ?', 'tag'))->num;
    }
}
