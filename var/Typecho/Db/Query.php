<?php

namespace Typecho\Db;

use Typecho\Db;

/**
 * Typecho数据库查询语句构建类
 * 使用方法:
 * $query = new Query();    //或者使用DB积累的sql方法返回实例化对象
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
class Query
{
    /** 数据库关键字 */
    private const KEYWORDS = '*PRIMARY|AND|OR|LIKE|ILIKE|BINARY|BY|DISTINCT|AS|IN|IS|NULL';

    /**
     * 默认字段
     *
     * @var array
     * @access private
     */
    private static $default = [
        'action' => null,
        'table'  => null,
        'fields' => '*',
        'join'   => [],
        'where'  => null,
        'limit'  => null,
        'offset' => null,
        'order'  => null,
        'group'  => null,
        'having' => null,
        'rows'   => [],
    ];

    /**
     * 数据库适配器
     *
     * @var Adapter
     */
    private $adapter;

    /**
     * 查询语句预结构,由数组构成,方便组合为SQL查询字符串
     *
     * @var array
     */
    private $sqlPreBuild;

    /**
     * 前缀
     *
     * @access private
     * @var string
     */
    private $prefix;

    /**
     * @var array
     */
    private $params = [];

    /**
     * 构造函数,引用数据库适配器作为内部数据
     *
     * @param Adapter $adapter 数据库适配器
     * @param string $prefix 前缀
     */
    public function __construct(Adapter $adapter, string $prefix)
    {
        $this->adapter = &$adapter;
        $this->prefix = $prefix;

        $this->sqlPreBuild = self::$default;
    }

    /**
     * set default params
     *
     * @param array $default
     */
    public static function setDefault(array $default)
    {
        self::$default = array_merge(self::$default, $default);
    }

    /**
     * 获取参数
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * 获取查询字串属性值
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return string
     */
    public function getAttribute(string $attributeName): ?string
    {
        return $this->sqlPreBuild[$attributeName] ?? null;
    }

    /**
     * 清除查询字串属性值
     *
     * @access public
     * @param string $attributeName 属性名称
     * @return Query
     */
    public function cleanAttribute(string $attributeName): Query
    {
        if (isset($this->sqlPreBuild[$attributeName])) {
            $this->sqlPreBuild[$attributeName] = self::$default[$attributeName];
        }
        return $this;
    }

    /**
     * 连接表
     *
     * @param string $table 需要连接的表
     * @param string $condition 连接条件
     * @param string $op 连接方法(LEFT, RIGHT, INNER)
     * @return Query
     */
    public function join(string $table, string $condition, string $op = Db::INNER_JOIN): Query
    {
        $this->sqlPreBuild['join'][] = [$this->filterPrefix($table), $this->filterColumn($condition), $op];
        return $this;
    }

    /**
     * 过滤表前缀,表前缀由table.构成
     *
     * @param string $string 需要解析的字符串
     * @return string
     */
    private function filterPrefix(string $string): string
    {
        return (0 === strpos($string, 'table.')) ? substr_replace($string, $this->prefix, 0, 6) : $string;
    }

    /**
     * 过滤数组键值
     *
     * @access private
     * @param string $str 待处理字段值
     * @return string
     */
    private function filterColumn(string $str): string
    {
        $str = $str . ' 0';
        $length = strlen($str);
        $lastIsAlnum = false;
        $result = '';
        $word = '';
        $split = '';
        $quotes = 0;

        for ($i = 0; $i < $length; $i++) {
            $cha = $str[$i];

            if (ctype_alnum($cha) || false !== strpos('_*', $cha)) {
                if (!$lastIsAlnum) {
                    if (
                        $quotes > 0 && !ctype_digit($word) && '.' != $split
                        && false === strpos(self::KEYWORDS, strtoupper($word))
                    ) {
                        $word = $this->adapter->quoteColumn($word);
                    } elseif ('.' == $split && 'table' == $word) {
                        $word = $this->prefix;
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
                        } elseif ('(' == $cha) {
                            $quotes = - 1;
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
     * AND条件查询语句
     *
     * @param ...$args
     * @return $this
     */
    public function where(...$args): Query
    {
        [$condition] = $args;
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->sqlPreBuild['where']) ? ' WHERE ' : ' AND';

        if (count($args) <= 1) {
            $this->sqlPreBuild['where'] .= $operator . ' (' . $condition . ')';
        } else {
            array_shift($args);
            $this->sqlPreBuild['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * 转义参数
     *
     * @param array $values
     * @access protected
     * @return array
     */
    protected function quoteValues(array $values): array
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = '(' . implode(',', array_map([$this, 'quoteValue'], $value)) . ')';
            } else {
                $value = $this->quoteValue($value);
            }
        }

        return $values;
    }

    /**
     * 延迟转义
     *
     * @param $value
     * @return string
     */
    public function quoteValue($value): string
    {
        $this->params[] = $value;
        return '#param:' . (count($this->params) - 1) . '#';
    }

    /**
     * OR条件查询语句
     *
     * @param ...$args
     * @return Query
     */
    public function orWhere(...$args): Query
    {
        [$condition] = $args;
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->sqlPreBuild['where']) ? ' WHERE ' : ' OR';

        if (func_num_args() <= 1) {
            $this->sqlPreBuild['where'] .= $operator . ' (' . $condition . ')';
        } else {
            array_shift($args);
            $this->sqlPreBuild['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * 查询行数限制
     *
     * @param mixed $limit 需要查询的行数
     * @return Query
     */
    public function limit($limit): Query
    {
        $this->sqlPreBuild['limit'] = intval($limit);
        return $this;
    }

    /**
     * 查询行数偏移量
     *
     * @param mixed $offset 需要偏移的行数
     * @return Query
     */
    public function offset($offset): Query
    {
        $this->sqlPreBuild['offset'] = intval($offset);
        return $this;
    }

    /**
     * 分页查询
     *
     * @param mixed $page 页数
     * @param mixed $pageSize 每页行数
     * @return Query
     */
    public function page($page, $pageSize): Query
    {
        $pageSize = intval($pageSize);
        $this->sqlPreBuild['limit'] = $pageSize;
        $this->sqlPreBuild['offset'] = (max(intval($page), 1) - 1) * $pageSize;
        return $this;
    }

    /**
     * 指定需要写入的栏目及其值
     *
     * @param array $rows
     * @return Query
     */
    public function rows(array $rows): Query
    {
        foreach ($rows as $key => $row) {
            $this->sqlPreBuild['rows'][$this->filterColumn($key)]
                = is_null($row) ? 'NULL' : $this->adapter->quoteValue($row);
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
     * @return Query
     */
    public function expression(string $key, $value, bool $escape = true): Query
    {
        $this->sqlPreBuild['rows'][$this->filterColumn($key)] = $escape ? $this->filterColumn($value) : $value;
        return $this;
    }

    /**
     * 排序顺序(ORDER BY)
     *
     * @param string $orderBy 排序的索引
     * @param string $sort 排序的方式(ASC, DESC)
     * @return Query
     */
    public function order(string $orderBy, string $sort = Db::SORT_ASC): Query
    {
        if (empty($this->sqlPreBuild['order'])) {
            $this->sqlPreBuild['order'] = ' ORDER BY ';
        } else {
            $this->sqlPreBuild['order'] .= ', ';
        }

        $this->sqlPreBuild['order'] .= $this->filterColumn($orderBy) . (empty($sort) ? null : ' ' . $sort);
        return $this;
    }

    /**
     * 集合聚集(GROUP BY)
     *
     * @param string $key 聚集的键值
     * @return Query
     */
    public function group(string $key): Query
    {
        $this->sqlPreBuild['group'] = ' GROUP BY ' . $this->filterColumn($key);
        return $this;
    }

    /**
     * @param string $condition
     * @param ...$args
     * @return $this
     */
    public function having(string $condition, ...$args): Query
    {
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->sqlPreBuild['having']) ? ' HAVING ' : ' AND';

        if (count($args) == 0) {
            $this->sqlPreBuild['having'] .= $operator . ' (' . $condition . ')';
        } else {
            $this->sqlPreBuild['having'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * 选择查询字段
     *
     * @param mixed ...$args 查询字段
     * @return $this
     */
    public function select(...$args): Query
    {
        $this->sqlPreBuild['action'] = Db::SELECT;

        $this->sqlPreBuild['fields'] = $this->getColumnFromParameters($args);
        return $this;
    }

    /**
     * 从参数中合成查询字段
     *
     * @access private
     * @param array $parameters
     * @return string
     */
    private function getColumnFromParameters(array $parameters): string
    {
        $fields = [];

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
     * 查询记录操作(SELECT)
     *
     * @param string $table 查询的表
     * @return Query
     */
    public function from(string $table): Query
    {
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 更新记录操作(UPDATE)
     *
     * @param string $table 需要更新记录的表
     * @return Query
     */
    public function update(string $table): Query
    {
        $this->sqlPreBuild['action'] = Db::UPDATE;
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 删除记录操作(DELETE)
     *
     * @param string $table 需要删除记录的表
     * @return Query
     */
    public function delete(string $table): Query
    {
        $this->sqlPreBuild['action'] = Db::DELETE;
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * 插入记录操作(INSERT)
     *
     * @param string $table 需要插入记录的表
     * @return Query
     */
    public function insert(string $table): Query
    {
        $this->sqlPreBuild['action'] = Db::INSERT;
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * @param string $query
     * @return string
     */
    public function prepare(string $query): string
    {
        $params = $this->params;
        $adapter = $this->adapter;

        return preg_replace_callback("/#param:([0-9]+)#/", function ($matches) use ($params, $adapter) {
            if (array_key_exists($matches[1], $params)) {
                return is_null($params[$matches[1]]) ? 'NULL' : $adapter->quoteValue($params[$matches[1]]);
            } else {
                return $matches[0];
            }
        }, $query);
    }

    /**
     * 构造最终查询语句
     *
     * @return string
     */
    public function __toString()
    {
        switch ($this->sqlPreBuild['action']) {
            case Db::SELECT:
                return $this->adapter->parseSelect($this->sqlPreBuild);
            case Db::INSERT:
                return 'INSERT INTO '
                    . $this->sqlPreBuild['table']
                    . '(' . implode(' , ', array_keys($this->sqlPreBuild['rows'])) . ')'
                    . ' VALUES '
                    . '(' . implode(' , ', array_values($this->sqlPreBuild['rows'])) . ')'
                    . $this->sqlPreBuild['limit'];
            case Db::DELETE:
                return 'DELETE FROM '
                    . $this->sqlPreBuild['table']
                    . $this->sqlPreBuild['where'];
            case Db::UPDATE:
                $columns = [];
                if (isset($this->sqlPreBuild['rows'])) {
                    foreach ($this->sqlPreBuild['rows'] as $key => $val) {
                        $columns[] = "$key = $val";
                    }
                }

                return 'UPDATE '
                    . $this->sqlPreBuild['table']
                    . ' SET ' . implode(' , ', $columns)
                    . $this->sqlPreBuild['where'];
            default:
                return null;
        }
    }
}
