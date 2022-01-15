<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * PostRenderer.php
 * Author     : metheno
 * Date       : 2017/03/17
 * Version    :
 * Description: Customize Post rendering.
 */

class PostRenderer {

    public static function parse($content) {
        $markdown_rendered = self::renderMarkdownExtension($content);
        $friendlinks_rendered = self::renderFriendLinks($markdown_rendered);
        return $friendlinks_rendered;
    }

    private static function renderMarkdownExtension($content) {
        $del_replaced = preg_replace('/\~\~(.+?)\~\~/i', "<del>$1</del>", $content);
        $mark_replaced = preg_replace('/\=\=(.+?)\=\=/i', "<mark>$1</mark>", $del_replaced);
        return $mark_replaced;
    }

    private static function renderFriendLinks($content) {
        $startsign_replaced = preg_replace('/\[\[(links)\]\]/i', "<ul class='links'>", $content);
        $endsign_replaced = preg_replace('/\[\[\/(links)\]\]/i', "</ul>", $startsign_replaced);
        return $endsign_replaced;   
    }
}
