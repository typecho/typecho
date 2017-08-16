<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 */

/**
 * 备份工具
 *
 * @package Widget
 */
class Widget_Backup extends Widget_Abstract_Options implements Widget_Interface_Do
{
    /**
     * @var array
     */
    private $_types = [
        'contents'          =>  1,
        'comments'          =>  2,
        'metas'             =>  3,
        'relationships'     =>  4,
        'users'             =>  5,
        'fields'            =>  6
    ];

    /**
     * @param $type
     * @param $data
     * @return string
     */
    private function buildBuffer($type, $data)
    {
        $buffer = '';
        $body = '';

        $schema = [];
        foreach ($data as $key => $val) {
            $schema[$key] = strlen($val);
            $body .= $val;
        }
        $schemaHeader = json_encode($schema);

        $buffer .= pack('C', $type);     // 写入类型
        $buffer .= pack('v', strlen($schemaHeader));    // 写入头
        $buffer .= $schemaHeader . $body;

        return $buffer;
    }

    /**
     * 导出数据
     */
    private function export()
    {
        $host = parse_url($this->options->siteUrl, PHP_URL_HOST);
        $this->response->setContentType('application/octet-stream');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="'
            . date('Ymd') . '_' . $host . '_' . uniqid() . '.dat"');

        $buffer = '';
        $db = Typecho_Db::get();

        foreach ($this->_types as $type => $val) {
            $page = 1;
            do {
                $rows = $db->fetchAll($db->select()->from('table.' . $type)->page($page, 20));
                $page ++;

                foreach ($rows as $row) {
                    $buffer .= $this->buildBuffer($val, $row);

                    if (sizeof($buffer) >= 1024 * 1024) {
                        echo $buffer;
                        ob_flush();
                        $buffer = '';
                    }
                }
            } while (count($rows) == 20);
        }

        if (!empty($buffer)) {
            echo $buffer;
            ob_flush();
        }

        Typecho_Plugin::factory(__CLASS__)->export();
    }

    /**
     * 绑定动作
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();

        $this->on($this->request->is('do=export'))->export();
        $this->on($this->request->is('do=import'))->import();
    }

}
