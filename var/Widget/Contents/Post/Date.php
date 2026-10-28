<?php

namespace Widget\Contents\Post;

use Typecho\Config;
use Typecho\Db;
use Typecho\Router;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 按日期归档列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Date extends Base
{
    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('format=Y-m&type=month&limit=0');
    }

    /**
     * 归档分组的键: 站点当地的那一天
     *
     * 归档的分组键是**站点当地**的年月, 不是服务器当地的。用 date()
     * 渲染会在服务器自己的墙上时钟跳过一小时时偏一小时 (服务器
     * Europe/Berlin, 站点 UTC, 2021-03-28 02:30 UTC 会被算进 3 月 28 日
     * —— 恰好是那一天里唯一有争议的一小时)。
     *
     * gmdate() 用 UTC, 而 $wall 已经是站点墙钟的数值, 所以结果只依赖
     * 站点时区。
     *
     * @param integer $created UTC 时间戳
     * @param string $format 格式
     * @return string
     */
    public static function groupKey(int $created, string $format, ?string $timezone = null): string
    {
        return gmdate($format, self::wallOf($created, $timezone));
    }

    /**
     * 站点当地的墙上时间 (按 UTC 解释)
     *
     * @param integer $created UTC 时间戳
     * @param string|null $timezone 站点时区, 为 null 时从 options 读
     * @return integer
     */
    public static function wallOf(int $created, ?string $timezone = null): int
    {
        if ($timezone === null) {
            /**
             * 【为什么用 alloc() 而不是 widget('Options')】
             *
             * Widget::widget() 把第一个参数当**类名**用:
             *
             *     [$className] = explode('@', $alias);
             *     ...
             *     $widget = new $className(...);
             *
             * 只有 Widget::alias() 注册过的短名才会被改写, 而 core 没有给
             * Options 注册别名。'Options' 既不是类名 (真正的类名是
             * Widget\Options), 也不带命名空间分隔符, 自动加载器找不到它 ——
             * 于是 new 'Options' 抛 Error: Class "Options" not found。
             *
             * alloc() 用 static::class 取全限定类名, 是仓库里的标准写法
             * (Widget\Init / Widget\Upload 都这么用)。
             */
            $timezone = \Widget\Options::alloc()->timezoneName;
        }

        if (!\Typecho\Timezone::isName($timezone)) {
            /**
             * 旧版固定偏移。越界的值按"解释不了"处理 (回退到不调整) ——
             * 与 Timezone::offsetAt / wallTimestamp / wallToTimestamp /
             * toLocalTimestamp 和 Options 的两个方法用同一个判断, 否则
             * 两处算出来的墙钟会差出一个荒谬的量。
             */
            if (!\Typecho\Timezone::isValidLegacyOffset((int) $timezone)) {
                return $created;
            }

            return $created + \Typecho\Timezone::normalizeLegacyOffset((int) $timezone);
        }

        return \Typecho\Timezone::wallTimestamp($created, $timezone);
    }

    /**
     * 初始化函数
     *
     * @return void
     */
    public function execute()
    {
        /** 设置参数默认值 */
        $this->parameter->setDefault('format=Y-m&type=month&limit=0');

        $resource = $this->db->query($this->db->select('created')->from('table.contents')
            ->where('type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $this->options->time)
            ->order('table.contents.created', Db::SORT_DESC));

        $result = [];
        while ($post = $this->db->fetchRow($resource)) {
            /**
             * 【为什么只换算一次】
             *
             * groupKey() 内部会调 wallOf(), 而 wallOf() 单次要构造几个
             * DateTimeZone (isName() 里的 zone(), wallTimestamp() 里再
             * isName() + offsetAt() -> isName() + zone())。两处各调一次
             * 等于每篇文章 8 次时区对象构造, 归档上千篇时是可观测的回归。
             *
             * 所以这里换算一次, $date 和 year/month/day 都从同一个结果派生。
             */
            $timeStamp = self::wallOf($post['created'], $this->options->timezoneName);
            $date = gmdate($this->parameter->format, $timeStamp);

            if (isset($result[$date])) {
                $result[$date]['count'] ++;
            } else {
                $result[$date]['year'] = gmdate('Y', $timeStamp);
                $result[$date]['month'] = gmdate('m', $timeStamp);
                $result[$date]['day'] = gmdate('d', $timeStamp);
                $result[$date]['date'] = $date;
                $result[$date]['count'] = 1;
            }
        }

        if ($this->parameter->limit > 0) {
            $result = array_slice($result, 0, $this->parameter->limit);
        }

        foreach ($result as $row) {
            $row['permalink'] = Router::url(
                'archive_' . $this->parameter->type,
                $row,
                $this->options->index
            );
            $this->push($row);
        }
    }
}
