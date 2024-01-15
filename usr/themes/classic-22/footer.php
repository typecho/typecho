<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<footer class="site-footer container-fluid">
    <div class="d-flex justify-content-between container-inner">
        <ul class="list-inline text-muted">
            <li>&copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a></li>
            <li><a href="<?php $this->options->feedUrl(); ?>"><?php _e('RSS'); ?></a></li>
        </ul>
        <ul class="list-inline text-muted">
            <li>
                <?php _e('由 <a href="https://typecho.org">Typecho</a> 强力驱动'); ?>
            </li>
        </ul>
    </div>
</footer>

<?php $this->footer(); ?>

</body>
</html>
