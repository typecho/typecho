<?php
use PHPUnit\Framework\TestCase;
use Typecho\Db;
use Typecho\Db\Query;

/**
 * Pins the SQL that Query builds, without touching a database: the builder is driven by an
 * adapter only for quoting and parseSelect(), so a Pdo_SQLite adapter on a temp file is
 * enough. Every assertion compares the final SQL text (with #param: placeholders resolved
 * through prepare()), which is what a wrong quoting or escaping rule would break.
 */
final class QuerySqlTest extends TestCase
{
    private Db $db;
    private array $paths = [];

    protected function setUp(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        $base = tempnam(sys_get_temp_dir(), 'typecho-query-');
        $this->paths[] = $base;
        $this->paths[] = $base . '.db';

        $this->db = new Db('Pdo_SQLite', 'typecho_');
        $this->db->addServer(['file' => $base . '.db'], Db::READ | Db::WRITE);
    }

    protected function tearDown(): void
    {
        $this->db->flushPool();

        foreach ($this->paths as $path) {
            @unlink($path);
        }

        $this->paths = [];
    }

    /** @param Query $query */
    private function sql(Query $query): string
    {
        return $query->prepare((string) $query);
    }

    public function testSelectAllFrom(): void
    {
        $this->assertSame(
            'SELECT *  FROM typecho_contents',
            $this->sql($this->db->select()->from('table.contents'))
        );
    }

    public function testSelectDefaultsToStarWhenCalledWithoutArguments(): void
    {
        $this->assertSame(
            'SELECT *  FROM typecho_metas',
            $this->sql($this->db->select()->from('table.metas'))
        );
    }

    public function testSelectFieldsAreQuotedAndAliasesPreserved(): void
    {
        $this->assertSame(
            'SELECT "cid" , "title" , COUNT("cid") AS "total"  FROM typecho_contents',
            $this->sql($this->db->select('cid', 'title', ['COUNT(cid)' => 'total'])
                ->from('table.contents'))
        );
    }

    public function testKeywordsAreNotQuoted(): void
    {
        $this->assertSame(
            'SELECT DISTINCT "type"  FROM typecho_contents',
            $this->sql($this->db->select('DISTINCT type')->from('table.contents'))
        );
    }

    public function testTablePrefixIsOnlyAppliedToTableDot(): void
    {
        $query = $this->db->select()->from('table.contents');

        $this->assertSame('typecho_contents', $query->getAttribute('table'));
    }

    public function testWhereChainsWithAndAndOr(): void
    {
        $sql = $this->sql($this->db->select()->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->orWhere('authorId = ?', 1));

        $this->assertSame(
            'SELECT *  FROM typecho_contents'
            . ' WHERE  ("type" = \'post\' ) AND ("status" = \'publish\' ) OR ("authorId" = \'1\' )',
            $sql
        );
    }

    public function testWhereWithoutValueIsUsedVerbatim(): void
    {
        $this->assertSame(
            'SELECT *  FROM typecho_contents WHERE  ("type" = \'post\' )',
            $this->sql($this->db->select()->from('table.contents')->where("type = 'post'"))
        );
    }

    public function testWhereWithArrayExpandsToInList(): void
    {
        $this->assertSame(
            'SELECT *  FROM typecho_metas WHERE  ("slug" IN (\'a\',\'b\',\'c\') )',
            $this->sql($this->db->select()->from('table.metas')->where('slug IN ?', ['a', 'b', 'c']))
        );
    }

    public function testWhereQuotesInjectedValues(): void
    {
        $sql = $this->sql($this->db->select()->from('table.contents')
            ->where('title = ?', "it's a 'test'\\"));

        // the value is bound, so quotes inside it are doubled rather than terminating the literal
        $this->assertStringContainsString("'it''s a ''test''\\'", $sql);
    }

    public function testWhereHandlesNull(): void
    {
        $this->assertSame(
            'SELECT *  FROM typecho_contents WHERE  ("parent" IS NULL )',
            $this->sql($this->db->select()->from('table.contents')->where('parent IS ?', null))
        );
    }

    public function testOrderGroupHavingLimitOffset(): void
    {
        $sql = $this->sql($this->db->select('type', ['COUNT(cid)' => 'total'])
            ->from('table.contents')
            ->group('type')
            ->having('COUNT(cid) > ?', 1)
            ->order('type', Db::SORT_DESC)
            ->order('cid')
            ->limit(10)
            ->offset(20));

        $this->assertSame(
            'SELECT "type" , COUNT("cid") AS "total"  FROM typecho_contents'
            . ' GROUP BY "type"  HAVING  (COUNT("cid") > \'1\' )'
            . ' ORDER BY "type"  DESC, "cid"  ASC LIMIT 10 OFFSET 20',
            $sql
        );
    }

    public function testPageDerivesLimitAndOffset(): void
    {
        $query = $this->db->select()->from('table.contents')->page(3, 20);

        $this->assertSame('20', $query->getAttribute('limit'));
        $this->assertSame('40', $query->getAttribute('offset'));
    }

    public function testPageTreatsPageZeroAsOne(): void
    {
        $query = $this->db->select()->from('table.contents')->page(0, 5);

        $this->assertSame('0', $query->getAttribute('offset'));
    }

    public function testJoin(): void
    {
        $sql = $this->sql($this->db->select('c.cid', 'm.name')
            ->from('table.contents')
            ->join('table.relationships r', 'r.cid = c.cid', Db::INNER_JOIN)
            ->where('c.cid = ?', 1));

        $this->assertStringContainsString('FROM typecho_contents INNER JOIN typecho_relationships r ON', $sql);
        $this->assertStringContainsString('WHERE  (c."cid" = \'1\' )', $sql);
    }

    public function testInsertBuildsColumnsAndValues(): void
    {
        $this->assertSame(
            'INSERT INTO typecho_metas("name"  , "slug" ) VALUES (\'n\' , \'s\')',
            $this->sql($this->db->insert('table.metas')->rows(['name' => 'n', 'slug' => 's']))
        );
    }

    public function testRowsKeepNullAsSqlNull(): void
    {
        $this->assertSame(
            'INSERT INTO typecho_metas("name"  , "parent" ) VALUES (\'n\' , NULL)',
            $this->sql($this->db->insert('table.metas')->rows(['name' => 'n', 'parent' => null]))
        );
    }

    public function testExpressionEscapedAndRaw(): void
    {
        $escaped = $this->sql($this->db->update('table.contents')
            ->expression('views', 'views + 1')
            ->where('cid = ?', 1));
        $this->assertStringContainsString('SET "views"  = "views" + 1', $escaped);

        $raw = $this->sql($this->db->update('table.contents')
            ->expression('views', 'views + 1', false)
            ->where('cid = ?', 1));
        $this->assertStringContainsString('SET "views"  = views + 1', $raw);
    }

    public function testUpdateAndDelete(): void
    {
        $this->assertSame(
            'UPDATE typecho_contents SET "title"  = \'x\' WHERE  ("cid" = \'2\' )',
            $this->sql($this->db->update('table.contents')->rows(['title' => 'x'])->where('cid = ?', 2))
        );

        $this->assertSame(
            'DELETE FROM typecho_contents WHERE  ("cid" = \'3\' )',
            $this->sql($this->db->delete('table.contents')->where('cid = ?', 3))
        );
    }

    public function testParamsAreCollectedForEveryQuotedValue(): void
    {
        $query = $this->db->select()->from('table.contents')
            ->where('type = ?', 'post')
            ->where('cid IN ?', [1, 2]);

        $this->assertSame(['post', 1, 2], $query->getParams());

        // prepare() consumes the same list, so a second call stays consistent
        $this->assertSame($this->sql($query), $this->sql($query));
    }

    public function testCleanAttributeResetsASinglePart(): void
    {
        $query = $this->db->select()->from('table.contents')->where('cid = ?', 1);
        $this->assertStringContainsString('WHERE', (string) $query);

        $query->cleanAttribute('where');

        $this->assertSame('', (string) $query->getAttribute('where'));
        $this->assertStringNotContainsString('WHERE', $this->sql($query));
    }

    public function testUnresolvedPlaceholderIsLeftAlone(): void
    {
        $query = $this->db->select()->from('table.contents');

        $this->assertSame('SELECT * FROM #param:99#', $query->prepare('SELECT * FROM #param:99#'));
    }
}
