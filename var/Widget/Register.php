<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 注册组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Widget_Register extends Widget_Abstract_Users implements Widget_Interface_Do
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
        if ($this->user->hasLogin() || !$this->options->allowRegister) {
            /** 直接返回 */
            $this->response->redirect($this->options->index);
        }

        /** 初始化验证类 */
        $validator = new Typecho_Validate();
        $validator->addRule('name', 'required', _t('必须填写用户名称'));
        $validator->addRule('name', 'minLength', _t('用户名至少包含2个字符'), 2);
        $validator->addRule('name', 'maxLength', _t('用户名最多包含32个字符'), 32);
        $validator->addRule('name', 'xssCheck', _t('请不要在用户名中使用特殊字符'));
        $validator->addRule('name', array($this, 'nameExists'), _t('用户名已经存在'));
        $validator->addRule('mail', 'required', _t('必须填写电子邮箱'));
        $validator->addRule('mail', array($this, 'mailExists'), _t('电子邮箱地址已经存在'));
        $validator->addRule('mail', 'email', _t('电子邮箱格式错误'));
        $validator->addRule('mail', 'maxLength', _t('电子邮箱最多包含200个字符'), 200);

        /** 如果请求中有password */
        if (array_key_exists('password', $_REQUEST)) {
            $validator->addRule('password', 'required', _t('必须填写密码'));
            $validator->addRule('password', 'minLength', _t('为了保证账户安全, 请输入至少六位的密码'), 6);
            $validator->addRule('password', 'maxLength', _t('为了便于记忆, 密码长度请不要超过十八位'), 18);
            $validator->addRule('confirm', 'confirm', _t('两次输入的密码不一致'), 'password');
        }

        /** 截获验证异常 */
        if ($error = $validator->run($this->request->from('name', 'password', 'mail', 'confirm'))) {
            Typecho_Cookie::set('__typecho_remember_name', $this->request->name);
            Typecho_Cookie::set('__typecho_remember_mail', $this->request->mail);

            /** 设置提示信息 */
            $this->widget('Widget_Notice')->set($error);
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);
        $generatedPassword = Typecho_Common::randString(7);

        $dataStruct = array(
            'name'      =>  $this->request->name,
            'mail'      =>  $this->request->mail,
            'screenName'=>  $this->request->name,
            'password'  =>  $hasher->HashPassword($generatedPassword),
            'created'   =>  $this->options->time,
            'group'     =>  'subscriber'
        );

        $dataStruct = $this->pluginHandle()->register($dataStruct);

        $insertId = $this->insert($dataStruct);
        $this->db->fetchRow($this->select()->where('uid = ?', $insertId)
        ->limit(1), array($this, 'push'));

        $this->pluginHandle()->finishRegister($this);

        $this->user->login($this->request->name, $generatedPassword);

        Typecho_Cookie::delete('__typecho_first_run');
        Typecho_Cookie::delete('__typecho_remember_name');
        Typecho_Cookie::delete('__typecho_remember_mail');

        $this->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $this->screenName, $generatedPassword), 'success');
        $this->response->redirect($this->options->adminUrl);
    }
}
