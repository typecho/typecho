<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="container">
    <div class="container-thin">
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <?php postMeta($this, 'post'); ?>

            <div class="entry-content fmt" itemprop="articleBody">
                <?php $this->content(); ?>
                <p itemprop="keywords"><?php _e('标签'); ?>：<?php $this->tags(', ', true, _t('无')); ?></p>
            </div>
        </article>

        <div class="grid post-next">
            <div>
                ← <?php $this->thePrev('%s', _t('没有了')); ?>
            </div>
            <div class="text-end">
                <?php $this->theNext('%s', _t('没有了')); ?> →
            </div>
        </div>

        <?php $this->need('comments.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
