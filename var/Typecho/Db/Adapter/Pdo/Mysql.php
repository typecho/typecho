<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: Mysql.php 89 2008-03-31 00:10:57Z magike.net $
 */

/**
 * 数据库Pdo_Mysql适配器
 *
 * @package Db
 */
class Typecho_Db_Adapter_Pdo_Mysql extends Typecho_Db_Adapter_Pdo
{
    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable()
    {
        return parent::isAvailable() && in_array('mysql', PDO::getAvailableDrivers());
    }

    /**
     * 清空数据表
     *
     * @param string $table
     * @param mixed $handle 连接对象
     * @return mixed|void
     * @throws Typecho_Db_Exception
     */
    public function truncate($table, $handle)
    {
        $this->query('TRUNCATE TABLE ' . $this->quoteColumn($table), $handle);
    }

    /**
     * 初始化数据库
     *
     * @param Typecho_Config $config 数据库配置
     * @access public
     * @return PDO
     */
    public function init(Typecho_Config $config)
    {
        $pdo = new PDO(!empty($config->dsn) ? $config->dsn :
            "mysql:dbname={$config->database};host={$config->host};port={$config->port}", $config->user, $config->password);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $pdo->exec("SET NAMES '{$config->charset}'");
        return $pdo;
    }

    /**
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn($string)
    {
        return '`' . $string . '`';
    }

    /**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string)
    {
        return '\'' . str_replace(array('\'', '\\'), array('\'\'', '\\\\'), $string) . '\'';
    }

    /**
     * 合成查询语句
     *
     * @access public
     * @param array $sql 查询对象词法数组
     * @return string
     */
    public function parseSelect(array $sql)
    {
        if (!empty($sql['join'])) {
            foreach ($sql['join'] as $val) {
                list($table, $condition, $op) = $val;
                $sql['table'] = "{$sql['table']} {$op} JOIN {$table} ON {$condition}";
            }
        }

        $sql['limit'] = (0 == strlen($sql['limit'])) ? NULL : ' LIMIT ' . $sql['limit'];
        $sql['offset'] = (0 == strlen($sql['offset'])) ? NULL : ' OFFSET ' . $sql['offset'];

        return 'SELECT ' . $sql['fields'] . ' FROM ' . $sql['table'] .
        $sql['where'] . $sql['group'] . $sql['having'] . $sql['order'] . $sql['limit'] . $sql['offset'];
    }
}
