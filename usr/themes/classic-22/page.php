<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="container">
    <div class="container-thin">
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <header class="entry-header text-center">
                <h1 class="entry-title" itemprop="name headline"><a itemprop="url"
               href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h1>
            </header>
            
            <div class="entry-content fmt" itemprop="articleBody">
                <?php $this->content(); ?>
            </div>
        </article>
    </div>

    <hr class="post-separator">
    
    <div class="container-thin">
        <?php $this->need('comments.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
