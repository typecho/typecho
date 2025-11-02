<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>
<?php $this->need('header.php'); ?>

<main class="main-container">
  <div class="content-wrapper" style="grid-template-columns:1fr;">
    <div class="main-content">
      <section class="error-page fancy">
        <h1 class="error-title">404</h1>
        <p class="error-desc"><?php _e('有的人曾经在这里，但现在不见了，要继续寻找吗。'); ?></p>
        <form method="post" class="error-search" role="search">
          <input type="text" name="s" class="error-input" placeholder="<?php _e('输入关键词搜索...'); ?>" autocomplete="off" />
          <button type="submit" class="error-btn primary"><span class="material-icons"
              aria-hidden="true">search</span><span><?php _e('搜索'); ?></span></button>
          <a href="<?php $this->options->siteUrl(); ?>" class="error-btn ghost"><span class="material-icons"
              aria-hidden="true">home</span><span><?php _e('返回首页'); ?></span></a>
        </form>
        <div class="error-tips">
          <ul>
            <li><?php _e('检查地址是否拼写正确'); ?></li>
            <li><?php _e('尝试使用其他关键词进行搜索'); ?></li>
          </ul>
        </div>
      </section>
    </div>
  </div>
</main>

<?php $this->need('footer.php'); ?>