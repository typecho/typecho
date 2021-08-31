<?php

namespace IXR;

use ReflectionMethod;

/**
 * hook rpc call
 */
interface Hook
{
    /**
     * @param string $methodName
     * @param ReflectionMethod $reflectionMethod
     * @param array $parameters
     * @return mixed
     */
    public function beforeRpcCall(string $methodName, ReflectionMethod $reflectionMethod, array $parameters);

    /**
     * @param string $methodName
     * @param mixed $result
     */
    public function afterRpcCall(string $methodName, &$result): void;
}
