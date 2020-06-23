<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

        </div><!-- end .row -->
    </div>
</div><!-- end #body -->

<footer id="footer" role="contentinfo">
    &copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a>.
    <?php _e('由 <a href="http://www.typecho.org">Typecho</a> 强力驱动'); ?>.
    <?php _e('本站点使用 <a href="https://haimablog.ooo">海马博客</a> 制作的针对TLS和PHP7专门优化的特殊版本'); ?>.
</footer><!-- end #footer -->

<?php $this->footer(); ?>
</body>
</html>
