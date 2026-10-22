<?php
use PHPUnit\Framework\TestCase;
use Typecho\Db;

/**
 * Fresh SQLite database per test, schema from install/SQLite.sql exactly like install.php does it.
 * Uses a temp FILE, not :memory: — Db keeps separate READ / WRITE connections, so :memory: would
 * give two different databases.
 */
abstract class SqliteTestCase extends TestCase
{
    protected Db $db;
    private string $base;
    private string $file;

    /** Overridden by data provider in subclasses to cover both adapters */
    protected function adapterName(): string
    {
        return getenv('TYPECHO_TEST_SQLITE_ADAPTER') ?: 'Pdo_SQLite';
    }

    protected function setUp(): void
    {
        // tempnam() reserves a unique name; the database itself lives next to it with a .db suffix
        $this->base = tempnam(sys_get_temp_dir(), 'typecho-test-');
        $this->file = $this->base . '.db';
        $this->db = new Db($this->adapterName(), 'typecho_');
        $this->db->addServer(['file' => $this->file], Db::READ | Db::WRITE);
        Db::set($this->db);

        $scripts = file_get_contents(__TYPECHO_ROOT_DIR__ . '/install/SQLite.sql');
        foreach (explode(';', $scripts) as $script) {
            if ($script = trim($script)) {
                $this->db->query($script, Db::WRITE);
            }
        }
    }

    protected function tearDown(): void
    {
        $this->db->flushPool();
        @unlink($this->file);
        @unlink($this->base);
    }
}
