<?php
use IXR\Message;
use IXR\Request;
use IXR\Server;
use PHPUnit\Framework\TestCase;

/**
 * The content of <string> is kept verbatim: leading/trailing whitespace is data, e.g. the
 * indentation of a Markdown code block at the start of a post body.
 */
final class XmlRpcStringTest extends TestCase
{
    public function testPaddedStringKeepsSurroundingSpaces(): void
    {
        $this->assertSame(['  padded  '], self::parseParams('<string>  padded  </string>'));
    }

    public function testStringKeepsLeadingNewlinesAndIndentation(): void
    {
        $this->assertSame(["\n\n  indented"], self::parseParams("<string>\n\n  indented</string>"));
    }

    public function testPlainStringIsUnchanged(): void
    {
        $this->assertSame(['plain'], self::parseParams('<string>plain</string>'));
    }

    public function testNewPostBodyReachesMethodByteForByte(): void
    {
        $body = "    \$ composer install\n"
            . "    \$ php -S localhost:8000\n"
            . "\n"
            . "Text with <tags> & entities.\n"
            . "\n"
            . "```php\n"
            . "if (\$a) {\n"
            . "\techo 1;\n"
            . "}\n"
            . "```\n"
            . "\n";

        $stub = new XmlRpcNewPostRecorder();
        $server = new Server(['metaWeblog.newPost' => [$stub, 'mwNewPost']]);

        $xml = (new Request('metaWeblog.newPost', [
            1,
            'admin',
            's3cret',
            ['title' => 'Code', 'description' => $body],
            true,
        ]))->getXml();
        $server->handle($xml);

        $this->assertSame($body, $stub->content['description']);
    }

    private static function parseParams(string $value): array
    {
        $message = new Message(
            '<?xml version="1.0"?><methodCall><methodName>test</methodName><params>'
            . '<param><value>' . $value . '</value></param></params></methodCall>'
        );
        self::assertTrue($message->parse());

        return $message->params;
    }
}

/**
 * Same signature as Widget\XmlRpc::mwNewPost(); remembers the post struct it was given.
 */
final class XmlRpcNewPostRecorder
{
    public $content;

    public function mwNewPost(int $blogId, string $userName, string $password, array $content, bool $publish): int
    {
        $this->content = $content;
        return 1;
    }
}
