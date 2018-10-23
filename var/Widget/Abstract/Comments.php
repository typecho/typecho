<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 评论基类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Abstract_Comments extends Widget_Abstract
{
    /**
     * 获取当前内容结构
     *
     * @access protected
     * @return array
     */
    protected function ___parentContent()
    {
        return $this->db->fetchRow($this->widget('Widget_Abstract_Contents')->select()
        ->where('table.contents.cid = ?', $this->cid)
        ->limit(1), array($this->widget('Widget_Abstract_Contents'), 'filter'));
    }

    /**
     * 获取当前评论标题
     *
     * @access protected
     * @return string
     */
    protected function ___title()
    {
        return $this->parentContent['title'];
    }

    /**
     * 获取当前评论链接
     *
     * @access protected
     * @return string
     */
    protected function ___permalink()
    {

        if ($this->options->commentsPageBreak && 'approved' == $this->status) {
            
            $coid = $this->coid;
            $parent = $this->parent;
            
            while ($parent > 0 && $this->options->commentsThreaded) {
                $parentRows = $this->db->fetchRow($this->db->select('parent')->from('table.comments')
                ->where('coid = ? AND status = ?', $parent, 'approved')->limit(1));
                
                if (!empty($parentRows)) {
                    $coid = $parent;
                    $parent = $parentRows['parent'];
                } else {
                    break;
                }
            }

            $select  = $this->db->select('coid', 'parent')
            ->from('table.comments')->where('cid = ? AND status = ?', $this->parentContent['cid'], 'approved')
            ->where('coid ' . ('DESC' == $this->options->commentsOrder ? '>=' : '<=') . ' ?', $coid)
            ->order('coid', Typecho_Db::SORT_ASC);

            if ($this->options->commentsShowCommentOnly) {
                $select->where('type = ?', 'comment');
            }
            
            $comments = $this->db->fetchAll($select);
            
            $commentsMap = array();
            $total = 0;
            
            foreach ($comments as $comment) {
                $commentsMap[$comment['coid']] = $comment['parent'];
                
                if (0 == $comment['parent'] || !isset($commentsMap[$comment['parent']])) {
                    $total ++;
                }
            }

            $currentPage = ceil($total / $this->options->commentsPageSize);
            
            $pageRow = array('permalink' => $this->parentContent['pathinfo'], 'commentPage' => $currentPage);
            return Typecho_Router::url('comment_page',
                        $pageRow, $this->options->index) . '#' . $this->theId;
        }
        
        return $this->parentContent['permalink'] . '#' . $this->theId;
    }

    /**
     * 获取当前评论内容
     *
     * @access protected
     * @return string
     */
    protected function ___content()
    {
        $text = $this->parentContent['hidden'] ? _t('内容被隐藏') : $this->text;

        $text = $this->pluginHandle(__CLASS__)->trigger($plugged)->content($text, $this);
        if (!$plugged) {
            $text = $this->options->commentsMarkdown ? $this->markdown($text)
                : $this->autoP($text);
        }

        $text = $this->pluginHandle(__CLASS__)->contentEx($text, $this);
        return Typecho_Common::stripTags($text, '<p><br>' . $this->options->commentsHTMLTagAllowed);
    }

    /**
     * 输出词义化日期
     *
     * @access protected
     * @return string
     */
    protected function ___dateWord()
    {
        return $this->date->word();
    }

    /**
     * 锚点id
     *
     * @access protected
     * @return string
     */
    protected function ___theId()
    {
        return $this->type . '-' . $this->coid;
    }

    /**
     * 获取查询对象
     *
     * @access public
     * @return Typecho_Db_Query
     */
    public function select()
    {
        return $this->db->select('table.comments.coid', 'table.comments.cid', 'table.comments.author', 'table.comments.mail', 'table.comments.url', 'table.comments.ip',
        'table.comments.authorId', 'table.comments.ownerId', 'table.comments.agent', 'table.comments.text', 'table.comments.type', 'table.comments.status', 'table.comments.parent', 'table.comments.created')
        ->from('table.comments');
    }

    /**
     * 增加评论
     *
     * @access public
     * @param array $comment 评论结构数组
     * @return integer
     */
    public function insert(array $comment)
    {
        /** 构建插入结构 */
        $insertStruct = array(
            'cid'       =>  $comment['cid'],
            'created'   =>  empty($comment['created']) ? $this->options->time : $comment['created'],
            'author'    =>  !isset($comment['author']) || strlen($comment['author']) === 0 ? NULL : $comment['author'],
            'authorId'  =>  empty($comment['authorId']) ? 0 : $comment['authorId'],
            'ownerId'   =>  empty($comment['ownerId']) ? 0 : $comment['ownerId'],
            'mail'      =>  !isset($comment['mail']) || strlen($comment['mail']) === 0 ? NULL : $comment['mail'],
            'url'       =>  !isset($comment['url']) || strlen($comment['url']) === 0 ? NULL : $comment['url'],
            'ip'        =>  !isset($comment['ip']) || strlen($comment['ip']) === 0 ? $this->request->getIp() : $comment['ip'],
            'agent'     =>  !isset($comment['agent']) || strlen($comment['agent']) === 0 ? $_SERVER["HTTP_USER_AGENT"] : $comment['agent'],
            'text'      =>  !isset($comment['text']) || strlen($comment['text']) === 0 ? NULL : $comment['text'],
            'type'      =>  empty($comment['type']) ? 'comment' : $comment['type'],
            'status'    =>  empty($comment['status']) ? 'approved' : $comment['status'],
            'parent'    =>  empty($comment['parent']) ? 0 : $comment['parent'],
        );

        if (!empty($comment['coid'])) {
            $insertStruct['coid'] = $comment['coid'];
        }

        /** 过长的客户端字符串要截断 */
        if (Typecho_Common::strLen($insertStruct['agent']) > 511) {
            $insertStruct['agent'] = Typecho_Common::subStr($insertStruct['agent'], 0, 511, '');
        }

        /** 首先插入部分数据 */
        $insertId = $this->db->query($this->db->insert('table.comments')->rows($insertStruct));

        /** 更新评论数 */
        $num = $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))->from('table.comments')
        ->where('status = ? AND cid = ?', 'approved', $comment['cid']))->num;

        $this->db->query($this->db->update('table.contents')->rows(array('commentsNum' => $num))
        ->where('cid = ?', $comment['cid']));

        return $insertId;
    }

    /**
     * 更新评论
     *
     * @access public
     * @param array $comment 评论结构数组
     * @param Typecho_Db_Query $condition 查询对象
     * @return integer
     */
    public function update(array $comment, Typecho_Db_Query $condition)
    {
        /** 获取内容主键 */
        $updateCondition = clone $condition;
        $updateComment = $this->db->fetchObject($condition->select('cid')->from('table.comments')->limit(1));

        if ($updateComment) {
            $cid = $updateComment->cid;
        } else {
            return false;
        }

        /** 构建插入结构 */
        $preUpdateStruct = array(
            'author'    =>  !isset($comment['author']) || strlen($comment['author']) === 0 ? NULL : $comment['author'],
            'mail'      =>  !isset($comment['mail']) || strlen($comment['mail']) === 0 ? NULL : $comment['mail'],
            'url'       =>  !isset($comment['url']) || strlen($comment['url']) === 0 ? NULL : $comment['url'],
            'text'      =>  !isset($comment['text']) || strlen($comment['text']) === 0 ? NULL : $comment['text'],
            'status'    =>  empty($comment['status']) ? 'approved' : $comment['status'],
        );

        $updateStruct = array();
        foreach ($comment as $key => $val) {
            if ((array_key_exists($key, $preUpdateStruct))) {
                $updateStruct[$key] = $preUpdateStruct[$key];
            }
        }
        
        /** 更新创建时间 */
        if (!empty($comment['created'])) {
            $updateStruct['created'] = $comment['created'];
        }

        /** 更新评论数据 */
        $updateRows = $this->db->query($updateCondition->update('table.comments')->rows($updateStruct));

        /** 更新评论数 */
        $num = $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))->from('table.comments')
        ->where('status = ? AND cid = ?', 'approved', $cid))->num;

        $this->db->query($this->db->update('table.contents')->rows(array('commentsNum' => $num))
        ->where('cid = ?', $cid));

        return $updateRows;
    }

    /**
     * 删除数据
     *
     * @access public
     * @param Typecho_Db_Query $condition 查询对象
     * @return integer
     */
    public function delete(Typecho_Db_Query $condition)
    {
        /** 获取内容主键 */
        $deleteCondition = clone $condition;
        $deleteComment = $this->db->fetchObject($condition->select('cid')->from('table.comments')->limit(1));

        if ($deleteComment) {
            $cid = $deleteComment->cid;
        } else {
            return false;
        }

        /** 删除评论数据 */
        $deleteRows = $this->db->query($deleteCondition->delete('table.comments'));

        /** 更新评论数 */
        $num = $this->db->fetchObject($this->db->select(array('COUNT(coid)' => 'num'))->from('table.comments')
        ->where('status = ? AND cid = ?', 'approved', $cid))->num;

        $this->db->query($this->db->update('table.contents')->rows(array('commentsNum' => $num))
        ->where('cid = ?', $cid));

        return $deleteRows;
    }

    /**
     * 评论是否可以被修改
     *
     * @access public
     * @param Typecho_Db_Query $condition 条件
     * @return mixed
     */
    public function commentIsWriteable(Typecho_Db_Query $condition = NULL)
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
     * 按照条件计算评论数量
     *
     * @access public
     * @param Typecho_Db_Query $condition 查询对象
     * @return integer
     */
    public function size(Typecho_Db_Query $condition)
    {
        return $this->db->fetchObject($condition->select(array('COUNT(coid)' => 'num'))->from('table.comments'))->num;
    }

    /**
     * 通用过滤器
     *
     * @access public
     * @param array $value 需要过滤的行数据
     * @return array
     */
    public function filter(array $value)
    {
        $value['date'] = new Typecho_Date($value['created']);
        $value = $this->pluginHandle(__CLASS__)->filter($value, $this);
        return $value;
    }

    /**
     * 将每行的值压入堆栈
     *
     * @access public
     * @param array $value 每行的值
     * @return array
     */
    public function push(array $value)
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * 输出文章发布日期
     *
     * @access public
     * @param string $format 日期格式
     * @return void
     */
    public function date($format = NULL)
    {
        echo $this->date->format(empty($format) ? $this->options->commentDateFormat : $format);
    }

    /**
     * 输出作者相关
     *
     * @access public
     * @param boolean $autoLink 是否自动加上链接
     * @param boolean $noFollow 是否加上nofollow标签
     * @return void
     */
    public function author($autoLink = NULL, $noFollow = NULL)
    {
        $autoLink = (NULL === $autoLink) ? $this->options->commentsShowUrl : $autoLink;
        $noFollow = (NULL === $noFollow) ? $this->options->commentsUrlNofollow : $noFollow;

        if ($this->url && $autoLink) {
            echo '<a href="' , $this->url , '"' , ($noFollow ? ' rel="external nofollow"' : NULL) , '>' , $this->author , '</a>';
        } else {
            echo $this->author;
        }
    }

    /**
     * 调用gravatar输出用户头像
     *
     * @access public
     * @param integer $size 头像尺寸
     * @param string $default 默认输出头像
     * @return void
     */
    public function gravatar($size = 32, $default = NULL)
    {
        if ($this->options->commentsAvatar && 'comment' == $this->type) {
            $rating = $this->options->commentsAvatarRating;
            
            $this->pluginHandle(__CLASS__)->trigger($plugged)->gravatar($size, $rating, $default, $this);
            if (!$plugged) {
                $url = Typecho_Common::gravatarUrl($this->mail, $size, $rating, $default, $this->request->isSecure());
                echo '<img class="avatar" src="' . $url . '" alt="' .
                $this->author . '" width="' . $size . '" height="' . $size . '" />';
            }
        }
    }

    /**
     * 输出评论摘要
     *
     * @access public
     * @param integer $length 摘要截取长度
     * @param string $trim 摘要后缀
     * @return void
     */
    public function excerpt($length = 100, $trim = '...')
    {
        echo Typecho_Common::subStr(strip_tags($this->content), 0, $length, $trim);
    }

    /**
     * autoP 
     * 
     * @param mixed $text 
     * @access public
     * @return string
     */
    public function autoP($text)
    {
        $html = $this->pluginHandle(__CLASS__)->trigger($parsed)->autoP($text);

        if (!$parsed) {
            static $parser;

            if (empty($parser)) {
                $parser = new AutoP();
            }

            $html = $parser->parse($text);
        }

        return $html;
    }

    /**
     * markdown  
     * 
     * @param mixed $text 
     * @access public
     * @return string
     */
    public function markdown($text)
    {
        $html = $this->pluginHandle(__CLASS__)->trigger($parsed)->markdown($text);

        if (!$parsed) {
            $html = Markdown::convert($text);
        }

        return $html;
    }
}

