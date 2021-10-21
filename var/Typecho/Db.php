<?php

namespace Typecho;

use Typecho\Db\Adapter;
use Typecho\Db\Query;
use Typecho\Db\Exception as DbException;

/**
 * 包含获取数据支持方法的类.
 * 必须定义__TYPECHO_DB_HOST__, __TYPECHO_DB_PORT__, __TYPECHO_DB_NAME__,
 * __TYPECHO_DB_USER__, __TYPECHO_DB_PASS__, __TYPECHO_DB_CHAR__
 *
 * @package Db
 */
class Db
{
    /** 读取数据库 */
    public const READ = 1;

    /** 写入数据库 */
    public const WRITE = 2;

    /** 升序方式 */
    public const SORT_ASC = 'ASC';

    /** 降序方式 */
    public const SORT_DESC = 'DESC';

    /** 表内连接方式 */
    public const INNER_JOIN = 'INNER';

    /** 表外连接方式 */
    public const OUTER_JOIN = 'OUTER';

    /** 表左连接方式 */
    public const LEFT_JOIN = 'LEFT';

    /** 表右连接方式 */
    public const RIGHT_JOIN = 'RIGHT';

    /** 数据库查询操作 */
    public const SELECT = 'SELECT';

    /** 数据库更新操作 */
    public const UPDATE = 'UPDATE';

    /** 数据库插入操作 */
    public const INSERT = 'INSERT';

    /** 数据库删除操作 */
    public const DELETE = 'DELETE';

    /**
     * 数据库适配器
     * @var Adapter
     */
    private $adapter;

    /**
     * 默认配置
     *
     * @var array
     */
    private $config;

    /**
     * 已经连接
     *
     * @access private
     * @var array
     */
    private $connectedPool;

    /**
     * 前缀
     *
     * @access private
     * @var string
     */
    private $prefix;

    /**
     * 适配器名称
     *
     * @access private
     * @var string
     */
    private $adapterName;

    /**
     * 实例化的数据库对象
     * @var Db
     */
    private static $instance;

    /**
     * 数据库类构造函数
     *
     * @param mixed $adapterName 适配器名称
     * @param string $prefix 前缀
     *
     * @throws DbException
     */
    public function __construct($adapterName, string $prefix = 'typecho_')
    {
        /** 获取适配器名称 */
        $adapterName = $adapterName == 'Mysql' ? 'Mysqli' : $adapterName;
        $this->adapterName = $adapterName;

        /** 数据库适配器 */
        $adapterName = '\Typecho\Db\Adapter\\' . str_replace('_', '\\', $adapterName);

        if (!call_user_func([$adapterName, 'isAvailable'])) {
            throw new DbException("Adapter {$adapterName} is not available");
        }

        $this->prefix = $prefix;

        /** 初始化内部变量 */
        $this->connectedPool = [];

        $this->config = [
            self::READ => [],
            self::WRITE => []
        ];

        //实例化适配器对象
        $this->adapter = new $adapterName();
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * 获取适配器名称
     *
     * @access public
     * @return string
     */
    public function getAdapterName(): string
    {
        return $this->adapterName;
    }

    /**
     * 获取表前缀
     *
     * @access public
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param Config $config
     * @param int $op
     */
    public function addConfig(Config $config, int $op)
    {
        if ($op & self::READ) {
            $this->config[self::READ][] = $config;
        }

        if ($op & self::WRITE) {
            $this->config[self::WRITE][] = $config;
        }
    }

    /**
     * getConfig
     *
     * @param int $op
     *
     * @return Config
     * @throws DbException
     */
    public function getConfig(int $op): Config
    {
        if (empty($this->config[$op])) {
            /** DbException */
            throw new DbException('Missing Database Connection');
        }

        $key = array_rand($this->config[$op]);
        return $this->config[$op][$key];
    }

    /**
     * 重置连接池
     *
     * @return void
     */
    public function flushPool()
    {
        $this->connectedPool = [];
    }

    /**
     * 选择数据库
     *
     * @param int $op
     *
     * @return mixed
     * @throws DbException
     */
    public function selectDb(int $op)
    {
        if (!isset($this->connectedPool[$op])) {
            $selectConnectionConfig = $this->getConfig($op);
            $selectConnectionHandle = $this->adapter->connect($selectConnectionConfig);
            $this->connectedPool[$op] = $selectConnectionHandle;
        }

        return $this->connectedPool[$op];
    }

    /**
     * 获取SQL词法构建器实例化对象
     *
     * @return Query
     */
    public function sql(): Query
    {
        return new Query($this->adapter, $this->prefix);
    }

    /**
     * 为多数据库提供支持
     *
     * @access public
     * @param array $config 数据库实例
     * @param integer $op 数据库操作
     * @return void
     */
    public function addServer(array $config, int $op)
    {
        $this->addConfig(Config::factory($config), $op);
        $this->flushPool();
    }

    /**
     * 获取版本
     *
     * @param int $op
     *
     * @return string
     * @throws DbException
     */
    public function getVersion(int $op = self::READ): string
    {
        return $this->adapter->getVersion($this->selectDb($op));
    }

    /**
     * 设置默认数据库对象
     *
     * @access public
     * @param Db $db 数据库对象
     * @return void
     */
    public static function set(Db $db)
    {
        self::$instance = $db;
    }

    /**
     * 获取数据库实例化对象
     * 用静态变量存储实例化的数据库对象,可以保证数据连接仅进行一次
     *
     * @return Db
     * @throws DbException
     */
    public static function get(): Db
    {
        if (empty(self::$instance)) {
            /** DbException */
            throw new DbException('Missing Database Object');
        }

        return self::$instance;
    }

    /**
     * 选择查询字段
     *
     * @param ...$ags
     *
     * @return Query
     * @throws DbException
     */
    public function select(...$ags): Query
    {
        $this->selectDb(self::READ);

        $args = func_get_args();
        return call_user_func_array([$this->sql(), 'select'], $args ?: ['*']);
    }

    /**
     * 更新记录操作(UPDATE)
     *
     * @param string $table 需要更新记录的表
     *
     * @return Query
     * @throws DbException
     */
    public function update(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->update($table);
    }

    /**
     * 删除记录操作(DELETE)
     *
     * @param string $table 需要删除记录的表
     *
     * @return Query
     * @throws DbException
     */
    public function delete(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->delete($table);
    }

    /**
     * 插入记录操作(INSERT)
     *
     * @param string $table 需要插入记录的表
     *
     * @return Query
     * @throws DbException
     */
    public function insert(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->insert($table);
    }

    /**
     * @param $table
     * @throws DbException
     */
    public function truncate($table)
    {
        $table = preg_replace("/^table\./", $this->prefix, $table);
        $this->adapter->truncate($table, $this->selectDb(self::WRITE));
    }

    /**
     * 执行查询语句
     *
     * @param mixed $query 查询语句或者查询对象
     * @param int $op 数据库读写状态
     * @param string $action 操作动作
     *
     * @return mixed
     * @throws DbException
     */
    public function query($query, int $op = self::READ, string $action = self::SELECT)
    {
        $table = null;

        /** 在适配器中执行查询 */
        if ($query instanceof Query) {
            $action = $query->getAttribute('action');
            $table = $query->getAttribute('table');
            $op = (self::UPDATE == $action || self::DELETE == $action
                || self::INSERT == $action) ? self::WRITE : self::READ;
        } elseif (!is_string($query)) {
            /** 如果query不是对象也不是字符串,那么将其判断为查询资源句柄,直接返回 */
            return $query;
        }

        /** 选择连接池 */
        $handle = $this->selectDb($op);

        /** 提交查询 */
        $resource = $this->adapter->query($query instanceof Query ?
            $query->prepare($query) : $query, $handle, $op, $action, $table);

        if ($action) {
            //根据查询动作返回相应资源
            switch ($action) {
                case self::UPDATE:
                case self::DELETE:
                    return $this->adapter->affectedRows($resource, $handle);
                case self::INSERT:
                    return $this->adapter->lastInsertId($resource, $handle);
                case self::SELECT:
                default:
                    return $resource;
            }
        } else {
            //如果直接执行查询语句则返回资源
            return $resource;
        }
    }

    /**
     * 一次取出所有行
     *
     * @param mixed $query 查询对象
     * @param callable|null $filter 行过滤器函数,将查询的每一行作为第一个参数传入指定的过滤器中
     *
     * @return array
     * @throws DbException
     */
    public function fetchAll($query, ?callable $filter = null): array
    {
        //执行查询
        $resource = $this->query($query);
        $result = $this->adapter->fetchAll($resource);

        return $filter ? array_map($filter, $result) : $result;
    }

    /**
     * 一次取出一行
     *
     * @param mixed $query 查询对象
     * @param callable|null $filter 行过滤器函数,将查询的每一行作为第一个参数传入指定的过滤器中
     * @return array|null
     * @throws DbException
     */
    public function fetchRow($query, ?callable $filter = null): ?array
    {
        $resource = $this->query($query);

        return ($rows = $this->adapter->fetch($resource)) ?
            ($filter ? call_user_func($filter, $rows) : $rows) :
            null;
    }

    /**
     * 一次取出一个对象
     *
     * @param mixed $query 查询对象
     * @param array|null $filter 行过滤器函数,将查询的每一行作为第一个参数传入指定的过滤器中
     * @return object|null
     * @throws DbException
     */
    public function fetchObject($query, ?array $filter = null): ?object
    {
        $resource = $this->query($query);

        return ($rows = $this->adapter->fetchObject($resource)) ?
            ($filter ? call_user_func($filter, $rows) : $rows) :
            null;
    }
}
