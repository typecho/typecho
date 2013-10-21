<?php
/**
 * 这是 Typecho 系统的一套默认皮肤
 * 
 * @package Typecho Default Theme 
 * @author fen
 * @version 1.0.7
 * @link http://typecho.org
 */
 
 $this->need('header.php');
 ?>

<div class="col-mb-12 col-8" id="main">
	<?php while($this->next()): ?>
        <article class="post">
			<h2 class="post-title"><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h2>
			<div class="post-meta">
				<span><?php _e('作者：'); ?><?php $this->author(); ?></span>
				<span><?php _e('时间：'); ?><?php $this->date('F j, Y'); ?></span>
				<span><?php _e('分类：'); ?><?php $this->category(','); ?></span>
				<a href="<?php $this->permalink() ?>#comments"><?php $this->commentsNum('没有评论', '1 条评论', '%d 条评论'); ?></a>
			</div>
            <div class="post-content">
    			<?php $this->content('阅读剩余部分...'); ?>
            </div>
        </article>
	<?php endwhile; ?>

    <?php $this->pageNav(); ?>
</div><!-- end #main-->

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
