<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<!DOCTYPE HTML>
<html class="no-js">
  <head>
    <meta charset="<?php $this->options->charset(); ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"/>
    <meta name="kibou" content="lite, 1.0.1.2"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="renderer" content="webkit"/>
    <meta name="author" content="<?php $this->author(); ?>"/>
    <!-- The above meta tags must be at top. -->

    <!-- Page Title -->
    <title><?php $this->archiveTitle(array(
            'category'  =>  _t('/category/%s'),
            'search'    =>  _t('/search: %s'),
            'tag'       =>  _t('/tag/%s'),
            'author'    =>  _t('by %s'),
        ), '', ' - '); ?><?php $this->options->title(); ?></title>

    <!-- Styles for Theme Kibou -->

    <link href="<?php $this->options->themeUrl('css/style.css'); ?>" rel="stylesheet">
    <link href="<?php $this->options->themeUrl('css/highlight.css'); ?>" rel="stylesheet">

    <!-- Styles for individual articles. -->
    <?php if ($this->fields->serif): ?>
      <script>
        window.onload = function makeSerif() {
          var e = document.querySelectorAll('#title, #article').forEach(el => el.classList.add('serif'));
        }; 
      </script> 
    <?php endif; ?>
    <?php $this->need('component/pageload.php'); ?>

    <!--[if lt IE 10]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <?php $this->header(); ?>
  </head>

  <body>
    
    <nav class="blog-nav">
      <div class="nav-container">
        <a class="blog-nav-item" href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a>
        <?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>
        <?php while($pages->next()): ?>
          <a class="blog-nav-item"  href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a>
        <?php endwhile; ?>
        <div class="site-search">
          <form id="search" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
             <input type="text" id="s" name="s" class="text" placeholder="<?php _e('/search'); ?>" />
          </form>
        </div>
      </div>
    </nav><!-- /.blog-nav -->
    
    <?php if ($this->is('index') || $this->is('archive')): ?>
    <div class="blog-masthead">
      <div class="blog-title">
        <div class="blog-container">
          <?php if ($this->is('index')): ?>
          <h1><a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a></h1>
          <p><?php $this->options->description(); ?></p>
          <?php endif; ?>
          <?php if ($this->is('archive')): ?>
          <h1><?php $this->archiveTitle(array(
            'category'  =>  _t('/category/%s'),
            'search'    =>  _t('/search: %s'),
            'tag'       =>  _t('/tag/%s'),
            'author'    =>  _t('by %s'),
          ), '', ''); ?></h1>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- /.blog-masthead -->
    <?php endif; ?>
