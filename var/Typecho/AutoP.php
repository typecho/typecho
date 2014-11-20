<?php

/**
 * AutoP 
 * 
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com> 
 * @license GNU General Public License 2.0
 */
class AutoP
{
    // 作为段落的标签
    const BLOCK = 'p|pre|div|blockquote|form|ul|ol|dd|table|ins|h1|h2|h3|h4|h5|h6';

    /**
     * 唯一id
     * 
     * @access private
     * @var integer
     */
    private $_uniqueId = 0;
    
    /**
     * 存储的段落
     * 
     * @access private
     * @var array
     */
    private $_blocks = array();

    /**
     * 生成唯一的id, 为了速度考虑最多支持1万个tag的处理
     * 
     * @access private
     * @return string
     */
    private function makeUniqueId()
    {
        return ':' . str_pad($this->_uniqueId ++, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 用段落方法处理换行
     * 
     * @access private
     * @param string $text
     * @return string
     */
    private function cutByBlock($text)
    {
        $space = "( |　)";
        $text = str_replace("\r\n", "\n", trim($text));
        $text = preg_replace("/{$space}*\n{$space}*/is", "\n", $text);
        $text = preg_replace("/\s*<p:([0-9]{4})\/>\s*/is", "</p><p:\\1/><p>", $text);
        $text = preg_replace("/\n{2,}/", "</p><p>", $text);
        $text = nl2br($text);
        $text = preg_replace("/(<p>)?\s*<p:([0-9]{4})\/>\s*(<\/p>)?/is", "<p:\\2/>", $text);
        $text = preg_replace("/<p>{$space}*<\/p>/is", '', $text);
        $text = preg_replace("/\s*<p>\s*$/is", '', $text);
        $text = preg_replace("/^\s*<\/p>\s*/is", '', $text);
        return $text;
    }

    /**
     * 修复段落开头和结尾
     * 
     * @access private
     * @param string $text
     * @return string
     */
    private function fixPragraph($text)
    {
        $text = trim($text);
        if (!preg_match("/^<(" . self::BLOCK . ")(\s|>)/i", $text)) {
            $text = '<p>' . $text;
        }
        
        if (!preg_match("/<\/(" . self::BLOCK . ")>$/i", $text)) {
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
    public function replaceBlockCallback($matches)
    {
        $tagMatch = '|' . $matches[1] . '|';
        $text = $matches[4];
    
        switch (true) {
            /** 用br处理换行 */
            case false !== strpos('|li|dd|dt|td|p|a|span|cite|strong|sup|sub|small|del|u|i|b|ins|h1|h2|h3|h4|h5|h6|', $tagMatch):
                $text = nl2br(trim($text));
                break;
            /** 用段落处理换行 */
            case false !== strpos('|div|blockquote|form|', $tagMatch):
                $text = $this->cutByBlock($text);
                if (false !== strpos($text, '</p><p>')) {
                    $text = $this->fixPragraph($text);
                }
                break;
            default:
                break;
        }
        
        /** 没有段落能力的标签 */
        if (false !== strpos('|a|span|font|code|cite|strong|sup|sub|small|del|u|i|b|', $tagMatch)) {
            $key = '<b' . $matches[2] . '/>';
        } else {
            $key = '<p' . $matches[2] . '/>';
        }
        
        $this->_blocks[$key] = "<{$matches[1]}{$matches[3]}>{$text}</{$matches[1]}>";
        return $key;
    }

    /**
     * 自动分段 
     * 
     * @param string $text 
     * @static
     * @access private
     * @return string
     */
    public function parse($text)
    {
        /** 重置计数器 */
        $this->_uniqueId = 0;
        $this->_blocks = array();
        
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
                    $uniqueId = $this->makeUniqueId();
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
                array($this, 'replaceBlockCallback'), $text, 1);
        }
        
        $text = $this->cutByBlock($text);
        $blocks = array_reverse($this->_blocks);
        
        foreach ($blocks as $blockKey => $blockValue) {
            $text = str_replace($blockKey, $blockValue, $text);
        }
        
        return $this->fixPragraph($text);        
    }
}

