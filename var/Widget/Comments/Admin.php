<?php

namespace Widget\Comments;

use Typecho\Cookie;
use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Comments;
use Widget\Base\Contents;
use Widget\Contents\From;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 后台评论输出组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Comments
{
    /**
     * 分页计算对象
     *
     * @access private
     * @var Query
     */
    private Query $countSql;

    /**
     * 当前页
     *
     * @access private
     * @var integer
     */
    private int $currentPage;

    /**
     * 所有文章个数
     *
     * @access private
     * @var integer|null
     */
    private ?int $total;

    /**
     * 获取菜单标题
     *
     * @return string
     * @throws Exception
     */
    public function getMenuTitle(): string
    {
        $content = $this->parentContent;

        if ($content) {
            return _t('%s的评论', $content['title']);
        }

        throw new Exception(_t('内容不存在'), 404);
    }

    /**
     * 执行函数
     *
     * @throws Db\Exception|Exception
     */
    public function execute()
    {
        $select = $this->select();
        $this->parameter->setDefault('pageSize=20');
        $this->currentPage = $this->request->filter('int')->get('page', 1);

        /** 过滤标题 */
        if (null != ($keywords = $this->request->filter('search')->get('keywords'))) {
            $select->where('table.comments.text LIKE ?', '%' . $keywords . '%');
        }

        /** 如果具有贡献者以上权限,可以查看所有评论,反之只能查看自己的评论 */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.comments.ownerId = ?', $this->user->uid);
        } elseif (!$this->request->is('cid')) {
            if ($this->request->is('__typecho_all_comments=on')) {
                Cookie::set('__typecho_all_comments', 'on');
            } else {
                if ($this->request->is('__typecho_all_comments=off')) {
                    Cookie::set('__typecho_all_comments', 'off');
                }

                if ('on' != Cookie::get('__typecho_all_comments')) {
                    $select->where('table.comments.ownerId = ?', $this->user->uid);
                }
            }
        }

        if (in_array($this->request->get('status'), ['approved', 'waiting', 'spam'])) {
            $select->where('table.comments.status = ?', $this->request->get('status'));
        } elseif ('hold' == $this->request->get('status')) {
            $select->where('table.comments.status <> ?', 'approved');
        } else {
            $select->where('table.comments.status = ?', 'approved');
        }

        //增加按文章归档功能
        if ($this->request->is('cid')) {
            $select->where('table.comments.cid = ?', $this->request->filter('int')->get('cid'));
        }

        $this->countSql = clone $select;

        $select->order('table.comments.coid', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * 输出分页
     *
     * @throws Exception|Db\Exception
     */
    public function pageNav()
    {
        $query = $this->request->makeUriByRequest('page={page}');

        /** 使用盒状分页 */
        $nav = new Box(
            !isset($this->total) ? $this->total = $this->size($this->countSql) : $this->total,
            $this->currentPage,
            $this->parameter->pageSize,
            $query
        );
        $nav->render(_t('&laquo;'), _t('&raquo;'));
    }

    /**
     * 获取当前内容结构
     *
     * @return Contents
     * @throws Db\Exception
     */
    protected function ___parentContent(): Contents
    {
        $cid = $this->request->is('cid') ? $this->request->filter('int')->get('cid') : $this->cid;
        return From::allocWithAlias($cid, ['cid' => $cid]);
    }

    /**
     * @return string
     */
    protected function ___permalink(): string
    {
        if ('approved' === $this->status) {
            return parent::___permalink();
        }

        return '#' . $this->theId;
    }
}
