<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 描述性数据
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 描述性数据组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Abstract_Metas extends Widget_Abstract
{
    /**
     * 锚点id
     *
     * @access protected
     * @return string
     */
    protected function ___theId()
    {
        return $this->type . '-' . $this->mid;
    }

    /**
     * 获取原始查询对象
     *
     * @access public
     * @return Typecho_Db_Query
     */
    public function select()
    {
        return $this->db->select()->from('table.metas');
    }

    /**
     * 插入一条记录
     *
     * @access public
     * @param array $options 记录插入值
     * @return integer
     */
    public function insert(array $options)
    {
        return $this->db->query($this->db->insert('table.metas')->rows($options));
    }

    /**
     * 更新记录
     *
     * @access public
     * @param array $options 记录更新值
     * @param Typecho_Db_Query $condition 更新条件
     * @return integer
     */
    public function update(array $options, Typecho_Db_Query $condition)
    {
        return $this->db->query($condition->update('table.metas')->rows($options));
    }

    /**
     * 删除记录
     *
     * @access public
     * @param Typecho_Db_Query $condition 删除条件
     * @return integer
     */
    public function delete(Typecho_Db_Query $condition)
    {
        return $this->db->query($condition->delete('table.metas'));
    }

    /**
     * 获取记录总数
     *
     * @access public
     * @param Typecho_Db_Query $condition 计算条件
     * @return integer
     */
    public function size(Typecho_Db_Query $condition)
    {
        return $this->db->fetchObject($condition->select(array('COUNT(mid)' => 'num'))->from('table.metas'))->num;
    }

    /**
     * 通用过滤器
     *
     * @access public
     * @param array $value 需要过滤的行数据
     * @return array
     */
    public function filter(array $value)
    {
        //生成静态链接
        $type = $value['type'];
        $routeExists = (NULL != Typecho_Router::get($type));
        $tmpSlug = $value['slug'];
        $value['slug'] = urlencode($value['slug']);

        $value['permalink'] = $routeExists ? Typecho_Router::url($type, $value, $this->options->index) : '#';

        /** 生成聚合链接 */
        /** RSS 2.0 */
        $value['feedUrl'] = $routeExists ? Typecho_Router::url($type, $value, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $value['feedRssUrl'] = $routeExists ? Typecho_Router::url($type, $value, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $value['feedAtomUrl'] = $routeExists ? Typecho_Router::url($type, $value, $this->options->feedAtomUrl) : '#';

        $value['slug'] = $tmpSlug;
        $value = $this->pluginHandle(__CLASS__)->filter($value, $this);
        return $value;
    }

    /**
     * 将每行的值压入堆栈
     *
     * @access public
     * @param array $value 每行的值
     * @return array
     */
    public function push(array $value)
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * 获取最大排序
     * 
     * @param mixed $type 
     * @param int $parent 
     * @access public
     * @return integer
     */
    public function getMaxOrder($type, $parent = 0)
    {
        return $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))
        ->from('table.metas')
        ->where('type = ? AND parent = ?', 'category', $parent))->maxOrder;
    }

    /**
     * 对数据按照sort字段排序
     *
     * @access public
     * @param array $metas
     * @param string $type
     * @return void
     */
    public function sort(array $metas, $type)
    {
        foreach ($metas as $sort => $mid) {
            $this->update(array('order' => $sort + 1),
            $this->db->sql()->where('mid = ?', $mid)->where('type = ?', $type));
        }
    }

    /**
     * 合并数据
     *
     * @access public
     * @param integer $mid 数据主键
     * @param string $type 数据类型
     * @param array $metas 需要合并的数据集
     * @return void
     */
    public function merge($mid, $type, array $metas)
    {
        $contents = Typecho_Common::arrayFlatten($this->db->fetchAll($this->select('cid')
        ->from('table.relationships')
        ->where('mid = ?', $mid)), 'cid');

        foreach ($metas as $meta) {
            if ($mid != $meta) {
                $existsContents = Typecho_Common::arrayFlatten($this->db->fetchAll($this->db
                ->select('cid')->from('table.relationships')
                ->where('mid = ?', $meta)), 'cid');

                $where = $this->db->sql()->where('mid = ? AND type = ?', $meta, $type);
                $this->delete($where);
                $diffContents = array_diff($existsContents, $contents);
                $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $meta));

                foreach ($diffContents as $content) {
                    $this->db->query($this->db->insert('table.relationships')
                    ->rows(array('mid' => $mid, 'cid' => $content)));
                    $contents[] = $content;
                }

                $this->update(array('parent' => $mid), $this->db->sql()->where('parent = ?', $meta));
                unset($existsContents);
            }
        }

        $num = $this->db->fetchObject($this->db
        ->select(array('COUNT(mid)' => 'num'))->from('table.relationships')
        ->where('table.relationships.mid = ?', $mid))->num;

        $this->update(array('count' => $num), $this->db->sql()->where('mid = ?', $mid));
    }

    /**
     * 根据tag获取ID
     *
     * @access public
     * @param mixed $inputTags 标签名
     * @return array
     */
    public function scanTags($inputTags)
    {
        $tags = is_array($inputTags) ? $inputTags : array($inputTags);
        $result = array();

        foreach ($tags as $tag) {
            if (empty($tag)) {
                continue;
            }

            $row = $this->db->fetchRow($this->select()
            ->where('type = ?', 'tag')
            ->where('name = ?', $tag)->limit(1));

            if ($row) {
                $result[] = $row['mid'];
            } else {
                $slug = Typecho_Common::slugName($tag);

                if ($slug) {
                    $result[] = $this->insert(array(
                        'name'  =>  $tag,
                        'slug'  =>  $slug,
                        'type'  =>  'tag',
                        'count' =>  0,
                        'order' =>  0,
                    ));
                }
            }
        }

        return is_array($inputTags) ? $result : current($result);
    }

    /**
     * 清理没有任何内容的标签
     * 
     * @access public
     * @return void
     */
    public function clearTags()
    {
        // 取出count为0的标签
        $tags = Typecho_Common::arrayFlatten($this->db->fetchAll($this->db->select('mid')
            ->from('table.metas')->where('type = ? AND count = ?', 'tags', 0)), 'mid');

        foreach ($tags as $tag) {
            // 确认是否已经没有关联了
            $content = $this->db->fetchRow($this->db->select('cid')
                ->from('table.relationships')->where('mid = ?', $tag)
                ->limit(1));

            if (empty($content)) {
                $this->db->query($this->db->delete('table.metas')
                    ->where('mid = ?', $tag));
            }
        }
    }

    /**
     * 根据内容的指定类别和状态更新相关meta的计数信息
     *
     * @access public
     * @param int $mid meta id
     * @param string $type 类别
     * @param string $status 状态
     * @return void
     */
    public function refreshCountByTypeAndStatus($mid, $type, $status = 'publish')
    {
        $num = $this->db->fetchObject($this->db->select(array('COUNT(table.contents.cid)' => 'num'))->from('table.contents')
        ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
        ->where('table.relationships.mid = ?', $mid)
        ->where('table.contents.type = ?', $type)
        ->where('table.contents.status = ?', $status))->num;

        $this->db->query($this->db->update('table.metas')->rows(array('count' => $num))
        ->where('mid = ?', $mid));
    }
}
