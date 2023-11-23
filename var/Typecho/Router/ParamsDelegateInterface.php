<?php

namespace Typecho\Router;

interface ParamsDelegateInterface
{
    public function getRouterParam(string $key): string;
}
