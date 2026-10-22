<?php
use PHPUnit\Framework\TestCase;
use Typecho\Http\Client;
use Typecho\Http\Client\Exception as ClientException;

/** Regressions for 96ccfdd / 16bee3f / 51fa580: safe-mode client must refuse internal targets before connecting */
final class SafeHttpClientTest extends TestCase
{
    /** @dataProvider internalUrls */
    public function testSafeModeRefusesInternalTarget(string $url): void
    {
        $client = Client::get();
        if (null === $client) {
            $this->markTestSkipped('curl not available');
        }

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(403);
        $client->setSafeMode()->setTimeout(2)->send($url);
    }

    public function internalUrls(): array
    {
        return [
            ['http://127.0.0.1:1/'],
            ['http://169.254.169.254/latest/meta-data/'],
            ['http://[::1]/'],
            ['http://localhost/'],
            ['http://2130706433/'],
        ];
    }

    public function testCallerCannotRepinConnectionViaCurlOption(): void
    {
        $client = Client::get();
        if (null === $client || !defined('CURLOPT_ALTSVC')) {
            $this->markTestSkipped('curl without alt-svc');
        }

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Unsafe request option');
        $client->setSafeMode()->setOption(CURLOPT_ALTSVC, '/tmp/altsvc')->setTimeout(2)->send('http://8.8.8.8/');
    }
}
