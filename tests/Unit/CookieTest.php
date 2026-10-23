<?php
use PHPUnit\Framework\TestCase;
use Typecho\Cookie;
use Typecho\Response;

/**
 * setrawcookie() is a no-op under the CLI SAPI (headers_list() stays empty), so these
 * tests assert on Response::buildCookie(), the pure function sendHeaders() feeds into it.
 */
final class CookieTest extends TestCase
{
    private const NOW = 1700000000;

    /** Cookie's static configuration, which setPrefix() / setOptions() leak into later tests */
    private const STATICS = ['prefix', 'path', 'domain', 'secure', 'httponly', 'sameSite'];

    private array $statics = [];

    private static function cookieProperty(string $name): \ReflectionProperty
    {
        $property = new \ReflectionProperty(Cookie::class, $name);
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        return $property;
    }

    protected function setUp(): void
    {
        foreach (self::STATICS as $name) {
            $this->statics[$name] = self::cookieProperty($name)->getValue();
        }

        Response::getInstance()->clean();
        Cookie::setPrefix('https://example.com/blog');
        Cookie::setOptions(['domain' => '', 'secure' => false, 'httponly' => false, 'samesite' => 'Lax']);
    }

    protected function tearDown(): void
    {
        Response::getInstance()->clean();
        $_COOKIE = [];

        foreach ($this->statics as $name => $value) {
            self::cookieProperty($name)->setValue(null, $value);
        }
    }

    /**
     * @return array [name, rawValue, options] for every cookie queued on the Response singleton
     */
    private function queuedCookies(): array
    {
        // Closure::call reads the private queue without ReflectionProperty::setAccessible(), deprecated in 8.5
        $queue = (function () {
            return $this->cookies;
        })->call(Response::getInstance());

        return array_map(function (array $cookie) {
            return Response::buildCookie($cookie, self::NOW);
        }, $queue);
    }

    private function lastCookie(): array
    {
        $cookies = $this->queuedCookies();
        $this->assertNotEmpty($cookies);
        return end($cookies);
    }

    public function testSetDefaultsToSameSiteLax(): void
    {
        Cookie::set('__typecho_uid', '1');

        [$name, $value, $options] = $this->lastCookie();
        $this->assertSame(Cookie::getPrefix() . '__typecho_uid', $name);
        $this->assertSame('1', $value);
        $this->assertSame('Lax', $options['samesite']);
        $this->assertSame('/blog/', $options['path']);
        $this->assertSame('example.com', $options['domain']);
    }

    public function testSessionCookieHasZeroExpires(): void
    {
        Cookie::set('session', 'x');
        $this->assertSame(0, $this->lastCookie()[2]['expires']);
    }

    public function testRelativeExpireIsAddedToNow(): void
    {
        Cookie::set('relative', 'x', 3600);
        $this->assertSame(self::NOW + 3600, $this->lastCookie()[2]['expires']);
    }

    public function testAbsoluteExpireIsKept(): void
    {
        Cookie::set('absolute', 'x', self::NOW + 30 * 86400);
        $this->assertSame(self::NOW + 30 * 86400, $this->lastCookie()[2]['expires']);
    }

    public function testDeleteExpiresInThePast(): void
    {
        $_COOKIE[Cookie::getPrefix() . 'gone'] = 'x';
        Cookie::delete('gone');

        [, $value, $options] = $this->lastCookie();
        $this->assertSame('', $value);
        $this->assertSame(1, $options['expires']);
        $this->assertSame('Lax', $options['samesite']);
        $this->assertArrayNotHasKey(Cookie::getPrefix() . 'gone', $_COOKIE);
    }

    public function testSecureAndHttpOnlyDefaultOff(): void
    {
        Cookie::set('flags', 'x');

        $options = $this->lastCookie()[2];
        $this->assertFalse($options['secure']);
        $this->assertFalse($options['httponly']);
    }

    public function testSecureAndHttpOnlyFromOptions(): void
    {
        Cookie::setOptions(['domain' => '', 'secure' => true, 'httponly' => true]);
        Cookie::set('flags', 'x');

        $options = $this->lastCookie()[2];
        $this->assertTrue($options['secure']);
        $this->assertTrue($options['httponly']);
        $this->assertSame('Lax', $options['samesite']);
    }

    public function testSameSiteOverrides(): void
    {
        Cookie::setOptions(['domain' => '', 'secure' => true, 'httponly' => false, 'samesite' => 'Strict']);
        Cookie::set('global', 'x');
        $this->assertSame('Strict', $this->lastCookie()[2]['samesite']);

        Cookie::set('per-call', 'x', 0, 'None');
        $this->assertSame('None', $this->lastCookie()[2]['samesite']);
    }

    public function testValueIsRawUrlEncoded(): void
    {
        Cookie::set('notice', '["a b;c"]');
        $this->assertSame('%5B%22a%20b%3Bc%22%5D', $this->lastCookie()[1]);
        $this->assertSame('["a b;c"]', Cookie::get('notice'));
    }

    public function testSandboxQueuesNothing(): void
    {
        Response::getInstance()->beginSandbox();
        try {
            Cookie::set('sandboxed', 'x');
        } finally {
            Response::getInstance()->endSandbox();
        }

        $this->assertSame([], $this->queuedCookies());
    }
}
