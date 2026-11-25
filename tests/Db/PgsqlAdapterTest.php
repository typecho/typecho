<?php
/**
 * PostgreSQL adapter coverage. Skipped unless TYPECHO_TEST_PGSQL_HOST is set, e.g.
 *
 *   docker run -d --rm --name tc-pg -e POSTGRES_PASSWORD=typecho \
 *     -e POSTGRES_DB=typecho_test -p 15432:5432 postgres:16
 *   TYPECHO_TEST_PGSQL_HOST=127.0.0.1 TYPECHO_TEST_PGSQL_PORT=15432 \
 *     tools/phpunit.sh --testsuite db --filter PgsqlAdapterTest
 *
 * TYPECHO_TEST_PGSQL_ADAPTER selects Pdo_Pgsql (default) or Pgsql.
 */
final class PgsqlAdapterTest extends ServerDbTestCase
{
    protected function adapterName(): string
    {
        return getenv('TYPECHO_TEST_PGSQL_ADAPTER') ?: 'Pdo_Pgsql';
    }

    protected function installScript(): string
    {
        return 'Pgsql.sql';
    }

    protected function connection(): ?array
    {
        $host = getenv('TYPECHO_TEST_PGSQL_HOST');

        if (false === $host || '' === $host) {
            return null;
        }

        if (!in_array($this->adapterName(), ['Pdo_Pgsql', 'Pgsql'], true)) {
            $this->markTestSkipped('unknown pgsql adapter ' . $this->adapterName());
        }

        return [
            'host' => $host,
            'port' => (int) (getenv('TYPECHO_TEST_PGSQL_PORT') ?: 5432),
            'database' => getenv('TYPECHO_TEST_PGSQL_DB') ?: 'typecho_test',
            'user' => getenv('TYPECHO_TEST_PGSQL_USER') ?: 'postgres',
            'password' => getenv('TYPECHO_TEST_PGSQL_PASS') ?: '',
            'charset' => getenv('TYPECHO_TEST_PGSQL_CHARSET') ?: 'utf8',
        ];
    }

    public function testDriverIsPgsql(): void
    {
        $this->assertSame('pgsql', $this->driver);
        $this->assertSame('"', substr($this->db->getAdapter()->quoteColumn('x'), 0, 1));
    }
}
