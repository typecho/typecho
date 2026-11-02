<?php

namespace Typecho;

/**
 * 日期处理
 *
 * @author qining
 * @category typecho
 * @package Date
 */
class Date
{
    /**
     * 期望时区偏移
     *
     * @access public
     * @var integer
     */
    public static int $timezoneOffset = 0;

    /**
     * 期望时区 (IANA 名称, 如 America/Los_Angeles)
     *
     * 站点时区改为保存 IANA 名称后, 夏令时偏移随所处日期变化, 无法再用
     * 单一偏移量表示, 故这里保存名称本身。未设置时回退到固定偏移。
     *
     * @access public
     * @var string
     */
    public static string $timezoneName = '';

    /**
     * 服务器时区偏移
     *
     * @access public
     * @var integer
     */
    public static int $serverTimezoneOffset = 0;

    /**
     * 当前的服务器时间戳
     *
     * @access public
     * @var integer
     */
    public static int $serverTimeStamp = 0;

    /**
     * 可以被直接转换的时间戳
     *
     * @access public
     * @var integer
     */
    public int $timeStamp = 0;

    /**
     * 站点当地的墙上时间 (按 UTC 解释的数值)
     *
     * 渲染站点墙钟时用它, 而不是 timeStamp。gmdate() 格式化它得到的就是
     * 站点当地的日期时间, 整个过程不碰服务器时区 —— 服务器自己的墙上
     * 时钟在夏令时切换日跳过那一小时时, 用 date() 渲染会偏一小时。
     *
     * @access public
     * @var integer
     */
    public int $wallTimeStamp = 0;

    /**
     * 原始的 UTC 时间戳
     *
     * timeStamp 是换算到站点时区之后的墙上时间戳, 两者在夏令时切换日
     * 的间隔不同, 所以要留着原始值来算真实经过的时间。
     *
     * @access public
     * @var integer
     */
    public int $utcTimeStamp = 0;

    /**
     * @var string
     */
    public string $year;

    /**
     * @var string
     */
    public string $month;

    /**
     * @var string
     */
    public string $day;

    /**
     * 初始化参数
     *
     * @param integer|null $time 时间戳
     */
    public function __construct(?int $time = null)
    {
        $timeStamp = null === $time ? self::time() : $time;

        $this->utcTimeStamp = $timeStamp;

        /**
         * 旧版固定偏移。
         *
         * 【为什么这里也要查范围】$timezoneOffset 是公开静态量, 数据库里
         * 一行越界的旧值就能让它变成 999999999。直接用它做加减, 站点
         * 墙钟会偏到 2053 年去 —— 而 siteZone() 那边已经按"解释不了"
         * 回退了, 这边不回退就自相矛盾。
         */
        $legacyOffset = Timezone::isValidLegacyOffset(self::$timezoneOffset)
            ? Timezone::normalizeLegacyOffset(self::$timezoneOffset)
            : 0;

        /**
         * 【三处判断必须一致】构造函数的两处、siteZone() 和 word() 用
         * 的是同一个判据: 时区名**确实是**一个 IANA 名称。
         *
         * 只判断"非空"不够: 迁移不到精确匹配地区时区的偏移会保留原值,
         * 站点时区就可能是 '12345' 这样的字符串 —— 那不是时区名。
         * 按固定偏移处理才是它的意思。
         */
        $hasName = self::$timezoneName !== '' && Timezone::isName(self::$timezoneName);

        // 两种配置都按目标墙钟的服务器日历生成载体，不能减请求季节的偏移。
        $this->timeStamp = Timezone::toLocalTimestamp(
            $timeStamp,
            $hasName ? self::$timezoneName : (string) $legacyOffset,
            self::$serverTimezoneOffset
        );

        /**
         * 站点墙钟: UTC 瞬间 + 站点时区在那一刻的偏移。
         *
         * 与服务器时区无关, 所以服务器自己的墙上时钟跳过一小时时,
         * 站点墙钟依然正确。
         */
        $this->wallTimeStamp = $hasName
            ? Timezone::wallTimestamp($timeStamp, self::$timezoneName)
            : $timeStamp + $legacyOffset;

        $this->year = gmdate('Y', $this->wallTimeStamp);
        $this->month = gmdate('m', $this->wallTimeStamp);
        $this->day = gmdate('d', $this->wallTimeStamp);
    }

    /**
     * 设置当前期望的时区
     *
     * @param string $timezone IANA 时区名, 如 America/Los_Angeles
     */
    public static function setTimezone(string $timezone): void
    {
        self::$timezoneName = trim($timezone);

        /**
         * 【为什么不是 0】
         *
         * $timezoneOffset 是**公开静态**属性, 语义上表示"站点当前的期望
         * 偏移"。清成 0 的话, 任何本 PR 之外读它的模块/插件在所有已
         * 迁移的站点上都会拿到 0 —— 也就是把站点当成 UTC, 整整偏一个
         * 时区。
         *
         * 所以这里填上**站点此刻**的偏移: 名字是 IANA 名, 偏移随日期
         * 变, 但"此刻的偏移"对读它的调用方仍然是有意义的答案。真正的
         * 按日期换算走 Date::format() 和 Timezone::* , 不依赖这个静态量。
         */
        self::$timezoneOffset = Timezone::offsetAt($timezone, self::time());
        self::$serverTimezoneOffset = idate('Z');
    }

    /**
     * 设置当前期望的时区偏移
     *
     * @param integer $offset
     */
    public static function setTimezoneOffset(int $offset)
    {
        /**
         * 越界的偏移按"解释不了"处理, 不写进这个公开静态量 ——
         * 否则下游会拿它当站点偏移, 而它连一个合法时区都算不上。
         */
        self::$timezoneOffset = Timezone::isValidLegacyOffset($offset)
            ? Timezone::normalizeLegacyOffset($offset)
            : 0;
        self::$timezoneName = '';
        self::$serverTimezoneOffset = idate('Z');
    }

    /**
     * 获取格式化时间
     *
     * @param string $format 时间格式
     * @return string
     */
    public function format(string $format): string
    {
        $hasName = self::$timezoneName !== '' && Timezone::isName(self::$timezoneName);
        $offset = $hasName ? Timezone::offsetAt(self::$timezoneName, $this->utcTimeStamp)
            : (Timezone::isValidLegacyOffset(self::$timezoneOffset) ? self::$timezoneOffset : 0);
        if ($offset % 60 !== 0) {
            // RFC 的分钟区标识不能表达秒偏移。含未转义区标签的整份格式
            // 改用 UTC，保持日期字段与标签一致；不能只替换其中的 c/r。
            $serialize = false;
            $wallFormat = '';
            $length = strlen($format);
            for ($i = 0; $i < $length; ++$i) {
                $token = $format[$i];
                if ($token === '\\') {
                    $wallFormat .= $token;
                    if (++$i < $length) {
                        $wallFormat .= $format[$i];
                    }
                    continue;
                }
                if (strpos('crOPeT', $token) !== false || ($token === 'p' && PHP_VERSION_ID >= 80000)) {
                    $serialize = true;
                }
                if (!$hasName && $token === 'U') {
                    $wallFormat .= (string) $this->utcTimeStamp;
                } elseif (!$hasName && $token === 'Z') {
                    $wallFormat .= (string) $offset;
                } elseif (!$hasName && $token === 'B') {
                    $wallFormat .= gmdate('B', $this->utcTimeStamp);
                } else {
                    $wallFormat .= $token;
                }
            }
            if ($serialize) {
                return gmdate($format, $this->utcTimeStamp);
            }
            if (!$hasName) {
                // 固定偏移没有DST；普通字段仍保留原墙钟秒，U/Z/B各有自身语义。
                return gmdate($wallFormat, $this->utcTimeStamp + $offset);
            }
            // 命名时区的 I/U/Z/B 等继续由原生对象解释，保留历史 DST 信息。
        }

        /**
         * 走 DateTimeImmutable, 而不是 gmdate()。
         *
         * 【为什么不是 gmdate()】gmdate() 用 UTC 格式化, 所以带时区
         * 指示符的格式会全部写成 +00:00:
         *
         *   date('c')  -> 2021-07-04T23:40:00+00:00
         *
         * 那个字符串被消费者解析回去时, 落到的是另一个瞬间 —— 差整整
         * 一个时区偏移。HTML5 的 <time datetime="...">、RSS 的
         * <pubDate>、JSON-LD 的 datePublished 都走这条路。
         *
         * 【为什么不是 date()】date() 用**服务器**时区, 服务器自己的墙上
         * 时钟在夏令时切换日跳过一小时时, 站点墙钟会跟着偏一小时。
         *
         * 【现在怎么做】把真实瞬间交给 DateTimeImmutable, 让它按**站点
         * 时区**解释:
         *
         *   - 日期时间字段 = 站点墙钟 (和 gmdate($format, $wallTimeStamp)
         *     完全一致, 所以这一路不变)
         *   - 时区指示符 = 站点在该瞬间的真实偏移 (e/O/P/T/Z)
         *   - I/i = 站点在该瞬间是否实行夏令时
         *   - U = 真实瞬间 (不是墙钟数值)
         *
         * 一次解决所有带指示符的格式, 不需要为每种格式单独拼。
         */
        $zone = self::siteZone();

        if ($zone === null) {
            /**
             * 拿不到站点时区 (名称无效) —— 退回原来的纯墙钟渲染。
             */
            return gmdate($format, $this->wallTimeStamp);
        }

        $date = new \DateTimeImmutable('@' . $this->utcTimeStamp);

        return $date->setTimezone($zone)->format($format);
    }

    /**
     * 站点时区
     *
     * IANA 名称优先; 旧版站点只有固定偏移, 用一个固定偏移的时区对象
     * 代替 —— 语义一样 (旧版本来就没有夏令时)。
     *
     * @return \DateTimeZone|null null 表示名称无效, 调用方自行降级
     */
    private static function siteZone(): ?\DateTimeZone
    {
        /**
         * 【必须先确认它是合法的 IANA 名称】
         *
         * 只判断"非空"是不够的: 迁移不到精确匹配地区时区的偏移会**保留
         * 原值** (比如 12345 秒 → '12345'), 而那不是一个时区名。
         * DateTimeZone('12345') 会抛异常, 于是这里返回 null, format()
         * 退回 gmdate() —— 那会发出 +00:00 的时区标识, 而且 'U' 返回的
         * 是**墙钟**而不是绝对瞬间, 订阅源和 JSON-LD 里的时间就是错的。
         *
         * isName() 才是那个判断: 它认得 IANA 名称, 也认得旧版的固定偏移
         * 形态, 所以旧值会走下面的固定偏移分支, 而不是掉进"名字无效"
         * 的兜底。
         */
        $timezoneName = trim(self::$timezoneName);
        if ($timezoneName !== '' && Timezone::isName($timezoneName)) {
            try {
                return new \DateTimeZone($timezoneName);
            } catch (\Exception $e) {
                return null;
            }
        }

        /**
         * 旧版固定偏移。
         *
         * 【为什么必须先查范围】下面的 sprintf 会把偏移拼成 '±HH:MM' 交给
         * DateTimeZone。越界的偏移 (比如 999999999) 拼出来是 '+277777:46',
         * DateTimeZone 构造函数会抛 Exception —— 而这里没有 try/catch,
         * 于是异常一路冒到 format(), 每一篇文章/评论/归档的渲染都 500。
         * 修复前这个值最多让日期算错, 不会让整个站点挂掉。
         *
         * 所以先查范围, 越界的按"非法名称"同样处理 (返回 null, 让调用
         * 方回退)。
         */
        if (!\Typecho\Timezone::isValidLegacyOffset(self::$timezoneOffset)) {
            return null;
        }

        $offset = self::$timezoneOffset;
        $sign = $offset < 0 ? '-' : '+';
        $offset = abs($offset);

        /** 秒级固定偏移由 format() 的兼容分支处理；此处只接收整分钟。 */
        $offset = Timezone::normalizeLegacyOffset($offset);

        $hours = intdiv($offset, 3600);
        $minutes = intdiv($offset % 3600, 60);

        return new \DateTimeZone(sprintf(
            '%s%02d:%02d',
            $sign,
            intdiv($offset, 3600),
            intdiv($offset % 3600, 60)
        ));
    }

    /**
     * 获取国际化偏移时间
     *
     * @return string
     */
    public function word(): string
    {
        $now = self::time();

        /**
         * 真实经过的秒数: 用两个 **UTC** 时间戳相减。
         *
         * 夏令时切换日, 墙上时间会跳过或重复一段, 两个墙上时间戳的差与
         * 真实经过的时间可以差整整一小时。相对时间的时长必须用真实经过
         * 时间算, 否则跨切换日时"1 小时前"会显示成"2 小时前"。
         *
         * $now 换算成墙钟是为了跟 $this->timeStamp 比日历 (同一天/昨天),
         * 时长则回到 UTC 域来算。
         */
        $nowUtc = self::time();
        $elapsed = $nowUtc - $this->utcTimeStamp;

        /**
         * 现在的墙钟也用 wallTimestamp() 算, 与 $this->wallTimeStamp 同一
         * 套语义 (站点墙钟, 按 UTC 解释)。两者用不同的公式会让"同一天"
         * 的判断自相矛盾。
         */
        if (self::$timezoneName !== '' && Timezone::isName(self::$timezoneName)) {
            $now = Timezone::wallTimestamp($nowUtc, self::$timezoneName);
        } else {
            /**
             * 越界的旧偏移按"解释不了"处理 (回退到不调整) —— 与构造函数
             * 和 siteZone() 用同一个判断, 三处不能各行其是。
             */
            $now = $nowUtc + (Timezone::isValidLegacyOffset(self::$timezoneOffset)
                ? Timezone::normalizeLegacyOffset(self::$timezoneOffset)
                : 0);
        }

        return I18n::dateWord($this->wallTimeStamp, $now, $elapsed);
    }

    /**
     * 获取GMT时间
     *
     * @deprecated
     * @return int
     */
    public static function gmtTime(): int
    {
        return self::time();
    }

    /**
     * 获取服务器时间
     *
     * @return int
     */
    public static function time(): int
    {
        return self::$serverTimeStamp ?: (self::$serverTimeStamp = time());
    }
}
