<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
    
    <footer class="blog-footer">
      <p>Copyright&nbsp;&copy;&nbsp;<?php echo date('Y'); ?>&nbsp;<a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?>.&nbsp;All&nbsp;right&nbsp;reserved.</a>
      </p>
      <p>For more information about this site, take a look at the About page by clicking the link on the navigation bar above.</p>
    </footer>
    
    <!-- Load Theme Dedicated JS -->
    <script src="https://cdn.bootcdn.net/ajax/libs/highlight.js/10.1.1/highlight.min.js"></script>
    <script src="<?php $this->options->themeUrl('js/loadup.js'); ?>"></script>

    <?php if ($this->options->additionalHTML): $this->options->additionalHTML(); endif; ?>

    <?php if ($this->options->enableMathJax == 1): ?>
    <script src="//cdn.bootcss.com/mathjax/2.7.2/latest.js?config=TeX-MML-AM_SVG"></script>
    <script type="text/x-mathjax-config">
      MathJax.Hub.Config({
        extensions: ["tex2jax.js"],
        jax: ["input/TeX", "output/SVG"],
        tex2jax: {
          inlineMath: [ ['$','$'], ["\\(","\\)"] ],
          displayMath: [ ['$$','$$'], ["\\[","\\]"] ],
          processEscapes: true
        }
      });
    </script>
    <?php endif; ?>

    <?php $this->footer(); ?>

  </body>
</html>