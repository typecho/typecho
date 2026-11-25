<?php
use PHPUnit\Framework\TestCase;
use Typecho\Request;
use Typecho\Router;
use Typecho\Router\Exception as RouterException;
use Typecho\Widget;
use Typecho\Widget\Exception as WidgetException;

final class RouterMatchStub extends Widget
{
    public static int $executed = 0;

    public function execute()
    {
        self::$executed++;
    }
}

final class RouterNotFoundStub extends Widget
{
    protected function init()
    {
        throw new WidgetException('not found', 404);
    }
}

final class RouterActionStub extends Widget
{
    public static int $calls = 0;

    public function handle()
    {
        self::$calls++;
    }
}

/**
 * Pins Router's dispatch-side contract: route table setup, matching with the 404 fallback
 * across routes, the once guard, action invocation by dispatch() and Router::$current.
 * Router keeps static state and dispatch() reads the Request singleton, so both are reset
 * around every test.
 */
final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetRouter();
        self::setPathInfo(null);
        RouterMatchStub::$executed = 0;
        RouterActionStub::$calls = 0;
    }

    protected function tearDown(): void
    {
        $this->resetRouter();
        self::setPathInfo(null);
    }

    private function resetRouter(): void
    {
        self::staticProperty(Router::class, 'routingTable')->setValue(null, []);
        self::staticProperty(Router::class, 'matched')->setValue(null, false);
        self::staticProperty(Router::class, 'current')->setValue(null, '');
    }

    private static function staticProperty(string $class, string $name): \ReflectionProperty
    {
        $property = new \ReflectionProperty($class, $name);
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        return $property;
    }

    /** dispatch() works off the path info cached in the Request singleton */
    private static function setPathInfo(?string $path): void
    {
        $property = new \ReflectionProperty(Request::class, 'pathInfo');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue(Request::getInstance(), $path);
    }

    public function testSetRoutesParsesAndGetReturnsTheRoute(): void
    {
        Router::setRoutes([
            'post' => ['url' => '/post/[cid:digital]', 'widget' => RouterMatchStub::class . '@get'],
        ]);

        $route = Router::get('post');

        $this->assertIsArray($route);
        $this->assertSame('/post/%s', $route['format']);
        $this->assertSame(['cid'], $route['params']);
        $this->assertSame(1, preg_match($route['regx'], '/post/42'));
        $this->assertSame(0, preg_match($route['regx'], '/post/abc'));
        $this->assertSame(1, preg_match($route['regx'], '/post/42/'));
        $this->assertNull(Router::get('absent'));
    }

    public function testSetRoutesAcceptsAPreparsedTable(): void
    {
        $table = [
            'plain' => ['regx' => '|^/plain[/]?$|', 'format' => '/plain', 'params' => []],
        ];

        Router::setRoutes([$table]);

        $this->assertSame($table['plain'], Router::get('plain'));
    }

    public function testMatchReturnsTheWidgetAndRecordsTheRoute(): void
    {
        Router::setRoutes([
            'post' => ['url' => '/post/[cid:digital]', 'widget' => RouterMatchStub::class . '@match'],
        ]);

        $result = Router::match('/post/42');

        $this->assertInstanceOf(RouterMatchStub::class, $result);
        $this->assertSame('post', Router::$current);
        $this->assertSame(1, RouterMatchStub::$executed);
    }

    public function testMatchFallsBackWhenAWidgetReportsNotFound(): void
    {
        Router::setRoutes([
            'first' => ['url' => '/hit', 'widget' => RouterNotFoundStub::class . '@fallback'],
            'second' => ['url' => '/hit', 'widget' => RouterMatchStub::class . '@fallback'],
        ]);

        $result = Router::match('/hit');

        $this->assertInstanceOf(RouterMatchStub::class, $result);
        $this->assertSame('second', Router::$current);
    }

    public function testMatchReturnsFalseWhenNothingMatches(): void
    {
        Router::setRoutes([
            'post' => ['url' => '/post/[cid:digital]', 'widget' => RouterMatchStub::class . '@none'],
        ]);

        $this->assertFalse(Router::match('/nope'));
    }

    public function testMatchOnceGuardRejectsASecondCall(): void
    {
        Router::setRoutes([]);

        $this->assertFalse(Router::match('/nope'));

        $this->expectException(RouterException::class);
        $this->expectExceptionCode(404);
        Router::match('/nope');
    }

    public function testMatchWithoutOnceGuardAllowsRepeatedCalls(): void
    {
        Router::setRoutes([]);

        $this->assertFalse(Router::match('/nope', null, false));
        $this->assertFalse(Router::match('/nope', null, false));
    }

    public function testDispatchCallsTheRouteAction(): void
    {
        Router::setRoutes([
            'hit' => [
                'url' => '/hit',
                'widget' => RouterActionStub::class . '@dispatch',
                'action' => 'handle',
            ],
        ]);
        self::setPathInfo('/hit');

        Router::dispatch();

        $this->assertSame(1, RouterActionStub::$calls);
        $this->assertSame('hit', Router::$current);
    }

    public function testDispatchFallsBackWhenAWidgetReportsNotFound(): void
    {
        Router::setRoutes([
            'first' => ['url' => '/hit', 'widget' => RouterNotFoundStub::class . '@dispatchFallback'],
            'second' => [
                'url' => '/hit',
                'widget' => RouterActionStub::class . '@dispatchFallback',
                'action' => 'handle',
            ],
        ]);
        self::setPathInfo('/hit');

        Router::dispatch();

        $this->assertSame(1, RouterActionStub::$calls);
    }

    public function testDispatchThrowsWhenNothingMatches(): void
    {
        Router::setRoutes([]);
        self::setPathInfo('/missing');

        $this->expectException(RouterException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage("Path '/missing' not found");
        Router::dispatch();
    }
}
