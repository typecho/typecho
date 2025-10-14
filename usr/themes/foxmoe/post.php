<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="main-container">
    <div class="content-wrapper">
        <div class="main-content">
            <article class="post-detail<?php if ($this->fields->thumbnail) echo ' has-hero'; ?>" itemscope itemtype="http://schema.org/BlogPosting">
                <?php if ($this->fields->thumbnail): ?>
                <div class="post-hero post-image">
                    <?php $prefix = foxmoe_opt('githubImageProxy', ''); ?>
                    <img src="<?php echo $prefix . ltrim($this->fields->thumbnail, '/'); ?>" alt="<?php $this->title(); ?>" class="post-img">
                    <div class="post-category"><?php $this->category(',', false); ?></div>
                    <button class="layout-toggle" type="button" aria-label="切换布局" title="切换布局">
                        <img class="layout-toggle-icon" src="<?php $this->options->themeUrl('img/expand.svg'); ?>" alt="toggle layout">
                    </button>
                </div>
                <?php else: ?>
                <div class="post-hero post-noimage">
                    <div class="post-category"><?php $this->category(',', false); ?></div>
                    <button class="layout-toggle" type="button" aria-label="切换布局" title="切换布局">
                        <img class="layout-toggle-icon" src="<?php $this->options->themeUrl('img/expand.svg'); ?>" alt="toggle layout">
                    </button>
                </div>
                <?php endif; ?>
                <!-- 文章头部信息 -->
                <header class="post-header">
                    <div class="post-categories">
                        <a class="post-homepage" href="<?php $this->options->siteUrl(); ?>">首页</a>
                        <span>/</span>
                        <?php $this->category(',', false); ?>
                    </div>
                    <h1 class="post-title" itemprop="name headline"><?php $this->title(); ?></h1>
                    <div class="post-meta">
                        <div class="meta-item">
                            <span class="material-icons">person</span>
                            <span itemprop="author" itemscope itemtype="http://schema.org/Person">
                                <a itemprop="name" href="<?php $this->author->permalink(); ?>" rel="author"><?php $this->author(); ?></a>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="material-icons">schedule</span>
                            <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date('Y年m月d日'); ?></time>
                        </div>
                        <div class="meta-item">
                            <span class="material-icons">visibility</span>
                            <span><?php get_and_update_post_view($this); ?> 次阅读</span>
                        </div>
                        <div class="meta-item">
                            <span class="material-icons">comment</span>
                            <a href="#comments"><?php $this->commentsNum('暂无评论', '1条评论', '%d条评论'); ?></a>
                        </div>
                    </div>
                </header>

                <!-- 文章内容 -->
                <div id="content" class="post-content" itemprop="articleBody">
                    <?php $this->content(); ?>
                </div>

                <!-- 文章标签 -->
                <?php if ($this->tags): ?>
                <div class="post-tags">
                    <span class="tags-label">
                        <span class="material-icons">local_offer</span>
                        <span>标签：</span>
                    </span>
                    <div class="tags-list">
                        <?php $this->tags(',', true, '默认标签'); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 文章导航 -->
                <nav class="post-navigation">
                    <div class="nav-item nav-prev">
                        <?php $this->thePrev('<div class="nav-link"><span class="nav-direction">上一篇</span><span class="nav-title">%s</span></div>', '<div class="nav-link disabled"><span class="nav-direction">上一篇</span><span class="nav-title">没有了</span></div>'); ?>
                    </div>
                    <div class="nav-item nav-next">
                        <?php $this->theNext('<div class="nav-link"><span class="nav-direction">下一篇</span><span class="nav-title">%s</span></div>', '<div class="nav-link disabled"><span class="nav-direction">下一篇</span><span class="nav-title">没有了</span></div>'); ?>
                    </div>
                </nav>
            </article>

            <!-- 评论区域 -->
            <section class="comments-section" id="comments">
                <?php $this->need('comments.php'); ?>
            </section>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
