<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE html>
<html lang="zh-Hans" data-theme="<?php $this->options->themeStyle(); ?>">

<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $this->archiveTitle('', '', ' - '); ?><?php $this->options->title(); ?></title>

    <link rel="stylesheet" href="<?php $this->options->themeUrl('static/css/style.css'); ?>">

    <?php $this->header(); ?>
</head>

<body>

<header class="site-navbar container-fluid">
    <div class="container-inner">
        <nav>
            <ul class="site-name">
            <?php if ($this->options->logoUrl): ?>
                <li><a href="<?php $this->options->siteUrl(); ?>" class="brand"><img src="<?php $this->options->logoUrl() ?>" alt="<?php $this->options->title() ?>"></a></li>
            <?php else: ?>
                <li>
                    <a href="<?php $this->options->siteUrl(); ?>" class="brand"><?php $this->options->title() ?></a>
                </li>
                <li class="desc"><?php $this->options->description() ?></li>
            <?php endif; ?>
            </ul>

            <ul>
                <li>
                    <label for="nav-toggler" class="nav-toggler-btn"><img src="<?php $this->options->themeUrl('static/img/menu.svg'); ?>" alt="Menu"></label>
                </li>
            </ul>
        </nav>

        <nav class="site-nav">
            <input type="checkbox" id="nav-toggler">

            <ul class="nav-menu">
                <li>
                    <a href="<?php $this->options->siteUrl(); ?>"<?php if ($this->is('index')): ?> class="active"<?php endif; ?>><?php _e('首页'); ?></a>
                </li>

                <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
                <?php while ($pages->next()): ?>
                <li>
                    <a href="<?php $pages->permalink(); ?>"<?php if ($this->is('page', $pages->slug)): ?> class="active"<?php endif; ?>><?php $pages->title(); ?></a>
                </li>
                <?php endwhile; ?>
                <li>
                    <form method="post" action="<?php $this->options->siteUrl(); ?>">
                        <input type="search" id="s" name="s">
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</header>
