<?php
/**
 * 编辑分类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 编辑分类组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Metas_Category_Edit extends Widget_Abstract_Metas implements Widget_Interface_Do
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
     * 判断分类是否存在
     *
     * @access public
     * @param integer $mid 分类主键
     * @return boolean
     */
    public function categoryExists($mid)
    {
        $category = $this->db->fetchRow($this->db->select()
        ->from('table.metas')
        ->where('type = ?', 'category')
        ->where('mid = ?', $mid)->limit(1));

        return $category ? true : false;
    }

    /**
     * 判断分类名称是否存在
     *
     * @access public
     * @param string $name 分类名称
     * @return boolean
     */
    public function nameExists($name)
    {
        $select = $this->db->select()
        ->from('table.metas')
        ->where('type = ?', 'category')
        ->where('name = ?', $name)
        ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->mid);
        }

        $category = $this->db->fetchRow($select);
        return $category ? false : true;
    }

    /**
     * 判断分类名转换到缩略名后是否合法
     *
     * @access public
     * @param string $name 分类名
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
     * 判断分类缩略名是否存在
     *
     * @access public
     * @param string $slug 缩略名
     * @return boolean
     */
    public function slugExists($slug)
    {
        $select = $this->db->select()
        ->from('table.metas')
        ->where('type = ?', 'category')
        ->where('slug = ?', Typecho_Common::slugName($slug))
        ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->mid);
        }

        $category = $this->db->fetchRow($select);
        return $category ? false : true;
    }

    /**
     * 生成表单
     *
     * @access public
     * @param string $action 表单动作
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function form($action = NULL)
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/metas-category-edit', $this->options->index),
        Typecho_Widget_Helper_Form::POST_METHOD);

        /** 分类名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL, _t('分类名称 *'));
        $form->addInput($name);

        /** 分类缩略名 */
        $slug = new Typecho_Widget_Helper_Form_Element_Text('slug', NULL, NULL, _t('分类缩略名'),
        _t('分类缩略名用于创建友好的链接形式,建议使用字母,数字,下划线和横杠.'));
        $form->addInput($slug);

        /** 分类描述 */
        $description =  new Typecho_Widget_Helper_Form_Element_Textarea('description', NULL, NULL,
        _t('分类描述'), _t('此文字用于描述分类,在有的主题中它会被显示.'));
        $form->addInput($description);

        /** 分类动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 分类主键 */
        $mid = new Typecho_Widget_Helper_Form_Element_Hidden('mid');
        $form->addInput($mid);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'primary');
        $form->addItem($submit);

        if (isset($this->request->mid) && 'insert' != $action) {
            /** 更新模式 */
            $meta = $this->db->fetchRow($this->select()
            ->where('mid = ?', $this->request->mid)
            ->where('type = ?', 'category')->limit(1));

            if (!$meta) {
                $this->response->redirect(Typecho_Common::url('manage-metas.php', $this->options->adminUrl));
            }

            $name->value($meta['name']);
            $slug->value($meta['slug']);
            $description->value($meta['description']);
            $do->value('update');
            $mid->value($meta['mid']);
            $submit->value(_t('编辑分类'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('增加分类'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('必须填写分类名称'));
            $name->addRule(array($this, 'nameExists'), _t('分类名称已经存在'));
            $name->addRule(array($this, 'nameToSlug'), _t('分类名称无法被转换为缩略名'));
            $slug->addRule(array($this, 'slugExists'), _t('缩略名已经存在'));
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('分类主键不存在'));
            $mid->addRule(array($this, 'categoryExists'), _t('分类不存在'));
        }

        return $form;
    }

    /**
     * 增加分类
     *
     * @access public
     * @return void
     */
    public function insertCategory()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $category = $this->request->from('name', 'slug', 'description');
        $category['slug'] = Typecho_Common::slugName(empty($category['slug']) ? $category['name'] : $category['slug']);
        $category['type'] = 'category';
        $category['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))
        ->from('table.metas')
        ->where('type = ?', 'category'))->maxOrder + 1;

        /** 插入数据 */
        $category['mid'] = $this->insert($category);
        $this->push($category);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight($this->theId);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('分类 <a href="%s">%s</a> 已经被增加',
        $this->permalink, $this->name), NULL, 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-metas.php', $this->options->adminUrl));
    }

    /**
     * 更新分类
     *
     * @access public
     * @return void
     */
    public function updateCategory()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $category = $this->request->from('name', 'slug', 'description');
        $category['slug'] = Typecho_Common::slugName(empty($category['slug']) ? $category['name'] : $category['slug']);
        $category['type'] = 'category';

        /** 更新数据 */
        $this->update($category, $this->db->sql()->where('mid = ?', $this->request->filter('int')->mid));
        $category['mid'] = $this->request->mid;
        $this->push($category);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight($this->theId);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('分类 <a href="%s">%s</a> 已经被更新',
        $this->permalink, $this->name), NULL, 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-metas.php', $this->options->adminUrl));
    }

    /**
     * 删除分类
     *
     * @access public
     * @return void
     */
    public function deleteCategory()
    {
        $categories = $this->request->filter('int')->mid;
        $deleteCount = 0;

        if ($categories && is_array($categories)) {
            foreach ($categories as $category) {
                if ($this->delete($this->db->sql()->where('mid = ?', $category))) {
                    $this->db->query($this->db->sql()->delete('table.relationships')->where('mid = ?', $category));
                    $deleteCount ++;
                }
            }
        }

        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('分类已经删除') : _t('没有分类被删除'), NULL,
        $deleteCount > 0 ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-metas.php', $this->options->adminUrl));
    }

    /**
     * 合并分类
     *
     * @access public
     * @return void
     */
    public function mergeCategory()
    {
        /** 验证数据 */
        $validator = new Typecho_Validate();
        $validator->addRule('merge', 'required', _t('分类主键不存在'));
        $validator->addRule('merge', array($this, 'categoryExists'), _t('请选择需要合并的分类'));

        if ($validator->run($this->request->from('merge'))) {
            $this->widget('Widget_Notice')->set($e->getMessages(), NULL, 'error');
            $this->response->goBack();
        }

        $merge = $this->request->merge;
        $categories = $this->request->filter('int')->mid;

        if ($categories && is_array($categories)) {
            $this->merge($merge, 'category', $categories);

            /** 提示信息 */
            $this->widget('Widget_Notice')->set(_t('分类已经合并'), NULL, 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t('没有选择任何分类'), NULL, 'notice');
        }

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 分类排序
     *
     * @access public
     * @return void
     */
    public function sortCategory()
    {
        $categories = $this->request->filter('int')->mid;
        if ($categories && is_array($categories)) {
            $this->sort($categories, 'category');
        }

        if (!$this->request->isAjax()) {
            /** 转向原页 */
            $this->response->redirect(Typecho_Common::url('manage-metas.php', $this->options->adminUrl));
        } else {
            $this->response->throwJson(array('success' => 1, 'message' => _t('分类排序已经完成')));
        }
    }

    /**
     * 刷新分类
     *
     * @access public
     * @return void
     */
    public function refreshCategory()
    {
        $categories = $this->request->filter('int')->mid;
        if ($categories && is_array($categories)) {
            foreach ($categories as $category) {
                $this->refreshCountByTypeAndStatus($category, 'post', 'publish');
            }

            $this->widget('Widget_Notice')->set(_t('分类刷新已经完成'), NULL, 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t('没有选择任何分类'), NULL, 'notice');
        }

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 设置默认分类
     *
     * @access public
     * @return void
     */
    public function defaultCategory()
    {
        /** 验证数据 */
        $validator = new Typecho_Validate();
        $validator->addRule('mid', 'required', _t('分类主键不存在'));
        $validator->addRule('mid', array($this, 'categoryExists'), _t('分类不存在'));

        if ($error = $validator->run($this->request->from('mid'))) {
            $this->widget('Widget_Notice')->set($error, NULL, 'error');
        } else {

            $this->db->query($this->db->update('table.options')
            ->rows(array('value' => $this->request->mid))
            ->where('name = ?', 'defaultCategory'));

            $this->db->fetchRow($this->select()->where('mid = ?', $this->request->mid)
            ->where('type = ?', 'category')->limit(1), array($this, 'push'));

            /** 设置高亮 */
            $this->widget('Widget_Notice')->highlight($this->theId);

            /** 提示信息 */
            $this->widget('Widget_Notice')->set(_t('<a href="%s">%s</a> 已经被设为默认分类',
            $this->permalink, $this->name), NULL, 'success');
        }

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-metas.php', $this->options->adminUrl));
    }

    /**
     * 入口函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->on($this->request->is('do=insert'))->insertCategory();
        $this->on($this->request->is('do=update'))->updateCategory();
        $this->on($this->request->is('do=delete'))->deleteCategory();
        $this->on($this->request->is('do=merge'))->mergeCategory();
        $this->on($this->request->is('do=sort'))->sortCategory();
        $this->on($this->request->is('do=refresh'))->refreshCategory();
        $this->on($this->request->is('do=default'))->defaultCategory();
        $this->response->redirect($this->options->adminUrl);
    }
}
