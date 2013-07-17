<?php
/**
 * 将 Magike 数据库中的数据转换为 Typecho
 * 
 * @package Magike to Typecho
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
class MagikeToTypecho_Plugin implements Typecho_Plugin_Interface
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
        if (!Typecho_Db_Adapter_Mysql::isAvailable() && !Typecho_Db_Adapter_Pdo_Mysql::isAvailable()) {
            throw new Typecho_Plugin_Exception(_t('没有找到任何可用的 Mysql 适配器'));
        }
        
        $error = NULL;
        if ((!is_dir(__TYPECHO_ROOT_DIR__ . '/usr/uploads/') || !is_writeable(__TYPECHO_ROOT_DIR__ . '/usr/uploads/'))
        && !is_writeable(__TYPECHO_ROOT_DIR__ . '/usr/')) {
            $error = '<br /><strong>' . _t('%s 目录不可写, 可能会导致附件转换不成功', __TYPECHO_ROOT_DIR__ . '/usr/uploads/') . '</strong>';
        }
    
        Helper::addPanel(1, 'MagikeToTypecho/panel.php', _t('从Magike导入数据'), _t('从Magike导入数据'), 'administrator');
        Helper::addAction('magike-to-typecho', 'MagikeToTypecho_Action');
        return _t('请在插件设置里设置 Magike 所在的数据库参数') . $error;
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction('magike-to-typecho');
        Helper::removePanel(1, 'MagikeToTypecho/panel.php');
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
        $host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL, 'localhost',
        _t('数据库地址'), _t('请填写 Magike 所在的数据库地址'));
        $form->addInput($host->addRule('required', _t('必须填写一个数据库地址')));
        
        $port = new Typecho_Widget_Helper_Form_Element_Text('port', NULL, '3306',
        _t('数据库端口'), _t('Magike 所在的数据库服务器端口'));
        $port->input->setAttribute('class', 'mini');
        $form->addInput($port->addRule('required', _t('必须填写数据库端口'))
        ->addRule('isInteger', _t('端口号必须是纯数字')));
        
        $user = new Typecho_Widget_Helper_Form_Element_Text('user', NULL, 'root',
        _t('数据库用户名'));
        $form->addInput($user->addRule('required', _t('必须填写数据库用户名')));
        
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL,
        _t('数据库密码'));
        $form->addInput($password);
        
        $database = new Typecho_Widget_Helper_Form_Element_Text('database', NULL, 'magike',
        _t('数据库名称'), _t('Magike 所在的数据库名称'));
        $form->addInput($database->addRule('required', _t('您必须填写数据库名称')));
    
        $prefix = new Typecho_Widget_Helper_Form_Element_Text('prefix', NULL, 'mg_',
        _t('表前缀'), _t('所有 Magike 数据表的前缀'));
        $form->addInput($prefix->addRule('required', _t('您必须填写表前缀')));
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
