<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body body-950">
        <?php include 'page-title.php'; ?>
        <div class="container typecho-page-main manage-metas">
                <div class="column-16 suffix">
                    <ul class="typecho-option-tabs">
                        <li<?php if(!isset($request->type) || 'category' == $request->get('type')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-metas.php'); ?>"><?php _e('分类'); ?></a></li>
                        <li<?php if('tag' == $request->get('type')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-metas.php?type=tag'); ?>"><?php _e('标签'); ?></a></li>
                    </ul>
                    
                    <?php if(!isset($request->type) || 'category' == $request->get('type')): ?>
                    <?php Typecho_Widget::widget('Widget_Metas_Category_List')->to($categories); ?>
                    <form method="post" name="manage_categories" class="operate-form" action="<?php $options->index('/action/metas-category-edit'); ?>">
                    <div class="typecho-list-operate">
                        <p class="operate"><?php _e('操作'); ?>: 
                            <span class="operate-button typecho-table-select-all"><?php _e('全选'); ?></span>, 
                            <span class="operate-button typecho-table-select-none"><?php _e('不选'); ?></span>&nbsp;&nbsp;&nbsp;
                            <?php _e('选中项'); ?>: 
                            <span rel="delete" lang="<?php _e('此分类下的所有内容将被删除, 你确认要删除这些分类吗?'); ?>" class="operate-button operate-delete typecho-table-select-submit"><?php _e('删除'); ?></span>, 
                            <span rel="refresh" lang="<?php _e('刷新分类可能需要等待较长时间, 你确认要刷新这些分类吗?'); ?>" class="operate-button typecho-table-select-submit"><?php _e('刷新'); ?></span>, 
                            <span rel="merge" class="operate-button typecho-table-select-submit"><?php _e('合并到'); ?></span>
                            <select name="merge">
                                <?php $categories->parse('<option value="{mid}">{name}</option>'); ?>
                            </select>
                        </p>
                    </div>
                    
                    <table class="typecho-list-table draggable">
                        <colgroup>
                            <col width="25"/>
                            <col width="230"/>
                            <col width="30"/>
                            <col width="170"/>
                            <col width="50"/>
                            <col width="65"/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th> </th>
                                <th><?php _e('名称'); ?></th>
                                <th> </th>
                                <th><?php _e('缩略名'); ?></th>
                                <th> </th>
                                <th><?php _e('文章数'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($categories->have()): ?>
                            <?php while ($categories->next()): ?>
                            <tr<?php $categories->alt(' class="even"', ''); ?> id="<?php $categories->theId(); ?>">
                                <td><input type="checkbox" value="<?php $categories->mid(); ?>" name="mid[]"/></td>
                                <td><a href="<?php echo $request->makeUriByRequest('mid=' . $categories->mid); ?>"><?php $categories->name(); ?></a></td>
                                <td>
                                <a class="right hidden-by-mouse" href="<?php $categories->permalink(); ?>"><img src="<?php $options->adminUrl('images/link.png'); ?>" title="<?php _e('浏览 %s', $categories->name); ?>" width="16" height="16" alt="view" /></a>
                                </td>
                                <td><?php $categories->slug(); ?></td>
                                <td>
                                <?php if ($options->defaultCategory == $categories->mid): ?>
                                <span class="balloon right"><?php _e('默认'); ?></span>
                                <?php else: ?>
                                <a class="balloon-button right hidden-by-mouse" href="<?php $options->index('/action/metas-category-edit?do=default&mid=' . $categories->mid); ?>"><?php _e('默认'); ?></a>
                                <?php endif; ?>
                                </td>
                                <td><a class="balloon-button left size-<?php echo Typecho_Common::splitByCount($categories->count, 1, 10, 20, 50, 100); ?>" href="<?php $options->adminUrl('manage-posts.php?category=' . $categories->mid); ?>"><?php $categories->count(); ?></a></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr class="even">
                                <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何分类'); ?></h6></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <input type="hidden" name="do" value="delete" />
                    </form>
                    <?php else: ?>
                    <?php Typecho_Widget::widget('Widget_Metas_Tag_Cloud', 'sort=mid&desc=0')->to($tags); ?>
                    <form method="post" name="manage_tags" class="operate-form" action="<?php $options->index('/action/metas-tag-edit'); ?>">
                    <div class="typecho-list-operate">
                        <p class="operate"><?php _e('操作'); ?>: 
                            <span class="operate-button typecho-table-select-all"><?php _e('全选'); ?></span>, 
                            <span class="operate-button typecho-table-select-none"><?php _e('不选'); ?></span>&nbsp;&nbsp;&nbsp;
                            <?php _e('选中项'); ?>: 
                            <span rel="delete" lang="<?php _e('此标签下的所有内容将被删除, 你确认要删除这些标签吗?'); ?>" class="operate-button operate-delete typecho-table-select-submit"><?php _e('删除'); ?></span>, 
                            <span rel="refresh" lang="<?php _e('刷新标签可能需要等待较长时间, 你确认要刷新这些分类吗?'); ?>" class="operate-button typecho-table-select-submit"><?php _e('刷新'); ?></span>, 
                            <span rel="merge" class="operate-button typecho-table-select-submit"><?php _e('合并到'); ?></span> 
                            <input type="text" name="merge" />
                        </p>
                    </div>
                    
                    <ul class="typecho-list-notable tag-list clearfix typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                        <?php if($tags->have()): ?>
                        <?php while ($tags->next()): ?>
                        <li class="size-<?php $tags->split(5, 10, 20, 30); ?>" id="<?php $tags->theId(); ?>">
                        <input type="checkbox" value="<?php $tags->mid(); ?>" name="mid[]"/>
                        <span rel="<?php echo $request->makeUriByRequest('mid=' . $tags->mid); ?>"><?php $tags->name(); ?></span>
                        </li>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <h6 class="typecho-list-table-title"><?php _e('没有任何标签'); ?></h6>
                        <?php endif; ?>
                    </ul>
                    <input type="hidden" name="do" value="delete" />
                    </form>
                    <?php endif; ?>
                    
                </div>
                <div class="column-08 typecho-mini-panel typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
                    <?php if(!isset($request->type) || 'category' == $request->get('type')): ?>
                        <?php Typecho_Widget::widget('Widget_Metas_Category_Edit')->form()->render(); ?>
                    <?php else: ?>
                        <?php Typecho_Widget::widget('Widget_Metas_Tag_Edit')->form()->render(); ?>
                    <?php endif; ?>
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
            var _selection;
            
            <?php if (isset($request->mid)): ?>
            var _hl = $(document).getElement('.typecho-mini-panel');
            if (_hl) {
                _hl.set('tween', {duration: 1500});
    
                var _bg = _hl.getStyle('background-color');
                if (!_bg || 'transparent' == _bg) {
                    _bg = '#F7FBE9';
                }

                _hl.tween('background-color', '#AACB36', _bg);
            }
            <?php endif; ?>
            
            if ('tr' == Typecho.Table.table._childTag) {
                Typecho.Table.dragStop = function (obj, result) {
                    var _r = new Request.JSON({
                        url: '<?php $options->index('/action/metas-category-edit'); ?>'
                    }).send(result + '&do=sort');
                };
            } else {
                Typecho.Table.checked = function (input, item) {
                    if (!_selection) {
                        _selection = document.createElement('div');
                        $(_selection).addClass('tag-selection');
                        $(_selection).addClass('clearfix');
                        $(document).getElement('.typecho-mini-panel form')
                        .insertBefore(_selection, $(document).getElement('.typecho-mini-panel form #typecho-option-item-name-0'));
                    }
                    
                    var _href = item.getElement('span').getProperty('rel');
                    var _text = item.getElement('span').get('text');
                    var _a = document.createElement('a');
                    $(_a).addClass('button');
                    $(_a).setProperty('href', _href);
                    $(_a).set('text', _text);
                    _selection.appendChild(_a);
                    item.checkedElement = _a;
                };
                
                Typecho.Table.unchecked = function (input, item) {
                    if (item.checkedElement) {
                        $(item.checkedElement).destroy();
                    }
                    
                    if (!$(_selection).getElement('a')) {
                        _selection.destroy();
                        _selection = null;
                    }
                };
            }
        });
    })();
</script>
<?php include 'footer.php'; ?>
