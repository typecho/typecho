<?php

namespace Widget;

use Exception;
use Typecho\Common;
use Typecho\Config;
use Typecho\Router;
use Typecho\Widget\Exception as WidgetException;
use Widget\Base\Contents;
use Typecho\Feed as FeedGenerator;
use Widget\Comments\Recent;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Feed handler
 */
class Feed extends Contents
{
    /**
     * @var FeedGenerator
     */
    private FeedGenerator $feed;

    /**
     * @param Config $parameter
     * @throws Exception
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault([
            'pageSize'       => 10,
        ]);
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $feedPath = $this->request->get('feed');
        $feedType = FeedGenerator::RSS2;
        $feedContentType = 'application/rss+xml';
        $currentFeedUrl = $this->options->feedUrl;
        $isComments = false;

        /** 判断聚合类型 */
        switch (true) {
            case preg_match("/^\/rss(\/|$)/", $feedPath):
                /** 如果是RSS1标准 */
                $feedPath = substr($feedPath, 4);
                $feedType = FeedGenerator::RSS1;
                $currentFeedUrl = $this->options->feedRssUrl;
                $feedContentType = 'application/rdf+xml';
                break;
            case preg_match("/^\/atom(\/|$)/", $feedPath):
                /** 如果是ATOM标准 */
                $feedPath = substr($feedPath, 5);
                $feedType = FeedGenerator::ATOM1;
                $currentFeedUrl = $this->options->feedAtomUrl;
                $feedContentType = 'application/atom+xml';
                break;
            default:
                break;
        }

        $feed = new FeedGenerator(
            Common::VERSION,
            $feedType,
            $this->options->charset,
            _t('zh-CN')
        );

        if (preg_match("/^\/comments\/?$/", $feedPath)) {
            $isComments = true;
            $currentFeedUrl = Common::url('/comments/', $currentFeedUrl);
            $feed->setBaseUrl($this->options->siteUrl);
            $feed->setSubTitle($this->options->description);
        } else {
            $archive = Router::match($feedPath, [
                'pageSize' => $this->parameter->pageSize,
                'isFeed'   => true
            ]);

            if (!($archive instanceof Archive)) {
                throw new WidgetException(_t('聚合页不存在'), 404);
            }

            switch ($feedType) {
                case FeedGenerator::RSS1:
                    $currentFeedUrl = $archive->getArchiveFeedRssUrl();
                    break;
                case FeedGenerator::ATOM1:
                    $currentFeedUrl = $archive->getArchiveFeedAtomUrl();
                    break;
                default:
                    $currentFeedUrl = $archive->getArchiveFeedUrl();
                    break;
            }

            $feed->setBaseUrl($archive->getArchiveUrl());
            $feed->setSubTitle($archive->getArchiveDescription());
        }

        $this->checkPermalink($currentFeedUrl);
        $feed->setFeedUrl($currentFeedUrl);
        $this->feed($feed, $feedContentType, $isComments, $archive ?? null);
        $this->feed = $feed;
    }

    /**
     * @param FeedGenerator $feed
     * @param string $contentType
     * @param bool $isComments
     * @param Archive|null $archive
     */
    public function feed(
        FeedGenerator $feed,
        string $contentType,
        bool $isComments,
        ?Archive $archive
    ) {
        if ($isComments || $archive->is('single')) {
            $feed->setTitle(_t(
                '%s 的评论',
                $this->options->title . ($isComments ? '' : ' - ' . $archive->getArchiveTitle())
            ));

            if ($isComments) {
                $comments = Recent::alloc('pageSize=10');
            } else {
                $comments = Recent::alloc('pageSize=10&parentId=' . $archive->cid);
            }

            while ($comments->next()) {
                $suffix = self::pluginHandle()->trigger($plugged)->call(
                    'commentFeedItem',
                    $feed->getType(),
                    $comments
                );

                if (!$plugged) {
                    $suffix = null;
                }

                $feed->addItem([
                    'title'   => $comments->author,
                    'content' => $comments->content,
                    'date'    => $comments->created,
                    'link'    => $comments->permalink,
                    'author'  => (object)[
                        'screenName' => $comments->author,
                        'url'        => $comments->url,
                        'mail'       => $comments->mail
                    ],
                    'excerpt' => strip_tags($comments->content),
                    'suffix'  => $suffix
                ]);
            }
        } else {
            $feed->setTitle($this->options->title
                . ($archive->getArchiveTitle() ? ' - ' . $archive->getArchiveTitle() : ''));

            while ($archive->next()) {
                $suffix = self::pluginHandle()->trigger($plugged)->call('feedItem', $feed->getType(), $archive);

                if (!$plugged) {
                    $suffix = null;
                }

                $feed->addItem([
                    'title'           => $archive->title,
                    'content'         => $this->options->feedFullText ? $archive->content
                        : (false !== strpos($archive->text, '<!--more-->') ? $archive->excerpt .
                            "<p class=\"more\"><a href=\"{$archive->permalink}\" title=\"{$archive->title}\">[...]</a></p>"
                            : $archive->content),
                    'date'            => $archive->created,
                    'link'            => $archive->permalink,
                    'author'          => $archive->author,
                    'excerpt'         => $archive->plainExcerpt,
                    'category'        => $archive->categories,
                    'comments'        => $archive->commentsNum,
                    'commentsFeedUrl' => Common::url($archive->path, $feed->getFeedUrl()),
                    'suffix'          => $suffix
                ]);
            }
        }

        $this->response->setContentType($contentType);
    }

    /**
     * @return void
     */
    public function render()
    {
        echo $this->feed;
    }

    /**
     * @param string $feedUrl
     */
    private function checkPermalink(string $feedUrl)
    {
        if ($feedUrl != $this->request->getRequestUrl()) {
            $this->response->redirect($feedUrl, true);
        }
    }
}
