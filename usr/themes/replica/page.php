<?php $this->need('header.php'); ?>

<div class="col-mb-12 col-8" id="main">
    <article class="post">
		<h2 class="post-title"><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h2>
		<div class="post-meta">
			<span><?php _e('作者：'); ?><?php $this->author(); ?></span>
			<?php _e('发布时间：'); ?><?php $this->date('F j, Y'); ?>
		</div>
        <div class="post-content">
    		<?php $this->content(); ?>
        </div>
	</article>
	<?php $this->need('comments.php'); ?>
</div><!-- end #main-->

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
