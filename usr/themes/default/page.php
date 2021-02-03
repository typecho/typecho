<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>


		<div class="row-2 container">
			<div class="row-2 row">
				<div class="gcol-mb-12 gcol-8">
					<div class="item">
                        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
                            <h1 class="post-title" itemprop="name headline"><a itemprop="url" href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h1>
                            <div class="post-content" itemprop="articleBody">
                                <?php $this->content(); ?>
                            </div>
                        </article>
                    </div>
                    <div class="item">
                        <?php $this->need('comments.php'); ?>
                    </div>
				</div>

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
