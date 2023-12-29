<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $themeStyle = new \Typecho\Widget\Helper\Form\Element\Radio(
        'themeStyle',
        array(
            'auto' => _t('自动'),
            'light' => _t('浅色'),
            'dark' => _t('深色')
        ),
        'auto',
        _t('外观风格')
    );

    $form->addInput($themeStyle);
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
            <li><i data-feather="calendar" class="is-sm me-2"></i><time datetime="<?php $archive->date('c'); ?>" itemprop="datePublished"><?php $archive->date(); ?></time></li>
            <li><i data-feather="folder" class="is-sm me-2"></i><?php $archive->category(', '); ?></li>
            <li><i data-feather="message-circle" class="is-sm me-2"></i><a href="<?php $archive->permalink() ?>#comments"  itemprop="discussionUrl"><?php $archive->commentsNum(_t('暂无评论'), _t('仅有一条评论'), _t('已有 %d 条评论')); ?></a></li>
        </ul>
        <?php endif; ?>
    </header>
<?php
}
