<?php

namespace IXR;

/**
 * IXR日期
 *
 * @package IXR
 */
class Date
{
    private $year;

    private $month;

    private $day;

    private $hour;

    private $minute;

    private $second;

    /**
     * @param int|string $time
     */
    public function __construct($time)
    {
        // $time can be a PHP timestamp or an ISO one
        if (is_numeric($time)) {
            $this->parseTimestamp(intval($time));
        } else {
            $this->parseIso($time);
        }
    }

    /**
     * @param int $timestamp
     */
    private function parseTimestamp(int $timestamp)
    {
        $this->year = date('Y', $timestamp);
        $this->month = date('m', $timestamp);
        $this->day = date('d', $timestamp);
        $this->hour = date('H', $timestamp);
        $this->minute = date('i', $timestamp);
        $this->second = date('s', $timestamp);
    }

    /**
     * @param string $iso
     */
    private function parseIso(string $iso)
    {
        $this->year = substr($iso, 0, 4);
        $this->month = substr($iso, 4, 2);
        $this->day = substr($iso, 6, 2);
        $this->hour = substr($iso, 9, 2);
        $this->minute = substr($iso, 12, 2);
        $this->second = substr($iso, 15, 2);
    }

    /**
     * @return string
     */
    public function getIso(): string
    {
        return $this->year . $this->month . $this->day . 'T' . $this->hour . ':' . $this->minute . ':' . $this->second;
    }

    /**
     * @return string
     */
    public function getXml(): string
    {
        return '<dateTime.iso8601>' . $this->getIso() . '</dateTime.iso8601>';
    }

    /**
     * @return false|int
     */
    public function getTimestamp()
    {
        return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }
}
