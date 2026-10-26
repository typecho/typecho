<?php

use PHPUnit\Framework\TestCase;
use Typecho\Date;
use Typecho\Db;
use Typecho\Timezone;
use Widget\Options;
use Widget\XmlRpc;

/**
 * 时区配置的公共契约：数字接口、原始配置、缓存与 XML-RPC 使用同一份选择。
 *
 * 使用生产类和真实 PDO SQLite。两个连接池共享同一个内存连接，既不创建
 * 临时站点，也不替换 Db 全局单例；不覆盖仓库的自动加载器。
 */
final class TimezoneOptionsContractTest extends TestCase
{
    private array $savedDateState = [];
    private string $savedServerTimezone;
    private ?Db $savedGlobalDb = null;
    private bool $globalDbWasInitialized = false;
    private PDO $pdo;
    private Db $db;
    private Options $options;
    private XmlRpc $xml;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for the XML-RPC option contract');
        }

        $this->savedServerTimezone = date_default_timezone_get();
        foreach (['timezoneOffset', 'timezoneName', 'serverTimezoneOffset', 'serverTimeStamp'] as $name) {
            $this->savedDateState[$name] = Date::${$name};
        }

        $global = $this->property(Db::class, 'instance');
        $this->globalDbWasInitialized = $global->isInitialized();
        if ($this->globalDbWasInitialized) {
            $this->savedGlobalDb = $global->getValue();
        }

        date_default_timezone_set('UTC');
        Date::$serverTimeStamp = strtotime('2026-07-01T12:00:00Z');
        Date::$serverTimezoneOffset = 0;

        $this->options = (new ReflectionClass(Options::class))->newInstanceWithoutConstructor();
        $this->options->push(['timezone' => 'America/Los_Angeles']);
        $this->options->setTimezone('America/Los_Angeles');

        $this->db = new Db('Pdo_SQLite', 'typecho_');
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->inject($this->db->getAdapter(), 'object', $this->pdo);
        $this->inject($this->db, 'connectedPool', [Db::READ => $this->pdo, Db::WRITE => $this->pdo]);
        $this->pdo->exec('CREATE TABLE typecho_options '
            . '(name TEXT, user INTEGER, value TEXT, PRIMARY KEY(name,user))');
        $this->pdo->exec("INSERT INTO typecho_options VALUES "
            . "('timezone',0,'America/Los_Angeles'),('timezone',7,'UTC')");

        $this->xml = (new ReflectionClass(XmlRpc::class))->newInstanceWithoutConstructor();
        $this->inject($this->xml, 'db', $this->db);
        $this->inject($this->xml, 'options', $this->options);
        $this->inject($this->xml, 'wpOptions', [
            'time_zone' => ['desc' => '时区', 'readonly' => false, 'option' => 'timezone']
        ]);
    }

    protected function tearDown(): void
    {
        try {
            // 测试从不安装自己的全局 Db；已有单例仍恢复为最初的同一对象。
            if ($this->globalDbWasInitialized && $this->savedGlobalDb !== null) {
                Db::set($this->savedGlobalDb);
            } elseif ($this->savedDateState) {
                $this->assertFalse($this->property(Db::class, 'instance')->isInitialized(),
                    'the local XML-RPC fixture must not install a global database');
            }
        } finally {
            foreach ($this->savedDateState as $name => $value) {
                Date::${$name} = $value;
            }
            if (isset($this->savedServerTimezone)) {
                date_default_timezone_set($this->savedServerTimezone);
            }
            if (isset($this->db)) {
                $this->db->flushPool();
            }
            parent::tearDown();
        }
    }

    public function testNamedConfigurationKeepsThePublicOffsetNumeric(): void
    {
        $this->assertSame('America/Los_Angeles', $this->options->timezoneName);
        $this->assertSame(-25200, $this->options->timezone);
        $this->assertSame(-28800, $this->options->timezoneOffsetAt(strtotime('2026-01-01T12:00:00Z')));
        $this->assertSame(-25200, $this->options->timezoneOffsetAt(strtotime('2026-07-01T12:00:00Z')));
    }

    public function testNumericMagicAssignmentInvalidatesBothCachesAndDateService(): void
    {
        // 先读取两种属性，确保随后验证的确覆盖旧缓存，而不只是第一次读取。
        $this->assertSame('America/Los_Angeles', $this->options->timezoneName);
        $this->assertSame(-25200, $this->options->timezone);

        $this->options->timezone = 28800;

        $this->assertSame('28800', $this->options->timezoneName);
        $this->assertSame(28800, $this->options->timezone);
        $this->assertSame('', Date::$timezoneName);
        $this->assertSame(28800, Date::$timezoneOffset);
        $this->assertCurrentDateService('', '+08:00');
    }

    public function testNamedMagicAssignmentAndIterationKeepOneConfiguration(): void
    {
        $this->assertSame(-25200, $this->options->timezone);
        $this->assertSame('America/Los_Angeles', $this->options->timezoneName);

        $this->options->timezoneName = ' Europe/Berlin ';
        $this->assertSame('Europe/Berlin', $this->options->timezoneName);
        $this->assertSame(7200, $this->options->timezone);

        $this->assertCurrentDateService('Europe/Berlin', 'Europe/Berlin');
        for ($round = 0; $round < 3; $round++) {
            $this->options->next();
            $this->assertSame('Europe/Berlin', $this->options->timezoneName);
            $this->assertSame(7200, $this->options->timezone);
            $this->assertCurrentDateService('Europe/Berlin', 'Europe/Berlin');
        }

        $this->options->customOption = 'kept';
        $this->assertSame('kept', $this->options->customOption);
    }

    public function testLegacyOffsetStaysFixedAcrossSeasons(): void
    {
        $this->options->setTimezone('-25200');

        $this->assertSame('-25200', $this->options->timezoneName);
        $this->assertSame(-25200, $this->options->timezoneOffsetAt(strtotime('2026-01-01T00:00:00Z')));
        $this->assertSame(-25200, $this->options->timezoneOffsetAt(strtotime('2026-07-01T00:00:00Z')));
        $this->assertSame('-25200', Timezone::migrateOffset(-25200));
        $this->assertSame('-21600', Timezone::migrateOffsetFromTable(-21600));
    }

    public function testXmlRpcNumericWriteSynchronizesStorageResponseAndCurrentRequest(): void
    {
        $this->assertSame(-25200, $this->rpcRead());
        $this->assertSame('America/Los_Angeles', $this->options->timezoneName);

        $written = $this->rpcWrite(-21600);

        $this->assertSame(-21600, $written);
        $this->assertSame('-21600', $this->options->timezoneName);
        $this->assertSame(-21600, $this->options->timezone);
        $this->assertSame(-21600, Date::$timezoneOffset);
        $this->assertSame('', Date::$timezoneName);
        $this->assertSame('-21600', $this->storedTimezone(0));
        $this->assertSame('UTC', $this->storedTimezone(7));
        $this->assertSame(-21600, $this->rpcRead());
    }

    public function testXmlRpcNamedAndDecimalWritesKeepTheNumericPublicContract(): void
    {
        foreach (['America/New_York', '+05:45', 'CEST'] as $name) {
            // 缩写在不同 PHP/tzdata 版本中的解释不同；所有路径须保持原生规则一致。
            $offset = (new DateTimeImmutable('@' . Date::$serverTimeStamp))
                ->setTimezone(new DateTimeZone($name))->getOffset();
            $this->assertSame($offset, $this->rpcWrite($name));
            $this->assertSame($name, $this->options->timezoneName);
            $this->assertSame($offset, $this->options->timezone);
            $this->assertSame($name, $this->storedTimezone(0));
            $this->assertSame('UTC', $this->storedTimezone(7));
            $this->assertSame($offset, $this->rpcRead());
            $this->assertCurrentDateService($name, $name);
        }

        $this->assertSame(28800, $this->rpcWrite('28800.0'));
        $this->assertSame('28800', $this->options->timezoneName);
        $this->assertSame('', Date::$timezoneName);
        $this->assertSame('28800', $this->storedTimezone(0));
        $this->assertSame('UTC', $this->storedTimezone(7));
        $this->assertCurrentDateService('', '+08:00');
    }

    public function testInvalidXmlRpcWritesPreserveSiteAndPersonalOptions(): void
    {
        foreach (['999999999', '-999999999', 'Not/AZone', '', ['unexpected']] as $invalid) {
            $this->assertSame(-25200, $this->rpcWrite($invalid));
            $this->assertSame('America/Los_Angeles', $this->options->timezoneName);
            $this->assertSame(-25200, $this->options->timezone);
            $this->assertSame('America/Los_Angeles', Date::$timezoneName);
            $this->assertSame('America/Los_Angeles', $this->storedTimezone(0));
            $this->assertSame('UTC', $this->storedTimezone(7));
        }
    }

    public function testMissingSiteRowCannotReportAZeroRowUpdateAsSaved(): void
    {
        // 真实 SQLite UPDATE 返回零行；个人同名项不能充当站点选项。
        $this->pdo->exec("DELETE FROM typecho_options WHERE name='timezone' AND user=0");
        $this->assertSame(-25200, $this->rpcRead());

        $this->assertSame(-25200, $this->rpcWrite(-21600));
        $this->assertSame('America/Los_Angeles', $this->options->timezoneName);
        $this->assertSame(-25200, $this->options->timezone);
        $this->assertSame('America/Los_Angeles', Date::$timezoneName);
        $this->assertSame(false, $this->storedTimezone(0));
        $this->assertSame('UTC', $this->storedTimezone(7));
        $this->assertSame(-25200, $this->rpcRead());
    }

    /**
     * 不显式传时区，真正经过 Post\Date::wallOf() -> Options::alloc()。
     * 注册真实 Options fixture 到实际 Widget 缓存，而不是替换工厂或自动加载器。
     */
    public function testPostDateResolvesTheCurrentOptionsThroughTheRealFactory(): void
    {
        $property = $this->property(\Typecho\Widget::class, 'widgetPool');
        $savedPool = $property->getValue();
        $pool = $savedPool;
        $pool[\Typecho\Common::nativeClassName(Options::class)] = $this->options;
        // 不让旧短别名缓存掩盖原来 widget('Options') 的错误。
        unset($pool['Options']);
        $property->setValue(null, $pool);
        try {
            $this->assertSame($this->options, Options::alloc());
            $instant = (new DateTimeImmutable('2026-07-01T02:34:56Z'))->getTimestamp();
            foreach (['America/Los_Angeles', '+05:45', 'CEST'] as $name) {
                // 修改通过真实 XML-RPC 和 SQLite，工厂读取的是同请求的实际 Options。
                $expected = (new DateTimeImmutable('@' . $instant))->setTimezone(new DateTimeZone($name));
                $this->assertSame($expected->getOffset(), $this->rpcWrite($name));
                $this->assertSame($name, $this->storedTimezone(0));
                $this->assertSame($instant + $expected->getOffset(),
                    \Widget\Contents\Post\Date::wallOf($instant));
                $this->assertSame($expected->format('Y-m-d H:i:s'),
                    \Widget\Contents\Post\Date::groupKey($instant, 'Y-m-d H:i:s'));
            }
        } finally {
            $property->setValue(null, $savedPool);
        }
    }

    /** 同请求中的 Date 全局服务必须同步，序列化以原生 DateTime 为独立基准。 */
    private function assertCurrentDateService(string $expectedName, string $nativeZone): void
    {
        $instant = Date::$serverTimeStamp;
        $expected = (new DateTimeImmutable('@' . $instant))->setTimezone(new DateTimeZone($nativeZone));
        $this->assertSame($expectedName, Date::$timezoneName);
        $this->assertSame($expected->getOffset(), Date::$timezoneOffset);
        $actual = new Date($instant);
        foreach (['c', 'r', 'U', 'Y-m-d H:i:s P'] as $format) {
            $this->assertSame($expected->format($format), $actual->format($format));
        }
    }

    /** 不声明返回类型，避免弱类型转换掩盖接口错误地返回数字字符串。 */
    private function rpcRead()
    {
        return $this->xml->wpGetOptions(1, '', '', ['time_zone'])['time_zone']['value'];
    }

    private function rpcWrite($value)
    {
        return $this->xml->wpSetOptions(1, '', '', ['time_zone' => $value])['time_zone']['value'];
    }

    /** @return string|false */
    private function storedTimezone(int $user)
    {
        $statement = $this->pdo->prepare("SELECT value FROM typecho_options WHERE name='timezone' AND user=?");
        $statement->execute([$user]);
        return $statement->fetchColumn();
    }

    private function inject(object $object, string $name, $value): void
    {
        $this->property(get_class($object), $name)->setValue($object, $value);
    }

    private function property(string $class, string $name): ReflectionProperty
    {
        $property = (new ReflectionClass($class))->getProperty($name);
        // PHP 7.4 需要开放私有访问；8.1 起无需调用，避免 8.5 的废弃告警。
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        return $property;
    }
}
