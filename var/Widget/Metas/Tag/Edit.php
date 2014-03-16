<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 标签编辑
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 标签编辑组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Metas_Tag_Edit extends Widget_Abstract_Metas implements Widget_Interface_Do
{
    /**
     * 入口函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 编辑以上权限 */
        $this->user->pass('editor');
    }

    /**
     * 判断标签是否存在
     *
     * @access public
     * @param integer $mid 标签主键
     * @return boolean
     */
    public function tagExists($mid)
    {
        $tag = $this->db->fetchRow($this->db->select()
        ->from('table.metas')
        ->where('type = ?', 'tag')
        ->where('mid = ?', $mid)->limit(1));

        return $tag ? true : false;
    }

    /**
     * 判断标签名称是否存在
     *
     * @access public
     * @param string $name 标签名称
     * @return boolean
     */
    public function nameExists($name)
    {
        $select = $this->db->select()
        ->from('table.metas')
        ->where('type = ?', 'tag')
        ->where('name = ?', $name)
        ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->filter('int')->mid);
        }

        $tag = $this->db->fetchRow($select);
        return $tag ? false : true;
    }

    /**
     * 判断标签名转换到缩略名后是否合法
     *
     * @access public
     * @param string $name 标签名
     * @return boolean
     */
    public function nameToSlug($name)
    {
        if (empty($this->request->slug)) {
            $slug = Typecho_Common::slugName($name);
            if (empty($slug) || !$this->slugExists($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断标签缩略名是否存在
     *
     * @access public
     * @param string $slug 缩略名
     * @return boolean
     */
    public function slugExists($slug)
    {
        $select = $this->db->select()
        ->from('table.metas')
        ->where('type = ?', 'tag')
        ->where('slug = ?', Typecho_Common::slugName($slug))
        ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->mid);
        }

        $tag = $this->db->fetchRow($select);
        return $tag ? false : true;
    }

    /**
     * 生成表单
     *
     * @access public
     * @param string $action 表单动作
     * @return Typecho_Widget_Helper_Form
     */
    public function form($action = NULL)
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/metas-tag-edit'),
            Typecho_Widget_Helper_Form::POST_METHOD);

        /** 标签名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL,
        _t('标签名称 *'), _t('这是标签在站点中显示的名称.可以使用中文,如 "地球".'));
        $form->addInput($name);

        /** 标签缩略名 */
        $slug = new Typecho_Widget_Helper_Form_Element_Text('slug', NULL, NULL,
        _t('标签缩略名'), _t('标签缩略名用于创建友好的链接形式, 如果留空则默认使用标签名称.'));
        $form->addInput($slug);

        /** 标签动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 标签主键 */
        $mid = new Typecho_Widget_Helper_Form_Element_Hidden('mid');
        $form->addInput($mid);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (isset($this->request->mid) && 'insert' != $action) {
            /** 更新模式 */
            $meta = $this->db->fetchRow($this->select()
            ->where('mid = ?', $this->request->mid)
            ->where('type = ?', 'tag')->limit(1));

            if (!$meta) {
                $this->response->redirect(Typecho_Common::url('manage-tags.php', $this->options->adminUrl));
            }

            $name->value($meta['name']);
            $slug->value($meta['slug']);
            $do->value('update');
            $mid->value($meta['mid']);
            $submit->value(_t('编辑标签'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('增加标签'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('必须填写标签名称'));
            $name->addRule(array($this, 'nameExists'), _t('标签名称已经存在'));
            $name->addRule(array($this, 'nameToSlug'), _t('标签名称无法被转换为缩略名'));
            $name->addRule('xssCheck', _t('请不要标签名称中使用特殊字符'));
            $slug->addRule(array($this, 'slugExists'), _t('缩略名已经存在'));
            $slug->addRule('xssCheck', _t('请不要在缩略名中使用特殊字符'));
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('标签主键不存在'));
            $mid->addRule(array($this, 'tagExists'), _t('标签不存在'));
        }

        return $form;
    }

    /**
     * 插入标签
     *
     * @access public
     * @return void
     */
    public function insertTag()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $tag = $this->request->from('name', 'slug');
        $tag['type'] = 'tag';
        $tag['slug'] = Typecho_Common::slugName(empty($tag['slug']) ? $tag['name'] : $tag['slug']);

        /** 插入数据 */
        $tag['mid'] = $this->insert($tag);
        $this->push($tag);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight($this->theId);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('标签 <a href="%s">%s</a> 已经被增加',
        $this->permalink, $this->name), 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 更新标签
     *
     * @access public
     * @return void
     */
    public function updateTag()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $tag = $this->request->from('name', 'slug', 'mid');
        $tag['type'] = 'tag';
        $tag['slug'] = Typecho_Common::slugName(empty($tag['slug']) ? $tag['name'] : $tag['slug']);

        /** 更新数据 */
        $this->update($tag, $this->db->sql()->where('mid = ?', $this->request->filter('int')->mid));
        $this->push($tag);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight($this->theId);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('标签 <a href="%s">%s</a> 已经被更新',
        $this->permalink, $this->name), 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 删除标签
     *
     * @access public
     * @return void
     */
    public function deleteTag()
    {
        $tags = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;

        if ($tags && is_array($tags)) {
            foreach ($tags as $tag) {
                if ($this->delete($this->db->sql()->where('mid = ?', $tag))) {
                    $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $tag));
                    $deleteCount ++;
                }
            }
        }

        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('标签已经删除') : _t('没有标签被删除'),
        $deleteCount > 0 ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 合并标签
     *
     * @access public
     * @return void
     */
    public function mergeTag()
    {
        if (empty($this->request->merge)) {
            $this->widget('Widget_Notice')->set(_t('请填写需要合并到的标签'), 'notice');
            $this->response->goBack();
        }

        $merge = $this->scanTags($this->request->merge);
        if (empty($merge)) {
            $this->widget('Widget_Notice')->set(_t('合并到的标签名不合法'), 'error');
            $this->response->goBack();
        }

        $tags = $this->request->filter('int')->getArray('mid');

        if ($tags) {
            $this->merge($merge, 'tag', $tags);

            /** 提示信息 */
            $this->widget('Widget_Notice')->set(_t('标签已经合并'), 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t('没有选择任何标签'), 'notice');
        }

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 刷新标签
     *
     * @access public
     * @return void
     */
    public function refreshTag()
    {
        $tags = $this->request->filter('int')->getArray('mid');
        if ($tags) {
            foreach ($tags as $tag) {
                $this->refreshCountByTypeAndStatus($tag, 'post', 'publish');
            } 

            // 自动清理标签
            $this->clearTags();

            $this->widget('Widget_Notice')->set(_t('标签刷新已经完成'), 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t('没有选择任何标签'), 'notice');
        }

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 入口函数,绑定事件
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertTag();
        $this->on($this->request->is('do=update'))->updateTag();
        $this->on($this->request->is('do=delete'))->deleteTag();
        $this->on($this->request->is('do=merge'))->mergeTag();
        $this->on($this->request->is('do=refresh'))->refreshTag();
        $this->response->redirect($this->options->adminUrl);
    }
}
