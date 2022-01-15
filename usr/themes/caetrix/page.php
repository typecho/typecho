<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php'); ?>

    <div class="blog-container">

      <article id="article" class="blog" itemscope="" itemtype="http://schema.org/BlogPosting">
        
        <div class="article-masthead blog-title">
          <div class="meta">
            <?php if ($this->fields->subtitle): $field = $this->fields->subtitle(); endif; ?>
          </div>
          <h1 id="title" class="title" itemprop="name headline"><?php $this->title() ?></h1>
        </div>

        <?php if ($this->options->enableCustomRenderer == 1): echo PostRenderer::parse($this->content); else: $this->content(); endif; ?>
    
        <?php if ($this->options->donateQRLink): ?>
        <div class="donate" style="margin-top: 25px;">
          <p>赏</p>
        </div>
        <?php endif; ?>

      </article><!-- /.blog-post -->
    </div><!-- /.blog-container -->

<?php $this->need('comments.php'); ?>

<?php $this->need('footer.php'); ?>
