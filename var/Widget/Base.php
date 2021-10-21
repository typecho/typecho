<?php

namespace Widget;

use Typecho\Config;
use Typecho\Db;
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
     * init db
     */
    protected const INIT_DB = 0b0001;

    /**
     * init user widget
     */
    protected const INIT_USER = 0b0010;

    /**
     * init security widget
     */
    protected const INIT_SECURITY = 0b0100;

    /**
     * init options widget
     */
    protected const INIT_OPTIONS = 0b1000;

    /**
     * init all widgets
     */
    protected const INIT_ALL = 0b1111;

    /**
     * init none widget
     */
    protected const INIT_NONE = 0;

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
        $components = self::INIT_ALL;

        $this->initComponents($components);

        if ($components != self::INIT_NONE) {
            $this->db = Db::get();
        }

        if ($components & self::INIT_USER) {
            $this->user = User::alloc();
        }

        if ($components & self::INIT_OPTIONS) {
            $this->options = Options::alloc();
        }

        if ($components & self::INIT_SECURITY) {
            $this->security = Security::alloc();
        }

        $this->initParameter($this->parameter);
    }

    /**
     * @param int $components
     */
    protected function initComponents(int &$components)
    {
    }

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
    }
}
