<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php
$fields = isset($post) ? $post->getFieldItems() : $page->getFieldItems();
$defaultFields = isset($post) ? $post->getDefaultFieldItems() : $page->getDefaultFieldItems();
?>
<section id="custom-field"
         class="typecho-post-option<?php if (empty($defaultFields) && empty($fields)): ?> fold<?php endif; ?>">
    <label id="custom-field-expand" class="typecho-label"><a href="##"><i
                class="i-caret-right"></i> <?php _e('自定义字段'); ?></a></label>
    <table class="typecho-list-table mono">
        <colgroup>
            <col width="20%"/>
            <col width="15%"/>
            <col width="55%"/>
            <col width="10%"/>
        </colgroup>
        <?php foreach ($defaultFields as $field): ?>
            <?php [$label, $input] = $field; ?>
            <tr>
                <td><?php $label->render(); ?></td>
                <td colspan="3"><?php $input->render(); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php foreach ($fields as $field): ?>
            <tr>
                <td>
                    <label for="fieldname" class="sr-only"><?php _e('字段名称'); ?></label>
                    <input type="text" name="fieldNames[]" value="<?php echo htmlspecialchars($field['name']); ?>"
                           id="fieldname" class="text-s w-100">
                </td>
                <td>
                    <label for="fieldtype" class="sr-only"><?php _e('字段类型'); ?></label>
                    <select name="fieldTypes[]" id="fieldtype">
                        <option
                            value="str"<?php if ('str' == $field['type']): ?> selected<?php endif; ?>><?php _e('字符'); ?></option>
                        <option
                            value="int"<?php if ('int' == $field['type']): ?> selected<?php endif; ?>><?php _e('整数'); ?></option>
                        <option
                            value="float"<?php if ('float' == $field['type']): ?> selected<?php endif; ?>><?php _e('小数'); ?></option>
                        <option
                            value="json"<?php if ('json' == $field['type']): ?> selected<?php endif; ?>><?php _e('JSON 结构'); ?></option>
                    </select>
                </td>
                <td>
                    <label for="fieldvalue" class="sr-only"><?php _e('字段值'); ?></label>
                    <textarea name="fieldValues[]" id="fieldvalue" class="text-s w-100"
                              rows="2"><?php echo htmlspecialchars($field[($field['type'] == 'json' ? 'str' : $field['type']) . '_value']); ?></textarea>
                </td>
                <td>
                    <button type="button" class="btn btn-xs"><?php _e('删除'); ?></button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($defaultFields) && empty($fields)): ?>
            <tr>
                <td>
                    <label for="fieldname" class="sr-only"><?php _e('字段名称'); ?></label>
                    <input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" id="fieldname"
                           class="text-s w-100">
                </td>
                <td>
                    <label for="fieldtype" class="sr-only"><?php _e('字段类型'); ?></label>
                    <select name="fieldTypes[]" id="fieldtype">
                        <option value="str"><?php _e('字符'); ?></option>
                        <option value="int"><?php _e('整数'); ?></option>
                        <option value="float"><?php _e('小数'); ?></option>
                    </select>
                </td>
                <td>
                    <label for="fieldvalue" class="sr-only"><?php _e('字段值'); ?></label>
                    <textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" id="fieldvalue"
                              class="text-s w-100" rows="2"></textarea>
                </td>
                <td>
                    <button type="button" class="btn btn-xs"><?php _e('删除'); ?></button>
                </td>
            </tr>
        <?php endif; ?>
    </table>
    <div class="description clearfix">
        <button type="button" class="btn btn-xs operate-add"><?php _e('+添加字段'); ?></button>
        <?php _e('自定义字段可以扩展你的模板功能, 使用方法参见 <a href="https://docs.typecho.org/help/custom-fields">帮助文档</a>'); ?>
    </div>
</section>
