<?php

namespace Typecho\I18n;

/*
   Copyright (c) 2003 Danilo Segan <danilo@kvota.net>.
   Copyright (c) 2005 Nico Kaiser <nico@siriux.net>

   This file is part of PHP-gettext.

   PHP-gettext is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-gettext is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-gettext; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

 */

/**
 * This file is part of PHP-gettext
 *
 * @author Danilo Segan <danilo@kvota.net>, Nico Kaiser <nico@siriux.net>
 * @category typecho
 * @package I18n
 */
class GetText
{
    //public:
    public $error = 0; // public variable that holds error code (0 if no error)

    //private:
    private $BYTE_ORDER = 0;        // 0: low endian, 1: big endian

    private $STREAM = null;

    private $short_circuit = false;

    private $enable_cache = false;

    private $originals = null;      // offset of original table

    private $translations = null;    // offset of translation table

    private $pluralHeader = null;    // cache header field for plural forms

    private $total = 0;          // total string count

    private $table_originals = null;  // table for original strings (offsets)

    private $table_translations = null;  // table for translated strings (offsets)

    private $cache_translations = null;  // original -> translation mapping


    /* Methods */
    /**
     * Constructor
     *
     * @param string $file file name
     * @param boolean enable_cache Enable or disable caching of strings (default on)
     */
    public function __construct(string $file, $enable_cache = true)
    {
        // If there isn't a StreamReader, turn on short circuit mode.
        if (!file_exists($file)) {
            $this->short_circuit = true;
            return;
        }

        // Caching can be turned off
        $this->enable_cache = $enable_cache;
        $this->STREAM = @fopen($file, 'rb');

        $unpacked = unpack('c', $this->read(4));
        $magic = array_shift($unpacked);

        if (-34 == $magic) {
            $this->BYTE_ORDER = 0;
        } elseif (-107 == $magic) {
            $this->BYTE_ORDER = 1;
        } else {
            $this->error = 1; // not MO file
            return false;
        }

        // FIXME: Do we care about revision? We should.
        $revision = $this->readInt();

        $this->total = $this->readInt();
        $this->originals = $this->readInt();
        $this->translations = $this->readInt();
    }

    /**
     * Translates a string
     *
     * @access public
     * @param string string to be translated
     * @param integer|null $num found string number
     * @return string translated string (or original, if not found)
     */
    public function translate($string, ?int &$num): string
    {
        if ($this->short_circuit) {
            return $string;
        }
        $this->loadTables();

        if ($this->enable_cache) {
            // Caching enabled, get translated string from cache
            if (array_key_exists($string, $this->cache_translations)) {
                return $this->cache_translations[$string];
            } else {
                return $string;
            }
        } else {
            // Caching not enabled, try to find string
            $num = $this->findString($string);
            if ($num == -1) {
                return $string;
            } else {
                return $this->getTranslationString($num);
            }
        }
    }

    /**
     * Plural version of gettext
     *
     * @access public
     * @param string single
     * @param string plural
     * @param string number
     * @param integer|null $num found string number
     * @return string plural form
     */
    public function ngettext($single, $plural, $number, ?int &$num): string
    {
        $number = intval($number);

        if ($this->short_circuit) {
            if ($number != 1) {
                return $plural;
            } else {
                return $single;
            }
        }

        // find out the appropriate form
        $select = $this->selectString($number);

        // this should contains all strings separated by NULLs
        $key = $single . chr(0) . $plural;


        if ($this->enable_cache) {
            if (!array_key_exists($key, $this->cache_translations)) {
                return ($number != 1) ? $plural : $single;
            } else {
                $result = $this->cache_translations[$key];
                $list = explode(chr(0), $result);
                return $list[$select] ?? '';
            }
        } else {
            $num = $this->findString($key);
            if ($num == -1) {
                return ($number != 1) ? $plural : $single;
            } else {
                $result = $this->getTranslationString($num);
                $list = explode(chr(0), $result);
                return $list[$select] ?? '';
            }
        }
    }

    /**
     * 关闭文件句柄
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        fclose($this->STREAM);
    }

    /**
     * read
     *
     * @param mixed $count
     * @access private
     * @return false|string
     */
    private function read($count)
    {
        $count = abs($count);

        if ($count > 0) {
            return fread($this->STREAM, $count);
        }

        return false;
    }

    /**
     * Reads a 32bit Integer from the Stream
     *
     * @access private
     * @return Integer from the Stream
     */
    private function readInt(): int
    {
        $end = unpack($this->BYTE_ORDER == 0 ? 'V' : 'N', $this->read(4));
        return array_shift($end);
    }

    /**
     * Loads the translation tables from the MO file into the cache
     * If caching is enabled, also loads all strings into a cache
     * to speed up translation lookups
     *
     * @access private
     */
    private function loadTables()
    {
        if (
            is_array($this->cache_translations) &&
            is_array($this->table_originals) &&
            is_array($this->table_translations)
        ) {
            return;
        }

        /* get original and translations tables */
        fseek($this->STREAM, $this->originals);
        $this->table_originals = $this->readIntArray($this->total * 2);
        fseek($this->STREAM, $this->translations);
        $this->table_translations = $this->readIntArray($this->total * 2);

        if ($this->enable_cache) {
            $this->cache_translations = ['' => null];
            /* read all strings in the cache */
            for ($i = 0; $i < $this->total; $i++) {
                if ($this->table_originals[$i * 2 + 1] > 0) {
                    fseek($this->STREAM, $this->table_originals[$i * 2 + 2]);
                    $original = fread($this->STREAM, $this->table_originals[$i * 2 + 1]);
                    fseek($this->STREAM, $this->table_translations[$i * 2 + 2]);
                    $translation = fread($this->STREAM, $this->table_translations[$i * 2 + 1]);
                    $this->cache_translations[$original] = $translation;
                }
            }
        }
    }

    /**
     * Reads an array of Integers from the Stream
     *
     * @param int count How many elements should be read
     * @return array of Integers
     */
    private function readIntArray($count): array
    {
        return unpack(($this->BYTE_ORDER == 0 ? 'V' : 'N') . $count, $this->read(4 * $count));
    }

    /**
     * Binary search for string
     *
     * @access private
     * @param string string
     * @param int start (internally used in recursive function)
     * @param int end (internally used in recursive function)
     * @return int string number (offset in originals table)
     */
    private function findString($string, $start = -1, $end = -1): int
    {
        if (($start == -1) or ($end == -1)) {
            // findString is called with only one parameter, set start end end
            $start = 0;
            $end = $this->total;
        }
        if (abs($start - $end) <= 1) {
            // We're done, now we either found the string, or it doesn't exist
            $txt = $this->getOriginalString($start);
            if ($string == $txt) {
                return $start;
            } else {
                return -1;
            }
        } elseif ($start > $end) {
            // start > end -> turn around and start over
            return $this->findString($string, $end, $start);
        } else {
            // Divide table in two parts
            $half = (int)(($start + $end) / 2);
            $cmp = strcmp($string, $this->getOriginalString($half));
            if ($cmp == 0) {
                // string is exactly in the middle => return it
                return $half;
            } elseif ($cmp < 0) {
                // The string is in the upper half
                return $this->findString($string, $start, $half);
            } else { // The string is in the lower half
                return $this->findString($string, $half, $end);
            }
        }
    }

    /**
     * Returns a string from the "originals" table
     *
     * @access private
     * @param int num Offset number of original string
     * @return string Requested string if found, otherwise ''
     */
    private function getOriginalString($num): string
    {
        $length = $this->table_originals[$num * 2 + 1];
        $offset = $this->table_originals[$num * 2 + 2];
        if (!$length) {
            return '';
        }
        fseek($this->STREAM, $offset);
        $data = fread($this->STREAM, $length);
        return (string)$data;
    }

    /**
     * Returns a string from the "translations" table
     *
     * @access private
     * @param int num Offset number of original string
     * @return string Requested string if found, otherwise ''
     */
    private function getTranslationString($num): string
    {
        $length = $this->table_translations[$num * 2 + 1];
        $offset = $this->table_translations[$num * 2 + 2];
        if (!$length) {
            return '';
        }
        fseek($this->STREAM, $offset);
        $data = fread($this->STREAM, $length);
        return (string)$data;
    }

    /**
     * Detects which plural form to take
     *
     * @access private
     * @param n count
     * @return int array index of the right plural form
     */
    private function selectString($n): int
    {
        $string = $this->getPluralForms();
        $string = str_replace('nplurals', "\$total", $string);
        $string = str_replace("n", $n, $string);
        $string = str_replace('plural', "\$plural", $string);

        $total = 0;
        $plural = 0;

        eval("$string");
        if ($plural >= $total) {
            $plural = $total - 1;
        }
        return $plural;
    }

    /**
     * Get possible plural forms from MO header
     *
     * @access private
     * @return string plural form header
     */
    private function getPluralForms(): string
    {
        // lets assume message number 0 is header
        // this is true, right?
        $this->loadTables();

        // cache header field for plural forms
        if (!is_string($this->pluralHeader)) {
            if ($this->enable_cache) {
                $header = $this->cache_translations[""];
            } else {
                $header = $this->getTranslationString(0);
            }
            if (preg_match("/plural\-forms: ([^\n]*)\n/i", $header, $regs)) {
                $expr = $regs[1];
            } else {
                $expr = "nplurals=2; plural=n == 1 ? 0 : 1;";
            }
            $this->pluralHeader = $expr;
        }
        return $this->pluralHeader;
    }
}
