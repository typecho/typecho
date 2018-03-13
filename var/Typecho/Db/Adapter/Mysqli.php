<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: Mysqli.php 103 2008-04-09 16:22:43Z magike.net $
 */

/**
 * 数据库Mysqli适配器
 *
 * @package Db
 */
class Typecho_Db_Adapter_Mysqli implements Typecho_Db_Adapter
{
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
    public static function isAvailable()
    {
        return class_exists('MySQLi');
    }

    /**
     * 数据库连接函数
     *
     * @param Typecho_Config $config 数据库配置
     * @throws Typecho_Db_Exception
     * @return resource
     */
    public function connect(Typecho_Config $config)
    {

        if ($this->_dbLink = @new MySQLi($config->host, $config->user, $config->password, $config->database, (empty($config->port) ? '' : $config->port))) {
            if ($config->charset) {
                $this->_dbLink->query("SET NAMES '{$config->charset}'");
            }
            return $this->_dbLink;
        }

        /** 数据库异常 */
        throw new Typecho_Db_Adapter_Exception(@$this->_dbLink->error);
    }

    /**
     * 获取数据库版本 
     * 
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle)
    {
        return 'mysqli:mysql ' . $this->_dbLink->server_version;
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
     * 执行数据库查询
     *
     * @param string $query 数据库查询SQL字符串
     * @param mixed $handle 连接对象
     * @param integer $op 数据库读写状态
     * @param string $action 数据库动作
     * @param string $table 数据表
     * @throws Typecho_Db_Exception
     * @return resource
     */
    public function query($query, $handle, $op = Typecho_Db::READ, $action = NULL, $table = NULL)
    {
        if ($resource = @$this->_dbLink->query($query)) {
            return $resource;
        }

        /** 数据库异常 */
        throw new Typecho_Db_Query_Exception($this->_dbLink->error, $this->_dbLink->errno);
    }

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param resource $resource 查询返回资源标识
     * @return array
     */
    public function fetch($resource)
    {
        return $resource->fetch_assoc();
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param resource $resource 查询的资源数据
     * @return object
     */
    public function fetchObject($resource)
    {
        return $resource->fetch_object();
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
     * 对象引号过滤
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn($string)
    {
        return $this->_dbLink->real_escape_string($string);
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

    /**
     * 取出最后一次查询影响的行数
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function affectedRows($resource, $handle)
    {
        return $this->_dbLink->affected_rows;
    }

    /*y
     * 取出最后一次插入返回的主键值
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle)
    {
        return $this->_dbLink->insert_id;
    }
}
