<?php

namespace Widget\Comments;

use Typecho\Db\Exception;
use Typecho\Db\Query;
use Widget\Base\Comments;
use Widget\ActionInterface;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 评论编辑组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Comments implements ActionInterface
{
    /**
     * 标记为待审核
     */
    public function waitingComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'waiting')) {
                $updateRows++;
            }
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $updateRows > 0 ? _t('评论已经被标记为待审核') : _t('没有评论被标记为待审核'),
                $updateRows > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 评论是否可以被修改
     *
     * @param Query|null $condition 条件
     * @return bool
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function commentIsWriteable(?Query $condition = null): bool
    {
        if (empty($condition)) {
            if ($this->have() && ($this->user->pass('editor', true) || $this->ownerId == $this->user->uid)) {
                return true;
            }
        } else {
            $post = $this->db->fetchRow($condition->select('ownerId')->from('table.comments')->limit(1));

            if ($post && ($this->user->pass('editor', true) || $post['ownerId'] == $this->user->uid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 标记评论状态
     *
     * @param integer $coid 评论主键
     * @param string $status 状态
     * @return boolean
     * @throws Exception
     */
    private function mark(int $coid, string $status): bool
    {
        $comment = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($comment && $this->commentIsWriteable()) {
            /** 增加评论编辑插件接口 */
            self::pluginHandle()->call('mark', $comment, $this, $status);

            /** 不必更新的情况 */
            if ($status == $comment['status']) {
                return false;
            }

            /** 更新评论 */
            $this->db->query($this->db->update('table.comments')
                ->rows(['status' => $status])->where('coid = ?', $coid));

            /** 更新相关内容的评论数 */
            if ('approved' == $comment['status'] && 'approved' != $status) {
                $this->db->query($this->db->update('table.contents')
                    ->expression('commentsNum', 'commentsNum - 1')
                    ->where('cid = ? AND commentsNum > 0', $comment['cid']));
            } elseif ('approved' != $comment['status'] && 'approved' == $status) {
                $this->db->query($this->db->update('table.contents')
                    ->expression('commentsNum', 'commentsNum + 1')->where('cid = ?', $comment['cid']));
            }

            return true;
        }

        return false;
    }

    /**
     * 标记为垃圾
     *
     * @throws Exception
     */
    public function spamComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'spam')) {
                $updateRows++;
            }
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $updateRows > 0 ? _t('评论已经被标记为垃圾') : _t('没有评论被标记为垃圾'),
                $updateRows > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 标记为展现
     *
     * @throws Exception
     */
    public function approvedComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'approved')) {
                $updateRows++;
            }
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $updateRows > 0 ? _t('评论已经被通过') : _t('没有评论被通过'),
                $updateRows > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 删除评论
     *
     * @throws Exception
     */
    public function deleteComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $deleteRows = 0;

        foreach ($comments as $coid) {
            $comment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

            if ($comment && $this->commentIsWriteable()) {
                self::pluginHandle()->call('delete', $comment, $this);

                /** 删除评论 */
                $this->db->query($this->db->delete('table.comments')->where('coid = ?', $coid));

                /** 更新相关内容的评论数 */
                if ('approved' == $comment['status']) {
                    $this->db->query($this->db->update('table.contents')
                        ->expression('commentsNum', 'commentsNum - 1')->where('cid = ?', $comment['cid']));
                }

                self::pluginHandle()->call('finishDelete', $comment, $this);

                $deleteRows++;
            }
        }

        if ($this->request->isAjax()) {
            if ($deleteRows > 0) {
                $this->response->throwJson([
                    'success' => 1,
                    'message' => _t('删除评论成功')
                ]);
            } else {
                $this->response->throwJson([
                    'success' => 0,
                    'message' => _t('删除评论失败')
                ]);
            }
        } else {
            /** 设置提示信息 */
            Notice::alloc()
                ->set(
                    $deleteRows > 0 ? _t('评论已经被删除') : _t('没有评论被删除'),
                    $deleteRows > 0 ? 'success' : 'notice'
                );

            /** 返回原网页 */
            $this->response->goBack();
        }
    }

    /**
     * 删除所有垃圾评论
     *
     * @throws Exception
     */
    public function deleteSpamComment()
    {
        $deleteQuery = $this->db->delete('table.comments')->where('status = ?', 'spam');
        if (!$this->request->is('__typecho_all_comments=on') || !$this->user->pass('editor', true)) {
            $deleteQuery->where('ownerId = ?', $this->user->uid);
        }

        if ($this->request->is('cid')) {
            $deleteQuery->where('cid = ?', $this->request->get('cid'));
        }

        $deleteRows = $this->db->query($deleteQuery);

        /** 设置提示信息 */
        Notice::alloc()->set(
            $deleteRows > 0 ? _t('所有垃圾评论已经被删除') : _t('没有垃圾评论被删除'),
            $deleteRows > 0 ? 'success' : 'notice'
        );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 获取可编辑的评论
     *
     * @throws Exception
     */
    public function getComment()
    {
        $coid = $this->request->filter('int')->get('coid');
        $comment = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($comment && $this->commentIsWriteable()) {
            $this->response->throwJson([
                'success' => 1,
                'comment' => $comment
            ]);
        } else {
            $this->response->throwJson([
                'success' => 0,
                'message' => _t('获取评论失败')
            ]);
        }
    }

    /**
     * 编辑评论
     *
     * @return bool
     * @throws Exception
     */
    public function editComment(): bool
    {
        $coid = $this->request->filter('int')->get('coid');
        $commentSelect = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($commentSelect && $this->commentIsWriteable()) {
            $comment['text'] = $this->request->get('text');
            $comment['author'] = $this->request->filter('strip_tags', 'trim', 'xss')->get('author');
            $comment['mail'] = $this->request->filter('strip_tags', 'trim', 'xss')->get('mail');
            $comment['url'] = $this->request->filter('url')->get('url');

            if ($this->request->is('created')) {
                $comment['created'] = $this->request->filter('int')->get('created');
            }

            /** 评论插件接口 */
            $comment = self::pluginHandle()->filter('edit', $comment, $this);

            /** 更新评论 */
            $this->update($comment, $this->db->sql()->where('coid = ?', $coid));

            $updatedComment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $coid)->limit(1), [$this, 'push']);
            $updatedComment['content'] = $this->content;

            /** 评论插件接口 */
            self::pluginHandle()->call('finishEdit', $this);

            $this->response->throwJson([
                'success' => 1,
                'comment' => $updatedComment
            ]);
        }

        $this->response->throwJson([
            'success' => 0,
            'message' => _t('修评论失败')
        ]);
    }

    /**
     * 回复评论
     *
     * @throws Exception
     */
    public function replyComment()
    {
        $coid = $this->request->filter('int')->get('coid');
        $commentSelect = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($commentSelect && $this->commentIsWriteable()) {
            $comment = [
                'cid'      => $commentSelect['cid'],
                'created'  => $this->options->time,
                'agent'    => $this->request->getAgent(),
                'ip'       => $this->request->getIp(),
                'ownerId'  => $commentSelect['ownerId'],
                'authorId' => $this->user->uid,
                'type'     => 'comment',
                'author'   => $this->user->screenName,
                'mail'     => $this->user->mail,
                'url'      => $this->user->url,
                'parent'   => $coid,
                'text'     => $this->request->get('text'),
                'status'   => 'approved'
            ];

            /** 评论插件接口 */
            self::pluginHandle()->call('comment', $comment, $this);

            /** 回复评论 */
            $commentId = $this->insert($comment);

            $insertComment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $commentId)->limit(1), [$this, 'push']);
            $insertComment['content'] = $this->content;

            /** 评论完成接口 */
            self::pluginHandle()->call('finishComment', $this);

            $this->response->throwJson([
                'success' => 1,
                'comment' => $insertComment
            ]);
        }

        $this->response->throwJson([
            'success' => 0,
            'message' => _t('回复评论失败')
        ]);
    }

    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('contributor');
        $this->security->protect();
        $this->on($this->request->is('do=waiting'))->waitingComment();
        $this->on($this->request->is('do=spam'))->spamComment();
        $this->on($this->request->is('do=approved'))->approvedComment();
        $this->on($this->request->is('do=delete'))->deleteComment();
        $this->on($this->request->is('do=delete-spam'))->deleteSpamComment();
        $this->on($this->request->is('do=get&coid'))->getComment();
        $this->on($this->request->is('do=edit&coid'))->editComment();
        $this->on($this->request->is('do=reply&coid'))->replyComment();

        $this->response->redirect($this->options->adminUrl);
    }
}
