<?php
/**
 * 支持用twitter帐号在blog中留言并同步到twitter上
 * 
 * @package Connect to Twittter
 * @author blankyao
 * @version 1.0.0 Beta
 * @link http://www.blankyao.cn
 * @todo 文章自动推送到twitter twitter帐号注册
 */

include 'twitterOAuth.php';

class ConnectToTwitter_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('ConnectToTwitter_Plugin', 'postToTwitter');
        Typecho_Plugin::factory('Widget_Archive')->beforeRender = array('ConnectToTwitter_Plugin', 'initComment');
    }
 
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {
    
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $consumerKey = new Typecho_Widget_Helper_Form_Element_Text('consumerKey', NULL, '',
        _t('Consumer Key'), _t('Your application consumer key from Twitter.com. '));
        $form->addInput($consumerKey->addRule('required', _t('You must give the Consumer Key from Twitter.com')));
        
        $consumerSecret = new Typecho_Widget_Helper_Form_Element_Text('consumerSecret', NULL, '',
        _t('Consumer Secret'), _t('Your application consumer secret from Twitter.com. '));
        $form->addInput($consumerSecret->addRule('required', _t('You must give the Consumer Key from Twitter.com')));
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function initComment($api)
    {
        session_start();
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('ConnectToTwitter');

        //发送请求到twitter
        if(isset($api->request->connect_to_twitter))
        {
            $to = new TwitterOAuth($config->consumerKey, $config->consumerSecret);

            $tok = $to->getRequestToken();
            
            Typecho_Cookie::set('oauth_request_token', $tok['oauth_token']);
            Typecho_Cookie::set('oauth_request_token_secret', $tok['oauth_token_secret']);

            /* Build the authorization URL */
            $request_link = $to->getAuthorizeURL($tok['oauth_token']);
            header('Location:'.$request_link);
        }

        //从twitter返回
        if(isset($api->request->oauth_token)) {
            if(Typecho_Cookie::get('oauth_request_token') && Typecho_Cookie::get('oauth_request_token_secret'))
            {
                $to = new TwitterOAuth($config->consumerKey, $config->consumerSecret, Typecho_Cookie::get('oauth_request_token'), Typecho_Cookie::get('oauth_request_token_secret'));

                $tok = $to->getAccessToken();

                Typecho_Cookie::set('oauth_access_token', $tok['oauth_token'], time()+60*60*24*30);
                Typecho_Cookie::set('oauth_access_token_secret', $tok['oauth_token_secret'], time()+60*60*24*30);

                $info_json = $to->OAuthRequest('https://twitter.com/account/verify_credentials.json', array(), 'GET');
                $info = Typecho_Json::decode($info_json, true);

                self::twitterLogin($info, $api);
            }
        }
    }

    //登录，暂时做为setcookie,以后要和用户帐号相关联
    public static function twitterLogin($info, $api)
    {
        if (!empty($info['screen_name'])) {
            Typecho_Cookie::set('__typecho_remember_author', $info['screen_name'], time()+60*60*24*30);
        }
        
        if (!empty($info['url'])) {
            Typecho_Cookie::set('__typecho_remember_url',  $info['url'], time()+60*60*24*30);
        }
    }

    //发送信息到twitter
    public static function postToTwitter($api)
    {
        if(Typecho_Cookie::get('oauth_access_token') && Typecho_Cookie::get('oauth_access_token_secret') && $api->request->post_to_twitter) {
            $options = Typecho_Widget::widget('Widget_Options');
            $config = $options->plugin('ConnectToTwitter');
            $to = new TwitterOAuth($config->consumerKey, $config->consumerSecret, Typecho_Cookie::get('oauth_access_token'), Typecho_Cookie::get('oauth_access_token_secret'));

            $url_array = array();
            $url_array = explode('?', $api->request->getReferer());
            $url = $url_array[0] . '#comment-' . $api->coid;
            $post = $api->text . '  ( from ' . $url . '  ) ';
            $twitter = $to->OAuthRequest('https://twitter.com/statuses/update.xml', array('status' => $post), 'POST');
        }
        return $comment;
    }

    function showButton()
    {
        if(Typecho_Cookie::get('oauth_access_token') && Typecho_Cookie::get('oauth_access_token_secret')) {
            echo '<p><input type="checkbox" checked="" value="yes" id="post_to_twitter" name="post_to_twitter"/><label for="post_to_twitter">同时把留言更新到你的 Twitter</label></p>';
        } else {
            echo '<p><a href="?connect_to_twitter=yes"><img src="http://s3.amazonaws.com/static.whitleymedia/twitconnect.png" /></a></p>';
        }
    }
}
