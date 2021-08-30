<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 后台成员列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Users
{
    /**
     * 分页计算对象
     *
     * @var Query
     */
    private $countSql;

    /**
     * 所有文章个数
     *
     * @var integer
     */
    private $total;

    /**
     * 当前页
     *
     * @var integer
     */
    private $currentPage;

    /**
     * 执行函数
     *
     * @throws Db\Exception
     */
    public function execute()
    {
        $this->parameter->setDefault('pageSize=20');
        $select = $this->select();
        $this->currentPage = $this->request->get('page', 1);

        /** 过滤标题 */
        if (null != ($keywords = $this->request->keywords)) {
            $select->where(
                'name LIKE ? OR screenName LIKE ?',
                '%' . Common::filterSearchQuery($keywords) . '%',
                '%' . Common::filterSearchQuery($keywords) . '%'
            );
        }

        $this->countSql = clone $select;

        $select->order('table.users.uid', Db::SORT_ASC)
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
        $nav->render('&laquo;', '&raquo;');
    }

    /**
     * 仅仅输出域名和路径
     *
     * @return string
     */
    protected function ___domainPath(): string
    {
        $parts = parse_url($this->url);
        return $parts['host'] . ($parts['path'] ?? null);
    }

    /**
     * 发布文章数
     *
     * @return integer
     * @throws Db\Exception
     */
    protected function ___postsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.authorId = ?', $this->uid))->num;
    }
}
