<?php

namespace Widget\Contents\Post;

use Typecho\Common;
use Typecho\Widget\Exception;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Typecho\Db\Exception as DbException;
use Typecho\Date as TypechoDate;
use Widget\Contents\EditTrait;
use Widget\Contents\PrepareEditTrait;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑文章组件
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
     * @throws Exception|DbException
     */
    public function execute()
    {
        /** 必须为贡献者以上权限 */
        $this->user->pass('contributor');
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
            $trackback = array_filter(
                array_unique(preg_split("/(\r|\n|\r\n)/", trim($this->request->get('trackback', ''))))
            );
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
            $draftId = $this->save($contents);

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
                    'draftId' => $draftId
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
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'revision')
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
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'revision')
                    ->limit(1));

                /** 删除自定义字段 */
                $this->deleteFields($post);

                if ($draft) {
                    $this->deleteContent($draft['cid']);
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
                ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'revision')
                ->limit(1));

            if ($draft) {
                $this->deleteContent($draft['cid']);
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
     * @return $this
     * @throws DbException
     * @throws Exception
     */
    public function prepare(): self
    {
        return $this->prepareEdit('post', true, _t('文章不存在'));
    }

    /**
     * 绑定动作
     *
     * @throws Exception|DbException
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))
            ->prepare()->writePost();
        $this->on($this->request->is('do=delete'))->deletePost();
        $this->on($this->request->is('do=mark'))->markPost();
        $this->on($this->request->is('do=deleteDraft'))->deletePostDraft();

        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * @return string
     */
    protected function getThemeFieldsHook(): string
    {
        return 'themePostFields';
    }
}
