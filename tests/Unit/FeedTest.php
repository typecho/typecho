<?php
use PHPUnit\Framework\TestCase;
use Typecho\Feed;

final class FeedTest extends TestCase
{
    private const HTML = "<p>Hello <strong>world</strong> &amp; <a href=\"https://example.com/?a=1&amp;b=2\">link</a></p>\n"
        . "<pre><code>if (a &lt; b) { return; }</code></pre>";

    public function formatProvider(): array
    {
        return [
            'rss1'  => [Feed::RSS1],
            'rss2'  => [Feed::RSS2],
            'atom1' => [Feed::ATOM1],
        ];
    }

    public function payloadProvider(): array
    {
        $payloads = [
            'middle'      => 'before ]]> after',
            'trailing'    => 'ends with ]]>',
            'consecutive' => 'a]]]]>b ]]>]]> c',
        ];

        $cases = [];
        foreach ($this->formatProvider() as $format => [$type]) {
            foreach ($payloads as $name => $payload) {
                $cases[$format . ':' . $name] = [$type, $payload];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider payloadProvider
     */
    public function testCdataTerminatorInFieldsStaysWellFormed(string $type, string $payload): void
    {
        $xml = $this->render($type, [
            'title'    => 'Title ' . $payload,
            'content'  => '<p>' . $payload . '</p>',
            'excerpt'  => 'Excerpt ' . $payload,
            'category' => [['name' => 'Cat ' . $payload, 'permalink' => 'https://example.com/category/c']],
        ]);

        $xpath = $this->parse($xml);

        switch ($type) {
            case Feed::RSS1:
                $xpath->registerNamespace('r', 'http://purl.org/rss/1.0/');
                $this->assertSame($payload, $this->text($xpath, '//r:item/r:description'));
                break;
            case Feed::RSS2:
                $this->assertSame("\n<p>" . $payload . "</p>\n", $this->text($xpath, '//item/content:encoded'));
                $this->assertSame('Excerpt ' . $payload, $this->text($xpath, '//item/description'));
                $this->assertSame('Cat ' . $payload, $this->text($xpath, '//item/category'));
                break;
            case Feed::ATOM1:
                $xpath->registerNamespace('a', 'http://www.w3.org/2005/Atom');
                $this->assertSame("\n<p>" . $payload . "</p>\n", $this->text($xpath, '//a:entry/a:content'));
                $this->assertSame('Title ' . $payload, $this->text($xpath, '//a:entry/a:title'));
                $this->assertSame(
                    htmlspecialchars('Excerpt ' . $payload),
                    $this->text($xpath, '//a:entry/a:summary')
                );
                break;
        }
    }

    public function testTerminatorIsSplitAcrossCdataSections(): void
    {
        $xml = $this->render(Feed::RSS2, ['content' => 'x]]]]>y']);

        $this->assertStringContainsString(
            "<content:encoded xml:lang=\"en\"><![CDATA[\nx]]]]]]><![CDATA[>y\n]]></content:encoded>",
            $xml
        );
    }

    /**
     * @dataProvider formatProvider
     */
    public function testOrdinaryHtmlIsUnchanged(string $type): void
    {
        $xml = $this->render($type, ['content' => self::HTML, 'excerpt' => self::HTML]);
        $xpath = $this->parse($xml);

        switch ($type) {
            case Feed::RSS1:
                $this->assertStringContainsString(
                    '<description>' . strip_tags(self::HTML) . '</description>',
                    $xml
                );
                break;
            case Feed::RSS2:
                $this->assertStringContainsString(
                    '<content:encoded xml:lang="en"><![CDATA[' . "\n" . self::HTML . "\n" . ']]></content:encoded>',
                    $xml
                );
                $this->assertStringContainsString(
                    '<description><![CDATA[' . strip_tags(self::HTML) . ']]></description>',
                    $xml
                );
                $this->assertSame("\n" . self::HTML . "\n", $this->text($xpath, '//item/content:encoded'));
                break;
            case Feed::ATOM1:
                $this->assertStringContainsString(
                    '<content type="html" xml:base="https://example.com/archives/1/" xml:lang="en"><![CDATA['
                        . "\n" . self::HTML . "\n" . ']]></content>',
                    $xml
                );
                $this->assertStringContainsString(
                    '<summary type="html"><![CDATA[' . htmlspecialchars(self::HTML) . ']]></summary>',
                    $xml
                );
                break;
        }
    }

    private function render(string $type, array $item): string
    {
        $feed = new Feed('1.3.0', $type, 'UTF-8', 'en');
        $feed->setTitle('Site');
        $feed->setSubTitle('Sub');
        $feed->setFeedUrl('https://example.com/feed/');
        $feed->setBaseUrl('https://example.com/');

        $feed->addItem($item + [
            'title'    => 'Post',
            'content'  => '',
            'excerpt'  => '',
            'date'     => 1700000000,
            'link'     => 'https://example.com/archives/1/',
            'author'   => (object) ['screenName' => 'admin', 'url' => 'https://example.com/'],
            'comments' => '3',
        ]);

        return (string) $feed;
    }

    private function parse(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertTrue($loaded, "Feed is not well-formed XML:\n" . implode('', array_map(
            static fn($e) => trim($e->message) . "\n",
            $errors
        )) . $xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('content', 'http://purl.org/rss/1.0/modules/content/');

        return $xpath;
    }

    private function text(DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        $this->assertSame(1, $nodes->length, $query);

        return $nodes->item(0)->textContent;
    }
}
