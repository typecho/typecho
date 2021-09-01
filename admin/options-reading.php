<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="form">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2">
                <?php \Widget\Options\Reading::alloc()->form()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
?>
<script>
$('#frontPage-recent,#frontPage-page,#frontPage-file').change(function () {
    var t = $(this);
    if (t.prop('checked')) {
        if ('frontPage-recent' == t.attr('id')) {
            $('.front-archive').addClass('hidden');
        } else {
            $('.front-archive').insertAfter(t.parent()).removeClass('hidden');
        }
    }
});
</script>
<?php
include 'footer.php';
?>
