
    </div><!-- end .col-group -->
</div><!-- end .container -->

<footer id="footer">
    <?php _e('Powered by'); ?> <a href="http://www.typecho.org">Typecho)))</a>
    <br />
    <a href="<?php $this->options->feedUrl(); ?>"><?php _e('文章'); ?> RSS</a>
    &bull;
    <a href="<?php $this->options->commentsFeedUrl(); ?>"><?php _e('评论'); ?> RSS</a>
</footer><!-- end #footer -->

<?php $this->footer(); ?>
</body>
</html>
