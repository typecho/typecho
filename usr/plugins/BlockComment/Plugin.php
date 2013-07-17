<?php
/**
 * 增加评论黑名单（根据 IP 地址过滤）
 * 
 * @package Block Comment
 * @author 明城
 * @version 0.0.1
 * @link http://typecho.org
 */
class BlockComment_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->comment   = array('BlockComment_Plugin', 'filter');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array('BlockComment_Plugin', 'filter');
        Typecho_Plugin::factory('Widget_XmlRpc')->pingback    = array('BlockComment_Plugin', 'filter');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $hosts = new Typecho_Widget_Helper_Form_Element_Textarea('hosts', NULL, NULL, 
            _t('地址列表'), _t('每行单个地址，请仔细匹配以免误封杀'));

        $form->addInput($hosts);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    
    /**
     * 标记评论状态时的插件接口
     * 
     * @access public
     * @param array $comment 评论数据的结构体
     * @param Typecho_Widget $commentWidget 评论组件
     * @param string $status 评论状态
     * @return void
     */
    public static function mark($comment, $commentWidget, $status)
    {
        if ('spam' == $comment['status'] && $status != 'spam') {
            self::filter($comment, $commentWidget, NULL, 'submit-ham');
        } else if ('spam' != $comment['status'] && $status == 'spam') {
            self::filter($comment, $commentWidget, NULL, 'submit-spam');
        }
    }
    
    /**
     * 评论过滤器
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @param array $result 返回的结果上下文
     * @param string $api api地址
     * @return void
     */
    public static function filter($comment, $post, $result, $api = 'comment-check')
    {
        $comment = empty($result) ? $comment : $result;
    
        $options = Typecho_Widget::widget('Widget_Options');
        $hosts   = $options->plugin('BlockComment')->hosts;
        
        $data = array(
            'blog'                  =>  $options->siteUrl,
            'user_ip'               =>  $comment['ip'],
            'user_agent'            =>  $comment['agent'],
            'referrer'              =>  Typecho_Request::getInstance()->getReferer(),
            'permalink'             =>  $post->permalink,
            'comment_type'          =>  $comment['type'],
            'comment_author'        =>  $comment['author'],
            'comment_author_email'  =>  $comment['mail'],
            'comment_author_url'    =>  $comment['url'],
            'comment_content'       =>  $comment['text']
        );

        foreach(split("\n", $hosts) as $key => $value){
            $value = trim($value);
            if (strlen($value)) {
                $regex = sprintf("/^%s/i", preg_quote($value));

                // 如果提交者符合指定的 IP，则扔进垃圾评论中
                if (preg_match($regex, $data['user_ip'])) {
                    $comment['status'] = 'spam';
                    break;
                }
            }
        }

        return $comment;
    }
}
