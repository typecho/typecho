<?php
include 'common.php';

if ($user->hasLogin()) {
    $response->redirect($options->adminUrl);
}
$rememberName = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_name'));
Typecho_Cookie::delete('__typecho_remember_name');

$bodyClass = 'layout-centered bg-img';
$bodyStyle = 'background-image: url(' . Typecho_Common::url('bg/4.jpg?v=' . $suffixVersion, $options->adminStaticUrl('assets/img')) . ');';

include 'header.php';
?>

<main class="main-content">
	<div class="bg-white rounded shadow-7 w-400 mw-100 p-6">
		<h5 class="mb-7"><? _e('登录')?></h5>
		<form action="<?php $options->loginAction(); ?>" method="post" name="login" role="form">
			<div class="form-group">
				<input type="text" class="form-control" name="name" value="<?php echo $rememberName; ?>" placeholder="<?php _e('用户名'); ?>" autofocus />
			</div>

			<div class="form-group">
				<input type="password" class="form-control" name="password" placeholder="<?php _e('密码'); ?>" />
			</div>

			<div class="form-group flexbox py-3">
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" name="remember" value="1" checked>
					<label class="custom-control-label"><?php _e('下次自动登录'); ?></label>
				</div>

<!--				<a class="text-muted small-2" href="user-recover.html">Forgot password?</a>-->
			</div>

			<div class="form-group">
				<input type="hidden" name="referer" value="<?php echo htmlspecialchars($request->get('referer')); ?>" />
				<button class="btn btn-block btn-primary" type="submit"><?php _e('登录'); ?></button>
			</div>
		</form>

<!--		<div class="divider">Or Login With</div>-->
<!--		<div class="text-center">-->
<!--			<a class="btn btn-circle btn-sm btn-facebook mr-2" href="#"><i class="fa fa-facebook"></i></a>-->
<!--			<a class="btn btn-circle btn-sm btn-google mr-2" href="#"><i class="fa fa-google"></i></a>-->
<!--			<a class="btn btn-circle btn-sm btn-twitter" href="#"><i class="fa fa-twitter"></i></a>-->
<!--		</div>-->

		<hr class="w-30">

		<p class="text-center text-muted small-2">还没有账号 ? <a href="<?php $options->registerUrl(); ?>"><?php _e('用户注册'); ?></a></p>
	</div>

</main>
<!-- /.main-content -->

<!--<div class="typecho-login-wrap">-->
<!--    <div class="typecho-login">-->
<!--        <h1><a href="http://typecho.org" class="i-logo">Typecho</a></h1>-->
<!--        <form action="--><?php //$options->loginAction(); ?><!--" method="post" name="login" role="form">-->
<!--            <p>-->
<!--                <label for="name" class="sr-only">--><?php //_e('用户名'); ?><!--</label>-->
<!--                <input type="text" id="name" name="name" value="--><?php //echo $rememberName; ?><!--" placeholder="--><?php //_e('用户名'); ?><!--" class="text-l w-100" autofocus />-->
<!--            </p>-->
<!--            <p>-->
<!--                <label for="password" class="sr-only">--><?php //_e('密码'); ?><!--</label>-->
<!--                <input type="password" id="password" name="password" class="text-l w-100" placeholder="--><?php //_e('密码'); ?><!--" />-->
<!--            </p>-->
<!--            <p class="submit">-->
<!--                <button type="submit" class="btn btn-l w-100 primary">--><?php //_e('登录'); ?><!--</button>-->
<!--                <input type="hidden" name="referer" value="--><?php //echo htmlspecialchars($request->get('referer')); ?><!--" />-->
<!--            </p>-->
<!--            <p>-->
<!--                <label for="remember"><input type="checkbox" name="remember" class="checkbox" value="1" id="remember" /> --><?php //_e('下次自动登录'); ?><!--</label>-->
<!--            </p>-->
<!--        </form>-->
<!--        -->
<!--        <p class="more-link">-->
<!--            <a href="--><?php //$options->siteUrl(); ?><!--">--><?php //_e('返回首页'); ?><!--</a>-->
<!--            --><?php //if($options->allowRegister): ?>
<!--            &bull;-->
<!--            <a href="--><?php //$options->registerUrl(); ?><!--">--><?php //_e('用户注册'); ?><!--</a>-->
<!--            --><?php //endif; ?>
<!--        </p>-->
<!--    </div>-->
<!--</div>-->
<?php 
include 'common-js.php';
?>
<script>
// $(document).ready(function () {
//     $('#name').focus();
// });
</script>
<?php
include 'footer.php';
?>
