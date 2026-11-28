<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Db;
use Typecho\Exception;
use Typecho\Plugin;
use Widget\Base\Options as BaseOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 备份工具
 *
 * @package Widget
 */
class Backup extends BaseOptions implements ActionInterface
{
    public const HEADER = '%TYPECHO_BACKUP_XXXX%';
    public const HEADER_VERSION = '0001';

    /**
     * @var array
     */
    private array $types = [
        'contents'      => 1,
        'comments'      => 2,
        'metas'         => 3,
        'relationships' => 4,
        'users'         => 5,
        'fields'        => 6
    ];

    /**
     * @var array
     */
    private array $fields = [
        'contents'      => [
            'cid', 'title', 'slug', 'created', 'modified', 'text', 'order', 'authorId',
            'template', 'type', 'status', 'password', 'commentsNum', 'allowComment', 'allowPing', 'allowFeed', 'parent'
        ],
        'comments'      => [
            'coid', 'cid', 'created', 'author', 'authorId', 'ownerId',
            'mail', 'url', 'ip', 'agent', 'text', 'type', 'status', 'parent'
        ],
        'metas'         => [
            'mid', 'name', 'slug', 'type', 'description', 'count', 'order', 'parent'
        ],
        'relationships' => ['cid', 'mid'],
        'users'         => [
            'uid', 'name', 'password', 'mail', 'url', 'screenName',
            'created', 'activated', 'logged', 'group', 'authCode'
        ],
        'fields'        => [
            'cid', 'name', 'type', 'str_value', 'int_value', 'float_value'
        ]
    ];

    /**
     * @var array
     */
    private array $primaryKeys = [
        'contents'      => ['cid'],
        'comments'      => ['coid'],
        'metas'         => ['mid'],
        'relationships' => ['cid', 'mid'],
        'users'         => ['uid'],
        'fields'        => ['cid', 'name']
    ];

    /**
     * @var array
     */
    private array $lastIds = [];

    private ?array $loginUser = null;

    private array $stagingTables = [];

    private array $replacedTables = [];

    /**
     * 列出已有备份文件
     *
     * @return array
     */
    public function listFiles(): array
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

    /**
     * 导出数据
     *
     * @throws \Typecho\Db\Exception
     */
    private function export()
    {
        $backupFile = tempnam(sys_get_temp_dir(), 'backup_');
        $fp = fopen($backupFile, 'wb');
        $host = parse_url($this->options->siteUrl, PHP_URL_HOST);
        $this->response->setContentType('application/octet-stream');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="'
            . date('Ymd') . '_' . $host . '_' . uniqid() . '.dat"');

        $header = str_replace('XXXX', self::HEADER_VERSION, self::HEADER);
        fwrite($fp, $header);
        $db = $this->db;
        $transactionStarted = false;
        $tablesLocked = false;

        try {
            if ($this->hasNonTransactionalTables(Db::READ)) {
                $this->lockExportTables();
                $tablesLocked = true;
            } else {
                $this->beginTransaction(Db::READ, true);
                $transactionStarted = true;
            }

            foreach ($this->types as $type => $val) {
                $cursor = null;
                do {
                    $rows = $db->fetchAll($this->buildExportQuery($type, $cursor));

                    foreach ($rows as $row) {
                        fwrite($fp, $this->buildBuffer($val, $this->applyFields($type, $row)));
                    }

                    if (!empty($rows)) {
                        $lastRow = $rows[count($rows) - 1];
                        $cursor = array_intersect_key(
                            $lastRow,
                            array_flip($this->primaryKeys[$type])
                        );
                    }
                } while (count($rows) == 20);
            }

            if ($tablesLocked) {
                $this->unlockExportTables();
                $tablesLocked = false;
            } else {
                $this->commitTransaction(Db::READ);
                $transactionStarted = false;
            }

            self::pluginHandle()->call('export', $fp);
            fwrite($fp, $header);
            fclose($fp);
        } catch (\Throwable $exception) {
            if ($transactionStarted) {
                $this->rollbackTransaction(Db::READ);
            }
            if ($tablesLocked) {
                $this->unlockExportTables(true);
            }
            if (is_resource($fp)) {
                fclose($fp);
            }
            @unlink($backupFile);
            throw $exception;
        }

        $this->response->throwFile($backupFile, 'application/octet-stream');
    }

    /**
     * 构造稳定的主键游标查询。
     *
     * @param string $table
     * @param array|null $cursor
     * @return \Typecho\Db\Query
     * @throws \Typecho\Db\Exception
     */
    private function buildExportQuery(string $table, ?array $cursor)
    {
        $query = $this->db->select()->from('table.' . $table)->limit(20);
        $keys = $this->primaryKeys[$table];

        foreach ($keys as $key) {
            $query->order($key, Db::SORT_ASC);
        }

        if (null !== $cursor) {
            $conditions = [];
            $values = [];
            foreach ($keys as $index => $key) {
                $parts = [];
                for ($previous = 0; $previous < $index; $previous++) {
                    $parts[] = $keys[$previous] . ' = ?';
                    $values[] = $cursor[$keys[$previous]];
                }
                $parts[] = $key . ' > ?';
                $values[] = $cursor[$key];
                $conditions[] = '(' . implode(' AND ', $parts) . ')';
            }
            $query->where(implode(' OR ', $conditions), ...$values);
        }

        return $query;
    }

    /**
     * @param $type
     * @param $data
     * @return string
     */
    private function buildBuffer($type, $data): string
    {
        $body = '';
        $schema = [];

        foreach ($data as $key => $val) {
            $schema[$key] = null === $val ? null : strlen($val);
            $body .= $val;
        }

        $header = json_encode($schema);
        return Common::buildBackupBuffer($type, $header, $body);
    }

    /**
     * 过滤字段
     *
     * @param $table
     * @param $data
     * @return array
     */
    private function applyFields($table, $data): array
    {
        $result = [];

        foreach ($data as $key => $val) {
            $index = array_search($key, $this->fields[$table]);

            if ($index !== false) {
                $result[$key] = $val;

                if ($index === 0 && !in_array($table, ['relationships', 'fields'])) {
                    $this->lastIds[$table] = isset($this->lastIds[$table])
                        ? max($this->lastIds[$table], $val) : $val;
                }
            }
        }

        return $result;
    }

    /**
     * 导入数据
     */
    private function import()
    {
        $path = null;

        if (!empty($_FILES)) {
            $file = array_pop($_FILES);

            if(UPLOAD_ERR_NO_FILE == $file['error']) {
                Notice::alloc()->set(_t('没有选择任何备份文件'), 'error');
                $this->response->goBack();
            }

            if (UPLOAD_ERR_OK == $file['error'] && is_uploaded_file($file['tmp_name'])) {
                $path = $file['tmp_name'];
            } else {
                Notice::alloc()->set(_t('备份文件上传失败'), 'error');
                $this->response->goBack();
            }
        } else {
            if (!$this->request->is('file')) {
                Notice::alloc()->set(_t('没有选择任何备份文件'), 'error');
                $this->response->goBack();
            }

            $path = __TYPECHO_BACKUP_DIR__ . '/' . $this->request->get('file');

            if (!file_exists($path)) {
                Notice::alloc()->set(_t('备份文件不存在'), 'error');
                $this->response->goBack();
            }
        }

        $this->extractData($path);
    }

    /**
     * 解析数据
     *
     * @param $file
     * @throws \Typecho\Db\Exception
     */
    private function extractData($file)
    {
        $fp = @fopen($file, 'rb');

        if (!$fp) {
            Notice::alloc()->set(_t('无法读取备份文件'), 'error');
            $this->response->goBack();
        }

        $fileSize = filesize($file);
        $headerSize = strlen(self::HEADER);

        if ($fileSize < $headerSize) {
            @fclose($fp);
            Notice::alloc()->set(_t('备份文件格式错误'), 'error');
            $this->response->goBack();
        }

        $fileHeader = @fread($fp, $headerSize);

        if (!$this->parseHeader($fileHeader, $version)) {
            @fclose($fp);
            Notice::alloc()->set(_t('备份文件格式错误'), 'error');
            $this->response->goBack();
        }

        fseek($fp, $fileSize - $headerSize);
        $fileFooter = @fread($fp, $headerSize);

        if (
            !$this->parseHeader($fileFooter, $footerVersion)
            || $footerVersion !== $version
        ) {
            @fclose($fp);
            Notice::alloc()->set(_t('备份文件格式错误'), 'error');
            $this->response->goBack();
        }

        try {
            $this->walkBackupData(
                $fp,
                $fileSize,
                $headerSize,
                $version,
                [$this, 'validateData']
            );
        } catch (\Throwable $exception) {
            @fclose($fp);
            Notice::alloc()->set(_t('备份文件格式错误'), 'error');
            $this->response->goBack();
            return;
        }

        $transactionStarted = false;
        $stagingPrepared = false;
        $this->lastIds = [];
        $this->loginUser = null;

        try {
            if ($this->hasNonTransactionalTables(Db::WRITE)) {
                $stagingPrepared = true;
                $this->prepareStagingTables();
            } else {
                $this->beginTransaction(Db::WRITE);
                $transactionStarted = true;
                $this->clearImportTables();
            }
            $this->walkBackupData(
                $fp,
                $fileSize,
                $headerSize,
                $version,
                [$this, 'processData']
            );

            if (null === $this->loginUser) {
                throw new Exception(_t('备份文件中没有管理员用户'));
            }

            // 针对PGSQL重置计数
            if (false !== strpos(strtolower($this->db->getAdapterName()), 'pgsql')) {
                foreach ($this->lastIds as $table => $id) {
                    $seq = $this->db->getPrefix() . $table . '_seq';
                    $this->db->query('ALTER SEQUENCE ' . $seq . ' RESTART WITH ' . ($id + 1));
                }
            }

            if ($stagingPrepared) {
                $this->swapStagingTables();
            } else {
                $this->commitTransaction(Db::WRITE);
                $transactionStarted = false;
            }
        } catch (\Throwable $exception) {
            if ($transactionStarted) {
                $this->rollbackTransaction(Db::WRITE);
            }
            if ($stagingPrepared) {
                $this->cleanupStagingTables(false);
            }
            @fclose($fp);
            Notice::alloc()->set(
                _t('恢复过程中遇到如下错误: %s', $exception->getMessage()),
                'error'
            );
            $this->response->goBack();
            return;
        }

        @fclose($fp);
        if ($stagingPrepared) {
            $this->cleanupStagingTables(true);
        }
        $this->completeLogin();
        Notice::alloc()->set(_t('数据恢复完成'), 'success');
        $this->response->goBack();
    }

    /**
     * 逐条读取备份记录。
     *
     * @param resource $fp
     * @param int $fileSize
     * @param int $headerSize
     * @param string $version
     * @param callable $callback
     */
    private function walkBackupData(
        $fp,
        int $fileSize,
        int $headerSize,
        string $version,
        callable $callback
    ) {
        if (0 !== fseek($fp, $headerSize)) {
            throw new Exception(_t('无法读取备份文件'));
        }

        $offset = $headerSize;
        $endOffset = $fileSize - $headerSize;
        while ($offset < $endOffset) {
            $data = Common::extractBackupBuffer($fp, $offset, $version);
            if (!$data || $offset > $endOffset) {
                throw new Exception(_t('恢复数据出现错误'));
            }

            $callback(...$data);
        }

        if ($offset !== $endOffset) {
            throw new Exception(_t('恢复数据出现错误'));
        }
    }

    /**
     * 校验核心表备份记录。
     *
     * @param int $type
     * @param string $header
     * @param string $body
     */
    private function validateData($type, $header, $body)
    {
        $table = array_search($type, $this->types, true);
        if (false === $table) {
            return;
        }

        $schema = json_decode($header, true);
        if (!is_array($schema)) {
            throw new Exception(_t('备份文件格式错误'));
        }

        $length = 0;
        foreach ($schema as $key => $valueLength) {
            if (!in_array($key, $this->fields[$table], true)) {
                throw new Exception(_t('备份文件格式错误'));
            }
            if (null !== $valueLength && (!is_int($valueLength) || $valueLength < 0)) {
                throw new Exception(_t('备份文件格式错误'));
            }
            $length += null === $valueLength ? 0 : $valueLength;
        }

        foreach ($this->primaryKeys[$table] as $key) {
            if (!array_key_exists($key, $schema)) {
                throw new Exception(_t('备份文件格式错误'));
            }
        }
        if ($length !== strlen($body)) {
            throw new Exception(_t('备份文件格式错误'));
        }
    }

    /**
     * 在事务中清空所有核心备份表。
     */
    private function clearImportTables()
    {
        $driver = $this->db->getAdapter()->getDriver();
        foreach (['relationships', 'fields', 'comments', 'contents', 'metas', 'users'] as $table) {
            if ('mysql' === $driver) {
                $this->db->query($this->db->delete('table.' . $table));
            } else {
                $this->db->truncate('table.' . $table);
            }
        }
    }

    /**
     * 判断 MySQL 核心表是否包含不支持事务的存储引擎。
     *
     * @return bool
     */
    private function hasNonTransactionalTables(int $op): bool
    {
        if ('mysql' !== $this->db->getAdapter()->getDriver()) {
            return false;
        }

        $adapter = $this->db->getAdapter();
        $prefix = $this->db->getPrefix();
        $tables = array_map(
            function ($table) use ($prefix) {
                return $prefix . $table;
            },
            array_keys($this->types)
        );
        $quotedTables = array_map([$adapter, 'quoteValue'], $tables);
        $rows = $this->db->fetchAll(
            $this->db->query(
                'SHOW TABLE STATUS WHERE Name IN (' . implode(', ', $quotedTables) . ')',
                $op
            )
        );

        if (count($rows) !== count($tables)) {
            throw new Exception(_t('无法确认数据库表的存储引擎'));
        }
        foreach ($rows as $row) {
            if (!isset($row['Engine']) || 'innodb' !== strtolower($row['Engine'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 锁定非事务 MySQL 核心表，获得一致的导出视图。
     */
    private function lockExportTables()
    {
        $adapter = $this->db->getAdapter();
        $prefix = $this->db->getPrefix();
        $tables = [];
        foreach (array_keys($this->types) as $table) {
            $tables[] = $adapter->quoteColumn($prefix . $table) . ' READ';
        }

        $this->db->query(
            'LOCK TABLES ' . implode(', ', $tables),
            Db::READ,
            ''
        );
    }

    /**
     * 解除 MySQL 导出读锁。
     */
    private function unlockExportTables(bool $ignoreErrors = false)
    {
        try {
            $this->db->query('UNLOCK TABLES', Db::READ, '');
        } catch (\Throwable $exception) {
            if (!$ignoreErrors) {
                throw $exception;
            }
        }
    }

    /**
     * 为 MySQL 创建独立的恢复暂存表，兼容不支持事务的 MyISAM。
     */
    private function prepareStagingTables()
    {
        $suffix = '_restore_' . substr(
            hash('sha256', Common::randString(32) . microtime(true)),
            0,
            12
        );
        $adapter = $this->db->getAdapter();
        $prefix = $this->db->getPrefix();
        $autoIncrementTables = ['contents', 'comments', 'metas', 'users'];

        foreach (array_keys($this->types) as $table) {
            $source = $prefix . $table;
            $staging = $source . $suffix;
            $replaced = $source . '_previous' . $suffix;
            $this->stagingTables[$table] = $staging;
            $this->replacedTables[$table] = $replaced;

            $this->db->query(
                'CREATE TABLE ' . $adapter->quoteColumn($staging)
                    . ' LIKE ' . $adapter->quoteColumn($source),
                Db::WRITE,
                ''
            );
            if (in_array($table, $autoIncrementTables, true)) {
                $this->db->query(
                    'ALTER TABLE ' . $adapter->quoteColumn($staging) . ' AUTO_INCREMENT = 1',
                    Db::WRITE,
                    ''
                );
            }
        }
    }

    /**
     * 一次性替换 MySQL 核心表。
     */
    private function swapStagingTables()
    {
        $adapter = $this->db->getAdapter();
        $prefix = $this->db->getPrefix();
        $renames = [];

        foreach (array_keys($this->types) as $table) {
            $renames[] = $adapter->quoteColumn($prefix . $table)
                . ' TO ' . $adapter->quoteColumn($this->replacedTables[$table]);
            $renames[] = $adapter->quoteColumn($this->stagingTables[$table])
                . ' TO ' . $adapter->quoteColumn($prefix . $table);
        }

        $this->db->query(
            'RENAME TABLE ' . implode(', ', $renames),
            Db::WRITE,
            ''
        );
    }

    /**
     * 清理 MySQL 暂存表或替换后的旧表。
     *
     * @param bool $swapped
     */
    private function cleanupStagingTables(bool $swapped)
    {
        $adapter = $this->db->getAdapter();
        $tables = $swapped ? $this->replacedTables : $this->stagingTables;

        foreach ($tables as $table) {
            try {
                $this->db->query(
                    'DROP TABLE IF EXISTS ' . $adapter->quoteColumn($table),
                    Db::WRITE,
                    ''
                );
            } catch (\Throwable $exception) {
                // 恢复结果已经确定，残留暂存表可由后续维护清理。
            }
        }

        $this->stagingTables = [];
        $this->replacedTables = [];
    }

    /**
     * 开启数据库事务。
     *
     * @param int $op
     * @param bool $consistentSnapshot
     */
    private function beginTransaction(int $op, bool $consistentSnapshot = false)
    {
        $driver = $this->db->getAdapter()->getDriver();
        if ($consistentSnapshot && 'mysql' === $driver) {
            $sql = 'START TRANSACTION WITH CONSISTENT SNAPSHOT';
        } elseif ($consistentSnapshot && 'pgsql' === $driver) {
            $sql = 'BEGIN ISOLATION LEVEL REPEATABLE READ, READ ONLY';
        } else {
            $sql = 'BEGIN';
        }

        $this->db->query($sql, $op, '');
    }

    /**
     * 提交数据库事务。
     *
     * @param int $op
     */
    private function commitTransaction(int $op)
    {
        $this->db->query('COMMIT', $op, '');
    }

    /**
     * 回滚数据库事务。
     *
     * @param int $op
     */
    private function rollbackTransaction(int $op)
    {
        try {
            $this->db->query('ROLLBACK', $op, '');
        } catch (\Throwable $exception) {
            // 保留触发回滚的原始异常。
        }
    }

    /**
     * @param $str
     * @param $version
     * @return bool
     */
    private function parseHeader($str, &$version): bool
    {
        if (!$str || strlen($str) != strlen(self::HEADER)) {
            return false;
        }

        if (!preg_match("/%TYPECHO_BACKUP_[A-Z0-9]{4}%/", $str)) {
            return false;
        }

        $version = substr($str, 16, - 1);
        return true;
    }

    /**
     * @param $type
     * @param $header
     * @param $body
     */
    private function processData($type, $header, $body)
    {
        $table = array_search($type, $this->types, true);

        if (false !== $table) {
            $schema = json_decode($header, true);
            $data = [];
            $offset = 0;

            foreach ($schema as $key => $val) {
                $data[$key] = null === $val ? null : substr($body, $offset, $val);
                $offset += $val;
            }

            $this->importData($table, $data);
        } else {
            self::pluginHandle()->import($type, $header, $body);
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
        $target = $this->stagingTables[$table] ?? 'table.' . $table;

        if (null === $this->loginUser && 'users' == $table && $data['group'] == 'administrator') {
            $this->prepareLogin($data);
        }

        $db->query($db->insert($target)->rows($this->applyFields($table, $data)));
    }

    /**
     * 备份过程会重写用户数据
     * 所以需要重新登录当前用户
     *
     * @param $user
     */
    private function prepareLogin(&$user)
    {
        if (empty($user['authCode'])) {
            $user['authCode'] = bin2hex(random_bytes(16));
        }

        $user['activated'] = $this->options->time;
        $user['logged'] = $user['activated'];
        $this->loginUser = $user;
    }

    /**
     * 恢复成功后重新登录管理员。
     */
    private function completeLogin()
    {
        Cookie::set('__typecho_uid', $this->loginUser['uid']);
        Cookie::set('__typecho_authCode', Common::hash($this->loginUser['authCode']));
    }
}
