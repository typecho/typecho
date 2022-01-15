<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
error_reporting(E_ALL); 
ini_set('display_errors', '1'); 
$this->need('header.php');?>
  
    <div class="blog-container">

      <article id="article" class="blog" itemscope="" itemtype="http://schema.org/BlogPosting">

        <div class="article-masthead blog-title">
          <div class="meta">
            <?php if ($this->fields->subtitle): $field = $this->fields->subtitle(); echo " • "; endif; ?>
            <?php if ($this->is('post')): ?><?php $this->category(', '); ?>&nbsp;• <?php endif; ?>
            <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->dateword(); ?></time>
          </div>
          <h1 id="title" class="title" itemprop="name headline"><?php $this->title() ?></h1>
        </div>
    
        <?php if ($this->options->enableCustomRenderer == 1): echo PostRenderer::parse($this->content); else: $this->content(); endif; ?>
        
        <p itemprop="keywords" class="post-tag-holder" style="padding-top:10px;"><?php _e('标签: '); ?><?php $this->tags(', ', true, '无标签'); ?></p>
        
        <?php if ($this->options->donateQRLink): ?>
        <div class="donate">
          <p>赏</p>
        </div>
        <?php endif; ?>

        

      </article><!-- /.blog-post -->

      <?php if (($this->options->enableGoogleAdsense == 1) && ($this->options->googleAdsenseAdContent)): $this->options-> googleAdsenseAdContent(); endif; ?>

    </div><!-- /.blog-container -->
    
    <div class="blog-bottom-bar">
      <div class="blog-container">
        <?php prev_post($this); next_post($this); ?>
        
      </div>
    </div>

  <?php $this->need('comments.php'); ?>

<?php $this->need('footer.php'); ?>
