<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; ?>
<div id="comments" class="comments-container">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
        <div class="comments-header">
            <h3 class="comments-title">
                <span class="material-icons">chat_bubble_outline</span>
                <?php $this->commentsNum(_t('暂无评论'), _t('仅有一条评论'), _t('%d 条评论')); ?>
            </h3>
        </div>

        <div class="comments-list">
            <?php $comments->listComments(); ?>
        </div>

        <div class="comments-pagination">
            <?php $comments->pageNav('&laquo; 前一页', '后一页 &raquo;'); ?>
        </div>

    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>" class="comment-form-container">
            <div class="cancel-comment-reply">
                <?php $comments->cancelReply(); ?>
            </div>

            <div class="comment-form-header">
                <h3 id="response" class="comment-form-title">
                    <span class="material-icons">edit</span>
                    <?php _e('发表评论'); ?>
                </h3>
                <p class="comment-form-subtitle">您的邮箱地址不会被公开，必填项已用 * 标注</p>
            </div>

            <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" class="comment-form" role="form">
                <?php if ($this->user->hasLogin()): ?>
                    <div class="logged-in-info">
                        <div class="user-avatar">
                            <span class="material-icons">account_circle</span>
                        </div>
                        <div class="user-details">
                            <p class="welcome-text">
                                欢迎回来，
                                <a href="<?php $this->options->profileUrl(); ?>" class="user-name">
                                    <?php $this->user->screenName(); ?>
                                </a>
                            </p>
                            <a href="<?php $this->options->logoutUrl(); ?>" class="logout-link" title="退出登录">
                                <span class="material-icons">logout</span>
                                退出登录
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="comment-form-subtitle">想要<a href="https://foxmoe.top/console">注册一个Foxmoe通行证</a>?</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="author" class="form-label required">
                                <span class="material-icons">person</span>
                                称呼 *
                            </label>
                            <input type="text" name="author" id="author" class="form-input" placeholder="请输入您的昵称"
                                value="<?php $this->remember('author'); ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="mail"
                                class="form-label<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>">
                                <span class="material-icons">email</span>
                                邮箱<?php if ($this->options->commentsRequireMail): ?> *<?php endif; ?>
                            </label>
                            <input type="email" name="mail" id="mail" class="form-input" placeholder="your@email.com"
                                value="<?php $this->remember('mail'); ?>" <?php if ($this->options->commentsRequireMail): ?>
                                    required<?php endif; ?> />
                        </div>
                        <div class="form-group">
                            <label for="url"
                                class="form-label<?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?>">
                                <span class="material-icons">link</span>
                                网站<?php if ($this->options->commentsRequireURL): ?> *<?php endif; ?>
                            </label>
                            <input type="url" name="url" id="url" class="form-input" placeholder="https://your-website.com"
                                value="<?php $this->remember('url'); ?>" <?php if ($this->options->commentsRequireURL): ?>
                                    required<?php endif; ?> />
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group comment-text-group">
                    <label for="textarea" class="form-label required">
                        <span class="material-icons">chat</span>
                        评论内容 *
                    </label>
                    <textarea rows="6" cols="50" name="text" id="textarea" class="form-textarea" placeholder="写下您的评论..."
                        required><?php $this->remember('text'); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <span class="material-icons">send</span>
                        发表评论
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="comments-closed">
            <span class="material-icons">lock</span>
            <h3>评论已关闭</h3>
            <p>该文章的评论功能已被关闭</p>
        </div>
    <?php endif; ?>
</div>