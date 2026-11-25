<?php
use PHPUnit\Framework\TestCase;
use Typecho\Http\Client;
use Typecho\Http\Client\Exception as ClientException;

/**
 * Client::send() re-checks the connected primary IP against the safe-mode pinned address
 * after curl_exec() and discards the response on mismatch (Client.php, the "Unsafe request
 * host" branch) — the last-resort guard against DNS rebinding between validation and
 * connect. A real mismatch cannot be staged without a hostile DNS setup, so these tests
 * stub the curl functions in the Typecho\Http namespace; the stubs live in
 * tests/fixtures/http/curl-stubs.php and are required from setUp(), i.e. they only ever
 * exist inside these isolated child processes.
 *
 * 8.8.8.8 / 2001:4860:4860::8888 are IP literals, so Common::resolveSafeHost() validates
 * them without any DNS lookup and no network traffic happens.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class HttpClientRebindRecheckTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('curl not available');
        }

        $GLOBALS['__http_stub__'] = [
            'exec' => 'OK',
            'info' => [CURLINFO_PRIMARY_IP => '8.8.8.8', CURLINFO_HTTP_CODE => 200],
            'options' => [],
        ];

        require dirname(__DIR__) . '/fixtures/http/curl-stubs.php';
    }

    public function testMatchingPrimaryIpKeepsTheResponse(): void
    {
        $client = new Client();
        $client->setSafeMode()->send('http://8.8.8.8/');

        $this->assertSame(200, $client->getResponseStatus());
        $this->assertSame('OK', $client->getResponseBody());
    }

    public function testMismatchedPrimaryIpDiscardsTheResponse(): void
    {
        $GLOBALS['__http_stub__']['info'][CURLINFO_PRIMARY_IP] = '203.0.113.9';

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Unsafe request host');
        $this->expectExceptionCode(500);

        (new Client())->setSafeMode()->send('http://8.8.8.8/');
    }

    /** the comparison goes through inet_pton(), so textual forms of one address must match */
    public function testIpv6PrimaryIpComparisonIsNormalized(): void
    {
        $GLOBALS['__http_stub__']['info'][CURLINFO_PRIMARY_IP] = '2001:4860:4860:0:0:0:0:8888';

        $client = new Client();
        $client->setSafeMode()->send('http://[2001:4860:4860::8888]/');

        $this->assertSame('OK', $client->getResponseBody());
    }

    /** pinSafeAddress() must reclaim every connection-steering option the caller set */
    public function testSafeModeReclaimsConnectionOptions(): void
    {
        (new Client())->setSafeMode()->setOption(CURLOPT_FOLLOWLOCATION, true)->send('http://8.8.8.8/');

        $options = $GLOBALS['__http_stub__']['options'];

        $this->assertSame(false, $options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(true, $options[CURLOPT_FRESH_CONNECT]);
        $this->assertSame(true, $options[CURLOPT_FORBID_REUSE]);
        $this->assertSame('', $options[CURLOPT_PROXY]);
        $this->assertNull($options[CURLOPT_UNIX_SOCKET_PATH]);
        $this->assertSame([], $options[CURLOPT_CONNECT_TO]);
        /** literal IP: nothing to pin, but the resolve list must still be reset */
        $this->assertSame([], $options[CURLOPT_RESOLVE]);
        $this->assertSame(CURLPROTO_HTTP | CURLPROTO_HTTPS, $options[CURLOPT_PROTOCOLS]);
        $this->assertSame(80, $options[CURLOPT_PORT]);

        if (defined('CURLOPT_PRE_PROXY')) {
            $this->assertSame('', $options[CURLOPT_PRE_PROXY]);
        }
    }

    /** transport failures surface the curl error message, wrapped as a server error */
    public function testTransportFailureWrapsCurlError(): void
    {
        $GLOBALS['__http_stub__']['exec'] = false;
        $GLOBALS['__http_stub__']['error'] = 'Connection refused';

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Connection refused');
        $this->expectExceptionCode(500);

        (new Client())->send('http://127.0.0.1:1/');
    }
}
