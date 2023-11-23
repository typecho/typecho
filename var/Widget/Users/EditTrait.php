<?php

namespace Widget\Users;

use Typecho\Db\Exception;

/**
 * 编辑用户组件
 */
trait EditTrait
{
    /**
     * 判断用户名称是否存在
     *
     * @param string $name 用户名称
     * @return boolean
     * @throws Exception
     */
    public function nameExists(string $name): bool
    {
        $select = $this->db->select()
            ->from('table.users')
            ->where('name = ?', $name)
            ->limit(1);

        if ($this->request->is('uid')) {
            $select->where('uid <> ?', $this->request->get('uid'));
        }

        $user = $this->db->fetchRow($select);
        return !$user;
    }

    /**
     * 判断电子邮件是否存在
     *
     * @param string $mail 电子邮件
     * @return boolean
     * @throws Exception
     */
    public function mailExists(string $mail): bool
    {
        $select = $this->db->select()
            ->from('table.users')
            ->where('mail = ?', $mail)
            ->limit(1);

        if ($this->request->is('uid')) {
            $select->where('uid <> ?', $this->request->get('uid'));
        }

        $user = $this->db->fetchRow($select);
        return !$user;
    }

    /**
     * 判断用户昵称是否存在
     *
     * @param string $screenName 昵称
     * @return boolean
     * @throws Exception
     */
    public function screenNameExists(string $screenName): bool
    {
        $select = $this->db->select()
            ->from('table.users')
            ->where('screenName = ?', $screenName)
            ->limit(1);

        if ($this->request->is('uid')) {
            $select->where('uid <> ?', $this->request->get('uid'));
        }

        $user = $this->db->fetchRow($select);
        return !$user;
    }

    /**
     * 获取页面偏移
     *
     * @param string $column 字段名
     * @param integer $offset 偏移值
     * @param string|null $group 用户组
     * @param integer $pageSize 分页值
     * @return integer
     * @throws Exception
     */
    protected function getPageOffset(string $column, int $offset, ?string $group = null, int $pageSize = 20): int
    {
        $select = $this->db->select(['COUNT(uid)' => 'num'])->from('table.users')
            ->where("table.users.{$column} > {$offset}");

        if (!empty($group)) {
            $select->where('table.users.group = ?', $group);
        }

        $count = $this->db->fetchObject($select)->num + 1;
        return ceil($count / $pageSize);
    }
}
