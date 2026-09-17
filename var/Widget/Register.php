<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Db\Exception;
use Typecho\Validate;
use Utils\PasswordHash;
use Widget\Base\Users;
use Widget\Users\EditTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 注册组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Register extends Users implements ActionInterface
{
    use EditTrait;

    /**
     * 初始化函数
     *
     * @throws Exception
     */
    public function action()
    {
        // protect
        $this->security->protect();

        /** 如果已经登录 */
        if ($this->user->hasLogin() || !$this->options->allowRegister) {
            /** 直接返回 */
            $this->response->redirect($this->options->index);
        }

        /** 初始化验证类 */
        $validator = new Validate();
        $validator->addRule('name', 'required', _t('必须填写用户名称'));
        $validator->addRule('name', 'minLength', _t('用户名至少包含2个字符'), 2);
        $validator->addRule('name', 'maxLength', _t('用户名最多包含32个字符'), 32);
        $validator->addRule('name', 'xssCheck', _t('请不要在用户名中使用特殊字符'));
        $validator->addRule('name', [$this, 'nameExists'], _t('注册失败，请检查输入'));
        $validator->addRule('mail', 'required', _t('必须填写电子邮箱'));
        $validator->addRule('mail', [$this, 'mailExists'], _t('注册失败，请检查输入'));
        $validator->addRule('mail', 'email', _t('电子邮箱格式错误'));
        $validator->addRule('mail', 'maxLength', _t('电子邮箱最多包含64个字符'), 64);

        /** 如果请求中有password */
        $hasCustomPassword = array_key_exists('password', $_POST)
            && $_POST['password'] !== '';
        if ($hasCustomPassword) {
            $validator->addRule('password', 'required', _t('必须填写密码'));
            $validator->addRule('password', 'minLength', _t('为了保证账户安全, 请输入至少六位的密码'), 6);
            $validator->addRule('password', 'maxLength', _t('为了便于记忆, 密码长度请不要超过十八位'), 18);
            $validator->addRule('confirm', 'confirm', _t('两次输入的密码不一致'), 'password');
        }

        /** 截获验证异常 */
        $input = $this->request->from('name', 'mail');
        if ($hasCustomPassword) {
            $input['password'] = $_POST['password'];
            $input['confirm'] = $_POST['confirm'] ?? '';
        }
        if ($error = $validator->run($input)) {
            Cookie::set('__typecho_remember_name', $this->request->get('name'));
            Cookie::set('__typecho_remember_mail', $this->request->get('mail'));

            /** 设置提示信息 */
            Notice::alloc()->set($error);
            $this->response->goBack();
        }

        $hasher = new PasswordHash(12, false);

        $password = $hasCustomPassword ? $_POST['password'] : Common::randString(12);

        $dataStruct = [
            'name' => $this->request->get('name'),
            'mail' => $this->request->get('mail'),
            'screenName' => $this->request->get('name'),
            'password' => $hasher->hashPassword($password),
            'created' => $this->options->time,
            'group' => 'subscriber'
        ];

        $dataStruct = self::pluginHandle()->filter('register', $dataStruct);

        $insertId = $this->insert($dataStruct);
        $this->db->fetchRow($this->select()->where('uid = ?', $insertId)
            ->limit(1), [$this, 'push']);

        self::pluginHandle()->call('finishRegister', $this);

        $this->user->login($this->request->get('name'), $password);

        Cookie::delete('__typecho_first_run');
        Cookie::delete('__typecho_remember_name');
        Cookie::delete('__typecho_remember_mail');

        if ($hasCustomPassword) {
            Notice::alloc()->set(
                _t(
                    '用户 <strong>%s</strong> 已经成功注册, 请使用您填写的密码登录',
                    $this->screenName
                ),
                'success'
            );
        } else {
            Notice::alloc()->set(
                _t(
                    '用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>',
                    $this->screenName,
                    $password
                ),
                'success'
            );
        }
        $this->response->redirect($this->options->adminUrl);
    }
}
