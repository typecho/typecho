<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
        <div class="row-2 container">
            <div class="row-2 row">
                <div class="gcol-mb-12 gcol-tb-8 gcol-tb-offset-2">
                    <div class="error-page">
                        <h2 class="post-title">404 - <?php _e('页面没找到'); ?></h2>
                        <p><?php _e('你想查看的页面已被转移或删除了, 要不要搜索看看: '); ?></p>
                        <form method="post">
                            <p><input type="text" name="s" class="text" autofocus /></p>
                            <p><button type="submit" class="submit"><?php _e('搜索'); ?></button></p>
                        </form>
                    </div>

                </div><!-- end #content-->
            </div>
        </div>


	<?php $this->need('footer.php'); ?>
