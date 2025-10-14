<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE html>
<html lang="zh-CN">
  <!-- FOXMOE.TOP ONLY -->
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="sogou_site_verification" content="zypCQpVIwW" />
    <script>(function(){try{var t=localStorage.getItem('theme')||'light';if(t==='dark'){document.documentElement.classList.add('dark-theme');document.documentElement.setAttribute('data-theme','dark');}else{document.documentElement.setAttribute('data-theme','light');}}catch(e){}})();</script>
    <title><?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 %s 下的文章'),
            'author'   => _t('%s 发布的文章')
        ], '', ' - '); ?><?php $this->options->title(); ?></title>

    <?php
    $description = '';
    if ($this->is('index')) {
        $description = $this->options->description;
    } elseif ($this->is('post') || $this->is('page')) {
        if ($this->fields->excerpt) {
            $description = strip_tags($this->markdown($this->fields->excerpt));
        } else {
            $description = strip_tags($this->markdown($this->excerpt));
        }
        $description = mb_substr($description, 0, 150, 'UTF-8');
    } elseif ($this->is('category')) {
        $description = $this->getDescription();
    }
    if (!empty($description)) {
        echo '<meta name="description" content="' . htmlspecialchars($description) . '" />' . "\n";
    }
    ?>

    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/normalize.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/core.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/layout.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/cards.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/components.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/theme.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/comments.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/utilities.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/bg-motion.css'); ?>">
    <link rel="icon" type="image/x-icon" href="<?php $this->options->themeUrl('favicon.ico'); ?>">
    <?php $this->header(); ?>
    <script>
      // 兼容模块化 CSS
      window.THEME_URL = (function(){
        try {
          var link = document.querySelector('link[rel="stylesheet"][href*="css/core.css"], link[rel="stylesheet"][href*="css/layout.css"], link[rel="stylesheet"][href*="css/normalize.css"], link[rel="stylesheet"][href*="css/style.css"]');
          if (!link) return '';
          var a = document.createElement('a');
          a.href = link.getAttribute('href');
          return a.href.replace(/css\/(?:core|layout|normalize|style)\.css.*$/, '');
        } catch(e) { return ''; }
      })();
      // 主题运行参数（由主题设置注入）
      window.THEME_OPTS = {
        autoThemeNightStart: <?php echo json_encode(foxmoe_opt('autoThemeNightStart', '18:00')); ?>,
        autoThemeDayStart: <?php echo json_encode(foxmoe_opt('autoThemeDayStart', '06:00')); ?>,
        toastDuration: <?php echo json_encode(intval(foxmoe_opt('toastDuration', '3000'))); ?>
      };
    </script>
<!-- Clarity tracking code for https://foxmoe.top/ -->
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "ss3a1su3uz");
</script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-9X6VT5G4FZ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-9X6VT5G4FZ');
</script>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-PNRHLLB2');</script>
<!-- End Google Tag Manager -->
    <!-- 字体 -->
    <link rel="preload" href="<?php $this->options->themeUrl('css/fonts.css'); ?>" as="style">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('css/fonts.css'); ?>">
    <!-- 预加载 -->
    <link rel="preload" href="<?php $this->options->themeUrl('fonts/MaterialIcons-Regular.woff2'); ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php $this->options->themeUrl('fonts/InterVariable.woff2'); ?>" as="font" type="font/woff2" crossorigin>
</head>
<body<?php if (!empty($this->options->runtimeStart)): ?> data-runtime-start="<?php echo htmlspecialchars($this->options->runtimeStart, ENT_QUOTES); ?>"<?php endif; ?>>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-brand">
                    <a class="nav-logo" href="<?php $this->options->siteUrl(); ?>">
                        <img src="<?php $this->options->themeUrl('image/logo/640_64.png'); ?>" alt="Foxmoe Logo" class="brand-logo">
                        <span class="brand-text"><?php $this->options->title(); ?></span>
                    </a>
                </div>
                <div class="nav-right">
                    <ul class="nav-menu" id="mainNavMenu" aria-label="主导航">
                        <li><a href="<?php $this->options->siteUrl(); ?>" class="nav-link<?php if ($this->is('index')): ?> active<?php endif; ?>">首页</a></li>
                        <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
                        <?php while ($pages->next()): ?>
                            <li><a href="<?php $pages->permalink(); ?>" class="nav-link<?php if ($this->is('page') && $this->slug == $pages->slug): ?> active<?php endif; ?>"><?php $pages->title(); ?></a></li>
                        <?php endwhile; ?>
                        <!-- 移动端菜单内搜索区域 -->
                        <li class="nav-search" id="mobileMenuSearch" hidden>
                          <form method="post" action="<?php $this->options->siteUrl(); ?>" role="search" aria-label="站内搜索 (移动菜单)">
                            <input type="text" name="s" class="search-input" placeholder="搜索..." value="<?php if (isset($_POST['s'])) echo htmlspecialchars($_POST['s']); ?>" />
                            <button type="submit" class="search-submit" aria-label="开始搜索"><span class="material-icons">search</span></button>
                          </form>
                        </li>
                    </ul>
                    <div class="nav-actions">
                        <button type="button" class="search-btn" id="globalSearchBtn" aria-label="打开搜索" aria-haspopup="true" aria-expanded="false">
                            <span class="material-icons">search</span>
                        </button>
                        <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="打开菜单" aria-expanded="false">
                            <span class="material-icons">menu</span>
                        </button>
                        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="打开侧栏" aria-expanded="false">
                            <span class="material-icons">view_sidebar</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <?php $this->need('search-form.php'); ?>
    <div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>
    <aside class="mobile-sidebar" id="mobileSidebar" aria-hidden="true" aria-label="移动侧栏">
        <?php $this->need('sidebar.php'); ?>
    </aside>


    <script>
    (function(){
      var dot = document.createElement('div');
      dot.id = 'cursor-dot';
      function appendDot(){ if(!dot.isConnected) document.body.appendChild(dot); initSize(); }
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', appendDot); else appendDot();

      var halfSize = 40; // fallback
      function initSize(){
        try {
          var w = parseFloat(getComputedStyle(dot).width);
          if (!isNaN(w) && w > 0) halfSize = w / 2;
        } catch(e) {}
      }
      window.addEventListener('resize', initSize, {passive:true});

      var targetX = -9999, targetY = -9999;
      var x = targetX, y = targetY;
      var ease = 0.35; // 跟手
      var ticking = false;
      var offset = 4; // 右下偏移 16px

      function onMove(e){
        targetX = e.clientX; targetY = e.clientY;
        if (!ticking) { ticking = true; requestAnimationFrame(step); }
      }
      function onLeave(){ targetX = -9999; targetY = -9999; if (!ticking) { ticking = true; requestAnimationFrame(step); } }

      function step(){
        x += (targetX - x) * ease;
        y += (targetY - y) * ease;
        dot.style.transform = 'translate3d(' + (x - halfSize + offset) + 'px,' + (y - halfSize + offset) + 'px,0)';
        // 收敛后停止下一帧
        if (Math.abs(targetX - x) < 0.5 && Math.abs(targetY - y) < 0.5) { ticking = false; return; }
        requestAnimationFrame(step);
      }

      window.addEventListener('pointermove', onMove, {passive: true});
      window.addEventListener('pointerleave', onLeave, {passive: true});
      window.addEventListener('blur', onLeave);
    })();
    </script>

    <script>
    // ================ 顶栏交互 ================
    (function(){
      window.__NEW_HEADER_NAV = true; // 新版导航启用标记
      function initHeaderInteractions(){
        var body=document.body;
        var searchPanel=document.getElementById('headerSearchPanel');
        var searchBtn=document.getElementById('globalSearchBtn');
        var menuBtn=document.getElementById('mobileMenuBtn');
        var navMenu=document.getElementById('mainNavMenu');
        var sidebarBtn=document.getElementById('sidebarToggleBtn');
        var sidebar=document.getElementById('mobileSidebar');
        var sidebarOverlay=document.getElementById('mobileSidebarOverlay');
        var mobileMenuSearch=document.getElementById('mobileMenuSearch');

        var mq=window.matchMedia('(max-width:768px)');
        var isMobile=function(){return mq.matches;};
        var lastIsMobile=isMobile();
        (mq.addEventListener||mq.addListener).call(mq,'change',onBreakpointChange);

        function syncMobileMenuSearch(){ if(!mobileMenuSearch) return; if(isMobile()){ mobileMenuSearch.removeAttribute('hidden'); } else { mobileMenuSearch.setAttribute('hidden',''); } }
        syncMobileMenuSearch();

        // 搜索面板
        function openSearch(){ if(!searchPanel) return; searchPanel.setAttribute('aria-hidden','false'); setTimeout(function(){ var inp=searchPanel.querySelector('.search-input'); inp&&inp.focus(); },40); }
        function closeSearch(){ if(!searchPanel) return; searchPanel.setAttribute('aria-hidden','true'); }
        searchBtn&&searchBtn.addEventListener('click',function(e){ e.stopPropagation(); (searchPanel.getAttribute('aria-hidden')==='false')?closeSearch():openSearch(); });
        document.addEventListener('click',function(e){ if(!searchPanel||searchPanel.getAttribute('aria-hidden')==='true') return; if(searchPanel.contains(e.target)) return; if(searchBtn&&searchBtn.contains(e.target)) return; closeSearch(); });
        document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ closeSearch(); closeMenu(); closeSidebar(); }});
        var scClose=searchPanel&&searchPanel.querySelector('.search-close'); scClose&&scClose.addEventListener('click',closeSearch);

        // 移动菜单
        var menuOpen=false;
        function updateMenuIcon(){ if(!menuBtn) return; var icon=menuBtn.querySelector('.material-icons'); if(icon) icon.textContent = menuOpen ? 'close' : 'menu'; }
        function setMenu(open){ if(!navMenu) return; menuOpen=open; navMenu.classList.toggle('active',menuOpen); menuBtn&&menuBtn.setAttribute('aria-expanded',menuOpen?'true':'false'); updateMenuIcon(); }
        function openMenu(){ if(menuOpen) return; setMenu(true); setTimeout(function(){ if(isMobile()){ var inp=mobileMenuSearch&&mobileMenuSearch.querySelector('.search-input'); inp&&inp.focus(); } },100); }
        function closeMenu(){ if(!menuOpen) return; setMenu(false); }
        function toggleMenu(){ menuOpen?closeMenu():openMenu(); }
        if(menuBtn){
          menuBtn.addEventListener('click',function(e){ e.preventDefault(); e.stopPropagation(); toggleMenu(); });
          menuBtn.addEventListener('keydown',function(e){ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); toggleMenu(); } else if(e.key==='Escape'){ closeMenu(); }});
        }
        updateMenuIcon();
        document.addEventListener('click',function(e){ if(!isMobile()||!menuOpen) return; if(navMenu&&navMenu.contains(e.target)) return; if(menuBtn&&menuBtn.contains(e.target)) return; if(sidebar&&sidebar.contains(e.target)) return; closeMenu(); });
        // 菜单内链接点击
        navMenu&&navMenu.addEventListener('click',function(e){ var a=e.target.closest('a.nav-link'); if(!a) return; if(isMobile()) closeMenu(); var href=a.getAttribute('href'); if(href && href.charAt(0)==='#'){ var target=document.querySelector(href); if(target){ e.preventDefault(); var y=target.getBoundingClientRect().top + window.pageYOffset - 80; window.scrollTo({top:y,behavior:'smooth'}); } } });

        // 抽屉侧栏
        function openSidebar(){ if(!sidebar) return; sidebar.classList.add('active'); sidebarOverlay&&sidebarOverlay.classList.add('active'); sidebarBtn&&sidebarBtn.setAttribute('aria-expanded','true'); body.style.overflow='hidden'; }
        function closeSidebar(){ if(!sidebar) return; sidebar.classList.remove('active'); sidebarOverlay&&sidebarOverlay.classList.remove('active'); sidebarBtn&&sidebarBtn.setAttribute('aria-expanded','false'); body.style.overflow=''; }
        sidebarBtn&&sidebarBtn.addEventListener('click',function(e){ e.preventDefault(); e.stopPropagation(); sidebar.classList.contains('active')?closeSidebar():openSidebar(); });
        sidebarOverlay&&sidebarOverlay.addEventListener('click',closeSidebar);

        function onBreakpointChange(){ var now=isMobile(); if(now!==lastIsMobile){ if(!now){ closeMenu(); closeSidebar(); } else { closeSearch(); } syncMobileMenuSearch(); lastIsMobile=now; } updateMenuIcon(); }
        window.addEventListener('resize',onBreakpointChange,{passive:true});
      }
      if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded',initHeaderInteractions); } else { initHeaderInteractions(); }
    })();
    </script>
</body>
</html>