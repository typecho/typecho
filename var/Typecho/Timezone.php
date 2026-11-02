<?php

namespace Typecho;

/**
 * 时区工具。
 *
 * 管理员可选择地区名称以按具体日期处理夏令时，也可保留旧固定偏移。
 * 固定偏移不能唯一确定地区，因此不会自动推测或改写用户的选择。
 */
class Timezone
{
    /**
     * 旧版固定偏移秒数的合法范围
     *
     * UTC-14:00 .. UTC+14:00, 也就是 -50400 .. 50400 秒。
     *
     * 【为什么要有上限】时区是客户端可控的值 (后台设置页、XML-RPC 的
     * wp.setOptions), 而站点各处按 (int) 解释它。落一个 999999999 会让
     * 偏移变成 11574 天, 落一个超出范围的数字会让整个站点的时间失去
     * 意义。真实存在的时区偏移都在 UTC-14..UTC+14 之间, 所以这个范围
     * 不会误伤任何合法值。
     */
    public const LEGACY_OFFSET_MIN = -50400;
    public const LEGACY_OFFSET_MAX = 50400;

    /**
     * 判断一个旧版固定偏移秒数是否在合法范围内
     *
     * @param integer $offset 偏移秒数
     * @return boolean
     */
    public static function isValidLegacyOffset(int $offset): bool
    {
        return $offset >= self::LEGACY_OFFSET_MIN && $offset <= self::LEGACY_OFFSET_MAX;
    }

    /**
     * 保留旧版固定偏移的整数秒精度。
     *
     * 旧版公开接口接受任意整数秒，不能因部分 PHP 版本不支持带秒的
     * DateTimeZone 字面而改写原值。保留本方法签名供已有调用点使用。
     */
    public static function normalizeLegacyOffset(int $offset): int
    {
        return $offset;
    }

    /**
     * 判断时区值是否为 IANA 名称
     *
     * 旧版保存的是固定偏移秒数, 形如 -28800 / 28800 / 0。
     *
     * 除了纯数字, 形如 "+28800" / "28800.0" 的值也属于旧格式, 不能
     * 当成 IANA 名称处理, 否则会被解析成一个不存在的时区而静默变成 UTC。
     * 真正的 IANA 名称必须能被 DateTimeZone 识别。
     *
     * @param string $timezone 时区值
     * @return boolean
     */
    public static function isName(string $timezone): bool
    {
        $timezone = trim($timezone);

        if ($timezone === '') {
            return false;
        }

        /** 固定偏移的旧格式, 含 "+28800" / "28800.0" 这类变体 */
        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $timezone)) {
            return false;
        }

        return self::zone($timezone) !== null;
    }

    /**
     * 获取时区在某一 UTC 瞬间的偏移 (秒)
     *
     * @param string $timezone IANA 名称或固定偏移秒数
     * @param int $timestamp UTC 时间戳
     * @return integer
     */
    public static function offsetAt(string $timezone, int $timestamp): int
    {
        $timezone = trim($timezone);

        if ($timezone === '') {
            return 0;
        }

        if (!self::isName($timezone)) {
            /**
             * 旧版固定偏移。越界的值按"解释不了"处理 (回退到不调整),
             * 免得一行被污染的数据把整站的时间带偏。
             */
            if (!self::isValidLegacyOffset((int) $timezone)) {
                return 0;
            }

            return self::normalizeLegacyOffset((int) $timezone);
        }

        $zone = self::zone($timezone);

        if ($zone === null) {
            return 0;
        }

        return (int) $zone->getOffset(new \DateTime('@' . $timestamp));
    }

    /**
     * 把 UTC 瞬间换算成站点当地的墙上时间 (按 UTC 解释的数值)
     *
     * 【为什么要有这个函数】渲染站点墙钟时, 不能借助服务器的 date() ——
     * date() 永远用服务器时区, 而服务器自己的墙上时钟在夏令时切换日
     * 会跳过或重复一段, 那一刻的"解"会偏一小时。实测: 服务器
     * Europe/Berlin, 站点 UTC, 2021-03-28 02:30 UTC 会被渲染成 01:30。
     *
     * 【怎么避开】站点墙钟只依赖两个量: UTC 瞬间, 与站点时区在**那一
     * 刻**的偏移。两者都与服务器时区无关。偏移加到 UTC 瞬间上得到的
     * 数值, 用 gmdate() 格式化就是站点当地的墙上时间 —— gmdate() 用
     * UTC, 不受服务器时区影响。
     *
     * 【与 toLocalTimestamp 的区别】那个函数解的是"按服务器时区读出来
     * 是站点墙钟"的兼容载体；服务器缺口时间会归一化，结果依赖服务器时区。这个函数直接把
     * 站点墙钟算成数值, 结果只依赖站点时区。渲染应当用这个。
     *
     * @param int $timestamp UTC 时间戳
     * @param string $timezone IANA 时区名
     * @return integer 站点墙钟 (按 UTC 解释)
     */
    public static function wallTimestamp(int $timestamp, string $timezone): int
    {
        if (!self::isName($timezone)) {
            /**
             * 旧版固定偏移。这个值不随日期变化, 直接加即可。
             * 越界的值按"解释不了"处理 (回退到不调整)。
             */
            if (!self::isValidLegacyOffset((int) $timezone)) {
                return $timestamp;
            }

            return $timestamp + self::normalizeLegacyOffset((int) $timezone);
        }

        return $timestamp + self::offsetAt($timezone, $timestamp);
    }

    /**
     * 把 UTC 时间戳换算成可交给服务器 date() 的兼容载体。
     *
     * 先计算站点墙钟字段，再交给服务器时区的 PHP 原生日期规则解释。
     * 如果这些字段落入服务器的夏令时缺口，就不存在精确的兼容载体；
     * 此时采用 PHP 原生向前归一化，重复时间也由平台规则选择。
     * 核心渲染应使用 wallTimestamp() 与 gmdate()，墙钟解析应使用
     * wallToTimestamp()，以避免经过服务器时区这一兼容层。
     *
     * @param int $timestamp UTC 时间戳
     * @param string $timezone IANA 名称或固定偏移秒数
     * @param int $serverOffset 保留的兼容参数；内部使用服务器时区的日期规则
     * @return integer
     */
    public static function toLocalTimestamp(int $timestamp, string $timezone, int $serverOffset): int
    {
        if (!self::isName($timezone)) {
            /** 越界的旧偏移按不调整站点墙钟处理。 */
            if (!self::isValidLegacyOffset((int) $timezone)) {
                return self::resolveLocalTimestamp($timestamp);
            }

            return self::resolveLocalTimestamp(
                $timestamp + self::normalizeLegacyOffset((int) $timezone)
            );
        }

        $target = $timestamp + self::offsetAt($timezone, $timestamp);

        return self::resolveLocalTimestamp($target);
    }

    /**
     * 按服务器时区解释目标墙钟字段，生成旧调用点需要的兼容载体。
     *
     * 服务器的缺口时间没有精确解，使用 PHP 原生向前归一化；重复时间
     * 由同一平台的日期规则处理，不自行猜测跳转方向或选择偏移。
     * 通过字段 setter 而非五位年字符串解析，保留 year 10000 的边界。
     *
     * @param int $target 目标墙钟（按 UTC 解释的数值）
     * @return integer
     */
    private static function resolveLocalTimestamp(int $target): int
    {
        return (new \DateTimeImmutable('@0'))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->setDate((int) gmdate('Y', $target), (int) gmdate('n', $target), (int) gmdate('j', $target))
            ->setTime((int) gmdate('G', $target), (int) gmdate('i', $target), (int) gmdate('s', $target))
            ->getTimestamp();
    }

    /**
     * 把 XML-RPC 的 GMT 字面换算成 UTC 时间戳
     *
     * 字面可以写成紧凑格式 (YYYYMMDDTHH:MM:SS) 或扩展 ISO
     * (YYYY-MM-DDTHH:MM:SS), 也可以带时区后缀 ('Z' 或 '+08:00')。
     *
     * 【为什么按形状取字段】按固定字符位置切, 扩展 ISO 会在第 17 个字符
     * 处把秒切掉, 拼出一个不存在的时刻。
     *
     * 【后缀怎么算】'Z' 或没有后缀 -> 按 GMT 解释 (这个字段的约定);
     * 明确的数字偏移 -> 尊重它, 不要把偏移吃掉。
     *
     * @param string $literal 字面
     * @return integer|null 时间戳, 形状不认识时返回 null
     */
    /**
     * 把一个墙钟字面按给定时区解析成 UTC 时间戳
     *
     * 与 gmtLiteralToTimestamp() 的区别: 那个按字面自带的时区后缀解析
     * (用于 GMT 字段), 这个**忽略后缀**, 把整个字面当成**目标时区下的
     * 墙上时间**。
     *
     * 【为什么需要它】IXR\Date::getTimestamp() 用 mktime() 解释字面, 而
     * mktime() 构造的是**服务器本地**时刻。字面落在服务器自己的夏令时
     * 缺口里时 (比如柏林 2021-03-28 02:00-02:59), mktime() 会把它向前
     * 归一化一小时, 客户端要的 02:30 变成了 03:30 —— 那一小时 60 分钟
     * 全部偏一小时。
     *
     * 字面本身没有歧义: 它就是目标时区下的墙钟文本, 不该经过服务器
     * 时区这一层。
     *
     * @param string $literal 墙钟字面
     * @param string $timezone 目标时区 (IANA 名称或旧版固定偏移秒数)
     * @return integer|null null 表示这个字面解析不出来
     */
    public static function wallLiteralToTimestamp(string $literal, string $timezone): ?int
    {
        $pattern = '/^(?<year>\d{4})-?(?<month>\d{2})-?(?<day>\d{2})'
            . '[T ](?<hour>\d{2}):?(?<minute>\d{2})(?::?(?<second>\d{2}))?'
            . '(?:[.,]\d+)?'
            . '(?<zone>[Zz]|[+-]\d{2}:?(?:\d{2})?)?$/';

        if (!preg_match($pattern, $literal, $matches)) {
            return null;
        }

        /**
         * 时区后缀一律丢掉 —— 这个字面是**目标时区下的**墙上时间,
         * 客户端给的偏移不是我们要的 (它可能是客户端自己的时区)。
         */
        $wall = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day']
            . ' ' . $matches['hour'] . ':' . $matches['minute']
            . ':' . (($matches['second'] ?? '') !== '' ? $matches['second'] : '00');

        return self::wallToTimestamp($wall, $timezone);
    }

    /**
     * 把一个带时区后缀的字面按**它自带的**时区解析成 UTC 时间戳
     *
     * 与 wallLiteralToTimestamp() 的区别: 那个忽略后缀, 把整个字面当成
     * **目标时区下**的墙上时间; 这个按字面自带的偏移解析, 用于 GMT 字段
     * (比如 WordPress 的 date_created_gmt)。
     *
     * 【为什么形状要按正则拆】紧凑格式 ('20210314T01:30:00Z')、扩展格式
     * ('2021-03-14T01:30:00Z')、缺秒、小数秒、不同的偏移写法 ('+08:00' /
     * '+0800' / '+08') 都有, 按固定字符位置切会在扩展格式上把秒切掉。
     *
     * @param string $literal 带时区后缀的字面
     * @return integer|null null 表示这个字面解析不出来
     */
    public static function gmtLiteralToTimestamp(string $literal): ?int
    {
        /**
         * 形状: 年 月 日 分 秒, 分隔符可以是 - / 无 / 空格, 时间部分
         * 可以用 T 或空格, 秒可以缺, 小数秒可以带, 时区后缀可以是
         * Z / z / ±HH:MM / ±HHMM / ±HH。
         */
        $pattern = '/^(?<year>\d{4})-?(?<month>\d{2})-?(?<day>\d{2})'
            . '[T ](?<hour>\d{2}):?(?<minute>\d{2})(?::?(?<second>\d{2}))?'
            . '(?:[.,]\d+)?'
            . '(?<zone>[Zz]|[+-]\d{2}:?(?:\d{2})?)?$/';

        if (!preg_match($pattern, $literal, $matches)) {
            return null;
        }

        $readable = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day']
            . ' ' . $matches['hour'] . ':' . $matches['minute']
            . ':' . (($matches['second'] ?? '') !== '' ? $matches['second'] : '00');

        $suffix = $matches['zone'] ?? '';

        if ($suffix === '' || strtoupper($suffix) === 'Z') {
            $suffix = 'UTC';
        }

        $timestamp = strtotime($readable . ' ' . $suffix);

        return $timestamp === false ? null : $timestamp;
    }

    /**
     * 把该时区的墙钟**文本** (Y-m-d H:i) 换算回 UTC 时间戳
     *
     * 【为什么不接受标量的服务器偏移】服务器时区本身可能实行夏令时。
     * mktime() 内嵌的是**被解析日期**当时的服务器偏移, 而 idate('Z')
     * 给的是请求时刻的服务器偏移, 跨季节时两者差一小时。传一个标量进去
     * 必然要在这两者之间二选一, 选哪个都会在另一种季节出错。
     *
     * 【怎么解决】直接用 DateTime 解析墙钟文本 —— 它同时知道墙钟和目标
     * 日期, 两边需要的偏移都由它自己按日期算, 不需要外部再传一个。
     *
     * @param string $wall 墙钟时间 (Y-m-d H:i)
     * @param string $timezone IANA 名称或固定偏移秒数
     * @return integer|null null 表示这个墙钟时间不合法
     */
    public static function wallToTimestamp(string $wall, string $timezone): ?int
    {
        $timezone = trim($timezone);
        if ($timezone === '') {
            $timezone = 'UTC';
        }

        $legacySeconds = 0;
        if (self::isName($timezone)) {
            $zone = self::zone($timezone);
        } else {
            $offset = (int) $timezone;
            if (!self::isValidLegacyOffset($offset)) {
                return null;
            }
            $offset = self::normalizeLegacyOffset($offset);
            if ($offset % 60 !== 0) {
                // PHP 7.4 的带秒固定区字面会静默变 UTC；用精确整数秒反解。
                $legacySeconds = $offset;
                $zone = new \DateTimeZone('UTC');
            } else {
                $absolute = abs($offset);
                $zone = new \DateTimeZone(sprintf('%s%02d:%02d', $offset < 0 ? '-' : '+',
                    intdiv($absolute, 3600), intdiv($absolute % 3600, 60)));
            }
        }

        try {
            // 由平台处理目标日期的规则，包括跳过与重复的本地小时。
            return (new \DateTimeImmutable($wall, $zone))->getTimestamp() - $legacySeconds;
        } catch (\Throwable $e) {
            // 最后一期归档的排他上界可能为 10000-01-01。文本解析器不支持
            // 五位年，setDate() 支持，且仍由同一时区对象负责换算。
            if (!preg_match('/^(\d{5})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/', $wall, $m)) {
                return null;
            }
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
            $hour = (int) $m[4];
            $minute = (int) $m[5];
            $second = (int) ($m[6] ?? 0);
            if ($month < 1 || $month > 12 || $day < 1 || $day > 31
                || $hour > 23 || $minute > 59 || $second > 59) {
                return null;
            }
            return (new \DateTimeImmutable('@0'))->setTimezone($zone)
                ->setDate($year, $month, $day)->setTime($hour, $minute, $second)->getTimestamp() - $legacySeconds;
        }
    }

    /**
     * 把该时区的墙钟时间戳换算回 UTC 时间戳
     *
     * 【入参的域】$timestamp 必须是**服务器本地语义**的墙钟时间戳, 也就是
     * mktime() 的结果 —— 它已经把目标日期当时的服务器偏移算进去了。
     *
     * 【为什么不接受标量的服务器偏移】服务器时区本身可能实行夏令时。
     * mktime() 用的是被解析日期的服务器偏移, idate('Z') 给的是请求时刻的
     * 服务器偏移, 跨季节时两者差一小时。如果这里再加一次标量偏移, 就会
     * 把服务器自己的夏令时算两遍。
     *
     * 所以正确的做法是: 先把服务器本地语义的墙钟时间戳还原成墙钟**文本**,
     * 再交给 wallToTimestamp() 按目标时区解析 —— 那一步由 DateTime 负责
     * 目标日期的偏移, 不需要外部再传。
     *
     * @param int $timestamp 墙钟时间戳 (mktime 语义)
     * @param string $timezone IANA 名称或固定偏移秒数
     * @param int $serverOffset 服务器时区偏移 (秒)
     *
     * 【这个参数已经用不上了】和 toLocalTimestamp() 一样, 传进来的标量
     * 是请求时刻的偏移, 而这里要的是**那个时间戳那一刻**的偏移, 方法
     * 内部用 date() 按时间戳取, 所以这个参数不再参与计算。
     *
     * @return integer
     */
    public static function fromLocalTimestamp(int $timestamp, string $timezone, int $serverOffset): int
    {
        /**
         * 服务器本地语义的墙钟时间戳 -> 墙钟文本。
         *
         * date() 按服务器时区格式化, 所以格式化出来的就是那个墙钟。
         *
         * 【必须带上秒】归档查询的上界是 mktime(23, 59, 59, ...), 而查询
         * 条件是 created < 上界。秒被截掉的话, 23:59:00-23:59:59 发布的
         * 文章会同时掉出本期和下一期归档。
         */
        $wall = date('Y-m-d H:i:s', $timestamp);

        $result = self::wallToTimestamp($wall, $timezone);

        if ($result === null) {
            /**
             * 【为什么这里降级而不是抛异常】
             *
             * $wall 是 date() 生成的, 一定是合法日期时间, 所以 null 只可能
             * 来自"站点时区不可用" (比如数据库里躺着一行越界的旧偏移)。
             * 那不是请求错误, 是站点数据的问题 —— 抛出去会让用到这个方法的
             * 页面 500。
             *
             * 而同一个坏值在 Date::siteZone()、Timezone::offsetAt()、
             * Timezone::wallTimestamp() 上都是降级, 所以这里也降级: 按
             * UTC 读这个墙钟, 也就是"不调整"。
             */
            $parsed = strtotime($wall . ' UTC');

            /**
             * 到不了这里: $wall 是 date() 生成的, 一定是合法日期时间。
             */
            if ($parsed === false) {
                throw new \InvalidArgumentException(
                    'fromLocalTimestamp: a date() formatted wall clock must parse'
                );
            }

            return $parsed;
        }

        return $result;
    }

    /**
     * 保留旧调用点的包装函数，固定偏移不能唯一确定地区。
     *
     * @deprecated 新代码应直接保存经过校验的原始配置。
     */
    public static function migrateOffset(int $offset): string
    {
        return self::isValidLegacyOffset($offset) ? (string) $offset : 'UTC';
    }

    /** @deprecated 不再使用地区映射表。 */
    public static function migrateOffsetFromTable(int $offset): string
    {
        return self::migrateOffset($offset);
    }

    /**
     * 安全地获取时区对象
     *
     * @param string $timezone IANA 名称
     * @return \DateTimeZone|null
     */
    private static function zone(string $timezone): ?\DateTimeZone
    {
        try {
            return new \DateTimeZone($timezone);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
