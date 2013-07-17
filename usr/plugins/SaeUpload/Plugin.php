<?php
/**
 * <a href="http://sae.sina.com.cn" target="_blank">Sina App Engine</a>专用的文件上传插件，使用Storage做持久化存储。
 * 
 * @package SaeUpload
 * @author Kimi
 * @version 1.0.0 Beta
 * @link http://www.ccvita.com/491.html
 */
class SaeUpload_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('SaeUpload_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('SaeUpload_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('SaeUpload_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('SaeUpload_Plugin', 'attachmentHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('SaeUpload_Plugin', 'attachmentDataHandle');
        
        return _t('请您在 <a href="http://sae.sina.com.cn/?m=storage&app_id='.$_SERVER['HTTP_APPNAME'].'" target="_blank">Sina App Engine控制面板</a> 中创建Storage的Domain: 名称固定为 <strong>typechoupload</strong>');
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
        $domainName = new Typecho_Widget_Helper_Form_Element_Text('saestoragedomain', NULL, 'typechoupload',
        _t('Domain名称'), _t('请您在 <a href="http://sae.sina.com.cn/?m=storage&app_id='.$_SERVER['HTTP_APPNAME'].'" target="_blank">Sina App Engine控制面板</a> 中创建Storage的Domain: 名称固定为 <strong>typechoupload</strong>'));
        $form->addInput($domainName->addRule(array('SaeUpload_Plugin', 'validateDomainName'), _t('Domain名称错误，或者未上传文件！')));
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
     * 验证Sina App Engine Storage中DomainName是否存在
     * 
     * @access public
     * @param string $domainName domainName
     * @return boolean
     */
    public static function validateDomainName($domainName)
    {
        return true;
        /*
        $stor = new SaeStorage();
        $ret = $stor->getFilesNum($domainName);
        if ($ret) {
            return true;
        } else {
            return false;
        }
        */
    }

    /**
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $fileName = preg_split("(\/|\\|:)", $file['name']);
        $file['name'] = array_pop($fileName);
        
        //获取扩展名
        $ext = '';
        $part = explode('.', $file['name']);
        if (($length = count($part)) > 1) {
            $ext = strtolower($part[$length - 1]);
        }

        if (!self::checkFileType($ext)) {
            return false;
        }

        //获取文件名
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $path . '/' . $fileName;//add for mkdir

        $stor = new SaeStorage();
        $options = Typecho_Widget::widget('Widget_Options');
        $SaeStorageDomain = $options->plugin('SaeUpload')->saestoragedomain;

        if (isset($file['tmp_name'])) {
            //移动上传文件
            if (!$path = $stor->upload($SaeStorageDomain,$fileName,$file['tmp_name'])) {
                return false;
            }
        } else if (isset($file['bits'])) {
            //直接写入文件
            if (!$path = $stor->write($SaeStorageDomain,$fileName,$file['bits'])) {
                return false;
            }
        } else {
            return false;
        }

        if (!isset($file['size'])) {
            $attr = $stor->getAttr($SaeStorageDomain,$fileName,array('length'));
            $file['size'] = $attr['length'];
        }

        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($path)
        );
    }

    /**
     * 修改文件处理函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function modifyHandle($content, $file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $fileName = preg_split("(\/|\\|:)", $file['name']);
        $file['name'] = array_pop($fileName);
        
        //获取扩展名
        $ext = '';
        $part = explode('.', $file['name']);
        if (($length = count($part)) > 1) {
            $ext = strtolower($part[$length - 1]);
        }

        if ($content['attachment']->type != $ext) {
            return false;
        }

        //获取文件名
        $fileName = $content['attachment']->path;
        $path = $path . '/' . $fileName;//add for mkdir

        $stor = new SaeStorage();
        $options = Typecho_Widget::widget('Widget_Options');
        $SaeStorageDomain = $options->plugin('SaeUpload')->saestoragedomain;

        if (isset($file['tmp_name'])) {
            //移动上传文件
            if (!$path = $stor->upload($SaeStorageDomain,$fileName,$file['tmp_name'])) {
                return false;
            }
        } else if (isset($file['bits'])) {
            //直接写入文件
            if (!$path = $stor->write($SaeStorageDomain,$fileName,$file['bits'])) {
                return false;
            }
        } else {
            return false;
        }

        if (!isset($file['size'])) {
            $attr = $stor->getAttr($SaeStorageDomain,$fileName,array('length'));
            $file['size'] = $attr['length'];
        }

        //返回相对存储路径
        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }

    /**
     * 删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function deleteHandle(array $content)
    {
        $stor = new SaeStorage();
        $options = Typecho_Widget::widget('Widget_Options');
        $SaeStorageDomain = $options->plugin('SaeUpload')->saestoragedomain;
        return $stor->delete($SaeStorageDomain,$content['attachment']->path);
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content)
    {
        $stor = new SaeStorage();
        $options = Typecho_Widget::widget('Widget_Options');
        $SaeStorageDomain = $options->plugin('SaeUpload')->saestoragedomain;
        return $stor->getUrl($SaeStorageDomain,$content['attachment']->path);
    }

    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function attachmentDataHandle(array $content)
    {
        $stor = new SaeStorage();
        $options = Typecho_Widget::widget('Widget_Options');
        $SaeStorageDomain = $options->plugin('SaeUpload')->saestoragedomain;
        return $stor->read($SaeStorageDomain,$content['attachment']->path);
    }

    /**
     * 检查文件名
     *
     * @access private
     * @param string $ext 扩展名
     * @return boolean
     */
    public static function checkFileType($ext)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        return in_array($ext, $options->allowedAttachmentTypes);
    }
}
