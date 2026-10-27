<?php
use Typecho\Config;
use Typecho\Db;
use Typecho\Plugin;
use Typecho\Request;
use Typecho\Response;
use Typecho\Widget;
use Widget\Feedback;
use Widget\Options;

/**
 * Widget\Feedback::trackback() against a real SQLite comments table: the excerpt is cleaned the
 * same way as a pingback before it is stored.
 */
final class TrackbackTest extends SqliteTestCase
{
    private const CID = 1;

    private array $server;

    protected function setUp(): void
    {
        parent::setUp();
        Widget::destroy();
        Plugin::init([]);

        // a parameterised Options widget would push an empty row over its parameters, so seed the table
        foreach (['theme' => 'default', 'plugins' => 'a:0:{}', 'secret' => 'test-secret', 'commentsRequireModeration' => 0] as $name => $value) {
            $this->db->query($this->db->insert('table.options')->rows(['name' => $name, 'user' => 0, 'value' => $value]));
        }
        Options::alloc();

        $this->server = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
        $_SERVER['HTTP_USER_AGENT'] = 'TrackbackTest/1.0';
        unset($_SERVER['HTTP_REFERER']);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        Widget::destroy();
        Plugin::init([]);
        parent::tearDown();
    }

    public function testScriptTagsAreStripped(): void
    {
        $text = $this->trackback('<script>alert(1)</script>Nice post <img src=x onerror=alert(2)> indeed');

        $this->assertStringNotContainsStringIgnoringCase('<script', $text);
        $this->assertStringNotContainsStringIgnoringCase('<img', $text);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $text);
        $this->assertStringContainsString('Nice post', $text);
    }

    public function testPlainTextIsKept(): void
    {
        $this->assertSame('A plain & friendly excerpt', $this->trackback('  A plain & friendly excerpt  '));
    }

    public function testLongTextIsCutTo150CharactersIncludingEllipsis(): void
    {
        $this->assertSame(str_repeat('字', 147) . '...', $this->trackback(str_repeat('字', 300)));
    }

    /**
     * Send a trackback with the given excerpt and return the text that was stored.
     */
    private function trackback(string $excerpt): string
    {
        $request = Request::getInstance();
        $params = new Config([
            'blog_name' => 'Other Blog',
            'url' => 'https://example.com/post-' . uniqid(),
            'excerpt' => $excerpt,
        ]);
        $feedback = new Feedback(new Widget\Request($request, $params), new Widget\Response($request, Response::getInstance()));

        // Closure::call reaches the private content and trackback() without setAccessible(), deprecated in 8.5
        $send = function (object $content) {
            $this->content = $content;
            $this->trackback();
        };

        // sandboxed, the closing throwXml() ends with Terminal instead of exit
        Response::getInstance()->beginSandbox();
        try {
            ob_start();
            // what Router::match() would have found for the permalink
            $send->call($feedback, (object) [
                'cid' => self::CID,
                'permalink' => 'https://blog.example.com/archives/1/',
                'author' => (object) ['uid' => 1],
            ]);
            $this->fail('trackback() should have responded');
        } catch (Widget\Terminal $e) {
            // responded
        } finally {
            ob_end_clean();
            Response::getInstance()->endSandbox();
        }

        $row = $this->db->fetchObject($this->db->select('text')->from('table.comments')
            ->where('cid = ? AND type = ?', self::CID, 'trackback')->order('coid', Db::SORT_DESC)->limit(1));
        $this->assertNotNull($row, 'trackback was not stored');

        return $row->text;
    }
}
