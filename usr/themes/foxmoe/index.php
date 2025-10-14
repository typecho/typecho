<?php
/**
 * Foxmoe Theme
 *
 * @package Foxmoe
 * @author Foxmoe TOP
 * @version 1.3
 * @link https://foxmoe.top
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>
<main class="main-container">
    <section class="hero-banner" id="home">
        <div class="hero-background">
            <img src="" data-src="https://www.dmoe.cc/random.php" referrerpolicy="no-referrer" alt="Hero" class="hero-bg-img lazy-hero" decoding="async" loading="lazy" fetchpriority="low">
        </div>
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">欢迎来到 <?php $this->options->title(); ?></h1>
                <p class="hero-subtitle" id="hero-subtitle"></p>
            </div>
        </div>
    </section>
    <script>
      (function(){
        // 背景
        function loadHero(){
          var img = document.querySelector('.lazy-hero');
            if(!img || img.dataset.loaded) return;
            var real = img.getAttribute('data-src');
            if(!real) return;
            var tmp = new Image();
            tmp.referrerPolicy = 'no-referrer';
            tmp.onload = function(){
              img.src = real;
              img.dataset.loaded = '1';
              img.classList.add('loaded');
            };
            tmp.onerror = function(){ img.dataset.loaded='1'; };
            tmp.src = real + (real.indexOf('?')>-1?'&':'?') + 'ts=' + Math.floor(Date.now()/60000);
        }
        if('requestIdleCallback' in window){ requestIdleCallback(loadHero,{timeout:1200}); } else if('IntersectionObserver' in window){
          var io=new IntersectionObserver(function(es){es.forEach(e=>{ if(e.isIntersecting){loadHero();io.disconnect();}});});
          io.observe(document.querySelector('.lazy-hero'));
        } else {
          window.addEventListener('load', loadHero);
        }

        // 一言
        function setHitokoto(){
          var el = document.getElementById('hero-subtitle') || document.querySelector('.hero-subtitle');
          if(!el) return;
          fetch('<?php $this->options->themeUrl('hitokoto.php'); ?>', { cache: 'no-store' })
            .then(function(r){ if(!r.ok) throw new Error('net'); return r.json(); })
            .then(function(d){ if(d && d.hitokoto){ el.textContent = d.hitokoto; } })
            .catch(function(){});
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setHitokoto); else setHitokoto();
        if (window.jQuery && jQuery(document).on) { jQuery(document).on('pjax:end', function(){ loadHero(); setHitokoto(); }); }
      })();
    </script>
    <div class="content-wrapper">
        <div class="main-content">
            <section class="posts-list-section">
                <h2 class="section-title">最新文章</h2>
                <div class="posts-list">
                    <?php while ($this->next()): ?>
                    <article class="post-card" data-url="<?php $this->permalink(); ?>">
                        <div class="post-image-wrapper">
                            <?php if ($this->fields->thumbnail): ?>
                                <div class="post-image">
                                <?php $prefix = foxmoe_opt('githubImageProxy', ''); ?>
                                <img src="<?php echo $prefix . ltrim($this->fields->thumbnail, '/'); ?>" alt="<?php $this->title(); ?>" class="post-img">
                            <?php else: ?>
                                <div class="post-noimage">
                            <?php endif; ?>
                                <div class="post-category"><?php $this->category(',', false); ?></div>
                            </div>
                        </div>
                        <div class="post-content">
                            <h3 class="post-title"><a href="<?php $this->permalink(); ?>" class="post-title-link"><?php $this->title(); ?></a></h3>
                            <p class="post-excerpt"><?php echo mb_substr(strip_tags($this->markdown($this->excerpt)), 0, 120, 'UTF-8') . '...'; ?></p>
                            <div class="post-meta">
                                <div class="post-author">
                                    <img src="<?php $this->options->themeUrl('image/logo/640.png'); ?>" alt="<?php $this->author(); ?>" class="author-avatar">
                                    <span><?php $this->author(); ?></span>
                                    <span class="post-date"><?php $this->date('Y-m-d'); ?></span>
                                </div>
                                
                                <span class="post-comments">
                                    <a href="<?php $this->permalink(); ?>#comments"><?php $this->commentsNum('评论', '1条评论', '%d条评论'); ?></a>
                                </span>
                            </div>
                            <div class="post-tags"><?php $this->tags(',', true, ''); ?></div>
                        </div>
                    </article>
                    <?php endwhile; ?>
                </div>
                <div class="page-nav">
                    <?php $this->pageNav('&laquo; 上一页', '下一页 &raquo;'); ?>
                </div>
            </section>
        </div>
        <?php $this->need('sidebar.php'); ?>
    </div>
</main>
<?php $this->need('footer.php'); ?>
