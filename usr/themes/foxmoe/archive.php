<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>
<?php $this->need('header.php'); ?>

<main class="main-container">
  <div class="content-wrapper">
    <div class="main-content">
      <section class="posts-list-section">
        <h2 class="section-title">
          <?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search' => _t('包含关键字 %s 的文章'),
            'tag' => _t('标签 %s 下的文章'),
            'author' => _t('%s 发布的文章')
          ], '', ''); ?>
        </h2>

        <?php if ($this->have()): ?>
          <div class="posts-list">
            <?php while ($this->next()): ?>
              <article class="post-card">
                <a href="<?php $this->permalink(); ?>">
                  <div class="post-image">
                    <?php if ($this->fields->thumbnail): ?>
                      <img src="<?php echo $this->fields->thumbnail; ?>" alt="<?php $this->title(); ?>" class="post-img">
                    <?php else: ?>
                      <img src="<?php $this->options->themeUrl('image/wallpaper.jpg'); ?>" alt="<?php $this->title(); ?>"
                        class="post-img">
                    <?php endif; ?>
                    <div class="post-category"><?php $this->category(',', false); ?></div>
                  </div>
                  <div class="post-content">
                    <h3 class="post-title"><?php $this->title(); ?></h3>
                    <p class="post-excerpt"><?php echo mb_substr(strip_tags($this->excerpt), 0, 120, 'UTF-8') . '...'; ?>
                    </p>
                    <div class="post-meta">
                      <div class="post-author">
                        <img src="<?php $this->options->themeUrl('image/logo/640.png'); ?>" alt="<?php $this->author(); ?>"
                          class="author-avatar">
                        <span><?php $this->author(); ?></span>
                      </div>
                      <span class="post-date"><?php $this->date('Y-m-d'); ?></span>
                      <span class="post-comments">
                        <a
                          href="<?php $this->permalink(); ?>#comments"><?php $this->commentsNum('评论', '1条评论', '%d条评论'); ?></a>
                      </span>
                    </div>
                    <div class="post-tags">
                      <?php $this->tags(',', true, ''); ?>
                    </div>
                  </div>
                </a>
              </article>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="no-content"><?php _e('没有找到内容'); ?></p>
        <?php endif; ?>

        <div class="page-nav">
          <?php $this->pageNav('&laquo; 前一页', '后一页 &raquo;'); ?>
        </div>
      </section>
    </div>

    <?php $this->need('sidebar.php'); ?>
  </div>
</main>

<?php $this->need('footer.php'); ?>