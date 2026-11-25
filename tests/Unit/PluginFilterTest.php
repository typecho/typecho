<?php
use PHPUnit\Framework\TestCase;
use Typecho\Plugin;

/**
 * Pins the plugin callback pipeline: registration through property assignment, weighted
 * ordering, filter()'s value-twice argument contract, call() return semantics and the
 * trigger() signal. Plugin keeps everything in static state, so it is reset around tests.
 */
final class PluginFilterTest extends TestCase
{
    protected function setUp(): void
    {
        self::resetStatics();
        Plugin::init([]);
    }

    protected function tearDown(): void
    {
        self::resetStatics();
        Plugin::init([]);
    }

    private static function resetStatics(): void
    {
        foreach (['instances', 'tmp'] as $name) {
            $property = new \ReflectionProperty(Plugin::class, $name);
            if (PHP_VERSION_ID < 80100) {
                $property->setAccessible(true);
            }
            $property->setValue(null, []);
        }
    }

    public function testFilterReturnsValueWhenNoHandlersAreRegistered(): void
    {
        $plugin = Plugin::factory('UnitFilter');

        $this->assertSame('value', $plugin->filter('missing', 'value'));
    }

    public function testFilterChainsCallbacksAndPassesTheValueTwice(): void
    {
        $plugin = Plugin::factory('UnitFilter');
        $seen = [];

        $plugin->title = function ($value, $suffix, $original) use (&$seen) {
            $seen[] = [$value, $suffix, $original];
            return $value . $suffix;
        };
        $plugin->title = function ($value) {
            return strtoupper($value);
        };

        $this->assertSame('X+A', $plugin->filter('title', 'x', '+a'));
        // each callback receives the current value first and the same value after extra args
        $this->assertSame([['x', '+a', 'x']], $seen);
    }

    public function testWeightedCallbacksRunInAscendingOrder(): void
    {
        $plugin = Plugin::factory('UnitFilter');
        $order = [];

        $plugin->order_20 = function () use (&$order) {
            $order[] = 'heavy';
            return 'heavy';
        };
        $plugin->order_5 = function () use (&$order) {
            $order[] = 'light';
            return 'light';
        };

        $plugin->filter('order', 'x');

        $this->assertSame(['light', 'heavy'], $order);
    }

    public function testEqualWeightsKeepRegistrationOrder(): void
    {
        $plugin = Plugin::factory('UnitFilter');
        $order = [];

        $plugin->sequence = function () use (&$order) {
            $order[] = 'first';
            return 'first';
        };
        $plugin->sequence = function () use (&$order) {
            $order[] = 'second';
            return 'second';
        };

        $plugin->filter('sequence', 'x');

        $this->assertSame(['first', 'second'], $order);
    }

    public function testCallReturnsLastResultAndNullWithoutHandlers(): void
    {
        $plugin = Plugin::factory('UnitFilter');

        $this->assertNull($plugin->call('absent'));

        $plugin->sum = function () {
            return 1;
        };
        $plugin->sum = function () {
            return 2;
        };

        $this->assertSame(2, $plugin->call('sum'));
    }

    public function testTriggerSignalsOnlyHandledComponents(): void
    {
        $plugin = Plugin::factory('UnitFilter');
        $plugin->title = function ($value) {
            return $value;
        };

        $signal = false;
        $plugin->trigger($signal)->filter('title', 'x');
        $this->assertTrue($signal);

        $signal = false;
        $plugin->trigger($signal)->filter('missing', 'x');
        $this->assertFalse($signal);
    }

    public function testHandlersAreScopedToTheirPlugin(): void
    {
        $one = Plugin::factory('UnitFilterOne');
        $two = Plugin::factory('UnitFilterTwo');

        $one->title = function ($value) {
            return $value . '-one';
        };

        $this->assertSame('x-one', $one->filter('title', 'x'));
        $this->assertSame('x', $two->filter('title', 'x'));
    }
}
