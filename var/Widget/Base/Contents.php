<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Config;
use Typecho\Cookie;
use Typecho\Date;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Plugin;
use Typecho\Router;
use Typecho\Widget;
use Utils\AutoP;
use Utils\Markdown;
use Widget\Base;
use Widget\Metas\Category\Rows;
use Widget\Upload;
use Widget\Users\Author;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 内容基类
 *
 * @property int $cid
 * @property string $title
 * @property string $slug
 * @property int $created
 * @property int $modified
 * @property string $text
 * @property int $order
 * @property int $authorId
 * @property string $template
 * @property string $type
 * @property string $status
 * @property string|null $password
 * @property int $commentsNum
 * @property bool $allowComment
 * @property bool $allowPing
 * @property bool $allowFeed
 * @property int $parent
 * @property int $parentId
 * @property-read Users $author
 * @property-read string $permalink
 * @property-read string $url
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 * @property-read bool $isMarkdown
 * @property-read bool $hidden
 * @property-read string $category
 * @property-read Date $date
 * @property-read string $dateWord
 * @property-read string[] $directory
 * @property-read array $tags
 * @property-read array $categories
 * @property-read string $description
 * @property-read string $excerpt
 * @property-read string $summary
 * @property-read string $content
 * @property-read Config $fields
 * @property-read Config $attachment
 * @property-read string $theId
 * @property-read string $respondId
 * @property-read string $commentUrl
 * @property-read string $trackbackUrl
 * @property-read string $responseUrl
 */
class Contents extends Base implements QueryInterface
{
    /**
     * 获取查询对象
     *
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select(
            'table.contents.cid',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.created',
            'table.contents.authorId',
            'table.contents.modified',
            'table.contents.type',
            'table.contents.status',
            'table.contents.text',
            'table.contents.commentsNum',
            'table.contents.order',
            'table.contents.template',
            'table.contents.password',
            'table.contents.allowComment',
            'table.contents.allowPing',
            'table.contents.allowFeed',
            'table.contents.parent'
        )->from('table.contents');
    }

    /**
     * 插入内容
     *
     * @param array $rows 内容数组
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        /** 构建插入结构 */
        $insertStruct = [
            'title'        => !isset($rows['title']) || strlen($rows['title']) === 0
                ? null : htmlspecialchars($rows['title']),
            'created'      => !isset($rows['created']) ? $this->options->time : $rows['created'],
            'modified'     => $this->options->time,
            'text'         => !isset($rows['text']) || strlen($rows['text']) === 0 ? null : $rows['text'],
            'order'        => empty($rows['order']) ? 0 : intval($rows['order']),
            'authorId'     => $rows['authorId'] ?? $this->user->uid,
            'template'     => empty($rows['template']) ? null : $rows['template'],
            'type'         => empty($rows['type']) ? 'post' : $rows['type'],
            'status'       => empty($rows['status']) ? 'publish' : $rows['status'],
            'password'     => !isset($rows['password']) || strlen($rows['password']) === 0 ? null : $rows['password'],
            'commentsNum'  => empty($rows['commentsNum']) ? 0 : $rows['commentsNum'],
            'allowComment' => !empty($rows['allowComment']) && 1 == $rows['allowComment'] ? 1 : 0,
            'allowPing'    => !empty($rows['allowPing']) && 1 == $rows['allowPing'] ? 1 : 0,
            'allowFeed'    => !empty($rows['allowFeed']) && 1 == $rows['allowFeed'] ? 1 : 0,
            'parent'       => empty($rows['parent']) ? 0 : intval($rows['parent'])
        ];

        if (!empty($rows['cid'])) {
            $insertStruct['cid'] = $rows['cid'];
        }

        /** 首先插入部分数据 */
        $insertId = $this->db->query($this->db->insert('table.contents')->rows($insertStruct));

        /** 更新缩略名 */
        if ($insertId > 0) {
            $this->applySlug(!isset($rows['slug']) || strlen($rows['slug']) === 0 ? null : $rows['slug'], $insertId);
        }

        return $insertId;
    }

    /**
     * 为内容应用缩略名
     *
     * @param string|null $slug 缩略名
     * @param mixed $cid 内容id
     * @return string
     * @throws Exception
     */
    public function applySlug(?string $slug, $cid): string
    {
        if ($cid instanceof Query) {
            $cid = $this->db->fetchObject($cid->select('cid')
                ->from('table.contents')->limit(1))->cid;
        }

        /** 生成一个非空的缩略名 */
        $slug = Common::slugName($slug, $cid);
        $result = $slug;

        /** 对草稿的slug做特殊处理 */
        $draft = $this->db->fetchObject($this->db->select('type', 'parent')
            ->from('table.contents')->where('cid = ?', $cid));

        if ('_draft' == substr($draft->type, - 6) && $draft->parent) {
            $result = '@' . $result;
        }


        /** 判断是否在数据库中已经存在 */
        $count = 1;
        while (
            $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
                ->from('table.contents')->where('slug = ? AND cid <> ?', $result, $cid))->num > 0
        ) {
            $result = $slug . '-' . $count;
            $count++;
        }

        $this->db->query($this->db->update('table.contents')->rows(['slug' => $result])
            ->where('cid = ?', $cid));

        return $result;
    }

    /**
     * 更新内容
     *
     * @param array $rows 内容数组
     * @param Query $condition 更新条件
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        /** 首先验证写入权限 */
        if (!$this->isWriteable(clone $condition)) {
            return 0;
        }

        /** 构建更新结构 */
        $preUpdateStruct = [
            'title'        => !isset($rows['title']) || strlen($rows['title']) === 0
                ? null : htmlspecialchars($rows['title']),
            'order'        => empty($rows['order']) ? 0 : intval($rows['order']),
            'text'         => !isset($rows['text']) || strlen($rows['text']) === 0 ? null : $rows['text'],
            'template'     => empty($rows['template']) ? null : $rows['template'],
            'type'         => empty($rows['type']) ? 'post' : $rows['type'],
            'status'       => empty($rows['status']) ? 'publish' : $rows['status'],
            'password'     => empty($rows['password']) ? null : $rows['password'],
            'allowComment' => !empty($rows['allowComment']) && 1 == $rows['allowComment'] ? 1 : 0,
            'allowPing'    => !empty($rows['allowPing']) && 1 == $rows['allowPing'] ? 1 : 0,
            'allowFeed'    => !empty($rows['allowFeed']) && 1 == $rows['allowFeed'] ? 1 : 0,
            'parent'       => empty($rows['parent']) ? 0 : intval($rows['parent'])
        ];

        $updateStruct = [];
        foreach ($rows as $key => $val) {
            if (array_key_exists($key, $preUpdateStruct)) {
                $updateStruct[$key] = $preUpdateStruct[$key];
            }
        }

        /** 更新创建时间 */
        if (isset($rows['created'])) {
            $updateStruct['created'] = $rows['created'];
        }

        $updateStruct['modified'] = $this->options->time;

        /** 首先插入部分数据 */
        $updateCondition = clone $condition;
        $updateRows = $this->db->query($condition->update('table.contents')->rows($updateStruct));

        /** 更新缩略名 */
        if ($updateRows > 0 && isset($rows['slug'])) {
            $this->applySlug(!isset($rows['slug']) || strlen($rows['slug']) === 0
                ? null : $rows['slug'], $updateCondition);
        }

        return $updateRows;
    }

    /**
     * 内容是否可以被修改
     *
     * @param Query $condition 条件
     * @return bool
     * @throws Exception
     */
    public function isWriteable(Query $condition): bool
    {
        $post = $this->db->fetchRow($condition->select('authorId')->from('table.contents')->limit(1));
        return $post && ($this->user->pass('editor', true) || $post['authorId'] == $this->user->uid);
    }

    /**
     * 删除内容
     *
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.contents'));
    }

    /**
     * 删除自定义字段
     *
     * @param integer $cid
     * @return integer
     * @throws Exception
     */
    public function deleteFields(int $cid): int
    {
        return $this->db->query($this->db->delete('table.fields')
            ->where('cid = ?', $cid));
    }

    /**
     * 保存自定义字段
     *
     * @param array $fields
     * @param mixed $cid
     * @return void
     * @throws Exception
     */
    public function applyFields(array $fields, $cid)
    {
        $exists = array_flip(array_column($this->db->fetchAll($this->db->select('name')
            ->from('table.fields')->where('cid = ?', $cid)), 'name'));

        foreach ($fields as $name => $value) {
            $type = 'str';

            if (is_array($value) && 2 == count($value)) {
                $type = $value[0];
                $value = $value[1];
            } elseif (strpos($name, ':') > 0) {
                [$type, $name] = explode(':', $name, 2);
            }

            if (!$this->checkFieldName($name)) {
                continue;
            }

            $isFieldReadOnly = Contents::pluginHandle()->trigger($plugged)->isFieldReadOnly($name);
            if ($plugged && $isFieldReadOnly) {
                continue;
            }

            if (isset($exists[$name])) {
                unset($exists[$name]);
            }

            $this->setField($name, $type, $value, $cid);
        }

        foreach ($exists as $name => $value) {
            $this->db->query($this->db->delete('table.fields')
                ->where('cid = ? AND name = ?', $cid, $name));
        }
    }

    /**
     * 检查字段名是否符合要求
     *
     * @param string $name
     * @return boolean
     */
    public function checkFieldName(string $name): bool
    {
        return preg_match("/^[_a-z][_a-z0-9]*$/i", $name);
    }

    /**
     * 设置单个字段
     *
     * @param string $name
     * @param string $type
     * @param string $value
     * @param integer $cid
     * @return integer|bool
     * @throws Exception
     */
    public function setField(string $name, string $type, string $value, int $cid)
    {
        if (
            empty($name) || !$this->checkFieldName($name)
            || !in_array($type, ['str', 'int', 'float'])
        ) {
            return false;
        }

        $exist = $this->db->fetchRow($this->db->select('cid')->from('table.fields')
            ->where('cid = ? AND name = ?', $cid, $name));

        if (empty($exist)) {
            return $this->db->query($this->db->insert('table.fields')
                ->rows([
                    'cid'         => $cid,
                    'name'        => $name,
                    'type'        => $type,
                    'str_value'   => 'str' == $type ? $value : null,
                    'int_value'   => 'int' == $type ? intval($value) : 0,
                    'float_value' => 'float' == $type ? floatval($value) : 0
                ]));
        } else {
            return $this->db->query($this->db->update('table.fields')
                ->rows([
                    'type'        => $type,
                    'str_value'   => 'str' == $type ? $value : null,
                    'int_value'   => 'int' == $type ? intval($value) : 0,
                    'float_value' => 'float' == $type ? floatval($value) : 0
                ])
                ->where('cid = ? AND name = ?', $cid, $name));
        }
    }

    /**
     * 自增一个整形字段
     *
     * @param string $name
     * @param integer $value
     * @param integer $cid
     * @return integer
     * @throws Exception
     */
    public function incrIntField(string $name, int $value, int $cid)
    {
        if (!$this->checkFieldName($name)) {
            return false;
        }

        $exist = $this->db->fetchRow($this->db->select('type')->from('table.fields')
            ->where('cid = ? AND name = ?', $cid, $name));
        $value = intval($value);

        if (empty($exist)) {
            return $this->db->query($this->db->insert('table.fields')
                ->rows([
                    'cid'         => $cid,
                    'name'        => $name,
                    'type'        => 'int',
                    'str_value'   => null,
                    'int_value'   => $value,
                    'float_value' => 0
                ]));
        } else {
            $struct = [
                'str_value'   => null,
                'float_value' => null
            ];

            if ('int' != $exist['type']) {
                $struct['type'] = 'int';
            }

            return $this->db->query($this->db->update('table.fields')
                ->rows($struct)
                ->expression('int_value', 'int_value ' . ($value >= 0 ? '+' : '') . $value)
                ->where('cid = ? AND name = ?', $cid, $name));
        }
    }

    /**
     * 按照条件计算内容数量
     *
     * @param Query $condition 查询对象
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition
            ->select(['COUNT(DISTINCT table.contents.cid)' => 'num'])
            ->from('table.contents')
            ->cleanAttribute('group'))->num;
    }

    /**
     * 获取当前所有自定义模板
     *
     * @return array
     */
    public function getTemplates(): array
    {
        $files = glob($this->options->themeFile($this->options->theme, '*.php'));
        $result = [];

        foreach ($files as $file) {
            $info = Plugin::parseInfo($file);
            $file = basename($file);

            if ('index.php' != $file && 'custom' == $info['title']) {
                $result[$file] = $info['description'];
            }
        }

        return $result;
    }

    /**
     * 将每行的值压入堆栈
     *
     * @param array $value 每行的值
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * 通用过滤器
     *
     * @param array $value 需要过滤的行数据
     * @return array
     */
    public function filter(array $value): array
    {
        /** 处理默认空值 */
        $value['title'] = $value['title'] ?? '';
        $value['text'] = $value['text'] ?? '';
        $value['slug'] = $value['slug'] ?? '';

        /** 取出所有分类 */
        $value['categories'] = $this->db->fetchAll($this->db
            ->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $value['cid'])
            ->where('table.metas.type = ?', 'category'), [Rows::alloc(), 'filter']);

        $value['category'] = '';
        $value['directory'] = [];

        /** 取出第一个分类作为slug条件 */
        if (!empty($value['categories'])) {
            /** 使用自定义排序 */
            usort($value['categories'], function ($a, $b) {
                $field = 'order';
                if ($a['order'] == $b['order']) {
                    $field = 'mid';
                }

                return $a[$field] < $b[$field] ? - 1 : 1;
            });

            $value['category'] = $value['categories'][0]['slug'];

            $value['directory'] = Rows::alloc()
                ->getAllParentsSlug($value['categories'][0]['mid']);
            $value['directory'][] = $value['category'];
        }

        $value['date'] = new Date($value['created']);

        /** 生成日期 */
        $value['year'] = $value['date']->year;
        $value['month'] = $value['date']->month;
        $value['day'] = $value['date']->day;

        /** 生成访问权限 */
        $value['hidden'] = false;

        /** 获取路由类型并判断此类型在路由表中是否存在 */
        $type = $value['type'];
        $routeExists = (null != Router::get($type));

        $tmpSlug = $value['slug'];
        $tmpCategory = $value['category'];
        $tmpDirectory = $value['directory'];
        $value['slug'] = urlencode($value['slug']);
        $value['category'] = urlencode($value['category']);
        $value['directory'] = implode('/', array_map('urlencode', $value['directory']));

        /** 生成静态路径 */
        $value['pathinfo'] = $routeExists ? Router::url($type, $value) : '#';

        /** 生成静态链接 */
        $value['url'] = $value['permalink'] = Common::url($value['pathinfo'], $this->options->index);

        /** 处理附件 */
        if ('attachment' == $type) {
            $content = @unserialize($value['text']);

            //增加数据信息
            $value['attachment'] = new Config($content);
            $value['attachment']->isImage = in_array($content['type'], ['jpg', 'jpeg', 'gif', 'png', 'tiff', 'bmp']);
            $value['attachment']->url = Upload::attachmentHandle($value);

            if ($value['attachment']->isImage) {
                $value['text'] = '<img src="' . $value['attachment']->url . '" alt="' .
                    $value['title'] . '" />';
            } else {
                $value['text'] = '<a href="' . $value['attachment']->url . '" title="' .
                    $value['title'] . '">' . $value['title'] . '</a>';
            }
        }

        /** 处理Markdown **/
        if (isset($value['text'])) {
            $value['isMarkdown'] = (0 === strpos($value['text'], '<!--markdown-->'));
            if ($value['isMarkdown']) {
                $value['text'] = substr($value['text'], 15);
            }
        }

        /** 生成聚合链接 */
        /** RSS 2.0 */
        $value['feedUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $value['feedRssUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $value['feedAtomUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedAtomUrl) : '#';

        $value['slug'] = $tmpSlug;
        $value['category'] = $tmpCategory;
        $value['directory'] = $tmpDirectory;

        /** 处理密码保护流程 */
        if (
            strlen($value['password'] ?? '') > 0 &&
            $value['password'] !== Cookie::get('protectPassword_' . $value['cid']) &&
            $value['authorId'] != $this->user->uid &&
            !$this->user->pass('editor', true)
        ) {
            $value['hidden'] = true;
        }

        $value = Contents::pluginHandle()->filter($value, $this);

        /** 如果访问权限被禁止 */
        if ($value['hidden']) {
            $value['text'] = '<form class="protected" action="' . $this->security->getTokenUrl($value['permalink'])
                . '" method="post">' .
                '<p class="word">' . _t('请输入密码访问') . '</p>' .
                '<p><input type="password" class="text" name="protectPassword" />
            <input type="hidden" name="protectCID" value="' . $value['cid'] . '" />
            <input type="submit" class="submit" value="' . _t('提交') . '" /></p>' .
                '</form>';

            $value['title'] = _t('此内容被密码保护');
            $value['tags'] = [];
            $value['commentsNum'] = 0;
        }

        return $value;
    }

    /**
     * 输出文章发布日期
     *
     * @param string|null $format 日期格式
     */
    public function date(?string $format = null)
    {
        echo $this->date->format(empty($format) ? $this->options->postDateFormat : $format);
    }

    /**
     * 输出文章内容
     *
     * @param mixed $more 文章截取后缀
     */
    public function content($more = false)
    {
        echo false !== $more && false !== strpos($this->text, '<!--more-->') ?
            $this->excerpt
                . "<p class=\"more\"><a href=\"{$this->permalink}\" title=\"{$this->title}\">{$more}</a></p>"
            : $this->content;
    }

    /**
     * 输出文章摘要
     *
     * @param integer $length 摘要截取长度
     * @param string $trim 摘要后缀
     */
    public function excerpt(int $length = 100, string $trim = '...')
    {
        echo Common::subStr(strip_tags($this->excerpt), 0, $length, $trim);
    }

    /**
     * 输出标题
     *
     * @param integer $length 标题截取长度
     * @param string $trim 截取后缀
     */
    public function title(int $length = 0, string $trim = '...')
    {
        $title = Contents::pluginHandle()->trigger($plugged)->title($this->title, $this);
        if (!$plugged) {
            echo $length > 0 ? Common::subStr($this->title, 0, $length, $trim) : $this->title;
        } else {
            echo $title;
        }
    }

    /**
     * 输出文章评论数
     *
     * @param ...$args
     */
    public function commentsNum(...$args)
    {
        if (empty($args)) {
            $args[] = '%d';
        }

        $num = intval($this->commentsNum);
        echo sprintf($args[$num] ?? array_pop($args), $num);
    }

    /**
     * 获取文章权限
     *
     * @param ...$permissions
     */
    public function allow(...$permissions): bool
    {
        $allow = true;

        foreach ($permissions as $permission) {
            $permission = strtolower($permission);

            if ('edit' == $permission) {
                $allow &= ($this->user->pass('editor', true) || $this->authorId == $this->user->uid);
            } else {
                /** 对自动关闭反馈功能的支持 */
                if (
                    ('ping' == $permission || 'comment' == $permission) && $this->options->commentsPostTimeout > 0 &&
                    $this->options->commentsAutoClose
                ) {
                    if ($this->options->time - $this->created > $this->options->commentsPostTimeout) {
                        return false;
                    }
                }

                $allow &= ($this->row['allow' . ucfirst($permission)] == 1) and !$this->hidden;
            }
        }

        return $allow;
    }

    /**
     * 输出文章分类
     *
     * @param string $split 多个分类之间分隔符
     * @param boolean $link 是否输出链接
     * @param string|null $default 如果没有则输出
     */
    public function category(string $split = ',', bool $link = true, ?string $default = null)
    {
        $categories = $this->categories;
        if ($categories) {
            $result = [];

            foreach ($categories as $category) {
                $result[] = $link ? '<a href="' . $category['permalink'] . '">'
                    . $category['name'] . '</a>' : $category['name'];
            }

            echo implode($split, $result);
        } else {
            echo $default;
        }
    }

    /**
     * 输出文章多级分类
     *
     * @param string $split 多个分类之间分隔符
     * @param boolean $link 是否输出链接
     * @param string|null $default 如果没有则输出
     * @throws \Typecho\Widget\Exception
     */
    public function directory(string $split = '/', bool $link = true, ?string $default = null)
    {
        $category = $this->categories[0];
        $directory = Rows::alloc()->getAllParents($category['mid']);
        $directory[] = $category;

        if ($directory) {
            $result = [];

            foreach ($directory as $category) {
                $result[] = $link ? '<a href="' . $category['permalink'] . '">'
                    . $category['name'] . '</a>' : $category['name'];
            }

            echo implode($split, $result);
        } else {
            echo $default;
        }
    }

    /**
     * 输出文章标签
     *
     * @param string $split 多个标签之间分隔符
     * @param boolean $link 是否输出链接
     * @param string|null $default 如果没有则输出
     */
    public function tags(string $split = ',', bool $link = true, ?string $default = null)
    {
        /** 取出tags */
        if ($this->tags) {
            $result = [];
            foreach ($this->tags as $tag) {
                $result[] = $link ? '<a href="' . $tag['permalink'] . '">'
                    . $tag['name'] . '</a>' : $tag['name'];
            }

            echo implode($split, $result);
        } else {
            echo $default;
        }
    }

    /**
     * 输出当前作者
     *
     * @param string $item 需要输出的项目
     */
    public function author(string $item = 'screenName')
    {
        if ($this->have()) {
            echo $this->author->{$item};
        }
    }

    /**
     * 将tags取出
     *
     * @return array
     * @throws Exception
     */
    protected function ___tags(): array
    {
        return $this->db->fetchAll($this->db
            ->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $this->cid)
            ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);
    }

    /**
     * 文章作者
     *
     * @return Users
     */
    protected function ___author(): Users
    {
        return Author::allocWithAlias($this->cid, ['uid' => $this->authorId]);
    }

    /**
     * 获取词义化日期
     *
     * @return string
     */
    protected function ___dateWord(): string
    {
        return $this->date->word();
    }

    /**
     * 获取父id
     *
     * @return int|null
     */
    protected function ___parentId(): ?int
    {
        return $this->row['parent'];
    }

    /**
     * 对文章的简短纯文本描述
     *
     * @return string|null
     */
    protected function ___description(): ?string
    {
        $plainTxt = str_replace("\n", '', trim(strip_tags($this->excerpt)));
        $plainTxt = $plainTxt ? $plainTxt : $this->title;
        return Common::subStr($plainTxt, 0, 100, '...');
    }

    /**
     * ___fields
     *
     * @return Config
     * @throws Exception
     */
    protected function ___fields(): Config
    {
        $fields = [];
        $rows = $this->db->fetchAll($this->db->select()->from('table.fields')
            ->where('cid = ?', $this->cid));

        foreach ($rows as $row) {
            $fields[$row['name']] = $row[$row['type'] . '_value'];
        }

        return new Config($fields);
    }

    /**
     * 获取文章内容摘要
     *
     * @return string|null
     */
    protected function ___excerpt(): ?string
    {
        if ($this->hidden) {
            return $this->text;
        }

        $content = Contents::pluginHandle()->trigger($plugged)->excerpt($this->text, $this);
        if (!$plugged) {
            $content = $this->isMarkdown ? $this->markdown($content)
                : $this->autoP($content);
        }

        $contents = explode('<!--more-->', $content);
        [$excerpt] = $contents;

        return Common::fixHtml(Contents::pluginHandle()->excerptEx($excerpt, $this));
    }

    /**
     * markdown
     *
     * @param string|null $text
     * @return string|null
     */
    public function markdown(?string $text): ?string
    {
        $html = Contents::pluginHandle()->trigger($parsed)->markdown($text);

        if (!$parsed) {
            $html = Markdown::convert($text);
        }

        return $html;
    }

    /**
     * autoP
     *
     * @param string|null $text
     * @return string|null
     */
    public function autoP(?string $text): ?string
    {
        $html = Contents::pluginHandle()->trigger($parsed)->autoP($text);

        if (!$parsed) {
            static $parser;

            if (empty($parser)) {
                $parser = new AutoP();
            }

            $html = $parser->parse($text);
        }

        return $html;
    }

    /**
     * 获取文章内容
     *
     * @return string|null
     */
    protected function ___content(): ?string
    {
        if ($this->hidden) {
            return $this->text;
        }

        $content = Contents::pluginHandle()->trigger($plugged)->content($this->text, $this);

        if (!$plugged) {
            $content = $this->isMarkdown ? $this->markdown($content)
                : $this->autoP($content);
        }

        return Contents::pluginHandle()->contentEx($content, $this);
    }

    /**
     * 输出文章的第一行作为摘要
     *
     * @return string|null
     */
    protected function ___summary(): ?string
    {
        $content = $this->content;
        $parts = preg_split("/(<\/\s*(?:p|blockquote|q|pre|table)\s*>)/i", $content, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (!empty($parts)) {
            $content = $parts[0] . $parts[1];
        }

        return $content;
    }

    /**
     * 锚点id
     *
     * @return string
     */
    protected function ___theId(): string
    {
        return $this->type . '-' . $this->cid;
    }

    /**
     * 回复框id
     *
     * @return string
     */
    protected function ___respondId(): string
    {
        return 'respond-' . $this->theId;
    }

    /**
     * 评论地址
     *
     * @return string
     */
    protected function ___commentUrl(): string
    {
        /** 生成反馈地址 */
        /** 评论 */
        return Router::url(
            'feedback',
            ['type' => 'comment', 'permalink' => $this->pathinfo],
            $this->options->index
        );
    }

    /**
     * trackback地址
     *
     * @return string
     */
    protected function ___trackbackUrl(): string
    {
        return Router::url(
            'feedback',
            ['type' => 'trackback', 'permalink' => $this->pathinfo],
            $this->options->index
        );
    }

    /**
     * 回复地址
     *
     * @return string
     */
    protected function ___responseUrl(): string
    {
        return $this->permalink . '#' . $this->respondId;
    }

    /**
     * 获取页面偏移
     *
     * @param string $column 字段名
     * @param integer $offset 偏移值
     * @param string $type 类型
     * @param string|null $status 状态值
     * @param integer $authorId 作者
     * @param integer $pageSize 分页值
     * @return integer
     * @throws Exception
     */
    protected function getPageOffset(
        string $column,
        int $offset,
        string $type,
        ?string $status = null,
        int $authorId = 0,
        int $pageSize = 20
    ): int {
        $select = $this->db->select(['COUNT(table.contents.cid)' => 'num'])->from('table.contents')
            ->where("table.contents.{$column} > {$offset}")
            ->where(
                "table.contents.type = ? OR (table.contents.type = ? AND table.contents.parent = ?)",
                $type,
                $type . '_draft',
                0
            );

        if (!empty($status)) {
            $select->where("table.contents.status = ?", $status);
        }

        if ($authorId > 0) {
            $select->where('table.contents.authorId = ?', $authorId);
        }

        $count = $this->db->fetchObject($select)->num + 1;
        return ceil($count / $pageSize);
    }
}
