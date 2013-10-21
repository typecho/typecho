<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="content-type" content="text/html; charset=<?php $this->options->charset(); ?>" />
<title><?php $this->archiveTitle(' &raquo; ', '', ' - '); ?><?php $this->options->title(); ?></title>

<!-- 使用url函数转换相关路径 -->
<link rel="stylesheet" type="text/css" media="all" href="<?php $this->options->themeUrl('style.css'); ?>" />

<!-- 通过自有函数输出HTML头部信息 -->
<?php $this->header(); ?>
</head>

<body>
<div id="header" class="container_16 clearfix">
    <div class="grid_14">
    	<form id="search" method="post" action="/">
    		<div><input type="text" name="s" class="text" size="20" /> <input type="submit" class="submit" value="<?php _e('搜索'); ?>" /></div>
        </form>
    	<div id="logo">
    	    <h1><a href="<?php $this->options->siteUrl(); ?>">
            <?php if ($this->options->logoUrl): ?>
            <img height="60" src="<?php $this->options->logoUrl() ?>" alt="<?php $this->options->title() ?>" />
            <?php endif; ?>
            <?php $this->options->title() ?>
            </a></h1>
    	    <p class="description"><?php $this->options->description() ?></p>
        </div>
    </div>
</div><!-- end #header -->

<div id="nav_box" class="clearfix">
    <div class="container_16">
        <ul class="grid_14 clearfix" id="nav_menu">
            <li<?php if($this->is('index')): ?> class="current"<?php endif; ?>><a href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a></li>
            <?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>
            <?php while($pages->next()): ?>
            <li<?php if($this->is('page', $pages->slug)): ?> class="current"<?php endif; ?>><a href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a></li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>

<div class="container_16 clearfix">
