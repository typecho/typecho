<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>
<div class="header-search-panel" id="headerSearchPanel" role="dialog" aria-modal="false" aria-label="站内搜索"
  aria-hidden="true">
  <div class="search-box">
    <form method="post" class="search-form" action="<?php $this->options->siteUrl(); ?>" role="search"
      aria-label="站内搜索表单">
      <input type="text" class="search-input" name="s" placeholder="搜索文章..."
        value="<?php if (isset($_POST['s']))
          echo htmlspecialchars($_POST['s']); ?>" aria-label="搜索关键字">
      <button type="submit" class="search-submit" title="搜索" aria-label="执行搜索">
        <span class="material-icons" aria-hidden="true">search</span>
      </button>
      <button type="button" class="search-close" title="关闭" aria-label="关闭搜索面板">
        <span class="material-icons" aria-hidden="true">close</span>
      </button>
    </form>
  </div>
</div>