<?php

/**
 * 此类主要帮助用户在命令行界面对博客进行管理
 *
 * @package Helper
 * @author joyqi
 * @version 1.0.0
 * @link http://typecho.org
 */
class CLI
{
    /**
     * @var array
     */
    private $_args = array();

    /**
     * @var array
     */
    private $_definition = array();

    /**
     * @var string
     */
    private $_help = "Usage: php index.php [options] [--] [args...]\n";

    /**
     * CLI constructor.
     */
    public function __construct()
    {
        $this->_definition = array(
            'h'             =>  array(_t('帮助信息')),
            'v'             =>  array(_t('获取版本信息')),
            'e'             =>  array(_t('导出数据')),
            'i'             =>  array(_t('导入数据')),
            'with-theme'    =>  array(_t('导出时包含现有主题')),
            'with-plugins'  =>  array(_t('导出时包含插件及配置'))
        );

        $this->parseArgs();
        $this->parseDefinition();

        switch (true) {
            case !empty($this->_args['v']):
                $this->handleVersion();
                break;
            case !empty($this->_args['h']):
            default:
                echo $this->_help;
                break;
        }
    }

    /**
     * 获取版本信息
     */
    private function handleVersion()
    {
        echo 'Typecho ' . Typecho_Common::VERSION . "\n";
        echo 'PHP ' . phpversion() . "\n";
        echo Typecho_Db::get()->getVersion() . "\n";
    }

    /**
     * 解析帮助信息
     */
    private function parseDefinition()
    {
        $splitted = false;

        foreach ($this->_definition as $key => $val) {
            $placeholder = isset($val[1]) ? " <{$val[1]}>" : '';
            $prefix = strlen($key) > 1 ? '--' : '-';

            if ($prefix == '--' && !$splitted) {
                $this->_help .= "\n";
                $splitted = true;
            }

            $this->_help .= "\n   " . str_pad($prefix . $key . $placeholder, 28, ' ', STR_PAD_RIGHT) . $val[0];
        }

        $this->_help .= "\n\n";
    }

    /**
     * 解析命令行参数
     */
    private function parseArgs()
    {
        global $argv;

        if ($argv[0] == $_SERVER['PHP_SELF']) {
            array_shift($argv);
        }

        $last = NULL;

        foreach ($argv as $arg) {
            if (preg_match("/^\-\-([_a-z0-9-]+)(=(.+))?$/i", $arg, $matches)) {
                $last = $matches[1];
                $val = isset($matches[3]) ? $matches[3] : true;

                $this->_args[$last] = $val;
            } else if (preg_match("/^\-([a-z0-9])(.*)$/i", $arg, $matches)) {
                $last = $matches[1];
                $val = $matches[2];

                $this->_args[$last] = strlen($val) == 0 ? true : $val;
            } else if (!empty($last)) {
                $this->_args[$last] = $arg;
            }
        }
    }
}