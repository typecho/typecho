<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 替换Gravatar头像为国内镜像源
 */
function gravatar_cn($url) {
    $sources = array(
        'www.gravatar.com',
        '0.gravatar.com',
        '1.gravatar.com',
        '2.gravatar.com'
    );

    // 从主题配置中获取用户选择的镜像源
    $options = Helper::options();
    $new_host = $options->gravatarMirror ? $options->gravatarMirror : 'cn.gravatar.com';

    return str_replace($sources, $new_host, $url);
}

// 注册Gravatar替换过滤器
Typecho_Plugin::factory('Widget_Abstract_Comments')->gravatar = array('GravatarReplace', 'filter');

class GravatarReplace {
    public static function filter($avatar) {
        return gravatar_cn($avatar);
    }
}

function themeConfig($form)
{
    // 移除：站点 LOGO 地址输入框（无需该功能）
    // $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
    //     'logoUrl',
    //     null,
    //     null,
    //     _t('站点 LOGO 地址'),
    //     _t('在这里填入一个图片 URL 地址, 以在网站标题前加上一个 LOGO')
    // );
    // $form->addInput($logoUrl);

    // 新增：站点运行开始时间（使用 HTML5 时间选择器）
    $runtimeStart = new \Typecho\Widget\Helper\Form\Element\Text(
        'runtimeStart',
        null,
        '2025-01-01T00:00:00',
        _t('站点运行开始时间'),
        _t('点击使用浏览器时间选择器；将用于展示站点运行时长')
    );
    if (isset($runtimeStart->input)) {
        $runtimeStart->input->setAttribute('type', 'datetime-local');
        $runtimeStart->input->setAttribute('step', '1'); // 支持到秒
        $runtimeStart->input->setAttribute('placeholder', '例如：2025-01-01T00:00:00');
    }
    $form->addInput($runtimeStart);

    // Gravatar镜像源设置
    $gravatarMirror = new \Typecho\Widget\Helper\Form\Element\Select(
        'gravatarMirror',
        [
            'cn.gravatar.com' => _t('Gravatar官方中国镜像'),
            'gravatar.loli.net' => _t('V2EX镜像 (gravatar.loli.net)'),
            'sdn.geekzu.org' => _t('极客族镜像 (sdn.geekzu.org)'),
            'www.gravatar.com' => _t('Gravatar官方源 (可能较慢)')
        ],
        'cn.gravatar.com',
        _t('Gravatar头像镜像源'),
        _t('选择Gravatar头像的镜像源，国内镜像可以提高加载速度')
    );

    $form->addInput($gravatarMirror);

    // 侧边栏显示模块（与当前侧栏一致）
    $sidebarBlock = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'sidebarBlock',
        [
            'ShowAbout'           => _t('显示 关于我'),
            'ShowRecommend'       => _t('显示 推荐阅读'),
            'ShowTagCloud'        => _t('显示 标签云'),
            'ShowCategories'      => _t('显示 分类目录'),
            'ShowRecentComments'  => _t('显示 最新评论'),
        ],
        ['ShowAbout', 'ShowRecommend', 'ShowTagCloud', 'ShowCategories', 'ShowRecentComments'],
        _t('侧边栏显示')
    );

    $form->addInput($sidebarBlock->multiMode());

    // ===== Hitokoto 与 Github 图床加速源配置 =====
    $hitokotoRemote = new \Typecho\Widget\Helper\Form\Element\Text(
        'hitokotoRemote', NULL, 'https://v1.hitokoto.cn/?encode=json', _t('Hitokoto 接口地址'), _t('一言 API 地址，可自定义其他句子服务')
    );
    $form->addInput($hitokotoRemote);

    $hitokotoUA = new \Typecho\Widget\Helper\Form\Element\Text(
        'hitokotoUA', NULL, 'FoxmoeTheme/1.1 (+https://foxmoe.top)', _t('Hitokoto 请求 UA'), _t('请求一言时使用的 User-Agent')
    );
    $form->addInput($hitokotoUA);

    $githubImageProxy = new \Typecho\Widget\Helper\Form\Element\Text(
        'githubImageProxy', NULL, 'https://get.2sb.org/', _t('Github 图床加速源'), _t('例如 https://get.2sb.org/ ，末尾保留 / ，留空则不加速；会在文章缩略图/列表中作为前缀拼接字段值')
    );
    $form->addInput($githubImageProxy);

    // 自动切换主题的时间配置（夜晚开始/白天开始）
    $autoThemeNightStart = new \Typecho\Widget\Helper\Form\Element\Text(
        'autoThemeNightStart', NULL, '18:00', _t('自动切换主题-夜晚开始时间'), _t('格式 HH:MM，默认 18:00；夜晚开始到次日白天开始为深色模式'));
    $form->addInput($autoThemeNightStart);

    $autoThemeDayStart = new \Typecho\Widget\Helper\Form\Element\Text(
        'autoThemeDayStart', NULL, '06:00', _t('自动切换主题-白天开始时间'), _t('格式 HH:MM，默认 06:00；白天开始到夜晚开始为浅色模式'));
    $form->addInput($autoThemeDayStart);

    // 浮动提示显示时长（毫秒）
    $toastDuration = new \Typecho\Widget\Helper\Form\Element\Text(
        'toastDuration', NULL, '3000', _t('浮动提示显示时长'), _t('单位：毫秒，默认 3000ms'));
    $form->addInput($toastDuration);
}

// 获取主题配置项的便捷函数
function foxmoe_opt($key, $default = null) {
    $opt = Helper::options();
    return isset($opt->$key) && $opt->$key !== '' ? $opt->$key : $default;
}

/**
 * 获取文章阅读次数
 */
function get_post_view($archive) {
    $cid = $archive->cid;
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();

    // 首先检查 views 字段是否存在
    try {
        // 尝试查询 views 字段
        $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
        $views = $row ? intval($row['views']) : 0;

        // 如果是文章页面，增加阅读次数
        if ($archive->is('single')) {
            $db->query($db->update('table.contents')->rows(array('views' => $views + 1))->where('cid = ?', $cid));
            $views += 1;
        }

        return $views;
    } catch (Exception $e) {
        // views 字段不存在，使用 meta 表存储浏览量
        try {
            $row = $db->fetchRow($db->select('str_value')->from('table.metas')->where('name = ? AND type = ?', 'views_' . $cid, 'view_count'));
            $views = $row ? intval($row['str_value']) : 0;

            // 如果是文章页面，增加阅读次数
            if ($archive->is('single')) {
                if ($views == 0) {
                    // 插入新记录
                    $db->query($db->insert('table.metas')->rows(array(
                        'name' => 'views_' . $cid,
                        'type' => 'view_count',
                        'description' => 'Post view count',
                        'count' => 0,
                        'order' => 0,
                        'parent' => 0,
                        'str_value' => '1'
                    )));
                    $views = 1;
                } else {
                    // 更新现有记录
                    $views += 1;
                    $db->query($db->update('table.metas')->rows(array('str_value' => $views))->where('name = ? AND type = ?', 'views_' . $cid, 'view_count'));
                }
            }

            return $views;
        } catch (Exception $e2) {
            // 如果都失败了，返回基于文章ID的模拟数值
            $baseViews = ($cid * 17 + 42) % 888 + 10; // 生成一个相对稳定的数值
            return $baseViews;
        }
    }
}

/**
 * 更新并获取文章阅读量
 * @param object $archive 当前文章对象
 */
function get_and_update_post_view($archive) {
    if (!$archive->is('single')) {
        // 如果不是文章页面，直接尝试输出字段，不存在则为0
        echo ($archive->fields->views ? $archive->fields->views : '0');
        return;
    }

    $cid = $archive->cid;
    $db = Typecho_Db::get();
    
    // 检查 'views' 字段是否存在
    $row = $db->fetchRow($db->select('int_value')->from('table.fields')->where('cid = ? AND name = ?', $cid, 'views'));

    // 检查cookie，防止重复计数
    $viewed_cids = isset($_COOKIE['viewed_cids']) ? json_decode($_COOKIE['viewed_cids'], true) : [];
    if (!is_array($viewed_cids)) {
        $viewed_cids = [];
    }

    if (!in_array($cid, $viewed_cids)) {
        if ($row) {
            // 字段存在，更新
            $views = $row['int_value'] + 1;
            $db->query($db->update('table.fields')->rows(['int_value' => $views])->where('cid = ? AND name = ?', $cid, 'views'));
        } else {
            // 字段不存在，插入
            $views = 1;
            $db->query($db->insert('table.fields')->rows([
                'cid' => $cid, 
                'name' => 'views', 
                'type' => 'int', 
                'str_value' => null, 
                'int_value' => 1, 
                'float_value' => 0
            ]));
        }
        // 将当前文章ID存入cookie
        $viewed_cids[] = $cid;
        setcookie('viewed_cids', json_encode($viewed_cids), time() + 3600 * 24, '/'); // cookie有效期24小时
    } else {
        // 已经看过，直接读取
        $views = $row ? $row['int_value'] : 0;
    }
    
    echo $views;
}

/**
 * 主题初始化
 */
function themeInit($archive) {
    // 启用缩略名
    if ($archive->is('single')) {
        $archive->content = preg_replace('/\[more\]/', '<!-- more -->', $archive->content);
    }
}

/**
 * 获取主题版本
 */
function getThemeVersion() {
    return '1.1.0';
}

/**
 * 输出主题自定义字段
 */
function themeFields($layout) {
    $thumbnail = new Typecho_Widget_Helper_Form_Element_Text('thumbnail', NULL, NULL, _t('缩略图'), _t('输入一个图片URL作为缩略图'));
    $layout->addItem($thumbnail);
}

/**
 * 输出缩略图
 */
function showThumbnail($widget) {
    $rand = rand(1, 10);
    $thumbnail = $widget->fields->thumbnail;

    if (empty($thumbnail)) {
        $thumbnail = $widget->widget('Widget_Options')->themeUrl . '/image/wallpaper.jpg';
    }

    return $thumbnail;
}
