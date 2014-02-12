<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 用于解决一个多个mo文件带来的读写问题
 * 我们重写了一个文件读取类
 *
 * @author qining
 * @category typecho
 * @package I18n
 */
class Typecho_I18n_GetTextMulti
{
    /**
     * 所有的文件读写句柄
     *
     * @access private
     * @var array
     */
    private $_handles = array();

    /**
     * 构造函数
     *
     * @access public
     * @param string $fileName 语言文件名
     * @return void
     */
    public function __construct($fileName)
    {
        $this->addFile($fileName);
    }

    /**
     * 增加一个语言文件
     *
     * @access public
     * @param string $fileName 语言文件名
     * @return void
     */
    public function addFile($fileName)
    {
        $this->_handles[] = new Typecho_I18n_GetText($fileName, true);
    }

    /**
     * Translates a string
     *
     * @access public
     * @param string string to be translated
     * @return string translated string (or original, if not found)
     */
    public function translate($string)
    {
        foreach ($this->_handles as $handle) {
            $string = $handle->translate($string, $count);
            if (-1 != $count) {
                break;
            }
        }

        return $string;
    }

    /**
     * Plural version of gettext
     *
     * @access public
     * @param string single
     * @param string plural
     * @param string number
     * @return translated plural form
     */
    public function ngettext($single, $plural, $number)
    {
        foreach ($this->_handles as $handle) {
            $string = $handle->ngettext($single, $plural, $number, $count);
            if (-1 != $count) {
                break;
            }
        }

        return $string;
    }

    /**
     * 关闭所有句柄
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->_handles as $handle) {
            /** 显示的释放内存 */
            unset($handle);
        }
    }
}
