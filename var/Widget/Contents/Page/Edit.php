<?php

namespace Widget\Contents\Page;

use Typecho\Common;
use Typecho\Date;
use Typecho\Db\Exception as DbException;
use Typecho\Widget\Exception;
use Widget\Base\Contents;
use Widget\Contents\EditTrait;
use Widget\ActionInterface;
use Widget\Contents\PrepareEditTrait;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑页面组件
 *
 * @property-read array $draft
 */
class Edit extends Contents implements ActionInterface
{
    use PrepareEditTrait;
    use EditTrait;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Exception
     * @throws DbException
     */
    public function execute()
    {
        /** 必须为编辑以上权限 */
        $this->user->pass('editor');
    }

    /**
     * 发布文章
     */
    public function writePage()
    {
        $contents = $this->request->from(
            'text',
            'template',
            'allowComment',
            'allowPing',
            'allowFeed',
            'slug',
            'order',
            'visibility'
        );

        $contents['title'] = $this->request->get('title', _t('未命名页面'));
        $contents['created'] = $this->getCreated();
        $contents['visibility'] = ('hidden' == $contents['visibility'] ? 'hidden' : 'publish');
        $contents['parent'] = $this->getParent();

        if ($this->request->is('markdown=1') && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = self::pluginHandle()->filter('write', $contents, $this);

        if ($this->request->is('do=publish')) {
            /** 重新发布已经存在的文章 */
            $contents['type'] = 'page';
            $this->publish($contents, false);

            // 完成发布插件接口
            self::pluginHandle()->call('finishPublish', $contents, $this);

            /** 发送ping */
            Service::alloc()->sendPing($this);

            /** 设置提示信息 */
            Notice::alloc()->set(
                _t('页面 "<a href="%s">%s</a>" 已经发布', $this->permalink, $this->title),
                'success'
            );

            /** 设置高亮 */
            Notice::alloc()->highlight($this->theId);

            /** 页面跳转 */
            $this->response->redirect(Common::url('manage-pages.php'
                . ($this->parent ? '?parent=' . $this->parent : ''), $this->options->adminUrl));
        } else {
            /** 保存文章 */
            $contents['type'] = 'page_draft';
            $draftId = $this->save($contents, false);

            // 完成发布插件接口
            self::pluginHandle()->call('finishSave', $contents, $this);

            /** 设置高亮 */
            Notice::alloc()->highlight($this->cid);

            if ($this->request->isAjax()) {
                $created = new Date($this->options->time);
                $this->response->throwJson([
                    'success' => 1,
                    'time'    => $created->format('H:i:s A'),
                    'cid'     => $this->cid,
                    'draftId' => $draftId
                ]);
            } else {
                /** 设置提示信息 */
                Notice::alloc()->set(_t('草稿 "%s" 已经被保存', $this->title), 'success');

                /** 返回原页面 */
                $this->response->redirect(Common::url('write-page.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * 标记页面
     *
     * @throws DbException
     */
    public function markPage()
    {
        $status = $this->request->get('status');
        $statusList = [
            'publish' => _t('公开'),
            'hidden'  => _t('隐藏')
        ];

        if (!isset($statusList[$status])) {
            $this->response->goBack();
        }

        $pages = $this->request->filter('int')->getArray('cid');
        $markCount = 0;

        foreach ($pages as $page) {
            // 标记插件接口
            self::pluginHandle()->call('mark', $status, $page, $this);
            $condition = $this->db->sql()->where('cid = ?', $page);

            if ($this->db->query($condition->update('table.contents')->rows(['status' => $status]))) {
                // 处理草稿
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'revision')
                    ->limit(1));

                if (!empty($draft)) {
                    $this->db->query($this->db->update('table.contents')->rows(['status' => $status])
                        ->where('cid = ?', $draft['cid']));
                }

                // 完成标记插件接口
                self::pluginHandle()->call('finishMark', $status, $page, $this);

                $markCount++;
            }

            unset($condition);
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $markCount > 0 ? _t('页面已经被标记为<strong>%s</strong>', $statusList[$status]) : _t('没有页面被标记'),
                $markCount > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 删除页面
     *
     * @throws DbException
     */
    public function deletePage()
    {
        $pages = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($pages as $page) {
            // 删除插件接口
            self::pluginHandle()->call('delete', $page, $this);
            $parent = $this->db->fetchObject($this->select()->where('cid = ?', $page))->parent;

            if ($this->delete($this->db->sql()->where('cid = ?', $page))) {
                /** 删除评论 */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $page));

                /** 解除附件关联 */
                $this->unAttach($page);

                /** 解除首页关联 */
                if ($this->options->frontPage == 'page:' . $page) {
                    $this->db->query($this->db->update('table.options')
                        ->rows(['value' => 'recent'])
                        ->where('name = ?', 'frontPage'));
                }

                /** 删除草稿 */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'revision')
                    ->limit(1));

                /** 删除自定义字段 */
                $this->deleteFields($page);

                if ($draft) {
                    $this->deleteContent($draft['cid'], false);
                    $this->deleteFields($draft['cid']);
                }

                // update parent
                $this->update(
                    ['parent' => $parent],
                    $this->db->sql()->where('parent = ?', $page)
                        ->where('type = ? OR type = ?', 'page', 'page_draft')
                );

                // 完成删除插件接口
                self::pluginHandle()->call('finishDelete', $page, $this);

                $deleteCount++;
            }
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('页面已经被删除') : _t('没有页面被删除'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 删除页面所属草稿
     *
     * @throws DbException
     */
    public function deletePageDraft()
    {
        $pages = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($pages as $page) {
            /** 删除草稿 */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'revision')
                ->limit(1));

            if ($draft) {
                $this->deleteContent($draft['cid'], false);
                $this->deleteFields($draft['cid']);
                $deleteCount++;
            }
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('草稿已经被删除') : _t('没有草稿被删除'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 页面排序
     *
     * @throws DbException
     */
    public function sortPage()
    {
        $pages = $this->request->filter('int')->getArray('cid');

        if ($pages) {
            foreach ($pages as $sort => $cid) {
                $this->db->query($this->db->update('table.contents')->rows(['order' => $sort + 1])
                    ->where('cid = ?', $cid));
            }
        }

        if (!$this->request->isAjax()) {
            /** 转向原页 */
            $this->response->goBack();
        } else {
            $this->response->throwJson(['success' => 1, 'message' => _t('页面排序已经完成')]);
        }
    }

    /**
     * @return $this
     * @throws DbException
     * @throws Exception
     */
    public function prepare(): self
    {
        return $this->prepareEdit('page', true, _t('页面不存在'));
    }

    /**
     * 绑定动作
     *
     * @return void
     * @throws DbException
     * @throws Exception
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))
            ->prepare()->writePage();
        $this->on($this->request->is('do=delete'))->deletePage();
        $this->on($this->request->is('do=mark'))->markPage();
        $this->on($this->request->is('do=deleteDraft'))->deletePageDraft();
        $this->on($this->request->is('do=sort'))->sortPage();
        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * 获取网页标题
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        $this->prepare();

        if ($this->have()) {
            return _t('编辑 %s', $this->title);
        }

        if ($this->request->is('parent')) {
            $page = $this->db->fetchRow($this->select()
                ->where('table.contents.type = ? OR table.contents.type', 'page', 'page_draft')
                ->where('table.contents.cid = ?', $this->request->filter('int')->get('parent')));

            if (!empty($page)) {
                return _t('新增 %s 的子页面', $page['title']);
            }
        }

        throw new Exception(_t('页面不存在'), 404);
    }


    /**
     * @return int
     */
    public function getParent(): int
    {
        if ($this->request->is('parent')) {
            $parent = $this->request->filter('int')->get('parent');

            if (!$this->have() || $this->cid != $parent) {
                $parentPage = $this->db->fetchRow($this->select()
                    ->where('table.contents.type = ? OR table.contents.type = ?', 'page', 'page_draft')
                    ->where('table.contents.cid = ?', $parent));

                if (!empty($parentPage)) {
                    return $parent;
                }
            }
        }

        return 0;
    }

    /**
     * @return string
     */
    protected function getThemeFieldsHook(): string
    {
        return 'themePageFields';
    }
}
