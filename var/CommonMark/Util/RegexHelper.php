<?php

/*
 * This file is part of the commonmark-php package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on stmd.js
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Provides regular expressions and utilties for parsing Markdown
 *
 * Singletons are generally bad, but it allows us to build the regexes once (and only once).
 */
class CommonMark_Util_RegexHelper
{
    const ESCAPABLE = 0;
    const ESCAPED_CHAR = 1;
    const IN_DOUBLE_QUOTES = 2;
    const IN_SINGLE_QUOTES = 3;
    const IN_PARENS = 4;
    const REG_CHAR = 5;
    const IN_PARENS_NOSP = 6;
    const TAGNAME = 7;
    const BLOCKTAGNAME = 8;
    const ATTRIBUTENAME = 9;
    const UNQUOTEDVALUE = 10;
    const SINGLEQUOTEDVALUE = 11;
    const DOUBLEQUOTEDVALUE = 12;
    const ATTRIBUTEVALUE = 13;
    const ATTRIBUTEVALUESPEC = 14;
    const ATTRIBUTE = 15;
    const OPENTAG = 16;
    const CLOSETAG = 17;
    const OPENBLOCKTAG = 18;
    const CLOSEBLOCKTAG = 19;
    const HTMLCOMMENT = 20;
    const PROCESSINGINSTRUCTION = 21;
    const DECLARATION = 22;
    const CDATA = 23;
    const HTMLTAG = 24;
    const HTMLBLOCKOPEN = 25;
    const LINK_TITLE = 26;

    const REGEX_ESCAPABLE = '[!"#$%&\'()*+,.\/:;<=>?@[\\\\\]^_`{|}~-]';

    protected $regex = array();

    static protected $instance;

    /**
     * Constructor
     */
    protected function __construct()
    {
        $this->buildRegexPatterns();
    }

    /**
     * @return RegexHelper
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new CommonMark_Util_RegexHelper();
        }

        return self::$instance;
    }

    /**
     * Builds the regular expressions required to parse Markdown
     *
     * We could hard-code them all as pre-built constants, but that would be more difficult to manage.
     */
    protected function buildRegexPatterns()
    {
        $regex = array();
        $regex[self::ESCAPABLE] = self::REGEX_ESCAPABLE;
        $regex[self::ESCAPED_CHAR] = '\\\\' . $regex[self::ESCAPABLE];
        $regex[self::IN_DOUBLE_QUOTES] = '"(' . $regex[self::ESCAPED_CHAR] . '|[^"\x00])*"';
        $regex[self::IN_SINGLE_QUOTES] = '\'(' . $regex[self::ESCAPED_CHAR] . '|[^\'\x00])*\'';
        $regex[self::IN_PARENS] = '\\((' . $regex[self::ESCAPED_CHAR] . '|[^)\x00])*\\)';
        $regex[self::REG_CHAR] = '[^\\\\()\x00-\x20]';
        $regex[self::IN_PARENS_NOSP] = '\((' . $regex[self::REG_CHAR] . '|' . $regex[self::ESCAPED_CHAR] . ')*\)';
        $regex[self::TAGNAME] = '[A-Za-z][A-Za-z0-9]*';
        $regex[self::BLOCKTAGNAME] = '(?:article|header|aside|hgroup|iframe|blockquote|hr|body|li|map|button|object|canvas|ol|caption|output|col|p|colgroup|pre|dd|progress|div|section|dl|table|td|dt|tbody|embed|textarea|fieldset|tfoot|figcaption|th|figure|thead|footer|footer|tr|form|ul|h1|h2|h3|h4|h5|h6|video|script|style)';
        $regex[self::ATTRIBUTENAME] = '[a-zA-Z_:][a-zA-Z0-9:._-]*';
        $regex[self::UNQUOTEDVALUE] = '[^"\'=<>`\x00-\x20]+';
        $regex[self::SINGLEQUOTEDVALUE] = '\'[^\']*\'';
        $regex[self::DOUBLEQUOTEDVALUE] = '"[^"]*"';
        $regex[self::ATTRIBUTEVALUE] = '(?:' . $regex[self::UNQUOTEDVALUE] . '|' . $regex[self::SINGLEQUOTEDVALUE] . '|' . $regex[self::DOUBLEQUOTEDVALUE] . ')';
        $regex[self::ATTRIBUTEVALUESPEC] = '(?:' . '\s*=' . '\s*' . $regex[self::ATTRIBUTEVALUE] . ')';
        $regex[self::ATTRIBUTE] = '(?:' . '\s+' . $regex[self::ATTRIBUTENAME] . $regex[self::ATTRIBUTEVALUESPEC] . '?)';
        $regex[self::OPENTAG] = '<' . $regex[self::TAGNAME] . $regex[self::ATTRIBUTE] . '*' . '\s*\/?>';
        $regex[self::CLOSETAG] = '<\/' . $regex[self::TAGNAME] . '\s*[>]';
        $regex[self::OPENBLOCKTAG] = '<' . $regex[self::BLOCKTAGNAME] . $regex[self::ATTRIBUTE] . '*' . '\s*\/?>';
        $regex[self::CLOSEBLOCKTAG] = '<\/' . $regex[self::BLOCKTAGNAME] . '\s*[>]';
        $regex[self::HTMLCOMMENT] = '<!--([^-]+|[-][^-]+)*-->';
        $regex[self::PROCESSINGINSTRUCTION] = '[<][?].*?[?][>]';
        $regex[self::DECLARATION] = '<![A-Z]+' . '\s+[^>]*>';
        $regex[self::CDATA] = '<!\[CDATA\[([^\]]+|\][^\]]|\]\][^>])*\]\]>';
        $regex[self::HTMLTAG] = '(?:' . $regex[self::OPENTAG] . '|' . $regex[self::CLOSETAG] . '|' . $regex[self::HTMLCOMMENT] . '|' .
            $regex[self::PROCESSINGINSTRUCTION] . '|' . $regex[self::DECLARATION] . '|' . $regex[self::CDATA] . ')';
        $regex[self::HTMLBLOCKOPEN] = '<(?:' . $regex[self::BLOCKTAGNAME] . '[\s\/>]' . '|' .
            '\/' . $regex[self::BLOCKTAGNAME] . '[\s>]' . '|' . '[?!])';
        $regex[self::LINK_TITLE] = '^(?:"(' . $regex[self::ESCAPED_CHAR] . '|[^"\x00])*"' .
            '|' . '\'(' . $regex[self::ESCAPED_CHAR] . '|[^\'\x00])*\'' .
            '|' . '\((' . $regex[self::ESCAPED_CHAR] . '|[^)\x00])*\))';

        $this->regex = $regex;
    }

    /**
     * Returns a partial regex
     *
     * It'll need to be wrapped with /.../ before use
     * @param int $const
     *
     * @return string
     */
    public function getPartialRegex($const)
    {
        return $this->regex[$const];
    }

    /**
     * @return string
     */
    public function getHtmlTagRegex()
    {
        return '/^' . $this->regex[self::HTMLTAG] . '/i';
    }

    /**
     * @return string
     */
    public function getHtmlBlockOpenRegex()
    {
        return '/^' . $this->regex[self::HTMLBLOCKOPEN] . '/i';
    }

    /**
     * @return string
     */
    public function getLinkTitleRegex()
    {
        return '/' . $this->regex[self::LINK_TITLE] . '/';
    }

    /**
     * @return string
     */
    public function getLinkDestinationRegex()
    {
        return '/^' . '(?:' . $this->regex[self::REG_CHAR] . '+|' . $this->regex[self::ESCAPED_CHAR] . '|' . $this->regex[self::IN_PARENS_NOSP] . ')*' . '/';
    }

    /**
     * @return string
     */
    public function getLinkDestinationBracesRegex()
    {
        return '/^(?:' . '[<](?:[^<>\\n\\\\\\x00]' . '|' . $this->regex[self::ESCAPED_CHAR] . '|' . '\\\\)*[>]' . ')/';
    }

    /**
     * @return string
     */
    public function getHRuleRegex()
    {
        return '/^(?:(?:\* *){3,}|(?:_ *){3,}|(?:- *){3,}) *$/';
    }

    /**
     * Matches a character with a special meaning in markdown,
     * or a string of non-special characters.
     *
     * @return string
     */
    public function getMainRegex()
    {
        return '/^(?:[\n`\[\]\\\\!<&*_]|[^\n`\[\]\\\\!<&*_]+)/m';
    }

    /**
     * Attempt to match a regex in string s at offset offset
     * @param string $regex
     * @param string $string
     * @param int    $offset
     *
     * @return int|null Index of match, or null
     */
    public static function matchAt($regex, $string, $offset)
    {
        $matches = array();
        $string = substr($string, $offset);
        if (!preg_match($regex, $string, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        return $offset + $matches[0][1];
    }

    /**
     * Functional wrapper around preg_match_all
     *
     * @param string $pattern
     * @param string $subject
     * @param int    $offset
     *
     * @return array|null
     */
    public static function matchAll($pattern, $subject, $offset = 0)
    {
        $matches = array();
        $subject = substr($subject, $offset);
        preg_match_all($pattern, $subject, $matches, PREG_PATTERN_ORDER);

        $fullMatches = reset($matches);
        if (empty($fullMatches)) {
            return null;
        }

        if (count($fullMatches) == 1) {
            foreach ($matches as &$match) {
                $match = reset($match);
            }
        }

        return !empty($matches) ? $matches : null;
    }

    /**
     * Replace backslash escapes with literal characters
     * @param string $string
     *
     * @return string
     */
    public static function unescape($string)
    {
        $allEscapedChar = '/\\\\(' . self::REGEX_ESCAPABLE . ')/';

        return preg_replace($allEscapedChar, '$1', $string);
    }
}
