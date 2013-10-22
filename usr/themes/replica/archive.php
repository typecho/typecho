<?php $this->need('header.php'); ?>

    <div class="col-mb-12 col-8" id="main">
        <?php if ($this->have()): ?>
    	<?php while($this->next()): ?>
            <article class="post">
    			<h2 class="post-title"><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h2>
    			<div class="post-meta">
    				<span><?php _e('作者：'); ?><?php $this->author(); ?></span>
    				<span><?php _e('发布时间：'); ?><?php $this->date('F j, Y'); ?></span>
    				<?php _e('分类：'); ?><?php $this->category(','); ?>
    			</div>
                <div class="post-content">
        			<?php $this->content('阅读剩余部分...'); ?>
                </div>
    		</article>
    	<?php endwhile; ?>
        <?php else: ?>
            <article class="post">
                <h2 class="post-title"><?php _e('没有找到内容'); ?></h2>
            </article>
        <?php endif; ?>

        <?php $this->pageNav(); ?>
    </div><!-- end #main -->

	<?php $this->need('sidebar.php'); ?>
	<?php $this->need('footer.php'); ?>
