<?php

/*
 * This file is part of the commonmark-php package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Array collection
 *
 * Provides a wrapper around a standard PHP array.
 */
class CommonMark_Util_ArrayCollection implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var array
     */
    private $elements;

    /**
     * Constructor
     * @param array $elements
     */
    public function __construct(array $elements = array())
    {
        $this->elements = $elements;
    }

    /**
     * @return mixed
     */
    public function first()
    {
        return reset($this->elements);
    }

    /**
     * @return mixed
     */
    public function last()
    {
        return end($this->elements);
    }

    /**
     * Retrieve an external iterator
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * @param mixed $element
     *
     * @return bool
     */
    public function add($element)
    {
        $this->elements[] = $element;

        return true;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->elements[$key] = $value;
    }

    /**
     * @param mixed $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        return isset($this->elements[$key]) ? $this->elements[$key] : null;
    }

    /**
     * @param mixed $key
     *
     * @return mixed|null
     */
    public function remove($key)
    {
        if (!isset($this->elements[$key]) && !array_key_exists($key, $this->elements)) {
            return null;
        }

        $removed = $this->elements[$key];
        unset($this->elements[$key]);

        return $removed;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->elements);
    }

    /**
     * @param mixed $key
     *
     * @return bool
     */
    public function containsKey($key)
    {
        return isset($this->elements[$key]) || array_key_exists($key, $this->elements);
    }

    /**
     * Count elements of an object
     *
     * @return int The count as an integer.
     */
    public function count()
    {
        return count($this->elements);
    }

    /**
     * Whether an offset exists
     *
     * @param mixed $offset An offset to check for.
     *
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * Offset to retrieve
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!isset($offset)) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * Offset to unset
     * @param mixed $offset The offset to unset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Returns a subset of the array
     * @param int      $offset
     * @param int|null $length
     *
     * @return array
     */
    public function slice($offset, $length = null)
    {
        return array_slice($this->elements, $offset, $length, true);
    }

    /**
     * Remove a subset of the array
     *
     * The removed part will be returned
     *
     * @param int      $offset
     * @param int|null $length
     * @param array    $replacement
     *
     * @return array The removed subset
     */
    public function splice($offset, $length = null, $replacement = array())
    {
        if ($length === null) {
            $length = count($this->elements);
        }

        return array_splice($this->elements, $offset, $length, $replacement);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->elements;
    }
}
