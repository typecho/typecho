<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div id="comments">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
        <h2 class="text-center"><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></h2>

        <?php $comments->listComments(array(
            'commentStatus' => _t('你的评论正等待审核'),
            'avatarSize' => 64,
            'defaultAvatar' => 'identicon'
        )); ?>

        <nav><?php $comments->pageNav(_t('前一页'), _t('后一页'), 3, '...', array('wrapTag' => 'ul', 'itemTag' => 'li')); ?></nav>

    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>" class="respond">
            <div class="cancel-comment-reply">
                <?php $comments->cancelReply(); ?>
            </div>

            <h5 id="response"><?php _e('你的评论'); ?></h5>

            <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" role="form">
                <div class="grid">
                    <textarea placeholder="<?php _e('评论内容...'); ?>" rows="4" cols="300" name="text" id="textarea" required><?php $this->remember('text'); ?></textarea>
                </div>
                <?php if ($this->user->hasLogin()): ?>
                <p>
                    <?php _e('登录身份：'); ?><a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a><span class="mx-2 text-muted">&middot;</span><a href="<?php $this->options->logoutUrl(); ?>"><?php _e('退出'); ?></a>
                </p>
                <?php else: ?>
                <div class="grid">
                    <input type="text" placeholder="<?php _e('名字'); ?>" name="author" id="author" value="<?php $this->remember('author'); ?>" required/>
                    <input type="email" placeholder="<?php _e('Email'); ?>" name="mail" id="mail" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?> />
                    <input type="url" placeholder="<?php _e('http://网站'); ?><?php if (!$this->options->commentsRequireUrl): ?><?php _e('（选填）'); ?><?php endif; ?>" name="url" id="url" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?> />
                </div>
                <?php endif; ?>
                <button type="submit"><?php _e('提交评论'); ?></button>
            </form>
        </div>
    <?php else: ?>
        <div class="text-center text-muted"><?php _e('评论已关闭'); ?></div>
    <?php endif; ?>
</div>
