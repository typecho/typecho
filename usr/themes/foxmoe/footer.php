<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; ?>
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
            <p>
                <a href="https://icp.gov.moe/?keyword=20259233" target="_blank">萌ICP备20259233号</a>
                <a href="https://icp.hentioe.dev/sites/20255198" target="_blank">喵ICP备20255198号</a>
                <a href="https://icp.redcha.cn/beian/ICP-2025110215.html"><img src="https://icp.redcha.cn/static/picture/icplogoi.png" style="height: 20px;">茶ICP备2025110215号</a>
                <a href="https://icp.ekucat.com/beian/2025102333.html" title="KUCAT盟2025102333号" target="_blank"><img
                        style="height:20px;margin-bottom:5px;"
                        src="https://icp.ekucat.com/images/icologo.png">KUCAT盟2025102333号</a>
            </p>
            <p><a href="https://github.com/FoxMoe/foxmoe-blog" target=="_blank">Foxmoe Blog Engine 1.4</a> Based on <a
                    href="https://typecho.org" target="_blank">Typecho</a>
            </p>
            <p>Protected by <a
                    href="https://anubis.techaro.lol/" target="_blank">Anubis</a>
                    <span> 网站已经运行了: <span id="runtime">0天0小时0分钟</span></span>
                    </p>
            <div style="display: flex;justify-items: center;justify-content: center;align-items: baseline;">
                <!-- 不算子 -->
                <a href="https://www.busuanzi.cc/count.php?search=foxmoe.top" title="不蒜子统计" target="_blank">
                    <img style="width:85px;height:25px;" src="https://www.busuanzi.cc/static/images/bsz-tongji.png">
                </a>
                <!-- MySSL SSL -->
                <a href="https://myssl.com/foxmoe.top?from=mysslid"><img
                        src="https://static.myssl.com/res/images/myssl-id1.png" alt=""
                        style="max-height:50px;display:block;margin:0 auto"></a>

                <!-- Trust SSL -->
                <a onclick="window.open('https://www.trustssl.cc/trust.php?sn=Zm94bW9lLnRvcA==','Trust SSL安全签章','height=1000,width=560')"
                    title="Trust SSL安全认证签章" target="_blank">
                    <img src="https://static.coolcdn.cn/images/TrustSSL.png" style="width:100px;height:36px;">
                </a>

            </div>
            <!-- <p>ICP备案号: <a href="https://beian.miit.gov.cn/" target="_blank">京ICP备XXXXXXXX号</a></p> -->
            <!-- <p><img src="https://list.mczfw.cn/mc/mc.foxmoe.top.png" lazy /></p> -->
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PNRHLLB2" height="0" width="0"
                    style="display:none;visibility:hidden"></iframe></noscript>
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
<script src="//cdn.busuanzi.cc/busuanzi/3.6.9/busuanzi.min.js" async></script>
<script src="https://vercount.one/js" async></script>
</body>

</html>