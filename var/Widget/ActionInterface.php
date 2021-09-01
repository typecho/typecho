<?php

namespace Widget;

/**
 * 可以被Widget\Action调用的接口
 */
interface ActionInterface
{
    /**
     * 接口需要实现的入口函数
     */
    public function action();
}
