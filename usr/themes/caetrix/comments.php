<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>


<div id="comments" class="blog-container">

  <!-- Default -->
  <?php function threadedComments($comments, $options) {
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
        } else {
            $commentClass .= ' comment-by-user';
        }
    }
    $commentLevelClass = $comments->levels > 0 ? ' comment-child' : ' comment-parent'; 
  ?>

  <li id="comment-<?php $comments->theId(); ?>" class="comment-body<?php 
    if ($comments->levels > 0) {
      echo ' comment-child';
      $comments->levelsAlt(' comment-level-odd', ' comment-level-even');
    } else {
      echo ' comment-parent';
    }
    $comments->alt(' comment-odd', ' comment-even');
    echo $commentClass;
    ?>">
    <div class="comment-box" id="<?php $comments->theId(); ?>">
      <div class="comment-author" itemprop="creator" itemscope itemtype="http://schema.org/Person">
        <span itemprop="image"><?php $comments->gravatar('100', ''); ?></span>
        <div class="comment-meta" style="font-size: 12px;">
          <cite class="fn" itemprop="name"><?php $comments->author(); ?></cite><br/>
          <span itemprop="commentTime"><?php $comments->dateWord(); ?> • <?php $comments->reply(); ?><?php if ('waiting' == $comments->status): _e(" • "); $options->commentStatus(); endif; ?></span>
        
        </div>
      </div>
      <div class="comment-content" itemprop="commentText">
        <?php $comments->content(); ?>
      </div>
      
    </div>
    <?php if ($comments->children) { ?>
    <div class="comment-children">
      <?php $comments->threadedComments($options); ?>
    </div>
    <?php } ?>
  </li>
  <?php } ?>

  <?php $this->comments()->to($comments); ?>

  <?php if($this->allow('comment')): ?>

    <div id="<?php $this->respondId(); ?>" class="respond">
      <div class="comments-content">
        <!-- Comment Form -->
        <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" class="comment-form" role="form">
          <h3 id="response"><?php _e("SLTP 网页客户端"); ?>&nbsp;<?php $comments->cancelReply(); ?></h3>
          <textarea name="text" id="textarea" class="form-control" placeholder="<?php _e("在此处填写你的内容"); ?>" required></textarea>
          <?php if($this->user->hasLogin()): ?>
          <p><?php _e('欢迎回来，'); ?><a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>。如果<?php _e('这不是「你」本人'); ?>，可能需要<a href="<?php $this->options->logoutUrl(); ?>" title="Logout"><?php _e('注销'); ?></a>当前会话。</p>
          <?php else: ?>
            <input type="text" name="author" maxlength="12" id="author" class="form-control input-control" placeholder="<?php _e('昵称（必填）'); ?>" value="<?php $this->remember('author'); ?>" required>
            <input type="email" name="mail" id="mail" class="form-control input-control" placeholder="<?php _e('电子邮箱地址（必填）'); ?>" value="<?php $this->remember('mail'); ?>" <?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>>
            <input type="url" name="url" id="url" class="form-control input-control" placeholder="<?php _e('网站地址'); ?>" value="<?php $this->remember('url'); ?>" <?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?>>
          <?php endif; ?>
          <button type="submit" id="misubmit">送信</button>
        </form>
      </div>
    </div>

    <?php if ($comments->have()): ?>
      <h3 style="margin-top: 30px;"><?php $this->commentsNum(_t('未有信来'), _t('有信来'), _t('%d 封来信已接收')); ?></h3>
      <?php $comments->listComments(); ?>
      <?php $comments->pageNav('&laquo;', '&raquo;', 5, '...', array('wrapTag' => 'ul', 'wrapClass' => 'page-change', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'active', 'prevClass' => 'prev', 'nextClass' => 'next')); ?>
    <?php endif;
  endif; ?>

</div>