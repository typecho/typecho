<?php

namespace Typecho\Widget\Helper;

use Typecho\Widget\Exception;

/**
 * 内容分页抽象类
 *
 * @package Widget
 */
abstract class PageNavigator
{
    /**
     * 记录总数
     *
     * @var integer
     */
    protected $total;

    /**
     * 页面总数
     *
     * @var integer
     */
    protected $totalPage;

    /**
     * 当前页面
     *
     * @var integer
     */
    protected $currentPage;

    /**
     * 每页内容数
     *
     * @var integer
     */
    protected $pageSize;

    /**
     * 页面链接模板
     *
     * @var string
     */
    protected $pageTemplate;

    /**
     * 链接锚点
     *
     * @var string
     */
    protected $anchor;

    /**
     * 页面占位符
     *
     * @var mixed
     */
    protected $pageHolder = ['{page}', '%7Bpage%7D'];

    /**
     * 构造函数,初始化页面基本信息
     *
     * @param integer $total 记录总数
     * @param integer $currentPage 当前页面
     * @param integer $pageSize 每页记录数
     * @param string $pageTemplate 页面链接模板
     * @throws Exception
     */
    public function __construct(int $total, int $currentPage, int $pageSize, string $pageTemplate)
    {
        $this->total = $total;
        $this->totalPage = ceil($total / $pageSize);
        $this->currentPage = $currentPage;
        $this->pageSize = $pageSize;
        $this->pageTemplate = $pageTemplate;

        if (($currentPage > $this->totalPage || $currentPage < 1) && $total > 0) {
            throw new Exception('Page Not Exists', 404);
        }
    }

    /**
     * 设置页面占位符
     *
     * @param string $holder 页面占位符
     */
    public function setPageHolder(string $holder)
    {
        $this->pageHolder = ['{' . $holder . '}',
            str_replace(['{', '}'], ['%7B', '%7D'], $holder)];
    }

    /**
     * 设置锚点
     *
     * @param string $anchor 锚点
     */
    public function setAnchor(string $anchor)
    {
        $this->anchor = '#' . $anchor;
    }

    /**
     * 输出方法
     *
     * @throws Exception
     */
    public function render()
    {
        throw new Exception(get_class($this) . ':' . __METHOD__, 500);
    }
}
