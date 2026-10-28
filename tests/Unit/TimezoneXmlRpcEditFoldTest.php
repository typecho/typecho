<?php

use PHPUnit\Framework\TestCase;
use Typecho\Db;
use Typecho\Timezone;
use Widget\Options;
use Widget\XmlRpc;

require_once __DIR__ . '/TimezonePublishPathTest.php';

/**
 * 真实XML-RPC方法、生产Post/Edit与临时SQLite的集成回归。
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class TimezoneXmlRpcEditFoldTest extends TestCase
{
    private Db $db;
    private int $uid;
    private string $serverZone;

    private function fixture(string $method): void
    {
        $reflection = new ReflectionMethod(TimezonePublishPathTest::class, $method);
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }
        $reflection->invoke(null);
    }

    protected function setUp(): void
    {
        $this->serverZone = date_default_timezone_get();
        $this->fixture('setUpFixture');
        $this->db = Db::get();
        $this->uid = $this->db->query($this->db->insert('table.users')->rows([
            'name'=>'xmlrpc-fold-user', 'password'=>'unused', 'screenName'=>'Offline',
            'mail'=>'offline@example.invalid', 'url'=>'', 'group'=>'administrator',
            'created'=>1, 'activated'=>1, 'logged'=>1, 'authCode'=>'offline',
        ]));
        $this->assertTrue(Widget\User::alloc()->simpleLogin($this->uid));
        $this->db->query($this->db->insert('table.metas')->rows([
            'name'=>'Offline category', 'slug'=>'offline-category', 'type'=>'category',
            'description'=>'', 'count'=>0, 'parent'=>0,
        ]));
    }

    protected function tearDown(): void
    {
        $this->fixture('tearDownFixture');
        Typecho\Widget::destroy();
        Typecho\Request::getInstance()->endProxy();
        date_default_timezone_set($this->serverZone);
        // PHPUnit 会把子进程测试对象序列化回父进程，实例不能残留含PDO的Db。
        unset($this->db);
    }

    private function site(string $server, string $zone): void
    {
        date_default_timezone_set($server);
        $this->db->query($this->db->update('table.options')->rows(['value'=>$zone])
            ->where('name = ? AND user = ?', 'timezone', 0));
        Options::destroy();
        // User可能已持有Options服务；同步真实公开setter，避免测试仅改DB而读旧实例。
        $options = Options::alloc();
        $options->timezone = $zone;
        $this->assertSame($zone, $options->timezoneName);
    }

    private function insertPost(int $created): int
    {
        return $this->db->query($this->db->insert('table.contents')->rows([
            'title'=>'Fold article', 'slug'=>'fold-article', 'created'=>$created, 'modified'=>$created,
            'text'=>'Original body', 'authorId'=>$this->uid, 'type'=>'post', 'status'=>'publish',
            'allowComment'=>1, 'allowPing'=>0, 'allowFeed'=>1, 'parent'=>0,
        ]));
    }

    private function readPost(int $cid): array
    {
        return $this->db->fetchRow($this->db->select()->from('table.contents')->where('cid = ?', $cid));
    }

    private function editPost(int $cid, string $literal, array $extra = []): array
    {
        $result = XmlRpc::alloc(null, null, false)->mwEditPost($cid, 'xmlrpc-fold-user', 'unused',
            $extra + ['title'=>'Fold article', 'description'=>'Body updated through XML-RPC',
                'dateCreated'=>new IXR\Date($literal), 'mt_allow_pings'=>0,
                'categories'=>['Offline category'], 'mt_keywords'=>''], true);
        $this->assertSame($cid, $result);
        $row = $this->readPost($cid);
        $this->assertSame('Body updated through XML-RPC', $row['text']);
        return $row;
    }

    public static function foldCases(): array
    {
        return [['UTC', 1636273845], ['UTC', 1636277445],
            ['Europe/Berlin', 1636273845], ['Europe/Berlin', 1636277445]];
    }

    /** @dataProvider foldCases */
    public function testUnchangedLocalDatePreservesEitherOriginalFoldAndSeconds(string $server, int $created): void
    {
        $this->site($server, 'America/Los_Angeles');
        $cid = $this->insertPost($created);
        // 与mwGetPost的真实序列化表达式相同，但原值来自SQLite而非手写墙钟。
        $stored = $this->readPost($cid);
        $literal = (new IXR\Date((int) $stored['created']
            + Options::alloc()->timezoneOffsetAt((int) $stored['created'])))->getIso();
        $this->assertSame('20211107T01:30:45', $literal);
        $row = $this->editPost($cid, $literal);
        $this->assertSame($created, (int) $row['created']);
    }

    public static function servers(): array { return [['UTC'], ['Europe/Berlin']]; }

    /** @dataProvider servers */
    public function testExplicitLocalChangeStillChangesTheInstantAndDoesNotAddGmtPriority(string $server): void
    {
        $this->site($server, 'America/Los_Angeles');
        $cid = $this->insertPost(1636277445);
        $row = $this->editPost($cid, '20211107T03:30:45', ['date_created_gmt'=>new IXR\Date(1636277445)]);
        $this->assertSame(strtotime('2021-11-07 11:30:45 UTC'), (int) $row['created']);
    }

    /** @dataProvider servers */
    public function testNewLocalPostKeepsTheExistingCreationPath(string $server): void
    {
        $this->site($server, 'America/Los_Angeles');
        $cid = XmlRpc::alloc(null, null, false)->mwNewPost(1, 'xmlrpc-fold-user', 'unused',
            ['title'=>'New article', 'description'=>'New body', 'dateCreated'=>new IXR\Date('20211107T03:30:45'),
                'mt_allow_pings'=>0, 'categories'=>['Offline category'], 'mt_keywords'=>''], true);
        $row = $this->readPost($cid);
        $this->assertSame(strtotime('2021-11-07 11:30:45 UTC'), (int) $row['created']);
        $this->assertSame('New body', $row['text']);
    }

    public static function numericCases(): array
    {
        return [['UTC', '28800'], ['Europe/Berlin', '28800'], ['UTC', '12345'], ['Europe/Berlin', '12345']];
    }

    /** @dataProvider numericCases */
    public function testNumericSiteEditsKeepTheirFixedOffsetContract(string $server, string $zone): void
    {
        $this->site($server, $zone);
        $created = strtotime('2021-07-04 12:00:45 UTC');
        $cid = $this->insertPost($created);
        $literal = (new IXR\Date($created + (int) $zone))->getIso();
        $row = $this->editPost($cid, $literal);
        $this->assertSame($created, (int) $row['created']);
    }
}
