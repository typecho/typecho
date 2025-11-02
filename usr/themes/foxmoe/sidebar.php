<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; ?>
<aside class="sidebar">
    <?php
    // 读取主题设置中的侧栏开关
    $blocks = $this->options->sidebarBlock;
    if (!is_array($blocks) || empty($blocks)) {
        $blocks = array('ShowAbout', 'ShowRecommend', 'ShowTagCloud', 'ShowCategories', 'ShowRecentComments');
    }
    ?>

    <?php if (in_array('ShowAbout', $blocks)): ?>
        <div class="widget about-widget" id="about">
            <h3 class="widget-title">关于我</h3>
            <div class="about-content">
                <div class="avatar-container">
                    <img src="<?php $this->options->themeUrl('image/logo/640.png'); ?>" alt="Foxmoe Avatar" class="avatar">
                    <div class="avatar-border"></div>
                </div>
                <h4 class="author-name">小狐</h4>
                <p class="author-bio">热爱二次元文化的博主，致力于分享动漫、技术和生活的美好瞬间。</p>
                <div class="social-links">
                    <a href="https://github.com/foxmoe" class="social-link github" target="_blank">
                        <span class="material-icons">code</span>
                    </a>
                    <a href="https://space.bilibili.com/150209133" class="social-link bilibili" target="_blank">
                        <span class="material-icons">play_circle</span>
                    </a>
                    <a href="mailto:dream.qu@qq.com" class="social-link email">
                        <span class="material-icons">email</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <?php if (in_array('ShowCount', $blocks)): ?>
        <div class="widget count-widget" id="enter-count">
            <h3 class="widget-title">访问统计</h3>
            <div class="count-content">
                <?php
                if (function_exists('increment_site_enter_count')) {
                    $enterCount = increment_site_enter_count();
                } else {
                    $enterCount = isset($this->options->enterCount) ? $this->options->enterCount : '1';
                }
                ?>
                <div class="enter-count-title-wrapper">
                    <img class="enter-count-title"
                        src="<?php echo $this->options->themeUrl('image/entercount/title.png'); ?>" alt="网站访问人数">
                </div>
                <div class="enter-count-digits" alt="<?php echo $enterCount; ?>">
                    <?php $enterCountChar = str_split(str_pad($enterCount, 8, '0', STR_PAD_LEFT));
                    for ($i = 0; $i < count($enterCountChar); $i++): ?>
                        <img class="enter-count-digit"
                            src="<?php echo $this->options->themeUrl('image/entercount/' . $enterCountChar[$i] . '.png'); ?>"
                            alt="<?php echo $enterCountChar[$i]; ?>">
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array('ShowRecommend', $blocks)): ?>
        <div class="widget recommend-widget">
            <h3 class="widget-title">推荐阅读</h3>
            <div class="recommend-list">
                <?php
                $recommend = \Widget\Contents\Post\Recent::alloc('pageSize=5&type=post');
                while ($recommend->next()):
                    ?>
                    <div class="recommend-item">
                        <div class="recommend-content">
                            <h4 class="recommend-title">
                                <a href="<?php $recommend->permalink(); ?>"><?php $recommend->title(); ?></a>
                            </h4>
                            <div class="recommend-meta">
                                <span class="recommend-date"><?php $recommend->date('m-d'); ?></span>
                                <span class="recommend-comments"><?php $recommend->commentsNum('%d'); ?>评论</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array('ShowTagCloud', $blocks)): ?>
        <div class="widget tags-widget">
            <h3 class="widget-title">标签云</h3>
            <div class="tag-cloud">
                <?php \Widget\Metas\Tag\Cloud::alloc()->to($tags); ?>
                <?php while ($tags->next()): ?>
                    <a href="<?php $tags->permalink(); ?>" class="tag-item" title="<?php $tags->count(); ?> 篇文章">
                        <?php $tags->name(); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array('ShowCategories', $blocks)): ?>
        <div class="widget categories-widget">
            <h3 class="widget-title">分类目录</h3>
            <div class="categories-list">
                <?php \Widget\Metas\Category\Rows::alloc()->to($categories); ?>
                <?php while ($categories->next()): ?>
                    <div class="category-item">
                        <a href="<?php $categories->permalink(); ?>" class="category-link">
                            <span class="category-name"><?php $categories->name(); ?></span>
                            <span class="category-count"><?php $categories->count(); ?></span>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array('ShowRecentComments', $blocks)): ?>
        <div class="widget comments-widget">
            <h3 class="widget-title">最新评论</h3>
            <div class="recent-comments">
                <?php \Widget\Comments\Recent::alloc()->to($comments); ?>
                <?php while ($comments->next()): ?>
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <?php $comments->gravatar(32); ?>
                        </div>
                        <div class="comment-content">
                            <div class="comment-author">
                                <a href="<?php $comments->permalink(); ?>"><?php $comments->author(false); ?></a>
                            </div>
                            <div class="comment-text">
                                <?php $comments->excerpt(40, '...'); ?>
                            </div>
                            <div class="comment-meta">
                                <span class="comment-date"><?php $comments->date('y-m-d'); ?></span>
                                <span class="comment-post">
                                    在文章 <a href="<?php $comments->permalink(); ?>"><?php $comments->title(); ?></a> 中
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</aside>