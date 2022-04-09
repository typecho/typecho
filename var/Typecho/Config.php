<?php

namespace Typecho;

/**
 * 配置管理类
 *
 * @category typecho
 * @package Config
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config implements \Iterator, \ArrayAccess
{
    /**
     * 当前配置
     *
     * @access private
     * @var array
     */
    private $currentConfig = [];

    /**
     * 实例化一个当前配置
     *
     * @access public
     * @param array|string|null $config 配置列表
     */
    public function __construct($config = [])
    {
        /** 初始化参数 */
        $this->setDefault($config);
    }

    /**
     * 工厂模式实例化一个当前配置
     *
     * @access public
     *
     * @param array|string|null $config 配置列表
     *
     * @return Config
     */
    public static function factory($config = []): Config
    {
        return new self($config);
    }

    /**
     * 设置默认的配置
     *
     * @access public
     *
     * @param mixed $config 配置信息
     * @param boolean $replace 是否替换已经存在的信息
     *
     * @return void
     */
    public function setDefault($config, bool $replace = false)
    {
        if (empty($config)) {
            return;
        }

        /** 初始化参数 */
        if (is_string($config)) {
            parse_str($config, $params);
        } else {
            $params = $config;
        }

        /** 设置默认参数 */
        foreach ($params as $name => $value) {
            if ($replace || !array_key_exists($name, $this->currentConfig)) {
                $this->currentConfig[$name] = $value;
            }
        }
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->currentConfig);
    }

    /**
     * 重设指针
     *
     * @access public
     * @return void
     */
    public function rewind(): void
    {
        reset($this->currentConfig);
    }

    /**
     * 返回当前值
     *
     * @access public
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->currentConfig);
    }

    /**
     * 指针后移一位
     *
     * @access public
     * @return void
     */
    public function next(): void
    {
        next($this->currentConfig);
    }

    /**
     * 获取当前指针
     *
     * @access public
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->currentConfig);
    }

    /**
     * 验证当前值是否到达最后
     *
     * @access public
     * @return boolean
     */
    public function valid(): bool
    {
        return false !== $this->current();
    }

    /**
     * 魔术函数获取一个配置值
     *
     * @access public
     * @param string $name 配置名称
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->offsetGet($name);
    }

    /**
     * 魔术函数设置一个配置值
     *
     * @access public
     * @param string $name 配置名称
     * @param mixed $value 配置值
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * 直接输出默认配置值
     *
     * @access public
     * @param string $name 配置名称
     * @param array|null $args 参数
     * @return void
     */
    public function __call(string $name, ?array $args)
    {
        echo $this->currentConfig[$name];
    }

    /**
     * 判断当前配置值是否存在
     *
     * @access public
     * @param string $name 配置名称
     * @return boolean
     */
    public function __isSet(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * 魔术方法,打印当前配置数组
     *
     * @access public
     * @return string
     */
    public function __toString(): string
    {
        return serialize($this->currentConfig);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->currentConfig;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->currentConfig[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->currentConfig[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->currentConfig[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->currentConfig[$offset]);
    }
}
