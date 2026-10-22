<?php
use PHPUnit\Framework\TestCase;
use Typecho\Common;

/**
 * Expectations hold whether mime_content_type() sniffs the content or the
 * extension table is used as fallback (ext/fileinfo unavailable).
 */
final class MimeContentTypeTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function fixture(string $ext, string $content): string
    {
        $base = tempnam(sys_get_temp_dir(), 'tc');
        $this->files[] = $base;
        $file = $base . '.' . $ext;
        $this->files[] = $file;
        file_put_contents($file, $content);
        return $file;
    }

    public function testPlainText(): void
    {
        $file = $this->fixture('txt', "hello typecho\n");
        $this->assertSame('text/plain', Common::mimeContentType($file));
    }

    public function testPng(): void
    {
        // 1x1 transparent PNG
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        );
        $file = $this->fixture('png', $png);
        $this->assertSame('image/png', Common::mimeContentType($file));
    }

    public function testAlwaysReturnsNonEmptyString(): void
    {
        $file = $this->fixture('unknownext', "\x00\x01\x02\x03");
        $type = Common::mimeContentType($file);
        $this->assertIsString($type);
        $this->assertNotSame('', $type);
    }
}
