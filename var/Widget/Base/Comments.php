<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Date;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Router;
use Typecho\Router\ParamsDelegateInterface;
use Utils\AutoP;
use Utils\Markdown;
use Widget\Base;
use Widget\Contents\From;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 评论基类
 *
 * @property int $coid
 * @property int $cid
 * @property int $created
 * @property string author
 * @property int $authorId
 * @property int $ownerId
 * @property string $mail
 * @property string $url
 * @property string $ip
 * @property string $agent
 * @property string $text
 * @property string $type
 * @property string status
 * @property int $parent
 * @property int $commentPage
 * @property Date $date
 * @property string $dateWord
 * @property string $theId
 * @property Contents $parentContent
 * @property string $title
 * @property string $permalink
 * @property string $content
 */
class Comments extends Base implements QueryInterface, RowFilterInterface, PrimaryKeyInterface, ParamsDelegateInterface
{
    /**
     * @return string 获取主键
     */
    public function getPrimaryKey(): string
    {
        return 'coid';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getRouterParam(string $key): string
    {
        switch ($key) {
            case 'permalink':
                return $this->parentContent->path;
            case 'commentPage':
                return $this->commentPage;
            default:
                return '{' . $key . '}';
        }
    }

    /**
     * 增加评论
     *
     * @param array $rows 评论结构数组
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        /** 构建插入结构 */
        $insertStruct = [
            'cid'      => $rows['cid'],
            'created'  => empty($rows['created']) ? $this->options->time : $rows['created'],
            'author'   => Common::strBy($rows['author'] ?? null),
            'authorId' => empty($rows['authorId']) ? 0 : $rows['authorId'],
            'ownerId'  => empty($rows['ownerId']) ? 0 : $rows['ownerId'],
            'mail'     => Common::strBy($rows['mail'] ?? null),
            'url'      => Common::strBy($rows['url'] ?? null),
            'ip'       => Common::strBy($rows['ip'] ?? null, $this->request->getIp()),
            'agent'    => Common::strBy($rows['agent'] ?? null, $this->request->getAgent()),
            'text'     => Common::strBy($rows['text'] ?? null),
            'type'     => Common::strBy($rows['type'] ?? null, 'comment'),
            'status'   => Common::strBy($rows['status'] ?? null, 'approved'),
            'parent'   => empty($rows['parent']) ? 0 : $rows['parent'],
        ];

        if (!empty($rows['coid'])) {
            $insertStruct['coid'] = $rows['coid'];
        }

        /** 过长的客户端字符串要截断 */
        if (Common::strLen($insertStruct['agent']) > 511) {
            $insertStruct['agent'] = Common::subStr($insertStruct['agent'], 0, 511, '');
        }

        /** 首先插入部分数据 */
        $insertId = $this->db->query($this->db->insert('table.comments')->rows($insertStruct));

        /** 更新评论数 */
        $num = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])->from('table.comments')
            ->where('status = ? AND cid = ?', 'approved', $rows['cid']))->num;

        $this->db->query($this->db->update('table.contents')->rows(['commentsNum' => $num])
            ->where('cid = ?', $rows['cid']));

        return $insertId;
    }

    /**
     * 更新评论
     *
     * @param array $rows 评论结构数组
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        /** 获取内容主键 */
        $updateCondition = clone $condition;
        $updateComment = $this->db->fetchObject($condition->select('cid')->from('table.comments')->limit(1));

        if ($updateComment) {
            $cid = $updateComment->cid;
        } else {
            return 0;
        }

        /** 构建插入结构 */
        $preUpdateStruct = [
            'author' => Common::strBy($rows['author'] ?? null),
            'mail'   => Common::strBy($rows['mail'] ?? null),
            'url'    => Common::strBy($rows['url'] ?? null),
            'text'   => Common::strBy($rows['text'] ?? null),
            'status' => Common::strBy($rows['status'] ?? null, 'approved'),
        ];

        $updateStruct = [];
        foreach ($rows as $key => $val) {
            if ((array_key_exists($key, $preUpdateStruct))) {
                $updateStruct[$key] = $preUpdateStruct[$key];
            }
        }

        /** 更新创建时间 */
        if (!empty($rows['created'])) {
            $updateStruct['created'] = $rows['created'];
        }

        /** 更新评论数据 */
        $updateRows = $this->db->query($updateCondition->update('table.comments')->rows($updateStruct));

        /** 更新评论数 */
        $num = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])->from('table.comments')
            ->where('status = ? AND cid = ?', 'approved', $cid))->num;

        $this->db->query($this->db->update('table.contents')->rows(['commentsNum' => $num])
            ->where('cid = ?', $cid));

        return $updateRows;
    }

    /**
     * 删除数据
     *
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        /** 获取内容主键 */
        $deleteCondition = clone $condition;
        $deleteComment = $this->db->fetchObject($condition->select('cid')->from('table.comments')->limit(1));

        if ($deleteComment) {
            $cid = $deleteComment->cid;
        } else {
            return 0;
        }

        /** 删除评论数据 */
        $deleteRows = $this->db->query($deleteCondition->delete('table.comments'));

        /** 更新评论数 */
        $num = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])->from('table.comments')
            ->where('status = ? AND cid = ?', 'approved', $cid))->num;

        $this->db->query($this->db->update('table.contents')->rows(['commentsNum' => $num])
            ->where('cid = ?', $cid));

        return $deleteRows;
    }

    /**
     * 按照条件计算评论数量
     *
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(coid)' => 'num'])->from('table.comments'))->num;
    }

    /**
     * 将每行的值压入堆栈
     *
     * @param array $value 每行的值
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * 通用过滤器
     *
     * @param array $row 需要过滤的行数据
     * @return array
     */
    public function filter(array $row): array
    {
        /** 处理默认空值 */
        $row['author'] = $row['author'] ?? '';
        $row['mail'] = $row['mail'] ?? '';
        $row['url'] = $row['url'] ?? '';
        $row['ip'] = $row['ip'] ?? '';
        $row['agent'] = $row['agent'] ?? '';
        $row['text'] = $row['text'] ?? '';

        $row['date'] = new Date($row['created']);
        return Comments::pluginHandle()->filter('filter', $row, $this);
    }

    /**
     * 输出文章发布日期
     *
     * @param string|null $format 日期格式
     */
    public function date(?string $format = null)
    {
        echo $this->date->format(empty($format) ? $this->options->commentDateFormat : $format);
    }

    /**
     * 输出作者相关
     *
     * @param boolean|null $autoLink 是否自动加上链接
     * @param boolean|null $noFollow 是否加上nofollow标签
     */
    public function author(?bool $autoLink = null, ?bool $noFollow = null)
    {
        $autoLink = (null === $autoLink) ? $this->options->commentsShowUrl : $autoLink;
        $noFollow = (null === $noFollow) ? $this->options->commentsUrlNofollow : $noFollow;

        if ($this->url && $autoLink) {
            echo '<a href="' . Common::safeUrl($this->url) . '"'
                . ($noFollow ? ' rel="external nofollow"' : null) . '>' . $this->author . '</a>';
        } else {
            echo $this->author;
        }
    }

    /**
     * 调用gravatar输出用户头像
     *
     * @param integer $size 头像尺寸
     * @param string|null $default 默认输出头像
     */
    public function gravatar(int $size = 32, ?string $default = null, $highRes = false)
    {
        if ($this->options->commentsAvatar && 'comment' == $this->type) {
            $rating = $this->options->commentsAvatarRating;

            Comments::pluginHandle()->trigger($plugged)->call('gravatar', $size, $rating, $default, $this);
            if (!$plugged) {
                $url = Common::gravatarUrl($this->mail, $size, $rating, $default, $this->request->isSecure());
                $srcset = '';

                if ($highRes) {
                    $url2x = Common::gravatarUrl($this->mail, $size * 2, $rating, $default, $this->request->isSecure());
                    $url3x = Common::gravatarUrl($this->mail, $size * 3, $rating, $default, $this->request->isSecure());
                    $srcset = ' srcset="' . $url2x . ' 2x, ' . $url3x . ' 3x"';
                }

                echo '<img class="avatar" loading="lazy" src="' . $url . '"' . $srcset . ' alt="' .
                    $this->author . '" width="' . $size . '" height="' . $size . '" />';
            }
        }
    }

    /**
     * 输出评论摘要
     *
     * @param integer $length 摘要截取长度
     * @param string $trim 摘要后缀
     */
    public function excerpt(int $length = 100, string $trim = '...')
    {
        echo Common::subStr(strip_tags($this->content), 0, $length, $trim);
    }

    /**
     * 输出邮箱地址
     *
     * @param bool $link
     * @return void
     */
    public function mail(bool $link = false)
    {
        $mail = htmlspecialchars($this->mail);
        echo $link ? 'mailto:' . $mail : $mail;
    }

    /**
     * 获取查询对象
     *
     * @param mixed $fields
     * @return Query
     */
    public function select(...$fields): Query
    {
        return $this->db->select(...$fields)->from('table.comments');
    }

    /**
     * markdown
     *
     * @param string|null $text
     * @return string|null
     */
    public function markdown(?string $text): ?string
    {
        $html = Comments::pluginHandle()->trigger($parsed)->filter('markdown', $text);

        if (!$parsed) {
            $html = Markdown::convert($text);
        }

        return $html;
    }

    /**
     * autoP
     *
     * @param string|null $text
     * @return string|null
     */
    public function autoP(?string $text): ?string
    {
        $html = Comments::pluginHandle()->trigger($parsed)->filter('autoP', $text);

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
     * 获取当前内容结构
     *
     * @return Contents
     */
    protected function ___parentContent(): Contents
    {
        return From::allocWithAlias($this->cid, ['cid' => $this->cid]);
    }

    /**
     * 获取当前评论标题
     *
     * @return string|null
     */
    protected function ___title(): ?string
    {
        return $this->parentContent->title;
    }

    /**
     * 获取当前评论页码
     *
     * @return int
     */
    protected function ___commentPage(): int
    {
        if ($this->options->commentsPageBreak) {
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

            $select = $this->db->select('coid', 'parent')
                ->from('table.comments')
                ->where(
                    'cid = ? AND (status = ? OR coid = ?)',
                    $this->cid,
                    'approved',
                    $this->status !== 'approved' ? $this->coid : 0
                )
                ->where('coid ' . ('DESC' == $this->options->commentsOrder ? '>=' : '<=') . ' ?', $coid)
                ->order('coid');

            if ($this->options->commentsShowCommentOnly) {
                $select->where('type = ?', 'comment');
            }

            $comments = $this->db->fetchAll($select);

            $commentsMap = [];
            $total = 0;

            foreach ($comments as $comment) {
                $commentsMap[$comment['coid']] = $comment['parent'];

                if (0 == $comment['parent'] || !isset($commentsMap[$comment['parent']])) {
                    $total++;
                }
            }

            return ceil($total / $this->options->commentsPageSize);
        }

        return 0;
    }

    /**
     * 获取当前评论链接
     *
     * @return string
     * @throws Exception
     */
    protected function ___permalink(): string
    {
        if ($this->options->commentsPageBreak) {
            return Router::url(
                'comment_page',
                $this,
                $this->options->index
            ) . '#' . $this->theId;
        }

        return $this->parentContent->permalink . '#' . $this->theId;
    }

    /**
     * 获取当前评论内容
     *
     * @return string|null
     */
    protected function ___content(): ?string
    {
        $text = $this->parentContent->hidden ? _t('内容被隐藏') : $this->text;

        $text = Comments::pluginHandle()->trigger($plugged)->filter('content', $text, $this);
        if (!$plugged) {
            $text = $this->options->commentsMarkdown ? $this->markdown($text)
                : $this->autoP($text);
        }

        $text = Comments::pluginHandle()->filter('contentEx', $text, $this);
        return Common::stripTags($text, '<p><br>' . $this->options->commentsHTMLTagAllowed);
    }

    /**
     * 输出词义化日期
     *
     * @return string
     */
    protected function ___dateWord(): string
    {
        return $this->date->word();
    }

    /**
     * 锚点id
     *
     * @return string
     */
    protected function ___theId(): string
    {
        return $this->type . '-' . $this->coid;
    }
}
