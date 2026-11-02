<?php

namespace Widget\Contents;

use Typecho\Config;
use Typecho\Db\Exception as DbException;
use Typecho\Timezone;
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
     * 删除自定义字段
     *
     * @param integer $cid
     * @return integer
     * @throws DbException
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

            $isFieldReadOnly = Contents::pluginHandle()->trigger($plugged)->call('isFieldReadOnly', $name);
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
     * @param mixed $value
     * @param integer $cid
     * @return integer|bool
     * @throws Exception
     */
    public function setField(string $name, string $type, $value, int $cid)
    {
        if (
            empty($name) || !$this->checkFieldName($name)
            || !in_array($type, ['str', 'int', 'float', 'json'])
        ) {
            return false;
        }

        if ($type === 'json') {
            $value = json_encode($value);
        }

        $exist = $this->db->fetchRow($this->db->select('cid')->from('table.fields')
            ->where('cid = ? AND name = ?', $cid, $name));

        $rows = [
            'type'        => $type,
            'str_value'   => 'str' == $type || 'json' == $type ? $value : null,
            'int_value'   => 'int' == $type ? intval($value) : 0,
            'float_value' => 'float' == $type ? floatval($value) : 0
        ];

        if (empty($exist)) {
            $rows['cid'] = $cid;
            $rows['name'] = $name;

            return $this->db->query($this->db->insert('table.fields')->rows($rows));
        } else {
            return $this->db->query($this->db->update('table.fields')
                ->rows($rows)
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
     * 删除内容
     *
     * @param integer $cid 草稿id
     * @throws DbException
     */
    protected function deleteContent(int $cid, bool $hasMetas = true)
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
     * 墙钟时间不合法时的兜底发布时间
     *
     * 【为什么统一放一处】"改文章时保留原发布时间, 新建时用当前时间"
     * 这条策略在三个调用点各写了一遍, 每次改都要记得改三处 —— 上一轮
     * 就漏了一处, 结果是 null 进了声明返回 int 的方法, 后台 500。
     *
     * @return integer
     */
    private function fallbackCreated(): int
    {
        return $this->have() && $this->created > 0
            ? $this->created
            : $this->options->time;
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
            $created = $this->getCreatedFromDate();
        } elseif ($this->request->is('year&month&day')) {
            $second = $this->request->filter('int')->get('sec', date('s'));
            $min = $this->request->filter('int')->get('min', date('i'));
            $hour = $this->request->filter('int')->get('hour', date('H'));

            $year = $this->request->filter('int')->get('year');
            $month = $this->request->filter('int')->get('month');
            $day = $this->request->filter('int')->get('day');

            /**
             * 和 date 字段路径走同一条路: 拼成墙钟文本, 按站点时区解释。
             *
             * 【为什么不走 mktime()】mktime() 把时分秒当作**服务器本地**
             * 时刻来构造, 服务器自己的墙上时间跳过一小时时 (纽约
             * 2021-03-14 02:00-02:59) 它会把结果归一化到 03:00, 于是这条
             * 路径和 date 路径给出的不是同一个瞬间。
             *
             * 作者填的本来就是**站点时区**的墙上时间, 两条路径应该
             * 解释得一样。
             */
            $wall = sprintf(
                '%04d-%02d-%02d %02d:%02d:%02d',
                $year,
                $month,
                $day,
                $hour,
                $min,
                $second
            );

            $created = Timezone::wallToTimestamp(
                $wall,
                $this->options->timezoneName
            );

            if ($created === null) {
                /**
                 * 形状不合法 (比如 00:99:00)。不写 0 —— 那会把文章存成
                 * 1970 年。
                 */
                $created = $this->fallbackCreated();
            }
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
     * 根据发布日期计算 created
     *
     * 表单里填写的是**站点时区**下的墙上时间: 后台的日期输入框预填的是站点
     * 墙钟时间, 时间选择器用的也是站点偏移。所以这里一律按站点时区解释,
     * 与改动前的设计保持一致。
     *
     * 站点时区本身也可能是固定偏移秒数 (旧版本), 此时退回到原有的固定偏移
     * 解释方式, 保证升级前后行为一致。
     *
     * @return integer
     */
    protected function getCreatedFromDate(): int
    {
        $raw = $this->request->get('date');

        /**
         * 防数组。
         *
         * 【为什么】客户端可以提交 date[]=x, 于是 Request::get() 返回数组。
         * 直接 (string) 转换会触发 "Array to string conversion" 警告 ——
         * phpunit.xml.dist 开了 failOnWarning, 生产环境也会进日志。
         * 数组不是合法的日期形状, 按形状非法处理。
         */
        $date = is_array($raw) ? '' : (string) $raw;

        /**
         * 【按空白切, 不是按单个空格】
         *
         * explode(' ', ...) 对多个空格会切出空段: '2021-07-01  12:00'
         * 变成 ['2021-07-01', '', '12:00'], 于是取到的"时间"是空串。
         * 而 strtotime() 对多个空格是宽容的 —— 作者在输入框里多打一个
         * 空格是完全正常的, 不该因此把文章的发布时间改掉。
         *
         * 所以按连续的空白切, 并且只取前两段 (后面还有内容的话, 那
         * 属于形状不合法, 由下面的正则去判)。
         */
        $parts = preg_split('/\s+/', trim($date));
        $day = $parts[0] ?? '';
        $time = $parts[1] ?? '00:00';

        /**
         * 【月和日允许不补零】
         *
         * 改之前这里走的是 strtotime(), 它对 '2021-7-1' 是宽容的。而
         * 日期输入框是自由文本, 填 '2021-7-1' 完全正常。收严成
         * '\d{4}-\d{2}-\d{2}' 之后, 这种输入会被当成形状不合法 ——
         * 用户的日期被静默忽略, 文章保留原来的发布时间。
         *
         * 所以只要求**能分辨出年-月-日**, 不要求补零。真正的不合法
         * (比如 '2021-13-45') 交给 wallToTimestamp() 去判, 那里按日期
         * 规则检查, 比这里的正则准。
         */
        if ($day === '' || !preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $day)) {
            /**
             * 形状不对 -> **不**把发布时间改成"现在"。
             *
             * 改文章时, 这个分支会把已有文章的发布时间悄悄改成当前时间 ——
             * 正是 XML-RPC 侧已经明确否决的那种做法。修改文章时应该
             * 保留原有的发布时间; 只有新建 (本来就没有原值) 才用当前时间。
             */
            return $this->fallbackCreated();
        }

        /**
         * 【时间形状不对, 跟日期形状不对一样处理】
         *
         * 之前这里把不合法的时间改成 '00:00' —— 于是作者把时间打错
         * (比如 '2021-07-01 12:0' 或 '2021-07-01 9.30'), 文章的发布
         * 时间被**静默地移到午夜**。那与上面几行写明的政策直接相反:
         * 形状不对要**保留原有的发布时间**, 不是替作者换一个时间。
         *
         * 所以这里也走 fallbackCreated()。
         */
        if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            return $this->fallbackCreated();
        }

        $wall = $day . ' ' . $time;
        $siteTimezone = $this->options->timezoneName;
        $created = Timezone::wallToTimestamp($wall, $siteTimezone);
        if ($created === null) {
            return $this->fallbackCreated();
        }

        // 未改发布日期时，保留原有秒数及秋季重复小时所属的真实瞬间。
        if ($this->have() && $this->created > 0) {
            $format = substr_count($time, ':') === 2 ? 'Y-m-d H:i:s' : 'Y-m-d H:i';
            $previousWall = gmdate($format, Timezone::wallTimestamp($this->created, $siteTimezone));
            $submittedWall = gmdate($format, Timezone::wallTimestamp($created, $siteTimezone));
            if ($previousWall === $submittedWall) {
                return (int) $this->created;
            }
        }

        if (Timezone::isName($siteTimezone)) {
            return $created;
        }

        // 旧前端提交 dst 的单位为分钟；地区配置已由服务端规则处理。
        $dstOffset = (int) $this->request->get('dst', 0);
        if ($dstOffset < -1440 || $dstOffset > 1440) {
            $dstOffset = 0;
        }
        return $created - $dstOffset * 60;
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
        $tags = str_replace('，', ',', $tags ?? '');
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
        $isDraftToPublish = false;
        $isBeforePublish = false;
        $isAfterPublish = 'publish' === $contents['status'];

        /** 重新发布现有内容 */
        if ($this->have()) {
            $isDraftToPublish = preg_match("/_draft$/", $this->type);
            $isBeforePublish = 'publish' === $this->status;

            /** 如果它本身不是草稿, 需要删除其草稿 */
            if (!$isDraftToPublish && $this->draft) {
                $cid = $this->draft['cid'];
                $this->deleteContent($cid);
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
            $isRevision = !preg_match("/_draft$/", $this->type);
            if ($isRevision) {
                $contents['parent'] = $this->cid;
                $contents['type'] = 'revision';
            }

            /** 直接将草稿状态更改 */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->draft['cid']))) {
                $realId = $this->draft['cid'];
            }
        } else {
            if ($this->have()) {
                $contents['parent'] = $this->cid;
                $contents['type'] = 'revision';
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
     * 获取页面偏移
     *
     * @param string $column 字段名
     * @param integer $offset 偏移值
     * @param string $type 类型
     * @param string|null $status 状态值
     * @param integer $authorId 作者
     * @param integer $pageSize 分页值
     * @return integer
     * @throws DbException
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
