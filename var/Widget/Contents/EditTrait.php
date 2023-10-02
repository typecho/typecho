<?php

namespace Widget\Contents;

use Typecho\Common;
use Typecho\Config;
use Typecho\Db\Exception as DbException;
use Typecho\Validate;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;
use Widget\Base\Contents;
use Widget\Base\Metas;

/**
 * 内容编辑组件
 */
trait EditTrait
{
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
                ->where('cid = ?', isset($this->draft) ? $this->draft['cid'] : $this->cid));

            foreach ($rows as $row) {
                $isFieldReadOnly = Contents::pluginHandle()
                    ->trigger($plugged)->call('isFieldReadOnly', $row['name']);

                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                $isFieldReadOnly = static::pluginHandle()
                    ->trigger($plugged)->call('isFieldReadOnly', $row['name']);

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

        Contents::pluginHandle()->call('getDefaultFieldItems', $layout);
        static::pluginHandle()->call('getDefaultFieldItems', $layout);

        if (file_exists($configFile)) {
            require_once $configFile;

            if (function_exists('themeFields')) {
                themeFields($layout);
            }

            $func = $this->getThemeFieldsHook();
            if (function_exists($func)) {
                call_user_func($func, $layout);
            }
        }

        $items = $layout->getItems();
        foreach ($items as $item) {
            if ($item instanceof Element) {
                $name = $item->input->getAttribute('name');

                $isFieldReadOnly = Contents::pluginHandle()
                    ->trigger($plugged)->call('isFieldReadOnly', $name);
                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                if (preg_match("/^fields\[(.+)\]$/", $name, $matches)) {
                    $name = $matches[1];
                } else {
                    $inputName = 'fields[' . $name . ']';
                    if (preg_match("/^(.+)\[\]$/", $name, $matches)) {
                        $name = $matches[1];
                        $inputName = 'fields[' . $name . '][]';
                    }

                    foreach ($item->inputs as $input) {
                        $input->setAttribute('name', $inputName);
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
     * 获取自定义字段的hook名称
     *
     * @return string
     */
    abstract protected function getThemeFieldsHook(): string;

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
        foreach ($customFields as $key => $val) {
            $fields[$key] = [is_array($val) ? 'json' : 'str', $val];
        }

        return $fields;
    }

    /**
     * 删除草稿
     *
     * @param integer $cid 草稿id
     * @throws DbException
     */
    protected function deleteDraft(int $cid, bool $hasMetas = true)
    {
        $this->delete($this->db->sql()->where('cid = ?', $cid));

        if ($hasMetas) {
            /** 删除草稿分类 */
            $this->setCategories($cid, [], false, false);

            /** 删除标签 */
            $this->setTags($cid, null, false, false);
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
        if ($this->request->is('created')) {
            $created = $this->request->get('created');
        } elseif ($this->request->is('date')) {
            $dstOffset = $this->request->get('dst', 0);
            $timezoneSymbol = $this->options->timezone >= 0 ? '+' : '-';
            $timezoneOffset = abs($this->options->timezone);
            $timezone = $timezoneSymbol . str_pad($timezoneOffset / 3600, 2, '0', STR_PAD_LEFT) . ':00';
            [$date, $time] = explode(' ', $this->request->get('date'));

            $created = strtotime("{$date}T{$time}{$timezone}") - $dstOffset;
        } elseif ($this->request->is('year&month&day')) {
            $second = $this->request->filter('int')->get('sec', date('s'));
            $min = $this->request->filter('int')->get('min', date('i'));
            $hour = $this->request->filter('int')->get('hour', date('H'));

            $year = $this->request->filter('int')->get('year');
            $month = $this->request->filter('int')->get('month');
            $day = $this->request->filter('int')->get('day');

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
     * 设置分类
     *
     * @param integer $cid 内容id
     * @param array $categories 分类id的集合数组
     * @param boolean $beforeCount 是否参与计数
     * @param boolean $afterCount 是否参与计数
     * @throws DbException
     */
    protected function setCategories(int $cid, array $categories, bool $beforeCount = true, bool $afterCount = true)
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
    protected function setTags(int $cid, ?string $tags, bool $beforeCount = true, bool $afterCount = true)
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
     * 取消附件关联
     *
     * @param integer $cid 内容id
     * @throws DbException
     */
    protected function unAttach(int $cid)
    {
        $this->db->query($this->db->update('table.contents')->rows(['parent' => 0, 'status' => 'publish'])
            ->where('parent = ? AND type = ?', $cid, 'attachment'));
    }

    /**
     * 发布内容
     *
     * @param array $contents 内容结构
     * @param boolean $hasMetas 是否有metas
     * @throws DbException|Exception
     */
    protected function publish(array $contents, bool $hasMetas = true)
    {
        /** 发布内容, 检查是否具有直接发布的权限 */
        $this->checkStatus($contents);

        /** 真实的内容id */
        $realId = 0;

        /** 是否是从草稿状态发布 */
        $isDraftToPublish = preg_match("/_draft$/", $this->type);

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
            if ($hasMetas) {
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
            }

            /** 同步附件 */
            $this->attach($realId);

            /** 保存自定义字段 */
            $this->applyFields($this->getFields(), $realId);

            $this->db->fetchRow($this->select()
                ->where('table.contents.cid = ?', $realId)->limit(1), [$this, 'push']);
        }
    }


    /**
     * 保存内容
     *
     * @param array $contents 内容结构
     * @param boolean $hasMetas 是否有metas
     * @return integer
     * @throws DbException|Exception
     */
    protected function save(array $contents, bool $hasMetas = true): int
    {
        /** 发布内容, 检查是否具有直接发布的权限 */
        $this->checkStatus($contents);

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
            if ($hasMetas) {
                /** 插入分类 */
                if (array_key_exists('category', $contents)) {
                    $this->setCategories($realId, !empty($contents['category']) && is_array($contents['category']) ?
                        $contents['category'] : [$this->options->defaultCategory], false, false);
                }

                /** 插入标签 */
                if (array_key_exists('tags', $contents)) {
                    $this->setTags($realId, $contents['tags'], false, false);
                }
            }

            /** 同步附件 */
            $this->attach($this->cid);

            /** 保存自定义字段 */
            $this->applyFields($this->getFields(), $realId);

            return $realId;
        }

        return $this->draft['cid'];
    }

    /**
     * @param array $contents
     * @return void
     * @throws DbException
     * @throws Exception
     */
    private function checkStatus(array &$contents)
    {
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
    }
}
