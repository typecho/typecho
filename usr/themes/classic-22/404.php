<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="container">
    <div class="container-thin">
        <h1 class="text-center" style="font-size: 4rem; margin-bottom: 2rem">404</h1>
        <ul style="margin-bottom: 2rem">
            <li>当前页面无法访问，可能没权限或已删除。</li>
            <li>The current page is not accessible, may not have permission or has been deleted.</li>
            <li>La page actuelle n'est pas accessible, elle n'a peut-être pas de droits ou a été supprimée.</li>
            <li>No se puede acceder a la página actual, puede que no tenga permiso o que haya sido eliminada.</li>
            <li>Доступ к текущей странице невозможен, возможно, у нее нет разрешения или она была удалена.</li>
            <li>現在のページにアクセスできない、権限がない、または削除された可能性があります。</li>
        </ul>
        <p class="text-center"><a href="<?php $this->options->siteUrl(); ?>" role="button" class="outline"><?php _e('回首页'); ?></a></p>
    </div>
</main>

<?php $this->need('footer.php'); ?>
