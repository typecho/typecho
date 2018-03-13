<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<script src="<?php $options->adminStaticUrl('assets/js', 'page.min.js?v=' . $suffixVersion); ?>"></script>
<script src="https://cdn.bootcss.com/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="<?php $options->adminStaticUrl('assets/js', 'script.js?v=' . $suffixVersion); ?>"></script>
<!--<script src="--><?php //$options->adminStaticUrl('assets/js', 'typecho.js?v=' . $suffixVersion); ?><!--"></script>-->
<!--<div id="popup-autohide" class="popup col-6 col-md-6" data-animation="slide-up" data-position="bottom-center" data-autohide="5000" data-autoshow="500">-->
<!--	<button type="button" class="close" data-dismiss="popup" aria-label="Close">-->
<!--		<span aria-hidden="true">×</span>-->
<!--	</button>-->
<!--	<div class="media">-->
<!--		<div class="media-body">-->
<!--			<p class="mb-0">We'd like to welcome you to our website and hope you have a great time browsing our-->
<!--							template.</p>-->
<!--		</div>-->
<!--	</div>-->
<!--</div>-->
<script>
    (function () {
        $(document).ready(function () {
            // 处理消息机制
            (function () {
                var prefix = '<?php echo Typecho_Cookie::getPrefix(); ?>',
                    cookies = {
                        notice: $.cookie(prefix + '__typecho_notice'),
                        noticeType: $.cookie(prefix + '__typecho_notice_type'),
                        highlight: $.cookie(prefix + '__typecho_notice_highlight')
                    },
                    path = '<?php echo Typecho_Cookie::getPath(); ?>';
                if (!!cookies.notice && 'success|notice|error'.indexOf(cookies.noticeType) >= 0) {
					var p = $('<div id="popup-autohide" class="popup col-6 col-md-6 show" data-animation="slide-up" data-position="bottom-center"><button type="button" class="close" data-dismiss="popup" aria-label="Close"><span aria-hidden="true">×</span></button><div class="media"><div class="media-body"><p class="mb-0">' + $.parseJSON(cookies.notice).join('<br>') + '</p></div></div></div>');
                    p.prependTo(document.body).delay(10000).slideDown(300, function(){ $(this).remove() });
                    // var modalV = $('#popup-autohide');
                    // setTimeout( function() { modalV.modal('show') }, 1000);

                    // function checkScroll () {
                    //            if ($(window).scrollTop() >= offset) {
                    //                p.css({
                    //                    'position'  :   'fixed',
                    //                    'top'       :   0
                    //                });
                    //            } else {
                    //                p.css({
                    //                    'position'  :   'absolute',
                    //                    'top'       :   offset
                    //                });
                    //            }
                    //        }
                    //
                    //        $(window).scroll(function () {
                    //            checkScroll();
                    //        });
                    //
                    //        checkScroll();
                    //
                    //        p.slideDown(function () {
                    //            var t = $(this), color = '#C6D880';
                    //
                    //            if (t.hasClass('error')) {
                    //                color = '#FBC2C4';
                    //            } else if (t.hasClass('notice')) {
                    //                color = '#FFD324';
                    //            }
                    //
                    //            t.effect('highlight', {color : color})
                    //                .delay(5000).fadeOut(function () {
                    //                $(this).remove();
                    //            });
                    //        });
                    //
                    //
                           $.cookie(prefix + '__typecho_notice', null, {path : path});
                           $.cookie(prefix + '__typecho_notice_type', null, {path : path});
                       }
                    //
                    //    if (cookies.highlight) {
                    //        $('#' + cookies.highlight).effect('highlight', 1000);
                    //        $.cookie(prefix + '__typecho_notice_highlight', null, {path : path});
                    //    }
                }
            )();
            //
            //
            //// 导航菜单 tab 聚焦时展开下拉菜单
            //(function () {
            //    $('#typecho-nav-list').find('.parent a').focus(function() {
            //        $('#typecho-nav-list').find('.child').hide();
            //        $(this).parents('.root').find('.child').show();
            //    });
            //    $('.operate').find('a').focus(function() {
            //        $('#typecho-nav-list').find('.child').hide();
            //    });
            //})();
            //
            //
            //if ($('.typecho-login').length == 0) {
            //    $('a').each(function () {
            //        var t = $(this), href = t.attr('href');
            //
            //        if ((href && href[0] == '#')
            //            || /^<?php //echo preg_quote($options->adminUrl, '/'); ?>//.*$/.exec(href)
            //                || /^<?php //echo substr(preg_quote(Typecho_Common::url('s', $options->index), '/'), 0, -1); ?>//action\/[_a-zA-Z0-9\/]+.*$/.exec(href)) {
            //            return;
            //        }
            //
            //        t.attr('target', '_blank');
            //    });
            //}
            //
            //$('.main form').submit(function () {
            //    $('button[type=submit]', this).attr('disabled', 'disabled');
            //});
        });
    })();
</script>
