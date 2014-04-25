<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 编辑用户
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 编辑用户组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Users_Profile extends Widget_Users_Edit implements Widget_Interface_Do
{
    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 注册用户以上权限 */
        $this->user->pass('subscriber');
        $this->request->setParam('uid', $this->user->uid);
    }

    /**
     * 生成表单
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function profileForm()
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/users-profile'),
        Typecho_Widget_Helper_Form::POST_METHOD);

        /** 用户昵称 */
        $screenName = new Typecho_Widget_Helper_Form_Element_Text('screenName', NULL, NULL, _t('昵称'), _t('用户昵称可以与用户名不同, 用于前台显示.')
            . '<br />' . _t('如果你将此项留空, 将默认使用用户名.'));
        $form->addInput($screenName);

        /** 个人主页地址 */
        $url = new Typecho_Widget_Helper_Form_Element_Text('url', NULL, NULL, _t('个人主页地址'), _t('此用户的个人主页地址, 请用 <code>http://</code> 开头.'));
        $form->addInput($url);

        /** 电子邮箱地址 */
        $mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL, _t('电子邮箱地址 *'), _t('电子邮箱地址将作为此用户的主要联系方式.')
            . '<br />' . _t('请不要与系统中现有的电子邮箱地址重复.'));
        $form->addInput($mail);

        /** 用户动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'profile');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('更新我的档案'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $screenName->value($this->user->screenName);
        $url->value($this->user->url);
        $mail->value($this->user->mail);

        /** 给表单增加规则 */
        $screenName->addRule(array($this, 'screenNameExists'), _t('昵称已经存在'));
        $screenName->addRule('xssCheck', _t('请不要在昵称中使用特殊字符'));
        $url->addRule('url', _t('个人主页地址格式错误'));
        $mail->addRule('required', _t('必须填写电子邮箱'));
        $mail->addRule(array($this, 'mailExists'), _t('电子邮箱地址已经存在'));
        $mail->addRule('email', _t('电子邮箱格式错误'));

        return $form;
    }

    /**
     * 输出表单结构
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function optionsForm()
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/users-profile'),
        Typecho_Widget_Helper_Form::POST_METHOD);

        /** 撰写设置 */
        $markdown = new Typecho_Widget_Helper_Form_Element_Radio('markdown',
        array('0' => _t('关闭'), '1' => _t('打开')),
        $this->options->markdown, _t('使用 Markdown 语法编辑和解析内容'), 
            _t('使用 <a href="http://daringfireball.net/projects/markdown/">Markdown</a> 语法能够使您的撰写过程更加简便直观.')
                . '<br />' . _t('此功能开启不会影响以前没有使用 Markdown 语法编辑的内容.'));
        $form->addInput($markdown);

        /** 自动保存 */
        $autoSave = new Typecho_Widget_Helper_Form_Element_Radio('autoSave',
        array('0' => _t('关闭'), '1' => _t('打开')),
        $this->options->autoSave, _t('自动保存'), _t('自动保存功能可以更好地保护你的文章不会丢失.'));
        $form->addInput($autoSave);

        /** 默认允许 */
        $allow = array();
        if ($this->options->defaultAllowComment) {
            $allow[] = 'comment';
        }

        if ($this->options->defaultAllowPing) {
            $allow[] = 'ping';
        }

        if ($this->options->defaultAllowFeed) {
            $allow[] = 'feed';
        }

        $defaultAllow = new Typecho_Widget_Helper_Form_Element_Checkbox('defaultAllow',
        array('comment' => _t('可以被评论'), 'ping' => _t('可以被引用'), 'feed' => _t('出现在聚合中')),
        $allow, _t('默认允许'), _t('设置你经常使用的默认允许权限'));
        $form->addInput($defaultAllow);

        /** 用户动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'options');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 输出自定义设置选项
     *
     * @access public
     * @param string $pluginName 插件名称
     * @param string $className 类名称
     * @param string $pluginFileName 插件文件名
     * @param string $group 用户组
     * @return Typecho_Widget_Helper_Form
     */
    public function personalForm($pluginName, $className, $pluginFileName, &$group)
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/users-profile'),
        Typecho_Widget_Helper_Form::POST_METHOD);
        $form->setAttribute('name', $pluginName);
        $form->setAttribute('id', $pluginName);

        require_once $pluginFileName;
        $group = call_user_func(array($className, 'personalConfig'), $form);
        $group = $group ? $group : 'subscriber';

        $options = $this->options->personalPlugin($pluginName);

        if (!empty($options)) {
            foreach ($options as $key => $val) {
                $form->getInput($key)->value($val);
            }
        }

        $form->addItem(new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'personal'));
        $form->addItem(new Typecho_Widget_Helper_Form_Element_Hidden('plugin', NULL, $pluginName));
        $form->addItem(new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置')));
        return $form;
    }

    /**
     * 自定义设置列表
     *
     * @access public
     * @return void
     */
    public function personalFormList()
    {
        $this->widget('Widget_Plugins_List@personalPlugins', 'activated=1')->to($plugins);
        while ($plugins->next()) {
            if ($plugins->personalConfig) {
                echo '<h3>' . $plugins->title . '</h3>';
                list($pluginFileName, $className) = Typecho_Plugin::portal($plugins->name,
                    $this->options->pluginDir($plugins->name));

                $form = $this->personalForm($plugins->name, $className, $pluginFileName, $group);
                if ($this->user->pass($group, true)) {
                    $form->render();
                }
            }
        }
    }

    /**
     * 生成表单
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function passwordForm()
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/users-profile'),
        Typecho_Widget_Helper_Form::POST_METHOD);

        /** 用户密码 */
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL, _t('用户密码'), _t('为此用户分配一个密码.')
            . '<br />' . _t('建议使用特殊字符与字母、数字的混编样式,以增加系统安全性.'));
        $password->input->setAttribute('class', 'w-60');
        $form->addInput($password);

        /** 用户密码确认 */
        $confirm = new Typecho_Widget_Helper_Form_Element_Password('confirm', NULL, NULL, _t('用户密码确认'), _t('请确认你的密码, 与上面输入的密码保持一致.'));
        $confirm->input->setAttribute('class', 'w-60');
        $form->addInput($confirm);

        /** 用户动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'password');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('更新密码'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $password->addRule('required', _t('必须填写密码'));
        $password->addRule('minLength', _t('为了保证账户安全, 请输入至少六位的密码'), 6);
        $confirm->addRule('confirm', _t('两次输入的密码不一致'), 'password');

        return $form;
    }

    /**
     * 更新用户
     *
     * @access public
     * @return void
     */
    public function updateProfile()
    {
        if ($this->profileForm()->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $user = $this->request->from('mail', 'screenName', 'url');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];

        /** 更新数据 */
        $this->update($user, $this->db->sql()->where('uid = ?', $this->user->uid));

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('user-' . $this->user->uid);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('您的档案已经更新'), 'success');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 执行更新动作
     *
     * @access public
     * @return void
     */
    public function updateOptions()
    {
        $settings['autoSave'] = $this->request->autoSave ? 1 : 0;
        $settings['markdown'] = $this->request->markdown ? 1 : 0;
        $defaultAllow = $this->request->getArray('defaultAllow');

        $settings['defaultAllowComment'] = in_array('comment', $defaultAllow) ? 1 : 0;
        $settings['defaultAllowPing'] = in_array('ping', $defaultAllow) ? 1 : 0;
        $settings['defaultAllowFeed'] = in_array('feed', $defaultAllow) ? 1 : 0;

        foreach ($settings as $name => $value) {
            if ($this->db->fetchObject($this->db->select(array('COUNT(*)' => 'num'))
            ->from('table.options')->where('name = ? AND user = ?', $name, $this->user->uid))->num > 0) {
                $this->widget('Widget_Abstract_Options')
                ->update(array('value' => $value), $this->db->sql()->where('name = ? AND user = ?', $name, $this->user->uid));
            } else {
                $this->widget('Widget_Abstract_Options')->insert(array(
                    'name'  =>  $name,
                    'value' =>  $value,
                    'user'  =>  $this->user->uid
                ));
            }
        }

        $this->widget('Widget_Notice')->set(_t("设置已经保存"), 'success');
        $this->response->goBack();
    }

    /**
     * 更新密码
     *
     * @access public
     * @return void
     */
    public function updatePassword()
    {
        /** 验证格式 */
        if ($this->passwordForm()->validate()) {
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);
        $password = $hasher->HashPassword($this->request->password);

        /** 更新数据 */
        $this->update(array('password' => $password),
        $this->db->sql()->where('uid = ?', $this->user->uid));

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('user-' . $this->user->uid);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('密码已经成功修改'), 'success');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 更新个人设置
     *
     * @access public
     * @return void
     */
    public function updatePersonal()
    {
        /** 获取插件名称 */
        $pluginName = $this->request->plugin;

        /** 获取已启用插件 */
        $plugins = Typecho_Plugin::export();
        $activatedPlugins = $plugins['activated'];

        /** 获取插件入口 */
        list($pluginFileName, $className) = Typecho_Plugin::portal($this->request->plugin,
        __TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__);
        $info = Typecho_Plugin::parseInfo($pluginFileName);

        if (!$info['personalConfig'] || !isset($activatedPlugins[$pluginName])) {
            throw new Typecho_Widget_Exception(_t('无法配置插件'), 500);
        }

        $form = $this->personalForm($pluginName, $className, $pluginFileName, $group);
        $this->user->pass($group);

        /** 验证表单 */
        if ($form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();
        unset($settings['do'], $settings['plugin']);
        $name = '_plugin:' . $pluginName;

        if (!$this->personalConfigHandle($className, $settings)) {
            if ($this->db->fetchObject($this->db->select(array('COUNT(*)' => 'num'))
            ->from('table.options')->where('name = ? AND user = ?', $name, $this->user->uid))->num > 0) {
                $this->widget('Widget_Abstract_Options')
                ->update(array('value' => serialize($settings)), $this->db->sql()->where('name = ? AND user = ?', $name, $this->user->uid));
            } else {
                $this->widget('Widget_Abstract_Options')->insert(array(
                    'name'  =>  $name,
                    'value' =>  serialize($settings),
                    'user'  =>  $this->user->uid
                ));
            }
        }

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t("%s 设置已经保存", $info['title']), 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('profile.php', $this->options->adminUrl));
    }

    /**
     * 用自有函数处理自定义配置信息
     *
     * @access public
     * @param string $className 类名
     * @param array $settings 配置值
     * @return boolean
     */
    public function personalConfigHandle($className, array $settings)
    {
        if (method_exists($className, 'personalConfigHandle')) {
            call_user_func(array($className, 'personalConfigHandle'), $settings, false);
            return true;
        }

        return false;
    }

    /**
     * 入口函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=profile'))->updateProfile();
        $this->on($this->request->is('do=options'))->updateOptions();
        $this->on($this->request->is('do=password'))->updatePassword();
        $this->on($this->request->is('do=personal&plugin'))->updatePersonal();
        $this->response->redirect($this->options->siteUrl);
    }
}
