<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Config;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Plugin;
use Typecho\Router;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 用户抽象类
 *
 * @property int $uid
 * @property string $name
 * @property string $password
 * @property string $mail
 * @property string $url
 * @property string $screenName
 * @property int $created
 * @property int $activated
 * @property int $logged
 * @property string $group
 * @property string $authCode
 * @property-read Config $personalOptions
 * @property-read string $permalink
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 */
class Users extends Base implements QueryInterface, RowFilterInterface
{
    /**
     * 将每行的值压入堆栈
     *
     * @param array $value 每行的值
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * 通用过滤器
     *
     * @param array $row 需要过滤的行数据
     * @return array
     */
    public function filter(array $row): array
    {
        //生成静态链接
        $routeExists = (null != Router::get('author'));

        $row['permalink'] = $routeExists ? Router::url('author', $row, $this->options->index) : '#';

        /** 生成聚合链接 */
        /** RSS 2.0 */
        $row['feedUrl'] = $routeExists ? Router::url('author', $row, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $row['feedRssUrl'] = $routeExists ? Router::url('author', $row, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $row['feedAtomUrl'] = $routeExists ? Router::url('author', $row, $this->options->feedAtomUrl) : '#';

        return Users::pluginHandle()->call('filter', $row, $this);
    }

    /**
     * 查询方法
     *
     * @param mixed $fields
     * @return Query
     */
    public function select(...$fields): Query
    {
        return $this->db->select(...$fields)->from('table.users');
    }

    /**
     * 获得所有记录数
     *
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(uid)' => 'num'])->from('table.users'))->num;
    }

    /**
     * 增加记录方法
     *
     * @param array $rows 字段对应值
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        return $this->db->query($this->db->insert('table.users')->rows($rows));
    }

    /**
     * 更新记录方法
     *
     * @param array $rows 字段对应值
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        return $this->db->query($condition->update('table.users')->rows($rows));
    }

    /**
     * 删除记录方法
     *
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.users'));
    }

    /**
     * 调用gravatar输出用户头像
     *
     * @param integer $size 头像尺寸
     * @param string $rating 头像评级
     * @param string|null $default 默认输出头像
     * @param string|null $class 默认css class
     */
    public function gravatar(int $size = 40, string $rating = 'X', ?string $default = null, ?string $class = null)
    {
        $url = Common::gravatarUrl($this->mail, $size, $rating, $default, $this->request->isSecure());
        echo '<img' . (empty($class) ? '' : ' class="' . $class . '"') . ' src="' . $url . '" alt="' .
            $this->screenName . '" width="' . $size . '" height="' . $size . '" />';
    }

    /**
     * personalOptions
     *
     * @return Config
     * @throws Exception
     */
    protected function ___personalOptions(): Config
    {
        $rows = $this->db->fetchAll($this->db->select()
            ->from('table.options')->where('user = ?', $this->uid));
        $options = [];
        foreach ($rows as $row) {
            $options[$row['name']] = $row['value'];
        }

        return new Config($options);
    }
}
