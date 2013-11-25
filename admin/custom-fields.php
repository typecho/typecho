<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$fields = isset($post) ? $post->getFieldItems() : $page->getFieldItems();
$defaultFields = isset($post) ? $post->getDefaultFieldItems() : $post->getDefaultFieldItems();
?>
                    <section id="custom-field" class="typecho-post-option fold">
                        <label id="custom-field-expand" class="typecho-label"><?php _e('自定义字段'); ?> <i class="i-caret-down"></i></label>
                        <table class="typecho-list-table mono">
                            <colgroup>
                                <col width="25%"/>
                                <col width="10%"/>
                                <col width="55%"/>
                                <col width="10%"/>
                            </colgroup>
                            <?php foreach ($defaultFields as $field): ?>
                            <?php list ($label, $input) = $field; ?>
                            <tr>
                                <td><?php $label->render(); ?></td>
                                <td colspan="3"><?php $input->render(); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><input type="text" name="fieldNames[]" value="<?php echo htmlspecialchars($field['name']); ?>" class="text-s w-100"></td>
                                <td>
                                    <select name="fieldTypes[]" id="">
                                        <option value="str"<?php if ('str' == $field['type']): ?> selected<?php endif; ?>><?php _e('字符'); ?></option>
                                        <option value="int"<?php if ('int' == $field['type']): ?> selected<?php endif; ?>><?php _e('整数'); ?></option>
                                        <option value="float"<?php if ('float' == $field['type']): ?> selected<?php endif; ?>><?php _e('小数'); ?></option>
                                    </select>
                                </td>
                                <td><textarea name="fieldValues[]" class="text-s w-100" rows="2"><?php echo htmlspecialchars($field['value']); ?></textarea></td>
                                <td>
                                    <button type="button" class="btn-xs"><?php _e('删除'); ?></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($defaultFields) && empty($fields)): ?>
                            <tr>
                                <td>
                                    <label for="title" class="visuallyhidden"><?php _e('字段名称'); ?></label>
                                    <input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100">
                                </td>
                                <td>
                                    <label for="title" class="visuallyhidden"><?php _e('字段类型'); ?></label>
                                    <select name="fieldTypes[]" id="">
                                        <option value="str"><?php _e('字符'); ?></option>
                                        <option value="int"><?php _e('整数'); ?></option>
                                        <option value="float"><?php _e('小数'); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <label for="title" class="visuallyhidden"><?php _e('字段值'); ?></label>
                                    <textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea>
                                </td>
                                <td>
                                    <button type="button" class="btn-xs"><?php _e('删除'); ?></button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                        <div class="description clearfix">
                            <button type="button" class="btn-xs operate-add"><?php _e('+添加字段'); ?></button>
                            <?php _e('自定义字段可以扩展你的模板功能, 使用方法参见 <a href="">帮助文档</a>'); ?>
                        </div>
                    </section>
