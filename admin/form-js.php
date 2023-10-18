<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<script>
(function () {
    $(document).ready(function () {
        var error = $('.typecho-option .error:first');

        if (error.length > 0) {
            $('html,body').scrollTop(error.parents('.typecho-option').offset().top);
        }

        $('.main form').submit(function () {
            const self = $(this);

            if (self.hasClass('submitting')) {
                return false;
            } else {
                let siteUrl = $('input[name="siteUrl"], input[name="url"]');
                if (siteUrl.length) {
                    const url = new URL(siteUrl.val());
                    siteUrl.val(url.origin);
                }

                $('button[type=submit]', this).attr('disabled', 'disabled');
                self.addClass('submitting');
            }
        }).on('submitted', function () {
            $('button[type=submit]', this).removeAttr('disabled');
            $(this).removeClass('submitting');
        });

        $('label input[type=text]').click(function (e) {
            var check = $('#' + $(this).parents('label').attr('for'));
            check.prop('checked', true);
            return false;
        });
    });
})();
</script>
