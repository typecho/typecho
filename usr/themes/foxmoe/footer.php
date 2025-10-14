<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<footer class="footer">
    <div class="footer-content">
        <div class="footer-left">
            <div class="footer-logo">
                <img src="<?php $this->options->themeUrl('image/logo/640_64.png'); ?>" alt="Foxmoe Logo" class="logo">
                <div class="logo-text">
                    <h3><?php $this->options->title(); ?></h3>
                    <p><?php $this->options->description(); ?></p>
                </div>
            </div>
        </div>
        <div class="footer-right">
            <div class="footer-links">
                <div class="link-group">
                    <h4>快速导航</h4>
                    <ul>
                        <li><a href="<?php $this->options->siteUrl(); ?>">首页</a></li>
                        <li><a href="<?php $this->options->siteUrl(); ?>/archives.html">归档</a></li>
                        <li><a href="<?php $this->options->siteUrl(); ?>/console">控制台</a></li>
                        <li><a href="<?php $this->options->siteUrl(); ?>/about.html">关于</a></li>
                    </ul>
                </div>
                <div class="link-group">
                    <h4>快速链接</h4>
                    <ul>
                        <li><a href="https://pan.foxmoe.top" target="_blank">灯狐苑网盘</a></li>
                        <li><a href="https://img.foxmoe.top" target="_blank">灯狐苑图床</a></li>
                        <li><a href="/rss.xml">RSS</a></li>
                        <li><a href="/sitemap.xml">网站地图</a></li>
                    </ul>
                </div>
                <div class="link-group">
                    <h4>联系方式</h4>
                    <ul>
                        <li><a href="https://github.com/foxmoe" target="_blank">GitHub</a></li>
                        <li><a href="https://space.bilibili.com/150209133" target="_blank">Bilibili</a></li>
                        <li><a href="mailto:dream.qu@qq.com">Email</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="footer-info">
            <p>&copy; <?php echo date('Y'); ?> <?php $this->options->title(); ?>. All rights reserved.</p>
            <p><a href="https://github.com/FoxMoe/foxmoe-blog" target=="_blank">Foxmoe Blog Engine 1.3</a> Based on <a href="https://typecho.org" target="_blank">Typecho</a></p>
            <p>网站运行时间: <span id="runtime">0天0小时0分钟</span></p>
            <!-- <p>ICP备案号: <a href="https://beian.miit.gov.cn/" target="_blank">京ICP备XXXXXXXX号</a></p> -->
            <!-- <p><img src="https://list.mczfw.cn/mc/mc.foxmoe.top.png" lazy /></p> -->
             <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PNRHLLB2"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
        </div>
    </div>
</footer>
<div class="toast-container" aria-live="polite" aria-atomic="true"></div>
<div class="fab-container">
    <button class="fab back-to-top" title="返回顶部">
        <span class="material-icons">keyboard_arrow_up</span>
    </button>
    <button class="fab main-fab">
        <span class="material-icons">settings</span>
    </button>
    <div class="fab-actions">
        <button class="fab action-fab auto-theme" title="自动切换主题">
            <span class="material-icons">brightness_auto</span>
        </button>
        <button class="fab action-fab theme-toggle" title="切换主题">
            <span class="material-icons">dark_mode</span>
        </button>
        <button class="fab action-fab font-size" title="字体大小">
            <span class="material-icons">text_fields</span>
        </button>
    </div>
</div>

<?php $this->footer(); ?>
<script src="<?php $this->options->themeUrl('js/jquery-3.7.1.min.js'); ?>" defer fetchpriority="high"></script>
<script src="<?php $this->options->themeUrl('js/jquery.pjax.min.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('js/components.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('js/main.js'); ?>" defer></script>
</body>
</html>
