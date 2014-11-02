<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: DbQuery.php 97 2008-04-04 04:39:54Z magike.net $
 */

/**
 * Typecho数据库查询语句构建类
 * 使用方法:
 * $query = new Typecho_Db_Query();	//或者使用DB积累的sql方法返回实例化对象
 * $query->select('posts', 'post_id, post_title')
 * ->where('post_id = %d', 1)
 * ->limit(1);
 * echo $query;
 * 打印的结果将是
 * SELECT post_id, post_title FROM posts WHERE 1=1 AND post_id = 1 LIMIT 1
 *
 *
 * @package Db
 */
class Typecho_Db_Query
{
    /** 数据库关键字 */
    const KEYWORDS = '*PRIMARY|AND|OR|LIKE|BINARY|BY|DISTINCT|AS|IN|IS|NULL';

    /**
     * 默认字段 
     * 
     * @var array
     * @access private
     */
    private static $_default = array(
        'action' => NULL,
        'table'  => NULL,
        'fields' => '*',
        'join'   => array(),
        'where'  => NULL,
        'limit'  => NULL,
        'offset' => NULL,
        'order'  => NULL,
        'group'  => NULL,
        'having'  => NULL,
        'rows'   => array(),
    );

    /**
     * 数据库适配器
     *
     * @var Typecho_Db_Adapter
     */
    private $_adapter;

    /**
     * 查询语句预结构,由数组构成,方便组合为SQL查询字符串
     *
     * @var array
     */
    private $_sqlPreBuild;

    /**
     * 前缀
     *
     * @access private
     * @var string
     */
    private $_prefix;

    /**
     * 构造函数,引用数据库适配器作为内部数据
     *
     * @param Typecho_Db_Adapter $adapter 数据库适配器
     * @param string $prefix 前缀
     * @return void
     */
    public function __construct(Typecho_Db_Adapter $adapter, $prefix)
    {
        $this->_adapter = &$adapter;
        $this->_prefix = $prefix;

        $this->_sqlPreBuild = self::$_default;
    }

    /**
     * 过滤表前缀,表前缀由table.构成
     *
     * @param string $string 需要解析的字符串
     * @return string
     */
    private function filterPrefix($string)
    {
        return (0 === strpos($string, 'table.')) ? substr_replace($string, $this->_prefix, 0, 6) : $string;
    }

    /**
     * 过滤数组键值
     *
     * @access private
     * @param string $str 待处理字段值
     * @return string
     */
    private function filterColumn($str)
    {
        $str = $str . ' 0';
        $length = strlen($str);
        $lastIsAlnum = false;
        $result = '';
        $word = '';
        $split = '';
        $quotes = 0;

        for ($i = 0; $i < $length; $i ++) {
            $cha = $str[$i];

            if (ctype_alnum($cha) || false !== strpos('_*', $cha)) {
                if (!$lastIsAlnum) {
                    if ($quotes > 0 && !ctype_digit($word) && '.' != $split
                    && false === strpos(self::KEYWORDS, strtoupper($word))) {
                        $word = $this->_adapter->quoteColumn($word);
                    } else if ('.' == $split && 'table' == $word) {
                        $word = $this->_prefix;
                        $split = '';
                    }

                    $result .= $word . $split;
                    $word = '';
                    $quotes = 0;
                }

                $word .= $cha;
                $lastIsAlnum = true;
            } else {

                if ($lastIsAlnum) {

                    if (0 == $quotes) {
                        if (false !== strpos(' ,)=<>.+-*/', $cha)) {
                            $quotes = 1;
                        } else if ('(' == $cha) {
                            $quotes = -1;
                        }
                    }

                    $split = '';
                }

                $split .= $cha;
                $lastIsAlnum = false;
            }

        }

        return $result;
    }

    /**
     * 从参数中合成查询字段
     *
     * @access private
     * @param array $parameters
     * @return string
     */
    private function getColumnFromParameters(array $parameters)
    {
        $fields = array();

        foreach ($parameters as $value) {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $fields[] = $key . ' AS ' . $val;
                }
            } else {
                 $fields[] = $value;
            }
        }

        return $this->filterColumn(implode(' , ', $fields));
    }

    /**
     * 转义参数 
     * 
     * @param array $values 
     * @access protected
     * @return array
     */
    protected function quoteValues(array $values)
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = '(' . implode(',', array_map(array($this->_adapter, 'quoteValue'), $value)) . ')';
            } else {
                $value = $this->_adapter->quoteValue($value);
            }
        }

        return $values;
    }

    /**
     * set default params
     *
     * @param array $default
     */
    public static function setDefault(array $default)
    {
        self::$_default = array_merge(self::$_default, $default);
    }

    /**
     * 获取查询字串属性值
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return string
     */
    public function getAttribute($attributeName)
    {
        return isset($this->_sqlPreBuild[$attributeName]) ? $this->_sqlPreBuild[$attributeName] : NULL;
    }

    /**
     * 清除查询字串属性值
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return Typecho_Db_Query
     */
    public function cleanAttribute($attributeName)
    {
        if (isset($this->_sqlPreBuild[$attributeName])) {
            $this->_sqlPreBuild[$attributeName] = self::$_default[$attributeName];
        }
        return $this;
    }

    /**
     * 连接表
     *
     * @param string $table 需要连接的表
     * @param string $condition 连接条件
     * @param string $op 连接方法(LEFT, RIGHT, INNER)
     * @return Typecho_Db_Query
     */
    public function join($table, $condition, $op = Typecho_Db::INNER_JOIN)
    {
        $this->_sqlPreBuild['join'][] = array($this->filterPrefix($table), $this->filterColumn($condition), $op);
        return $this;
    }

    /**
     * AND条件查询语句
     *
     * @param string $condition 查询条件
     * @param mixed $param 条件值
     * @return Typecho_Db_Query
     */
    public function where()
    {
        $condition = func_get_arg(0);
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->_sqlPreBuild['where']) ? ' WHERE ' : ' AND';

        if (func_num_args() <= 1) {
            $this->_sqlPreBuild['where'] .= $operator . ' (' . $condition . ')';
        } else {
            $args = func_get_args();
            array_shift($args);
            $this->_sqlPreBuild['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * OR条件查询语句
     *
     * @param string $condition 查询条件
     * @param mixed $param 条件值
     * @return Typecho_Db_Query
     */
    public function orWhere()
    {
        $condition = func_get_arg(0);
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->_sqlPreBuild['where']) ? ' WHERE ' : ' OR';

        if (func_num_args() <= 1) {
            $this->_sqlPreBuild['where'] .= $operator . ' (' . $condition . ')';
        } else {
            $args = func_get_args();
            array_shift($args);
            $this->_sqlPreBuild['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * 查询行数限制
     *
     * @param integer $limit 需要查询的行数
     * @return Typecho_Db_Query
     */
    public function limit($limit)
    {
        $this->_sqlPreBuild['limit'] = intval($limit);
        return $this;
    }

    /**
     * 查询行数偏移量
     *
     * @param integer $offset 需要偏移的行数
     * @return Typecho_Db_Query
     */
    public function offset($offset)
    {
        $this->_sqlPreBuild['offset'] = intval($offset);
        return $this;
    }

    /**
     * 分页查询
     *
     * @param integer $page 页数
     * @param integer $pageSize 每页行数
     * @return Typecho_Db_Query
     */
    public function page($page, $pageSize)
    {
        $pageSize = intval($pageSize);
        $this->_sqlPreBuild['limit'] = $pageSize;
        $this->_sqlPreBuild['offset'] = (max(intval($page), 1) - 1) * $pageSize;
        return $this;
    }

    /**
     * 指定需要写入的栏目及其值
     *
     * @param array $rows
     * @return Typecho_Db_Query
     */
    public function rows(array $rows)
    {
        foreach ($rows as $key => $row) {
            $this->_sqlPreBuild['rows'][$this->filterColumn($key)] = is_null($row) ? 'NULL' : $this->_adapter->quoteValue($row);
        }
        return $this;
    }

    /**
     * 指定需要写入栏目及其值
     * 单行且不会转义引号
     *
     * @param string $key 栏目名称
     * @param mixed $value 指定的值
     * @param bool $escape 是否转义
     * @return Typecho_Db_Query
     */
    public function expression($key, $value, $escape = true)
    {
        $this->_sqlPreBuild['rows'][$this->filterColumn($key)] = $escape ? $this->filterColumn($value) : $value;
        return $this;
    }

    /**
     * 排序顺序(ORDER BY)
     *
     * @param string $orderby 排序的索引
     * @param string $sort 排序的方式(ASC, DESC)
     * @return Typecho_Db_Query
     */
    public function order($orderby, $sort = Typecho_Db::SORT_ASC)
    {
        $this->_sqlPreBuild['order'] = ' ORDER BY ' . $this->filterColumn($orderby) . (empty($sort) ? NULL : ' ' . $sort);
        return $this;
    }

    /**
     * 集合聚集(GROUP BY)
     *
     * @param string $key 聚集的键值
     * @return Typecho_Db_Query
     */
    public function group($key)
    {
        $this->_sqlPreBuild['group'] = ' GROUP BY ' . $this->filterColumn($key);
        return $this;
    }

    /**
     * HAVING (HAVING)
     *
     * @return Typecho_Db_Query
     */
    public function having()
    {
        $condition = func_get_arg(0);
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->_sqlPreBuild['having']) ? ' HAVING ' : ' AND';

        if (func_num_args() <= 1) {
            $this->_sqlPreBuild['having'] .= $operator . ' (' . $condition . ')';
        } else {
            $args = func_get_args();
            array_shift($args);
            $this->_sqlPreBuild['having'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * 选择查询字段
     *
     * @access public
     * @param mixed $field 查询字段
     * @return Typecho_Db_Query
     */
    public function select($field = '*')
    {
        $this->_sqlPreBuild['action'] = Typecho_Db::SELECT;
        $args = func_get_args();

        $this->_sqlPreBuild['fields'] = $this->getColumnFromParameters($args);
        return $this;
    }

    /**
     * 查询记录操作(SELECT)
     *
     * @param string $table 查询的表
     * @return Typecho_Db_Query
     */
    public function from($table)
    {
        $this->_sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 更新记录操作(UPDATE)
     *
     * @param string $table 需要更新记录的表
     * @return Typecho_Db_Query
     */
    public function update($table)
    {
        $this->_sqlPreBuild['action'] = Typecho_Db::UPDATE;
        $this->_sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 删除记录操作(DELETE)
     *
     * @param string $table 需要删除记录的表
     * @return Typecho_Db_Query
     */
    public function delete($table)
    {
        $this->_sqlPreBuild['action'] = Typecho_Db::DELETE;
        $this->_sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 插入记录操作(INSERT)
     *
     * @param string $table 需要插入记录的表
     * @return Typecho_Db_Query
     */
    public function insert($table)
    {
        $this->_sqlPreBuild['action'] = Typecho_Db::INSERT;
        $this->_sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 构造最终查询语句
     *
     * @return string
     */
    public function __toString()
    {
        switch ($this->_sqlPreBuild['action']) {
            case Typecho_Db::SELECT:
                return $this->_adapter->parseSelect($this->_sqlPreBuild);
            case Typecho_Db::INSERT:
                return 'INSERT INTO '
                . $this->_sqlPreBuild['table']
                . '(' . implode(' , ', array_keys($this->_sqlPreBuild['rows'])) . ')'
                . ' VALUES '
                . '(' . implode(' , ', array_values($this->_sqlPreBuild['rows'])) . ')'
                . $this->_sqlPreBuild['limit'];
            case Typecho_Db::DELETE:
                return 'DELETE FROM '
                . $this->_sqlPreBuild['table']
                . $this->_sqlPreBuild['where'];
            case Typecho_Db::UPDATE:
                $columns = array();
                if (isset($this->_sqlPreBuild['rows'])) {
                    foreach ($this->_sqlPreBuild['rows'] as $key => $val) {
                        $columns[] = "$key = $val";
                    }
                }

                return 'UPDATE '
                . $this->_sqlPreBuild['table']
                . ' SET ' . implode(' , ', $columns)
                . $this->_sqlPreBuild['where'];
            default:
                return NULL;
        }
    }
}
