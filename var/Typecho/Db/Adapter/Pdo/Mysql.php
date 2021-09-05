<?php

namespace Typecho\Db\Adapter\Pdo;

use Typecho\Config;
use Typecho\Db\Adapter\MysqlTrait;
use Typecho\Db\Adapter\Pdo;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Pdo_Mysql适配器
 *
 * @package Db
 */
class Mysql extends Pdo
{
    use MysqlTrait;

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return parent::isAvailable() && in_array('mysql', \PDO::getAvailableDrivers());
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
     * 初始化数据库
     *
     * @param Config $config 数据库配置
     * @access public
     * @return \PDO
     */
    public function init(Config $config): \PDO
    {
        $pdo = new \PDO(
            !empty($config->dsn)
                ? $config->dsn : "mysql:dbname={$config->database};host={$config->host};port={$config->port}",
            $config->user,
            $config->password
        );
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        if ($config->charset) {
            $pdo->exec("SET NAMES '{$config->charset}'");
        }

        return $pdo;
    }

    /**
     * 引号转义函数
     *
     * @param mixed $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . str_replace(['\'', '\\'], ['\'\'', '\\\\'], $string) . '\'';
    }
}
