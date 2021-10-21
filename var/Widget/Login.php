<?php

namespace Widget;

use Typecho\Cookie;
use Typecho\Validate;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 登录组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Login extends Users implements ActionInterface
{
    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        // protect
        $this->security->protect();

        /** 如果已经登录 */
        if ($this->user->hasLogin()) {
            /** 直接返回 */
            $this->response->redirect($this->options->index);
        }

        /** 初始化验证类 */
        $validator = new Validate();
        $validator->addRule('name', 'required', _t('请输入用户名'));
        $validator->addRule('password', 'required', _t('请输入密码'));
        $expire = 30 * 24 * 3600;

        /** 记住密码状态 */
        if ($this->request->remember) {
            Cookie::set('__typecho_remember_remember', 1, $expire);
        } elseif (Cookie::get('__typecho_remember_remember')) {
            Cookie::delete('__typecho_remember_remember');
        }

        /** 截获验证异常 */
        if ($error = $validator->run($this->request->from('name', 'password'))) {
            Cookie::set('__typecho_remember_name', $this->request->name);

            /** 设置提示信息 */
            Notice::alloc()->set($error);
            $this->response->goBack();
        }

        /** 开始验证用户 **/
        $valid = $this->user->login(
            $this->request->name,
            $this->request->password,
            false,
            1 == $this->request->remember ? $expire : 0
        );

        /** 比对密码 */
        if (!$valid) {
            /** 防止穷举,休眠3秒 */
            sleep(3);

            self::pluginHandle()->loginFail(
                $this->user,
                $this->request->name,
                $this->request->password,
                1 == $this->request->remember
            );

            Cookie::set('__typecho_remember_name', $this->request->name);
            Notice::alloc()->set(_t('用户名或密码无效'), 'error');
            $this->response->goBack('?referer=' . urlencode($this->request->referer));
        }

        self::pluginHandle()->loginSucceed(
            $this->user,
            $this->request->name,
            $this->request->password,
            1 == $this->request->remember
        );

        /** 跳转验证后地址 */
        if (!empty($this->request->referer)) {
            /** fix #952 & validate redirect url */
            if (
                0 === strpos($this->request->referer, $this->options->adminUrl)
                || 0 === strpos($this->request->referer, $this->options->siteUrl)
            ) {
                $this->response->redirect($this->request->referer);
            }
        } elseif (!$this->user->pass('contributor', true)) {
            /** 不允许普通用户直接跳转后台 */
            $this->response->redirect($this->options->profileUrl);
        }

        $this->response->redirect($this->options->adminUrl);
    }
}
