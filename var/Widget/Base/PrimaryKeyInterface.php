<?php

namespace Widget\Base;

interface PrimaryKeyInterface
{
    /**
     * 获取主键
     *
     * @return string
     */
    public function getPrimaryKey(): string;
}
