<?php $this->need('header.php'); ?>

<div class="col-mb-12 col-8" id="main">
    <article class="post">
		<h1 class="post-title"><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h1>
        <div class="post-content">
    		<?php $this->content(); ?>
        </div>
	</article>
	<?php $this->need('comments.php'); ?>
</div><!-- end #main-->

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
