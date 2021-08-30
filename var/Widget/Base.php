<?php

namespace Widget;

use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 纯数据抽象组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
abstract class Base extends Widget
{
    /**
     * 全局选项
     *
     * @var Options
     */
    protected $options;

    /**
     * 用户对象
     *
     * @var User
     */
    protected $user;

    /**
     * 安全模块
     *
     * @var Security
     */
    protected $security;

    /**
     * 数据库对象
     *
     * @var Db
     */
    protected $db;

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     * @throws Db\Exception
     */
    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);

        /** 初始化数据库 */
        $this->db = Db::get();

        /** 初始化常用组件 */
        $this->options = Options::alloc();
        $this->user = User::alloc();
        $this->security = Security::alloc();
    }

    /**
     * 查询方法
     *
     * @return Query
     */
    abstract public function select(): Query;

    /**
     * 获得所有记录数
     *
     * @access public
     * @param Query $condition 查询对象
     * @return integer
     */
    abstract public function size(Query $condition): int;

    /**
     * 增加记录方法
     *
     * @access public
     * @param array $rows 字段对应值
     * @return integer
     */
    abstract public function insert(array $rows): int;

    /**
     * 更新记录方法
     *
     * @access public
     * @param array $rows 字段对应值
     * @param Query $condition 查询对象
     * @return integer
     */
    abstract public function update(array $rows, Query $condition): int;

    /**
     * 删除记录方法
     *
     * @access public
     * @param Query $condition 查询对象
     * @return integer
     */
    abstract public function delete(Query $condition): int;
}
