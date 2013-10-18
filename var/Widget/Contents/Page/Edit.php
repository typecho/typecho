<?php
/**
 * 编辑页面
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 编辑页面组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Contents_Page_Edit extends Widget_Contents_Post_Edit implements Widget_Interface_Do
{
    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 必须为编辑以上权限 */
        $this->user->pass('editor');

        /** 获取文章内容 */
        if (!empty($this->request->cid) && 'delete' != $this->request->do 
            && 'sort' != $this->request->do) {
            $this->db->fetchRow($this->select()
            ->where('table.contents.type = ? OR table.contents.type = ?', 'page', 'page_draft')
            ->where('table.contents.cid = ?', $this->request->filter('int')->cid)
            ->limit(1), array($this, 'push'));

            if ('page_draft' == $this->status && $this->parent) {
                $this->response->redirect(Typecho_Common::url('write-page.php?cid=' . $this->parent, $this->options->adminUrl));
            }

            if (!$this->have()) {
                throw new Typecho_Widget_Exception(_t('页面不存在'), 404);
            } else if ($this->have() && !$this->allow('edit')) {
                throw new Typecho_Widget_Exception(_t('没有编辑权限'), 403);
            }
        }
    }

    /**
     * 发布文章
     *
     * @access public
     * @return void
     */
    public function writePage()
    {
        $contents = $this->request->from('text', 'template', 'allowComment',
            'allowPing', 'allowFeed', 'slug', 'order');

        $contents['title'] = $this->request->get('title', _t('未命名页面'));
        $contents['created'] = $this->getCreated();

        if ($this->request->markdown && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = $this->pluginHandle()->write($contents, $this);

        if ($this->request->is('do=publish')) { 
            /** 重新发布已经存在的文章 */
            $contents['type'] = 'page';
            $this->publish($contents);

            /** 发送ping */
            $this->widget('Widget_Service')->sendPing($this->cid);

            /** 设置提示信息 */
            $this->widget('Widget_Notice')->set(_t('页面 "<a href="%s">%s</a>" 已经发布', $this->permalink, $this->title),
            NULL, 'success');

            /** 设置高亮 */
            $this->widget('Widget_Notice')->highlight($this->theId);

            /** 页面跳转 */
            $this->response->redirect(Typecho_Common::url('manage-pages.php?', $this->options->adminUrl));
        } else {
            /** 保存文章 */
            $contents['type'] = 'page_draft';
            $this->save($contents);

            if ($this->request->isAjax()) {
                $created = new Typecho_Date($this->options->gmtTime);
                $this->response->throwJson(array(
                    'success'   =>  1,
                    'time'      =>  $created->format('H:i:s A'),
                    'cid'       =>  $this->cid
                ));
            } else {
                /** 设置提示信息 */
                $this->widget('Widget_Notice')->set(_t('草稿 "%s" 已经被保存', $this->title), NULL, 'success');

                /** 返回原页面 */
                $this->response->redirect(Typecho_Common::url('write-page.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * 删除页面
     *
     * @access public
     * @return void
     */
    public function deletePage()
    {
        $cid = $this->request->filter('int')->cid;
        $deleteCount = 0;

        if ($cid) {
            /** 格式化页面主键 */
            $pages = is_array($cid) ? $cid : array($cid);
            foreach ($pages as $page) {
                if ($this->delete($this->db->sql()->where('cid = ?', $page))) {
                    /** 删除评论 */
                    $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $page));

                    /** 解除附件关联 */
                    $this->unAttach($page);

                    /** 解除首页关联 */
                    if ($this->options->frontPage == 'page:' . $page) {
                        $this->db->query($this->db->update('table.options')
                        ->rows(array('value' => 'recent'))
                        ->where('name = ?', 'frontPage'));
                    }

                    /** 删除草稿 */
                    $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?',
                        $page, 'page_draft')
                    ->limit(1));

                    if ($draft) {
                        $this->deleteDraft($draft['cid']);
                    }

                    $deleteCount ++;
                }
            }
        }

        /** 设置提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('页面已经被删除') : _t('没有页面被删除'), NULL,
        $deleteCount > 0 ? 'success' : 'notice');

        /** 返回原网页 */
        $this->response->goBack();
    }
    
    /**
     * 删除页面所属草稿
     * 
     * @access public
     * @return void
     */
    public function deletePageDraft()
    {
        $cid = $this->request->filter('int')->cid;
        $deleteCount = 0;
        
        if ($cid) {
            /** 格式化文章主键 */
            $pages = is_array($cid) ? $cid : array($cid);
            
            foreach ($pages as $page) {
                /** 删除草稿 */
                $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?',
                    $page, 'page_draft')
                ->limit(1));

                if ($draft) {
                    $this->deleteDraft($draft['cid']);
                    $deleteCount ++;
                }
            }
        }
        
        /** 设置提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('草稿已经被删除') : _t('没有草稿被删除'), NULL,
        $deleteCount > 0 ? 'success' : 'notice');
        
        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 页面排序
     *
     * @access public
     * @return void
     */
    public function sortPage()
    {
        $pages = $this->request->filter('int')->cid;

        if ($pages && is_array($pages)) {
            foreach ($pages as $sort => $cid) {
                $this->db->query($this->db->update('table.contents')->rows(array('order' => $sort + 1))
                ->where('cid = ?', $cid));
            }
        }

        if (!$this->request->isAjax()) {
            /** 转向原页 */
            $this->response->goBack();
        } else {
            $this->response->throwJson(array('success' => 1, 'message' => _t('页面排序已经完成')));
        }
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))->writePage();
        $this->on($this->request->is('do=delete'))->deletePage();
        $this->on($this->request->is('do=deleteDraft'))->deletePageDraft();
        $this->on($this->request->is('do=sort'))->sortPage();
        $this->response->redirect($this->options->adminUrl);
    }
}
