<?php
/**
 * 段落处理类
 *
 * @category typecho
 * @package Common
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */
 
/** 载入api支持 */
require_once 'Typecho/Common.php';

/**
 * 用于对自动分段做处理
 *
 * @category typecho
 * @package Common
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Common_Paragraph
{
    /**
     * 唯一id
     * 
     * @access private
     * @var integer
     */
    private static $_uniqueId = 0;
    
    /**
     * 存储的段落
     * 
     * @access private
     * @var array
     */
    private static $_blocks = array();
    
    /**
     * 作为段落看待的标签
     * 
     * (default value: 'p|code|pre|div|blockquote|form|ul|ol|dd|table|h1|h2|h3|h4|h5|h6')
     * 
     * @var string
     * @access private
     * @static
     */
    private static $_blockTag = 'p|code|pre|div|blockquote|form|ul|ol|dd|table|h1|h2|h3|h4|h5|h6';

    /**
     * 生成唯一的id, 为了速度考虑最多支持1万个tag的处理
     * 
     * @access private
     * @return string
     */
    private static function makeUniqueId()
    {
        return ':' . str_pad(self::$_uniqueId ++, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 用段落方法处理换行
     * 
     * @access private
     * @param string $text
     * @return string
     */
    private static function cutByBlock($text)
    {
        $space = "( |　)";
        $text = str_replace("\r\n", "\n", trim($text));
        $text = preg_replace("/{$space}*\n{$space}*/is", "\n", $text);
        $text = preg_replace("/\n{2,}/", "</p><p>", $text);
        $text = nl2br($text);
        $text = preg_replace("/(<p>)?\s*<p:([0-9]{4})\/>\s*(<\/p>)?/s", "<p:\\2/>", $text);
        $text = preg_replace("/<p>{$space}*<\/p>/is", '', $text);
        return $text;
    }
    
    /**
     * 修复段落开头和结尾
     * 
     * @access private
     * @param string $text
     * @return string
     */
    private static function fixPragraph($text)
    {
        $text = trim($text);
        if (!preg_match("/^<(" . self::$_blockTag . ")(\s|>)/i", $text)) {
            $text = '<p>' . $text;
        }
        
        if (!preg_match("/<\/(" . self::$_blockTag . ")>$/i", $text)) {
            $text = $text . '</p>';
        }
        
        return $text;
    }
    
    /**
     * 替换段落的回调函数
     * 
     * @access public
     * @param array $matches 匹配值
     * @return string
     */
    public static function replaceBlockCallback($matches)
    {
        $tagMatch = '|' . $matches[1] . '|';
        $text = $matches[4];
    
        switch (true) {
            /** 用br处理换行 */
            case false !== strpos('|li|dd|dt|td|p|a|span|cite|strong|sup|sub|small|del|u|i|b|h1|h2|h3|h4|h5|h6|', $tagMatch):
                $text = nl2br(trim($text));
                break;
            /** 用段落处理换行 */
            case false !== strpos('|div|blockquote|form|', $tagMatch):
                $text = self::cutByBlock($text);
                if (false !== strpos($text, '</p><p>')) {
                    $text = self::fixPragraph($text);
                }
                break;
            default:
                break;
        }
        
        /** 没有段落能力的标签 */
        if (false !== strpos('|a|span|cite|strong|sup|sub|small|del|u|i|b|', $tagMatch)) {
            $key = '<b' . $matches[2] . '/>';
        } else {
            $key = '<p' . $matches[2] . '/>';
        }
        
        self::$_blocks[$key] = "<{$matches[1]}{$matches[3]}>{$text}</{$matches[1]}>";
        return $key;
    }

    /**
     * 处理文本
     * 
     * @access public
     * @param string $text 文本
     * @return string
     */
    public static function process($text)
    {
        /** 锁定标签 */
        $text = Typecho_Common::lockHTML($text);
        
        /** 重置计数器 */
        self::$_uniqueId = 0;
        self::$_blocks = array();
    
        /** 将已有的段落后面的换行处理掉 */
        $text = preg_replace(array("/<\/p>\s+<p(\s*)/is", "/\s*<br\s*\/?>\s*/is"), array("</p><p\\1", "<br />"), trim($text));
        
        /** 将所有非自闭合标签解析为唯一的字符串 */
        $foundTagCount = 0;
        $textLength = strlen($text);
        $uniqueIdList = array();
        
        if (preg_match_all("/<\/\s*([a-z0-9]+)>/is", $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $key => $match) {
                $tag = $matches[1][$key][0];
                
                $leftOffset = $match[1] - $textLength;
                $posSingle = strrpos($text, '<' . $tag . '>', $leftOffset);
                $posFix = strrpos($text, '<' . $tag . ' ', $leftOffset);
                $pos = false;
                
                switch (true) {
                    case (false !== $posSingle && false !== $posFix):
                        $pos = max($posSingle, $posFix);
                        break;
                    case false === $posSingle && false !== $posFix:
                        $pos = $posFix;
                        break;
                    case false !== $posSingle && false === $posFix:
                        $pos = $posSingle;
                        break;
                    default:
                        break;
                }
                
                if (false !== $pos) {
                    $uniqueId = self::makeUniqueId();
                    $uniqueIdList[$uniqueId] = $tag;
                    $tagLength = strlen($tag);
                    
                    $text = substr_replace($text, $uniqueId, $pos + 1 + $tagLength, 0);
                    $text = substr_replace($text, $uniqueId, $match[1] + 7 + $foundTagCount * 10 + $tagLength, 0); // 7 = 5 + 2
                    $foundTagCount ++;
                }
            }
        }
        
        foreach ($uniqueIdList as $uniqueId => $tag) {
            $text = preg_replace_callback("/<({$tag})({$uniqueId})([^>]*)>(.*)<\/\\1\\2>/is",
                array('Typecho_Common_Paragraph', 'replaceBlockCallback'), $text, 1);
        }
        
        $text = self::cutByBlock($text);
        $blocks = array_reverse(self::$_blocks);
        
        foreach ($blocks as $blockKey => $blockValue) {
            $text = str_replace($blockKey, $blockValue, $text);
        }
        
        $text = self::fixPragraph($text);
        
        /** 释放标签 */
        return Typecho_Common::releaseHTML($text);
    }
}
