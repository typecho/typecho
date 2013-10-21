<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs clearfix">
                    <li class="current"><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('可以使用的外观'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('theme-editor.php'); ?>"><?php _e('编辑当前外观'); ?></a></li>
                    <?php if (Widget_Themes_Config::isExists()): ?>
                    <li><a href="<?php $options->adminUrl('options-theme.php'); ?>"><?php _e('设置外观'); ?></a></li>
                    <?php endif; ?>
                </ul>
                
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table typecho-theme-list">
                        <colgroup>
                            <col width="35%" />
                            <col />
                        </colgroup>
                        
                        <thead>
                            <th>截图</th>
                            <th>详情</th>
                        </thead>

                        <tbody>
                            <?php Typecho_Widget::widget('Widget_Themes_List')->to($themes); ?>
                            <?php while($themes->next()): ?>
                            <tr id="theme-<?php $themes->name(); ?>" class="<?php if($themes->activated): ?>current<?php endif; ?>">
                                <td valign="top"><img src="<?php $themes->screen(); ?>" alt="<?php $themes->name(); ?>" /></td>
                                <td valign="top">
                                    <h3><?php '' != $themes->title ? $themes->title() : $themes->name(); ?></h3>
                                    <cite>
                                        <?php if($themes->author): ?><?php _e('作者'); ?>: <?php if($themes->homepage): ?><a href="<?php $themes->homepage() ?>"><?php endif; ?><?php $themes->author(); ?><?php if($themes->homepage): ?></a><?php endif; ?> &nbsp;&nbsp;<?php endif; ?>
                                        <?php if($themes->version): ?><?php _e('版本'); ?>: <?php $themes->version() ?><?php endif; ?>
                                    </cite>
                                    <p><?php echo nl2br($themes->description); ?></p>
                                    <?php if($options->theme != $themes->name): ?>
                                        <p>
                                            <a class="edit" href="<?php $options->adminUrl('theme-editor.php?theme=' . $themes->name); ?>"><?php _e('编辑'); ?></a> &nbsp;
                                            <a class="activate" href="<?php $options->index('/action/themes-edit?change=' . $themes->name); ?>"><?php _e('启用'); ?></a>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
    (function () {
        window.addEvent('domready', function() {
            $(document).getElements('table.typecho-list-table tr td').each(function (item, index) {
                var _a = item.getElement('a.activate'),
                _e = item.getElement('a.edit');
                
                if (_a && _e) {
                    item.addEvents({
                    
                        'mouseover': function () {
                            this.addClass('hover');
                            
                            if (0 == index % 2) {
                                _a.setStyles({
                                
                                    'right': _a.getParent('td').getNext('td').getSize().x + 1,
                                    
                                    'top': _a.getParent('td').getPosition(_a.getParent('.col-12')).y
                                
                                });
                                
                                _a.addClass('typecho-radius-bottomleft');
                                
                                _e.setStyles({
                                
                                    'right': _e.getParent('td').getNext('td').getSize().x + 1,
                                    
                                    'top': _e.getParent('td').getPosition(_e.getParent('.col-12')).y + _e.getParent('td').getSize().y - _e.getSize().y - 1
                                
                                });
                                
                                _e.addClass('typecho-radius-topleft');
                            } else {
                                _a.setStyles({
                                
                                    'left': _a.getParent('td').getPosition(_a.getParent('.col-12')).x,
                                    
                                    'top': _a.getParent('td').getPosition(_a.getParent('.col-12')).y
                                
                                });
                                
                                _a.addClass('typecho-radius-bottomright');
                                
                                _e.setStyles({
                                
                                    'left': _e.getParent('td').getPosition(_e.getParent('.col-12')).x,
                                    
                                    'top': _e.getParent('td').getPosition(_e.getParent('.col-12')).y + _e.getParent('td').getSize().y - _e.getSize().y - 1
                                
                                });
                                
                                _e.addClass('typecho-radius-topright');
                            }
                        },
                        
                        'mouseleave': function () {
                            this.removeClass('hover');
                        }
                    
                    });
                }
            });
        });
    })();
</script>

<?php include 'footer.php'; ?>
