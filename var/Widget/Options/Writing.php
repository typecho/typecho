<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章撰写设置
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 文章撰写设置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Options_Writing extends Widget_Options_Permalink
{
    /**
     * 输出表单结构
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function form()
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/options-writing'),
            Typecho_Widget_Helper_Form::POST_METHOD);

        /** 撰写设置 */
        $markdown = new Typecho_Widget_Helper_Form_Element_Radio('markdown',
            array('0' => _t('关闭'), '1' => _t('打开')),
            $this->options->markdown, _t('使用 Markdown 语法编辑和解析内容'),
            _t('使用 <a href="http://daringfireball.net/projects/markdown/">Markdown</a> 语法能够使您的撰写过程更加简便直观.')
            . '<br />' . _t('此功能开启不会影响以前没有使用 Markdown 语法编辑的内容.'));
        $form->addInput($markdown);

        /** Markdown URL识别设置 */
        $markdownAutoLink = new Typecho_Widget_Helper_Form_Element_Radio('markdownAutoLink',
            array('0' => _t('关闭'), '1' => _t('打开')),
            $this->options->markdownAutoLink, _t('启用Markdown自动转换URL为a标签功能'));
        $form->addInput($markdownAutoLink);

        $xmlrpcMarkdown = new Typecho_Widget_Helper_Form_Element_Radio('xmlrpcMarkdown',
            array('0' => _t('关闭'), '1' => _t('打开')),
            $this->options->xmlrpcMarkdown, _t('在 XMLRPC 接口中使用 Markdown 语法'),
            _t('对于完全支持 <a href="http://daringfireball.net/projects/markdown/">Markdown</a> 语法写作的离线编辑器, 打开此选项后将避免内容被转换为 HTML.'));
        $form->addInput($xmlrpcMarkdown);

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
     * 执行更新动作
     *
     * @access public
     * @return void
     */
    public function updateOptions()
    {
        $settings['autoSave'] = $this->request->autoSave ? 1 : 0;
        $settings['markdown'] = $this->request->markdown ? 1 : 0;
        $settings['markdownAutoLink'] = $this->request->markdownAutoLink ? 1 : 0;
        $settings['xmlrpcMarkdown'] = $this->request->xmlrpcMarkdown ? 1 : 0;
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
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->updateOptions();
        $this->response->redirect($this->options->adminUrl);
    }
}
