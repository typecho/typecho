<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
    
    <?php if ($this->options->enableCopyrightProtection == 1): ?>
    <style>
      article {
        -webkit-user-select: none;
      }
    </style>
    <script type="text/javascript">
      !function() {
        var o = /x/;
        console.log(o), o.toString = function() {
          window.location.href = "<?php $this->options->themeUrl('component/copyright_notice.php'); ?>";
        };
      }();
      document.onkeydown = function(e) {
        var t = e.keyCode || e.which || e.charCode, n = e.ctrlKey || e.metaKey;
        return n && 83 == t && alert("??"), e.preventDefault(), !1;
      };
    </script>
    <?php endif; ?>
    
    <?php if ($this->options->donateQRLink): ?>
    <style>
      .donate:hover {
        background-image: url(<?php $this->options->donateQRLink(); ?>);
      }
    </style>
    <?php endif; ?>
    
    <?php if ($this->options->colorScheme): ?>
    <style>
      a, a:hover {
        color: rgba(<?php $this->options->colorScheme(); ?>, 0.9);
      }
      .comment-form #misubmit, .post-tag-holder a, .pagination > li.active > a {
        background-color: rgba(<?php $this->options->colorScheme(); ?>, 0.9);
      }
      .blog-nav .blog-nav-item:focus, .blog-nav .blog-nav-item:hover, article .more a, .pagination > li > a, .pagination > li > span, .pagination > li.active > a {
	color: rgba(<?php $this->options->colorScheme(); ?>, 0.9);
      }
      .post-tag-holder a, article .more a, .pagination > li > a, .pagination > li > span, .pagination > li.active > a {
        border: 1px solid rgba(<?php $this->options->colorScheme(); ?>, 0.9);
      }
      .post-tag-holder a:hover, article .more a:hover, .pagination > li > a:hover, .pagination > li > span:hover, .pagination > li.active > a, .pagination > li.active > a:hover {
        color: white;
	background-color: rgba(<?php $this->options->colorScheme(); ?>, 1);
        border: 1px solid rgba(<?php $this->options->colorScheme(); ?>, 1);
      }
    </style>
    <?php endif; ?>

    <style>
      body {
        background-image: url("<?php $this->options->themeUrl('img/bg_pattern1.png'); ?>");
        background-repeat: repeat;
      }
      @media (prefers-color-scheme: dark) {
        body {
          background-image: url("<?php $this->options->themeUrl('img/bg_pattern3.png'); ?>");
        }
    </style>
