<?php
use PHPUnit\Framework\TestCase;
use Typecho\Router\Parser;

final class RouterParserTest extends TestCase
{
    public function testParsesTypedPlaceholders(): void
    {
        $parsed = (new Parser([
            'post' => ['url' => '/archives/[cid:digital]/', 'widget' => 'Widget\Archive', 'action' => 'render'],
            'page' => ['url' => '/[slug].html', 'widget' => 'Widget\Archive', 'action' => 'render'],
        ]))->parse();

        $this->assertSame('|^/archives/([0-9]+)[/]?$|', $parsed['post']['regx']);
        $this->assertSame(['cid'], $parsed['post']['params']);
        $this->assertSame('/archives/%s/', $parsed['post']['format']);
        $this->assertSame(1, preg_match($parsed['post']['regx'], '/archives/123/'));
        $this->assertSame(0, preg_match($parsed['post']['regx'], '/archives/abc/'));
        $this->assertSame(1, preg_match($parsed['page']['regx'], '/about.html', $m));
        $this->assertSame('about', $m[1]);
    }
}
