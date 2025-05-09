<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php
$fields = isset($post) ? $post->getFieldItems() : $page->getFieldItems();
$defaultFields = isset($post) ? $post->getDefaultFieldItems() : $page->getDefaultFieldItems();
?>
<details id="custom-field"
         class="typecho-post-option" <?php if (!empty($defaultFields) || !empty($fields)): ?>open<?php endif; ?>>
    <summary><?php _e('自定义字段'); ?></summary>
    <ul class="fields mono">
        <?php foreach ($defaultFields as $field): ?>
            <?php [$label, $input] = $field; ?>
            <li class="field">
                <div class="field-name"><?php $label->render(); ?></div>
                <div class="field-value"><?php $input->render(); ?></div>
            </li>
        <?php endforeach; ?>
        <?php foreach ($fields as $field): ?>
            <li class="field">
                <div class="field-name">
                    <label for="fieldname" class="sr-only"><?php _e('字段名称'); ?></label>
                    <input type="text" name="fieldNames[]" value="<?php echo htmlspecialchars($field['name']); ?>"
                           id="fieldname" pattern="^[_a-zA-Z][_a-zA-Z0-9]*$" oninput="this.reportValidity()" class="text-s w-100">
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
                </div>
                <div class="field-value">
                    <label for="fieldvalue" class="sr-only"><?php _e('字段值'); ?></label>
                    <textarea name="fieldValues[]" id="fieldvalue" class="text-s w-100"
                              rows="2"><?php echo htmlspecialchars($field[($field['type'] == 'json' ? 'str' : $field['type']) . '_value']); ?></textarea>
                    <button type="button" class="btn btn-xs"><?php _e('删除'); ?></button>
                </div>
            </li>
        <?php endforeach; ?>
        <?php if (empty($defaultFields) && empty($fields)): ?>
            <li class="field">
                <div class="field-name">
                    <label for="fieldname" class="sr-only"><?php _e('字段名称'); ?></label>
                    <input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" id="fieldname"
                           class="text-s w-100" pattern="^[_a-zA-Z][_a-zA-Z0-9]*$" oninput="this.reportValidity()">
                    <label for="fieldtype" class="sr-only"><?php _e('字段类型'); ?></label>
                    <select name="fieldTypes[]" id="fieldtype">
                        <option value="str"><?php _e('字符'); ?></option>
                        <option value="int"><?php _e('整数'); ?></option>
                        <option value="float"><?php _e('小数'); ?></option>
                        <option value="json"><?php _e('JSON 结构'); ?></option>
                    </select>
                </div>
                <div class="field-value">
                    <label for="fieldvalue" class="sr-only"><?php _e('字段值'); ?></label>
                    <textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" id="fieldvalue"
                              class="text-s w-100" rows="2"></textarea>
                    <button type="button" class="btn btn-xs"><?php _e('删除'); ?></button>
                </div>
            </li>
        <?php endif; ?>
    </ul>
    <div class="add">
        <button type="button" class="btn btn-xs operate-add"><?php _e('+添加字段'); ?></button>
        <div class="description kit-hidden-mb">
            <?php _e('自定义字段可以扩展你的模板功能, 使用方法参见 <a href="https://docs.typecho.org/help/custom-fields">帮助文档</a>'); ?>
        </div>
    </div>
</details>
