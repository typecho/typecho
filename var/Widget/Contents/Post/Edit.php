<?php

namespace Widget\Contents\Post;

use Typecho\Common;
use Typecho\Config;
use Typecho\Validate;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Typecho\Db\Exception as DbException;
use Typecho\Date as TypechoDate;
use Widget\Contents\EditTrait;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑文章组件
 *
 * @property-read array|null $draft
 */
class Edit extends Contents implements ActionInterface
{
    use EditTrait;

    /**
     * 自定义字段的hook名称
     *
     * @var string
     */
    protected string $themeCustomFieldsHook = 'themePostFields';

    /**
     * 执行函数
     *
     * @throws Exception|DbException
     */
    public function execute()
    {
        /** 必须为贡献者以上权限 */
        $this->user->pass('contributor');
    }

    /**
     * 获取网页标题
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('编辑 %s', $this->title);
    }

    /**
     * 发布文章
     */
    public function writePost()
    {
        $contents = $this->request->from(
            'password',
            'allowComment',
            'allowPing',
            'allowFeed',
            'slug',
            'tags',
            'text',
            'visibility'
        );

        $contents['category'] = $this->request->getArray('category');
        $contents['title'] = $this->request->get('title', _t('未命名文档'));
        $contents['created'] = $this->getCreated();

        if ($this->request->is('markdown=1') && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = self::pluginHandle()->call('write', $contents, $this);

        if ($this->request->is('do=publish')) {
            /** 重新发布已经存在的文章 */
            $contents['type'] = 'post';
            $this->publish($contents);

            // 完成发布插件接口
            self::pluginHandle()->call('finishPublish', $contents, $this);

            /** 发送ping */
            $trackback = array_filter(array_unique(preg_split("/(\r|\n|\r\n)/", trim($this->request->get('trackback')))));
            Service::alloc()->sendPing($this, $trackback);

            /** 设置提示信息 */
            Notice::alloc()->set('post' == $this->type ?
                _t('文章 "<a href="%s">%s</a>" 已经发布', $this->permalink, $this->title) :
                _t('文章 "%s" 等待审核', $this->title), 'success');

            /** 设置高亮 */
            Notice::alloc()->highlight($this->theId);

            /** 获取页面偏移 */
            $pageQuery = $this->getPageOffsetQuery($this->cid);

            /** 页面跳转 */
            $this->response->redirect(Common::url('manage-posts.php?' . $pageQuery, $this->options->adminUrl));
        } else {
            /** 保存文章 */
            $contents['type'] = 'post_draft';
            $this->save($contents);

            // 完成保存插件接口
            self::pluginHandle()->call('finishSave', $contents, $this);

            /** 设置高亮 */
            Notice::alloc()->highlight($this->cid);

            if ($this->request->isAjax()) {
                $created = new TypechoDate();
                $this->response->throwJson([
                    'success' => 1,
                    'time'    => $created->format('H:i:s A'),
                    'cid'     => $this->cid,
                    'draftId' => $this->draft['cid']
                ]);
            } else {
                /** 设置提示信息 */
                Notice::alloc()->set(_t('草稿 "%s" 已经被保存', $this->title), 'success');

                /** 返回原页面 */
                $this->response->redirect(Common::url('write-post.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * 发布内容
     *
     * @param array $contents 内容结构
     * @throws DbException|Exception
     */
    protected function publish(array $contents)
    {
        /** 发布内容, 检查是否具有直接发布的权限 */
        $this->checkStatus($contents);

        /** 真实的内容id */
        $realId = 0;

        /** 是否是从草稿状态发布 */
        $isDraftToPublish = ('post_draft' == $this->type || 'page_draft' == $this->type);

        $isBeforePublish = ('publish' == $this->status);
        $isAfterPublish = ('publish' == $contents['status']);

        /** 重新发布现有内容 */
        if ($this->have()) {

            /** 如果它本身不是草稿, 需要删除其草稿 */
            if (!$isDraftToPublish && $this->draft) {
                $cid = $this->draft['cid'];
                $this->deleteDraft($cid);
                $this->deleteFields($cid);
            }

            /** 直接将草稿状态更改 */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->cid))) {
                $realId = $this->cid;
            }
        } else {
            /** 发布一个新内容 */
            $realId = $this->insert($contents);
        }

        if ($realId > 0) {
            /** 插入分类 */
            if (array_key_exists('category', $contents)) {
                $this->setCategories(
                    $realId,
                    !empty($contents['category']) && is_array($contents['category'])
                        ? $contents['category'] : [$this->options->defaultCategory],
                    !$isDraftToPublish && $isBeforePublish,
                    $isAfterPublish
                );
            }

            /** 插入标签 */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], !$isDraftToPublish && $isBeforePublish, $isAfterPublish);
            }

            /** 同步附件 */
            $this->attach($realId);

            /** 保存自定义字段 */
            $this->applyFields($this->getFields(), $realId);

            $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $realId)->limit(1), [$this, 'push']);
        }
    }

    /**
     * 同步附件
     *
     * @param integer $cid 内容id
     * @throws DbException
     */
    protected function attach(int $cid)
    {
        $attachments = $this->request->getArray('attachment');
        if (!empty($attachments)) {
            foreach ($attachments as $key => $attachment) {
                $this->db->query($this->db->update('table.contents')->rows([
                    'parent' => $cid,
                    'status' => 'publish',
                    'order'  => $key + 1
                ])->where('cid = ? AND type = ?', $attachment, 'attachment'));
            }
        }
    }

    /**
     * getFields
     *
     * @return array
     */
    protected function getFields(): array
    {
        $fields = [];
        $fieldNames = $this->request->getArray('fieldNames');

        if (!empty($fieldNames)) {
            $data = [
                'fieldNames'  => $this->request->getArray('fieldNames'),
                'fieldTypes'  => $this->request->getArray('fieldTypes'),
                'fieldValues' => $this->request->getArray('fieldValues')
            ];
            foreach ($data['fieldNames'] as $key => $val) {
                $val = trim($val);

                if (0 == strlen($val)) {
                    continue;
                }

                $fields[$val] = [$data['fieldTypes'][$key], $data['fieldValues'][$key]];
            }
        }

        $customFields = $this->request->getArray('fields');
        foreach ($customFields as $key => $val) {
            $fields[$key] = [is_array($val) ? 'json' : 'str', $val];
        }

        return $fields;
    }

    /**
     * 获取页面偏移的URL Query
     *
     * @param integer $cid 文章id
     * @param string|null $status 状态
     * @return string
     * @throws DbException
     */
    protected function getPageOffsetQuery(int $cid, ?string $status = null): string
    {
        return 'page=' . $this->getPageOffset(
            'cid',
            $cid,
            'post',
            $status,
            $this->request->is('__typecho_all_posts=on') ? 0 : $this->user->uid
        );
    }

    /**
     * 保存内容
     *
     * @param array $contents 内容结构
     * @throws DbException|Exception
     */
    protected function save(array $contents)
    {
        /** 发布内容, 检查是否具有直接发布的权限 */
        $this->checkStatus($contents);

        /** 真实的内容id */
        $realId = 0;

        /** 如果草稿已经存在 */
        if ($this->draft) {

            /** 直接将草稿状态更改 */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->draft['cid']))) {
                $realId = $this->draft['cid'];
            }
        } else {
            if ($this->have()) {
                $contents['parent'] = $this->cid;
            }

            /** 发布一个新内容 */
            $realId = $this->insert($contents);

            if (!$this->have()) {
                $this->db->fetchRow(
                    $this->select()->where('table.contents.cid = ?', $realId)->limit(1),
                    [$this, 'push']
                );
            }
        }

        if ($realId > 0) {
            /** 插入分类 */
            if (array_key_exists('category', $contents)) {
                $this->setCategories($realId, !empty($contents['category']) && is_array($contents['category']) ?
                    $contents['category'] : [$this->options->defaultCategory], false, false);
            }

            /** 插入标签 */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], false, false);
            }

            /** 同步附件 */
            $this->attach($this->cid);

            /** 保存自定义字段 */
            $this->applyFields($this->getFields(), $realId);
        }
    }

    /**
     * 标记文章
     *
     * @throws DbException
     */
    public function markPost()
    {
        $status = $this->request->get('status');
        $statusList = [
            'publish' => _t('公开'),
            'private' => _t('私密'),
            'hidden'  => _t('隐藏'),
            'waiting' => _t('待审核')
        ];

        if (!isset($statusList[$status])) {
            $this->response->goBack();
        }

        $posts = $this->request->filter('int')->getArray('cid');
        $markCount = 0;

        foreach ($posts as $post) {
            // 标记插件接口
            self::pluginHandle()->call('mark', $status, $post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject)) {

                /** 标记状态 */
                $this->db->query($condition->update('table.contents')->rows(['status' => $status]));

                // 刷新Metas
                if ($postObject->type == 'post') {
                    $op = null;

                    if ($status == 'publish' && $postObject->status != 'publish') {
                        $op = '+';
                    } elseif ($status != 'publish' && $postObject->status == 'publish') {
                        $op = '-';
                    }

                    if (!empty($op)) {
                        $metas = $this->db->fetchAll(
                            $this->db->select()->from('table.relationships')->where('cid = ?', $post)
                        );
                        foreach ($metas as $meta) {
                            $this->db->query($this->db->update('table.metas')
                                ->expression('count', 'count ' . $op . ' 1')
                                ->where('mid = ? AND (type = ? OR type = ?)', $meta['mid'], 'category', 'tag'));
                        }
                    }
                }

                // 处理草稿
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                    ->limit(1));

                if (!empty($draft)) {
                    $this->db->query($this->db->update('table.contents')->rows(['status' => $status])
                        ->where('cid = ?', $draft['cid']));
                }

                // 完成标记插件接口
                self::pluginHandle()->call('finishMark', $status, $post, $this);

                $markCount++;
            }

            unset($condition);
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $markCount > 0 ? _t('文章已经被标记为<strong>%s</strong>', $statusList[$status]) : _t('没有文章被标记'),
                $markCount > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 删除文章
     *
     * @throws DbException
     */
    public function deletePost()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            // 删除插件接口
            self::pluginHandle()->call('delete', $post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject) && $this->delete($condition)) {

                /** 删除分类 */
                $this->setCategories($post, [], 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** 删除标签 */
                $this->setTags($post, null, 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** 删除评论 */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $post));

                /** 解除附件关联 */
                $this->unAttach($post);

                /** 删除草稿 */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                    ->limit(1));

                /** 删除自定义字段 */
                $this->deleteFields($post);

                if ($draft) {
                    $this->deleteDraft($draft['cid']);
                    $this->deleteFields($draft['cid']);
                }

                // 完成删除插件接口
                self::pluginHandle()->call('finishDelete', $post, $this);

                $deleteCount++;
            }

            unset($condition);
        }

        // 清理标签
        if ($deleteCount > 0) {
            Metas::alloc()->clearTags();
        }

        /** 设置提示信息 */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('文章已经被删除') : _t('没有文章被删除'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 取消附件关联
     *
     * @param integer $cid 内容id
     * @throws DbException
     */
    protected function unAttach(int $cid)
    {
        $this->db->query($this->db->update('table.contents')->rows(['parent' => 0, 'status' => 'publish'])
            ->where('parent = ? AND type = ?', $cid, 'attachment'));
    }

    /**
     * 删除文章所属草稿
     *
     * @throws DbException
     */
    public function deletePostDraft()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            /** 删除草稿 */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                ->limit(1));

            if ($draft) {
                $this->deleteDraft($draft['cid']);
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
     * 绑定动作
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))
            ->prepareEdit('post', true, _t('文章不存在'))
            ->writePost();
        $this->on($this->request->is('do=delete'))->deletePost();
        $this->on($this->request->is('do=mark'))->markPost();
        $this->on($this->request->is('do=deleteDraft'))->deletePostDraft();

        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * @param array $contents
     * @return void
     */
    private function checkStatus(array &$contents)
    {
        if ($this->user->pass('editor', true)) {
            if (empty($contents['visibility'])) {
                $contents['status'] = 'publish';
            } elseif (
                !in_array($contents['visibility'], ['private', 'waiting', 'publish', 'hidden'])
            ) {
                if (empty($contents['password']) || 'password' != $contents['visibility']) {
                    $contents['password'] = '';
                }
                $contents['status'] = 'publish';
            } else {
                $contents['status'] = $contents['visibility'];
                $contents['password'] = '';
            }
        } else {
            $contents['status'] = 'waiting';
            $contents['password'] = '';
        }
    }
}
