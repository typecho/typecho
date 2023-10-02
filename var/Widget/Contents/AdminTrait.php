<?php

namespace Widget\Contents;

use Typecho\Config;
use Typecho\Db\Exception as DbException;
use Typecho\Db\Query;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\PageNavigator\Box;

/**
 * 文章管理列表组件
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
     * @param Config $parameter
     * @return void
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('pageSize=20');
        $this->currentPage = $this->request->get('page', 1);
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
}
