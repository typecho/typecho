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
    const HEADER = '%TYPECHO_BACKUP_FILE%';

    /**
     * @var array
     */
    private $_types = array(
        'contents'          =>  1,
        'comments'          =>  2,
        'metas'             =>  3,
        'relationships'     =>  4,
        'users'             =>  5,
        'fields'            =>  6
    );

    /**
     * @var array
     */
    private $_cleared = array();

    /**
     * @var bool
     */
    private $_login = false;

    /**
     * @param $type
     * @param $data
     * @return string
     */
    private function buildBuffer($type, $data)
    {
        $body = '';
        $schema = array();

        foreach ($data as $key => $val) {
            $schema[$key] = NULL === $val ? NULL : strlen($val);
            $body .= $val;
        }

        $header = Json::encode($schema);
        return Typecho_Common::buildBackupBuffer($type, $header, $body);
    }

    /**
     * 解析数据
     *
     * @param $file
     */
    private function extractData($file)
    {
        $fp = @fopen($file, 'rb');

        if (!$fp) {
            $this->widget('Widget_Notice')->set(_t('无法读取备份文件'), 'error');
            $this->response->goBack();
        }

        $fileSize = filesize($file);
        $headerSize = strlen(self::HEADER);

        if ($fileSize < $headerSize) {
            @fclose($fp);
            $this->widget('Widget_Notice')->set(_t('备份文件格式错误'), 'error');
            $this->response->goBack();
        }

        $fileHeader = @fread($fp, $headerSize);

        if (!$fileHeader || $fileHeader != self::HEADER) {
            @fclose($fp);
            $this->widget('Widget_Notice')->set(_t('备份文件格式错误'), 'error');
            $this->response->goBack();
        }

        fseek($fp, $fileSize - $headerSize);
        $fileFooter = @fread($fp, $headerSize);

        if (!$fileFooter || $fileFooter != self::HEADER) {
            @fclose($fp);
            $this->widget('Widget_Notice')->set(_t('备份文件格式错误'), 'error');
            $this->response->goBack();
        }

        fseek($fp, $headerSize);
        $offset = $headerSize;

        while (!feof($fp) && $offset + $headerSize < $fileSize) {
            $data = Typecho_Common::extractBackupBuffer($fp, $offset);

            if (!$data) {
                @fclose($fp);
                $this->widget('Widget_Notice')->set(_t('恢复数据出现错误'), 'error');
                $this->response->goBack();
            }

            list ($type, $header, $body) = $data;
            $this->processData($type, $header, $body);
        }

        @fclose($fp);
        $this->widget('Widget_Notice')->set(_t('数据恢复完成'), 'success');
        $this->response->goBack();
    }

    /**
     * @param $type
     * @param $header
     * @param $body
     */
    private function processData($type, $header, $body)
    {
        $table = array_search($type, $this->_types);

        if (!empty($table)) {
            $schema = Json::decode($header, true);
            $data = array();
            $offset = 0;

            foreach ($schema as $key => $val) {
                $data[$key] = NULL === $val ? NULL : substr($body, $offset, $val);
                $offset += $val;
            }

            $this->importData($table, $data);
        } else {
            Typecho_Plugin::factory(__CLASS__)->import($type, $header, $body);
        }
    }

    /**
     * 导入单条数据
     *
     * @param $table
     * @param $data
     */
    private function importData($table, $data)
    {
        $db = $this->db;

        try {
            if (empty($this->_cleared[$table])) {
                // 清除数据
                $db->query($db->delete('table.' . $table));
                $this->_cleared[$table] = true;
            }

            if (!$this->_login && 'users' == $table && $data['group'] == 'administrator') {
                // 重新登录
                $this->reLogin($data);
            }

            $db->query($db->insert('table.' . $table)->rows($data));
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('恢复过程中遇到如下错误: %s', $e->getMessage()), 'error');
            $this->response->goBack();
        }
    }

    /**
     * 备份过程会重写用户数据
     * 所以需要重新登录当前用户
     *
     * @param $user
     */
    private function reLogin(&$user)
    {
        if (empty($user['authCode'])) {
            $user['authCode'] = function_exists('openssl_random_pseudo_bytes') ?
                bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
        }

        $user['activated'] = $this->options->time;
        $user['logged'] = $user['activated'];

        Typecho_Cookie::set('__typecho_uid', $user['uid']);
        Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($user['authCode']));
        $this->_login = true;
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

        $buffer = self::HEADER;
        $db = $this->db;

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
        echo self::HEADER;
        ob_end_flush();
    }

    /**
     * 导入数据
     */
    private function import()
    {
        $path = NULL;

        if (!empty($_FILES)) {
            $file = array_pop($_FILES);

            if (0 == $file['error'] && is_uploaded_file($file['tmp_name'])) {
                $path = $file['tmp_name'];
            } else {
                $this->widget('Widget_Notice')->set(_t('备份文件上传失败'), 'error');
                $this->response->goBack();
            }
        } else {
            if (!$this->request->is('file')) {
                $this->widget('Widget_Notice')->set(_t('没有选择任何备份文件'), 'error');
                $this->response->goBack();
            }

            $path = __TYPECHO_BACKUP_DIR__ . '/' . $this->request->get('file');

            if (!file_exists($path)) {
                $this->widget('Widget_Notice')->set(_t('备份文件不存在'), 'error');
                $this->response->goBack();
            }
        }

        $this->extractData($path);
    }

    /**
     * 列出已有备份文件
     *
     * @return array
     */
    public function listFiles()
    {
        return array_map('basename', glob(__TYPECHO_BACKUP_DIR__ . '/*.dat'));
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
