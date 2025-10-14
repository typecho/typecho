$(document).ready(function () {
    App.init();
});

const App = {
    init() {
        this.initNavigation();
        this.initTheme();
        this.initSearch();
        this.initFAB();
        this.initScrollEffects();
        this.initRuntimeCounter();
        this.initPjax();
        this.initMarkdown();
        this.initTables();
        this.initCardClick();
        this.initLayoutToggle();
        this.initToasts();
        this.initAutoTheme();
    },

    initHightight(){
        console.log("highlight");
        if (typeof Prism !== 'undefined') {
var pres = document.getElementsByTagName('pre');
                for (var i = 0; i < pres.length; i++){
                    if (pres[i].getElementsByTagName('code').length > 0)
                        pres[i].className  = 'line-numbers';}
Prism.highlightAll(true,null);}
    },

    initMarkdown() {
        // const renderMarkdown = () => {
        //     if (typeof editormd !== "undefined" && document.getElementById("content")) {
        //         editormd.markdownToHTML("content", {
        //             htmlDecode: "style,script,iframe",
        //             emoji: true,
        //             taskList: true,
        //             tex: true,
        //             flowChart: true,
        //             sequenceDiagram: true,
        //         });
        //     }
        // };
        // renderMarkdown();
        // $(document).on('pjax:complete', renderMarkdown);
    },

    // 包裹文章与页面正文中的表格，添加底部同步滚动条
    initTables() {
        const wrapTables = ($root) => {
            const $scopes = $root && $root.length ? $root : $('.post-content, .page-content');
            $scopes.each(function () {
                const $scope = $(this);
                // 避免重复包裹
                $scope.find('table').each(function () {
                    const $table = $(this);
                    if ($table.closest('.table-scroll-wrap').length) return;

                    // 创建结构：wrap > scroll(top) + bottom-scroll
                    const $wrap = $('<div class="table-scroll-wrap"></div>');
                    const $scrollTop = $('<div class="table-scroll"></div>');
                    const $scrollBottom = $('<div class="table-bottom-scroll"><div class="table-bottom-scroll-content"></div></div>');

                    // 插入到 DOM
                    $table.before($wrap);
                    $scrollTop.append($table);
                    $wrap.append($scrollTop).append($scrollBottom);

                    // 同步宽度：根据表格实际滚动宽度设置底部 placeholder 宽度
                    const syncWidths = () => {
                        const el = $scrollTop.get(0);
                        const scrollWidth = el.scrollWidth;
                        $scrollBottom.find('.table-bottom-scroll-content').width(scrollWidth);
                    };
                    syncWidths();

                    // 绑定双向同步滚动
                    let syncing = false;
                    $scrollTop.on('scroll', function () {
                        if (syncing) return; syncing = true;
                        $scrollBottom.scrollLeft(this.scrollLeft);
                        syncing = false;
                    });
                    $scrollBottom.on('scroll', function () {
                        if (syncing) return; syncing = true;
                        $scrollTop.scrollLeft(this.scrollLeft);
                        syncing = false;
                    });

                    // 监听窗口变化，更新宽度
                    $(window).on('resize', $.throttle(100, syncWidths));
                });
            });
        };

        wrapTables();
        // PJAX 后重新包裹
        $(document).on('pjax:end', () => wrapTables($('.post-content, .page-content')));
    },

    initCardClick() {
        const handleCardClick = (e) => {
            const card = e.target.closest('.post-card');
            if (!card || e.target.closest('a')) return;
            
            const url = card.getAttribute('data-url');
            if (url) {
                if (window.Pjax) {
                    new Pjax().handleLink(card.querySelector('.post-title-link'));
                } else {
                    window.location.href = url;
                }
            }
        };

        $(document).off('click.cardClick').on('click.cardClick', '.post-card', handleCardClick);
    },

    initNavigation() {
        const $header = $('.header');
        const $mobileMenuBtn = $('.mobile-menu-btn');
        const $navMenu = $('.nav-menu');
        const $navLinks = $('.nav-link');
        let lastScrollTop = 0;
        let ticking = false;
        const skipLegacyMenu = !!window.__NEW_HEADER_NAV; // 新版导航

        const updateHeader = () => {
            const scrollTop = $(window).scrollTop();

            if (scrollTop > 100) {
                $header.addClass('scrolled');
            } else {
                $header.removeClass('scrolled');
            }

            if (scrollTop > lastScrollTop && scrollTop > 200) {
                $header.css('transform', 'translateY(-100%)');
            } else {
                $header.css('transform', 'translateY(0)');
            }
            lastScrollTop = scrollTop;
            ticking = false;
        };

        $(window).on('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateHeader);
                ticking = true;
            }
        });

        if(!skipLegacyMenu){
            $mobileMenuBtn.on('click', () => {
                $navMenu.toggleClass('active');
                const icon = $mobileMenuBtn.find('.material-icons').text();
                $mobileMenuBtn.find('.material-icons').text(
                    icon === 'menu' ? 'close' : 'menu'
                );
            });
        }

        $navLinks.on('click', function (e) {
            const target = $(this).attr('href');

            if (target && target.startsWith('#')) {
                e.preventDefault();
                $navLinks.removeClass('active');
                $(this).addClass('active');

                $navMenu.removeClass('active');
                $mobileMenuBtn.find('.material-icons').text('menu');

                const $target = $(target);
                if ($target.length) {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 80
                    }, 600);
                }
            } else {
                $navMenu.removeClass('active');
                $mobileMenuBtn.find('.material-icons').text('menu');
            }
        });
    },

    initTheme() {
        const $body = $('body');
        const savedTheme = localStorage.getItem('theme') || 'light';
        const savedFontSize = localStorage.getItem('fontSize') || 'normal';

        // 应用保存的主题
        this.setTheme(savedTheme);
        this.setFontSize(savedFontSize);

        $(document).off('click.themeToggle').on('click.themeToggle', '.theme-toggle', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            App.setTheme(newTheme);
            localStorage.setItem('theme', newTheme);
            // 手动切换主题时，关闭自动切换
            try { localStorage.setItem('autoTheme', 'off'); } catch(e) {}
            App.toast('已关闭自动切换主题');
        });

        // 字体大小切换
        let fontSizeIndex = ['small', 'normal', 'large'].indexOf(savedFontSize);
        const fontSizes = ['small', 'normal', 'large'];

        $(document).off('click.fontSizeToggle').on('click.fontSizeToggle', '.font-size', (e) => {
            e.preventDefault();
            e.stopPropagation();

            fontSizeIndex = (fontSizeIndex + 1) % fontSizes.length;
            this.setFontSize(fontSizes[fontSizeIndex]);
            localStorage.setItem('fontSize', fontSizes[fontSizeIndex]);
        });
    },

    // 浮动提示系统
    initToasts() {
        if (this._toastInited) return; this._toastInited = true;
        const container = document.querySelector('.toast-container');
        if (!container) return;
            const show = (text, ms) => {
            const defaultMs = (window.THEME_OPTS && window.THEME_OPTS.toastDuration) || 3000;
            ms = ms || defaultMs;
            const el = document.createElement('div');
            el.className = 'toast';
            el.innerHTML = '<div class="toast-text"></div><div class="toast-slider"></div>';
            el.querySelector('.toast-text').textContent = text;
            container.appendChild(el);
            // 动画：入场（确保初始样式生效后再加 show）
            // 强制一次回流，避免同帧添加导致无过渡
            void el.offsetWidth;
            requestAnimationFrame(() => { el.classList.add('show'); });
            // 定时隐藏
            const hideTimer = setTimeout(() => {
                el.classList.remove('show');
                el.classList.add('hide');
                // 读取 CSS 里的过渡时长，保证等到淡出完成再移除
                const cs = getComputedStyle(el);
                const parseDur = (s) => {
                    if (!s) return 0;
                    return s.split(',').map(v=>v.trim()).map(v=> v.endsWith('ms') ? parseFloat(v) : parseFloat(v)*1000).reduce((a,b)=>Math.max(a,b),0);
                };
                const fadeMs = Math.max(parseDur(cs.transitionDuration), parseDur(cs.transitionDelay));
                const rmDelay = (isFinite(fadeMs) && fadeMs>0) ? fadeMs + 50 : 300;
                setTimeout(() => { el.remove(); }, rmDelay);
            }, ms);
            return () => { clearTimeout(hideTimer); if (el.parentNode) el.parentNode.removeChild(el); };
        };
        this.toast = show;
        // PJAX 后容器仍然存在，无需重建
    },

    // 自动切换主题（白天/夜晚）
    initAutoTheme() {
        const isNight = () => {
            const now = new Date();
            const parseHM = (s, fallback) => {
                if (!s || !/^\d{1,2}:\d{2}$/.test(s)) return fallback;
                const [hh, mm] = s.split(':').map(n=>parseInt(n,10));
                return {hh, mm};
            };
            const ns = (window.THEME_OPTS && window.THEME_OPTS.autoThemeNightStart) || '18:00';
            const ds = (window.THEME_OPTS && window.THEME_OPTS.autoThemeDayStart) || '06:00';
            const n = parseHM(ns, {hh:18, mm:0});
            const d = parseHM(ds, {hh:6, mm:0});
            const minutes = now.getHours()*60 + now.getMinutes();
            const nightMin = n.hh*60 + n.mm;
            const dayMin = d.hh*60 + d.mm;
            if (nightMin > dayMin) {
                // 夜晚跨越午夜： [nightMin, 1440) ∪ [0, dayMin)
                return (minutes >= nightMin || minutes < dayMin);
            } else {
                // 夜晚在白天之后（少见配置），则 [nightMin, dayMin)
                return (minutes >= nightMin && minutes < dayMin);
            }
        };

        const applyAuto = () => {
            const mode = isNight() ? 'dark' : 'light';
            const current = localStorage.getItem('theme') || 'light';
            if (current !== mode) {
                this.setTheme(mode);
                localStorage.setItem('theme', mode);
            }
        };

        const val = (localStorage.getItem('autoTheme') || 'on');
        if (val === 'on') { applyAuto(); }

        // 周期性检查，避免长时间停留不变更
        if (this._autoThemeTimer) clearInterval(this._autoThemeTimer);
        this._autoThemeTimer = setInterval(() => {
            if ((localStorage.getItem('autoTheme') || 'on') === 'on') applyAuto();
        }, 5 * 60 * 1000);

        // 首次访问提示
        try {
            if (!localStorage.getItem('visited')) {
                localStorage.setItem('visited', '1');
                if (val === 'on') {
                    this.toast && this.toast('已开启自动切换主题');
                }
            }
        } catch (e) {}

        // FAB 按钮绑定
        $(document).off('click.autoTheme').on('click.autoTheme', '.fab-actions .auto-theme', (e) => {
            e.preventDefault();
            const cur = (localStorage.getItem('autoTheme') || 'on') === 'on';
            const next = !cur;
            localStorage.setItem('autoTheme', next ? 'on' : 'off');
            if (next) { applyAuto(); }
            this.toast && this.toast(next ? '开启自动切换主题' : '关闭自动切换主题');
        });

        // 字体大小按钮提示
        $(document).off('click.fontSizeToast').on('click.fontSizeToast', '.fab-actions .font-size', (e) => {
            this.toast && this.toast('已切换字体大小');
        });

        // 手动切换主题后的提示（在 initTheme 中已关闭自动切换）
        $(document).off('click.themeToggleToast').on('click.themeToggleToast', '.fab-actions .theme-toggle', (e) => {
            const t = (localStorage.getItem('theme') || 'light') === 'dark' ? '深色模式' : '浅色模式';
            this.toast && this.toast('已切换为' + t);
        });
    },

    initPjax() {
        // 加载条
        let $loadingBar = $('.page-loading-bar');
        if ($loadingBar.length === 0) {
            $loadingBar = $('<div class="page-loading-bar"></div>');
            $('body').append($loadingBar);
        }

        this.ensureArchiveCss();

        if (!$.support.pjax) {
            return;
        }

        console.log(`%cFoxmoe Blog Engine v1.3 %cMade with %c❤ %c!`, 'color: magenta;','color: white;','color: red;','color: white;');

        $(document).pjax(
            'a[href]:not([target="_blank"]):not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"]):not([href^="javascript:"])',
            '.main-container',
            { fragment: '.main-container', timeout: 8000 }
        );

        $(document).on('pjax:send', function () {
            $loadingBar.removeClass('progress-30 progress-60 progress-80 progress-100 fade-out').addClass('active');
            setTimeout(() => $loadingBar.addClass('progress-30'), 80);
            setTimeout(() => $loadingBar.addClass('progress-60'), 180);
        });

        $(document).on('pjax:end', () => {
            $('html, body').scrollTop(0);
            const savedTheme = localStorage.getItem('theme') || 'light';
            App.setTheme(savedTheme);
            App.updateActiveNav();
            $(window).trigger('scroll');

            // 归档CSS
            this.ensureArchiveCss();

            // PJAX 后重新初始化运行时计时器
            this.initRuntimeCounter();
            this.initMarkdown();
            this.initHightight();
            this.initCardClick();

            // 重新初始化 tooltip
            if (window.Components && typeof Components.initTooltips === 'function') {
                Components.initTooltips();
            }

            $loadingBar.addClass('progress-100');
            setTimeout(() => {
                $loadingBar.addClass('fade-out');
                setTimeout(() => {
                    $loadingBar.removeClass('active progress-30 progress-60 progress-80 progress-100 fade-out');
                }, 300);
            }, 200);
        });
    },

    ensureArchiveCss() {
        try {
            // 判断页面是否含有归档容器
            if (document.querySelector('.archive-container')) {
                var loaded = !!document.querySelector('link[data-archive-css="1"]');
                if (!loaded) {
                    var href = (window.THEME_URL || '') + 'css/archive.css';
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = href;
                    link.setAttribute('data-archive-css', '1');
                    document.head.appendChild(link);
                }
            }
        } catch (e) {
        }
    },

    setTheme(theme) {
        const $root = $('html');
        const $body = $('body');
        const $themeIcon = $('.theme-toggle .material-icons');

        // 过渡类
        $root.addClass('theme-animating');
        clearTimeout(this._themeAnimatingTimer);
        this._themeAnimatingTimer = setTimeout(() => {
            $root.removeClass('theme-animating');
        }, 260);

        $root.removeClass('light-theme dark-theme');
        $body.removeClass('light-theme dark-theme');

        // data-theme
        if (theme === 'dark') {
            $root.addClass('dark-theme');
            $body.addClass('dark-theme');
            $root.attr('data-theme', 'dark');
        } else {
            $root.attr('data-theme', 'light');
        }

        // 更新图标
        setTimeout(() => {
            if ($themeIcon.length) {
                $themeIcon.text(theme === 'light' ? 'dark_mode' : 'light_mode');
            }
        }, 10);
    },

    setFontSize(size) {
        const $body = $('body');
        $body.removeClass('font-size-small font-size-large');

        if (size !== 'normal') {
            $body.addClass(`font-size-${size}`);
        }
    },

    initSearch() {
        const $searchBtn = $('.search-btn');
        const $searchContainer = $('.search-container');
        const $searchClose = $('.search-close');
        const $searchInput = $('.search-input');
        const $searchSubmit = $('.search-submit');

        $searchBtn.on('click', () => {
            $searchContainer.addClass('active');
            setTimeout(() => $searchInput.focus(), 300);
        });

        $searchClose.on('click', () => {
            $searchContainer.removeClass('active');
            $searchInput.val('');
        });

        $(document).on('keydown', (e) => {
            if (e.key === 'Escape' && $searchContainer.hasClass('active')) {
                $searchContainer.removeClass('active');
                $searchInput.val('');
            }
        });

        $searchSubmit.on('click', () => {
            this.performSearch($searchInput.val());
        });

        $searchInput.on('keypress', (e) => {
            if (e.key === 'Enter') {
                this.performSearch($searchInput.val());
            }
        });

        $(document).on('click', (e) => {
            if (!$searchContainer.is(e.target) &&
                $searchContainer.has(e.target).length === 0 &&
                !$searchBtn.is(e.target) &&
                $searchBtn.has(e.target).length === 0) {
                $searchContainer.removeClass('active');
            }
        });
    },

    performSearch(query) {
        if (!query.trim()) return;
        console.log('搜索:', query);
        $('.search-overlay').removeClass('active');
    },

    initFAB() {
        const $fabContainer = $('.fab-container');
        const $mainFab = $('.main-fab');
        const $backToTop = $('.back-to-top');

        $mainFab.on('click', () => {
            $fabContainer.toggleClass('active');
        });

        $backToTop.on('click', () => {
            $('html, body').animate({ scrollTop: 0 }, { duration: 600, easing: 'easeOutCubic' });
            $fabContainer.removeClass('active');
        });

        $(window).on('scroll', $.throttle(100, () => {
            const scrollTop = $(window).scrollTop();
            if (scrollTop > 200) { $backToTop.addClass('show'); } else { $backToTop.removeClass('show'); }
        }));

        $(document).on('click', (e) => {
            if (!$fabContainer.is(e.target) && $fabContainer.has(e.target).length === 0) {
                $fabContainer.removeClass('active');
            }
        });
    },

    initScrollEffects() {
        let scrollTicking = false;

        const updateScrollEffects = () => {
            $('.fade-in-up:not(.visible)').each(function () {
                const $element = $(this);
                const elementTop = $element.offset().top;
                const windowBottom = $(window).scrollTop() + $(window).height();

                if (elementTop < windowBottom - 50) {
                    $element.addClass('visible');
                }
            });
            scrollTicking = false;
        };

        $(window).on('scroll', () => {
            if (!scrollTicking) {
                requestAnimationFrame(updateScrollEffects);
                scrollTicking = true;
            }
        });
    },

    initRuntimeCounter() {
        const attr = (document.body && document.body.getAttribute('data-runtime-start')) || '';
        const parsed = this.parseRuntimeStart(attr);
        const startDate = parsed || new Date('2025-01-01T00:00:00');
        this._runtimeStart = startDate;

        const updateRuntime = () => {
            const el = document.getElementById('runtime');
            if (!el) return;
            const now = new Date();
            const diff = now - this._runtimeStart;
            if (isNaN(diff) || diff < 0) { el.textContent = '--'; return; }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            el.textContent = `${days}天${hours}小时${minutes}分钟`;
        };

        // 避免重复定时器
        if (this._runtimeTimer) clearInterval(this._runtimeTimer);
        updateRuntime();
        this._runtimeTimer = setInterval(updateRuntime, 60000);
    },

    // 解析 YYYY-MM-DD 或 YYYY-MM-DD HH:MM[:SS]
    parseRuntimeStart(str) {
        if (!str || typeof str !== 'string') return null;
        const s = str.trim();
        const m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
        if (!m) return null;
        const y = parseInt(m[1], 10);
        const mo = parseInt(m[2], 10) - 1;
        const d = parseInt(m[3], 10);
        const hh = m[4] ? parseInt(m[4], 10) : 0;
        const mm = m[5] ? parseInt(m[5], 10) : 0;
        const ss = m[6] ? parseInt(m[6], 10) : 0;
        const dt = new Date(y, mo, d, hh, mm, ss);
        return isNaN(dt.getTime()) ? null : dt;
    },

    updateActiveNav() {
        // 更新导航菜单的激活状态
        const currentPath = window.location.pathname;
        $('.nav-link').removeClass('active');

        $('.nav-link').each(function () {
            const linkPath = new URL($(this).attr('href'), window.location.origin).pathname;
            if (linkPath === currentPath) {
                $(this).addClass('active');
            }
        });
    },

    // 页面布局切换：隐藏/显示侧边栏，扩充主内容
    initLayoutToggle() {
        const applyState = (isNoSidebar) => {
            const $wrap = $('.content-wrapper');
            if (!$wrap.length) return;
            $wrap.toggleClass('no-sidebar', !!isNoSidebar);
        };

        const swapIcon = (btn, state) => {
            try {
                const $img = $(btn).find('.layout-toggle-icon');
                const base = window.THEME_URL || '';
                $img.attr('src', base + (state ? 'img/shrink.svg' : 'img/expand.svg'));
            } catch (e) {}
        };

        const isHome = () => !!document.querySelector('.hero-banner');

        const bind = () => {
            $(document).off('click.layoutToggle').on('click.layoutToggle', '.layout-toggle', function () {
                const wrap = document.querySelector('.content-wrapper');
                if (!wrap) return;
                const now = !wrap.classList.contains('no-sidebar');
                wrap.classList.toggle('no-sidebar', now);
                swapIcon(this, now);
                // 可选：保存状态（仅桌面端）
                if (window.matchMedia('(min-width: 769px)').matches) {
                    try { localStorage.setItem('layoutNoSidebar', String(now)); } catch (e) {}
                }
            });
        };

        // 初始应用上次状态（桌面端）
        let saved = null;
        try { saved = localStorage.getItem('layoutNoSidebar'); } catch (e) { saved = null; }
        const initial = saved === 'true';
        if (window.matchMedia('(min-width: 769px)').matches) {
            // 首页不允许隐藏侧边栏
            if (isHome()) {
                applyState(false);
            } else {
                applyState(initial);
            }
        }
        // 设置初始图标
        $('.layout-toggle').each(function(){ swapIcon(this, initial && !isHome()); });

        bind();
        // PJAX 后重绑并恢复图标/状态
        $(document).on('pjax:end', function () {
            let saved2 = null;
            try { saved2 = localStorage.getItem('layoutNoSidebar'); } catch (e) { saved2 = null; }
            const state = saved2 === 'true';
            if (window.matchMedia('(min-width: 769px)').matches) {
                if (isHome()) {
                    applyState(false);
                    $('.layout-toggle').each(function(){ swapIcon(this, false); });
                } else {
                    applyState(state);
                    $('.layout-toggle').each(function(){ swapIcon(this, state); });
                }
            }
            bind();
        });
    },
};

// 保留工具函数
$.throttle = function (delay, fn) {
    let timeoutID = null;
    let lastExec = 0;

    function wrapper() {
        const elapsed = +new Date() - lastExec;
        const args = Array.prototype.slice.call(arguments);
        const exec = () => {
            lastExec = +new Date();
            fn.apply(this, args);
        };

        clearTimeout(timeoutID);
        if (elapsed > delay) {
            exec();
        } else {
            timeoutID = setTimeout(exec, delay - elapsed);
        }
    }
    return wrapper;
};

$.easing.easeOutCubic = function (x, t, b, c, d) {
    return c * ((t = t / d - 1) * t * t + 1) + b;
};
