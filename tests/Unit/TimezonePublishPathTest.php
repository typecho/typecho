<?php

use PHPUnit\Framework\TestCase;
use Typecho\Db;
use Typecho\Timezone;

/**
 * The publish form has two entry paths -- a date field and separate
 * year/month/day fields. Both carry a site wall clock, so both have to
 * deal with a wall clock that cannot be parsed.
 *
 * The point of this test is the **whole path**, not the converter: the
 * converter returning null is fine, but the null must not travel on into
 * a function declared to return int. That raises a TypeError, and the
 * request ends in a 500 instead of a rejected form.
 *
 * So this drives the real widget with a real $_POST, against a real
 * database.
 *
 * Db 的 typed 静态单例无法恢复为未初始化状态。每个用例在独立进程中
 * 建库，且不在父进程执行类级建库钩子，避免临时连接影响其它测试类。
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class TimezonePublishPathTest extends TestCase
{
    private static $db;

    private static $dbFile;

    /**
     * The database instance that was in place before this class replaced it
     */
    private static $previousDb;

    /**
     * 站点时区的进程级静态量, 跑完这个类要还回去
     */
    private static $previousTimezoneName;

    private static $previousTimezoneOffset;

    private static $previousServerTimezoneOffset;

    private static $previousServerTimeStamp;

    protected function setUp(): void
    {
        self::setUpFixture();
    }

    protected function tearDown(): void
    {
        self::tearDownFixture();
    }

    private static function setUpFixture(): void
    {
        /**
         * 【原来这里会漏一个文件】
         *
         * tempnam() 自己**创建一个**文件, 名字就是它返回的那个; 这里
         * 又拼了一个 '.db' 后缀, 于是磁盘上留下一个 tempnam 建的空文件,
         * 而真正用的数据库是另一个名字。删掉的是后者, 前者一直在泄漏。
         */
        self::$dbFile = tempnam(sys_get_temp_dir(), 'typecho-tz-');

        /**
         * 【先把 Options 的缓存清掉】
         *
         * Widget::alloc() 按类名缓存实例, 所以 Options::alloc() 拿到的是
         * **进程级的同一个对象**。这个类要建自己的临时库, 而如果前面
         * 有任何一个用例已经 alloc() 过 Options, 拿到的就是绑定**前一个**
         * 库 (可能已经被 unlink) 和前一个站点配置的实例。
         *
         * 销毁在 tearDownAfterClass 里也有一次, 但那是**跑完之后**的
         * 清理 —— 开始之前也得清一次, 否则这个类拿到的不是自己的。
         */
        \Widget\Options::destroy();

        /**
         * 站点时区是进程级的静态量, 这个类会改它 (Options 的构造函数
         * 会设), 跑完要还回去。
         */
        self::$previousTimezoneName = \Typecho\Date::$timezoneName;
        self::$previousTimezoneOffset = \Typecho\Date::$timezoneOffset;
        self::$previousServerTimezoneOffset = \Typecho\Date::$serverTimezoneOffset;
        self::$previousServerTimeStamp = \Typecho\Date::$serverTimeStamp;

        /**
         * 【把原来的 Db 存起来】Db::set() 是进程级的静态状态, 换掉它
         * 就影响了后面所有的用例。文档里的运行方式是一条命令跑完
         * unit,security,db 三个 suite, 所以这个替换会一直生效到进程结束
         * —— 后面的用例会拿到一个已经被 unlink 的数据库文件。
         */
        try {
            self::$previousDb = Db::get();
        } catch (\Throwable $e) {
            self::$previousDb = null;
        }

        $db = new Db('Pdo_SQLite', 'typecho_');
        $db->addServer(['file' => self::$dbFile], Db::READ | Db::WRITE);
        Db::set($db);
        self::$db = $db;

        foreach (explode(';', file_get_contents(dirname(__DIR__, 2) . '/install/SQLite.sql')) as $statement) {
            if (trim($statement)) {
                $db->query($statement, Db::WRITE);
            }
        }

        $options = [
            'timezone' => 'Asia/Shanghai',
            'theme' => 'default',
            'generator' => 'Typecho 1.3.0',
            'installed' => '1',
            'postDateFormat' => 'Y-m-d',
            'commentDateFormat' => 'Y-m-d',
            'title' => 'T',
            'description' => 'D',
            'allowRegister' => '0',
            'allowXmlRpc' => '0',
            'lang' => 'zh_CN',
            'attachmentTypes' => 'image',
            'siteUrl' => 'http://example.com',
            'routingTable' => 'a',
            'plugins' => 'a',
            'charset' => 'UTF-8',
            'contentType' => 'text/html',
            'secret' => 's',
            'frontPage' => 'index',
            'defaultAllowComment' => '1',
            'defaultAllowPing' => '1',
            'defaultAllowFeed' => '1',
            'commentsListSize' => '10',
            'commentsShowCommentOnly' => '0',
            'commentsThreaded' => '0',
            'commentsPostInterval' => '0',
            'pageSize' => '10',
            'markdown' => '0',
            'xmlrpcMarkdown' => '0',
            'actionTable' => 'a',
            'panelTable' => 'a',
        ];

        foreach ($options as $name => $value) {
            $db->query($db->insert('table.options')->rows([
                'name' => $name,
                'user' => 0,
                'value' => $value,
            ]));
        }
    }

    private static function tearDownFixture(): void
    {
        /**
         * 子进程原来有 Db 时还原同一对象；原来未初始化时，子进程退出
         * 会释放本例的整个单例状态，父进程始终不接触临时 Db。
         */
        if (self::$previousDb instanceof Db) {
            Db::set(self::$previousDb);
        }

        /**
         * 【Options 的实例要销毁, 时区的静态量要恢复】
         *
         * Widget::alloc() 按类名缓存实例, 所以 Options::alloc() 拿到的是
         * **进程级的同一个对象** —— 它持有那个已经被删掉的临时库, 而且
         * 它的构造函数顺手把 Date 的静态时区设成了站点的时区。
         *
         * 不销毁的话, 后面那些用例拿到的还是这个对象, 读的是删掉的库,
         * 而且继承了一个被改过的站点时区。
         */
        \Widget\Options::destroy();

        \Typecho\Date::$timezoneName = self::$previousTimezoneName;
        \Typecho\Date::$timezoneOffset = self::$previousTimezoneOffset;
        \Typecho\Date::$serverTimezoneOffset = self::$previousServerTimezoneOffset;
        \Typecho\Date::$serverTimeStamp = self::$previousServerTimeStamp;

        if (self::$dbFile && file_exists(self::$dbFile)) {
            unlink(self::$dbFile);
        }
    }

    /**
     * Drive the real publish path with a real $_POST.
     *
     * @param array $post the submitted fields
     * @return integer the publish time it computed
     */
    private function publish(array $post, ?string $timezone = null): int
    {
        /**
         * 【参数必须走 setParams(), 不能靠 $_POST】
         *
         * Widget\Request::get() 读的是 $this->params, 而它来自构造时传入
         * 的那个 Config。$_POST 只在 Typecho\Request 单例里被读, 而那个
         * 单例在 bootstrap 时就建好了 —— 之后再改 $_POST 对它没有影响,
         * 于是请求里根本没有这些参数, 测试走的是另一条分支。
         *
         * setParams() 直接写进 widget 自己的 Config, 所以数组也能原样
         * 传过去 (客户端可以提交 date[]=x)。
         */
        $request = new \Typecho\Widget\Request(
            \Typecho\Request::getInstance(),
            new \Typecho\Config([])
        );

        $request->setParams($post);
        $response = new \Typecho\Widget\Response(
            \Typecho\Request::getInstance(),
            \Typecho\Response::getInstance()
        );

        /**
         * The options widget is a protected property, so the test hands it
         * over from inside a subclass.
         *
         * Reflection would do it too, but ReflectionProperty::
         * setAccessible() is deprecated since PHP 8.5 and a deprecation
         * makes a test fail in this project.
         */
        /**
         * 【把原来的站点时区存下来 —— 必须在 widget 建好**之前**读】
         *
         * Options::alloc() 是按类名缓存的单例, 改它的 timezone 等于改
         * 全局状态。而下面那个匿名类的构造函数**实例化的时候**就把
         * timezone 覆盖掉了 —— 所以原值必须在 new 之前读, 否则读到的
         * 已经是覆盖值, finally 里"还回去"还的是覆盖值, 原值就此丢失。
         *
         * 后果很隐蔽: 28800 和 Asia/Shanghai 的偏移一样, 所以站点时间
         * 看起来完全正常, 只有比较**时区名**的用例才会发现不对。
         */
        $options = \Widget\Options::alloc();
        $originalTimezone = (string) $options->timezoneName;

        $widget = new class ($request, $response, $timezone) extends \Widget\Contents\Post\Edit {
            public function publishTime(): int
            {
                return $this->getCreated();
            }

            public function __construct($request, $response, ?string $timezone)
            {
                parent::__construct($request, $response);

                $options = \Widget\Options::alloc();

                if ($timezone !== null) {
                    $options->timezoneName = $timezone;

                    /**
                     * 【断言覆盖真的生效了】
                     *
                     * Options::alloc() 返回的是按类名缓存的单例, 所以这个
                     * 赋值改的是**全局**那个对象。之前没有任何断言确认它
                     * 生效了 —— 如果赋值是空操作, 或者 widget 拿到的
                     * 是另一个 Options 实例, 这个测试照样会绿。
                     */
                    \PHPUnit\Framework\Assert::assertSame(
                        $timezone,
                        (string) $options->timezoneName,
                        'the site time zone override must take effect'
                    );
                }

                $this->options = $options;
            }
        };

        try {
            // 真实已有文章必须进入生产 Contents::push/filter，getCreated 才能看到原值。
            if (isset($post['cid'])) {
                $row = self::$db->fetchRow(self::$db->select()->from('table.contents')
                    ->where('cid = ?', (int) $post['cid'])->limit(1));
                $this->assertNotEmpty($row, 'the existing article must be loaded from SQLite');
                $widget->push($row);
                $this->assertTrue($widget->have());
            }
            return $widget->publishTime();
        } finally {
            $options->timezoneName = $originalTimezone;

            /**
             * Typecho\Request 的单例被 proxy() 设过的 params 要清掉,
             * 否则后面的用例会继承这里的参数。
             */
            \Typecho\Request::getInstance()->endProxy();
        }
    }

    /**
     * The site time zone override must be given back afterwards.
     *
     * Options::alloc() is cached per class name, so writing the site time
     * zone on it changes it for the whole process. The override has to be
     * undone, and the value that is put back has to be the one that was
     * there **before** the widget was built — the widget's constructor
     * overwrites it while it is being constructed.
     *
     * The two values have the same offset (28800 and Asia/Shanghai are
     * both UTC+8), so a site that only looked at the clock would not
     * notice. Only the name differs.
     */
    public function testTheSiteTimeZoneOverrideIsGivenBack(): void
    {
        $options = \Widget\Options::alloc();
        $before = (string) $options->timezoneName;

        $this->publish(['date' => '2021-07-04 12:00'], '28800');

        $this->assertSame(
            $before,
            (string) $options->timezoneName,
            'the site time zone must be the one that was there before the override'
        );

        $this->assertNotSame(
            '28800',
            (string) $options->timezoneName,
            'the override must not survive the call'
        );
    }

    /**
     * An array in the date field must not break the publish path.
     *
     * The date field comes from the request, so a client can post
     * date[]=x. Two things have to hold: nothing is raised, and the
     * publish time does not become 0 (1970).
     *
     * 【数组在哪一步被挡下的】Typecho\Request::get() 只在"请求值的类型
     * 和默认值的类型一致"时返回请求值, 否则交回默认值。所以
     * $this->request->get('date') (默认值 null) 拿到的是 null, 数组根本
     * 到不了 EditTrait 里的 (string) 转换 —— 那道 is_array() 防护是
     * 防御性的第二道, 不是第一道。
     *
     * 【为什么这条测试还是有用】它钉的是"客户端发一个数组过来, 发布
     * 路径不会炸"这个**端到端**的性质。只读源码里的 is_array($raw) 是
     * 钉不住这个性质的 —— 数组在更早一层就被换掉了。
     */
    public function testAnArrayInTheDateFieldDoesNotBreakThePublishPath(): void
    {
        $created = $this->publish(
            ['date' => ['not', 'a', 'date']],
            '28800'
        );

        $this->assertIsInt($created);
        $this->assertGreaterThan(
            0,
            $created,
            'the publish time must not become 0 (1970)'
        );
    }

    /**
     * A malformed time must not move the publish time to midnight.
     *
     * The date field carries the wall clock as free text. When the time
     * part is malformed the publish time used to be rewritten to 00:00,
     * which silently moved the article to midnight -- the opposite of
     * the policy a few lines above it states: a malformed shape keeps
     * the original publish time.
     *
     * The same goes for extra spaces: '2021-07-01  12:00' used to split
     * into an empty second part.
     */
    public function testAMalformedTimeDoesNotMoveThePublishTimeToMidnight(): void
    {
        /**
         * The midnight this would be rewritten to.
         */
        $midnight = \Typecho\Timezone::wallToTimestamp('2021-07-01 00:00', 'Asia/Shanghai');

        $this->assertNotNull($midnight);

        /**
         * 注意: **缺**时间部分 (只有日期) 不是形状不对 —— 那就是午夜,
         * 与改动前的行为一致, 不在这条断言的范围内。
         */
        foreach (['2021-07-01 12:0', '2021-07-01 9.30', '2021-07-01  12:00'] as $value) {
            $result = $this->publish(['date' => $value]);

            $this->assertNotSame(
                $midnight,
                $result,
                sprintf('"%s" must not move the publish time to midnight', $value)
            );
        }
    }

    /**
     * A wall clock that cannot be parsed must not raise.
     *
     * The date field path and the year/month/day path both go through
     * the same converter. Both used to hand its result straight to a
     * function declared to return int, so a null became a TypeError.
     */
    public function testAWallClockThatCannotBeParsedDoesNotRaise(): void
    {
        $cases = [
            'the date field, an hour past the day' => ['date' => '2021-03-15 25:00'],
            'the date field, a month past the year' => ['date' => '2021-13-45 10:30'],
            'the date field, a minute past the hour' => ['date' => '2021-03-15 10:61'],
            'the date field, nonsense' => ['date' => 'not a date'],
            'the fields, a month past the year' => [
                'year' => 2021, 'month' => 13, 'day' => 45,
                'hour' => 10, 'min' => 30, 'sec' => 0,
            ],
            'the fields, a minute past the hour' => [
                'year' => 2021, 'month' => 3, 'day' => 15,
                'hour' => 10, 'min' => 99, 'sec' => 0,
            ],
        ];

        foreach ($cases as $label => $post) {
            $result = $this->publish($post);

            $this->assertIsInt(
                $result,
                "$label must not raise, and must not come back as null"
            );

            $this->assertNotSame(
                0,
                $result,
                "$label must not store the post as 1970-01-01"
            );
        }
    }

    /**
     * The same, on a site that still has the old fixed offset.
     *
     * The old format is a number of seconds, not a name, and it takes a
     * different branch. Both branches have to survive an unparseable
     * wall clock.
     *
     * The site time zone is set on the widget's own options row, not in
     * the database: Widget::widget() caches instances by class name, so
     * a second alloc() would hand back the row it read the first time.
     */
    public function testAnUnparseableWallClockDoesNotRaiseOnALegacySite(): void
    {
        foreach ([
            'the date field, an hour past the day' => ['date' => '2021-03-15 25:00'],
            'the date field, a month past the year' => ['date' => '2021-13-45 10:30'],
            'the fields, a month past the year' => [
                'year' => 2021, 'month' => 13, 'day' => 45,
                'hour' => 10, 'min' => 30, 'sec' => 0,
            ],
        ] as $label => $post) {
            $result = $this->publish($post, '28800');

            $this->assertIsInt(
                $result,
                "$label must not raise on a legacy site either"
            );

            $this->assertNotSame(
                0,
                $result,
                "$label must not store the post as 1970-01-01"
            );
        }
    }

    /**
     * A valid wall clock still gives the right instant.
     *
     * The guard above must not swallow the normal case.
     */
    public function testAValidWallClockStillGivesTheRightInstant(): void
    {
        $expected = Timezone::wallToTimestamp('2021-03-15 10:30', 'Asia/Shanghai');

        $this->assertSame(
            $expected,
            $this->publish(['date' => '2021-03-15 10:30']),
            'the date field must give the site wall clock'
        );

        $this->assertSame(
            $expected,
            $this->publish([
                'year' => 2021, 'month' => 3, 'day' => 15,
                'hour' => 10, 'min' => 30, 'sec' => 0,
            ]),
            'the year/month/day fields must give the same instant'
        );
    }

    /** 两个相同墙钟的真实瞬间独立执行；不让第一条失败遮住第二条。 */
    public static function repeatedHourPosts(): array
    {
        return [
            'first 01:10 with nonzero seconds' => ['2021-11-07T08:10:37Z'],
            'second 01:10 with nonzero seconds' => ['2021-11-07T09:10:49Z'],
        ];
    }

    private function createExistingFoldPost(int $created): int
    {
        $slug = 'existing-fold-' . $created;
        self::$db->query(self::$db->insert('table.contents')->rows([
            'title' => 'Before editing', 'slug' => $slug,
            'created' => $created, 'modified' => $created,
            'text' => 'Existing body', 'type' => 'post', 'status' => 'publish',
            'authorId' => 1, 'parent' => 0,
        ]));
        $row = self::$db->fetchRow(self::$db->select('cid')->from('table.contents')
            ->where('slug = ?', $slug)->limit(1));
        $this->assertNotEmpty($row, 'the existing post fixture must be persisted');
        return (int) $row['cid'];
    }

    /**
     * 日期控件只显示分钟。只改标题时，两个 fold 和原本秒数都必须保留。
     * @dataProvider repeatedHourPosts
     */
    public function testExistingFoldPostRetainsItsInstantWhenTheDateIsUnchanged(string $utc): void
    {
        $created = (new DateTimeImmutable($utc))->getTimestamp();
        $cid = $this->createExistingFoldPost($created);
        try {
            $this->assertSame('2021-11-07 01:10',
                (new DateTimeImmutable('@' . $created))->setTimezone(new DateTimeZone('America/Los_Angeles'))
                    ->format('Y-m-d H:i'));
            $actual = $this->publish(['cid' => $cid, 'title' => 'Only the title changed',
                'date' => '2021-11-07 01:10'], 'America/Los_Angeles');
            $this->assertSame($created, $actual,
                'an untouched minute-precision date must preserve the existing fold and seconds');
        } finally {
            self::$db->query(self::$db->delete('table.contents')->where('cid = ?', $cid));
        }
    }

    /**
     * 保留原始日期的规则不能吞掉作者明确修改的日期；两次 fold 都独立验收。
     * @dataProvider repeatedHourPosts
     */
    public function testExplicitDateChangeStillAppliesToAnExistingFoldPost(string $utc): void
    {
        $created = (new DateTimeImmutable($utc))->getTimestamp();
        $cid = $this->createExistingFoldPost($created);
        try {
            $actual = $this->publish(['cid' => $cid, 'title' => 'Rescheduled article',
                'date' => '2021-11-07 02:11:23'], 'America/Los_Angeles');
            $this->assertSame((new DateTimeImmutable('2021-11-07T10:11:23Z'))->getTimestamp(), $actual,
                'an explicitly changed unambiguous site time must take effect including its seconds');
            $this->assertNotSame($created, $actual);
        } finally {
            self::$db->query(self::$db->delete('table.contents')->where('cid = ?', $cid));
        }
    }


    /** 已有文章收到非法日期时，真实发布路径必须精确保留原 UTC 和秒数。 */
    public function testInvalidDatePreservesTheExistingPublishInstantExactly(): void
    {
        $created = (new DateTimeImmutable('2021-11-07T09:10:49Z'))->getTimestamp();
        $cid = $this->createExistingFoldPost($created);
        try {
            foreach (['2021-13-45 25:00', '2021-07-04 12:61', 'not a date'] as $invalid) {
                $actual = $this->publish(['cid' => $cid, 'title' => 'Title edited with invalid date',
                    'date' => $invalid], 'America/Los_Angeles');
                $this->assertSame($created, $actual,
                    'an invalid edit date must preserve the existing publish instant exactly: ' . $invalid);
            }
        } finally {
            self::$db->query(self::$db->delete('table.contents')->where('cid = ?', $cid));
        }
    }

}
