<?php

namespace Widget\Contents\Page;

use Typecho\Config;
use Typecho\Db\Exception;
use Widget\Base\Contents;
use Widget\Base\TreeViewTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 独立页面列表组件
 *
 * @author qining
 * @page typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Rows extends Contents
{
    use TreeViewTrait;

    /**
     * 执行函数
     *
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $this->pushAll($this->getRows($this->orders, $this->parameter->ignore));
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function initTreeRows(): array
    {
        $select = $this->select(
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
        )->where('table.contents.type = ?', 'page')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $this->options->time);

        //去掉自定义首页
        $frontPage = explode(':', $this->options->frontPage);
        if (2 == count($frontPage) && 'page' == $frontPage[0]) {
            $select->where('table.contents.cid <> ?', $frontPage[1]);
        }

        return $this->db->fetchAll($select);
    }

    /**
     * treeViewPages
     *
     * @param mixed $pageOptions 输出选项
     */
    public function listPages($pageOptions = null)
    {
        //初始化一些变量
        $pageOptions = Config::factory($pageOptions);
        $pageOptions->setDefault([
            'wrapTag'       => 'ul',
            'wrapClass'     => '',
            'itemTag'       => 'li',
            'itemClass'     => '',
            'showCount'     => false,
            'showFeed'      => false,
            'countTemplate' => '(%d)',
            'feedTemplate'  => '<a href="%s">RSS</a>'
        ]);

        // 插件插件接口
        self::pluginHandle()->trigger($plugged)->call('listPages', $pageOptions, $this);

        if (!$plugged) {
            $this->listRows($pageOptions, 'treeViewPagesCallback', intval($this->parameter->current));
        }
    }
}
