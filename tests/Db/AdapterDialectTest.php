<?php
use PHPUnit\Framework\TestCase;
use Typecho\Db\Adapter;
use Typecho\Db\Adapter\Mysqli;
use Typecho\Db\Adapter\Pdo\Mysql as PdoMysql;
use Typecho\Db\Adapter\Pdo\Pgsql as PdoPgsql;
use Typecho\Db\Adapter\Pdo\SQLite as PdoSQLite;
use Typecho\Db\Adapter\Pgsql;
use Typecho\Db\Adapter\SQLite;

/**
 * Pins the SQL dialect each adapter is responsible for without opening a connection:
 * identifier quoting, driver names and the keyword handling of the shared QueryTrait.
 * QuoteValue() is covered where it does not need a live handle (Mysqli and Pdo quote through
 * the connection, so their escaping is asserted in the server-backed suites instead).
 */
final class AdapterDialectTest extends TestCase
{
    /** @dataProvider mysqlDialects */
    public function testMysqlQuotesIdentifiersWithBackticks(string $class): void
    {
        $adapter = new $class();

        $this->assertSame('mysql', $adapter->getDriver());
        $this->assertSame('`cid`', $adapter->quoteColumn('cid'));
    }

    public function mysqlDialects(): array
    {
        return [
            'mysqli' => [Mysqli::class],
            'pdo_mysql' => [PdoMysql::class],
        ];
    }

    /** @dataProvider sqliteDialects */
    public function testSqliteQuotesIdentifiersWithDoubleQuotes(string $class): void
    {
        $adapter = new $class();

        $this->assertSame('sqlite', $adapter->getDriver());
        $this->assertSame('"cid"', $adapter->quoteColumn('cid'));
    }

    public function sqliteDialects(): array
    {
        return [
            'pdo_sqlite' => [PdoSQLite::class],
            'sqlite3' => [SQLite::class],
        ];
    }

    public function testLegacySqliteEscapesQuotes(): void
    {
        $adapter = new SQLite();

        $this->assertSame("'it''s'", $adapter->quoteValue("it's"));
    }

    /**
     * The Pdo adapter delegates value quoting to the live PDO handle, so calling it before
     * connect() is a programming error rather than a silent empty string.
     */
    public function testPdoQuoteValueRequiresAConnection(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('must not be accessed before initialization');

        (new PdoSQLite())->quoteValue('x');
    }

    /** @dataProvider pgsqlDialects */
    public function testPgsqlQuotesIdentifiersWithDoubleQuotes(string $class): void
    {
        $adapter = new $class();

        $this->assertSame('pgsql', $adapter->getDriver());
        $this->assertSame('"cid"', $adapter->quoteColumn('cid'));
    }

    public function pgsqlDialects(): array
    {
        return [
            'pdo_pgsql' => [PdoPgsql::class],
            'pgsql' => [Pgsql::class],
        ];
    }

    public function testPgsqlEscapesOnlyQuotes(): void
    {
        $adapter = new Pgsql();

        // standard_conforming_strings: backslashes are literal, only ' is doubled
        $this->assertSame("'it''s'", $adapter->quoteValue("it's"));
        $this->assertSame("'a\\b'", $adapter->quoteValue('a\\b'));
    }

    public function testMysqlEscapesQuotesAndBackslashes(): void
    {
        $adapter = new PdoMysql();

        // this one does not need a handle: it is implemented with str_replace
        $this->assertSame("'it''s'", $adapter->quoteValue("it's"));
        $this->assertSame("'a\\\\b'", $adapter->quoteValue('a\\b'));
    }

    public function testNullIsNotQuotedByQuery(): void
    {
        // the legacy adapter quotes without a handle, which keeps this a pure SQL-text test
        $adapter = new SQLite();
        $query = new \Typecho\Db\Query($adapter, 'typecho_');

        $query->insert('table.metas')->rows(['name' => 'n', 'parent' => null]);

        $this->assertStringContainsString('NULL', (string) $query);
        $this->assertStringNotContainsString("'NULL'", (string) $query);
    }

    /**
     * The install SQL ships one schema per adapter, so a missing file or a missing table
     * would only show up at install time.
     *
     * @dataProvider installScripts
     */
    public function testInstallScriptIsPresentAndDefinesTheCoreTables(string $script): void
    {
        $path = __TYPECHO_ROOT_DIR__ . '/install/' . $script;

        $this->assertFileExists($path);

        $sql = file_get_contents($path);
        foreach (['contents', 'comments', 'fields', 'metas', 'options', 'relationships', 'users'] as $table) {
            $this->assertMatchesRegularExpression(
                '/CREATE TABLE[^;]*' . $table . '/i',
                $sql,
                "$script must create the $table table"
            );
        }
    }

    public function installScripts(): array
    {
        return [['Mysql.sql'], ['Pgsql.sql'], ['SQLite.sql']];
    }

    public function testAdapterAvailabilityChecksAreStatic(): void
    {
        // every adapter must answer isAvailable() without being constructed
        foreach ([Mysqli::class, PdoMysql::class, PdoPgsql::class, PdoSQLite::class, Pgsql::class, SQLite::class] as $class) {
            $this->assertTrue(
                is_callable([$class, 'isAvailable']),
                "$class must expose a static isAvailable()"
            );
            $this->assertIsBool($class::isAvailable());
        }
    }

    public function testEveryAdapterImplementsTheInterface(): void
    {
        foreach ([Mysqli::class, PdoMysql::class, PdoPgsql::class, PdoSQLite::class, Pgsql::class, SQLite::class] as $class) {
            $this->assertInstanceOf(Adapter::class, new $class());
        }
    }
}
