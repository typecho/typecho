<?php
/**
 * GAL模板
 *
 * @package custom
 */
?>
<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; ?>

<?php $this->need('header.php'); ?>

<?php $this->need('r18confirm.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/showdown@1.9.1/dist/showdown.min.js"></script>
<script src="<?php $this->options->themeUrl('js/gal-list.js'); ?>" defer></script>

<main class="main-container">
    <div class="content-wrapper always-no-sidebar">
        <div class="main-content slim-padding">
            <article class="page-detail" itemscope itemtype="http://schema.org/WebPage">
                <!-- 页面头部信息 -->
                <header class="page-header">
                    <h1 class="page-title" itemprop="name headline"><?php $this->title(); ?></h1>

                    <button class="layout-toggle" type="button" aria-label="切换布局" title="切换布局">
                        <img class="layout-toggle-icon" src="<?php $this->options->themeUrl('img/expand.svg'); ?>"
                            alt="toggle layout">
                    </button>
                    <div class="page-meta">
                        <div class="meta-item">
                            <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished">
                                最后更新：<?php $this->date('Y年m月d日'); ?>
                            </time>
                            <span><?php get_and_update_post_view($this); ?> 次阅读</span>
                        </div>
                    </div>
                </header>
                <div id="content" class="post-content" itemprop="articleBody">
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