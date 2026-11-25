<?php
use PHPUnit\Framework\TestCase;
use Typecho\Plugin;

/**
 * Pins the plugin registry lifecycle: init defaults, activation bookkeeping (the handle
 * list a plugin contributed), deactivation removing only that plugin's callbacks, and
 * exists()/export() reflecting the state. Static state is reset around tests.
 */
final class PluginRegistryTest extends TestCase
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

    public function testInitFillsMissingSections(): void
    {
        Plugin::init([]);

        $export = Plugin::export();

        $this->assertSame([], $export['activated']);
        $this->assertSame([], $export['handles']);
    }

    public function testInitKeepsProvidedSections(): void
    {
        $activated = ['Existing' => ['handles' => []]];

        Plugin::init(['activated' => $activated, 'handles' => ['Existing:title' => []]]);

        $this->assertTrue(Plugin::exists('Existing'));
        $this->assertArrayHasKey('Existing:title', Plugin::export()['handles']);
    }

    public function testActivateStoresTheHandlesThePluginContributed(): void
    {
        $plugin = Plugin::factory('UnitRegistry');
        $plugin->title = function ($value) {
            return $value . '!';
        };

        Plugin::activate('UnitRegistry');

        $this->assertTrue(Plugin::exists('UnitRegistry'));

        $activated = Plugin::export()['activated']['UnitRegistry'];
        $this->assertArrayHasKey('UnitRegistry:title', $activated['handles']);
        $this->assertCount(1, $activated['handles']['UnitRegistry:title']);
    }

    public function testDeactivateRemovesOnlyThatPluginsHandlers(): void
    {
        $one = Plugin::factory('UnitOne');
        $one->title = function ($value) {
            return $value . '-one';
        };
        Plugin::activate('UnitOne');

        $two = Plugin::factory('UnitTwo');
        $two->title = function ($value) {
            return $value . '-two';
        };
        Plugin::activate('UnitTwo');

        Plugin::deactivate('UnitOne');

        $export = Plugin::export();
        $this->assertFalse(Plugin::exists('UnitOne'));
        $this->assertTrue(Plugin::exists('UnitTwo'));
        $this->assertArrayNotHasKey('UnitOne:title', $export['handles']);
        $this->assertArrayHasKey('UnitTwo:title', $export['handles']);
        $this->assertSame('x-two', $two->filter('title', 'x'));
        $this->assertSame('x', $one->filter('title', 'x'));
    }

    public function testDeactivateRemovesEveryCallbackAndThenTheKey(): void
    {
        $plugin = Plugin::factory('UnitSolo');
        $plugin->title = function ($value) {
            return $value . '-a';
        };
        $plugin->title = function ($value) {
            return $value . '-b';
        };
        Plugin::activate('UnitSolo');
        $this->assertCount(2, Plugin::export()['handles']['UnitSolo:title']);

        Plugin::deactivate('UnitSolo');

        $this->assertArrayNotHasKey('UnitSolo:title', Plugin::export()['handles']);
        $this->assertSame('x', $plugin->filter('title', 'x'));
    }

    public function testDeactivatingAnUnknownPluginIsSafe(): void
    {
        Plugin::deactivate('NeverActivated');

        $this->assertFalse(Plugin::exists('NeverActivated'));
    }

    public function testDeactivateLeavesOtherPluginsHandlersIntact(): void
    {
        $one = Plugin::factory('UnitSharedOne');
        $one->title = function ($value) {
            return $value . '-one';
        };
        Plugin::activate('UnitSharedOne');

        $two = Plugin::factory('UnitSharedTwo');
        $two->title = function ($value) {
            return $value . '-two';
        };
        Plugin::activate('UnitSharedTwo');

        Plugin::deactivate('UnitSharedOne');

        // UnitTwo's callback for the same component key must survive the diff
        $this->assertSame('x-two', $two->filter('title', 'x'));
    }
}
