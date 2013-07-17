<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$response->setContentType('text/javascript');
?>

tinyMCE.addI18n({typecho:{
common:{
edit_confirm:"<?php _e('在这个文本域启用所见即所得模式？'); ?>",
apply:"<?php _e('应用'); ?>",
insert:"<?php _e('插入'); ?>",
update:"<?php _e('更新'); ?>",
cancel:"<?php _e('取消'); ?>",
close:"<?php _e('关闭'); ?>",
browse:"<?php _e('浏览'); ?>",
class_name:"<?php _e('类别'); ?>",
not_set:"<?php _e('-- 未设定 --'); ?>",
clipboard_msg:"<?php _e('贴上复制、剪下和贴上功能在 Mozilla 和 Firefox 中无法使用。\n你需要了解更多相关信息吗？'); ?>",
clipboard_no_support:"<?php _e('目前你的浏览器无法支持，请用键盘快捷键。'); ?>",
popup_blocked:"<?php _e('抱歉，快捷功能在你的系统上被封锁，使程序无法正常使用，你需要暂时解除快捷封锁，使工具能正常使用。'); ?>",
invalid_data:"<?php _e('错误：输入无效的值，以红色字表示。'); ?>",
more_colors:"<?php _e('其它更多颜色'); ?>"
},
contextmenu:{
align:"<?php _e('对齐方式'); ?>",
left:"<?php _e('居左对齐'); ?>",
center:"<?php _e('居中对齐'); ?>",
right:"<?php _e('居右对齐'); ?>",
full:"<?php _e('左右对齐'); ?>"
},
insertdatetime:{
date_fmt:"<?php _e('%Y-%m-%d'); ?>",
time_fmt:"<?php _e('%H:%M:%S'); ?>",
insertdate_desc:"<?php _e('插入今天日期'); ?>",
inserttime_desc:"<?php _e('插入现在时间'); ?>",
months_long:"<?php _e('一月,二月,三月,四月,五月,六月,七月,八月,九月,十月,十一月,十二月'); ?>",
months_short:"<?php _e('1月,2月,3月,4月,5月,6月,7月,8月,9月,10月,11月,12月'); ?>",
day_long:"<?php _e('星期日,星期一,星期二,星期三,星期四,星期五,星期六,星期日'); ?>",
day_short:"<?php _e('周日,周一,周二,周三,周四,周五,周六,周日'); ?>"
},
print:{
print_desc:"<?php _e('打印'); ?>"
},
preview:{
preview_desc:"<?php _e('预览'); ?>"
},
directionality:{
ltr_desc:"<?php _e('文字从左到右'); ?>",
rtl_desc:"<?php _e('文字从右到左'); ?>"
},
layer:{
insertlayer_desc:"<?php _e('插入层'); ?>",
forward_desc:"<?php _e('前移'); ?>",
backward_desc:"<?php _e('后移'); ?>",
absolute_desc:"<?php _e('切换绝对寻址'); ?>",
content:"<?php _e('新增层..'); ?>"
},
save:{
save_desc:"<?php _e('保存'); ?>",
cancel_desc:"<?php _e('取消所有变更'); ?>"
},
nonbreaking:{
nonbreaking_desc:"<?php _e('插入非截断的空格符'); ?>"
},
iespell:{
iespell_desc:"<?php _e('执行拼写检查'); ?>",
download:"<?php _e('侦测不到ieSpell套件，是否立即安装？'); ?>"
},
advhr:{
advhr_desc:"<?php _e('水平分隔线'); ?>"
},
emotions:{
emotions_desc:"<?php _e('表情'); ?>"
},
searchreplace:{
search_desc:"<?php _e('查找'); ?>",
replace_desc:"<?php _e('查找/替换'); ?>"
},
advimage:{
image_desc:"<?php _e('插入/编辑 图片'); ?>"
},
advlink:{
link_desc:"<?php _e('插入/编辑 链接'); ?>"
},
xhtmlxtras:{
cite_desc:"<?php _e('引用'); ?>",
abbr_desc:"<?php _e('缩写'); ?>",
acronym_desc:"<?php _e('首字母缩写'); ?>",
del_desc:"<?php _e('删除'); ?>",
ins_desc:"<?php _e('插入'); ?>",
attribs_desc:"<?php _e('插入/编辑 属性'); ?>"
},
style:{
desc:"<?php _e('编辑 CSS 样式'); ?>"
},
paste:{
paste_text_desc:"<?php _e('以纯文字格式粘贴'); ?>",
paste_word_desc:"<?php _e('从Word粘贴'); ?>",
selectall_desc:"<?php _e('全选'); ?>"
},
paste_dlg:{
text_title:"<?php _e('用 Ctrl+V 组合键将文字贴入窗口中。'); ?>",
text_linebreaks:"<?php _e('保留换行符号'); ?>",
word_title:"<?php _e('用 Ctrl+V 组合键将文字贴入窗口中。'); ?>"
},
table:{
desc:"<?php _e('插入新表格'); ?>",
row_before_desc:"<?php _e('插入列于前'); ?>",
row_after_desc:"<?php _e('插入列于后'); ?>",
delete_row_desc:"<?php _e('删除本列'); ?>",
col_before_desc:"<?php _e('插入栏于前'); ?>",
col_after_desc:"<?php _e('插入栏于后'); ?>",
delete_col_desc:"<?php _e('删除本栏'); ?>",
split_cells_desc:"<?php _e('分割储存格'); ?>",
merge_cells_desc:"<?php _e('合并储存格'); ?>",
row_desc:"<?php _e('表格列 属性'); ?>",
cell_desc:"<?php _e('储存格 属性'); ?>",
props_desc:"<?php _e('表格 属性'); ?>",
paste_row_before_desc:"<?php _e('贴入列于前'); ?>",
paste_row_after_desc:"<?php _e('贴入列于后'); ?>",
cut_row_desc:"<?php _e('剪切此列'); ?>",
copy_row_desc:"<?php _e('复制此列'); ?>",
del:"<?php _e('删除表格'); ?>",
row:"<?php _e('列'); ?>",
col:"<?php _e('栏'); ?>",
cell:"<?php _e('储存格'); ?>"
},
autosave:{
unload_msg:"<?php _e('如果离开该页，将导致所有修改全部遗失。'); ?>"
},
fullscreen:{
desc:"<?php _e('切换全屏幕模式'); ?>"
},
media:{
desc:"<?php _e('插入/编辑 媒体文件'); ?>",
edit:"<?php _e('编辑 媒体文件'); ?>"
},
fullpage:{
desc:"<?php _e('文档属性'); ?>"
},
template:{
desc:"<?php _e('插入预先定义的模板内容'); ?>"
},
visualchars:{
desc:"<?php _e('可见控制字符 开/关'); ?>"
},
spellchecker:{
desc:"<?php _e('切换拼写检查'); ?>",
menu:"<?php _e('拼写检查设定'); ?>",
ignore_word:"<?php _e('忽略字'); ?>",
ignore_words:"<?php _e('忽略全部'); ?>",
langs:"<?php _e('语言'); ?>",
wait:"<?php _e('请稍后..'); ?>",
sug:"<?php _e('建议'); ?>",
no_sug:"<?php _e('无建议'); ?>",
no_mpell:"<?php _e('无拼写错误'); ?>"
},
morebreak:{
desc:"<?php _e('插入摘要分割符'); ?>"
}}});

tinyMCE.addI18n('typecho.advanced',{
style_select:"<?php _e('样式'); ?>",
font_size:"<?php _e('字体样式'); ?>",
fontdefault:"<?php _e('字体'); ?>",
block:"<?php _e('格式'); ?>",
paragraph:"<?php _e('段落'); ?>",
div:"<?php _e('布局'); ?>",
address:"<?php _e('地址'); ?>",
pre:"<?php _e('原始格式'); ?>",
h1:"<?php _e('标题 1 (H1)'); ?>",
h2:"<?php _e('标题 2 (H2)'); ?>",
h3:"<?php _e('标题 3 (H3)'); ?>",
h4:"<?php _e('标题 4 (H4)'); ?>",
h5:"<?php _e('标题 5 (H5)'); ?>",
h6:"<?php _e('标题 6 (H6)'); ?>",
blockquote:"<?php _e('引用'); ?>",
code:"<?php _e('代码'); ?>",
samp:"<?php _e('程序范例'); ?>",
dt:"<?php _e('名词定义'); ?>",
dd:"<?php _e('名词解释'); ?>",
bold_desc:"<?php _e('粗体 (Ctrl+B)'); ?>",
italic_desc:"<?php _e('斜体 (Ctrl+I)'); ?>",
underline_desc:"<?php _e('下划线 (Ctrl+U)'); ?>",
striketrough_desc:"<?php _e('删除线'); ?>",
justifyleft_desc:"<?php _e('居左对齐'); ?>",
justifycenter_desc:"<?php _e('居中对齐'); ?>",
justifyright_desc:"<?php _e('居右对齐'); ?>",
justifyfull_desc:"<?php _e('左右对齐'); ?>",
bullist_desc:"<?php _e('无序列表'); ?>",
numlist_desc:"<?php _e('有序列表'); ?>",
outdent_desc:"<?php _e('减少缩排'); ?>",
indent_desc:"<?php _e('增加缩排'); ?>",
undo_desc:"<?php _e('撤销 (Ctrl+Z)'); ?>",
redo_desc:"<?php _e('重做 (Ctrl+Y)'); ?>",
link_desc:"<?php _e('插入/编辑 链接'); ?>",
unlink_desc:"<?php _e('取消链接'); ?>",
image_desc:"<?php _e('插入/编辑 图片'); ?>",
cleanup_desc:"<?php _e('清除冗余代码'); ?>",
code_desc:"<?php _e('编辑 HTML 源代码'); ?>",
sub_desc:"<?php _e('下标'); ?>",
sup_desc:"<?php _e('上标'); ?>",
hr_desc:"<?php _e('插入水平分割线'); ?>",
removeformat_desc:"<?php _e('清除样式'); ?>",
custom1_desc:"<?php _e('在此输入自定义描述'); ?>",
forecolor_desc:"<?php _e('选择文本前景色'); ?>",
backcolor_desc:"<?php _e('选择文本背景色'); ?>",
charmap_desc:"<?php _e('插入自定义符号'); ?>",
visualaid_desc:"<?php _e('切换可见/隐藏元素'); ?>",
anchor_desc:"<?php _e('插入/编辑 锚点'); ?>",
cut_desc:"<?php _e('剪切 (Ctrl+X)'); ?>",
copy_desc:"<?php _e('复制 (Ctrl+C)'); ?>",
paste_desc:"<?php _e('粘贴 (Ctrl+V)'); ?>",
image_props_desc:"<?php _e('图片属性'); ?>",
newdocument_desc:"<?php _e('新文件'); ?>",
help_desc:"<?php _e('帮助'); ?>",
blockquote_desc:"<?php _e('引用'); ?>",
clipboard_msg:"<?php _e('复制/剪切/粘贴功能在 Mozilla 和 Firefox 中无法使用。\r\n需要获取更多相关信息？'); ?>",
path:"<?php _e('路径'); ?>",
newdocument:"<?php _e('你确定要清除所有内容吗？'); ?>",
toolbar_focus:"<?php _e('移至工具栏 - Alt+Q, 移至编辑器 - Alt-Z, 移至元素路径 - Alt-X'); ?>",
more_colors:"<?php _e('其它更多颜色'); ?>",

colorpicker_delta_height: 30,
image_delta_height: 30,
link_delta_height: -20,
link_delta_width: 10
});

tinyMCE.addI18n('typecho.advanced_dlg',{
about_title:"<?php _e('关于TinyMCE'); ?>",
about_general:"<?php _e('关于'); ?>",
about_help:"<?php _e('帮助'); ?>",
about_license:"<?php _e('授权'); ?>",
about_plugins:"<?php _e('插件'); ?>",
about_plugin:"<?php _e('插件'); ?>",
about_author:"<?php _e('作者'); ?>",
about_version:"<?php _e('版本'); ?>",
about_loaded:"<?php _e('已加载的插件'); ?>",
anchor_title:"<?php _e('插入/编辑 锚点'); ?>",
anchor_name:"<?php _e('锚点名'); ?>",
code_title:"<?php _e('HTML 源代码编辑器'); ?>",
code_wordwrap:"<?php _e('自动换行'); ?>",
colorpicker_title:"<?php _e('选择颜色'); ?>",
colorpicker_picker_tab:"<?php _e('选色器'); ?>",
colorpicker_picker_title:"<?php _e('选色器'); ?>",
colorpicker_palette_tab:"<?php _e('色盘'); ?>",
colorpicker_palette_title:"<?php _e('色盘颜色'); ?>",
colorpicker_named_tab:"<?php _e('已指定'); ?>",
colorpicker_named_title:"<?php _e('已指定颜色'); ?>",
colorpicker_color:"<?php _e('颜色:'); ?>",
colorpicker_name:"<?php _e('名称:'); ?>",
charmap_title:"<?php _e('选择自定义符号'); ?>",
image_title:"<?php _e('插入/编辑图片'); ?>",
image_src:"<?php _e('图片地址'); ?>",
image_alt:"<?php _e('图片描述'); ?>",
image_list:"<?php _e('图片列表'); ?>",
image_border:"<?php _e('边框'); ?>",
image_dimensions:"<?php _e('尺寸'); ?>",
image_vspace:"<?php _e('垂直间距'); ?>",
image_hspace:"<?php _e('水平间距'); ?>",
image_align:"<?php _e('对齐'); ?>",
image_align_baseline:"<?php _e('基线对齐'); ?>",
image_align_top:"<?php _e('居上'); ?>",
image_align_middle:"<?php _e('居中'); ?>",
image_align_bottom:"<?php _e('居下'); ?>",
image_align_texttop:"<?php _e('文字上方'); ?>",
image_align_textbottom:"<?php _e('文字下方'); ?>",
image_align_left:"<?php _e('居左'); ?>",
image_align_right:"<?php _e('居右'); ?>",
link_title:"<?php _e('插入/编辑链接'); ?>",
link_url:"<?php _e('链接地址'); ?>",
link_target:"<?php _e('目标'); ?>",
link_target_same:"<?php _e('在当前窗口打开链接'); ?>",
link_target_blank:"<?php _e('在新窗口打开链接'); ?>",
link_titlefield:"<?php _e('标题'); ?>",
link_is_email:"<?php _e('你输入的URL似乎是一个email地址，是否要加上前辍字 mailto: ?'); ?>",
link_is_external:"<?php _e('你输入的URL似乎是一个外部链接，是否要加上前辍字 http:// ?'); ?>",
link_list:"<?php _e('链接列表'); ?>"
});

tinyMCE.addI18n('typecho.media_dlg',{
title:"<?php _e('插入/编辑 媒体文件'); ?>",
general:"<?php _e('常规'); ?>",
advanced:"<?php _e('高级'); ?>",
file:"<?php _e('文件/URL'); ?>",
list:"<?php _e('列表'); ?>",
size:"<?php _e('尺寸'); ?>",
preview:"<?php _e('预览'); ?>",
constrain_proportions:"<?php _e('保持比例'); ?>",
type:"<?php _e('类型'); ?>",
id:"<?php _e('ID'); ?>",
name:"<?php _e('名称'); ?>",
class_name:"<?php _e('类别'); ?>",
vspace:"<?php _e('垂直间距'); ?>",
hspace:"<?php _e('水平间距'); ?>",
play:"<?php _e('自动播放'); ?>",
loop:"<?php _e('循环'); ?>",
menu:"<?php _e('显示菜单'); ?>",
quality:"<?php _e('质量'); ?>",
scale:"<?php _e('缩放'); ?>",
align:"<?php _e('对齐'); ?>",
salign:"<?php _e('SAlign'); ?>",
wmode:"<?php _e('WMode'); ?>",
bgcolor:"<?php _e('背景色'); ?>",
base:"<?php _e('基底'); ?>",
flashvars:"<?php _e('Flash变量'); ?>",
liveconnect:"<?php _e('SWLiveConnect'); ?>",
autohref:"<?php _e('AutoHREF'); ?>",
cache:"<?php _e('缓存'); ?>",
hidden:"<?php _e('隐藏'); ?>",
controller:"<?php _e('控制台'); ?>",
kioskmode:"<?php _e('Kiosk 模式'); ?>",
playeveryframe:"<?php _e('逐帧播放'); ?>",
targetcache:"<?php _e('目标暂存'); ?>",
correction:"<?php _e('修正'); ?>",
enablejavascript:"<?php _e('启用 JavaScript'); ?>",
starttime:"<?php _e('开始时间'); ?>",
endtime:"<?php _e('结束时间'); ?>",
href:"<?php _e('Href'); ?>",
qtsrcchokespeed:"<?php _e('Choke速度'); ?>",
target:"<?php _e('目标'); ?>",
volume:"<?php _e('音量'); ?>",
autostart:"<?php _e('自动播放'); ?>",
enabled:"<?php _e('启用'); ?>",
fullscreen:"<?php _e('全屏幕'); ?>",
invokeurls:"<?php _e('挂用的URLs'); ?>",
mute:"<?php _e('静音'); ?>",
stretchtofit:"<?php _e('缩放至适合大小'); ?>",
windowlessvideo:"<?php _e('无窗口播放'); ?>",
balance:"<?php _e('平衡'); ?>",
baseurl:"<?php _e('基底 网址'); ?>",
captioningid:"<?php _e('字幕ID'); ?>",
currentmarker:"<?php _e('目前标记'); ?>",
currentposition:"<?php _e('当前位置'); ?>",
defaultframe:"<?php _e('预设帧'); ?>",
playcount:"<?php _e('播放次数'); ?>",
rate:"<?php _e('码率'); ?>",
uimode:"<?php _e('UI 模式'); ?>",
flash_options:"<?php _e('Flash 选项'); ?>",
qt_options:"<?php _e('Quicktime 选项'); ?>",
wmp_options:"<?php _e('Windows Media Player 选项'); ?>",
rmp_options:"<?php _e('Real Media Player 选项'); ?>",
shockwave_options:"<?php _e('Shockwave 选项'); ?>",
autogotourl:"<?php _e('自动转至 URL'); ?>",
center:"<?php _e('居中'); ?>",
imagestatus:"<?php _e('图片状态'); ?>",
maintainaspect:"<?php _e('维持比例'); ?>",
nojava:"<?php _e('No java'); ?>",
prefetch:"<?php _e('预读'); ?>",
shuffle:"<?php _e('随机'); ?>",
console:"<?php _e('控制台'); ?>",
numloop:"<?php _e('循环次数'); ?>",
controls:"<?php _e('控制'); ?>",
scriptcallbacks:"<?php _e('Script回传'); ?>",
swstretchstyle:"<?php _e('缩放样式'); ?>",
swstretchhalign:"<?php _e('缩放至水平对齐'); ?>",
swstretchvalign:"<?php _e('缩放至垂直对齐'); ?>",
sound:"<?php _e('声音'); ?>",
progress:"<?php _e('进度'); ?>",
qtsrc:"<?php _e('QT Src'); ?>",
qt_stream_warn:"<?php _e('RTSP协议的流资源需要在高级选项中增加QT Src域。 \n您还要补充一个非流的SRC域..'); ?>",
align_top:"<?php _e('居顶'); ?>",
align_right:"<?php _e('居右'); ?>",
align_bottom:"<?php _e('居底'); ?>",
align_left:"<?php _e('居左'); ?>",
align_center:"<?php _e('居中'); ?>",
align_top_left:"<?php _e('居顶左'); ?>",
align_top_right:"<?php _e('居顶右'); ?>",
align_bottom_left:"<?php _e('居底左'); ?>",
align_bottom_right:"<?php _e('居底右'); ?>",
flv_options:"<?php _e('Flash 视频选项'); ?>",
flv_scalemode:"<?php _e('缩放模式'); ?>",
flv_buffer:"<?php _e('缓冲'); ?>",
flv_startimage:"<?php _e('启动图片'); ?>",
flv_starttime:"<?php _e('启动时间'); ?>",
flv_defaultvolume:"<?php _e('预设音量'); ?>",
flv_hiddengui:"<?php _e('隐藏GUI'); ?>",
flv_autostart:"<?php _e('自动启动'); ?>",
flv_loop:"<?php _e('循环'); ?>",
flv_showscalemodes:"<?php _e('显示缩放模式'); ?>",
flv_smoothvideo:"<?php _e('平滑视图'); ?>",
flv_jscallback:"<?php _e('JS 回传'); ?>"
});

/** offset */
tinyMCE.addI18n('typecho.media',{
    delta_height:40
});
