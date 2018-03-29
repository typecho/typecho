<?php
include 'common.php';

if ($user->hasLogin() || !$options->allowRegister) {
    $response->redirect($options->siteUrl);
}
$rememberName = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_name'));
$rememberMail = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_mail'));
Typecho_Cookie::delete('__typecho_remember_name');
Typecho_Cookie::delete('__typecho_remember_mail');

$bodyClass = 'layout-centered bg-img';
$bodyStyle = 'background-image: url(' . Typecho_Common::url('bg/4.jpg?v=' . $suffixVersion, $options->adminStaticUrl('assets/img')) . ');';

include 'header.php';
?>
<main class="main-content">
	<div class="bg-white rounded shadow-7 w-400 mw-100 p-6">
		<h5 class="mb-7"><? _e('注册')?></h5>
		<form action="<?php $options->registerAction(); ?>" method="post" name="login" role="form">
			<div class="form-group">
				<input type="text" class="form-control" name="name" value="<?php echo $rememberName; ?>" placeholder="<?php _e('用户名'); ?>" autofocus />
			</div>

			<div class="form-group">
				<input type="text" class="form-control" name="mail" value="<?php echo $rememberMail; ?>" placeholder="<?php _e('Email'); ?>" autofocus />
			</div>

			<div class="form-group">
				<button class="btn btn-block btn-primary" type="submit"><?php _e('注册'); ?></button>
			</div>
		</form>

		<hr class="w-30">

		<p class="text-center text-muted small-2">已有账号 ? <a href="<?php $options->loginUrl(); ?>"><?php _e('用户登录'); ?></a></p>
	</div>

</main>
<?php 
include 'common-js.php';
?>
<script>
$(document).ready(function () {
    $('#name').focus();
});
</script>
<?php
include 'footer.php';
?>
