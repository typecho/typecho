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
use Typecho\Router\ParamsDelegateInterface;
use Typecho\Widget;
use Utils\AutoP;
use Utils\Markdown;
use Widget\Base;
use Widget\Metas\Category\Rows;
use Widget\Upload;
use Widget\Users\Author;
use Widget\Metas\Category\Related as CategoryRelated;
use Widget\Metas\Tag\Related as TagRelated;

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
 * @property-read Users $author
 * @property-read string $permalink
 * @property-read string $path
 * @property-read string $url
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 * @property-read bool $isMarkdown
 * @property-read bool $hidden
 * @property-read Date $date
 * @property-read string $dateWord
 * @property-read string[] $directory
 * @property-read array[] $tags
 * @property-read array[] $categories
 * @property-read string $excerpt
 * @property-read string $plainExcerpt
 * @property-read string $summary
 * @property-read string $content
 * @property-read Config $fields
 * @property-read Config $attachment
 * @property-read string $theId
 * @property-read string $respondId
 * @property-read string $commentUrl
 * @property-read string $trackbackUrl
 * @property-read string $responseUrl
 * @property-read string $year
 * @property-read string $month
 * @property-read string $day
 */
class Contents extends Base implements QueryInterface, RowFilterInterface, PrimaryKeyInterface, ParamsDelegateInterface
{
    /**
     * @return string 获取主键
     */
    public function getPrimaryKey(): string
    {
        return 'cid';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getRouterParam(string $key): string
    {
        switch ($key) {
            case 'cid':
                return $this->cid;
            case 'slug':
                return urlencode($this->slug);
            case 'directory':
                return implode('/', array_map('urlencode', $this->directory));
            case 'category':
                return empty($this->categories) ? '' : urlencode($this->categories[0]['slug']);
            case 'year':
                return $this->date->year;
            case 'month':
                return $this->date->month;
            case 'day':
                return $this->date->day;
            default:
                return '{' . $key . '}';
        }
    }

    /**
     * 获取查询对象
     *
     * @param mixed $fields
     * @return Query
     */
    public function select(...$fields): Query
    {
        return $this->db->select(...$fields)->from('table.contents');
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
            'created'      => empty($rows['created']) ? $this->options->time : $rows['created'],
            'modified'     => $this->options->time,
            'text'         => Common::strBy($rows['text'] ?? null),
            'order'        => empty($rows['order']) ? 0 : intval($rows['order']),
            'authorId'     => $rows['authorId'] ?? $this->user->uid,
            'template'     => Common::strBy($rows['template'] ?? null),
            'type'         => Common::strBy($rows['type'] ?? null, 'post'),
            'status'       => Common::strBy($rows['status'] ?? null, 'publish'),
            'password'     => Common::strBy($rows['password'] ?? null),
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
            $this->applySlug(Common::strBy($rows['slug'] ?? null), $insertId, $insertStruct['title']);
        }

        return $insertId;
    }

    /**
     * 为内容应用缩略名
     *
     * @param string|null $slug 缩略名
     * @param mixed $cid 内容id
     * @param string $title 标题
     * @return string
     * @throws Exception
     */
    public function applySlug(?string $slug, $cid, string $title = ''): string
    {
        if ($cid instanceof Query) {
            $cid = $this->db->fetchObject($cid->select('cid')
                ->from('table.contents')->limit(1))->cid;
        }

        /** 生成一个非空的缩略名 */
        if ((!isset($slug) || strlen($slug) === 0) && preg_match_all("/\w+/", $title, $matches)) {
            $slug = implode('-', $matches[0]);
        }

        $slug = Common::slugName($slug, $cid);
        $result = $slug;

        /** 对草稿的slug做特殊处理 */
        $draft = $this->db->fetchObject($this->db->select('type', 'parent')
            ->from('table.contents')->where('cid = ?', $cid));

        if (preg_match("/_draft$/", $draft->type) && $draft->parent) {
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
            'text'         => Common::strBy($rows['text'] ?? null),
            'template'     => Common::strBy($rows['template'] ?? null),
            'type'         => Common::strBy($rows['type'] ?? null, 'post'),
            'status'       => Common::strBy($rows['status'] ?? null, 'publish'),
            'password'     => Common::strBy($rows['password'] ?? null),
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
            $this->applySlug(strlen($rows['slug']) === 0 ? null : $rows['slug'], $updateCondition);
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
     * @param array $row 需要过滤的行数据
     * @return array
     */
    public function filter(array $row): array
    {
        /** 处理默认空值 */
        $row['title'] = $row['title'] ?? '';
        $row['text'] = $row['text'] ?? '';
        $row['slug'] = $row['slug'] ?? '';
        $row['password'] = $row['password'] ?? '';
        $row['date'] = new Date($row['created']);

        return Contents::pluginHandle()->call('filter', $row, $this);
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
        $title = Contents::pluginHandle()->trigger($plugged)->call('title', $this->title, $this);
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
        if (!empty($this->categories)) {
            $result = [];

            foreach ($this->categories as $category) {
                $result[] = $link ? "<a href=\"{$category['permalink']}\">{$category['name']}</a>" : $category['name'];
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
     * @throws Widget\Exception
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
        if (!empty($this->tags)) {
            $result = [];

            foreach ($this->tags as $tag) {
                $result[] = $link ? "<a href=\"{$tag['permalink']}\">{$tag['name']}</a>" : $tag['name'];
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
     * @return string
     */
    protected function ___title(): string
    {
        return $this->hidden ? _t('此内容被密码保护') : $this->row['title'];
    }

    /**
     * @return string
     */
    protected function ___text(): string
    {
        if ('attachment' == $this->type) {
            if ($this->attachment->isImage) {
                return '<img src="' . $this->attachment->url . '" alt="' .
                    $this->title . '" />';
            } else {
                return '<a href="' . $this->attachment->url . '" title="' .
                    $this->title . '">' . $this->title . '</a>';
            }
        } elseif ($this->hidden) {
            return '<form class="protected" action="' . $this->security->getTokenUrl($this->permalink)
                . '" method="post">' .
                '<p class="word">' . _t('请输入密码访问') . '</p>' .
                '<p><input type="password" class="text" name="protectPassword" />
            <input type="hidden" name="protectCID" value="' . $this->cid . '" />
            <input type="submit" class="submit" value="' . _t('提交') . '" /></p>' .
                '</form>';
        }

        return $this->isMarkdown ? substr($this->row['text'], 15) : $this->row['text'];
    }

    /**
     * @return bool
     */
    protected function ___isMarkdown(): bool
    {
        return 0 === strpos($this->row['text'], '<!--markdown-->');
    }

    /**
     * 是否为隐藏文章
     *
     * @return bool
     */
    protected function ___hidden(): bool
    {
        if (
            strlen($this->password) > 0 &&
            $this->password !== Cookie::get('protectPassword_' . $this->cid) &&
            $this->authorId != $this->user->uid &&
            !$this->user->pass('editor', true)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function ___path(): string
    {
        return Router::url($this->type, $this);
    }

    /**
     * @return string
     */
    protected function ___permalink(): string
    {
        return Common::url($this->path, $this->options->index);
    }

    /**
     * @return string
     */
    protected function ___url(): string
    {
        return $this->permalink;
    }

    /**
     * @return string
     */
    protected function ___feedUrl(): string
    {
        return Router::url($this->type, $this, $this->options->feedUrl);
    }

    /**
     * @return string
     */
    protected function ___feedRssUrl(): string
    {
        return Router::url($this->type, $this, $this->options->feedRssUrl);
    }

    /**
     * @return string
     */
    protected function ___feedAtomUrl(): string
    {
        return Router::url($this->type, $this, $this->options->feedAtomUrl);
    }

    /**
     * 多级目录结构
     *
     * @return array
     */
    protected function ___directory(): array
    {
        $directory = [];

        if (!empty($this->categories)) {
            $directory = Rows::alloc()->getAllParentsSlug($this->categories[0]['mid']);
            $directory[] = $this->categories[0]['slug'];
        }

        return $directory;
    }

    /**
     * @return array
     */
    protected function ___categories(): array
    {
        return CategoryRelated::allocWithAlias($this->cid, ['cid' => $this->cid])
            ->toArray(['mid', 'name', 'slug', 'description', 'order', 'parent', 'count', 'permalink']);
    }

    /**
     * 将tags取出
     *
     * @return array
     */
    protected function ___tags(): array
    {
        return TagRelated::allocWithAlias($this->cid, ['cid' => $this->cid])
            ->toArray(['mid', 'name', 'slug', 'description', 'count', 'permalink']);
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
     * 对文章的简短纯文本描述
     *
     * @deprecated
     * @return string|null
     */
    protected function ___description(): ?string
    {
        return $this->plainExcerpt;
    }

    /**
     * @return Config|null
     */
    protected function ___attachment(): ?Config
    {
        if ('attachment' == $this->type) {
            $content = json_decode($this->row['text'], true);

            //增加数据信息
            $attachment = new Config($content);
            $attachment->isImage = in_array($content['type'], [
                'jpg', 'jpeg', 'gif', 'png', 'tiff', 'bmp', 'webp', 'avif'
            ]);
            $attachment->url = Upload::attachmentHandle($attachment);

            return $attachment;
        }

        return null;
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
            $value = 'json' == $row['type'] ? json_decode($row['str_value'], true) : $row[$row['type'] . '_value'];
            $fields[$row['name']] = $value;
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

        $content = Contents::pluginHandle()->call('excerpt', $this->content, $this);
        [$excerpt] = explode('<!--more-->', $content);

        return Common::fixHtml(Contents::pluginHandle()->call('excerptEx', $excerpt, $this));
    }

    /**
     * 对文章的简短纯文本描述
     *
     * @return string|null
     */
    protected function ___plainExcerpt(): ?string
    {
        $plainText = str_replace("\n", '', trim(strip_tags($this->excerpt)));
        $plainText = $plainText ?: $this->title;
        return Common::subStr($plainText, 0, 100);
    }

    /**
     * markdown
     *
     * @param string|null $text
     * @return string|null
     */
    protected function markdown(?string $text): ?string
    {
        $html = Contents::pluginHandle()->trigger($parsed)->call('markdown', $text);

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
    protected function autoP(?string $text): ?string
    {
        $html = Contents::pluginHandle()->trigger($parsed)->call('autoP', $text);

        if (!$parsed && $text) {
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

        $content = Contents::pluginHandle()->trigger($plugged)->call('content', $this->text, $this);

        if (!$plugged) {
            $content = $this->isMarkdown ? $this->markdown($content)
                : $this->autoP($content);
        }

        return Contents::pluginHandle()->call('contentEx', $content, $this);
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
            ['type' => 'comment', 'permalink' => $this->path],
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
            ['type' => 'trackback', 'permalink' => $this->path],
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
     * @return string
     */
    protected function ___year(): string
    {
        return $this->date->year;
    }

    /**
     * @return string
     */
    protected function ___month(): string
    {
        return $this->date->month;
    }

    /**
     * @return string
     */
    protected function ___day(): string
    {
        return $this->date->day;
    }
}
