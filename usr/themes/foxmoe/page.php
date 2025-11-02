<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; ?>
<?php $this->need('header.php'); ?>

<main class="main-container">
    <div class="content-wrapper">
        <div class="main-content">
            <article class="page-detail" itemscope itemtype="http://schema.org/WebPage">
                <!-- 页面头部信息 -->
                <header class="page-header">
                    <h1 class="page-title" itemprop="name headline"><?php $this->title(); ?></h1>
                    <!-- 桌面端右上角布局切换按钮 -->
                    <button class="layout-toggle" type="button" aria-label="切换布局" title="切换布局">
                        <img class="layout-toggle-icon" src="<?php $this->options->themeUrl('img/expand.svg'); ?>"
                            alt="toggle layout">
                    </button>
                    <div class="page-meta">
                        <div class="meta-item">
                            <span class="material-icons">schedule</span>
                            <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished">
                                最后更新：<?php $this->date('Y年m月d日'); ?>
                            </time>
                        </div>
                    </div>
                </header>

                <!-- 页面内容 -->
                <div id="content" class="page-content" itemprop="articleBody">
                    <?php $this->content(); ?>
                </div>
            </article>

            <!-- 评论区域（如果页面开启评论） -->
            <?php if ($this->allow('comment')): ?>
                <section class="comments-section glass-effect" id="comments">
                    <?php $this->need('comments.php'); ?>
                </section>
            <?php endif; ?>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>