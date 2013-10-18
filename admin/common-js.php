<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script src="<?php $options->adminUrl('js/jquery.js?v=' . $suffixVersion); ?>"></script> 
<script src="<?php $options->adminUrl('js/jquery-ui.js?v=' . $suffixVersion); ?>"></script> 
<script src="<?php $options->adminUrl('js/typecho.js?v=' . $suffixVersion); ?>"></script>
<script>
    (function () {
        $(document).ready(function() {
            <?php if ($notice->highlight): ?>                
            //增加高亮效果
            $('#<?php echo $notice->highlight; ?>').effect('highlight', 1000);
            <?php endif; ?>

            //增加淡出效果
            (function () {
                var p = $('.popup');

                if (p.length > 0) {
                    var head = $('.typecho-head-nav'), 
                        offset = head.length > 0 ? head.outerHeight() : 0;

                    function checkScroll () {
                        if ($(window).scrollTop() >= offset) {
                            p.css({
                                'position'  :   'fixed',
                                'top'       :   0
                            });
                        } else {
                            p.css({
                                'position'  :   'absolute',
                                'top'       :   offset
                            });
                        }
                    }

                    $(window).scroll(function () {
                        checkScroll();
                    });

                    checkScroll();

                    p.slideDown(function () {
                        var t = $(this), color = '#C6D880';
                        
                        if (t.hasClass('error')) {
                            color = '#FBC2C4';
                        } else if (t.hasClass('notice')) {
                            color = '#FFD324';
                        }

                        t.effect('highlight', {color : color})
                            .delay(5000).slideUp(function () {
                            $(this).remove();
                        });
                    });
                }
            })();

            if ($('.typecho-login').length == 0) {
                $('a').each(function () {
                    var t = $(this), href = t.attr('href');

                    if ((href.length > 1 && href[0] == '#')
                        || /^<?php echo preg_quote($options->adminUrl, '/'); ?>.*$/.exec(href) 
                            || /^<?php echo substr(preg_quote(Typecho_Common::url('s', $options->index), '/'), 0, -1); ?>action\/[_a-zA-Z0-9\/]+.*$/.exec(href)) {
                        return;
                    }

                    t.attr('target', '_blank');
                });
            }
        });
    })();
</script>
