<?php

namespace IXR;

/**
 * IXR日期
 *
 * @package IXR
 */
class Date
{
    private string $year;

    private string $month;

    private string $day;

    private string $hour;

    private string $minute;

    private string $second;

    private string $timezone;

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
        $this->year = gmdate('Y', $timestamp);
        $this->month = gmdate('m', $timestamp);
        $this->day = gmdate('d', $timestamp);
        $this->hour = gmdate('H', $timestamp);
        $this->minute = gmdate('i', $timestamp);
        $this->second = gmdate('s', $timestamp);
        $this->timezone = '';
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
        $this->timezone = substr($iso, 17);
    }

    /**
     * @return string
     */
    public function getIso(): string
    {
        return $this->year . $this->month . $this->day . 'T' . $this->hour . ':' . $this->minute . ':' . $this->second . $this->timezone;
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
