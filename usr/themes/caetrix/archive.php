<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php'); ?>

<div class="blog-container">

  <?php if ($this->have()): ?>

  <?php while($this->next()): ?>
  <article class="archive" itemscope="" itemtype="http://schema.org/BlogPosting">
    <h1 class="blog-post-title" itemprop="name headline"><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h1>
    <p class="blog-post-meta">
      <?php $this->category(', '); ?>&nbsp;•
      <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->dateword(); ?></time>
    </p>
    
    <?php $this->content('阅读'); ?>
    
  </article><!-- /.blog-post -->
  <?php endwhile; ?>

  <?php else: ?>
  <article class="blog" itemscope="" itemtype="http://schema.org/BlogPosting">
    
    <h1 class="blog-post-title" style="font-size: 70px;"><?php _e('null'); ?></h1>
    <p class="blog-post-meta" style="font-size: 20px;"><?php $this->archiveTitle(array(
            'category'  =>  _t('这个分类下没有文章。'),
            'search'    =>  _t('未找到内容。'),
            'tag'       =>  _t('这个标签内没有内容。'),
            'author'    =>  _t('%s 没有发布内容。')
        ), null, null); ?></p>
    
  </article><!-- /.blog-post -->
  <?php endif; ?>

</div><!-- /.blog-container -->

  <?php $this->pageNav('&laquo;', '&raquo;', 1, '…', array('wrapTag' => 'ul', 'wrapClass' => 'pagination', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'active', 'prevClass' => 'prev', 'nextClass' => 'next')); ?>


<?php $this->need('footer.php'); ?>
