<?php
/**
 * 将文章同时发布到您的Qzone
 *
 * @package PostToQzone
 * @version 1.0 beta
 * @author blankyao
 * @link http://www.blankyao.cn
 */
include "phpmailer.php";
include "smtp.php";
class PostToQzone_Plugin implements Typecho_Plugin_Interface
{
    /**
     * activate
     *
     * @static
     * @access public
     * @return void
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->insert =
            array('PostToQzone_Plugin', 'publish');
        if(!extension_loaded("sockets")){
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持socket扩展, 无法正常使用此功能'));
        }
        return _t('请配置您的qq号码以及密码，以便发布文章到Qzone');
    }

    /**
     * deactivate
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate()
    {
    }

    /**
     * 插件配置面板
     *
     * @param Typecho_Widget_Helper_Form $form
     * @static
     * @access public
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $qq = new Typecho_Widget_Helper_Form_Element_Text('qq', NULL, NULL,
        _t('qq号码'), _t('请填写您的qq号码'));
        $qq->addRule('isInteger', _t('qq号码必须是纯数字'));
        $form->addInput($qq->addRule('required', _t('必须填写一个qq号码')));
        $psw = new Typecho_Widget_Helper_Form_Element_Password('psw', NULL, NULL,
        _t('qq邮箱密码'), _t('请填写您的qq邮箱密码'));
        $form->addInput($psw->addRule('required', _t('必须填写一个qq邮箱密码')));
        $title = new Typecho_Widget_Helper_Form_Element_Text('title', NULL, '{post_title}',
        _t('标题模板'), _t('请填写您的标题模板'));
        $form->addInput($title->addRule('required', _t('必须填写一个标题模板')));
        $content = new Typecho_Widget_Helper_Form_Element_Textarea('content', NULL, '{post_content}',
        _t('内容模板'), _t('请填写您的内容模板'));
        $form->addInput($content->addRule('required', _t('必须填写一个内容模板')));
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
     * 发送文章到qzone
     *
     * @param mixed $contents 文章结构体
     * @access public
     * @return mixed $contents 处理后的文章结构体
     */
    public function publish($contents)
    {
        //todo:增加一个选项，如果选择发送的qzone的话再发到qzone
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('PostToQzone');
        $config = postToQzoneDefault($config);

        if($config->qq > 1000 && !empty($contents['title'])  && !empty($contents['text'])){

            $post_content = str_replace('{post_content}', $contents['text'], $config->content);
            $post_content = str_replace('{post_title}', $contents['title'], $post_content);

            $post_title = str_replace('{post_title}', $contents['title'], $config->title);

            $m=new Mailer($config->qq,$config->psw);
            $m->Halo($post_title,$post_content);
        }
        return $contents;
    }
}

function postToQzoneDefault($config){
	if(strpos($config->title,'{post_title}') === false){
		$config->title = '{post_title}';
	}

	if(strpos($config->content,'{post_content}') === false){
		$config->content = '{post_content}';
	}
	return $config;
}

class Mailer extends PHPMailer
{
	var $qq=null;
	function Mailer($qq,$psw) {
		$this->qq=$qq;
		$this->From	 = "{$qq}@qq.com";
		$this->FromName = $qq;
		$this->Host	 = "smtp.qq.com";
		$this->Mailer   = "smtp";
		$this->WordWrap = 75;
		$this->CharSet = Typecho_Widget::widget('Widget_Options')->charset;
		$this->Encoding = 'base64';
		$this->SMTPAuth = true;
		$this->IsHTML(true);
		$this->Username = $qq;
		$this->Password = $psw;
	}

	function Halo($subject,$body){
		$this->AddAddress("{$this->qq}@qzone.qq.com", "{$this->qq}@qzone.qq.com");
		$this->Subject = $subject;
		$this->Body	= $body;
		return $this->Send();
	}
}

class Crypter
{
   var $key;

   function Crypter($clave){
	  $this->key = $clave;
   }

   function keyED($txt) {
	  $encrypt_key = md5($this->key);
	  $ctr=0;
	  $tmp = "";
	  for ($i=0;$i<strlen($txt);$i++) {
		 if ($ctr==strlen($encrypt_key)) $ctr=0;
		 $tmp.= substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1);
		 $ctr++;
	  }
	  return $tmp;
   }

   function encrypt($txt){
	  srand((double)microtime()*1000000);
	  $encrypt_key = md5(rand(0,32000));
	  $ctr=0;
	  $tmp = "";
	  for ($i=0;$i<strlen($txt);$i++){
		 if ($ctr==strlen($encrypt_key)) $ctr=0;
		 $tmp.= substr($encrypt_key,$ctr,1) .
			 (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
		 $ctr++;
	  }
	  return base64_encode($this->keyED($tmp));
   }

   function decrypt($txt) {
	  $txt = $this->keyED(base64_decode($txt));
	  $tmp = "";
	  for ($i=0;$i<strlen($txt);$i++){
		 $md5 = substr($txt,$i,1);
		 $i++;
		 $tmp.= (substr($txt,$i,1) ^ $md5);
	  }
	  return $tmp;
   }
}
