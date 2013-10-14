<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php
if (isset($post) || isset($page)) {
    $cid = isset($post) ? $post->cid : $page->cid;
    
    if ($cid) {
        Typecho_Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $cid)->to($attachment);
    } else {
        Typecho_Widget::widget('Widget_Contents_Attachment_Unattached')->to($attachment);
    }
}
?>

<!--<style>
.upload-progress {
    font-size: 12px;
}

#upload-panel ul li.upload-progress-item {
	background-image: url(<?php $options->adminUrl('images/progress.gif'); ?>);
	background-repeat: repeat-y;
	background-position: -1000px 0;
    background-color: #fff;
    padding: 5px;
    margin-bottom: 5px;
    border: 1px solid #C1CD94;
    
	-moz-border-radius-topleft: 2px;
	-moz-border-radius-topright: 2px;
	-moz-border-radius-bottomleft: 2px;
	-moz-border-radius-bottomright: 2px;
	-webkit-border-top-left-radius: 2px;
	-webkit-border-top-right-radius: 2px;
	-webkit-border-bottom-left-radius: 2px;
	-webkit-border-bottom-right-radius: 2px;
	
	/* hope IE support border radius, God save me! */
	border-top-left-radius: 2px;
	border-top-right-radius: 2px;
	border-bottom-left-radius: 2px;
	border-bottom-right-radius: 2px;
}

.upload-progress-item strong {
    float: left;
}

.upload-progress-item strong.delete {
    text-decoration: line-through;
}

.upload-progress-item small {
    float: right;
    font-size: 8pt;
}

.upload-progress-item small .insert, .upload-progress-item small .delete {
    cursor: pointer;
    text-decoration: underline;
}

.upload-progress-item small .insert {
    color: #00AA00;
}

.upload-progress-item small .delete {
    color: #CC0000;
}
</style>

<div class="typecho-list-operate">
<p class="operate">
    <a class="button"><?php _e('正在加载上传组件'); ?></a>
    <span id="swfu"><span id="swfu-placeholder"></span></span>
</p>
</div>

<ul class="upload-progress">
<?php while ($attachment->next()): ?>
    <li class="upload-progress-item clearfix">
        <strong>
            <?php $attachment->title(); ?>
            <input type="hidden" name="attachment[]" value="<?php $attachment->cid(); ?>" />
        </strong>
        <small>
            <span class="insert" onclick="<?php if ($attachment->attachment->isImage){
                        echo "insertImageToEditor('{$attachment->title}', '{$attachment->attachment->url}', '{$attachment->permalink}', {$attachment->cid});";
                    } else {
                        echo "insertLinkToEditor('{$attachment->title}', '{$attachment->attachment->url}', '{$attachment->permalink}', {$attachment->cid});";
                    } ?>"><?php _e('插入'); ?></span>
            ,
            <span class="delete" onclick="deleteAttachment(<?php $attachment->cid(); ?>, this);"><?php _e('删除'); ?></span>
        </small>
    </li>
<?php endwhile; ?>
</ul>-->

<div id="upload-panel" class="p">
    将要上传的文件拖放到这里 或者 <a href="" class="upload-file">选择文件上传</a><input type="file" class="visuallyhidden">
    <ul id="file-list">
        <li class="loading">widescreen.jpg</li>
        <li><a href="###">Sample.zip</a> 100KB <a href="###" class="delete">&times;</a></li>
        <li><a href="###">segmentfault.profile.png</a> 60KB <a href="###" class="delete">&times;</a></li>
    </ul>
</div>

