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

trait EditTrait
{
    /**
     * 自定义字段的hook名称
     *
     * @var string
     */
    protected string $themeCustomFieldsHook;

    /**
     * 获取权限
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

            if (function_exists($this->themeCustomFieldsHook)) {
                call_user_func($this->themeCustomFieldsHook, $layout);
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
     * 删除草稿
     *
     * @param integer $cid 草稿id
     * @throws DbException
     */
    protected function deleteDraft(int $cid)
    {
        $this->delete($this->db->sql()->where('cid = ?', $cid));

        /** 删除草稿分类 */
        $this->setCategories($cid, [], false, false);

        /** 删除标签 */
        $this->setTags($cid, null, false, false);
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
     * 准备编辑
     *
     * @param string $type
     * @param bool $hasDraft
     * @param string $notFoundMessage
     * @return $this
     */
    protected function prepareEdit(string $type, bool $hasDraft, string $notFoundMessage): self
    {
        if ($this->request->is('cid')) {
            $contentTypes = [$type];
            if ($hasDraft) {
                $contentTypes[] = $type . '_draft';
            }

            $this->db->fetchRow($this->select()
                ->where('table.contents.type IN ?', $contentTypes)
                ->where('table.contents.cid = ?', $this->request->filter('int')->get('cid'))
                ->limit(1), [$this, 'push']);

            if (!$this->have()) {
                throw new Exception($notFoundMessage, 404);
            }

            if ($hasDraft) {
                if ($type . '_draft' === $this->type && $this->parent) {
                    $this->response->redirect(
                        Common::url('write-' . $type . '.php?cid=' . $this->parent, $this->options->adminUrl)
                    );
                }

                $draft = $this->type === $type . '_draft' ? $this->row : $this->db->fetchRow($this->select()
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $this->cid, $type . '_draft')
                    ->limit(1), [$this, 'filter']);

                if (isset($draft)) {
                    $draft['slug'] = ltrim($draft['slug'], '@');
                    $draft['type'] = $type;
                    $draft['draft'] = $draft;
                    $draft['cid'] = $this->cid;
                    $draft['tags'] = $this->db->fetchAll($this->db
                        ->select()->from('table.metas')
                        ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                        ->where('table.relationships.cid = ?', $draft['cid'])
                        ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);

                    $this->push($draft);
                }
            }

            if (!$this->allow('edit')) {
                throw new Exception(_t('没有编辑权限'), 403);
            }
        }

        return $this;
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
}
