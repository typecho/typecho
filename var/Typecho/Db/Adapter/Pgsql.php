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
    use PgsqlTrait;

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
     * @throws ConnectionException
     */
    public function connect(Config $config)
    {
        $dsn = $this->buildConnectionString($config);

        if ($dbLink = @pg_connect($dsn)) {
            return $dbLink;
        }

        /** 数据库异常 */
        throw new ConnectionException("Couldn't connect to database.");
    }

    /**
     * 构造 libpq 连接字符串。
     *
     * @param Config $config
     * @return string
     */
    private function buildConnectionString(Config $config): string
    {
        $parameters = [
            'host' => $config->host,
            'port' => $config->port,
            'dbname' => $config->database,
            'user' => $config->user,
            'password' => $config->password
        ];

        if ($config->sslVerify) {
            $parameters['sslmode'] = 'verify-full';
            if (!empty($config->sslCa)) {
                $parameters['sslrootcert'] = $config->sslCa;
            }
        }

        if ($config->charset) {
            $parameters['options'] = '--client_encoding=' . $config->charset;
        }

        $parts = [];
        foreach ($parameters as $key => $value) {
            $parts[] = $key . '=' . $this->quoteConnectionValue($value);
        }

        return implode(' ', $parts);
    }

    /**
     * 按 libpq conninfo 规则引用连接参数。
     *
     * @param mixed $value
     * @return string
     */
    private function quoteConnectionValue($value): string
    {
        return '\'' . str_replace(
            ['\\', '\''],
            ['\\\\', '\\\''],
            (string) $value
        ) . '\'';
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
        return $version['server'];
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
     * @throws SQLException
     */
    public function query(string $query, $handle, int $op = Db::READ, ?string $action = null, ?string $table = null)
    {
        $this->prepareQuery($query, $handle, $action, $table);
        if (!@pg_send_query($handle, $query)) {
            throw new SQLException(@pg_last_error($handle));
        }

        $resource = false;
        $error = null;
        while (false !== ($result = pg_get_result($handle))) {
            $status = pg_result_status($result);
            if (
                null === $error
                && in_array(
                    $status,
                    [PGSQL_BAD_RESPONSE, PGSQL_NONFATAL_ERROR, PGSQL_FATAL_ERROR],
                    true
                )
            ) {
                $error = [
                    pg_result_error($result) ?: @pg_last_error($handle),
                    pg_result_error_field($result, PGSQL_DIAG_SQLSTATE) ?: 0
                ];
            }
            if (false !== $resource) {
                pg_free_result($resource);
            }
            $resource = $result;
        }

        if (null !== $error) {
            if (false !== $resource) {
                pg_free_result($resource);
            }
            throw new SQLException($error[0], $error[1]);
        }
        if (false === $resource) {
            throw new SQLException(@pg_last_error($handle));
        }

        return $resource;
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
     * @return \stdClass|null
     */
    public function fetchObject($resource): ?\stdClass
    {
        return pg_fetch_object($resource) ?: null;
    }

    /**
     * @param resource $resource
     * @return array
     */
    public function fetchAll($resource): array
    {
        return pg_fetch_all($resource, PGSQL_ASSOC) ?: [];
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
     * 引号转义函数
     *
     * @param mixed $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . str_replace('\'', '\'\'', $string) . '\'';
    }
}
