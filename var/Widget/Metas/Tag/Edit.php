<?php

namespace Widget\Metas\Tag;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Widget\Helper\Form;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 标签编辑组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Metas implements ActionInterface
{
    /**
     * 入口函数
     */
    public function execute()
    {
        /** 编辑以上权限 */
        $this->user->pass('editor');
    }

    /**
     * 判断标签是否存在
     *
     * @param integer $mid 标签主键
     * @return boolean
     * @throws Exception
     */
    public function tagExists(int $mid): bool
    {
        $tag = $this->db->fetchRow($this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('mid = ?', $mid)->limit(1));

        return (bool)$tag;
    }

    /**
     * 判断标签名称是否存在
     *
     * @param string $name 标签名称
     * @return boolean
     * @throws Exception
     */
    public function nameExists(string $name): bool
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
        return !$tag;
    }

    /**
     * 判断标签名转换到缩略名后是否合法
     *
     * @param string $name 标签名
     * @return boolean
     */
    public function nameToSlug(string $name): bool
    {
        if (empty($this->request->slug)) {
            $slug = Common::slugName($name);
            if (empty($slug) || !$this->slugExists($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断标签缩略名是否存在
     *
     * @param string $slug 缩略名
     * @return boolean
     * @throws Exception
     */
    public function slugExists(string $slug): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('slug = ?', Common::slugName($slug))
            ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->mid);
        }

        $tag = $this->db->fetchRow($select);
        return !$tag;
    }

    /**
     * 插入标签
     *
     * @throws Exception
     */
    public function insertTag()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $tag = $this->request->from('name', 'slug');
        $tag['type'] = 'tag';
        $tag['slug'] = Common::slugName(empty($tag['slug']) ? $tag['name'] : $tag['slug']);

        /** 插入数据 */
        $tag['mid'] = $this->insert($tag);
        $this->push($tag);

        /** 设置高亮 */
        Notice::alloc()->highlight($this->theId);

        /** 提示信息 */
        Notice::alloc()->set(
            _t('标签 <a href="%s">%s</a> 已经被增加', $this->permalink, $this->name),
            'success'
        );

        /** 转向原页 */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 生成表单
     *
     * @param string|null $action 表单动作
     * @return Form
     * @throws Exception
     */
    public function form(?string $action = null): Form
    {
        /** 构建表格 */
        $form = new Form($this->security->getIndex('/action/metas-tag-edit'), Form::POST_METHOD);

        /** 标签名称 */
        $name = new Form\Element\Text(
            'name',
            null,
            null,
            _t('标签名称') . ' *',
            _t('这是标签在站点中显示的名称.可以使用中文,如 "地球".')
        );
        $form->addInput($name);

        /** 标签缩略名 */
        $slug = new Form\Element\Text(
            'slug',
            null,
            null,
            _t('标签缩略名'),
            _t('标签缩略名用于创建友好的链接形式, 如果留空则默认使用标签名称.')
        );
        $form->addInput($slug);

        /** 标签动作 */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** 标签主键 */
        $mid = new Form\Element\Hidden('mid');
        $form->addInput($mid);

        /** 提交按钮 */
        $submit = new Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (isset($this->request->mid) && 'insert' != $action) {
            /** 更新模式 */
            $meta = $this->db->fetchRow($this->select()
                ->where('mid = ?', $this->request->mid)
                ->where('type = ?', 'tag')->limit(1));

            if (!$meta) {
                $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
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
            $name->addRule([$this, 'nameExists'], _t('标签名称已经存在'));
            $name->addRule([$this, 'nameToSlug'], _t('标签名称无法被转换为缩略名'));
            $name->addRule('xssCheck', _t('请不要标签名称中使用特殊字符'));
            $slug->addRule([$this, 'slugExists'], _t('缩略名已经存在'));
            $slug->addRule('xssCheck', _t('请不要在缩略名中使用特殊字符'));
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('标签主键不存在'));
            $mid->addRule([$this, 'tagExists'], _t('标签不存在'));
        }

        return $form;
    }

    /**
     * 更新标签
     *
     * @throws Exception
     */
    public function updateTag()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $tag = $this->request->from('name', 'slug', 'mid');
        $tag['type'] = 'tag';
        $tag['slug'] = Common::slugName(empty($tag['slug']) ? $tag['name'] : $tag['slug']);

        /** 更新数据 */
        $this->update($tag, $this->db->sql()->where('mid = ?', $this->request->filter('int')->mid));
        $this->push($tag);

        /** 设置高亮 */
        Notice::alloc()->highlight($this->theId);

        /** 提示信息 */
        Notice::alloc()->set(
            _t('标签 <a href="%s">%s</a> 已经被更新', $this->permalink, $this->name),
            'success'
        );

        /** 转向原页 */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 删除标签
     *
     * @throws Exception
     */
    public function deleteTag()
    {
        $tags = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;

        if ($tags && is_array($tags)) {
            foreach ($tags as $tag) {
                if ($this->delete($this->db->sql()->where('mid = ?', $tag))) {
                    $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $tag));
                    $deleteCount++;
                }
            }
        }

        /** 提示信息 */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('标签已经删除') : _t('没有标签被删除'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** 转向原页 */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 合并标签
     *
     * @throws Exception
     */
    public function mergeTag()
    {
        if (empty($this->request->merge)) {
            Notice::alloc()->set(_t('请填写需要合并到的标签'), 'notice');
            $this->response->goBack();
        }

        $merge = $this->scanTags($this->request->merge);
        if (empty($merge)) {
            Notice::alloc()->set(_t('合并到的标签名不合法'), 'error');
            $this->response->goBack();
        }

        $tags = $this->request->filter('int')->getArray('mid');

        if ($tags) {
            $this->merge($merge, 'tag', $tags);

            /** 提示信息 */
            Notice::alloc()->set(_t('标签已经合并'), 'success');
        } else {
            Notice::alloc()->set(_t('没有选择任何标签'), 'notice');
        }

        /** 转向原页 */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 刷新标签
     *
     * @access public
     * @return void
     * @throws Exception
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

            Notice::alloc()->set(_t('标签刷新已经完成'), 'success');
        } else {
            Notice::alloc()->set(_t('没有选择任何标签'), 'notice');
        }

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 入口函数,绑定事件
     *
     * @access public
     * @return void
     * @throws Exception
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
