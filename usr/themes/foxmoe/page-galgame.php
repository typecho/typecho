<?php
/**
 * Galgame模板
 *
 * @package custom
 */
?>
<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; ?>

<?php $this->need('header.php'); ?>
<?php $this->need('r18confirm.php'); ?>
<main class="main-container">
<?php
// $ch = curl_init();

// curl_setopt($ch, CURLOPT_URL, "https://gal.foxmoe.top");
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// $content = curl_exec($ch);
// curl_close($ch);
// echo $content;
 ?>
<embed type="text/x-scriptlet" src="https://gal.foxmoe.top/" width="100%" height="1080">
</main>
<?php $this->need('footer.php'); ?>