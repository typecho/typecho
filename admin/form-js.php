<script>
(function () {
    $(document).ready(function () {
        var error = $('.typecho-option .error:first');

        if (error.length > 0) {
            $('html,body').scrollTop(error.offset().top);
        }

        $('form').submit(function () {
            $('*[type=submit]', this).prop('disabled', true);
        });

        $('label input[type=text]').click(function (e) {
            var check = $('#' + $(this).parents('label').attr('for'));
            check.prop('checked', true);
            return false;
        });
    });
})();
</script>
