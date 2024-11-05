<?php

namespace Widget\Metas\Category;

use Typecho\Config;
use Widget\Base\Metas;
use Widget\Base\TreeViewTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 分类输出组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @property-read int $levels
 * @property-read array $children
 */
class Rows extends Metas
{
    use InitTreeRowsTrait;
    use TreeViewTrait;

    /**
     * 执行函数
     *
     * @return void
     */
    public function execute()
    {
        $this->pushAll($this->getRows($this->orders, $this->parameter->ignore));
    }

    /**
     * treeViewCategories
     *
     * @param mixed $categoryOptions 输出选项
     */
    public function listCategories($categoryOptions = null)
    {
        //初始化一些变量
        $categoryOptions = Config::factory($categoryOptions);
        $categoryOptions->setDefault([
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
        self::pluginHandle()->trigger($plugged)->call('listCategories', $categoryOptions, $this);

        if (!$plugged) {
            $this->listRows(
                $categoryOptions,
                'category',
                'treeViewCategoriesCallback',
                intval($this->parameter->current)
            );
        }
    }
}
