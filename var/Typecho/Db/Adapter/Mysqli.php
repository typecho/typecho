<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Mysqli适配器
 *
 * @package Db
 */
class Mysqli implements Adapter
{
    use MysqlTrait;

    /**
     * 数据库连接字符串标示
     *
     * @access private
     * @var \mysqli
     */
    private $dbLink;

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('mysqli');
    }

    /**
     * 数据库连接函数
     *
     * @param Config $config 数据库配置
     * @return \mysqli
     * @throws ConnectionException
     */
    public function connect(Config $config): \mysqli
    {

        if (
            $this->dbLink = @mysqli_connect(
                $config->host,
                $config->user,
                $config->password,
                $config->database,
                (empty($config->port) ? null : $config->port)
            )
        ) {
            if ($config->charset) {
                $this->dbLink->query("SET NAMES '{$config->charset}'");
            }
            return $this->dbLink;
        }

        /** 数据库异常 */
        throw new ConnectionException("Couldn't connect to database.", mysqli_connect_errno());
    }

    /**
     * 获取数据库版本
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return $this->dbLink->server_version;
    }

    /**
     * 执行数据库查询
     *
     * @param string $query 数据库查询SQL字符串
     * @param mixed $handle 连接对象
     * @param integer $op 数据库读写状态
     * @param string|null $action 数据库动作
     * @param string|null $table 数据表
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ) {
        if ($resource = @$this->dbLink->query($query)) {
            return $resource;
        }

        /** 数据库异常 */
        throw new SQLException($this->dbLink->error, $this->dbLink->errno);
    }

    /**
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        return '`' . $string . '`';
    }

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param \mysqli_result $resource 查询返回资源标识
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return $resource->fetch_assoc();
    }

    /**
     * 将数据查询的结果作为数组全部取出,其中字段名对应数组键值
     *
     * @param \mysqli_result $resource 查询返回资源标识
     * @return array
     */
    public function fetchAll($resource): array
    {
        return $resource->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param \mysqli_result $resource 查询的资源数据
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        return $resource->fetch_object();
    }

    /**
     * 引号转义函数
     *
     * @param mixed $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string): string
    {
        return "'" . $this->dbLink->real_escape_string($string) . "'";
    }

    /**
     * 取出最后一次查询影响的行数
     *
     * @param mixed $resource 查询的资源数据
     * @param \mysqli $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $handle->affected_rows;
    }

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param mixed $resource 查询的资源数据
     * @param \mysqli $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->insert_id;
    }
}
