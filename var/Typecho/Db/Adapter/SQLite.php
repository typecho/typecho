<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: Mysql.php 103 2008-04-09 16:22:43Z magike.net $
 */

/** 数据库适配器接口 */
require_once 'Typecho/Db/Adapter.php';

/**
 * 数据库SQLite适配器
 *
 * @package Db
 */
class Typecho_Db_Adapter_SQLite implements Typecho_Db_Adapter
{
    /**
     * 数据库标示
     *
     * @access private
     * @var resource
     */
    private $_dbHandle;

    /**
     * 判断适配器是否可用
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable()
    {
        return function_exists('sqlite_open');
    }

    /**
     * 过滤字段名
     *
     * @access private
     * @param mixed $result
     * @return array
     */
    private function filterColumnName($result)
    {
        /** 如果结果为空,直接返回 */
        if (!$result) {
            return $result;
        }

        $tResult = array();

        /** 遍历数组 */
        foreach ($result as $key => $val) {
            /** 按点分隔 */
            if (false !== ($pos = strpos($key, '.'))) {
                $key = substr($key, $pos + 1);
            }

            /** 按引号分割 */
            if (false === ($pos = strpos($key, '"'))) {
                $tResult[$key] = $val;
            } else {
                $tResult[substr($key, $pos + 1, -1)] = $val;
            }
        }

        return $tResult;
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
        if ($this->_dbHandle = sqlite_open($config->file, 0666, $error)) {
            return $this->_dbHandle;
        }

        /** 数据库异常 */
        require_once 'Typecho/Db/Adapter/Exception.php';
        throw new Typecho_Db_Adapter_Exception($error);
    }

    /**
     * 执行数据库查询
     *
     * @param string $sql 查询字符串
     * @param mixed $handle 连接对象
     * @param boolean $op 查询读写开关
     * @throws Typecho_Db_Exception
     * @return resource
     */
    public function query($query, $handle, $op = Typecho_Db::READ, $action = NULL)
    {
        if ($resource = @sqlite_query($query instanceof Typecho_Db_Query ? $query->__toString() : $query, $handle)) {
            return $resource;
        }

        /** 数据库异常 */
        require_once 'Typecho/Db/Query/Exception.php';
        $errorCode = sqlite_last_error($this->_dbHandle);
        throw new Typecho_Db_Query_Exception(sqlite_error_string($errorCode), $errorCode);
    }

    /**
     * 将数据查询的其中一行作为数组取出,其中字段名对应数组键值
     *
     * @param resource $resource 查询返回资源标识
     * @return array
     */
    public function fetch($resource)
    {
        return $this->filterColumnName(sqlite_fetch_array($resource, SQLITE_ASSOC));
    }

    /**
     * 将数据查询的其中一行作为对象取出,其中字段名对应对象属性
     *
     * @param resource $resource 查询的资源数据
     * @return object
     */
    public function fetchObject($resource)
    {
        return (object) $this->filterColumnName(sqlite_fetch_array($resource, SQLITE_ASSOC));
    }

    /**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string)
    {
        return '\'' . str_replace('\'', '\'\'', $string) . '\'';
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
        return '"' . $string . '"';
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
        $sql['where'] . $sql['group'] . $sql['order'] . $sql['limit'] . $sql['offset'];
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
        return sqlite_changes($handle);
    }

    /**
     * 取出最后一次插入返回的主键值
     *
     * @param resource $resource 查询的资源数据
     * @param mixed $handle 连接对象
     * @return integer
     */
    public function lastInsertId($resource, $handle)
    {
        return sqlite_last_insert_rowid($handle);
    }
}
