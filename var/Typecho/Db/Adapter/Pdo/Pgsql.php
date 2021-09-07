<?php

namespace Typecho\Db\Adapter\Pdo;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter\SQLException;
use Typecho\Db\Adapter\Pdo;
use Typecho\Db\Adapter\PgsqlTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Pdo_Pgsql适配器
 *
 * @package Db
 */
class Pgsql extends Pdo
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
        return parent::isAvailable() && in_array('pgsql', \PDO::getAvailableDrivers());
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
        $this->prepareQuery($query, $handle, $action, $table);
        return parent::query($query, $handle, $op, $action, $table);
    }

    /**
     * 初始化数据库
     *
     * @param Config $config 数据库配置
     * @return \PDO
     */
    public function init(Config $config): \PDO
    {
        $pdo = new \PDO(
            "pgsql:dbname={$config->database};host={$config->host};port={$config->port}",
            $config->user,
            $config->password
        );

        if ($config->charset) {
            $pdo->exec("SET NAMES '{$config->charset}'");
        }

        return $pdo;
    }
}
