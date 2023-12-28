<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="container">
    <div class="container-thin">
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <header class="entry-header text-center">
                <h1 class="entry-title" itemprop="name headline"><a itemprop="url"
               href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h1>
                <ul class="entry-meta list-inline text-muted">
                    <li><i data-feather="calendar" class="is-sm me-2"></i><time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date(); ?></time></li>
                    <li><i data-feather="folder" class="is-sm me-2"></i><?php $this->category(', '); ?></li>
                    <li><i data-feather="message-circle" class="is-sm me-2"></i><a href="#comments"  itemprop="discussionUrl"><?php $this->commentsNum('暂无评论', '1 条评论', '%d 条评论'); ?></a></li>
                </ul>
            </header>
            
            <div class="entry-content fmt" itemprop="articleBody">
                <?php $this->content(); ?>
                <p itemprop="keywords"><?php _e('标签：'); ?><?php $this->tags(', ', true, '无'); ?></p>
            </div>
        </article>
    </div>

    <div class="grid post-next">
        <div>
            <div class="text-muted">&laquo; 上一篇</div>
            <?php $this->thePrev('%s', '没有了'); ?>
        </div>
        <div class="text-end">
            <div class="text-muted">下一篇 &raquo;</div>
            <?php $this->theNext('%s', '没有了'); ?>
        </div>
    </div>

    <div class="container-thin">
        <?php $this->need('comments.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
