<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="container">
    <div class="container-thin">

        <h1 class="text-center"><?php _e('搜索'); ?></h1>
        
        <form method="post" action="<?php $this->options->siteUrl(); ?>">
            <input type="search" id="s" name="s" placeholder="<?php _e('搜索关键字'); ?>" value="<?php $this->archiveTitle('','',''); ?>">
        </form>

        <div class="text-center">
            <?php \Widget\Metas\Category\Rows::alloc()->listCategories('wrapClass=list-inline'); ?>
        </div>
    
        <hr class="post-separator">

    <?php if ($this->have()): ?>
        <?php while ($this->next()): ?>
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <?php postMeta($this); ?>
            
            <div class="entry-content fmt" itemprop="articleBody">
                <?php $this->content('阅读全文'); ?>
            </div>
        </article>
        <hr class="post-separator">
        <?php endwhile; ?>
    <?php else: ?>
        <article class="post">
            <div class="entry-content fmt text-center" itemprop="articleBody">
                <p><?php _e('没有找到内容'); ?></p>
            </div>
        </article>
    <?php endif; ?>
    </div>

    <?php $this->pageNav('&laquo; 前一页', '后一页 &raquo;'); ?>
</main>

<?php $this->need('footer.php'); ?>
