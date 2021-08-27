<?php

namespace Widget\Contents\Post;

use Typecho\Db;
use Typecho\Router;
use Typecho\Widget;
use Widget\Options;

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
class Date extends Widget
{
    /**
     * 全局选项
     *
     * @var Options
     */
    protected $options;

    /**
     * 数据库对象
     *
     * @var Db
     */
    protected $db;

    /**
     * 构造函数,初始化组件
     *
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     * @throws Db\Exception
     * @throws Widget\Exception
     */
    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);

        /** 初始化数据库 */
        $this->db = Db::get();

        /** 初始化常用组件 */
        $this->options = self::widget('\Widget\Options');
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
                self::widget('Widget_Options')->index
            );
            $this->push($row);
        }
    }
}
