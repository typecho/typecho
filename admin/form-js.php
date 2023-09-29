<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<script>
(function () {
    $(document).ready(function () {
        var error = $('.typecho-option .error:first');

        if (error.length > 0) {
            $('html,body').scrollTop(error.parents('.typecho-option').offset().top);
        }

        $('form').submit(function () {
            if (this.submitted) {
                return false;
            } else {
                let siteUrl = $('input[name="siteUrl"], input[name="url"]');
                if (siteUrl.length) {
                    const url = new URL(siteUrl.val());
                    siteUrl.val(url.origin);
                }
                this.submitted = true;
            }
        });

        $('label input[type=text]').click(function (e) {
            var check = $('#' + $(this).parents('label').attr('for'));
            check.prop('checked', true);
            return false;
        });
    });
})();
</script>
