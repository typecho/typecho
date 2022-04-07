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
