<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 当前登录用户
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_User extends Typecho_Widget
{
    /**
     * 用户
     *
     * @access private
     * @var array
     */
    private $_user;

    /**
     * 是否已经登录
     *
     * @access private
     * @var boolean
     */
    private $_hasLogin = NULL;

    /**
     * 全局选项
     *
     * @access protected
     * @var Widget_Options
     */
    protected $options;

    /**
     * 数据库对象
     *
     * @access protected
     * @var Typecho_Db
     */
    protected $db;

    /**
     * 用户组
     *
     * @access public
     * @var array
     */
    public $groups = array(
            'administrator' => 0,
            'editor'		=> 1,
            'contributor'	=> 2,
            'subscriber'	=> 3,
            'visitor'		=> 4
            );

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /** 初始化数据库 */
        $this->db = Typecho_Db::get();
        $this->options = $this->widget('Widget_Options');
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        if ($this->hasLogin()) {
            $rows = $this->db->fetchAll($this->db->select()
            ->from('table.options')->where('user = ?', $this->_user['uid']));

            $this->push($this->_user);

            foreach ($rows as $row) {
                $this->options->__set($row['name'], $row['value']);
            }

            //更新最后活动时间
            $this->db->query($this->db
            ->update('table.users')
            ->rows(array('activated' => $this->options->time))
            ->where('uid = ?', $this->_user['uid']));
        }
    }

    /**
     * 以用户名和密码登录
     *
     * @access public
     * @param string $name 用户名
     * @param string $password 密码
     * @param boolean $temporarily 是否为临时登录
     * @param integer $expire 过期时间
     * @return boolean
     */
    public function login($name, $password, $temporarily = false, $expire = 0)
    {
        //插件接口
        $result = $this->pluginHandle()->trigger($loginPluggable)->login($name, $password, $temporarily, $expire);
        if ($loginPluggable) {
            return $result;
        }

        /** 开始验证用户 **/
        $user = $this->db->fetchRow($this->db->select()
        ->from('table.users')
        ->where((strpos($name, '@') ? 'mail' : 'name') . ' = ?', $name)
        ->limit(1));

        if (empty($user)) {
            return false;
        }

        $hashValidate = $this->pluginHandle()->trigger($hashPluggable)->hashValidate($password, $user['password']);
        if (!$hashPluggable) {
            if ('$P$' == substr($user['password'], 0, 3)) {
                $hasher = new PasswordHash(8, true);
                $hashValidate = $hasher->CheckPassword($password, $user['password']);
            } else {
                $hashValidate = Typecho_Common::hashValidate($password, $user['password']);
            }
        }

        if ($user && $hashValidate) {

            if (!$temporarily) {
                $authCode = function_exists('openssl_random_pseudo_bytes') ?
                    bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
                $user['authCode'] = $authCode;

                Typecho_Cookie::set('__typecho_uid', $user['uid'], $expire);
                Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), $expire);

                //更新最后登录时间以及验证码
                $this->db->query($this->db
                ->update('table.users')
                ->expression('logged', 'activated')
                ->rows(array('authCode' => $authCode))
                ->where('uid = ?', $user['uid']));
            }

            /** 压入数据 */
            $this->push($user);
            $this->_user = $user;
            $this->_hasLogin = true;
            $this->pluginHandle()->loginSucceed($this, $name, $password, $temporarily, $expire);

            return true;
        }

        $this->pluginHandle()->loginFail($this, $name, $password, $temporarily, $expire);
        return false;
    }
    
    /**
     * 只需要提供uid即可登录的方法, 多用于插件等特殊场合
     * 
     * @access public
     * @param integer $uid 用户id
     * @return boolean
     */
    public function simpleLogin($uid)
    {
        $user = $this->db->fetchRow($this->db->select()
        ->from('table.users')
        ->where('uid = ?', $uid)
        ->limit(1));
        
        if (empty($user)) {
            return false;
        }
        
        $this->push($user);
        $this->_hasLogin = true;
        
        return true;
    }

    /**
     * 用户登出函数
     *
     * @access public
     * @return void
     */
    public function logout()
    {
        $this->pluginHandle()->trigger($logoutPluggable)->logout();
        if ($logoutPluggable) {
            return;
        }

        Typecho_Cookie::delete('__typecho_uid');
        Typecho_Cookie::delete('__typecho_authCode');
    }

    /**
     * 判断用户是否已经登录
     *
     * @access public
     * @return boolean
     */
    public function hasLogin()
    {
        if (NULL !== $this->_hasLogin) {
            return $this->_hasLogin;
        } else {
            $cookieUid = Typecho_Cookie::get('__typecho_uid');
            if (NULL !== $cookieUid) {
                /** 验证登陆 */
                $user = $this->db->fetchRow($this->db->select()->from('table.users')
                ->where('uid = ?', intval($cookieUid))
                ->limit(1));

                $cookieAuthCode = Typecho_Cookie::get('__typecho_authCode');
                if ($user && Typecho_Common::hashValidate($user['authCode'], $cookieAuthCode)) {
                    $this->_user = $user;
                    return ($this->_hasLogin = true);
                }

                $this->logout();
            }

            return ($this->_hasLogin = false);
        }
    }

    /**
     * 判断用户权限
     *
     * @access public
     * @param string $group 用户组
     * @param boolean $return 是否为返回模式
     * @return boolean
     * @throws Typecho_Widget_Exception
     */
    public function pass($group, $return = false)
    {
        if ($this->hasLogin()) {
            if (array_key_exists($group, $this->groups) && $this->groups[$this->group] <= $this->groups[$group]) {
                return true;
            }
        } else {
            if ($return) {
                return false;
            } else {
                //防止循环重定向
                $this->response->redirect($this->options->loginUrl .
                (0 === strpos($this->request->getReferer(), $this->options->loginUrl) ? '' :
                '?referer=' . urlencode($this->request->makeUriByRequest())), false);
            }
        }

        if ($return) {
            return false;
        } else {
            throw new Typecho_Widget_Exception(_t('禁止访问'), 403);
        }
    }
}
