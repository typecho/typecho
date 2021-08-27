<?php

namespace Widget\Comments;

use Typecho\Db;
use Typecho\Db\Exception;
use Typecho\Widget\Request;
use Typecho\Widget\Response;
use Widget\Base\Comments;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 最近评论组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Recent extends Comments
{
    /**
     * 构造函数,初始化组件
     *
     * @param Request $request request对象
     * @param Response $response response对象
     * @param mixed $params 参数列表
     * @throws Exception
     */
    public function __construct(Request $request, Response $response, $params = null)
    {
        parent::__construct($request, $response, $params);
        $this->parameter->setDefault(
            ['pageSize' => $this->options->commentsListSize, 'parentId' => 0, 'ignoreAuthor' => false]
        );
    }

    /**
     * 执行函数
     *
     * @throws Exception
     */
    public function execute()
    {
        $select = $this->select()->limit($this->parameter->pageSize)
            ->where('table.comments.status = ?', 'approved')
            ->order('table.comments.coid', Db::SORT_DESC);

        if ($this->parameter->parentId) {
            $select->where('cid = ?', $this->parameter->parentId);
        }

        if ($this->options->commentsShowCommentOnly) {
            $select->where('type = ?', 'comment');
        }

        /** 忽略作者评论 */
        if ($this->parameter->ignoreAuthor) {
            $select->where('ownerId <> authorId');
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
