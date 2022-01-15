<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Tags + Archives
 *
 * @package custom
 */

$this->need('header.php'); ?>

    <div class="blog-container">

      <article id="article" class="blog" itemscope="" itemtype="http://schema.org/BlogPosting">

        <div class="article-masthead blog-title">
          <div class="meta">
            <?php if ($this->fields->subtitle): $field = $this->fields->subtitle(); endif; ?>
          </div>
          <h1 id="title" class="title" itemprop="name headline"><?php $this->title() ?></h1>
        </div>

        <h4><?php _e('🔖 Tags'); ?></h1>

        <div style="padding-top:20px;text-align:center;">
          <?php $this->widget('Widget_Metas_Tag_Cloud', 'sort=mid&ignoreZeroCount=1&desc=0&limit=1000')->to($tags); ?>
          <?php if($tags->have()):?>
          <?php while ($tags->next()): ?>
          <a href="<?php $tags->permalink(); ?>" style="color: rgb(255, <?php echo(rand(110, 160)); ?>, <?php echo(rand(0, 160)); ?>)" rel="tag" class="archives-tags" title="<?php $tags->name(); ?> 有 <?php $tags->count(); ?> 个话题"><?php $tags->name(); ?></a>
          <?php endwhile; ?>
          <?php else: ?>
          <p><?php _e('没有任何标签'); ?></p>
          <?php endif; ?>
        </div>

        <hr style="margin: 50px 0;"/>

        <h4><?php _e('💡 Recent Posts'); ?></h1>

        <div class="archives-loop">

        <?php $this->widget('Widget_Contents_Post_Recent', 'pageSize=1000')->to($archives);
        while($archives->next()): ?>
          <a href="<?php $archives->permalink() ?>">
            <span><?php $archives->dateword(); ?></span>
            <h3><?php $archives->title('false'); ?></h3>
          </a>
        <?php endwhile; ?>
        </div>

        <?php if ($this->options->enableCustomRenderer == 1): echo PostRenderer::parse($this->content); else: $this->content(); endif; ?>

      </article><!-- /.blog-post -->
    </div><!-- /.blog-container -->

<?php $this->need('footer.php'); ?>
