<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库PDOMysql适配器
 *
 * @package Db
 */
abstract class Pdo implements Adapter
{
    /**
     * 数据库对象
     *
     * @access protected
     * @var \PDO
     */
    protected $object;

    /**
     * 最后一次操作的数据表
     *
     * @access protected
     * @var string
     */
    protected $lastTable;

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return class_exists('PDO');
    }

    /**
     * 数据库连接函数
     *
     * @param Config $config 数据库配置
     * @return \PDO
     * @throws ConnectionException
     */
    public function connect(Config $config): \PDO
    {
        try {
            $this->object = $this->init($config);
            $this->object->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this->object;
        } catch (\PDOException $e) {
            /** 数据库异常 */
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 初始化数据库
     *
     * @param Config $config 数据库配置
     * @abstract
     * @access public
     * @return \PDO
     */
    abstract public function init(Config $config): \PDO;

    /**
     * 获取数据库版本
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return $handle->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * 执行数据库查询
     *
     * @param string $query 数据库查询SQL字符串
     * @param \PDO $handle 连接对象
     * @param integer $op 数据库读写状态
     * @param string|null $action 数据库动作
     * @param string|null $table 数据表
     * @return \PDOStatement
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ): \PDOStatement {
        try {
            $this->lastTable = $table;
            $resource = $handle->prepare($query);
            $resource->execute();
        } catch (\PDOException $e) {
            /** 数据库异常 */
            throw new SQLException($e->getMessage(), $e->getCode());
        }

        return $resource;
    }

    /**
     * 将数据查询的结果作为数组全部取出,其中字段名对应数组键值
     *
     * @param \PDOStatement $resource 查询的资源数据
     * @return array
     */
    public function fetchAll($resource): array
    {
        return $resource->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param \PDOStatement $resource 查询返回资源标识
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return $resource->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param \PDOStatement $resource 查询的资源数据
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        return $resource->fetchObject() ?: null;
    }

    /**
     * 引号转义函数
     *
     * @param mixed $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string): string
    {
        return $this->object->quote($string);
    }

    /**
     * 取出最后一次查询影响的行数
     *
     * @param \PDOStatement $resource 查询的资源数据
     * @param \PDO $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $resource->rowCount();
    }

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param \PDOStatement $resource 查询的资源数据
     * @param \PDO $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->lastInsertId();
    }
}
