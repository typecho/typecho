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
     * @var \DateTimeZone|null
     */
    private ?\DateTimeZone $timezone = null;

    /**
     * 获取博客固定时区
     *
     * @return \DateTimeZone
     */
    private function getTimezone(): \DateTimeZone
    {
        if (null === $this->timezone) {
            $offset = (int) $this->options->timezone;
            $sign = $offset < 0 ? '-' : '+';
            $offset = abs($offset);
            $timezone = sprintf(
                '%s%02d:%02d',
                $sign,
                intdiv($offset, 3600),
                intdiv($offset % 3600, 60)
            );
            $this->timezone = new \DateTimeZone($timezone);
        }

        return $this->timezone;
    }

    /**
     * 获取数据库聚合粒度
     *
     * @return string|null
     */
    private function getGroupUnit(): ?string
    {
        $format = preg_replace('/\\\\./', '', (string) $this->parameter->format);

        if (preg_match('/[aABcegGhHisuUvr]/', $format)) {
            return null;
        } elseif (preg_match('/[dDjlNSwzWoIOPpTZ]/', $format)) {
            return 'day';
        } elseif (preg_match('/[FmMnt]/', $format)) {
            return 'month';
        }

        return 'year';
    }

    /**
     * 获取数据库日期分组表达式
     *
     * @param string $unit
     * @return array
     */
    private function getGroupExpressions(string $unit): array
    {
        $adapter = $this->db->getAdapter();
        $created = $adapter->quoteColumn('created');
        $timestamp = '(' . $created . ' + ' . (int) $this->options->timezone . ')';

        switch ($adapter->getDriver()) {
            case 'mysql':
                $date = "TIMESTAMPADD(SECOND, {$timestamp}, '1970-01-01 00:00:00')";
                $expressions = ["YEAR({$date})", "MONTH({$date})", "DAYOFMONTH({$date})"];
                break;
            case 'pgsql':
                $date = "(TIMESTAMP 'epoch' + {$timestamp} * INTERVAL '1 second')";
                $expressions = [
                    "EXTRACT(YEAR FROM {$date})",
                    "EXTRACT(MONTH FROM {$date})",
                    "EXTRACT(DAY FROM {$date})"
                ];
                break;
            default:
                $expressions = [
                    "STRFTIME('%Y', {$timestamp}, 'unixepoch')",
                    "STRFTIME('%m', {$timestamp}, 'unixepoch')",
                    "STRFTIME('%d', {$timestamp}, 'unixepoch')"
                ];
                break;
        }

        return array_slice($expressions, 0, ['year' => 1, 'month' => 2, 'day' => 3][$unit]);
    }

    /**
     * 按日期聚合已发布文章
     *
     * @param string $unit
     * @return mixed
     * @throws \Typecho\Db\Exception
     */
    private function getAggregatedRows(string $unit)
    {
        $adapter = $this->db->getAdapter();
        $created = $adapter->quoteColumn('created');
        $groups = $this->getGroupExpressions($unit);
        $table = $adapter->quoteColumn($this->db->getPrefix() . 'contents');

        $sql = 'SELECT MAX(' . $created . ') AS ' . $adapter->quoteColumn('created')
            . ', COUNT(*) AS ' . $adapter->quoteColumn('count')
            . ' FROM ' . $table
            . ' WHERE ' . $adapter->quoteColumn('type') . ' = ' . $adapter->quoteValue('post')
            . ' AND ' . $adapter->quoteColumn('status') . ' = ' . $adapter->quoteValue('publish')
            . ' AND ' . $created . ' < ' . (int) $this->options->time
            . ' GROUP BY ' . implode(', ', $groups)
            . ' ORDER BY ' . implode(' DESC, ', $groups) . ' DESC';

        return $this->db->query($sql);
    }

    /**
     * 合并日期归档行
     *
     * @param array $result
     * @param int $created
     * @param int $count
     */
    private function appendResult(array &$result, int $created, int $count)
    {
        $dateTime = (new \DateTimeImmutable('@' . $created))->setTimezone($this->getTimezone());
        $date = $dateTime->format($this->parameter->format);

        if (isset($result[$date])) {
            $result[$date]['count'] += $count;
        } else {
            $result[$date] = [
                'year'  => $dateTime->format('Y'),
                'month' => $dateTime->format('m'),
                'day'   => $dateTime->format('d'),
                'date'  => $date,
                'count' => $count
            ];
        }
    }

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('format=Y-m&type=month&limit=0');
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

        $result = [];

        if (null === ($unit = $this->getGroupUnit())) {
            $resource = $this->db->query($this->db->select('created')->from('table.contents')
                ->where('type = ?', 'post')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.created < ?', $this->options->time)
                ->order('table.contents.created', Db::SORT_DESC));

            while ($post = $this->db->fetchRow($resource)) {
                $this->appendResult($result, (int) $post['created'], 1);
            }
        } else {
            $resource = $this->getAggregatedRows($unit);
            while ($row = $this->db->fetchRow($resource)) {
                $this->appendResult($result, (int) $row['created'], (int) $row['count']);
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
