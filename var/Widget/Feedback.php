<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Db;
use Typecho\Router;
use Typecho\Validate;
use Typecho\Widget\Exception;
use Widget\Base\Comments;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 反馈提交组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Feedback extends Comments implements ActionInterface
{
    /**
     * 内容对象
     *
     * @access private
     * @var Archive
     */
    private $content;

    /**
     * 对已注册用户的保护性检测
     *
     * @param string $userName 用户名
     * @return bool
     * @throws \Typecho\Db\Exception
     */
    public function requireUserLogin(string $userName): bool
    {
        if ($this->user->hasLogin() && $this->user->screenName != $userName) {
            /** 当前用户名与提交者不匹配 */
            return false;
        } elseif (
            !$this->user->hasLogin() && $this->db->fetchRow($this->db->select('uid')
                ->from('table.users')->where('screenName = ? OR name = ?', $userName, $userName)->limit(1))
        ) {
            /** 此用户名已经被注册 */
            return false;
        }

        return true;
    }

    /**
     * 初始化函数
     *
     * @throws \Exception
     */
    public function action()
    {
        /** 回调方法 */
        $callback = $this->request->type;
        $this->content = Router::match($this->request->permalink);

        /** 判断内容是否存在 */
        if (
            $this->content instanceof Archive &&
            $this->content->have() && $this->content->is('single') &&
            in_array($callback, ['comment', 'trackback'])
        ) {

            /** 如果文章不允许反馈 */
            if ('comment' == $callback) {
                /** 评论关闭 */
                if (!$this->content->allow('comment')) {
                    throw new Exception(_t('对不起,此内容的反馈被禁止.'), 403);
                }

                /** 检查来源 */
                if ($this->options->commentsCheckReferer && 'false' != $this->parameter->checkReferer) {
                    $referer = $this->request->getReferer();

                    if (empty($referer)) {
                        throw new Exception(_t('评论来源页错误.'), 403);
                    }

                    $refererPart = parse_url($referer);
                    $currentPart = parse_url($this->content->permalink);

                    if (
                        $refererPart['host'] != $currentPart['host'] ||
                        0 !== strpos($refererPart['path'], $currentPart['path'])
                    ) {
                        //自定义首页支持
                        if ('page:' . $this->content->cid == $this->options->frontPage) {
                            $currentPart = parse_url(rtrim($this->options->siteUrl, '/') . '/');

                            if (
                                $refererPart['host'] != $currentPart['host'] ||
                                0 !== strpos($refererPart['path'], $currentPart['path'])
                            ) {
                                throw new Exception(_t('评论来源页错误.'), 403);
                            }
                        } else {
                            throw new Exception(_t('评论来源页错误.'), 403);
                        }
                    }
                }

                /** 检查ip评论间隔 */
                if (
                    !$this->user->pass('editor', true) && $this->content->authorId != $this->user->uid &&
                    $this->options->commentsPostIntervalEnable
                ) {
                    $latestComment = $this->db->fetchRow($this->db->select('created')->from('table.comments')
                        ->where('cid = ? AND ip = ?', $this->content->cid, $this->request->getIp())
                        ->order('created', Db::SORT_DESC)
                        ->limit(1));

                    if (
                        $latestComment && ($this->options->time - $latestComment['created'] > 0 &&
                            $this->options->time - $latestComment['created'] < $this->options->commentsPostInterval)
                    ) {
                        throw new Exception(_t('对不起, 您的发言过于频繁, 请稍侯再次发布.'), 403);
                    }
                }
            }

            /** 如果文章不允许引用 */
            if ('trackback' == $callback && !$this->content->allow('ping')) {
                throw new Exception(_t('对不起,此内容的引用被禁止.'), 403);
            }

            /** 调用函数 */
            $this->$callback();
        } else {
            throw new Exception(_t('找不到内容'), 404);
        }
    }

    /**
     * 评论处理函数
     *
     * @throws \Exception
     */
    private function comment()
    {
        // 使用安全模块保护
        $this->security->enable($this->options->commentsAntiSpam);
        $this->security->protect();

        $comment = [
            'cid' => $this->content->cid,
            'created' => $this->options->time,
            'agent' => $this->request->getAgent(),
            'ip' => $this->request->getIp(),
            'ownerId' => $this->content->author->uid,
            'type' => 'comment',
            'status' => !$this->content->allow('edit')
                && $this->options->commentsRequireModeration ? 'waiting' : 'approved'
        ];

        /** 判断父节点 */
        if ($parentId = $this->request->filter('int')->get('parent')) {
            if (
                $this->options->commentsThreaded
                && ($parent = $this->db->fetchRow($this->db->select('coid', 'cid')->from('table.comments')
                    ->where('coid = ?', $parentId))) && $this->content->cid == $parent['cid']
            ) {
                $comment['parent'] = $parentId;
            } else {
                throw new Exception(_t('父级评论不存在'));
            }
        }

        //检验格式
        $validator = new Validate();
        $validator->addRule('author', 'required', _t('必须填写用户名'));
        $validator->addRule('author', 'xssCheck', _t('请不要在用户名中使用特殊字符'));
        $validator->addRule('author', [$this, 'requireUserLogin'], _t('您所使用的用户名已经被注册,请登录后再次提交'));
        $validator->addRule('author', 'maxLength', _t('用户名最多包含150个字符'), 150);

        if ($this->options->commentsRequireMail && !$this->user->hasLogin()) {
            $validator->addRule('mail', 'required', _t('必须填写电子邮箱地址'));
        }

        $validator->addRule('mail', 'email', _t('邮箱地址不合法'));
        $validator->addRule('mail', 'maxLength', _t('电子邮箱最多包含150个字符'), 150);

        if ($this->options->commentsRequireUrl && !$this->user->hasLogin()) {
            $validator->addRule('url', 'required', _t('必须填写个人主页'));
        }
        $validator->addRule('url', 'url', _t('个人主页地址格式错误'));
        $validator->addRule('url', 'maxLength', _t('个人主页地址最多包含255个字符'), 255);

        $validator->addRule('text', 'required', _t('必须填写评论内容'));

        $comment['text'] = $this->request->text;

        /** 对一般匿名访问者,将用户数据保存一个月 */
        if (!$this->user->hasLogin()) {
            /** Anti-XSS */
            $comment['author'] = $this->request->filter('trim')->author;
            $comment['mail'] = $this->request->filter('trim')->mail;
            $comment['url'] = $this->request->filter('trim', 'url')->url;

            /** 修正用户提交的url */
            if (!empty($comment['url'])) {
                $urlParams = parse_url($comment['url']);
                if (!isset($urlParams['scheme'])) {
                    $comment['url'] = 'http://' . $comment['url'];
                }
            }

            $expire = 30 * 24 * 3600;
            Cookie::set('__typecho_remember_author', $comment['author'], $expire);
            Cookie::set('__typecho_remember_mail', $comment['mail'], $expire);
            Cookie::set('__typecho_remember_url', $comment['url'], $expire);
        } else {
            $comment['author'] = $this->user->screenName;
            $comment['mail'] = $this->user->mail;
            $comment['url'] = $this->user->url;

            /** 记录登录用户的id */
            $comment['authorId'] = $this->user->uid;
        }

        /** 评论者之前须有评论通过了审核 */
        if (!$this->options->commentsRequireModeration && $this->options->commentsWhitelist) {
            if (
                $this->size(
                    $this->select()->where(
                        'author = ? AND mail = ? AND status = ?',
                        $comment['author'],
                        $comment['mail'],
                        'approved'
                    )
                )
            ) {
                $comment['status'] = 'approved';
            } else {
                $comment['status'] = 'waiting';
            }
        }

        if ($error = $validator->run($comment)) {
            /** 记录文字 */
            Cookie::set('__typecho_remember_text', $comment['text']);
            throw new Exception(implode("\n", $error));
        }

        /** 生成过滤器 */
        try {
            $comment = self::pluginHandle()->comment($comment, $this->content);
        } catch (\Typecho\Exception $e) {
            Cookie::set('__typecho_remember_text', $comment['text']);
            throw $e;
        }

        /** 添加评论 */
        $commentId = $this->insert($comment);
        Cookie::delete('__typecho_remember_text');
        $this->db->fetchRow($this->select()->where('coid = ?', $commentId)
            ->limit(1), [$this, 'push']);

        /** 评论完成接口 */
        self::pluginHandle()->finishComment($this);

        $this->response->goBack('#' . $this->theId);
    }

    /**
     * 引用处理函数
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    private function trackback()
    {
        /** 如果不是POST方法 */
        if (!$this->request->isPost() || $this->request->getReferer()) {
            $this->response->redirect($this->content->permalink);
        }

        /** 如果库中已经存在当前ip为spam的trackback则直接拒绝 */
        if (
            $this->size($this->select()
                ->where('status = ? AND ip = ?', 'spam', $this->request->getIp())) > 0
        ) {
            /** 使用404告诉机器人 */
            throw new Exception(_t('找不到内容'), 404);
        }

        $trackback = [
            'cid' => $this->content->cid,
            'created' => $this->options->time,
            'agent' => $this->request->getAgent(),
            'ip' => $this->request->getIp(),
            'ownerId' => $this->content->author->uid,
            'type' => 'trackback',
            'status' => $this->options->commentsRequireModeration ? 'waiting' : 'approved'
        ];

        $trackback['author'] = $this->request->filter('trim')->blog_name;
        $trackback['url'] = $this->request->filter('trim', 'url')->url;
        $trackback['text'] = $this->request->excerpt;

        //检验格式
        $validator = new Validate();
        $validator->addRule('url', 'required', 'We require all Trackbacks to provide an url.')
            ->addRule('url', 'url', 'Your url is not valid.')
            ->addRule('url', 'maxLength', 'Your url is not valid.', 255)
            ->addRule('text', 'required', 'We require all Trackbacks to provide an excerption.')
            ->addRule('author', 'required', 'We require all Trackbacks to provide an blog name.')
            ->addRule('author', 'xssCheck', 'Your blog name is not valid.')
            ->addRule('author', 'maxLength', 'Your blog name is not valid.', 150);

        $validator->setBreak();
        if ($error = $validator->run($trackback)) {
            $message = ['success' => 1, 'message' => current($error)];
            $this->response->throwXml($message);
        }

        /** 截取长度 */
        $trackback['text'] = Common::subStr($trackback['text'], 0, 100, '[...]');

        /** 如果库中已经存在重复url则直接拒绝 */
        if (
            $this->size($this->select()
                ->where('cid = ? AND url = ? AND type <> ?', $this->content->cid, $trackback['url'], 'comment')) > 0
        ) {
            /** 使用403告诉机器人 */
            throw new Exception(_t('禁止重复提交'), 403);
        }

        /** 生成过滤器 */
        $trackback = self::pluginHandle()->trackback($trackback, $this->content);

        /** 添加引用 */
        $this->insert($trackback);

        /** 评论完成接口 */
        self::pluginHandle()->finishTrackback($this);

        /** 返回正确 */
        $this->response->throwXml(['success' => 0, 'message' => 'Trackback has registered.']);
    }
}
