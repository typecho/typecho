<?php

namespace Widget\Contents\Post;

use Typecho\Config;
use Typecho\Db;
use Typecho\Router;
use Typecho\Widget;
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
     * 初始化函数
     *
     * @return void
     */
    public function execute()
    {
        // 在开发主题时，type和format应该同时设置
        // 如果没有设置type，则以全局选项为准
        // 未来如有需要，可在全局选项中单独设置format和limit
        if (!isset($this->parameter->type)) {
            switch ($this->options->postArchiveType) {
                case 'year':
                    $this->parameter->setDefault('format=Y&type=year&limit=0', true);
                    break;
                case 'month':
                    $this->parameter->setDefault('format=Y-m&type=month&limit=0', true);
                    break;
                default:
                    $this->parameter->setDefault('format=F Y&type=month&limit=0', true);
            }
        } else {
            /** 设置参数默认值 */
            $this->parameter->setDefault('format=Y-m&type=month&limit=0');
        }

        $resource = $this->db->query($this->db->select('created')->from('table.contents')
            ->where('type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $this->options->time)
            ->order('table.contents.created', Db::SORT_DESC));

        $offset = $this->options->timezone - $this->options->serverTimezone;
        $result = [];
        while ($post = $this->db->fetchRow($resource)) {
            $timeStamp = $post['created'] + $offset;
            $date = date($this->parameter->format, $timeStamp);

            if (isset($result[$date])) {
                $result[$date]['count'] ++;
            } else {
                $result[$date]['year'] = date('Y', $timeStamp);
                $result[$date]['month'] = date('m', $timeStamp);
                $result[$date]['day'] = date('d', $timeStamp);
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
