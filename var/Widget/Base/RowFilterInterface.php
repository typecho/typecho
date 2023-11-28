<?php

namespace Widget\Base;

/**
 * 行过滤器接口
 */
interface RowFilterInterface
{
    /**
     * 过滤行
     *
     * @param array $row
     * @return array
     */
    public function filter(array $row): array;
}
