<?php
use PHPUnit\Framework\TestCase;
use Typecho\Request;
use Typecho\Response;
use Typecho\Widget;
use Typecho\Widget\Terminal;

final class WidgetFactoryOkStub extends Widget
{
    public static int $executed = 0;

    public function execute()
    {
        self::$executed++;
    }
}

final class WidgetFactoryThrowingStub extends Widget
{
    public static ?Widget $last = null;

    public function execute()
    {
        self::$last = $this;
        throw new \RuntimeException('boom');
    }
}

final class WidgetFactoryTerminalStub extends Widget
{
    public static ?Widget $last = null;

    public function execute()
    {
        self::$last = $this;
        throw new Terminal('stop');
    }
}

final class WidgetFactoryBrokenStub extends Widget
{
    protected function init()
    {
        throw new \RuntimeException('ctor');
    }
}

/**
 * Pins the behavior of Widget::widget() across sandbox and pooled modes.
 * Each test uses its own "@suffix" alias so the static widget pool never leaks between tests.
 */
final class WidgetFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        WidgetFactoryThrowingStub::$last = null;
        WidgetFactoryTerminalStub::$last = null;
    }

    public function testPooledExceptionPropagates(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        Widget::widget(WidgetFactoryThrowingStub::class . '@pooledThrow');
    }

    public function testPooledReturnsSameInstance(): void
    {
        $alias = WidgetFactoryOkStub::class . '@pooledOk';
        $before = WidgetFactoryOkStub::$executed;

        $first = Widget::widget($alias);
        $second = Widget::widget($alias);

        $this->assertInstanceOf(WidgetFactoryOkStub::class, $first);
        $this->assertSame($first, $second);
        $this->assertSame($before + 1, WidgetFactoryOkStub::$executed);
    }

    public function testPooledTerminalKeepsInstance(): void
    {
        $alias = WidgetFactoryTerminalStub::class . '@pooledTerminal';

        $first = Widget::widget($alias);

        $this->assertSame(WidgetFactoryTerminalStub::$last, $first);
        $this->assertSame($first, Widget::widget($alias));
    }

    public function testSandboxSwallowsException(): void
    {
        $widget = Widget::widget(WidgetFactoryThrowingStub::class . '@sandboxThrow', null, null, false);

        $this->assertInstanceOf(WidgetFactoryThrowingStub::class, $widget);
        $this->assertSame(WidgetFactoryThrowingStub::$last, $widget);
        $this->assertSandboxEnded();
    }

    public function testSandboxSwallowsCallbackException(): void
    {
        $widget = Widget::widget(
            WidgetFactoryOkStub::class . '@sandboxCallbackThrow',
            null,
            null,
            function () {
                throw new \RuntimeException('callback');
            }
        );

        $this->assertInstanceOf(WidgetFactoryOkStub::class, $widget);
        $this->assertSandboxEnded();
    }

    public function testSandboxSwallowsTerminal(): void
    {
        $widget = Widget::widget(WidgetFactoryTerminalStub::class . '@sandboxTerminal', null, null, false);

        $this->assertSame(WidgetFactoryTerminalStub::$last, $widget);
        $this->assertSandboxEnded();
    }

    public function testSandboxBypassesPool(): void
    {
        $alias = WidgetFactoryOkStub::class . '@sandboxOk';
        $seen = null;

        $first = Widget::widget($alias, null, ['a' => 1], function ($widget) use (&$seen) {
            $seen = $widget;
        });
        $second = Widget::widget($alias, null, null, false);

        $this->assertInstanceOf(WidgetFactoryOkStub::class, $first);
        $this->assertSame($first, $seen);
        $this->assertNotSame($first, $second);
        $this->assertSandboxEnded();

        // a pooled call after sandbox calls must build a fresh instance, proving sandbox never wrote to the pool
        $pooled = Widget::widget($alias);
        $this->assertNotSame($first, $pooled);
        $this->assertNotSame($second, $pooled);
        $this->assertSame($pooled, Widget::widget($alias));
    }

    public function testSandboxConstructorFailureIsTypeError(): void
    {
        // no instance exists to return, so the non-nullable return type rejects null
        $this->expectException(\TypeError::class);

        Widget::widget(WidgetFactoryBrokenStub::class . '@sandboxBroken', null, null, false);
    }

    private function assertSandboxEnded(): void
    {
        $request = new \ReflectionProperty(Request::class, 'sandbox');
        if (PHP_VERSION_ID < 80100) {
            $request->setAccessible(true);
        }
        $this->assertNull($request->getValue(Request::getInstance()));

        $response = new \ReflectionProperty(Response::class, 'sandbox');
        if (PHP_VERSION_ID < 80100) {
            $response->setAccessible(true);
        }
        $this->assertFalse($response->getValue(Response::getInstance()));
    }
}
