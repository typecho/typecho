<?php
use PHPUnit\Framework\TestCase;
use Typecho\Router;
use Typecho\Router\ParamsDelegateInterface;

final class RouterUrlDelegateStub implements ParamsDelegateInterface
{
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getRouterParam(string $key): string
    {
        return (string) ($this->values[$key] ?? '');
    }
}

/**
 * Pins Router::url(): filling route placeholders from arrays or ParamsDelegateInterface
 * objects, keeping unresolved placeholders, prefix handling and unknown route names.
 */
final class RouterUrlTest extends TestCase
{
    protected function setUp(): void
    {
        self::resetRoutingTable();
    }

    protected function tearDown(): void
    {
        self::resetRoutingTable();
    }

    private static function resetRoutingTable(): void
    {
        $property = new \ReflectionProperty(Router::class, 'routingTable');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue(null, []);
    }

    private function registerRoutes(): void
    {
        Router::setRoutes([
            'post' => ['url' => '/post/[cid:digital]', 'widget' => 'Widget\Contents\Post'],
            'archive' => ['url' => '/[year:digital:4]/[month:digital:2]/', 'widget' => 'Widget\Archive'],
            'about' => ['url' => '/about', 'widget' => 'Widget\Page'],
        ]);
    }

    public function testFillsSingleParamFromArray(): void
    {
        $this->registerRoutes();

        $this->assertSame('/post/42', Router::url('post', ['cid' => 42]));
    }

    public function testAppliesPrefix(): void
    {
        $this->registerRoutes();

        $this->assertSame(
            'https://example.com/blog/post/42',
            Router::url('post', ['cid' => 42], 'https://example.com/blog')
        );
    }

    public function testDelegatesToParamsDelegate(): void
    {
        $this->registerRoutes();

        $this->assertSame('/post/7', Router::url('post', new RouterUrlDelegateStub(['cid' => 7])));
    }

    public function testKeepsPlaceholdersForMissingValues(): void
    {
        $this->registerRoutes();

        $this->assertSame('/post/{cid}', Router::url('post'));
        $this->assertSame('/2026/{month}/', Router::url('archive', ['year' => 2026]));
    }

    public function testFillsAllParams(): void
    {
        $this->registerRoutes();

        $this->assertSame('/2026/11/', Router::url('archive', ['year' => 2026, 'month' => 11]));
    }

    public function testRouteWithoutParams(): void
    {
        $this->registerRoutes();

        $this->assertSame('/about', Router::url('about'));
    }

    public function testUnknownRouteReturnsHash(): void
    {
        $this->registerRoutes();

        $this->assertSame('#', Router::url('absent'));
    }
}
