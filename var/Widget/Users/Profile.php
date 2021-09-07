<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Plugin;
use Typecho\Widget\Helper\Form;
use Utils\PasswordHash;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;
use Widget\Plugins\Rows;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑用户组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Profile extends Edit implements ActionInterface
{
    /**
     * 执行函数
     */
    public function execute()
    {
        /** 注册用户以上权限 */
        $this->user->pass('subscriber');
        $this->request->setParam('uid', $this->user->uid);
    }

    /**
     * 输出表单结构
     *
     * @access public
     * @return Form
     */
    public function optionsForm(): Form
    {
        /** 构建表格 */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);

        /** 撰写设置 */
        $markdown = new Form\Element\Radio(
            'markdown',
            ['0' => _t('关闭'), '1' => _t('打开')],
            $this->options->markdown,
            _t('使用 Markdown 语法编辑和解析内容'),
            _t('使用 <a href="http://daringfireball.net/projects/markdown/">Markdown</a> 语法能够使您的撰写过程更加简便直观.')
            . '<br />' . _t('此功能开启不会影响以前没有使用 Markdown 语法编辑的内容.')
        );
        $form->addInput($markdown);

        $xmlrpcMarkdown = new Form\Element\Radio(
            'xmlrpcMarkdown',
            ['0' => _t('关闭'), '1' => _t('打开')],
            $this->options->xmlrpcMarkdown,
            _t('在 XMLRPC 接口中使用 Markdown 语法'),
            _t('对于完全支持 <a href="http://daringfireball.net/projects/markdown/">Markdown</a> 语法写作的离线编辑器, 打开此选项后将避免内容被转换为 HTML.')
        );
        $form->addInput($xmlrpcMarkdown);

        /** 自动保存 */
        $autoSave = new Form\Element\Radio(
            'autoSave',
            ['0' => _t('关闭'), '1' => _t('打开')],
            $this->options->autoSave,
            _t('自动保存'),
            _t('自动保存功能可以更好地保护你的文章不会丢失.')
        );
        $form->addInput($autoSave);

        /** 默认允许 */
        $allow = [];
        if ($this->options->defaultAllowComment) {
            $allow[] = 'comment';
        }

        if ($this->options->defaultAllowPing) {
            $allow[] = 'ping';
        }

        if ($this->options->defaultAllowFeed) {
            $allow[] = 'feed';
        }

        $defaultAllow = new Form\Element\Checkbox(
            'defaultAllow',
            ['comment' => _t('可以被评论'), 'ping' => _t('可以被引用'), 'feed' => _t('出现在聚合中')],
            $allow,
            _t('默认允许'),
            _t('设置你经常使用的默认允许权限')
        );
        $form->addInput($defaultAllow);

        /** 用户动作 */
        $do = new Form\Element\Hidden('do', null, 'options');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Form\Element\Submit('submit', null, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 自定义设置列表
     *
     * @throws Plugin\Exception
     */
    public function personalFormList()
    {
        $plugins = Rows::alloc('activated=1');

        while ($plugins->next()) {
            if ($plugins->personalConfig) {
                [$pluginFileName, $className] = Plugin::portal($plugins->name, $this->options->pluginDir);

                $form = $this->personalForm($plugins->name, $className, $pluginFileName, $group);
                if ($this->user->pass($group, true)) {
                    echo '<br><section id="personal-' . $plugins->name . '">';
                    echo '<h3>' . $plugins->title . '</h3>';

                    $form->render();

                    echo '</section>';
                }
            }
        }
    }

    /**
     * 输出自定义设置选项
     *
     * @access public
     * @param string $pluginName 插件名称
     * @param string $className 类名称
     * @param string $pluginFileName 插件文件名
     * @param string|null $group 用户组
     * @throws Plugin\Exception
     */
    public function personalForm(string $pluginName, string $className, string $pluginFileName, ?string &$group)
    {
        /** 构建表格 */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);
        $form->setAttribute('name', $pluginName);
        $form->setAttribute('id', $pluginName);

        require_once $pluginFileName;
        $group = call_user_func([$className, 'personalConfig'], $form);
        $group = $group ?: 'subscriber';

        $options = $this->options->personalPlugin($pluginName);

        if (!empty($options)) {
            foreach ($options as $key => $val) {
                $form->getInput($key)->value($val);
            }
        }

        $form->addItem(new Form\Element\Hidden('do', null, 'personal'));
        $form->addItem(new Form\Element\Hidden('plugin', null, $pluginName));
        $submit = new Form\Element\Submit('submit', null, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }

    /**
     * 更新用户
     *
     * @throws Exception
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
        Notice::alloc()->highlight('user-' . $this->user->uid);

        /** 提示信息 */
        Notice::alloc()->set(_t('您的档案已经更新'), 'success');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 生成表单
     *
     * @return Form
     */
    public function profileForm()
    {
        /** 构建表格 */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);

        /** 用户昵称 */
        $screenName = new Form\Element\Text('screenName', null, null, _t('昵称'), _t('用户昵称可以与用户名不同, 用于前台显示.')
            . '<br />' . _t('如果你将此项留空, 将默认使用用户名.'));
        $form->addInput($screenName);

        /** 个人主页地址 */
        $url = new Form\Element\Text('url', null, null, _t('个人主页地址'), _t('此用户的个人主页地址, 请用 <code>http://</code> 开头.'));
        $form->addInput($url);

        /** 电子邮箱地址 */
        $mail = new Form\Element\Text('mail', null, null, _t('邮件地址') . ' *', _t('电子邮箱地址将作为此用户的主要联系方式.')
            . '<br />' . _t('请不要与系统中现有的电子邮箱地址重复.'));
        $form->addInput($mail);

        /** 用户动作 */
        $do = new Form\Element\Hidden('do', null, 'profile');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Form\Element\Submit('submit', null, _t('更新我的档案'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $screenName->value($this->user->screenName);
        $url->value($this->user->url);
        $mail->value($this->user->mail);

        /** 给表单增加规则 */
        $screenName->addRule([$this, 'screenNameExists'], _t('昵称已经存在'));
        $screenName->addRule('xssCheck', _t('请不要在昵称中使用特殊字符'));
        $url->addRule('url', _t('个人主页地址格式错误'));
        $mail->addRule('required', _t('必须填写电子邮箱'));
        $mail->addRule([$this, 'mailExists'], _t('电子邮箱地址已经存在'));
        $mail->addRule('email', _t('电子邮箱格式错误'));

        return $form;
    }

    /**
     * 执行更新动作
     *
     * @throws Exception
     */
    public function updateOptions()
    {
        $settings['autoSave'] = $this->request->autoSave ? 1 : 0;
        $settings['markdown'] = $this->request->markdown ? 1 : 0;
        $settings['xmlrpcMarkdown'] = $this->request->xmlrpcMarkdown ? 1 : 0;
        $defaultAllow = $this->request->getArray('defaultAllow');

        $settings['defaultAllowComment'] = in_array('comment', $defaultAllow) ? 1 : 0;
        $settings['defaultAllowPing'] = in_array('ping', $defaultAllow) ? 1 : 0;
        $settings['defaultAllowFeed'] = in_array('feed', $defaultAllow) ? 1 : 0;

        foreach ($settings as $name => $value) {
            if (
                $this->db->fetchObject($this->db->select(['COUNT(*)' => 'num'])
                    ->from('table.options')->where('name = ? AND user = ?', $name, $this->user->uid))->num > 0
            ) {
                Options::alloc()
                    ->update(
                        ['value' => $value],
                        $this->db->sql()->where('name = ? AND user = ?', $name, $this->user->uid)
                    );
            } else {
                Options::alloc()->insert([
                    'name'  => $name,
                    'value' => $value,
                    'user'  => $this->user->uid
                ]);
            }
        }

        Notice::alloc()->set(_t("设置已经保存"), 'success');
        $this->response->goBack();
    }

    /**
     * 更新密码
     *
     * @throws Exception
     */
    public function updatePassword()
    {
        /** 验证格式 */
        if ($this->passwordForm()->validate()) {
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);
        $password = $hasher->hashPassword($this->request->password);

        /** 更新数据 */
        $this->update(
            ['password' => $password],
            $this->db->sql()->where('uid = ?', $this->user->uid)
        );

        /** 设置高亮 */
        Notice::alloc()->highlight('user-' . $this->user->uid);

        /** 提示信息 */
        Notice::alloc()->set(_t('密码已经成功修改'), 'success');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 生成表单
     *
     * @return Form
     */
    public function passwordForm(): Form
    {
        /** 构建表格 */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);

        /** 用户密码 */
        $password = new Form\Element\Password('password', null, null, _t('用户密码'), _t('为此用户分配一个密码.')
            . '<br />' . _t('建议使用特殊字符与字母、数字的混编样式,以增加系统安全性.'));
        $password->input->setAttribute('class', 'w-60');
        $form->addInput($password);

        /** 用户密码确认 */
        $confirm = new Form\Element\Password('confirm', null, null, _t('用户密码确认'), _t('请确认你的密码, 与上面输入的密码保持一致.'));
        $confirm->input->setAttribute('class', 'w-60');
        $form->addInput($confirm);

        /** 用户动作 */
        $do = new Form\Element\Hidden('do', null, 'password');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Form\Element\Submit('submit', null, _t('更新密码'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $password->addRule('required', _t('必须填写密码'));
        $password->addRule('minLength', _t('为了保证账户安全, 请输入至少六位的密码'), 6);
        $confirm->addRule('confirm', _t('两次输入的密码不一致'), 'password');

        return $form;
    }

    /**
     * 更新个人设置
     *
     * @throws \Typecho\Widget\Exception
     */
    public function updatePersonal()
    {
        /** 获取插件名称 */
        $pluginName = $this->request->plugin;

        /** 获取已启用插件 */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];

        /** 获取插件入口 */
        [$pluginFileName, $className] = Plugin::portal(
            $this->request->plugin,
            __TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__
        );
        $info = Plugin::parseInfo($pluginFileName);

        if (!$info['personalConfig'] || !isset($activatedPlugins[$pluginName])) {
            throw new \Typecho\Widget\Exception(_t('无法配置插件'), 500);
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
            if (
                $this->db->fetchObject($this->db->select(['COUNT(*)' => 'num'])
                    ->from('table.options')->where('name = ? AND user = ?', $name, $this->user->uid))->num > 0
            ) {
                Options::alloc()
                    ->update(
                        ['value' => serialize($settings)],
                        $this->db->sql()->where('name = ? AND user = ?', $name, $this->user->uid)
                    );
            } else {
                Options::alloc()->insert([
                    'name'  => $name,
                    'value' => serialize($settings),
                    'user'  => $this->user->uid
                ]);
            }
        }

        /** 提示信息 */
        Notice::alloc()->set(_t("%s 设置已经保存", $info['title']), 'success');

        /** 转向原页 */
        $this->response->redirect(Common::url('profile.php', $this->options->adminUrl));
    }

    /**
     * 用自有函数处理自定义配置信息
     *
     * @access public
     * @param string $className 类名
     * @param array $settings 配置值
     * @return boolean
     */
    public function personalConfigHandle(string $className, array $settings): bool
    {
        if (method_exists($className, 'personalConfigHandle')) {
            call_user_func([$className, 'personalConfigHandle'], $settings, false);
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
