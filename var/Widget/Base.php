<?php

namespace Widget;

use Typecho\Db;
use Typecho\Plugin;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 纯数据抽象组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
abstract class Base extends Widget
{
    /**
     * 全局选项
     *
     * @var Options
     */
    protected $options;

    /**
     * 用户对象
     *
     * @var User
     */
    protected $user;

    /**
     * 安全模块
     *
     * @var Security
     */
    protected $security;

    /**
     * 数据库对象
     *
     * @var Db
     */
    protected $db;

    /**
     * init method
     */
    protected function init()
    {
        $this->initWith('db', 'options', 'user', 'security');
    }

    /**
     * init base component
     *
     * @param string ...$components
     */
    protected function initWith(string ...$components)
    {
        if (in_array('db', $components)) {
            $this->db = Db::get();
        }

        if (in_array('options', $components)) {
            $this->options = Options::alloc();
        }

        if (in_array('user', $components)) {
            $this->user = User::alloc();
        }

        if (in_array('security', $components)) {
            $this->security = Security::alloc();
        }
    }
}
