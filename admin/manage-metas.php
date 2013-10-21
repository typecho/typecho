<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main manage-metas">
                <div class="col-mb-12">
                    <ul class="typecho-option-tabs clearfix">
                        <li<?php if(!isset($request->type) || 'category' == $request->get('type')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-metas.php'); ?>"><?php _e('分类'); ?></a></li>
                        <li<?php if('tag' == $request->get('type')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-metas.php?type=tag'); ?>"><?php _e('标签'); ?></a></li>
                    </ul>
                </div>
                
                <div class="col-mb-12 col-tb-8">
                    <?php if(!isset($request->type) || 'category' == $request->get('type')): ?>
                    <?php Typecho_Widget::widget('Widget_Metas_Category_List')->to($categories); ?>
                    
                    <form method="post" name="manage_categories" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <input type="checkbox" class="typecho-table-select-all" />
                            <div class="btn-group btn-drop">
                                <button class="dropdown-toggle btn-s" type="button" href="">选中项 <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('此分类下的所有内容将被删除, 你确认要删除这些分类吗?'); ?>" href="<?php $options->index('/action/metas-category-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                                    <li><a lang="<?php _e('刷新分类可能需要等待较长时间, 你确认要刷新这些分类吗?'); ?>" href="<?php $options->index('/action/metas-category-edit?do=refresh'); ?>"><?php _e('刷新'); ?></a></li>
                                    <li class="multiline">
                                        <button type="button" class="merge btn-s" rel="<?php $options->index('/action/metas-category-edit?do=merge'); ?>"><?php _e('合并到'); ?></button>
                                        <select name="merge">
                                            <?php $categories->parse('<option value="{mid}">{name}</option>'); ?>
                                        </select>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="20"/>
                                <col width="35%"/>
                                <col width="30%"/>
                                <col width=""/>
                                <col width="15%"/>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th> </th>
                                    <th><?php _e('名称'); ?></th>
                                    <th><?php _e('缩略名'); ?></th>
                                    <th> </th>
                                    <th><?php _e('文章数'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($categories->have()): ?>
                                <?php while ($categories->next()): ?>
                                <tr id="mid-<?php $categories->theId(); ?>">
                                    <td><input type="checkbox" value="<?php $categories->mid(); ?>" name="mid[]"/></td>
                                    <td><a href="<?php echo $request->makeUriByRequest('mid=' . $categories->mid); ?>"><?php $categories->name(); ?></a>
                                    <a href="<?php $categories->permalink(); ?>"><i class="i-exlink" title="<?php _e('浏览 %s', $categories->name); ?>"></i></a>
                                    </td>
                                    <td><?php $categories->slug(); ?></td>
                                    <td>
                                    <?php if ($options->defaultCategory == $categories->mid): ?>
                                    <span class="balloon right"><?php _e('默认'); ?></span>
                                    <?php else: ?>
                                    <a class="hidden-by-mouse" href="<?php $options->index('/action/metas-category-edit?do=default&mid=' . $categories->mid); ?>" title="<?php _e('设为默认'); ?>"><?php _e('默认'); ?></a>
                                    <?php endif; ?>
                                    </td>
                                    <td><a class="balloon-button left size-<?php echo Typecho_Common::splitByCount($categories->count, 1, 10, 20, 50, 100); ?>" href="<?php $options->adminUrl('manage-posts.php?category=' . $categories->mid); ?>"><?php $categories->count(); ?></a></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5"><h6 class="typecho-list-table-title"><?php _e('没有任何分类'); ?></h6></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </form>
                    <?php else: ?>
                    <?php Typecho_Widget::widget('Widget_Metas_Tag_Cloud', 'sort=mid&desc=0')->to($tags); ?>
                    <form method="post" name="manage_tags" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                        <input type="checkbox" class="typecho-table-select-all" />
                        <div class="btn-group btn-drop">
                        <button class="dropdown-toggle btn-s" type="button" href="">选中项 <i class="i-caret-down"></i></button>
                        <ul class="dropdown-menu">
                            <li><a lang="<?php _e('你确认要删除这些标签吗?'); ?>" href="<?php $options->index('/action/metas-tag-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            <li><a lang="<?php _e('刷新标签可能需要等待较长时间, 你确认要刷新这些标签吗?'); ?>" href="<?php $options->index('/action/metas-tag-edit?do=refresh'); ?>"><?php _e('刷新'); ?></a></li>
                            <li class="multiline">
                                <button type="button" class="merge" rel="<?php $options->index('/action/metas-tag-edit?do=merge'); ?>"><?php _e('合并到'); ?></button>
                                <input type="text" name="merge" />
                            </li>
                        </ul>
                        </div>
                        </div>
                    </div>
                    
                    <ul class="typecho-list-notable tag-list clearfix">
                        <?php if($tags->have()): ?>
                        <?php while ($tags->next()): ?>
                        <li class="size-<?php $tags->split(5, 10, 20, 30); ?>" id="<?php $tags->theId(); ?>">
                        <input type="checkbox" value="<?php $tags->mid(); ?>" name="mid[]"/>
                        <span rel="<?php echo $request->makeUriByRequest('mid=' . $tags->mid); ?>"><?php $tags->name(); ?></span>
                        <a class="tag-edit-link" href="<?php echo $request->makeUriByRequest('mid=' . $tags->mid); ?>"><i class="i-edit"></i></a>
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
                <div class="col-mb-12 col-tb-4 typecho-mini-panel typecho-radius-topleft typecho-radius-topright typecho-radius-bottomleft typecho-radius-bottomright">
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
    $(document).ready(function () {
        var table = $('.typecho-list-table').tableDnD({
            onDrop : function () {
                var ids = [];

                $('input[type=checkbox]', table).each(function () {
                    ids.push($(this).val());
                });

                $.post('<?php $options->index('/action/metas-category-edit?do=sort'); ?>', 
                    $.param({mid : ids}));

                $('tr', table).each(function (i) {
                    if (i % 2) {
                        $(this).addClass('even');
                    } else {
                        $(this).removeClass('even');
                    }
                });
            }
        });

        if (table.length > 0) {
            table.tableSelectable({
                checkEl     :   'input[type=checkbox]',
                rowEl       :   'tr',
                selectAllEl :   '.typecho-table-select-all',
                actionEl    :   '.dropdown-menu a'
            });
        } else {
            $('.typecho-list-notable').tableSelectable({
                checkEl     :   'input[type=checkbox]',
                rowEl       :   'li',
                selectAllEl :   '.typecho-table-select-all',
                actionEl    :   '.dropdown-menu a'
            });

            // $('.typecho-table-select-all').click(function () {
            //     var selection = $('.tag-selection');

            //     if (0 == selection.length) {
            //         selection = $('<div class="tag-selection clearfix" />').prependTo('.typecho-mini-panel');
            //     }

            //     selection.html('');

            //     if ($(this).prop('checked')) {
            //         $('.typecho-list-notable li').each(function () {
            //             var span = $('span', this),
            //                 a = $('<a class="button" href="' + span.attr('rel') + '">' + span.text() + '</a>');
                        
            //             this.aHref = a;
            //             selection.append(a);
            //         });
            //     }
            // });
        }

        $('.btn-drop').dropdownMenu({
            btnEl       :   '.dropdown-toggle',
            menuEl      :   '.dropdown-menu'
        });

        $('.dropdown-menu button.merge').click(function () {
            var btn = $(this);
            btn.parents('form').attr('action', btn.attr('rel')).submit();
        });

        // $('.typecho-list-notable li').click(function () {
        //     var selection = $('.tag-selection'), span = $('span', this),
        //         a = $('<a class="button" href="' + span.attr('rel') + '">' + span.text() + '</a>'),
        //         li = $(this);

        //     if (0 == selection.length) {
        //         selection = $('<div class="tag-selection clearfix" />').prependTo('.typecho-mini-panel');
        //     }

        //     if (li.hasClass('checked')) {
        //         this.aHref = a;
        //         a.appendTo(selection);
        //     } else {
        //         this.aHref.remove();
        //     }
        // });

        <?php if (isset($request->mid)): ?>
        $('.typecho-mini-panel').effect('highlight', '#AACB36');
        <?php endif; ?>
    });
})();
</script>
<?php include 'footer.php'; ?>
