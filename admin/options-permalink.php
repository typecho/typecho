<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body body-950">
        <?php include 'page-title.php'; ?>
        <div class="container typecho-page-main">
            <div class="column-22 start-02">
                <?php Typecho_Widget::widget('Widget_Options_Permalink')->form()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
    (function () {
        window.addEvent('domready', function() {

            $(document)
            .getElement('input[name=customPattern]')
            .addEvent('click', function (event) {
                $('postPattern-custom').set('checked', true);
                this.focus();
                event.stop();
            });

        });
    })();
</script>

<?php include 'footer.php'; ?>
