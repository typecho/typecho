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

        <nav class="post-nav">
            <ul class="page-navigator">
                <li class="prev"><?php $this->thePrev('%s', _t('没有了')); ?></li>
                <li class="next"><?php $this->theNext('%s', _t('没有了')); ?></li>
            </ul>
        </nav>

        <?php $this->need('comments.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
