<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库SQLite适配器
 *
 * @package Db
 */
class SQLite implements Adapter
{
    use SQLiteTrait;

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('sqlite3');
    }

    /**
     * 数据库连接函数
     *
     * @param Config $config 数据库配置
     * @return \SQLite3
     * @throws ConnectionException
     */
    public function connect(Config $config): \SQLite3
    {
        try {
            $dbHandle = new \SQLite3($config->file);
            $this->isSQLite2 = version_compare(\SQLite3::version()['versionString'], '3.0.0', '<');
        } catch (\Exception $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }

        return $dbHandle;
    }

    /**
     * 获取数据库版本
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return \SQLite3::version()['versionString'];
    }

    /**
     * 执行数据库查询
     *
     * @param string $query 数据库查询SQL字符串
     * @param \SQLite3 $handle 连接对象
     * @param integer $op 数据库读写状态
     * @param string|null $action 数据库动作
     * @param string|null $table 数据表
     * @return \SQLite3Result
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ): \SQLite3Result {
        if ($stm = $handle->prepare($query)) {
            if ($resource = $stm->execute()) {
                return $resource;
            }
        }

        /** 数据库异常 */
        throw new SQLException($handle->lastErrorMsg(), $handle->lastErrorCode());
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param \SQLite3Result $resource 查询的资源数据
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        $result = $this->fetch($resource);
        return $result ? (object) $result : null;
    }

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param \SQLite3Result $resource 查询返回资源标识
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        $result = $resource->fetchArray(SQLITE3_ASSOC);
        return $result ? $this->filterColumnName($result) : null;
    }

    /**
     * 将数据查询的结果作为数组全部取出,其中字段名对应数组键值
     *
     * @param \SQLite3Result $resource 查询的资源数据
     * @return array
     */
    public function fetchAll($resource): array
    {
        $result = [];

        while ($row = $this->fetch($resource)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * 引号转义函数
     *
     * @param mixed $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . str_replace('\'', '\'\'', $string) . '\'';
    }

    /**
     * 取出最后一次查询影响的行数
     *
     * @param \SQLite3Result $resource 查询的资源数据
     * @param \SQLite3 $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $handle->changes();
    }

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param \SQLite3Result $resource 查询的资源数据
     * @param \SQLite3 $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->lastInsertRowID();
    }
}
