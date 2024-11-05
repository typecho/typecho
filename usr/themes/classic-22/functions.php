<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('网站 Logo'),
        _t('在这里填写图片 URL，网站将显示 Logo')
    );

    $form->addInput($logoUrl->addRule('url', _t('请填写正确的 URL 地址')));

    $colorSchema = new \Typecho\Widget\Helper\Form\Element\Select(
        'colorSchema',
        array(
            null => _t('自动'),
            'light' => _t('浅色'),
            'dark' => _t('深色'),
            'customize' => _t('自定义'),
        ),
        null,
        _t('外观风格'),
        _t('如果选择了自定义，主题将使用 theme.css 的样式')
    );

    $form->addInput($colorSchema);
}

function postMeta(
    \Widget\Archive $archive,
    string $metaType = 'archive'
)
{
?>
    <header class="entry-header text-center">
        <h1 class="entry-title" itemprop="name headline">
            <a href="<?php $archive->permalink() ?>" itemprop="url"><?php $archive->title() ?></a>
        </h1>
        <?php if ($metaType != 'page'): ?>
        <ul class="entry-meta list-inline text-muted">
            <li class="feather-calendar"><time datetime="<?php $archive->date('c'); ?>" itemprop="datePublished"><?php $archive->date(); ?></time></li>
            <li class="feather-folder"><?php $archive->category(', '); ?></li>
            <li class="feather-message"><a href="<?php $archive->permalink() ?>#comments"  itemprop="discussionUrl"><?php $archive->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></a></li>
        </ul>
        <?php endif; ?>
    </header>
<?php
}
