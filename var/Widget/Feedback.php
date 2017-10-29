<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 反馈提交
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 反馈提交组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Feedback extends Widget_Abstract_Comments implements Widget_Interface_Do
{
    /**
     * 内容对象
     *
     * @access private
     * @var Widget_Archive
     */
    private $_content;

    /**
     * 评论处理函数
     *
     * @throws Typecho_Widget_Exception
     * @throws Exception
     * @throws Typecho_Exception
     */
    private function comment()
    {
        // 使用安全模块保护
        $this->security->enable($this->options->commentsAntiSpam);
        $this->security->protect();

        $comment = array(
            'cid'       =>  $this->_content->cid,
            'created'   =>  $this->options->time,
            'agent'     =>  $this->request->getAgent(),
            'ip'        =>  $this->request->getIp(),
            'ownerId'   =>  $this->_content->author->uid,
            'type'      =>  'comment',
            'status'    =>  !$this->_content->allow('edit') && $this->options->commentsRequireModeration ? 'waiting' : 'approved'
        );

        /** 判断父节点 */
        if ($parentId = $this->request->filter('int')->get('parent')) {
            if ($this->options->commentsThreaded && ($parent = $this->db->fetchRow($this->db->select('coid', 'cid')->from('table.comments')
            ->where('coid = ?', $parentId))) && $this->_content->cid == $parent['cid']) {
                $comment['parent'] = $parentId;
            } else {
                throw new Typecho_Widget_Exception(_t('父级评论不存在'));
            }
        }

        //检验格式
        $validator = new Typecho_Validate();
        $validator->addRule('author', 'required', _t('必须填写用户名'));
        $validator->addRule('author', 'xssCheck', _t('请不要在用户名中使用特殊字符'));
        $validator->addRule('author', array($this, 'requireUserLogin'), _t('您所使用的用户名已经被注册,请登录后再次提交'));
        $validator->addRule('author', 'maxLength', _t('用户名最多包含200个字符'), 200);

        if ($this->options->commentsRequireMail && !$this->user->hasLogin()) {
            $validator->addRule('mail', 'required', _t('必须填写电子邮箱地址'));
        }

        $validator->addRule('mail', 'email', _t('邮箱地址不合法'));
        $validator->addRule('mail', 'maxLength', _t('电子邮箱最多包含200个字符'), 200);

        if ($this->options->commentsRequireUrl && !$this->user->hasLogin()) {
            $validator->addRule('url', 'required', _t('必须填写个人主页'));
        }
        $validator->addRule('url', 'url', _t('个人主页地址格式错误'));
        $validator->addRule('url', 'maxLength', _t('个人主页地址最多包含200个字符'), 200);

        $validator->addRule('text', 'required', _t('必须填写评论内容'));

        $comment['text'] = $this->request->text;

        /** 对一般匿名访问者,将用户数据保存一个月 */
        if (!$this->user->hasLogin()) {
            /** Anti-XSS */
            $comment['author'] = $this->request->filter('trim')->author;
            $comment['mail'] = $this->request->filter('trim')->mail;
            $comment['url'] = $this->request->filter('trim')->url;

            /** 修正用户提交的url */
            if (!empty($comment['url'])) {
                $urlParams = parse_url($comment['url']);
                if (!isset($urlParams['scheme'])) {
                    $comment['url'] = 'http://' . $comment['url'];
                }
            }

            $expire = $this->options->time + $this->options->timezone + 30*24*3600;
            Typecho_Cookie::set('__typecho_remember_author', $comment['author'], $expire);
            Typecho_Cookie::set('__typecho_remember_mail', $comment['mail'], $expire);
            Typecho_Cookie::set('__typecho_remember_url', $comment['url'], $expire);
        } else {
            $comment['author'] = $this->user->screenName;
            $comment['mail'] = $this->user->mail;
            $comment['url'] = $this->user->url;

            /** 记录登录用户的id */
            $comment['authorId'] = $this->user->uid;
        }
        
        /** 评论者之前须有评论通过了审核 */
        if (!$this->options->commentsRequireModeration && $this->options->commentsWhitelist) {
            if ($this->size($this->select()->where('author = ? AND mail = ? AND status = ?', $comment['author'], $comment['mail'], 'approved'))) {
                $comment['status'] = 'approved';
            } else {
                $comment['status'] = 'waiting';
            }
        }

        if ($error = $validator->run($comment)) {
            /** 记录文字 */
            Typecho_Cookie::set('__typecho_remember_text', $comment['text']);
            throw new Typecho_Widget_Exception(implode("\n", $error));
        }

        /** 生成过滤器 */
        try {
            $comment = $this->pluginHandle()->comment($comment, $this->_content);
        } catch (Typecho_Exception $e) {
            Typecho_Cookie::set('__typecho_remember_text', $comment['text']);
            throw $e;
        }

        /** 添加评论 */
        $commentId = $this->insert($comment);
        Typecho_Cookie::delete('__typecho_remember_text');
        $this->db->fetchRow($this->select()->where('coid = ?', $commentId)
        ->limit(1), array($this, 'push'));

        /** 评论完成接口 */
        $this->pluginHandle()->finishComment($this);

        $this->response->goBack('#' . $this->theId);
    }

    /**
     * 引用处理函数
     *
     * @access private
     * @return void
     */
    private function trackback()
    {
        /** 如果不是POST方法 */
        if (!$this->request->isPost() || $this->request->getReferer()) {
            $this->response->redirect($this->_content->permalink);
        }

        /** 如果库中已经存在当前ip为spam的trackback则直接拒绝 */
        if ($this->size($this->select()
        ->where('status = ? AND ip = ?', 'spam', $this->request->getIp())) > 0) {
            /** 使用404告诉机器人 */
            throw new Typecho_Widget_Exception(_t('找不到内容'), 404);
        }

        $trackback = array(
            'cid'       =>  $this->_content->cid,
            'created'   =>  $this->options->time,
            'agent'     =>  $this->request->getAgent(),
            'ip'        =>  $this->request->getIp(),
            'ownerId'   =>  $this->_content->author->uid,
            'type'      =>  'trackback',
            'status'    =>  $this->options->commentsRequireModeration ? 'waiting' : 'approved'
        );

        $trackback['author'] = $this->request->filter('trim')->blog_name;
        $trackback['url'] = $this->request->filter('trim')->url;
        $trackback['text'] = $this->request->excerpt;

        //检验格式
        $validator = new Typecho_Validate();
        $validator->addRule('url', 'required', 'We require all Trackbacks to provide an url.')
        ->addRule('url', 'url', 'Your url is not valid.')
        ->addRule('url', 'maxLength', 'Your url is not valid.', 200)
        ->addRule('text', 'required', 'We require all Trackbacks to provide an excerption.')
        ->addRule('author', 'required', 'We require all Trackbacks to provide an blog name.')
        ->addRule('author', 'xssCheck', 'Your blog name is not valid.')
        ->addRule('author', 'maxLength', 'Your blog name is not valid.', 200);

        $validator->setBreak();
        if ($error = $validator->run($trackback)) {
            $message = array('success' => 1, 'message' => current($error));
            $this->response->throwXml($message);
        }

        /** 截取长度 */
        $trackback['text'] = Typecho_Common::subStr($trackback['text'], 0, 100, '[...]');

        /** 如果库中已经存在重复url则直接拒绝 */
        if ($this->size($this->select()
        ->where('cid = ? AND url = ? AND type <> ?', $this->_content->cid, $trackback['url'], 'comment')) > 0) {
            /** 使用403告诉机器人 */
            throw new Typecho_Widget_Exception(_t('禁止重复提交'), 403);
        }

        /** 生成过滤器 */
        $trackback = $this->pluginHandle()->trackback($trackback, $this->_content);

        /** 添加引用 */
        $this->insert($trackback);

        /** 评论完成接口 */
        $this->pluginHandle()->finishTrackback($this);

        /** 返回正确 */
        $this->response->throwXml(array('success' => 0, 'message' => 'Trackback has registered.'));
    }

    /**
     * 过滤评论内容
     *
     * @access public
     * @param string $text 评论内容
     * @return string
     */
    public function filterText($text)
    {
        $text = str_replace("\r", '', trim($text));
        $text = preg_replace("/\n{2,}/", "\n\n", $text);

        return Typecho_Common::removeXSS(Typecho_Common::stripTags(
        $text, $this->options->commentsHTMLTagAllowed));
    }

    /**
     * 对已注册用户的保护性检测
     *
     * @access public
     * @param string $userName 用户名
     * @return void
     */
    public function requireUserLogin($userName)
    {
        if ($this->user->hasLogin() && $this->user->screenName != $userName) {
            /** 当前用户名与提交者不匹配 */
            return false;
        } else if (!$this->user->hasLogin() && $this->db->fetchRow($this->db->select('uid')
        ->from('table.users')->where('screenName = ? OR name = ?', $userName, $userName)->limit(1))) {
            /** 此用户名已经被注册 */
            return false;
        }

        return true;
    }

    /**
     * 初始化函数
     *
     * @access public
     * @return void
     * @throws Typecho_Widget_Exception
     */
    public function action()
    {
        /** 回调方法 */
        $callback = $this->request->type;
        $this->_content = Typecho_Router::match($this->request->permalink);

        /** 判断内容是否存在 */
        if (false !== $this->_content && $this->_content instanceof Widget_Archive &&
        $this->_content->have() && $this->_content->is('single') &&
        in_array($callback, array('comment', 'trackback'))) {

            /** 如果文章不允许反馈 */
            if ('comment' == $callback) {
                /** 评论关闭 */
                if (!$this->_content->allow('comment')) {
                    throw new Typecho_Widget_Exception(_t('对不起,此内容的反馈被禁止.'), 403);
                }
                
                /** 检查来源 */
                if ($this->options->commentsCheckReferer && 'false' != $this->parameter->checkReferer) {
                    $referer = $this->request->getReferer();

                    if (empty($referer)) {
                        throw new Typecho_Widget_Exception(_t('评论来源页错误.'), 403);
                    }

                    $refererPart = parse_url($referer);
                    $currentPart = parse_url($this->_content->permalink);

                    if ($refererPart['host'] != $currentPart['host'] ||
                    0 !== strpos($refererPart['path'], $currentPart['path'])) {
                        
                        //自定义首页支持
                        if ('page:' . $this->_content->cid == $this->options->frontPage) {
                            $currentPart = parse_url(rtrim($this->options->siteUrl, '/') . '/');
                            
                            if ($refererPart['host'] != $currentPart['host'] ||
                            0 !== strpos($refererPart['path'], $currentPart['path'])) {
                                throw new Typecho_Widget_Exception(_t('评论来源页错误.'), 403);
                            }
                        } else {
                            throw new Typecho_Widget_Exception(_t('评论来源页错误.'), 403);
                        }
                    }
                }

                /** 检查ip评论间隔 */
                if (!$this->user->pass('editor', true) && $this->_content->authorId != $this->user->uid &&
                    $this->options->commentsPostIntervalEnable) {

                    $latestComment = $this->db->fetchRow($this->db->select('created')->from('table.comments')
                    ->where('cid = ? AND ip = ?', $this->_content->cid, $this->request->getIp())
                    ->order('created', Typecho_Db::SORT_DESC)
                    ->limit(1));

                    if ($latestComment && ($this->options->time - $latestComment['created'] > 0 &&
                    $this->options->time - $latestComment['created'] < $this->options->commentsPostInterval)) {
                        throw new Typecho_Widget_Exception(_t('对不起, 您的发言过于频繁, 请稍侯再次发布.'), 403);
                    }
                }
            }

            /** 如果文章不允许引用 */
            if ('trackback' == $callback && !$this->_content->allow('ping')) {
                throw new Typecho_Widget_Exception(_t('对不起,此内容的引用被禁止.'), 403);
            }

            /** 调用函数 */
            $this->$callback();
        } else {
            throw new Typecho_Widget_Exception(_t('找不到内容'), 404);
        }
    }
}
