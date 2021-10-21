<?php

namespace Typecho\Db;

use Typecho\Config;
use Typecho\Db;

/**
 * Typecho数据库适配器
 * 定义通用的数据库适配接口
 *
 * @package Db
 */
interface Adapter
{
    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool;

    /**
     * 数据库连接函数
     *
     * @param Config $config 数据库配置
     * @return mixed
     */
    public function connect(Config $config);

    /**
     * 获取数据库版本
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string;

    /**
     * 获取数据库类型
     *
     * @return string
     */
    public function getDriver(): string;

    /**
     * 清空数据表
     *
     * @param string $table 数据表名
     * @param mixed $handle 连接对象
     */
    public function truncate(string $table, $handle);

    /**
     * 执行数据库查询
     *
     * @param string $query 数据库查询SQL字符串
     * @param mixed $handle 连接对象
     * @param integer $op 数据库读写状态
     * @param string|null $action 数据库动作
     * @param string|null $table 数据表
     * @return resource
     */
    public function query(string $query, $handle, int $op = Db::READ, ?string $action = null, ?string $table = null);

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param resource $resource 查询的资源数据
     * @return array|null
     */
    public function fetch($resource): ?array;

    /**
     * 将数据查询的结果作为数组全部取出,其中字段名对应数组键值
     *
     * @param resource $resource 查询的资源数据
     * @return array
     */
    public function fetchAll($resource): array;

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param resource $resource 查询的资源数据
     * @return object|null
     */
    public function fetchObject($resource): ?object;

    /**
     * 引号转义函数
     *
     * @param mixed $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string): string;

    /**
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string;

    /**
     * 合成查询语句
     *
     * @access public
     * @param array $sql 查询对象词法数组
     * @return string
     */
    public function parseSelect(array $sql): string;

    /**
     * 取出最后一次查询影响的行数
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle): int;

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle): int;
}
