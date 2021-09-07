<?php

namespace Typecho\I18n;

/**
 * 用于解决一个多个mo文件带来的读写问题
 * 我们重写了一个文件读取类
 *
 * @author qining
 * @category typecho
 * @package I18n
 */
class GetTextMulti
{
    /**
     * 所有的文件读写句柄
     *
     * @access private
     * @var GetText[]
     */
    private $handlers = [];

    /**
     * 构造函数
     *
     * @access public
     * @param string $fileName 语言文件名
     * @return void
     */
    public function __construct(string $fileName)
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
    public function addFile(string $fileName)
    {
        $this->handlers[] = new GetText($fileName, true);
    }

    /**
     * Translates a string
     *
     * @access public
     * @param string string to be translated
     * @return string translated string (or original, if not found)
     */
    public function translate(string $string): string
    {
        foreach ($this->handlers as $handle) {
            $string = $handle->translate($string, $count);
            if (- 1 != $count) {
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
     * @return string translated plural form
     */
    public function ngettext($single, $plural, $number): string
    {
        $count = - 1;

        foreach ($this->handlers as $handler) {
            $string = $handler->ngettext($single, $plural, $number, $count);
            if (- 1 != $count) {
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
        foreach ($this->handlers as $handler) {
            /** 显示的释放内存 */
            unset($handler);
        }
    }
}
