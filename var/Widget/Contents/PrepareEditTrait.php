<?php

namespace Widget\Contents;

use Typecho\Db\Exception as DbException;
use Typecho\Widget\Exception;
use Widget\Base\Metas;
use Widget\Metas\From as MetasFrom;

/**
 * 编辑准备组件
 */
trait PrepareEditTrait
{

    /**
     * 准备编辑
     *
     * @param string $type
     * @param bool $hasDraft
     * @param string $notFoundMessage
     * @return $this
     * @throws Exception|DbException
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
                $draft = $this->type === $type . '_draft' ? $this->row : $this->db->fetchRow($this->select()
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $this->cid, 'revision')
                    ->limit(1), [$this, 'filter']);

                if (isset($draft)) {
                    $draft['parent'] = $this->row['parent'];    // keep parent
                    $draft['slug'] = ltrim($draft['slug'], '@');
                    $draft['type'] = $this->type;
                    $draft['draft'] = $draft;
                    $draft['cid'] = $this->cid;
                    $draft['tags'] = $this->db->fetchAll($this->db
                        ->select()->from('table.metas')
                        ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                        ->where('table.relationships.cid = ?', $draft['cid'])
                        ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);

                    $this->row = $draft;
                }
            }

            if (!$this->allow('edit')) {
                throw new Exception(_t('没有编辑权限'), 403);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    abstract public function prepare(): self;

    /**
     * 获取网页标题
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('编辑 %s', $this->prepare()->title);
    }

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
     * @return string
     */
    protected function ___title(): string
    {
        return $this->have() ? $this->row['title'] : '';
    }

    /**
     * @return string
     */
    protected function ___text(): string
    {
        return $this->have() ? ($this->isMarkdown ? substr($this->row['text'], 15) : $this->row['text']) : '';
    }

    /**
     * @return array
     */
    protected function ___categories(): array
    {
        return $this->have() ? parent::___categories()
            : MetasFrom::allocWithAlias(
                'category:' . $this->options->defaultCategory,
                ['mid' => $this->options->defaultCategory]
            )->toArray(['mid', 'name', 'slug']);
    }
}
