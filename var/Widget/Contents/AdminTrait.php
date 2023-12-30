<?php

namespace Widget\Contents;

use Typecho\Config;
use Typecho\Db\Exception as DbException;
use Typecho\Db\Query;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\PageNavigator\Box;

/**
 * 文章管理列表组件
 *
 * @property-read array? $revision
 */
trait AdminTrait
{
    /**
     * 所有文章个数
     *
     * @var integer|null
     */
    private ?int $total;

    /**
     * 当前页
     *
     * @var integer
     */
    private int $currentPage;

    /**
     * @return void
     */
    protected function initPage()
    {
        $this->parameter->setDefault('pageSize=20');
        $this->currentPage = $this->request->filter('int')->get('page', 1);
    }

    /**
     * @param Query $select
     * @return void
     */
    protected function searchQuery(Query $select)
    {
        if ($this->request->is('keywords')) {
            $keywords = $this->request->filter('search')->get('keywords');
            $args = [];
            $keywordsList = explode(' ', $keywords);
            $args[] = implode(' OR ', array_fill(0, count($keywordsList), 'table.contents.title LIKE ?'));

            foreach ($keywordsList as $keyword) {
                $args[] = '%' . $keyword . '%';
            }

            $select->where(...$args);
        }
    }

    /**
     * @param Query $select
     * @return void
     */
    protected function countTotal(Query $select)
    {
        $countSql = clone $select;
        $this->total = $this->size($countSql);
    }

    /**
     * 输出分页
     *
     * @throws Exception
     * @throws DbException
     */
    public function pageNav()
    {
        $query = $this->request->makeUriByRequest('page={page}');

        /** 使用盒状分页 */
        $nav = new Box(
            $this->total,
            $this->currentPage,
            $this->parameter->pageSize,
            $query
        );

        $nav->render('&laquo;', '&raquo;');
    }

    /**
     * @return array|null
     * @throws DbException
     */
    protected function ___revision(): ?array
    {
        return $this->db->fetchRow(
            $this->select('cid', 'modified')
                ->where(
                    'table.contents.parent = ? AND table.contents.type = ?',
                    $this->cid,
                    'revision'
                )
                ->limit(1)
        );
    }
}
