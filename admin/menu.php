<?php
if (!defined('__TYPECHO_ADMIN__')) {
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-stick-dark" data-navbar="sticky">
	<div class="container">

		<div class="navbar-left mr-4">
			<button class="navbar-toggler" type="button">☰</button>
			<a class="navbar-brand" href="#">
				<img class="logo-dark" src="<?php echo Typecho_Common::url('logo-dark.png', $options->adminStaticUrl('assets/img')) ?>" alt="logo">
				<img class="logo-light" src="<?php echo Typecho_Common::url('logo-light.png', $options->adminStaticUrl('assets/img')) ?>" alt="logo">
			</a>
		</div>

		<section class="navbar-mobile">
			<nav class="nav nav-navbar mr-auto">
                <?php $menu->output(); ?>
			</nav>

			<ul class="nav">
				<li class="nav-item">
					<a class="nav-link" title="<?php
                    if ($user->logged > 0) {
                        $logged = new Typecho_Date($user->logged);
                        _e('最后登录: %s', $logged->word());
                    }
                    ?>" href="<?php $options->adminUrl('profile.php'); ?>"><?php $user->screenName(); ?></a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?php $options->logoutUrl(); ?>"><?php _e('登出'); ?></a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?php $options->siteUrl(); ?>" target="_blank"><?php _e('网站'); ?></a>
				</li>
			</ul>
		</section>
	</div>
</nav>

