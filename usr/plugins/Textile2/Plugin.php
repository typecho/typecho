<?php
/**
 * This is a wrapper for Jim Riggs' <a href="http://jimandlissa.com/project/textilephp">PHP implementation</a> of <a href="http://bradchoate.com/mt-plugins/textile">Brad Choate's Textile 2</a>.  It is feature compatible with the MovableType plugin. <strong>Does not play well with the Markdown, Textile, or Textile 2 plugins that ship with WordPress.</strong>  Packaged by <a href="http://idly.org/">Adam Gessaman</a>.
 * 
 * @package Textile 2 (Improved)
 * @author Jim Riggs
 * @version 2.1.1
 * @dependence 9.9.2-*
 * @link http://jimandlissa.com/project/textilephp
 */
 
require('Textile2/Textile.php');

class Textile2_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('Textile2_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('Textile2_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->content = array('Textile2_Plugin', 'parse');
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
        $version = new Typecho_Widget_Helper_Form_Element_Radio('version', 
        array('MTTextile' => 'MTTextile - includes Brad Choates\' extensions.',
        'Textile' => 'Textile for the Textile purist.'), 'MTTextile',
        'Textile Flavor');
        $form->addInput($version->multiMode());

        $filters = new Typecho_Widget_Helper_Form_Element_Checkbox('filters', 
        array('SmartyPants' => 'Apply SmartyPants (provides em and en dashes, and other typographic niceities)',
        'EducateQuotes' => 'Apply Texturize (applies curly quotes)'),
        array('SmartyPants', 'EducateQuotes'), 'Text Filters');
        $form->addInput($filters->multiMode());
        
        $headerOffset = new Typecho_Widget_Helper_Form_Element_Select('headerOffset', 
        array('0 (.h1 = .h1)', '1 (.h1 = .h2)', '2 (.h1 = .h3)', '3 (.h1 = .h4)', '4 (.h1 = .h5)', '5 (.h1 = .h6)'),
        0, 'Header Offset');
        $form->addInput($headerOffset);
        
        $parsing = new Typecho_Widget_Helper_Form_Element_Checkbox('parsing', 
        array('ClearLines' => 'Strip extra spaces from the end of each line.',
        'PreserveSpaces' => 'Change double-spaces to the HTML entity for an em-space (&8195;).'),
        NULL, 'Parsing Options');
        $form->addInput($parsing->multiMode());
        
        $inputEncoding = new Typecho_Widget_Helper_Form_Element_Text('inputEncoding', NULL, Helper::options()->charset,
        _t('Input Character Encoding'));
        $inputEncoding->input->setAttribute('class', 'mini');
        $form->addInput($inputEncoding);
        
        $encoding = new Typecho_Widget_Helper_Form_Element_Text('encoding', NULL, Helper::options()->charset,
        _t('Output Character Encoding'));
        $encoding->input->setAttribute('class', 'mini');
        $form->addInput($encoding);
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
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;
        
        $settings = Helper::options()->plugin('Textile2');
        
        if ($settings->version == 'Textile') {
            $textile = new Textile;
        } else {
            $textile = new MTLikeTextile;
        }

        $textile->options['head_offset'] = $settings->headerOffset;
        $textile->options['char_encoding'] = $settings->encoding;
        $textile->options['input_encoding'] = $settings->inputEncoding;
        
        $textile->options['do_quotes'] = $settings->filters && in_array('EducateQuotes', $settings->filters);
        $textile->options['smarty_mode'] = $settings->filters && in_array('SmartyPants', $settings->filters);
        $textile->options['trim_spaces'] = $settings->parsing && in_array('ClearLines', $settings->parsing);
        $textile->options['preserve_spaces'] = $settings->parsing && in_array('PreserveSpaces', $settings->parsing);

        return $textile->process($text);
    }
}
