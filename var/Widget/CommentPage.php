<?php

namespace Widget;

use Exception;
use Typecho\Router;
use Typecho\Widget\Exception as WidgetException;

/**
 * Comment Page Widget
 */
class CommentPage extends Base implements ActionInterface
{
    /**
     * Perform comment page action
     *
     * @throws Exception
     */
    public function action()
    {
        $page = abs($this->request->filter('int')->get('commentPage'));

        $archive = Router::match($this->request->get('permalink'), [
            'checkPermalink'    =>  false,
            'commentPage'       =>  $page
        ]);

        if (!($archive instanceof Archive) || !$archive->is('single')) {
            throw new WidgetException(_t('请求的地址不存在'), 404);
        }

        $currentCommentUrl = Router::url('comment_page', [
            'permalink'     =>  $archive->path,
            'commentPage'   =>  $page
        ], $this->options->index);

        $this->checkPermalink($currentCommentUrl);
        $archive->render();
    }

    /**
     * @param string $commentUrl
     */
    private function checkPermalink(string $commentUrl)
    {
        if ($commentUrl != $this->request->getRequestUrl()) {
            $this->response->redirect($commentUrl, true);
        }
    }
}
