<?php
use PHPUnit\Framework\TestCase;
use Typecho\Plugin;
use Typecho\Plugin\Exception as PluginException;

/**
 * Covers the file-oriented parts of the plugin subsystem: header parsing for the admin
 * plugin list, dependency checks against Common::VERSION and resolving a plugin's entry
 * file to its class name.
 */
final class PluginInfoTest extends TestCase
{
    /** @var string[] */
    private array $paths = [];

    protected function tearDown(): void
    {
        // deepest paths first so nested plugin dirs can be removed
        foreach (array_reverse($this->paths) as $path) {
            if (is_dir($path)) {
                foreach (glob($path . '/*') ?: [] as $child) {
                    @unlink($child);
                }
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }

        $this->paths = [];
    }

    private function tempDir(): string
    {
        $path = sys_get_temp_dir() . '/typecho-plugin-' . bin2hex(random_bytes(6));
        mkdir($path);
        $this->paths[] = $path;

        return $path;
    }

    private function writePluginFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'typecho-pluginfile-');
        file_put_contents($path, $content);
        $this->paths[] = $path;

        return $path;
    }

    public function testParseInfoReadsTheDocHeader(): void
    {
        $file = $this->writePluginFile(<<<'PHP'
<?php
/**
 * A short description
 * spanning two lines
 *
 * @package HelloWorld
 * @author someone
 * @link https://example.com
 * @version 1.2.3
 * @since 1.0.0
 */
class HelloWorld_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        return 'ok';
    }

    public static function deactivate()
    {
        return 'ok';
    }
}
PHP);

        $info = Plugin::parseInfo($file);

        $this->assertSame("A short description\nspanning two lines", $info['description']);
        $this->assertSame('HelloWorld', $info['title']);
        $this->assertSame('someone', $info['author']);
        $this->assertSame('https://example.com', $info['homepage']);
        $this->assertSame('1.2.3', $info['version']);
        $this->assertSame('1.0.0', $info['since']);
        $this->assertTrue($info['activate']);
        $this->assertTrue($info['deactivate']);
        $this->assertFalse($info['config']);
        $this->assertFalse($info['personalConfig']);
    }

    public function testParseInfoDetectsConfigAndPersonalConfig(): void
    {
        $file = $this->writePluginFile(<<<'PHP'
<?php
/**
 * Description
 *
 * @package WithConfig
 */
class WithConfig_Plugin implements Typecho_Plugin_Interface
{
    public static function config()
    {
        return [];
    }

    public static function personalConfig()
    {
        return [];
    }
}
PHP);

        $info = Plugin::parseInfo($file);

        $this->assertSame('WithConfig', $info['title']);
        $this->assertTrue($info['config']);
        $this->assertTrue($info['personalConfig']);
        $this->assertFalse($info['activate']);
    }

    public function testParseInfoReturnsDefaultsWithoutDocComment(): void
    {
        $file = $this->writePluginFile("<?php\nclass Plain_Plugin {}\n");

        $info = Plugin::parseInfo($file);

        $this->assertSame('', $info['description']);
        $this->assertSame('', $info['title']);
        $this->assertFalse($info['activate']);
    }

    public function testCheckDependence(): void
    {
        $this->assertTrue(Plugin::checkDependence(null));
        $this->assertTrue(Plugin::checkDependence(''));
        $this->assertTrue(Plugin::checkDependence('1.0.0'));
        $this->assertFalse(Plugin::checkDependence('999.0.0'));
    }

    public function testPortalPrefersThePluginDirectoryEntry(): void
    {
        $dir = $this->tempDir();
        mkdir($dir . '/Foo');
        file_put_contents($dir . '/Foo/Plugin.php', '<?php');
        $this->paths[] = $dir . '/Foo';
        $this->paths[] = $dir . '/Foo/Plugin.php';

        [$file, $class] = Plugin::portal('Foo', $dir);

        $this->assertSame($dir . '/Foo/Plugin.php', $file);
        $this->assertStringEndsWith('\Foo\Plugin', $class);
    }

    public function testPortalFallsBackToASingleFile(): void
    {
        $dir = $this->tempDir();
        file_put_contents($dir . '/Bar.php', '<?php');
        $this->paths[] = $dir . '/Bar.php';

        [$file, $class] = Plugin::portal('Bar', $dir);

        $this->assertSame($dir . '/Bar.php', $file);
        $this->assertStringEndsWith('\Bar', $class);
    }

    public function testPortalThrowsForAMissingPlugin(): void
    {
        $dir = $this->tempDir();

        $this->expectException(PluginException::class);
        $this->expectExceptionCode(404);
        Plugin::portal('Absent', $dir);
    }
}
