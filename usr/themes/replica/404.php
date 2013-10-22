<?php $this->need('header.php'); ?>

    <div class="col-mb-12 col-tb-8" id="content">
        <div class="post">
            <h2 class="entry-title">404 - <?php _e('页面没找到'); ?></h2>
            <p>
            <form method="post">
                <div><input type="text" name="s" class="text" size="20" /> <input type="submit" class="submit" value="<?php _e('搜索'); ?>" /></div>
            </form>
            </p>
        </div>

    </div><!-- end #content-->
	<?php $this->need('sidebar.php'); ?>
	<?php $this->need('footer.php'); ?>
