<?php
/**
 * MySQL adapter coverage. Skipped unless TYPECHO_TEST_MYSQL_HOST is set, e.g.
 *
 *   docker run -d --rm --name tc-mysql -e MYSQL_ROOT_PASSWORD=typecho \
 *     -e MYSQL_DATABASE=typecho_test -p 13306:3306 mysql:8.0
 *   TYPECHO_TEST_MYSQL_HOST=127.0.0.1 TYPECHO_TEST_MYSQL_PORT=13306 \
 *     tools/phpunit.sh --testsuite db --filter MysqlAdapterTest
 *
 * TYPECHO_TEST_MYSQL_ADAPTER selects Pdo_Mysql (default) or Mysqli, so both MySQL code
 * paths share one set of assertions.
 */
final class MysqlAdapterTest extends ServerDbTestCase
{
    protected function adapterName(): string
    {
        return getenv('TYPECHO_TEST_MYSQL_ADAPTER') ?: 'Pdo_Mysql';
    }

    protected function installScript(): string
    {
        return 'Mysql.sql';
    }

    protected function connection(): ?array
    {
        $host = getenv('TYPECHO_TEST_MYSQL_HOST');

        if (false === $host || '' === $host) {
            return null;
        }

        $config = [
            'host' => $host,
            'port' => (int) (getenv('TYPECHO_TEST_MYSQL_PORT') ?: 3306),
            'database' => getenv('TYPECHO_TEST_MYSQL_DB') ?: 'typecho_test',
            'user' => getenv('TYPECHO_TEST_MYSQL_USER') ?: 'root',
            'password' => getenv('TYPECHO_TEST_MYSQL_PASS') ?: '',
            'charset' => getenv('TYPECHO_TEST_MYSQL_CHARSET') ?: 'utf8mb4',
        ];

        if (!in_array($this->adapterName(), ['Pdo_Mysql', 'Mysqli'], true)) {
            $this->markTestSkipped('unknown mysql adapter ' . $this->adapterName());
        }

        return $config;
    }

    public function testDriverIsMysql(): void
    {
        $this->assertSame('mysql', $this->driver);
        $this->assertSame('`', substr($this->db->getAdapter()->quoteColumn('x'), 0, 1));
    }
}
