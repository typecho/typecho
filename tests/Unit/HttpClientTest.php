<?php
use PHPUnit\Framework\TestCase;
use Typecho\Http\Client;
use Typecho\Http\Client\Exception as ClientException;

/**
 * Happy-path coverage for Client against a real HTTP server: PHP's built-in web server on
 * 127.0.0.1, started once per class with tests/fixtures/http/router.php as router.
 * Safe mode refuses loopback targets by design, so these tests exercise the plain client;
 * the safe-mode pinning is covered separately in tests/Security.
 */
final class HttpClientTest extends TestCase
{
    /** @var resource|null */
    private static $serverProcess;

    private static ?string $baseUrl = null;

    public static function setUpBeforeClass(): void
    {
        self::$serverProcess = null;
        self::$baseUrl = null;

        if (!extension_loaded('curl')) {
            return;
        }

        $router = dirname(__DIR__) . '/fixtures/http/router.php';

        for ($attempt = 0; $attempt < 5 && null === self::$serverProcess; $attempt++) {
            $port = random_int(20000, 60000);
            $process = proc_open(
                [PHP_BINARY, '-S', '127.0.0.1:' . $port, $router],
                [['file', '/dev/null', 'r'], ['file', '/dev/null', 'a'], ['file', '/dev/null', 'a']],
                $pipes
            );

            if (!is_resource($process)) {
                continue;
            }

            if (self::waitForServer($process, $port)) {
                self::$serverProcess = $process;
                self::$baseUrl = 'http://127.0.0.1:' . $port;
            } else {
                proc_terminate($process);
                proc_close($process);
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
        }

        self::$serverProcess = null;
        self::$baseUrl = null;
    }

    /**
     * @param resource $process
     */
    private static function waitForServer($process, int $port): bool
    {
        $deadline = microtime(true) + 5.0;

        while (microtime(true) < $deadline) {
            $status = proc_get_status($process);

            if (!$status['running']) {
                return false;
            }

            $connection = @fsockopen('127.0.0.1', $port, $errno, $error, 0.2);

            if (is_resource($connection)) {
                fclose($connection);
                return true;
            }

            usleep(50000);
        }

        return false;
    }

    protected function setUp(): void
    {
        if (null === self::$baseUrl) {
            $this->markTestSkipped('curl missing or the built-in web server failed to start');
        }
    }

    private function client(): Client
    {
        /** @var Client $client */
        $client = Client::get();
        /** proxy env vars of the host running the suite must not hijack loopback requests */
        $client->setOption(CURLOPT_PROXY, '');
        return $client;
    }

    /** Sends $path through the client and returns the router's decoded JSON report */
    private function request(Client $client, string $path): array
    {
        $client->send(self::$baseUrl . $path);

        $body = json_decode($client->getResponseBody(), true);
        $this->assertIsArray($body, 'router did not answer with JSON: ' . $client->getResponseBody());
        return $body;
    }

    public function testGetMergesUrlAndClientQueries(): void
    {
        $body = $this->request(
            $this->client()->setQuery(['b' => '2'])->setQuery('c=3'),
            '/some/path?a=1'
        );

        $this->assertSame('/some/path', parse_url($body['uri'], PHP_URL_PATH));
        $this->assertSame(['a' => '1', 'b' => '2', 'c' => '3'], $body['query']);
    }

    public function testResponseAccessors(): void
    {
        $client = $this->client();
        $this->request($client, '/ok');

        $this->assertSame(200, $client->getResponseStatus());
        $this->assertSame('typecho-http-fixture', $client->getResponseHeader('X-Router'));
        $this->assertSame('typecho-http-fixture', $client->getResponseHeader('x-router'));
        $this->assertNull($client->getResponseHeader('x-absent'));
    }

    public function testHttpErrorStatusDoesNotThrow(): void
    {
        $client = $this->client();
        $this->request($client, '/status/404');

        $this->assertSame(404, $client->getResponseStatus());
    }

    public function testRedirectsAreNotFollowed(): void
    {
        $client = $this->client();
        $client->send(self::$baseUrl . '/redirect');

        $this->assertSame(302, $client->getResponseStatus());
        $this->assertSame('/target', $client->getResponseHeader('location'));
    }

    public function testPostDefaultsToMultipartFormData(): void
    {
        $body = $this->request(
            $this->client()->setData(['title' => 'héllo & <b>', 'page' => '2']),
            '/'
        );

        $this->assertSame('POST', $body['method']);
        $this->assertStringContainsString('multipart/form-data', $body['headers']['content-type']);
        $this->assertSame(['title' => 'héllo & <b>', 'page' => '2'], $body['post']);
    }

    public function testPostUrlencodedWhenMultipartIsOff(): void
    {
        $body = $this->request(
            $this->client()->setData(['a' => '1', 'b' => 'x y'])->setMultipart(false),
            '/'
        );

        $this->assertSame('POST', $body['method']);
        $this->assertStringContainsString('application/x-www-form-urlencoded', $body['headers']['content-type']);
        $this->assertSame(['a' => '1', 'b' => 'x y'], $body['post']);
        $this->assertSame('a=1&b=x+y', $body['raw']);
    }

    public function testSetJsonSendsJsonBody(): void
    {
        $body = $this->request($this->client()->setJson(['k' => 'v']), '/');

        $this->assertSame('POST', $body['method']);
        $this->assertSame('application/json', $body['headers']['content-type']);
        $this->assertSame('{"k":"v"}', $body['raw']);
    }

    public function testCustomMethodAndRawBody(): void
    {
        $body = $this->request(
            $this->client()->setData('put-payload', Client::METHOD_PUT),
            '/'
        );

        $this->assertSame('PUT', $body['method']);
        $this->assertSame('put-payload', $body['raw']);
    }

    public function testDeleteMethod(): void
    {
        $body = $this->request(
            $this->client()->setMethod(Client::METHOD_DELETE),
            '/item/42'
        );

        $this->assertSame('DELETE', $body['method']);
        $this->assertSame('/item/42', $body['uri']);
    }

    public function testHeadersAgentAndCookiesReachTheServer(): void
    {
        $body = $this->request(
            $this->client()
                ->setHeader('x-test-header', 'Yes')
                ->setAgent('TypechoTest/1.0')
                ->setCookie('a', '1')
                ->setCookie('b', 'x y'),
            '/'
        );

        $this->assertSame('Yes', $body['headers']['x-test-header']);
        $this->assertSame('TypechoTest/1.0', $body['headers']['user-agent']);
        $this->assertSame('a=1; b=x+y', $body['headers']['cookie']);
    }

    public function testUserAgentHeaderOverridesAgent(): void
    {
        $body = $this->request(
            $this->client()->setHeader('User-Agent', 'ViaHeader/2.0'),
            '/'
        );

        $this->assertSame('ViaHeader/2.0', $body['headers']['user-agent']);
    }

    public function testFileUpload(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'typecho-upload-');
        file_put_contents($file, 'upload-payload');

        try {
            $body = $this->request(
                $this->client()->setData(['field' => 'value'])->setFiles(['attachment' => $file]),
                '/'
            );

            $this->assertSame('value', $body['post']['field']);
            $this->assertSame('upload-payload', $body['files']['attachment']['content']);
            $this->assertSame(basename($file), $body['files']['attachment']['name']);
        } finally {
            @unlink($file);
        }
    }

    public function testTimeoutRaisesServerError(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(500);
        $this->client()->setTimeout(1)->send(self::$baseUrl . '/slow');
    }

    public function testInvalidUrlIsRejected(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(400);
        $this->client()->send('http:///no-host');
    }

    /** @dataProvider unsupportedSchemes */
    public function testUnsupportedSchemeIsRejected(string $url): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(400);
        $this->client()->send($url);
    }

    public function unsupportedSchemes(): array
    {
        return [
            ['ftp://example.com/file'],
            ['gopher://example.com/'],
            ['file:///etc/passwd'],
        ];
    }
}
