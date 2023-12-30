<?php

namespace Widget\Contents;

use Typecho\Config;
use Typecho\Db\Exception;
use Widget\Base\Contents;
use Widget\Base\TreeTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 单个内容组件
 */
class From extends Contents
{
    use TreeTrait {
        initParameter as initTreeParameter;
        ___directory as ___treeDirectory;
    }

    /**
     * @param Config $parameter
     * @return void
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault([
            'cid' => null,
            'query' => null,
        ]);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $query = null;

        if (isset($this->parameter->cid)) {
            $query = $this->select()->where('cid = ?', $this->parameter->cid);
        } elseif (isset($this->parameter->query)) {
            $query = $this->parameter->query;
        }

        if ($query) {
            $this->db->fetchAll($query, [$this, 'push']);

            if ($this->type == 'page') {
                $this->initTreeParameter($this->parameter);
            }
        }
    }

    /**
     * @return array
     */
    protected function ___directory(): array
    {
        return $this->type == 'page' ? $this->___treeDirectory() : parent::___directory();
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function initTreeRows(): array
    {
        return $this->db->fetchAll($this->select(
            'table.contents.cid',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.created',
            'table.contents.authorId',
            'table.contents.modified',
            'table.contents.type',
            'table.contents.status',
            'table.contents.commentsNum',
            'table.contents.order',
            'table.contents.parent',
            'table.contents.template',
            'table.contents.password',
            'table.contents.allowComment',
            'table.contents.allowPing',
            'table.contents.allowFeed'
        )->where('table.contents.type = ?', 'page'));
    }
}
