<?php
use Typecho\Db;

final class SchemaTest extends SqliteTestCase
{
    public function testInstallSchemaCreatesAllTables(): void
    {
        $tables = array_column(
            $this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"),
            'name'
        );
        $this->assertSame(
            ['typecho_comments', 'typecho_contents', 'typecho_fields', 'typecho_metas',
             'typecho_options', 'typecho_relationships', 'typecho_users'],
            $tables
        );
    }

    public function testInsertSelectThroughQueryBuilder(): void
    {
        $cid = $this->db->query($this->db->insert('table.contents')->rows([
            'title' => 'Hello 世界', 'slug' => 'hello', 'created' => 1700000000,
            'type' => 'post', 'status' => 'publish', 'text' => '<!--markdown-->**hi**',
        ]), Db::WRITE, Db::INSERT);

        $this->assertSame(1, (int) $cid);

        $row = $this->db->fetchRow($this->db->select('cid', 'title', 'slug', 'status')
            ->from('table.contents')->where('slug = ?', 'hello'));

        $this->assertSame('Hello 世界', $row['title']);
        $this->assertSame('publish', $row['status']);

        // parameter binding must neutralise injection payloads
        $this->assertNull($this->db->fetchRow($this->db->select()->from('table.contents')
            ->where('slug = ?', "hello' OR '1'='1")));
    }

    public function testUpdateAndDeleteReturnAffectedRows(): void
    {
        foreach (['a', 'b', 'c'] as $slug) {
            $this->db->query($this->db->insert('table.metas')
                ->rows(['name' => $slug, 'slug' => $slug, 'type' => 'tag']), Db::WRITE, Db::INSERT);
        }

        $this->assertSame(2, (int) $this->db->query($this->db->update('table.metas')
            ->rows(['count' => 5])->where('slug IN ?', ['a', 'b']), Db::WRITE, Db::UPDATE));
        $this->assertSame(1, (int) $this->db->query($this->db->delete('table.metas')
            ->where('slug = ?', 'c'), Db::WRITE, Db::DELETE));
        $this->assertSame(10, (int) $this->db->fetchObject($this->db->select(['SUM(count)' => 'total'])
            ->from('table.metas'))->total);
    }
}
