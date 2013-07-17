<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body body-950">
        <?php include 'page-title.php'; ?>
        <div class="container typecho-page-main">
            <div class="column-24">
                <ul class="typecho-option-tabs">
                    <li class="current"><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('可以使用的外观'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('theme-editor.php'); ?>"><?php _e('编辑当前外观'); ?></a></li>
                    <?php if (Widget_Themes_Config::isExists()): ?>
                    <li><a href="<?php $options->adminUrl('options-theme.php'); ?>"><?php _e('设置外观'); ?></a></li>
                    <?php endif; ?>
                </ul>
                
                <table class="typecho-list-table typecho-theme-list" cellspacing="0" cellpadding="0">
                    <colgroup>
                        <col width="450"/>
                        <col width="450"/>
                    </colgroup>
                    <?php Typecho_Widget::widget('Widget_Themes_List')->to($themes); ?>
                    <?php while($themes->next()): ?>
                    <?php $themes->alt('<tr>', ''); ?>
                    <?php
                    $borderBottom = ($themes->length - $themes->sequence >= ($themes->length % 2 ? 1 : 2));
                    ?>
                    <td id="theme-<?php $themes->name(); ?>" class="<?php if($themes->activated): ?>current <?php endif; $themes->alt('border-right', ''); if ($borderBottom): echo ' border-bottom'; endif; ?>">
                        <div class="column-04">
                            <img src="<?php $themes->screen(); ?>" width="120" height="90" align="left" />
                        </div>
                        <div class="column-08">
                        <h4><?php '' != $themes->title ? $themes->title() : $themes->name(); ?></h4>
                        <cite><?php if($themes->author): ?><?php _e('作者'); ?>: <?php if($themes->homepage): ?><a href="<?php $themes->homepage() ?>"><?php endif; ?><?php $themes->author(); ?><?php if($themes->homepage): ?></a><?php endif; ?>&nbsp;&nbsp;&nbsp;<?php endif; ?>
                        <?php if($themes->version): ?><?php _e('版本'); ?>: <?php $themes->version() ?><?php endif; ?>
                        </cite>
                        <p><?php echo nl2br($themes->description); ?></p>
                        </div>
                        <?php if($options->theme != $themes->name): ?>
                            <a class="edit" href="<?php $options->adminUrl('theme-editor.php?theme=' . $themes->name); ?>"><?php _e('编辑'); ?></a>
                            <a class="activate" href="<?php $options->index('/action/themes-edit?change=' . $themes->name); ?>"><?php _e('激活'); ?></a>
                        <?php endif; ?>
                    </td>
                    <?php $last = $themes->sequence; ?>
                    <?php $themes->alt('', '</tr>'); ?>
                    <?php endwhile; ?>
                    <?php if($last % 2): ?>
                    <td>&nbsp;</td></tr>
                    <?php endif; ?>
                </table>
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
                                    
                                    'top': _a.getParent('td').getPosition(_a.getParent('.column-24')).y
                                
                                });
                                
                                _a.addClass('typecho-radius-bottomleft');
                                
                                _e.setStyles({
                                
                                    'right': _e.getParent('td').getNext('td').getSize().x + 1,
                                    
                                    'top': _e.getParent('td').getPosition(_e.getParent('.column-24')).y + _e.getParent('td').getSize().y - _e.getSize().y - 1
                                
                                });
                                
                                _e.addClass('typecho-radius-topleft');
                            } else {
                                _a.setStyles({
                                
                                    'left': _a.getParent('td').getPosition(_a.getParent('.column-24')).x,
                                    
                                    'top': _a.getParent('td').getPosition(_a.getParent('.column-24')).y
                                
                                });
                                
                                _a.addClass('typecho-radius-bottomright');
                                
                                _e.setStyles({
                                
                                    'left': _e.getParent('td').getPosition(_e.getParent('.column-24')).x,
                                    
                                    'top': _e.getParent('td').getPosition(_e.getParent('.column-24')).y + _e.getParent('td').getSize().y - _e.getSize().y - 1
                                
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
