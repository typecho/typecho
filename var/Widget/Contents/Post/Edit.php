<?php

namespace Widget\Contents\Post;

use Typecho\Common;
use Typecho\Config;
use Typecho\Validate;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Typecho\Db\Exception as DbException;
use Typecho\Date as TypechoDate;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑文章组件
 *
 * @property-read array|null $draft
 */
class Edit extends Contents implements ActionInterface
{
    /**
     * 自定义字段的hook名称
     *
     * @var string
     */
    protected $themeCustomFieldsHook = 'themePostFields';

    /**
     * 执行函数
     *
     * @throws Exception|DbException
     */
    public function execute()
    {
        /** 必须为贡献者以上权限 */
        $this->user->pass('contributor');

        /** 获取文章内容 */
        if (!empty($this->request->cid)) {
            $this->db->fetchRow($this->select()
                ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
                ->where('table.contents.cid = ?', $this->request->filter('int')->cid)
                ->limit(1), [$this, 'push']);

            if ('post_draft' == $this->type && $this->parent) {
                $this->response->redirect(
                    Common::url('write-post.php?cid=' . $this->parent, $this->options->adminUrl)
                );
            }

            if (!$this->have()) {
                throw new Exception(_t('文章不存在'), 404);
            } elseif (!$this->allow('edit')) {
                throw new Exception(_t('没有编辑权限'), 403);
            }
        }
    }

    /**
     * 获取文章权限
     *
     * @param mixed ...$permissions
     * @return bool
     * @throws Exception|DbException
     */
    public function allow(...$permissions): bool
    {
        $allow = true;

        foreach ($permissions as $permission) {
            $permission = strtolower($permission);

            if ('edit' == $permission) {
                $allow &= ($this->user->pass('editor', true) || $this->authorId == $this->user->uid);
            } else {
                $permission = 'allow' . ucfirst(strtolower($permission));
                $optionPermission = 'default' . ucfirst($permission);
                $allow &= ($this->{$permission} ?? $this->options->{$optionPermission});
            }
        }

        return $allow;
    }

    /**
     * 过滤堆栈
     *
     * @param array $value 每行的值
     * @return array
     * @throws DbException
     */
    public function filter(array $value): array
    {
        if ('post' == $value['type'] || 'page' == $value['type']) {
            $draft = $this->db->fetchRow(Contents::alloc()->select()
                ->where(
                    'table.contents.parent = ? AND table.contents.type = ?',
                    $value['cid'],
                    $value['type'] . '_draft'
                )
                ->limit(1));

            if (!empty($draft)) {
                $draft['slug'] = ltrim($draft['slug'], '@');
                $draft['type'] = $value['type'];

                $draft = parent::filter($draft);

                $draft['tags'] = $this->db->fetchAll($this->db
                    ->select()->from('table.metas')
                    ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                    ->where('table.relationships.cid = ?', $draft['cid'])
                    ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);
                $draft['cid'] = $value['cid'];

                return $draft;
            }
        }

        return parent::filter($value);
    }

    /**
     * 输出文章发布日期
     *
     * @param string $format 日期格式
     * @return void
     */
    public function date($format = null)
    {
        if (isset($this->created)) {
            parent::date($format);
        } else {
            echo date($format, $this->options->time + $this->options->timezone - $this->options->serverTimezone);
        }
    }

    /**
     * 获取网页标题
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('编辑 %s', $this->title);
    }

    /**
     * getFieldItems
     *
     * @throws DbException
     */
    public function getFieldItems(): array
    {
        $fields = [];

        if ($this->have()) {
            $defaultFields = $this->getDefaultFieldItems();
            $rows = $this->db->fetchAll($this->db->select()->from('table.fields')
                ->where('cid = ?', $this->cid));

            foreach ($rows as $row) {
                $isFieldReadOnly = Contents::pluginHandle()
                    ->trigger($plugged)->isFieldReadOnly($row['name']);

                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                if (!isset($defaultFields[$row['name']])) {
                    $fields[] = $row;
                }
            }
        }

        return $fields;
    }

    /**
     * getDefaultFieldItems
     *
     * @return array
     */
    public function getDefaultFieldItems(): array
    {
        $defaultFields = [];
        $configFile = $this->options->themeFile($this->options->theme, 'functions.php');
        $layout = new Layout();
        $fields = new Config();

        if ($this->have()) {
            $fields = $this->fields;
        }

        self::pluginHandle()->getDefaultFieldItems($layout);

        if (file_exists($configFile)) {
            require_once $configFile;

            if (function_exists('themeFields')) {
                themeFields($layout);
            }

            if (function_exists($this->themeCustomFieldsHook)) {
                call_user_func($this->themeCustomFieldsHook, $layout);
            }
        }

        $items = $layout->getItems();
        foreach ($items as $item) {
            if ($item instanceof Element) {
                $name = $item->input->getAttribute('name');

                $isFieldReadOnly = Contents::pluginHandle()
                    ->trigger($plugged)->isFieldReadOnly($name);
                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                if (preg_match("/^fields\[(.+)\]$/", $name, $matches)) {
                    $name = $matches[1];
                } else {
                    foreach ($item->inputs as $input) {
                        $input->setAttribute('name', 'fields[' . $name . ']');
                    }
                }

                if (isset($fields->{$name})) {
                    $item->value($fields->{$name});
                }

                $elements = $item->container->getItems();
                array_shift($elements);
                $div = new Layout('div');

                foreach ($elements as $el) {
                    $div->addItem($el);
                }

                $defaultFields[$name] = [$item->label, $div];
            }
        }

        return $defaultFields;
    }

    /**
     * 发布文章
     */
    public function writePost()
    {
        $contents = $this->request->from(
            'password',
            'allowComment',
            'allowPing',
            'allowFeed',
            'slug',
            'tags',
            'text',
            'visibility'
        );

        $contents['category'] = $this->request->getArray('category');
        $contents['title'] = $this->request->get('title', _t('未命名文档'));
        $contents['created'] = $this->getCreated();

        if ($this->request->markdown && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = self::pluginHandle()->write($contents, $this);

        if ($this->request->is('do=publish')) {
            /** 重新发布已经存在的文章 */
            $contents['type'] = 'post';
            $this->publish($contents);

            // 完成发布插件接口
            self::pluginHandle()->finishPublish($contents, $this);

            /** 发送ping */
            $trackback = array_unique(preg_split("/(\r|\n|\r\n)/", trim($this->request->trackback)));
            Service::alloc()->sendPing($this, $trackback);

            /** 设置提示信息 */
            Notice::alloc()->set('post' == $this->type ?
                _t('文章 "<a href="%s">%s</a>" 已经发布', $this->permalink, $this->title) :
                _t('文章 "%s" 等待审核', $this->title), 'success');

            /** 设置高亮 */
            Notice::alloc()->highlight($this->theId);

            /** 获取页面偏移 */
            $pageQuery = $this->getPageOffsetQuery($this->cid);

            /** 页面跳转 */
            $this->response->redirect(Common::url('manage-posts.php?' . $pageQuery, $this->options->adminUrl));
        } else {
            /** 保存文章 */
            $contents['type'] = 'post_draft';
            $this->save($contents);

            // 完成保存插件接口
            self::pluginHandle()->finishSave($contents, $this);

            /** 设置高亮 */
            Notice::alloc()->highlight($this->cid);

            if ($this->request->isAjax()) {
                $created = new TypechoDate();
                $this->response->throwJson([
                    'success' => 1,
                    'time'    => $created->format('H:i:s A'),
                    'cid'     => $this->cid,
                    'draftId' => $this->draft['cid']
                ]);
            } else {
                /** 设置提示信息 */
                Notice::alloc()->set(_t('草稿 "%s" 已经被保存', $this->title), 'success');

                /** 返回原页面 */
                $this->response->redirect(Common::url('write-post.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * 根据提交值获取created字段值
     *
     * @return integer
     */
    protected function getCreated(): int
    {
        $created = $this->options->time;
        if (!empty($this->request->created)) {
            $created = $this->request->created;
        } elseif (!empty($this->request->date)) {
            $dstOffset = !empty($this->request->dst) ? $this->request->dst : 0;
            $timezoneSymbol = $this->options->timezone >= 0 ? '+' : '-';
            $timezoneOffset = abs($this->options->timezone);
            $timezone = $timezoneSymbol . str_pad($timezoneOffset / 3600, 2, '0', STR_PAD_LEFT) . ':00';
            [$date, $time] = explode(' ', $this->request->date);

            $created = strtotime("{$date}T{$time}{$timezone}") - $dstOffset;
        } elseif (!empty($this->request->year) && !empty($this->request->month) && !empty($this->request->day)) {
            $second = intval($this->request->get('sec', date('s')));
            $min = intval($this->request->get('min', date('i')));
            $hour = intval($this->request->get('hour', date('H')));

            $year = intval($this->request->year);
            $month = intval($this->request->month);
            $day = intval($this->request->day);

            $created = mktime($hour, $min, $second, $month, $day, $year)
                - $this->options->timezone + $this->options->serverTimezone;
        } elseif ($this->have() && $this->created > 0) {
            //如果是修改文章
            $created = $this->created;
        } elseif ($this->request->is('do=save')) {
            // 如果是草稿而且没有任何输入则保持原状
            $created = 0;
        }

        return $created;
    }

    /**
     * 发布内容
     *
     * @param array $contents 内容结构
     * @throws DbException|Exception
     */
    protected function publish(array $contents)
    {
        /** 发布内容, 检查是否具有直接发布的权限 */
        if ($this->user->pass('editor', true)) {
            if (empty($contents['visibility'])) {
                $contents['status'] = 'publish';
            } elseif (
                !in_array($contents['visibility'], ['private', 'waiting', 'publish', 'hidden'])
            ) {
                if (empty($contents['password']) || 'password' != $contents['visibility']) {
                    $contents['password'] = '';
                }
                $contents['status'] = 'publish';
            } else {
                $contents['status'] = $contents['visibility'];
                $contents['password'] = '';
            }
        } else {
            $contents['status'] = 'waiting';
            $contents['password'] = '';
        }

        /** 真实的内容id */
        $realId = 0;

        /** 是否是从草稿状态发布 */
        $isDraftToPublish = ('post_draft' == $this->type);

        $isBeforePublish = ('publish' == $this->status);
        $isAfterPublish = ('publish' == $contents['status']);

        /** 重新发布现有内容 */
        if ($this->have()) {

            /** 如果它本身不是草稿, 需要删除其草稿 */
            if (!$isDraftToPublish && $this->draft) {
                $cid = $this->draft['cid'];
                $this->deleteDraft($cid);
                $this->deleteFields($cid);
            }

            /** 直接将草稿状态更改 */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->cid))) {
                $realId = $this->cid;
            }
        } else {
            /** 发布一个新内容 */
            $realId = $this->insert($contents);
        }

        if ($realId > 0) {
            /** 插入分类 */
            if (array_key_exists('category', $contents)) {
                $this->setCategories(
                    $realId,
                    !empty($contents['category']) && is_array($contents['category'])
                        ? $contents['category'] : [$this->options->defaultCategory],
                    !$isDraftToPublish && $isBeforePublish,
                    $isAfterPublish
                );
            }

            /** 插入标签 */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], !$isDraftToPublish && $isBeforePublish, $isAfterPublish);
            }

            /** 同步附件 */
            $this->attach($realId);

            /** 保存自定义字段 */
            $this->applyFields($this->getFields(), $realId);

            $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $realId)->limit(1), [$this, 'push']);
        }
    }

    /**
     * 删除草稿
     *
     * @param integer $cid 草稿id
     * @throws DbException
     */
    protected function deleteDraft($cid)
    {
        $this->delete($this->db->sql()->where('cid = ?', $cid));

        /** 删除草稿分类 */
        $this->setCategories($cid, [], false, false);

        /** 删除标签 */
        $this->setTags($cid, null, false, false);
    }

    /**
     * 设置分类
     *
     * @param integer $cid 内容id
     * @param array $categories 分类id的集合数组
     * @param boolean $beforeCount 是否参与计数
     * @param boolean $afterCount 是否参与计数
     * @throws DbException
     */
    public function setCategories(int $cid, array $categories, bool $beforeCount = true, bool $afterCount = true)
    {
        $categories = array_unique(array_map('trim', $categories));

        /** 取出已有category */
        $existCategories = array_column(
            $this->db->fetchAll(
                $this->db->select('table.metas.mid')
                    ->from('table.metas')
                    ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                    ->where('table.relationships.cid = ?', $cid)
                    ->where('table.metas.type = ?', 'category')
            ),
            'mid'
        );

        /** 删除已有category */
        if ($existCategories) {
            foreach ($existCategories as $category) {
                $this->db->query($this->db->delete('table.relationships')
                    ->where('cid = ?', $cid)
                    ->where('mid = ?', $category));

                if ($beforeCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count - 1')
                        ->where('mid = ?', $category));
                }
            }
        }

        /** 插入category */
        if ($categories) {
            foreach ($categories as $category) {
                /** 如果分类不存在 */
                if (
                    !$this->db->fetchRow(
                        $this->db->select('mid')
                        ->from('table.metas')
                        ->where('mid = ?', $category)
                        ->limit(1)
                    )
                ) {
                    continue;
                }

                $this->db->query($this->db->insert('table.relationships')
                    ->rows([
                        'mid' => $category,
                        'cid' => $cid
                    ]));

                if ($afterCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count + 1')
                        ->where('mid = ?', $category));
                }
            }
        }
    }

    /**
     * 设置内容标签
     *
     * @param integer $cid
     * @param string|null $tags
     * @param boolean $beforeCount 是否参与计数
     * @param boolean $afterCount 是否参与计数
     * @throws DbException
     */
    public function setTags(int $cid, ?string $tags, bool $beforeCount = true, bool $afterCount = true)
    {
        $tags = str_replace('，', ',', $tags);
        $tags = array_unique(array_map('trim', explode(',', $tags)));
        $tags = array_filter($tags, [Validate::class, 'xssCheck']);

        /** 取出已有tag */
        $existTags = array_column(
            $this->db->fetchAll(
                $this->db->select('table.metas.mid')
                ->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $cid)
                ->where('table.metas.type = ?', 'tag')
            ),
            'mid'
        );

        /** 删除已有tag */
        if ($existTags) {
            foreach ($existTags as $tag) {
                if (0 == strlen($tag)) {
                    continue;
                }

                $this->db->query($this->db->delete('table.relationships')
                    ->where('cid = ?', $cid)
                    ->where('mid = ?', $tag));

                if ($beforeCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count - 1')
                        ->where('mid = ?', $tag));
                }
            }
        }

        /** 取出插入tag */
        $insertTags = Metas::alloc()->scanTags($tags);

        /** 插入tag */
        if ($insertTags) {
            foreach ($insertTags as $tag) {
                if (0 == strlen($tag)) {
                    continue;
                }

                $this->db->query($this->db->insert('table.relationships')
                    ->rows([
                        'mid' => $tag,
                        'cid' => $cid
                    ]));

                if ($afterCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count + 1')
                        ->where('mid = ?', $tag));
                }
            }
        }
    }

    /**
     * 同步附件
     *
     * @param integer $cid 内容id
     * @throws DbException
     */
    protected function attach(int $cid)
    {
        $attachments = $this->request->getArray('attachment');
        if (!empty($attachments)) {
            foreach ($attachments as $key => $attachment) {
                $this->db->query($this->db->update('table.contents')->rows([
                    'parent' => $cid,
                    'status' => 'publish',
                    'order'  => $key + 1
                ])->where('cid = ? AND type = ?', $attachment, 'attachment'));
            }
        }
    }

    /**
     * getFields
     *
     * @return array
     */
    protected function getFields(): array
    {
        $fields = [];
        $fieldNames = $this->request->getArray('fieldNames');

        if (!empty($fieldNames)) {
            $data = [
                'fieldNames'  => $this->request->getArray('fieldNames'),
                'fieldTypes'  => $this->request->getArray('fieldTypes'),
                'fieldValues' => $this->request->getArray('fieldValues')
            ];
            foreach ($data['fieldNames'] as $key => $val) {
                $val = trim($val);

                if (0 == strlen($val)) {
                    continue;
                }

                $fields[$val] = [$data['fieldTypes'][$key], $data['fieldValues'][$key]];
            }
        }

        $customFields = $this->request->getArray('fields');
        if (!empty($customFields)) {
            $fields = array_merge($fields, $customFields);
        }

        return $fields;
    }

    /**
     * 获取页面偏移的URL Query
     *
     * @param integer $cid 文章id
     * @param string|null $status 状态
     * @return string
     * @throws DbException
     */
    protected function getPageOffsetQuery(int $cid, ?string $status = null): string
    {
        return 'page=' . $this->getPageOffset(
            'cid',
            $cid,
            'post',
            $status,
            'on' == $this->request->__typecho_all_posts ? 0 : $this->user->uid
        );
    }

    /**
     * 保存内容
     *
     * @param array $contents 内容结构
     * @throws DbException|Exception
     */
    protected function save(array $contents)
    {
        /** 发布内容, 检查是否具有直接发布的权限 */
        if ($this->user->pass('editor', true)) {
            if (empty($contents['visibility'])) {
                $contents['status'] = 'publish';
            } elseif (
                !in_array($contents['visibility'], ['private', 'waiting', 'publish', 'hidden'])
            ) {
                if (empty($contents['password']) || 'password' != $contents['visibility']) {
                    $contents['password'] = '';
                }
                $contents['status'] = 'publish';
            } else {
                $contents['status'] = $contents['visibility'];
                $contents['password'] = '';
            }
        } else {
            $contents['status'] = 'waiting';
            $contents['password'] = '';
        }

        /** 真实的内容id */
        $realId = 0;

        /** 如果草稿已经存在 */
        if ($this->draft) {

            /** 直接将草稿状态更改 */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->draft['cid']))) {
                $realId = $this->draft['cid'];
            }
        } else {
            if ($this->have()) {
                $contents['parent'] = $this->cid;
            }

            /** 发布一个新内容 */
            $realId = $this->insert($contents);

            if (!$this->have()) {
                $this->db->fetchRow(
                    $this->select()->where('table.contents.cid = ?', $realId)->limit(1),
                    [$this, 'push']
                );
            }
        }

        if ($realId > 0) {
            /** 插入分类 */
            if (array_key_exists('category', $contents)) {
                $this->setCategories($realId, !empty($contents['category']) && is_array($contents['category']) ?
                    $contents['category'] : [$this->options->defaultCategory], false, false);
            }

            /** 插入标签 */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], false, false);
            }

            /** 同步附件 */
            $this->attach($this->cid);

            /** 保存自定义字段 */
            $this->applyFields($this->getFields(), $realId);
        }
    }

    /**
     * 标记文章
     *
     * @throws DbException
     */
    public function markPost()
    {
        $status = $this->request->get('status');
        $statusList = [
            'publish' => _t('公开'),
            'private' => _t('私密'),
            'hidden'  => _t('隐藏'),
            'waiting' => _t('待审核')
        ];

        if (!isset($statusList[$status])) {
            $this->response->goBack();
        }

        $posts = $this->request->filter('int')->getArray('cid');
        $markCount = 0;

        foreach ($posts as $post) {
            // 标记插件接口
            self::pluginHandle()->mark($status, $post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject)) {

                /** 标记状态 */
                $this->db->query($condition->update('table.contents')->rows(['status' => $status]));

                // 刷新Metas
                if ($postObject->type == 'post') {
                    $op = null;

                    if ($status == 'publish' && $postObject->status != 'publish') {
                        $op = '+';
                    } elseif ($status != 'publish' && $postObject->status == 'publish') {
                        $op = '-';
                    }

                    if (!empty($op)) {
                        $metas = $this->db->fetchAll(
                            $this->db->select()->from('table.relationships')->where('cid = ?', $post)
                        );
                        foreach ($metas as $meta) {
                            $this->db->query($this->db->update('table.metas')
                                ->expression('count', 'count ' . $op . ' 1')
                                ->where('mid = ? AND (type = ? OR type = ?)', $meta['mid'], 'category', 'tag'));
                        }
                    }
                }

                // 处理草稿
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                    ->limit(1));

                if (!empty($draft)) {
                    $this->db->query($this->db->update('table.contents')->rows(['status' => $status])
                        ->where('cid = ?', $draft['cid']));
                }

                // 完成标记插件接口
                self::pluginHandle()->finishMark($status, $post, $this);

                $markCount++;
            }

            unset($condition);
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $markCount > 0 ? _t('文章已经被标记为<strong>%s</strong>', $statusList[$status]) : _t('没有文章被标记'),
                $markCount > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 删除文章
     *
     * @throws DbException
     */
    public function deletePost()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            // 删除插件接口
            self::pluginHandle()->delete($post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject) && $this->delete($condition)) {

                /** 删除分类 */
                $this->setCategories($post, [], 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** 删除标签 */
                $this->setTags($post, null, 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** 删除评论 */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $post));

                /** 解除附件关联 */
                $this->unAttach($post);

                /** 删除草稿 */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                    ->limit(1));

                /** 删除自定义字段 */
                $this->deleteFields($post);

                if ($draft) {
                    $this->deleteDraft($draft['cid']);
                    $this->deleteFields($draft['cid']);
                }

                // 完成删除插件接口
                self::pluginHandle()->finishDelete($post, $this);

                $deleteCount++;
            }

            unset($condition);
        }

        // 清理标签
        if ($deleteCount > 0) {
            Metas::alloc()->clearTags();
        }

        /** 设置提示信息 */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('文章已经被删除') : _t('没有文章被删除'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 取消附件关联
     *
     * @param integer $cid 内容id
     * @throws DbException
     */
    protected function unAttach($cid)
    {
        $this->db->query($this->db->update('table.contents')->rows(['parent' => 0, 'status' => 'publish'])
            ->where('parent = ? AND type = ?', $cid, 'attachment'));
    }

    /**
     * 删除文章所属草稿
     *
     * @throws DbException
     */
    public function deletePostDraft()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            /** 删除草稿 */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                ->limit(1));

            if ($draft) {
                $this->deleteDraft($draft['cid']);
                $this->deleteFields($draft['cid']);
                $deleteCount++;
            }
        }

        /** 设置提示信息 */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('草稿已经被删除') : _t('没有草稿被删除'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 绑定动作
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))->writePost();
        $this->on($this->request->is('do=delete'))->deletePost();
        $this->on($this->request->is('do=mark'))->markPost();
        $this->on($this->request->is('do=deleteDraft'))->deletePostDraft();

        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * 将tags取出
     *
     * @return array
     * @throws DbException
     */
    protected function ___tags(): array
    {
        if ($this->have()) {
            return $this->db->fetchAll($this->db
                ->select()->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $this->cid)
                ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);
        }

        return [];
    }

    /**
     * 获取当前时间
     *
     * @return TypechoDate
     */
    protected function ___date(): TypechoDate
    {
        return new TypechoDate();
    }

    /**
     * 当前文章的草稿
     *
     * @return array|null
     * @throws DbException
     */
    protected function ___draft(): ?array
    {
        if ($this->have()) {
            if ('post_draft' == $this->type || 'page_draft' == $this->type) {
                return $this->row;
            } else {
                return $this->db->fetchRow(Contents::alloc()->select()
                    ->where(
                        'table.contents.parent = ? AND (table.contents.type = ? OR table.contents.type = ?)',
                        $this->cid,
                        'post_draft',
                        'page_draft'
                    )
                    ->limit(1), [Contents::alloc(), 'filter']);
            }
        }

        return null;
    }
}
