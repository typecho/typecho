<?php
use PHPUnit\Framework\TestCase;
use Typecho\Db;

/**
 * Server-backed coverage for the adapters that the SQLite suite cannot reach: the same
 * assertions run against MySQL and PostgreSQL so a dialect regression (quoting, last insert
 * id, affected rows, boolean/number handling) fails here instead of in production.
 *
 * The servers are opt-in: without TYPECHO_TEST_MYSQL_* / TYPECHO_TEST_PGSQL_* environment
 * variables pointing at a reachable database the tests are skipped, so the default suite
 * never needs a network service.
 */
abstract class ServerDbTestCase extends TestCase
{
    protected Db $db;
    protected string $driver;

    /** @return array<string, mixed>|null connection config, null to skip */
    abstract protected function connection(): ?array;

    abstract protected function adapterName(): string;

    abstract protected function installScript(): string;

    /** Charset used for %charset% in the install script */
    protected function charset(): string
    {
        return 'utf8';
    }

    /** Storage engine used for %engine% in the install script */
    protected function engine(): string
    {
        return 'InnoDB';
    }

    protected function setUp(): void
    {
        $config = $this->connection();

        if (null === $config) {
            $this->markTestSkipped('no ' . $this->adapterName() . ' server configured');
        }

        $this->db = new Db($this->adapterName(), 'typecho_');
        $this->db->addServer($config, Db::READ | Db::WRITE);
        $this->driver = $this->db->getAdapter()->getDriver();

        $this->dropTables();
        $this->installSchema();
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->dropTables();
            $this->db->flushPool();
        }
    }

    private function tableNames(): array
    {
        return ['comments', 'contents', 'fields', 'metas', 'options', 'relationships', 'users'];
    }

    private function dropTables(): void
    {
        foreach ($this->tableNames() as $table) {
            try {
                $this->db->query('DROP TABLE IF EXISTS typecho_' . $table, Db::WRITE);
            } catch (\Typecho\Db\Adapter\SQLException $e) {
                // fresh database: nothing to drop
            }
        }

        // PostgreSQL keeps id sequences in their own namespace, so DROP TABLE leaves them behind
        if ($this->isPgsql()) {
            foreach ($this->tableNames() as $table) {
                try {
                    $this->db->query('DROP SEQUENCE IF EXISTS typecho_' . $table . '_seq', Db::WRITE);
                } catch (\Typecho\Db\Adapter\SQLException $e) {
                    // no sequence to drop
                }
            }
        }
    }

    private function isPgsql(): bool
    {
        return 0 === stripos($this->adapterName(), 'Pgsql') || 0 === stripos($this->adapterName(), 'Pdo_Pgsql');
    }

    private function installSchema(): void
    {
        $script = file_get_contents(__TYPECHO_ROOT_DIR__ . '/install/' . $this->installScript());

        // install.php resolves these placeholders from the connection settings
        $script = str_replace(
            ['%charset%', '%engine%'],
            [$this->charset(), $this->engine()],
            $script
        );

        // same splitting strategy as SqliteTestCase: statements end with ';'
        foreach (explode(';', $script) as $statement) {
            $lines = [];

            foreach (explode("\n", $statement) as $line) {
                $trimmed = ltrim($line);

                // phpMyAdmin and the Pgsql dump prefix statements with -- comments
                if (0 === strpos($trimmed, '--') || 0 === strpos($trimmed, '//')) {
                    continue;
                }

                $lines[] = $line;
            }

            $statement = trim(implode("\n", $lines));

            if ('' !== $statement) {
                $this->db->query($statement, Db::WRITE);
            }
        }
    }

    public function testSchemaInstalls(): void
    {
        $installed = [];

        foreach (['comments', 'contents', 'fields', 'metas', 'options', 'relationships', 'users'] as $table) {
            // a select against every table proves both the table and its columns exist
            $this->db->fetchAll($this->db->select()->from('table.' . $table)->limit(1));
            $installed[] = $table;
        }

        $this->assertSame(
            ['comments', 'contents', 'fields', 'metas', 'options', 'relationships', 'users'],
            $installed
        );
    }

    public function testInsertReturnsTheNewIdAndRoundTripsUtf8(): void
    {
        $cid = $this->db->query($this->db->insert('table.contents')->rows([
            'title' => 'Hello 世界',
            'slug' => 'hello',
            'created' => 1700000000,
            'type' => 'post',
            'status' => 'publish',
            'text' => 'body',
        ]), Db::WRITE, Db::INSERT);

        $this->assertGreaterThan(0, (int) $cid);

        $row = $this->db->fetchRow($this->db->select('cid', 'title')->from('table.contents')
            ->where('cid = ?', $cid));

        $this->assertSame('Hello 世界', $row['title']);
    }

    public function testUpdateAndDeleteReportAffectedRows(): void
    {
        foreach (['a', 'b', 'c'] as $slug) {
            $this->db->query($this->db->insert('table.metas')
                ->rows(['name' => $slug, 'slug' => $slug, 'type' => 'tag', 'count' => 0]), Db::WRITE, Db::INSERT);
        }

        $updated = $this->db->query(
            $this->db->update('table.metas')->rows(['count' => 5])->where('slug IN ?', ['a', 'b']),
            Db::WRITE,
            Db::UPDATE
        );
        $this->assertSame(2, (int) $updated);

        $deleted = $this->db->query(
            $this->db->delete('table.metas')->where('slug = ?', 'c'),
            Db::WRITE,
            Db::DELETE
        );
        $this->assertSame(1, (int) $deleted);
    }

    public function testAggregateAndGrouping(): void
    {
        foreach ([['a', 1], ['a', 4], ['b', 10]] as $i => [$type, $count]) {
            $this->db->query($this->db->insert('table.metas')
                ->rows(['name' => $type . $i, 'slug' => $type . $i, 'type' => $type, 'count' => $count]),
                Db::WRITE, Db::INSERT);
        }

        $total = $this->db->fetchObject(
            $this->db->select(['SUM(count)' => 'total'])->from('table.metas')
        )->total;
        $this->assertEquals(15, $total);

        $rows = $this->db->fetchAll(
            $this->db->select('type', ['SUM(count)' => 'total'])->from('table.metas')
                ->group('type')->order('type')
        );

        $this->assertCount(2, $rows);
        $this->assertEquals(5, $rows[0]['total']);
        $this->assertEquals(10, $rows[1]['total']);
    }

    public function testPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->db->query($this->db->insert('table.metas')
                ->rows(['name' => "n$i", 'slug' => "n$i", 'type' => 'tag', 'count' => $i]),
                Db::WRITE, Db::INSERT);
        }

        $page2 = $this->db->fetchAll(
            $this->db->select('slug')->from('table.metas')->order('count')->page(2, 2)
        );

        $this->assertSame(['n3', 'n4'], array_column($page2, 'slug'));
    }

    public function testBindingNeutralisesInjection(): void
    {
        $this->db->query($this->db->insert('table.metas')
            ->rows(['name' => 'safe', 'slug' => 'safe', 'type' => 'tag']), Db::WRITE, Db::INSERT);

        $payload = "safe' OR '1'='1";

        $this->assertNull($this->db->fetchRow(
            $this->db->select()->from('table.metas')->where('slug = ?', $payload)
        ));

        // the payload must survive as data, not break the statement
        $this->db->query($this->db->insert('table.metas')
            ->rows(['name' => $payload, 'slug' => $payload, 'type' => 'tag']), Db::WRITE, Db::INSERT);

        $row = $this->db->fetchRow($this->db->select('name')->from('table.metas')->where('slug = ?', $payload));
        $this->assertSame($payload, $row['name']);
    }

    public function testJoin(): void
    {
        $cid = $this->db->query($this->db->insert('table.contents')->rows([
            'title' => 'joined', 'slug' => 'joined', 'created' => 1700000000,
            'type' => 'post', 'status' => 'publish', 'text' => 'x',
        ]), Db::WRITE, Db::INSERT);

        $mid = $this->db->query($this->db->insert('table.metas')
            ->rows(['name' => 'tag1', 'slug' => 'tag1', 'type' => 'tag']), Db::WRITE, Db::INSERT);

        $this->db->query($this->db->insert('table.relationships')
            ->rows(['cid' => $cid, 'mid' => $mid]), Db::WRITE, Db::INSERT);

        $row = $this->db->fetchRow(
            $this->db->select('c.title', 'm.name')
                ->from('table.contents c')
                ->join('table.relationships r', 'r.cid = c.cid')
                ->join('table.metas m', 'm.mid = r.mid')
                ->where('c.cid = ?', $cid)
        );

        $this->assertSame('joined', $row['title']);
        $this->assertSame('tag1', $row['name']);
    }

    public function testTruncate(): void
    {
        $this->db->query($this->db->insert('table.metas')
            ->rows(['name' => 'x', 'slug' => 'x', 'type' => 'tag']), Db::WRITE, Db::INSERT);

        $this->db->truncate('table.metas');

        $this->assertSame(0, (int) $this->db->fetchObject(
            $this->db->select(['COUNT(*)' => 'count'])->from('table.metas')
        )->count);
    }

    public function testVersionIsReported(): void
    {
        $this->assertNotSame('', $this->db->getVersion());
    }
}
