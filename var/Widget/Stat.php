<?php

namespace Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 全局统计组件
 *
 * @property-read int $publishedPostsNum
 * @property-read int $waitingPostsNum
 * @property-read int $draftPostsNum
 * @property-read int $myPublishedPostsNum
 * @property-read int $myWaitingPostsNum
 * @property-read int $myDraftPostsNum
 * @property-read int $currentPublishedPostsNum
 * @property-read int $currentWaitingPostsNum
 * @property-read int $currentDraftPostsNum
 * @property-read int $publishedPagesNum
 * @property-read int $draftPagesNum
 * @property-read int $publishedCommentsNum
 * @property-read int $waitingCommentsNum
 * @property-read int $spamCommentsNum
 * @property-read int $myPublishedCommentsNum
 * @property-read int $myWaitingCommentsNum
 * @property-read int $mySpamCommentsNum
 * @property-read int $currentCommentsNum
 * @property-read int $currentPublishedCommentsNum
 * @property-read int $currentWaitingCommentsNum
 * @property-read int $currentSpamCommentsNum
 * @property-read int $categoriesNum
 * @property-read int $tagsNum
 */
class Stat extends Base
{
    /**
     * @param int $components
     */
    protected function initComponents(int &$components)
    {
        $components = self::INIT_USER;
    }

    /**
     * 获取已发布的文章数目
     *
     * @return integer
     */
    protected function ___publishedPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish'))->num;
    }

    /**
     * 获取待审核的文章数目
     *
     * @return integer
     */
    protected function ___waitingPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
            ->where('table.contents.status = ?', 'waiting'))->num;
    }

    /**
     * 获取草稿文章数目
     *
     * @return integer
     */
    protected function ___draftPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post_draft'))->num;
    }

    /**
     * 获取当前用户已发布的文章数目
     *
     * @return integer
     */
    protected function ___myPublishedPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.authorId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户待审核文章数目
     *
     * @return integer
     */
    protected function ___myWaitingPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
            ->where('table.contents.status = ?', 'waiting')
            ->where('table.contents.authorId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户草稿文章数目
     *
     * @return integer
     */
    protected function ___myDraftPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post_draft')
            ->where('table.contents.authorId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户已发布的文章数目
     *
     * @return integer
     */
    protected function ___currentPublishedPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.authorId = ?', $this->request->filter('int')->uid))->num;
    }

    /**
     * 获取当前用户待审核文章数目
     *
     * @return integer
     */
    protected function ___currentWaitingPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
            ->where('table.contents.status = ?', 'waiting')
            ->where('table.contents.authorId = ?', $this->request->filter('int')->uid))->num;
    }

    /**
     * 获取当前用户草稿文章数目
     *
     * @return integer
     */
    protected function ___currentDraftPostsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post_draft')
            ->where('table.contents.authorId = ?', $this->request->filter('int')->uid))->num;
    }

    /**
     * 获取已发布页面数目
     *
     * @return integer
     */
    protected function ___publishedPagesNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'page')
            ->where('table.contents.status = ?', 'publish'))->num;
    }

    /**
     * 获取草稿页面数目
     *
     * @return integer
     */
    protected function ___draftPagesNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'page_draft'))->num;
    }

    /**
     * 获取当前显示的评论数目
     *
     * @return integer
     */
    protected function ___publishedCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'approved'))->num;
    }

    /**
     * 获取当前待审核的评论数目
     *
     * @return integer
     */
    protected function ___waitingCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'waiting'))->num;
    }

    /**
     * 获取当前垃圾评论数目
     *
     * @return integer
     */
    protected function ___spamCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'spam'))->num;
    }

    /**
     * 获取当前用户显示的评论数目
     *
     * @return integer
     */
    protected function ___myPublishedCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'approved')
            ->where('table.comments.ownerId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户待审核的评论数目
     *
     * @return integer
     */
    protected function ___myWaitingCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'waiting')
            ->where('table.comments.ownerId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前用户垃圾评论数目
     *
     * @return integer
     */
    protected function ___mySpamCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'spam')
            ->where('table.comments.ownerId = ?', $this->user->uid))->num;
    }

    /**
     * 获取当前文章的评论数目
     *
     * @return integer
     */
    protected function ___currentCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取当前文章显示的评论数目
     *
     * @return integer
     */
    protected function ___currentPublishedCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'approved')
            ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取当前文章待审核的评论数目
     *
     * @return integer
     */
    protected function ___currentWaitingCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'waiting')
            ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取当前文章垃圾评论数目
     *
     * @return integer
     */
    protected function ___currentSpamCommentsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('table.comments.status = ?', 'spam')
            ->where('table.comments.cid = ?', $this->request->filter('int')->cid))->num;
    }

    /**
     * 获取分类数目
     *
     * @return integer
     */
    protected function ___categoriesNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(mid)' => 'num'])
            ->from('table.metas')
            ->where('table.metas.type = ?', 'category'))->num;
    }

    /**
     * 获取标签数目
     *
     * @return integer
     */
    protected function ___tagsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(mid)' => 'num'])
            ->from('table.metas')
            ->where('table.metas.type = ?', 'tag'))->num;
    }
}
