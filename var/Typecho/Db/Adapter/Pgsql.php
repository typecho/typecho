<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Pgsql适配器
 *
 * @package Db
 */
class Pgsql implements Adapter
{
    /**
     * 最后一次操作的数据表
     *
     * @access protected
     * @var string
     */
    protected $_lastTable;

    /**
     * 数据库连接字符串标示
     *
     * @access private
     * @var resource
     */
    private $_dbLink;

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('pgsql');
    }

    /**
     * 数据库连接函数
     *
     * @param Config $config 数据库配置
     * @return resource
     * @throws Exception
     */
    public function connect(Config $config)
    {
        if (
            $this->_dbLink = pg_connect("host={$config->host} port={$config->port}"
                . " dbname={$config->database} user={$config->user} password={$config->password}")
        ) {
            if ($config->charset) {
                pg_query($this->_dbLink, "SET NAMES '{$config->charset}'");
            }
            return $this->_dbLink;
        }

        /** 数据库异常 */
        throw new Exception(pg_last_error($this->_dbLink));
    }

    /**
     * 获取数据库版本
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        $version = pg_version($handle);
        return 'pgsql:pgsql ' . $version['server'];
    }

    /**
     * 清空数据表
     *
     * @param string $table
     * @param resource $handle 连接对象
     * @throws Exception
     */
    public function truncate(string $table, $handle)
    {
        $this->query('TRUNCATE TABLE ' . $this->quoteColumn($table) . ' RESTART IDENTITY', $handle);
    }

    /**
     * 执行数据库查询
     *
     * @param string $query 数据库查询SQL字符串
     * @param resource $handle 连接对象
     * @param integer $op 数据库读写状态
     * @param string|null $action 数据库动作
     * @param string|null $table 数据表
     * @return resource
     * @throws Exception
     */
    public function query(string $query, $handle, int $op = Db::READ, ?string $action = null, ?string $table = null)
    {
        $this->_lastTable = $table;
        if ($resource = pg_query($handle, $query)) {
            return $resource;
        }

        /** 数据库异常 */
        throw new Exception(
            @pg_last_error($handle),
            pg_result_error_field(pg_get_result($handle), PGSQL_DIAG_SQLSTATE)
        );
    }

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param resource $resource 查询返回资源标识
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return pg_fetch_assoc($resource) ?: null;
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param resource $resource 查询的资源数据
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        return pg_fetch_object($resource) ?: null;
    }

    /**
     * 取出最后一次查询影响的行数
     *
     * @param resource $resource 查询的资源数据
     * @param resource $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return pg_affected_rows($resource);
    }

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param resource $resource 查询的资源数据
     * @param resource $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle)
    {
        /** 查看是否存在序列,可能需要更严格的检查 */
        if (pg_fetch_assoc(pg_query($handle, 'SELECT oid FROM pg_class WHERE relname = ' . $this->quoteValue($this->_lastTable . '_seq')))) {
            return pg_fetch_result(pg_query($handle, 'SELECT CURRVAL(' . $this->quoteValue($this->_lastTable . '_seq') . ')'), 0, 0);
        }

        return 0;
    }

    /**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue(string $string): string
    {
        return '\'' . pg_escape_string($string) . '\'';
    }
}
