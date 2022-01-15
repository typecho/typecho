<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Gallery
 *
 * @package custom
 */

$this->need('header.php'); ?>

    <div class="gallery-container">

      <article id="article" class="blog" itemscope="" itemtype="http://schema.org/BlogPosting">

        <div class="blog-container">
          <div class="article-masthead blog-title">
            <div class="meta">
              <?php if ($this->fields->subtitle): $field = $this->fields->subtitle(); endif; ?>
            </div>
            <h1 id="title" class="title" itemprop="name headline"><?php $this->title() ?></h1>
          </div>
        </div><!-- /.blog-container -->

        <?php if ($this->options->enableCustomRenderer == 1): echo PostRenderer::parse($this->content); else: $this->content(); endif; ?>

      </article><!-- /.blog-post -->
    </div><!-- /.gallery-container -->

<?php $this->need('footer.php'); ?>
