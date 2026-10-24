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
    /**
     * The maximum number of strings allowed in a single language file; 
     * this prevents excessive memory allocation caused by a large number of forged entries.
     */
    private const MAX_TOTAL = 100000;

    /**
     * The maximum allowed length of a complex expression 
     * also limits the recursion depth of expression parsing.
     */
    private const MAX_EXPRESSION = 256;

    //public:
    public int $error = 0; // public variable that holds error code (0 if no error)

    //private:
    private int $BYTE_ORDER = 0;        // 0: low endian, 1: big endian

    private $STREAM = null;

    private bool $short_circuit = false;

    private bool $enable_cache = false;

    private ?int $originals = null;      // offset of original table

    private ?int $translations = null;    // offset of translation table

    private ?string $pluralHeader = null;    // cache header field for plural forms

    private ?array $pluralForms = null;      // rules for complex numbers after parsing

    private ?string $header = null;          // language file header; plural rules are stored within it.

    private array $pluralTokens = [];        // lexical units of complex number expressions

    private int $pluralPosition = 0;         // current parsing position of the complex number expression

    private int $size = 0;                   // file size, used to limit the read range.

    private int $total = 0;          // total string count

    private ?array $table_originals = null;  // table for original strings (offsets)

    private ?array $table_translations = null;  // table for translated strings (offsets)

    private ?array $cache_translations = null;  // original -> translation mapping


    /* Methods */
    /**
     * Constructor
     *
     * @param string $file file name
     * @param boolean $enable_cache Enable or disable caching of strings (default on)
     */
    public function __construct(string $file, bool $enable_cache = true)
    {
        // If there isn't a StreamReader, turn on short circuit mode.
        if (!file_exists($file)) {
            $this->short_circuit = true;
            return;
        }

        // Caching can be turned off
        $this->enable_cache = $enable_cache;
        $this->STREAM = @fopen($file, 'rb');

        if (!is_resource($this->STREAM)) {
            $this->fail();
            return;
        }

        $data = $this->read(4);

        if (false === $data || strlen($data) < 4) {
            $this->fail();
            return;
        }

        $unpacked = unpack('c', $data);
        $magic = array_shift($unpacked);

        if (-34 == $magic) {
            $this->BYTE_ORDER = 0;
        } elseif (-107 == $magic) {
            $this->BYTE_ORDER = 1;
        } else {
            $this->error = 1; // not MO file
            $this->short_circuit = true;
            return;
        }

        /** Record the file size to limit the scope of subsequent reads. */
        $stat = fstat($this->STREAM);
        $this->size = is_array($stat) ? max(0, intval($stat['size'] ?? 0)) : 0;

        // FIXME: Do we care about revision? We should.
        $revision = $this->readInt();

        $total = $this->readInt();
        $originals = $this->readInt();
        $translations = $this->readInt();

        if (
            null === $revision || null === $total || null === $originals || null === $translations
            || $total < 0 || $originals < 0 || $translations < 0
        ) {
            $this->fail();
            return;
        }

        $this->total = $total;
        $this->originals = $originals;
        $this->translations = $translations;

        if ($total > self::MAX_TOTAL) {
            $this->fail();
            return;
        }

        /** 索引表必须落在文件范围内 */
        if ($this->size > 0 && $total > 0) {
            $length = $total * 8;

            if ($originals + $length > $this->size || $translations + $length > $this->size) {
                $this->fail();
            }
        }
    }

    /**
     * Translates a string
     *
     * @access public
     * @param string $string to be translated
     * @param integer|null $num found string number
     * @return string translated string (or original, if not found)
     */
    public function translate($string, ?int &$num): string
    {
        if ($this->short_circuit) {
            $num = -1;
            return $string;
        }
        $this->loadTables();

        if ($this->short_circuit) {
            $num = -1;
            return $string;
        }

        if ($this->enable_cache) {
            // Caching enabled, get translated string from cache
            if (
                is_array($this->cache_translations)
                && array_key_exists($string, $this->cache_translations)
                && null !== $this->cache_translations[$string]
            ) {
                $num = 1;
                return (string) $this->cache_translations[$string];
            } else {
                $num = - 1;
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
            $num = - 1;
            return ($number != 1) ? $plural : $single;
        }

        $this->loadTables();

        if ($this->short_circuit) {
            $num = - 1;
            return ($number != 1) ? $plural : $single;
        }

        // find out the appropriate form
        $select = $this->selectString($number);

        // this should contains all strings separated by NULLs
        $key = $single . chr(0) . $plural;


        if ($this->enable_cache) {
            if (
                !is_array($this->cache_translations)
                || !array_key_exists($key, $this->cache_translations)
                || null === $this->cache_translations[$key]
            ) {
                $num = - 1;
                return ($number != 1) ? $plural : $single;
            } else {
                $result = $this->cache_translations[$key];
                $list = explode(chr(0), $result);
                $num = 1;
                return $list[$select] ?? $list[0];
            }
        } else {
            $num = $this->findString($key);
            if ($num == -1) {
                return ($number != 1) ? $plural : $single;
            } else {
                $result = $this->getTranslationString($num);
                $list = explode(chr(0), $result);
                return $list[$select] ?? $list[0];
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
        if (is_resource($this->STREAM)) {
            fclose($this->STREAM);
        }
    }

    /**
     * Enter short-circuit mode upon loading failure
     *
     * Corrupted language files should not cause a fatal error; in this case, all translations return the original text
     *
     * @access private
     * @return void
     */
    private function fail()
    {
        $this->error = 2; // Unable to read MO file
        $this->short_circuit = true;
        $this->table_originals = [];
        $this->table_translations = [];
        $this->cache_translations = [];
    }

    /**
     * Read a string of a specified length from a specific position in the file
     *
     * The read length is capped at the file's actual size to 
     * prevent excessive memory allocation caused by a forged length value.
     *
     * @param int $offset
     * @param int $length
     * @access private
     * @return string
     */
    private function readString(int $offset, int $length): string
    {
        if (!is_resource($this->STREAM) || $length <= 0 || $offset < 0) {
            return '';
        }

        if ($this->size > 0) {
            $length = min($length, $this->size - $offset);

            if ($length <= 0) {
                return '';
            }
        }

        fseek($this->STREAM, $offset);
        return (string) fread($this->STREAM, $length);
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
    private function readInt(): ?int
    {
        $data = $this->read(4);

        if (false === $data || strlen($data) < 4) {
            return null;
        }

        $end = unpack($this->BYTE_ORDER == 0 ? 'V' : 'N', $data);

        return is_array($end) ? intval(array_shift($end)) : null;
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

        if (!is_resource($this->STREAM)) {
            $this->fail();
            return;
        }

        /* get original and translations tables */
        fseek($this->STREAM, (int) $this->originals);
        $this->table_originals = $this->readIntArray($this->total * 2);
        fseek($this->STREAM, (int) $this->translations);
        $this->table_translations = $this->readIntArray($this->total * 2);

        if (
            count($this->table_originals) != $this->total * 2 ||
            count($this->table_translations) != $this->total * 2
        ) {
            $this->fail();
            return;
        }

        if ($this->enable_cache) {
            $this->cache_translations = ['' => null];
            /* read all strings in the cache */
            for ($i = 0; $i < $this->total; $i++) {
                $length = intval($this->table_originals[$i * 2 + 1] ?? 0);

                if ($length > 0) {
                    $original = $this->readString(
                        intval($this->table_originals[$i * 2 + 2] ?? 0),
                        $length
                    );
                    $translation = $this->readString(
                        intval($this->table_translations[$i * 2 + 2] ?? 0),
                        intval($this->table_translations[$i * 2 + 1] ?? 0)
                    );
                    $this->cache_translations[$original] = $translation;
                } elseif (null === $this->header) {
                    /** Records with an empty `msgid` store the file header, which contains the plural rules. */
                    $this->header = $this->getTranslationString($i);
                }
            }
        }
    }

    /**
     * Reads an array of Integers from the Stream
     *
     * @param int $count How many elements should be read
     * @return array of Integers
     */
    private function readIntArray(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $data = $this->read(4 * $count);

        if (false === $data || strlen($data) < 4 * $count) {
            return [];
        }

        $unpacked = unpack(($this->BYTE_ORDER == 0 ? 'V' : 'N') . $count, $data);

        return is_array($unpacked) ? $unpacked : [];
    }

    /**
     * Binary search for string
     *
     * @access private
     * @param string $string
     * @param int $start (internally used in recursive function)
     * @param int $end (internally used in recursive function)
     * @return int string number (offset in originals table)
     */
    private function findString(string $string, int $start = -1, int $end = -1): int
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
     * @param int $num Offset number of original string
     * @return string Requested string if found, otherwise ''
     */
    private function getOriginalString(int $num): string
    {
        $length = intval($this->table_originals[$num * 2 + 1] ?? 0);
        $offset = intval($this->table_originals[$num * 2 + 2] ?? 0);
        if (!$length) {
            return '';
        }

        return $this->readString($offset, $length);
    }

    /**
     * Returns a string from the "translations" table
     *
     * @access private
     * @param int $num Offset number of original string
     * @return string Requested string if found, otherwise ''
     */
    private function getTranslationString(int $num): string
    {
        $length = intval($this->table_translations[$num * 2 + 1] ?? 0);
        $offset = intval($this->table_translations[$num * 2 + 2] ?? 0);
        if (!$length) {
            return '';
        }

        return $this->readString($offset, $length);
    }

    /**
     * Detects which plural form to take
     *
     * @param int $n count
     * @return int array index of the right plural form
     */
    private function selectString(int $n): int
    {
        $forms = $this->getPluralForms();
        $total = $forms['total'];
        $plural = $this->evaluateExpression($forms['expression'], $n);

        if (null === $plural) {
            /** Fall back to default rules when the expression cannot be parsed. */
            $total = 2;
            $plural = ($n == 1) ? 0 : 1;
        }

        if ($plural >= $total) {
            $plural = $total - 1;
        }

        if ($plural < 0) {
            $plural = 0;
        }

        return $plural;
    }

    /**
     * Get possible plural forms from MO header
     *
     * @access private
     * @return array plural form header
     */
    private function getPluralForms(): array
    {
        if (is_array($this->pluralForms)) {
            return $this->pluralForms;
        }

        // lets assume message number 0 is header
        // this is true, right?
        $this->loadTables();

        // cache header field for plural forms
        if (!is_string($this->pluralHeader)) {
            if ($this->enable_cache) {
                $header = $this->header;
            } else {
                $header = $this->getTranslationString(0);
            }

            $this->pluralHeader = is_string($header) ? $header : '';
        }

        $this->pluralForms = $this->parsePluralForms($this->pluralHeader);

        return $this->pluralForms;
    }

    /**
     * Parse the plural rules from the language file header
     *
     * Only the `nplurals` and `plural` fields are extracted; 
     * the expression itself is not executed as code.
     *
     * @access private
     * @param string $header
     * @return array Includes the total count and the expression.
     */
    private function parsePluralForms(string $header): array
    {
        $default = ['total' => 2, 'expression' => 'n == 1 ? 0 : 1'];

        if ('' === $header || !preg_match("/plural-forms\s*:\s*([^\n]*)/i", $header, $regs)) {
            return $default;
        }

        $total = 2;

        if (preg_match("/nplurals\s*=\s*(\d+)/i", $regs[1], $matches)) {
            $total = max(1, intval($matches[1]));
        }

        if (!preg_match("/plural\s*=\s*([^;]*)/i", $regs[1], $matches)) {
            return $default;
        }

        $expression = trim($matches[1]);

        if ('' === $expression) {
            return $default;
        }

        return ['total' => $total, 'expression' => $expression];
    }

    /**
     * Evaluate complex number expressions
     *
     * A restricted parser is used here instead of `eval`; only numbers, the variable `n`,
     * and operators required for complex number operations are permitted in the expressions,
     * thereby preventing the execution of arbitrary code from language files.
     *
     * @access private
     * @param string $expression
     * @param int $n
     * @return int|null Returns null if parsing fails.
     */
    private function evaluateExpression(string $expression, int $n): ?int
    {
        if (strlen($expression) > self::MAX_EXPRESSION) {
            return null;
        }

        $tokens = $this->tokenizeExpression($expression);

        if (null === $tokens) {
            return null;
        }

        $this->pluralTokens = $tokens;
        $this->pluralPosition = 0;
        $result = $this->parseTernary($n);

        if (null === $result || isset($this->pluralTokens[$this->pluralPosition])) {
            return null;
        }

        return intval(round($result));
    }

    /**
     * Split the complex expression into lexical units.
     *
     * @access private
     * @param string $expression
     * @return array|null Returns null when an invalid character is encountered.
     */
    private function tokenizeExpression(string $expression): ?array
    {
        $tokens = [];
        $length = strlen($expression);
        $offset = 0;

        while ($offset < $length) {
            if (preg_match('/\G\s+/', $expression, $matches, 0, $offset)) {
                $offset += strlen($matches[0]);
                continue;
            }

            if (preg_match('/\G\d+/', $expression, $matches, 0, $offset)) {
                $tokens[] = ['number', intval($matches[0])];
                $offset += strlen($matches[0]);
                continue;
            }

            if (preg_match('/\G(==|!=|<=|>=|&&|\|\|)/', $expression, $matches, 0, $offset)) {
                $tokens[] = ['operator', $matches[0]];
                $offset += strlen($matches[0]);
                continue;
            }

            if (preg_match('/\G[<>()?:!+\-*\/%]/', $expression, $matches, 0, $offset)) {
                $tokens[] = ['operator', $matches[0]];
                $offset += 1;
                continue;
            }

            if (preg_match('/\Gn(?![a-zA-Z0-9_])/', $expression, $matches, 0, $offset)) {
                $tokens[] = ['variable', 'n'];
                $offset += 1;
                continue;
            }

            /** Reject parsing if any disallowed characters are encountered */
            return null;
        }

        return $tokens;
    }

    /**
     * Parsing Ternary Expressions
     *
     * @param int $n
     * @return float|null
     */
    private function parseTernary(int $n): ?float
    {
        $condition = $this->parseOr($n);

        if (null === $condition) {
            return null;
        }

        if (null === $this->matchOperator('?')) {
            return $condition;
        }

        $yes = $this->parseTernary($n);

        if (null === $yes || null === $this->matchOperator(':')) {
            return null;
        }

        $no = $this->parseTernary($n);

        return null === $no ? null : ($condition ? $yes : $no);
    }

    /**
     * Parsing Logical OR
     *
     * @param int $n
     * @return float|null
     */
    private function parseOr(int $n): ?float
    {
        $left = $this->parseAnd($n);

        if (null === $left) {
            return null;
        }

        while (null !== $this->matchOperator('||')) {
            $right = $this->parseAnd($n);

            if (null === $right) {
                return null;
            }

            $left = ($left || $right) ? 1.0 : 0.0;
        }

        return $left;
    }

    /**
     * Parsing Logical AND
     *
     * @param int $n
     * @return float|null
     */
    private function parseAnd(int $n): ?float
    {
        $left = $this->parseEquality($n);

        if (null === $left) {
            return null;
        }

        while (null !== $this->matchOperator('&&')) {
            $right = $this->parseEquality($n);

            if (null === $right) {
                return null;
            }

            $left = ($left && $right) ? 1.0 : 0.0;
        }

        return $left;
    }

    /**
     * Analyzing Equality Comparisons
     *
     * @param int $n
     * @return float|null
     */
    private function parseEquality(int $n): ?float
    {
        $left = $this->parseRelational($n);

        if (null === $left) {
            return null;
        }

        while (null !== ($operator = $this->matchOperator(['==', '!=']))) {
            $right = $this->parseRelational($n);

            if (null === $right) {
                return null;
            }

            $left = ('==' === $operator ? $left == $right : $left != $right) ? 1.0 : 0.0;
        }

        return $left;
    }

    /**
     * Analysis of Size Comparison
     *
     * @param int $n
     * @return float|null
     */
    private function parseRelational(int $n): ?float
    {
        $left = $this->parseAdditive($n);

        if (null === $left) {
            return null;
        }

        while (null !== ($operator = $this->matchOperator(['<', '>', '<=', '>=']))) {
            $right = $this->parseAdditive($n);

            if (null === $right) {
                return null;
            }

            switch ($operator) {
                case '<':
                    $left = $left < $right ? 1.0 : 0.0;
                    break;
                case '>':
                    $left = $left > $right ? 1.0 : 0.0;
                    break;
                case '<=':
                    $left = $left <= $right ? 1.0 : 0.0;
                    break;
                default:
                    $left = $left >= $right ? 1.0 : 0.0;
                    break;
            }
        }

        return $left;
    }

    /**
     * Parsing Addition and Subtraction Operations
     *
     * @param int $n
     * @return float|null
     */
    private function parseAdditive(int $n): ?float
    {
        $left = $this->parseMultiplicative($n);

        if (null === $left) {
            return null;
        }

        while (null !== ($operator = $this->matchOperator(['+', '-']))) {
            $right = $this->parseMultiplicative($n);

            if (null === $right) {
                return null;
            }

            $left = '+' === $operator ? $left + $right : $left - $right;
        }

        return $left;
    }

    /**
     * An Analysis of Integer Division and Modulo Operations
     *
     * @param int $n
     * @return float|null
     */
    private function parseMultiplicative(int $n): ?float
    {
        $left = $this->parseUnary($n);

        if (null === $left) {
            return null;
        }

        while (null !== ($operator = $this->matchOperator(['*', '/', '%']))) {
            $right = $this->parseUnary($n);

            if (null === $right) {
                return null;
            }

            if ('*' === $operator) {
                $left *= $right;
            } elseif ('/' === $operator) {
                if (0.0 == $right) {
                    return null;
                }

                $left /= $right;
            } else {
                if (0.0 == $right) {
                    return null;
                }

                $left = (float) (intval($left) % intval($right));
            }
        }

        return $left;
    }

    /**
     * Parsing Unary Operations
     *
     * @param int $n
     * @return float|null
     */
    private function parseUnary(int $n): ?float
    {
        if (null !== $this->matchOperator('!')) {
            $value = $this->parseUnary($n);
            return null === $value ? null : ($value ? 0.0 : 1.0);
        }

        if (null !== $this->matchOperator('-')) {
            $value = $this->parseUnary($n);
            return null === $value ? null : - $value;
        }

        if (null !== $this->matchOperator('+')) {
            return $this->parseUnary($n);
        }

        return $this->parsePrimary($n);
    }

    /**
     * Parsing numbers, variables, and parentheses
     *
     * @param int $n
     * @return float|null
     */
    private function parsePrimary(int $n): ?float
    {
        $token = $this->pluralTokens[$this->pluralPosition] ?? null;

        if (null === $token) {
            return null;
        }

        if ('number' === $token[0]) {
            $this->pluralPosition++;
            return (float) $token[1];
        }

        if ('variable' === $token[0]) {
            $this->pluralPosition++;
            return (float) $n;
        }

        if (null === $this->matchOperator('(')) {
            return null;
        }

        $value = $this->parseTernary($n);

        if (null === $value || null === $this->matchOperator(')')) {
            return null;
        }

        return $value;
    }

    /**
     * Attempt to match an operator.
     *
     * @param string|array $operators
     * @return string|null Upon a successful match, advance by one position and return the operator.
     */
    private function matchOperator($operators): ?string
    {
        $token = $this->pluralTokens[$this->pluralPosition] ?? null;

        if (null === $token || 'operator' !== $token[0]) {
            return null;
        }

        if (!in_array($token[1], (array) $operators, true)) {
            return null;
        }

        $this->pluralPosition++;
        return $token[1];
    }
}
