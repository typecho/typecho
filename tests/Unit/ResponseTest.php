<?php
use PHPUnit\Framework\TestCase;
use Typecho\Response;
use Typecho\Widget\Terminal;

/**
 * Pins the Response state that the Cookie tests do not reach: header/status/content-type
 * bookkeeping, the sandbox guard on every mutator, the respond() sandbox exit and the
 * expiry normalisation of buildCookie(). sendHeaders() itself needs a real SAPI, so the
 * state is read through Closure::call instead of headers_list().
 */
final class ResponseTest extends TestCase
{
    private const NOW = 1700000000;

    protected function setUp(): void
    {
        $this->resetResponse();
    }

    protected function tearDown(): void
    {
        $this->resetResponse();
    }

    private function resetResponse(): void
    {
        // clean() does not restore the charset, so reset it explicitly first
        $response = Response::getInstance();
        $response->endSandbox();
        $response->setCharset('UTF-8');
        $response->clean();
    }

    private function response(): Response
    {
        return Response::getInstance();
    }

    /** Reads a private property without ReflectionProperty::setAccessible() (deprecated in 8.5) */
    private function state(string $name)
    {
        return (function ($name) {
            return $this->$name;
        })->call($this->response(), $name);
    }

    public function testDefaultsAfterClean(): void
    {
        $this->assertSame(200, $this->state('status'));
        $this->assertSame('UTF-8', $this->response()->getCharset());
        $this->assertSame('text/html', $this->state('contentType'));
        $this->assertSame('text/html; charset=UTF-8', $this->state('headers')['Content-Type']);
    }

    public function testBuildCookieKeepsRecentAbsoluteTimestamps(): void
    {
        // an expiry one hour ago is still an absolute timestamp (delete-on-sight), not a duration
        $cookie = ['k', 'v', self::NOW - 3600, '/', '', false, false, 'Lax'];

        $this->assertSame(self::NOW - 3600, Response::buildCookie($cookie, self::NOW)[2]['expires']);
    }

    public function testBuildCookieTreatsSmallValuesAsDurations(): void
    {
        // 25 hours is far below a Unix timestamp, so it is a duration and gets "now" added
        $cookie = ['k', 'v', 90000, '/', '', false, false, 'Lax'];

        $this->assertSame(self::NOW + 90000, Response::buildCookie($cookie, self::NOW)[2]['expires']);
    }

    public function testBuildCookieNormalisesZeroAndNegativeExpiry(): void
    {
        $this->assertSame(
            0,
            Response::buildCookie(['k', 'v', 0, '/', '', false, false, 'Lax'], self::NOW)[2]['expires']
        );
        $this->assertSame(
            1,
            Response::buildCookie(['k', 'v', -1, '/', '', false, false, 'Lax'], self::NOW)[2]['expires']
        );
    }

    public function testBuildCookieRawUrlEncodesTheValue(): void
    {
        [, $value] = Response::buildCookie(['k', 'a b;c', 0, '/', '', false, false, 'Lax'], self::NOW);

        $this->assertSame('a%20b%3Bc', $value);
    }

    public function testSetHeaderNormalisesTheName(): void
    {
        $this->response()->setHeader('x-custom-header', 'v');

        $this->assertSame('v', $this->state('headers')['X-Custom-Header']);
    }

    public function testSetStatus(): void
    {
        $this->response()->setStatus(404);

        $this->assertSame(404, $this->state('status'));
    }

    public function testSetContentTypeKeepsTheCurrentCharset(): void
    {
        $this->response()->setContentType('application/json');

        $this->assertSame('application/json; charset=UTF-8', $this->state('headers')['Content-Type']);
    }

    public function testSetCharsetRewritesTheContentTypeHeader(): void
    {
        $this->response()->setContentType('text/xml')->setCharset('GBK');

        $this->assertSame('text/xml; charset=GBK', $this->state('headers')['Content-Type']);
        $this->assertSame('GBK', $this->response()->getCharset());
    }

    public function testSandboxBlocksEveryMutator(): void
    {
        $response = $this->response()->beginSandbox();

        try {
            $response->setStatus(500)
                ->setHeader('X-Test', '1')
                ->setContentType('application/json')
                ->setCharset('GBK')
                ->addResponder(function () {
                });

            $this->assertSame(200, $this->state('status'));
            $this->assertArrayNotHasKey('X-Test', $this->state('headers'));
            $this->assertSame('text/html', $this->state('contentType'));
            $this->assertSame('UTF-8', $this->response()->getCharset());
            $this->assertSame([], $this->state('responders'));
        } finally {
            $response->endSandbox();
        }
    }

    public function testRespondInSandboxThrowsTerminal(): void
    {
        $this->response()->beginSandbox();

        try {
            $this->expectException(Terminal::class);
            $this->response()->respond();
        } finally {
            $this->response()->endSandbox();
        }
    }
}