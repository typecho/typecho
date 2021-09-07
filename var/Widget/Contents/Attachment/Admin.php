<?php

namespace Widget\Contents\Attachment;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 文件管理列表组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Contents
{
    /**
     * 用于计算数值的语句对象
     *
     * @var Query
     */
    private $countSql;

    /**
     * 所有文章个数
     *
     * @var integer
     */
    private $total = false;

    /**
     * 当前页
     *
     * @var integer
     */
    private $currentPage;

    /**
     * 执行函数
     *
     * @return void
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function execute()
    {
        $this->parameter->setDefault('pageSize=20');
        $this->currentPage = $this->request->get('page', 1);

        /** 构建基础查询 */
        $select = $this->select()->where('table.contents.type = ?', 'attachment');

        /** 如果具有编辑以上权限,可以查看所有文件,反之只能查看自己的文件 */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.contents.authorId = ?', $this->user->uid);
        }

        /** 过滤标题 */
        if (null != ($keywords = $this->request->filter('search')->keywords)) {
            $args = [];
            $keywordsList = explode(' ', $keywords);
            $args[] = implode(' OR ', array_fill(0, count($keywordsList), 'table.contents.title LIKE ?'));

            foreach ($keywordsList as $keyword) {
                $args[] = '%' . $keyword . '%';
            }

            call_user_func_array([$select, 'where'], $args);
        }

        /** 给计算数目对象赋值,克隆对象 */
        $this->countSql = clone $select;

        /** 提交查询 */
        $select->order('table.contents.created', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * 输出分页
     *
     * @return void
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function pageNav()
    {
        $query = $this->request->makeUriByRequest('page={page}');

        /** 使用盒状分页 */
        $nav = new Box(
            false === $this->total ? $this->total = $this->size($this->countSql) : $this->total,
            $this->currentPage,
            $this->parameter->pageSize,
            $query
        );

        $nav->render('&laquo;', '&raquo;');
    }

    /**
     * 所属文章
     *
     * @return Config
     * @throws Exception
     */
    protected function ___parentPost(): Config
    {
        return new Config($this->db->fetchRow(
            $this->select()->where('table.contents.cid = ?', $this->parentId)->limit(1)
        ));
    }
}
