<?php $this->need('header.php'); ?>

<div class="col-mb-12 col-8" id="main" role="main">
    <article class="post">
        <h1 class="post-title"><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h1>
        <ul class="post-meta">
            <li><?php _e('作者：'); ?><?php $this->author(); ?></li>
            <li><?php _e('时间：'); ?><?php $this->date('F j, Y'); ?></li>
            <li><?php _e('分类：'); ?><?php $this->category(','); ?></li>
        </ul>
        <div class="post-content">
            <?php $this->content(); ?>
        </div>
        <p class="tags"><?php _e('标签：'); ?><?php $this->tags(', ', true, 'none'); ?></p>
    </article>

    <?php $this->need('comments.php'); ?>

    <ul class="post-near">
        <li>上一篇：<?php $this->thePrev('%s','没有了'); ?></li>
        <li>下一篇：<?php $this->theNext('%s','没有了'); ?></li>
    </ul>
</div><!-- end #main-->

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
