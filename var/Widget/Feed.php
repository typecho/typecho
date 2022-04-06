<?php

namespace Widget;

use Exception;
use Typecho\Common;
use Typecho\Config;
use Typecho\Router;
use Typecho\Widget\Exception as WidgetException;
use Widget\Base\Contents;
use Typecho\Feed as FeedGenerator;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Feed handler
 */
class Feed extends Contents
{
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
        $feedPath = $this->request->feed;
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

            $path = parse_url($archive->getArchiveUrl(), PHP_URL_PATH);
            $currentFeedUrl = Common::url($path, $currentFeedUrl);
            $feed->setBaseUrl($archive->getArchiveUrl());
            $feed->setSubTitle($archive->getDescription());
        }

        $this->checkPermalink($currentFeedUrl);
        $feed->setFeedUrl($currentFeedUrl);
        $this->render($feed, $feedContentType, $isComments, $archive ?? null);
    }

    /**
     * @param FeedGenerator $feed
     * @param string $contentType
     * @param bool $isComments
     * @param Archive|null $archive
     */
    public function render(
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
        } else {
            $feed->setTitle($this->options->title
                . ($archive->getArchiveTitle() ? ' - ' . $archive->getArchiveTitle() : ''));
        }
    }

    /**
     * @param string $feedUrl
     */
    private function checkPermalink(string $feedUrl)
    {
        if ($feedUrl != $this->request->getRequestUri()) {
            $this->response->redirect($feedUrl, true);
        }
    }
}
