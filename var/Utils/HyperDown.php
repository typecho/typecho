<?php

namespace Utils;

/**
 * Parser
 *
 * @copyright Copyright (c) 2012 SegmentFault Team. (http://segmentfault.com)
 * @author Joyqi <joyqi@segmentfault.com>
 * @license BSD License
 */
class HyperDown
{
    /**
     * _whiteList
     *
     * @var string
     */
    private $_commonWhiteList = 'kbd|b|i|strong|em|sup|sub|br|code|del|a|hr|small';

    /**
     * html tags
     *
     * @var string
     */
    private $_blockHtmlTags = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|form|fieldset|iframe|hr|legend|article|section|nav|aside|hgroup|header|footer|figcaption|svg|script|noscript';

    /**
     * _specialWhiteList
     *
     * @var mixed
     * @access private
     */
    private $_specialWhiteList = [
        'table' => 'table|tbody|thead|tfoot|tr|td|th'
    ];

    /**
     * _footnotes
     *
     * @var array
     */
    private $_footnotes;

    /**
     * @var bool
     */
    private $_html = false;

    /**
     * @var bool
     */
    private $_line = false;

    /**
     * @var array
     */
    private $blockParsers = [
        ['code', 10],
        ['shtml', 20],
        ['pre', 30],
        ['ahtml', 40],
        ['shr', 50],
        ['list', 60],
        ['math', 70],
        ['html', 80],
        ['footnote', 90],
        ['definition', 100],
        ['quote', 110],
        ['table', 120],
        ['sh', 130],
        ['mh', 140],
        ['dhr', 150],
        ['default', 9999]
    ];

    /**
     * _blocks
     *
     * @var array
     */
    private $_blocks;

    /**
     * _current
     *
     * @var string
     */
    private $_current;

    /**
     * _pos
     *
     * @var int
     */
    private $_pos;

    /**
     * _definitions
     *
     * @var array
     */
    private $_definitions;

    /**
     * @var array
     */
    private $_hooks = [];

    /**
     * @var array
     */
    private $_holders;

    /**
     * @var string
     */
    private $_uniqid;

    /**
     * @var int
     */
    private $_id;

    /**
     * @var array
     */
    private $_parsers = [];

    /**
     * makeHtml
     *
     * @param mixed $text
     *
     * @return string
     */
    public function makeHtml($text): string
    {
        $this->_footnotes = [];
        $this->_definitions = [];
        $this->_holders = [];
        $this->_uniqid = md5(uniqid());
        $this->_id = 0;

        usort($this->blockParsers, function ($a, $b) {
            return $a[1] < $b[1] ? - 1 : 1;
        });

        foreach ($this->blockParsers as $parser) {
            [$name] = $parser;

            if (isset($parser[2])) {
                $this->_parsers[$name] = $parser[2];
            } else {
                $this->_parsers[$name] = [$this, 'parseBlock' . ucfirst($name)];
            }
        }

        $text = $this->initText($text);
        $html = $this->parse($text);
        $html = $this->makeFootnotes($html);
        $html = $this->optimizeLines($html);

        return $this->call('makeHtml', $html);
    }

    /**
     * @param bool $html
     */
    public function enableHtml(bool $html = true)
    {
        $this->_html = $html;
    }

    /**
     * @param bool $line
     */
    public function enableLine(bool $line = true)
    {
        $this->_line = $line;
    }

    /**
     * @param string $type
     * @param callable $callback
     */
    public function hook(string $type, callable $callback)
    {
        $this->_hooks[$type][] = $callback;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function makeHolder(string $str): string
    {
        $key = "\r" . $this->_uniqid . $this->_id . "\r";
        $this->_id ++;
        $this->_holders[$key] = $str;

        return $key;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function initText(string $text): string
    {
        $text = str_replace(["\t", "\r"], ['    ', ''], $text);
        return $text;
    }

    /**
     * @param string $html
     *
     * @return string
     */
    private function makeFootnotes(string $html): string
    {
        if (count($this->_footnotes) > 0) {
            $html .= '<div class="footnotes"><hr><ol>';
            $index = 1;

            while ($val = array_shift($this->_footnotes)) {
                if (is_string($val)) {
                    $val .= " <a href=\"#fnref-{$index}\" class=\"footnote-backref\">&#8617;</a>";
                } else {
                    $val[count($val) - 1] .= " <a href=\"#fnref-{$index}\" class=\"footnote-backref\">&#8617;</a>";
                    $val = count($val) > 1 ? $this->parse(implode("\n", $val)) : $this->parseInline($val[0]);
                }

                $html .= "<li id=\"fn-{$index}\">{$val}</li>";
                $index ++;
            }

            $html .= '</ol></div>';
        }

        return $html;
    }

    /**
     * parse
     *
     * @param string $text
     * @param bool $inline
     * @param int $offset
     *
     * @return string
     */
    private function parse(string $text, bool $inline = false, int $offset = 0): string
    {
        $blocks = $this->parseBlock($text, $lines);
        $html = '';

        // inline mode for single normal block
        if ($inline && count($blocks) == 1 && $blocks[0][0] == 'normal') {
            $blocks[0][3] = true;
        }

        foreach ($blocks as $block) {
            [$type, $start, $end, $value] = $block;
            $extract = array_slice($lines, $start, $end - $start + 1);
            $method = 'parse' . ucfirst($type);

            $extract = $this->call('before' . ucfirst($method), $extract, $value);
            $result = $this->{$method}($extract, $value, $start + $offset, $end + $offset);
            $result = $this->call('after' . ucfirst($method), $result, $value);

            $html .= $result;
        }

        return $html;
    }

    /**
     * @param string $text
     * @param bool $clearHolders
     *
     * @return string
     */
    private function releaseHolder(string $text, bool $clearHolders = true): string
    {
        $deep = 0;
        while (strpos($text, "\r") !== false && $deep < 10) {
            $text = str_replace(array_keys($this->_holders), array_values($this->_holders), $text);
            $deep ++;
        }

        if ($clearHolders) {
            $this->_holders = [];
        }

        return $text;
    }

    /**
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    private function markLine(int $start, int $end = - 1): string
    {
        if ($this->_line) {
            $end = $end < 0 ? $start : $end;
            return '<span class="line" data-start="' . $start
                . '" data-end="' . $end . '" data-id="' . $this->_uniqid . '"></span>';
        }

        return '';
    }

    /**
     * @param array $lines
     * @param int $start
     *
     * @return string[]
     */
    private function markLines(array $lines, int $start): array
    {
        $i = - 1;

        return $this->_line ? array_map(function ($line) use ($start, &$i) {
            $i ++;
            return $this->markLine($start + $i) . $line;
        }, $lines) : $lines;
    }

    /**
     * @param string $html
     *
     * @return string
     */
    private function optimizeLines(string $html): string
    {
        $last = 0;

        return $this->_line ?
            preg_replace_callback("/class=\"line\" data\-start=\"([0-9]+)\" data\-end=\"([0-9]+)\" (data\-id=\"{$this->_uniqid}\")/",
                function ($matches) use (&$last) {
                    if ($matches[1] != $last) {
                        $replace = 'class="line" data-start="' . $last . '" data-start-original="' . $matches[1] . '" data-end="' . $matches[2] . '" ' . $matches[3];
                    } else {
                        $replace = $matches[0];
                    }

                    $last = $matches[2] + 1;
                    return $replace;
                }, $html) : $html;
    }

    /**
     * @param string $type
     * @param ...$args
     *
     * @return mixed
     */
    private function call(string $type, ...$args)
    {
        $value = $args[0];

        if (empty($this->_hooks[$type])) {
            return $value;
        }

        foreach ($this->_hooks[$type] as $callback) {
            $value = call_user_func_array($callback, $args);
            $args[0] = $value;
        }

        return $value;
    }

    /**
     * parseInline
     *
     * @param string $text
     * @param string $whiteList
     * @param bool $clearHolders
     * @param bool $enableAutoLink
     *
     * @return string
     */
    private function parseInline(
        string $text,
        string $whiteList = '',
        bool $clearHolders = true,
        bool $enableAutoLink = true
    ): string {
        $text = $this->call('beforeParseInline', $text);

        // code
        $text = preg_replace_callback(
            "/(^|[^\\\])(`+)(.+?)\\2/",
            function ($matches) {
                return $matches[1] . $this->makeHolder(
                        '<code>' . htmlspecialchars($matches[3]) . '</code>'
                    );
            },
            $text
        );

        // mathjax
        $text = preg_replace_callback(
            "/(^|[^\\\])(\\$+)(.+?)\\2/",
            function ($matches) {
                return $matches[1] . $this->makeHolder(
                        $matches[2] . htmlspecialchars($matches[3]) . $matches[2]
                    );
            },
            $text
        );

        // escape
        $text = preg_replace_callback(
            "/\\\(.)/u",
            function ($matches) {
                $prefix = preg_match("/^[-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]$/", $matches[1]) ? '' : '\\';
                $escaped = htmlspecialchars($matches[1]);
                $escaped = str_replace('$', '&dollar;', $escaped);
                return $this->makeHolder($prefix . $escaped);
            },
            $text
        );

        // link
        $text = preg_replace_callback(
            "/<(https?:\/\/.+|(?:mailto:)?[_a-z0-9-\.\+]+@[_\w-]+(?:\.[a-z]{2,})+)>/i",
            function ($matches) {
                $url = $this->cleanUrl($matches[1]);
                $link = $this->call('parseLink', $url);

                return $this->makeHolder(
                    "<a href=\"{$url}\">{$link}</a>"
                );
            },
            $text
        );

        // encode unsafe tags
        $text = preg_replace_callback(
            "/<(\/?)([a-z0-9-]+)(\s+[^>]*)?>/i",
            function ($matches) use ($whiteList) {
                if ($this->_html || false !== stripos(
                        '|' . $this->_commonWhiteList . '|' . $whiteList . '|', '|' . $matches[2] . '|'
                    )) {
                    return $this->makeHolder($matches[0]);
                } else {
                    return $this->makeHolder(htmlspecialchars($matches[0]));
                }
            },
            $text
        );

        if ($this->_html) {
            $text = preg_replace_callback("/<!\-\-(.*?)\-\->/", function ($matches) {
                return $this->makeHolder($matches[0]);
            }, $text);
        }

        $text = str_replace(['<', '>'], ['&lt;', '&gt;'], $text);

        // footnote
        $text = preg_replace_callback(
            "/\[\^((?:[^\]]|\\\\\]|\\\\\[)+?)\]/",
            function ($matches) {
                $id = array_search($matches[1], $this->_footnotes);

                if (false === $id) {
                    $id = count($this->_footnotes) + 1;
                    $this->_footnotes[$id] = $this->parseInline($matches[1], '', false);
                }

                return $this->makeHolder(
                    "<sup id=\"fnref-{$id}\"><a href=\"#fn-{$id}\" class=\"footnote-ref\">{$id}</a></sup>"
                );
            },
            $text
        );

        // image
        $text = preg_replace_callback(
            "/!\[((?:[^\]]|\\\\\]|\\\\\[)*?)\]\(((?:[^\)]|\\\\\)|\\\\\()+?)\)/",
            function ($matches) {
                $escaped = htmlspecialchars($this->escapeBracket($matches[1]));
                $url = $this->escapeBracket($matches[2]);
                [$url, $title] = $this->cleanUrl($url, true);
                $title = empty($title) ? $escaped : " title=\"{$title}\"";

                return $this->makeHolder(
                    "<img src=\"{$url}\" alt=\"{$title}\" title=\"{$title}\">"
                );
            },
            $text
        );

        $text = preg_replace_callback(
            "/!\[((?:[^\]]|\\\\\]|\\\\\[)*?)\]\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]/",
            function ($matches) {
                $escaped = htmlspecialchars($this->escapeBracket($matches[1]));

                $result = isset($this->_definitions[$matches[2]]) ?
                    "<img src=\"{$this->_definitions[$matches[2]]}\" alt=\"{$escaped}\" title=\"{$escaped}\">"
                    : $escaped;

                return $this->makeHolder($result);
            },
            $text
        );

        // link
        $text = preg_replace_callback(
            "/\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]\(((?:[^\)]|\\\\\)|\\\\\()+?)\)/",
            function ($matches) {
                $escaped = $this->parseInline(
                    $this->escapeBracket($matches[1]), '', false, false
                );
                $url = $this->escapeBracket($matches[2]);
                [$url, $title] = $this->cleanUrl($url, true);
                $title = empty($title) ? '' : " title=\"{$title}\"";

                return $this->makeHolder("<a href=\"{$url}\"{$title}>{$escaped}</a>");
            },
            $text
        );

        $text = preg_replace_callback(
            "/\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]\[((?:[^\]]|\\\\\]|\\\\\[)+?)\]/",
            function ($matches) {
                $escaped = $this->parseInline(
                    $this->escapeBracket($matches[1]), '', false
                );
                $result = isset($this->_definitions[$matches[2]]) ?
                    "<a href=\"{$this->_definitions[$matches[2]]}\">{$escaped}</a>"
                    : $escaped;

                return $this->makeHolder($result);
            },
            $text
        );

        // strong and em and some fuck
        $text = $this->parseInlineCallback($text);
        $text = preg_replace(
            "/<([_a-z0-9-\.\+]+@[^@]+\.[a-z]{2,})>/i",
            "<a href=\"mailto:\\1\">\\1</a>",
            $text
        );

        // autolink url
        if ($enableAutoLink) {
            $text = preg_replace_callback(
                "/(^|[^\"])(https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\b([-a-zA-Z0-9@:%_\+.~#?&\/=]*)|(?:mailto:)?[_a-z0-9-\.\+]+@[_\w-]+(?:\.[a-z]{2,})+)($|[^\"])/",
                function ($matches) {
                    $url = $this->cleanUrl($matches[2]);
                    $link = $this->call('parseLink', $matches[2]);
                    return "{$matches[1]}<a href=\"{$url}\">{$link}</a>{$matches[5]}";
                },
                $text
            );
        }

        $text = $this->call('afterParseInlineBeforeRelease', $text);
        $text = $this->releaseHolder($text, $clearHolders);

        $text = $this->call('afterParseInline', $text);

        return $text;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function parseInlineCallback(string $text): string
    {
        $text = preg_replace_callback(
            "/(\*{3})(.+?)\\1/",
            function ($matches) {
                return '<strong><em>' .
                    $this->parseInlineCallback($matches[2]) .
                    '</em></strong>';
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\*{2})(.+?)\\1/",
            function ($matches) {
                return '<strong>' .
                    $this->parseInlineCallback($matches[2]) .
                    '</strong>';
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\*)(.+?)\\1/",
            function ($matches) {
                return '<em>' .
                    $this->parseInlineCallback($matches[2]) .
                    '</em>';
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\s+|^)(_{3})(.+?)\\2(\s+|$)/",
            function ($matches) {
                return $matches[1] . '<strong><em>' .
                    $this->parseInlineCallback($matches[3]) .
                    '</em></strong>' . $matches[4];
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\s+|^)(_{2})(.+?)\\2(\s+|$)/",
            function ($matches) {
                return $matches[1] . '<strong>' .
                    $this->parseInlineCallback($matches[3]) .
                    '</strong>' . $matches[4];
            },
            $text
        );

        $text = preg_replace_callback(
            "/(\s+|^)(_)(.+?)\\2(\s+|$)/",
            function ($matches) {
                return $matches[1] . '<em>' .
                    $this->parseInlineCallback($matches[3]) .
                    '</em>' . $matches[4];
            },
            $text
        );

        $text = preg_replace_callback(
            "/(~{2})(.+?)\\1/",
            function ($matches) {
                return '<del>' .
                    $this->parseInlineCallback($matches[2]) .
                    '</del>';
            },
            $text
        );

        return $text;
    }

    /**
     * parseBlock
     *
     * @param string $text
     * @param array|null $lines
     *
     * @return array
     */
    private function parseBlock(string $text, ?array &$lines): array
    {
        $lines = explode("\n", $text);
        $this->_blocks = [];
        $this->_current = 'normal';
        $this->_pos = - 1;

        $state = [
            'special' => implode("|", array_keys($this->_specialWhiteList)),
            'empty'   => 0,
            'html'    => false
        ];

        // analyze by line
        foreach ($lines as $key => $line) {
            $block = $this->getBlock();
            $args = [$block, $key, $line, &$state, $lines];

            if ($this->_current != 'normal') {
                $pass = call_user_func_array($this->_parsers[$this->_current], $args);

                if (!$pass) {
                    continue;
                }
            }

            foreach ($this->_parsers as $name => $parser) {
                if ($name != $this->_current) {
                    $pass = call_user_func_array($parser, $args);

                    if (!$pass) {
                        break;
                    }
                }
            }
        }

        return $this->optimizeBlocks($this->_blocks, $lines);
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     *
     * @return bool
     */
    private function parseBlockList(?array $block, int $key, string $line, ?array &$state): bool
    {
        if ($this->isBlock('list') && !preg_match("/^\s*\[((?:[^\]]|\\]|\\[)+?)\]:\s*(.+)$/", $line)) {
            if (preg_match("/^(\s*)(~{3,}|`{3,})([^`~]*)$/i", $line)) {
                // ignore code
                return true;
            } elseif ($state['empty'] <= 1
                && preg_match("/^(\s*)\S+/", $line, $matches)
                && strlen($matches[1]) >= ($block[3][0] + $state['empty'])) {

                $state['empty'] = 0;
                $this->setBlock($key);
                return false;
            } elseif (preg_match("/^(\s*)$/", $line) && $state['empty'] == 0) {
                $state['empty'] ++;
                $this->setBlock($key);
                return false;
            }
        }

        if (preg_match("/^(\s*)((?:[0-9]+\.)|\-|\+|\*)\s+/i", $line, $matches)) {
            $space = strlen($matches[1]);
            $tab = strlen($matches[0]) - $space;
            $state['empty'] = 0;
            $type = false !== strpos('+-*', $matches[2]) ? 'ul' : 'ol';

            // opened
            if ($this->isBlock('list')) {
                if ($space < $block[3][0] || ($space == $block[3][0] && $type != $block[3][1])) {
                    $this->startBlock('list', $key, [$space, $type, $tab]);
                } else {
                    $this->setBlock($key);
                }
            } else {
                $this->startBlock('list', $key, [$space, $type, $tab]);
            }

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     *
     * @return bool
     */
    private function parseBlockCode(?array $block, int $key, string $line, ?array &$state): bool
    {
        if (preg_match("/^(\s*)(~{3,}|`{3,})([^`~]*)$/i", $line, $matches)) {
            if ($this->isBlock('code')) {
                if ($state['code'] != $matches[2]) {
                    $this->setBlock($key);
                    return false;
                }

                $isAfterList = $block[3][2];

                if ($isAfterList) {
                    $state['empty'] = 0;
                    $this->combineBlock()
                        ->setBlock($key);
                } else {
                    $this->setBlock($key)
                        ->endBlock();
                }
            } else {
                $isAfterList = false;

                if ($this->isBlock('list')) {
                    $space = $block[3][0];

                    $isAfterList = strlen($matches[1]) >= $space + $state['empty'];
                }

                $state['code'] = $matches[2];

                $this->startBlock('code', $key, [
                    $matches[1], $matches[3], $isAfterList
                ]);
            }

            return false;
        } elseif ($this->isBlock('code')) {
            $this->setBlock($key);
            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     *
     * @return bool
     */
    private function parseBlockShtml(?array $block, int $key, string $line, ?array &$state): bool
    {
        if ($this->_html) {
            if (preg_match("/^(\s*)!!!(\s*)$/", $line, $matches)) {
                if ($this->isBlock('shtml')) {
                    $this->setBlock($key)->endBlock();
                } else {
                    $this->startBlock('shtml', $key);
                }

                return false;
            } elseif ($this->isBlock('shtml')) {
                $this->setBlock($key);
                return false;
            }
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     *
     * @return bool
     */
    private function parseBlockAhtml(?array $block, int $key, string $line, ?array &$state): bool
    {
        if ($this->_html) {
            if (preg_match("/^\s*<({$this->_blockHtmlTags})(\s+[^>]*)?>/i", $line, $matches)) {
                if ($this->isBlock('ahtml')) {
                    $this->setBlock($key);
                    return false;
                } elseif (empty($matches[2]) || $matches[2] != '/') {
                    $this->startBlock('ahtml', $key);
                    preg_match_all("/<({$this->_blockHtmlTags})(\s+[^>]*)?>/i", $line, $allMatches);
                    $lastMatch = $allMatches[1][count($allMatches[0]) - 1];

                    if (strpos($line, "</{$lastMatch}>") !== false) {
                        $this->endBlock();
                    } else {
                        $state['html'] = $lastMatch;
                    }
                    return false;
                }
            } elseif (!!$state['html'] && strpos($line, "</{$state['html']}>") !== false) {
                $this->setBlock($key)->endBlock();
                $state['html'] = false;
                return false;
            } elseif ($this->isBlock('ahtml')) {
                $this->setBlock($key);
                return false;
            } elseif (preg_match("/^\s*<!\-\-(.*?)\-\->\s*$/", $line, $matches)) {
                $this->startBlock('ahtml', $key)->endBlock();
                return false;
            }
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     *
     * @return bool
     */
    private function parseBlockMath(?array $block, int $key, string $line): bool
    {
        if (preg_match("/^(\s*)\\$\\$(\s*)$/", $line, $matches)) {
            if ($this->isBlock('math')) {
                $this->setBlock($key)->endBlock();
            } else {
                $this->startBlock('math', $key);
            }

            return false;
        } elseif ($this->isBlock('math')) {
            $this->setBlock($key);
            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     *
     * @return bool
     */
    private function parseBlockPre(?array $block, int $key, string $line, ?array &$state): bool
    {
        if (preg_match("/^ {4}/", $line)) {
            if ($this->isBlock('pre')) {
                $this->setBlock($key);
            } else {
                $this->startBlock('pre', $key);
            }

            return false;
        } elseif ($this->isBlock('pre') && preg_match("/^\s*$/", $line)) {
            $this->setBlock($key);
            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     *
     * @return bool
     */
    private function parseBlockHtml(?array $block, int $key, string $line, ?array &$state): bool
    {
        if (preg_match("/^\s*<({$state['special']})(\s+[^>]*)?>/i", $line, $matches)) {
            $tag = strtolower($matches[1]);
            if (!$this->isBlock('html', $tag) && !$this->isBlock('pre')) {
                $this->startBlock('html', $key, $tag);
            }

            return false;
        } elseif (preg_match("/<\/({$state['special']})>\s*$/i", $line, $matches)) {
            $tag = strtolower($matches[1]);

            if ($this->isBlock('html', $tag)) {
                $this->setBlock($key)
                    ->endBlock();
            }

            return false;
        } elseif ($this->isBlock('html')) {
            $this->setBlock($key);
            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     *
     * @return bool
     */
    private function parseBlockFootnote(?array $block, int $key, string $line): bool
    {
        if (preg_match("/^\[\^((?:[^\]]|\\]|\\[)+?)\]:/", $line, $matches)) {
            $space = strlen($matches[0]) - 1;
            $this->startBlock('footnote', $key, [
                $space, $matches[1]
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     *
     * @return bool
     */
    private function parseBlockDefinition(?array $block, int $key, string $line): bool
    {
        if (preg_match("/^\s*\[((?:[^\]]|\\]|\\[)+?)\]:\s*(.+)$/", $line, $matches)) {
            $this->_definitions[$matches[1]] = $this->cleanUrl($matches[2]);
            $this->startBlock('definition', $key)
                ->endBlock();

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     *
     * @return bool
     */
    private function parseBlockQuote(?array $block, int $key, string $line): bool
    {
        if (preg_match("/^(\s*)>/", $line, $matches)) {
            if ($this->isBlock('list') && strlen($matches[1]) > 0) {
                $this->setBlock($key);
            } elseif ($this->isBlock('quote')) {
                $this->setBlock($key);
            } else {
                $this->startBlock('quote', $key);
            }

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     * @param array|null $lines
     *
     * @return bool
     */
    private function parseBlockTable(?array $block, int $key, string $line, ?array &$state, array $lines): bool
    {
        if (preg_match("/^((?:(?:(?:\||\+)(?:[ :]*\-+[ :]*)(?:\||\+))|(?:(?:[ :]*\-+[ :]*)(?:\||\+)(?:[ :]*\-+[ :]*))|(?:(?:[ :]*\-+[ :]*)(?:\||\+))|(?:(?:\||\+)(?:[ :]*\-+[ :]*)))+)$/", $line, $matches)) {
            if ($this->isBlock('table')) {
                $block[3][0][] = $block[3][2];
                $block[3][2] ++;
                $this->setBlock($key, $block[3]);
            } else {
                $head = 0;

                if (empty($block) ||
                    $block[0] != 'normal' ||
                    preg_match("/^\s*$/", $lines[$block[2]])) {
                    $this->startBlock('table', $key);
                } else {
                    $head = 1;
                    $this->backBlock(1, 'table');
                }

                if ($matches[1][0] == '|') {
                    $matches[1] = substr($matches[1], 1);

                    if ($matches[1][strlen($matches[1]) - 1] == '|') {
                        $matches[1] = substr($matches[1], 0, - 1);
                    }
                }

                $rows = preg_split("/(\+|\|)/", $matches[1]);
                $aligns = [];
                foreach ($rows as $row) {
                    $align = 'none';

                    if (preg_match("/^\s*(:?)\-+(:?)\s*$/", $row, $matches)) {
                        if (!empty($matches[1]) && !empty($matches[2])) {
                            $align = 'center';
                        } elseif (!empty($matches[1])) {
                            $align = 'left';
                        } elseif (!empty($matches[2])) {
                            $align = 'right';
                        }
                    }

                    $aligns[] = $align;
                }

                $this->setBlock($key, [[$head], $aligns, $head + 1]);
            }

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     *
     * @return bool
     */
    private function parseBlockSh(?array $block, int $key, string $line): bool
    {
        if (preg_match("/^(#+)(.*)$/", $line, $matches)) {
            $num = min(strlen($matches[1]), 6);
            $this->startBlock('sh', $key, $num)
                ->endBlock();

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     * @param array $lines
     *
     * @return bool
     */
    private function parseBlockMh(?array $block, int $key, string $line, ?array &$state, array $lines): bool
    {
        if (preg_match("/^\s*((=|-){2,})\s*$/", $line, $matches)
            && ($block && $block[0] == "normal" && !preg_match("/^\s*$/", $lines[$block[2]]))) {    // check if last line isn't empty
            if ($this->isBlock('normal')) {
                $this->backBlock(1, 'mh', $matches[1][0] == '=' ? 1 : 2)
                    ->setBlock($key)
                    ->endBlock();
            } else {
                $this->startBlock('normal', $key);
            }

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     *
     * @return bool
     */
    private function parseBlockShr(?array $block, int $key, string $line): bool
    {
        if (preg_match("/^(\* *){3,}\s*$/", $line)) {
            $this->startBlock('hr', $key)
                ->endBlock();

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     *
     * @return bool
     */
    private function parseBlockDhr(?array $block, int $key, string $line): bool
    {
        if (preg_match("/^(- *){3,}\s*$/", $line)) {
            $this->startBlock('hr', $key)
                ->endBlock();

            return false;
        }

        return true;
    }

    /**
     * @param array|null $block
     * @param int $key
     * @param string $line
     * @param array|null $state
     *
     * @return bool
     */
    private function parseBlockDefault(?array $block, int $key, string $line, ?array &$state): bool
    {
        if ($this->isBlock('footnote')) {
            preg_match("/^(\s*)/", $line, $matches);
            if (strlen($matches[1]) >= $block[3][0]) {
                $this->setBlock($key);
            } else {
                $this->startBlock('normal', $key);
            }
        } elseif ($this->isBlock('table')) {
            if (false !== strpos($line, '|')) {
                $block[3][2] ++;
                $this->setBlock($key, $block[3]);
            } else {
                $this->startBlock('normal', $key);
            }
        } elseif ($this->isBlock('quote')) {
            if (!preg_match("/^(\s*)$/", $line)) { // empty line
                $this->setBlock($key);
            } else {
                $this->startBlock('normal', $key);
            }
        } else {
            if (empty($block) || $block[0] != 'normal') {
                $this->startBlock('normal', $key);
            } else {
                $this->setBlock($key);
            }
        }

        return true;
    }

    /**
     * @param array $blocks
     * @param array $lines
     *
     * @return array
     */
    private function optimizeBlocks(array $blocks, array $lines): array
    {
        $blocks = $this->call('beforeOptimizeBlocks', $blocks, $lines);

        $key = 0;
        while (isset($blocks[$key])) {
            $moved = false;

            $block = &$blocks[$key];
            $prevBlock = $blocks[$key - 1] ?? null;
            $nextBlock = $blocks[$key + 1] ?? null;

            [$type, $from, $to] = $block;

            if ('pre' == $type) {
                $isEmpty = array_reduce(
                    array_slice($lines, $block[1], $block[2] - $block[1] + 1),
                    function ($result, $line) {
                        return preg_match("/^\s*$/", $line) && $result;
                    },
                    true
                );

                if ($isEmpty) {
                    $block[0] = $type = 'normal';
                }
            }

            if ('normal' == $type) {
                // combine two blocks
                $types = ['list', 'quote'];

                if ($from == $to && preg_match("/^\s*$/", $lines[$from])
                    && !empty($prevBlock) && !empty($nextBlock)) {
                    if ($prevBlock[0] == $nextBlock[0] && in_array($prevBlock[0], $types)
                        && ($prevBlock[0] != 'list'
                            || ($prevBlock[3][0] == $nextBlock[3][0] && $prevBlock[3][1] == $nextBlock[3][1]))) {
                        // combine 3 blocks
                        $blocks[$key - 1] = [
                            $prevBlock[0], $prevBlock[1], $nextBlock[2], $prevBlock[3] ?? null
                        ];
                        array_splice($blocks, $key, 2);

                        // do not move
                        $moved = true;
                    }
                }
            }

            if (!$moved) {
                $key ++;
            }
        }

        return $this->call('afterOptimizeBlocks', $blocks, $lines);
    }

    /**
     * parseCode
     *
     * @param array $lines
     * @param array $parts
     * @param int $start
     *
     * @return string
     */
    private function parseCode(array $lines, array $parts, int $start): string
    {
        [$blank, $lang] = $parts;
        $lang = trim($lang);
        $count = strlen($blank);

        if (!preg_match("/^[_a-z0-9-\+\#\:\.]+$/i", $lang)) {
            $lang = null;
        } else {
            $parts = explode(':', $lang);
            if (count($parts) > 1) {
                [$lang, $rel] = $parts;
                $lang = trim($lang);
                $rel = trim($rel);
            }
        }

        $isEmpty = true;

        $lines = array_map(function ($line) use ($count, &$isEmpty) {
            $line = preg_replace("/^[ ]{{$count}}/", '', $line);
            if ($isEmpty && !preg_match("/^\s*$/", $line)) {
                $isEmpty = false;
            }

            return htmlspecialchars($line);
        }, array_slice($lines, 1, - 1));
        $str = implode("\n", $this->markLines($lines, $start + 1));

        return $isEmpty ? '' :
            '<pre><code' . (!empty($lang) ? " class=\"{$lang}\"" : '')
            . (!empty($rel) ? " rel=\"{$rel}\"" : '') . '>'
            . $str . '</code></pre>';
    }

    /**
     * parsePre
     *
     * @param array $lines
     * @param mixed $value
     * @param int $start
     *
     * @return string
     */
    private function parsePre(array $lines, $value, int $start): string
    {
        foreach ($lines as &$line) {
            $line = htmlspecialchars(substr($line, 4));
        }

        $str = implode("\n", $this->markLines($lines, $start));
        return preg_match("/^\s*$/", $str) ? '' : '<pre><code>' . $str . '</code></pre>';
    }

    /**
     * parseAhtml
     *
     * @param array $lines
     * @param mixed $value
     * @param int $start
     *
     * @return string
     */
    private function parseAhtml(array $lines, $value, int $start): string
    {
        return trim(implode("\n", $this->markLines($lines, $start)));
    }

    /**
     * parseShtml
     *
     * @param array $lines
     * @param mixed $value
     * @param int $start
     *
     * @return string
     */
    private function parseShtml(array $lines, $value, int $start): string
    {
        return trim(implode("\n", $this->markLines(array_slice($lines, 1, - 1), $start + 1)));
    }

    /**
     * parseMath
     *
     * @param array $lines
     * @param mixed $value
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    private function parseMath(array $lines, $value, int $start, int $end): string
    {
        return '<p>' . $this->markLine($start, $end) . htmlspecialchars(implode("\n", $lines)) . '</p>';
    }

    /**
     * parseSh
     *
     * @param array $lines
     * @param int $num
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    private function parseSh(array $lines, int $num, int $start, int $end): string
    {
        $line = $this->markLine($start, $end) . $this->parseInline(trim($lines[0], '# '));
        return preg_match("/^\s*$/", $line) ? '' : "<h{$num}>{$line}</h{$num}>";
    }

    /**
     * parseMh
     *
     * @param array $lines
     * @param int $num
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    private function parseMh(array $lines, int $num, int $start, int $end): string
    {
        return $this->parseSh($lines, $num, $start, $end);
    }

    /**
     * parseQuote
     *
     * @param array $lines
     * @param mixed $value
     * @param int $start
     *
     * @return string
     */
    private function parseQuote(array $lines, $value, int $start): string
    {
        foreach ($lines as &$line) {
            $line = preg_replace("/^\s*> ?/", '', $line);
        }
        $str = implode("\n", $lines);

        return preg_match("/^\s*$/", $str) ? '' : '<blockquote>' . $this->parse($str, true, $start) . '</blockquote>';
    }

    /**
     * parseList
     *
     * @param array $lines
     * @param mixed $value
     * @param int $start
     *
     * @return string
     */
    private function parseList(array $lines, $value, int $start): string
    {
        $html = '';
        [$space, $type, $tab] = $value;
        $rows = [];
        $suffix = '';
        $last = 0;

        foreach ($lines as $key => $line) {
            if (preg_match("/^(\s{" . $space . "})((?:[0-9]+\.?)|\-|\+|\*)(\s+)(.*)$/i", $line, $matches)) {
                if ($type == 'ol' && $key == 0) {
                    $olStart = intval($matches[2]);

                    if ($olStart != 1) {
                        $suffix = ' start="' . $olStart . '"';
                    }
                }

                $rows[] = [$matches[4]];
                $last = count($rows) - 1;
            } else {
                $rows[$last][] = preg_replace("/^\s{" . ($tab + $space) . "}/", '', $line);
            }
        }

        foreach ($rows as $row) {
            $html .= "<li>" . $this->parse(implode("\n", $row), true, $start) . "</li>";
            $start += count($row);
        }

        return "<{$type}{$suffix}>{$html}</{$type}>";
    }

    /**
     * @param array $lines
     * @param mixed $value
     * @param int $start
     *
     * @return string
     */
    private function parseTable(array $lines, $value, int $start): string
    {
        [$ignores, $aligns] = $value;
        $head = count($ignores) > 0 && array_sum($ignores) > 0;

        $html = '<table>';
        $body = $head ? null : true;
        $output = false;

        foreach ($lines as $key => $line) {
            if (in_array($key, $ignores)) {
                if ($head && $output) {
                    $head = false;
                    $body = true;
                }

                continue;
            }

            $line = trim($line);
            $output = true;

            if ($line[0] == '|') {
                $line = substr($line, 1);

                if ($line[strlen($line) - 1] == '|') {
                    $line = substr($line, 0, - 1);
                }
            }


            $rows = array_map(function ($row) {
                if (preg_match("/^\s*$/", $row)) {
                    return ' ';
                } else {
                    return trim($row);
                }
            }, explode('|', $line));
            $columns = [];
            $last = - 1;

            foreach ($rows as $row) {
                if (strlen($row) > 0) {
                    $last ++;
                    $columns[$last] = [
                        isset($columns[$last]) ? $columns[$last][0] + 1 : 1, $row
                    ];
                } elseif (isset($columns[$last])) {
                    $columns[$last][0] ++;
                } else {
                    $columns[0] = [1, $row];
                }
            }

            if ($head) {
                $html .= '<thead>';
            } elseif ($body) {
                $html .= '<tbody>';
            }

            $html .= '<tr' . ($this->_line ? ' class="line" data-start="'
                    . ($start + $key) . '" data-end="' . ($start + $key)
                    . '" data-id="' . $this->_uniqid . '"' : '') . '>';

            foreach ($columns as $key => $column) {
                [$num, $text] = $column;
                $tag = $head ? 'th' : 'td';

                $html .= "<{$tag}";
                if ($num > 1) {
                    $html .= " colspan=\"{$num}\"";
                }

                if (isset($aligns[$key]) && $aligns[$key] != 'none') {
                    $html .= " align=\"{$aligns[$key]}\"";
                }

                $html .= '>' . $this->parseInline($text) . "</{$tag}>";
            }

            $html .= '</tr>';

            if ($head) {
                $html .= '</thead>';
            } elseif ($body) {
                $body = false;
            }
        }

        if ($body !== null) {
            $html .= '</tbody>';
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * parseHr
     *
     * @param array $lines
     * @param mixed $value
     * @param int $start
     *
     * @return string
     */
    private function parseHr(array $lines, $value, int $start): string
    {
        return $this->_line ? '<hr class="line" data-start="' . $start . '" data-end="' . $start . '">' : '<hr>';
    }

    /**
     * parseNormal
     *
     * @param array $lines
     * @param mixed $inline
     * @param int $start
     *
     * @return string
     */
    private function parseNormal(array $lines, $inline, int $start): string
    {
        foreach ($lines as $key => &$line) {
            $line = $this->parseInline($line);

            if (!preg_match("/^\s*$/", $line)) {
                $line = $this->markLine($start + $key) . $line;
            }
        }

        $str = trim(implode("\n", $lines));
        $str = preg_replace_callback("/(\n\s*){2,}/", function () use (&$inline) {
            $inline = false;
            return "</p><p>";
        }, $str);
        $str = preg_replace("/\n/", "<br>", $str);

        return preg_match("/^\s*$/", $str) ? '' : ($inline ? $str : "<p>{$str}</p>");
    }

    /**
     * parseFootnote
     *
     * @param array $lines
     * @param array $value
     *
     * @return string
     */
    private function parseFootnote(array $lines, array $value): string
    {
        [$space, $note] = $value;
        $index = array_search($note, $this->_footnotes);

        if (false !== $index) {
            $lines[0] = preg_replace("/^\[\^((?:[^\]]|\\]|\\[)+?)\]:/", '', $lines[0]);
            $this->_footnotes[$index] = $lines;
        }

        return '';
    }

    /**
     * parseDefine
     *
     * @return string
     */
    private function parseDefinition(): string
    {
        return '';
    }

    /**
     * parseHtml
     *
     * @param array $lines
     * @param string $type
     * @param int $start
     *
     * @return string
     */
    private function parseHtml(array $lines, string $type, int $start): string
    {
        foreach ($lines as &$line) {
            $line = $this->parseInline($line,
                $this->_specialWhiteList[$type] ?? '');
        }

        return implode("\n", $this->markLines($lines, $start));
    }

    /**
     * @param $url
     * @param bool $parseTitle
     *
     * @return mixed
     */
    private function cleanUrl($url, bool $parseTitle = false)
    {
        $title = null;
        $url = trim($url);

        if ($parseTitle) {
            $pos = strpos($url, ' ');

            if ($pos !== false) {
                $title = htmlspecialchars(trim(substr($url, $pos + 1), ' "\''));
                $url = substr($url, 0, $pos);
            }
        }

        $url = preg_replace("/[\"'<>\s]/", '', $url);

        if (preg_match("/^(mailto:)?[_a-z0-9-\.\+]+@[_\w-]+(?:\.[a-z]{2,})+$/i", $url, $matches)) {
            if (empty($matches[1])) {
                $url = 'mailto:' . $url;
            }
        }

        if (preg_match("/^\w+:/i", $url) && !preg_match("/^(https?|mailto):/i", $url)) {
            return '#';
        }

        return $parseTitle ? [$url, $title] : $url;
    }

    /**
     * @param $str
     *
     * @return string
     */
    private function escapeBracket($str): string
    {
        return str_replace(
            ['\[', '\]', '\(', '\)'], ['[', ']', '(', ')'], $str
        );
    }

    /**
     * startBlock
     *
     * @param mixed $type
     * @param mixed $start
     * @param mixed $value
     *
     * @return $this
     */
    private function startBlock($type, $start, $value = null): HyperDown
    {
        $this->_pos ++;
        $this->_current = $type;

        $this->_blocks[$this->_pos] = [$type, $start, $start, $value];

        return $this;
    }

    /**
     * endBlock
     *
     * @return $this
     */
    private function endBlock(): HyperDown
    {
        $this->_current = 'normal';
        return $this;
    }

    /**
     * isBlock
     *
     * @param mixed $type
     * @param mixed $value
     *
     * @return bool
     */
    private function isBlock($type, $value = null): bool
    {
        return $this->_current == $type
            && (null === $value || $this->_blocks[$this->_pos][3] == $value);
    }

    /**
     * getBlock
     *
     * @return array
     */
    private function getBlock(): ?array
    {
        return $this->_blocks[$this->_pos] ?? null;
    }

    /**
     * setBlock
     *
     * @param mixed $to
     * @param mixed $value
     *
     * @return $this
     */
    private function setBlock($to = null, $value = null): HyperDown
    {
        if (null !== $to) {
            $this->_blocks[$this->_pos][2] = $to;
        }

        if (null !== $value) {
            $this->_blocks[$this->_pos][3] = $value;
        }

        return $this;
    }

    /**
     * backBlock
     *
     * @param mixed $step
     * @param mixed $type
     * @param mixed $value
     *
     * @return $this
     */
    private function backBlock($step, $type, $value = null): HyperDown
    {
        if ($this->_pos < 0) {
            return $this->startBlock($type, 0, $value);
        }

        $last = $this->_blocks[$this->_pos][2];
        $this->_blocks[$this->_pos][2] = $last - $step;

        if ($this->_blocks[$this->_pos][1] <= $this->_blocks[$this->_pos][2]) {
            $this->_pos ++;
        }

        $this->_current = $type;
        $this->_blocks[$this->_pos] = [
            $type, $last - $step + 1, $last, $value
        ];

        return $this;
    }

    /**
     * @return $this
     */
    private function combineBlock(): HyperDown
    {
        if ($this->_pos < 1) {
            return $this;
        }

        $prev = $this->_blocks[$this->_pos - 1];
        $current = $this->_blocks[$this->_pos];

        $prev[2] = $current[2];
        $this->_blocks[$this->_pos - 1] = $prev;
        $this->_current = $prev[0];
        unset($this->_blocks[$this->_pos]);
        $this->_pos --;

        return $this;
    }
}
