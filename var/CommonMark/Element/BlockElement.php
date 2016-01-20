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
 * Block-level element
 */
class CommonMark_Element_BlockElement
{
    const TYPE_ATX_HEADER = 'ATXHeader';
    const TYPE_BLOCK_QUOTE = 'BlockQuote';
    const TYPE_DOCUMENT = 'Document';
    const TYPE_FENCED_CODE = 'FencedCode';
    const TYPE_HORIZONTAL_RULE = 'HorizontalRule';
    const TYPE_HTML_BLOCK = 'HtmlBlock';
    const TYPE_INDENTED_CODE = 'IndentedCode';
    const TYPE_LIST = 'List';
    const TYPE_LIST_ITEM = 'ListItem';
    const TYPE_PARAGRAPH = 'Paragraph';
    const TYPE_REFERENCE_DEF = 'ReferenceDef';
    const TYPE_SETEXT_HEADER = 'SetextHeader';

    const LIST_TYPE_ORDERED = 'Outline';
    const LIST_TYPE_UNORDERED = 'Bullet';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var bool
     */
    protected $open = true;

    /**
     * @var bool
     */
    protected $lastLineBlank = false;

    /**
     * @var int
     */
    protected $startLine;

    /**
     * @var int
     */
    protected $startColumn;

    /**
     * @var int
     */
    protected $endLine;

    /**
     * @var ArrayCollection|BlockELement[]
     */
    protected $children;

    /**
     * @var BlockElement|null
     */
    protected $parent = null;

    /**
     * This is formed by concatenating strings, in finalize:
     * @var string
     */
    protected $stringContent = '';

    /**
     * @var string[]
     */
    protected $strings;

    /**
     * @var ArrayCollection|InlineElementInterface[]
     */
    protected $inlineContent;

    /**
     * Extra data storage
     * @var array
     */
    protected $extras = array();

    /**
     * Constrcutor
     *
     * @param string $type        Block type (see TYPE_ constants)
     * @param int    $startLine   Line where the block element starts
     * @param int    $startColumn Column where the block element starts
     */
    public function __construct($type, $startLine, $startColumn)
    {
        $this->type = $type;
        $this->startLine = $startLine;
        $this->startColumn = $startColumn;
        $this->endLine = $startLine;

        $this->children = new CommonMark_Util_ArrayCollection();
        $this->strings = new CommonMark_Util_ArrayCollection();
        $this->inlineContent = new CommonMark_Util_ArrayCollection();
    }

    /**
     * Returns true if parent block can contain child block
     *
     * @param mixed $childType The type of child block to add (see TYPE_ constants)
     *
     * @return bool
     */
    public function canContain($childType)
    {
        $parentType = $this->type;

        return ($parentType == self::TYPE_DOCUMENT ||
            $parentType == self::TYPE_BLOCK_QUOTE ||
            $parentType == self::TYPE_LIST_ITEM ||
            ($parentType == self::TYPE_LIST && $childType == self::TYPE_LIST_ITEM));
    }

    /**
     * Returns true if block type can accept lines of text
     *
     * @return bool
     */
    public function acceptsLines()
    {
        return ($this->type == self::TYPE_PARAGRAPH ||
            $this->type == self::TYPE_INDENTED_CODE ||
            $this->type == self::TYPE_FENCED_CODE);
    }

    /**
     * Whether the block ends with a blank line
     *
     * @return bool
     */
    public function endsWithBlankLine()
    {
        if ($this->lastLineBlank) {
            return true;
        }

        if (($this->type == self::TYPE_LIST || $this->type == self::TYPE_LIST_ITEM) && $this->hasChildren()) {
            return $this->getChildren()->last()->endsWithBlankLine();
        }

        return false;
    }

    /**
     * @return ArrayCollection|BlockElement[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !$this->children->isEmpty();
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return BlockElement|null
     */
    public function getParent()
    {
        return $this->parent ? $this->parent : null;
    }

    /**
     * Whether the block is open for modifications
     *
     * @return bool
     */
    public function getIsOpen()
    {
        return $this->open;
    }

    /**
     * @return ArrayCollection|string[]
     */
    public function getStrings()
    {
        return $this->strings;
    }

    /**
     * @param BlockElement $parent
     *
     * @return $this
     */
    public function setParent(CommonMark_Element_BlockElement $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsLastLineBlank()
    {
        return $this->lastLineBlank;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIsLastLineBlank($value)
    {
        $this->lastLineBlank = $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIsOpen($value)
    {
        $this->open = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getStartLine()
    {
        return $this->startLine;
    }

    /**
     * @param int $lineNumber
     *
     * @return $this
     */
    public function setEndLine($lineNumber)
    {
        $this->endLine = $lineNumber;

        return $this;
    }

    /**
     * @return ArrayCollection|InlineElementInterface[]
     */
    public function getInlineContent()
    {
        return $this->inlineContent;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getExtra($key)
    {
        return isset($this->extras[$key]) ? $this->extras[$key] : null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setExtra($key, $value)
    {
        $this->extras[$key] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getStringContent()
    {
        return $this->stringContent;
    }

    /**
     * Returns true if string contains only space characters
     *
     * @return bool
     */
    private function isStringContentBlank()
    {
        return preg_match('/^\s*$/', $this->stringContent) == 1;
    }

    /**
     * Finalize the block; mark it closed for modification
     *
     * @param int          $lineNumber
     * @param InlineParser $inlineParser
     * @param ReferenceMap $refMap
     */
    public function finalize($lineNumber, CommonMark_InlineParser $inlineParser, CommonMark_Reference_ReferenceMap $refMap)
    {
        if (!$this->open) {
            return;
        }

        $this->open = false;
        if ($lineNumber > $this->startLine) {
            $this->endLine = $lineNumber - 1;
        } else {
            $this->endLine = $lineNumber;
        }

        switch ($this->getType()) {
            case self::TYPE_PARAGRAPH:
                $this->stringContent = preg_replace(
                    '/^  */m',
                    '',
                    implode("\n", $this->strings->toArray())
                );

                // Try parsing the beginning as link reference definitions:
                while ($this->stringContent[0] === '[' &&
                    ($pos = $inlineParser->parseReference($this->stringContent, $refMap))
                ) {
                    $this->stringContent = substr($this->stringContent, $pos);
                    if ($this->isStringContentBlank()) { //RegexHelper::getInstance()->isBlank($this->stringContent)) {
                        $this->type = self::TYPE_REFERENCE_DEF;
                        break;
                    }
                }
                break;

            case self::TYPE_ATX_HEADER:
            case self::TYPE_SETEXT_HEADER:
            case self::TYPE_HTML_BLOCK:
                $this->stringContent = implode("\n", $this->strings->toArray());
                break;

            case self::TYPE_INDENTED_CODE:
                $reversed = array_reverse($this->strings->toArray(), true);
                foreach ($reversed as $index => $line) {
                    if ($line == '' || $line === "\n" || preg_match('/^(\n *)$/', $line)) {
                        unset($reversed[$index]);
                    } else {
                        break;
                    }
                }
                $fixed = array_reverse($reversed);
                $tmp = implode("\n", $fixed);
                if (substr($tmp, -1) !== "\n") {
                    $tmp .= "\n";
                }

                $this->stringContent = $tmp;
                break;

            case self::TYPE_FENCED_CODE:
                // first line becomes info string
                $this->setExtra('info', CommonMark_Util_RegexHelper::unescape(trim($this->strings->first())));
                if ($this->strings->count() == 1) {
                    $this->stringContent = '';
                } else {
                    $this->stringContent = implode("\n", $this->strings->slice(1)) . "\n";
                }
                break;

            case self::TYPE_LIST:
                $this->setExtra('tight', true); // tight by default

                $numItems = $this->children->count();
                $i = 0;
                while ($i < $numItems) {
                    /** @var BlockElement $item */
                    $item = $this->children->get($i);
                    // check for non-final list item ending with blank line:
                    $lastItem = $i == $numItems - 1;
                    if ($item->endsWithBlankLine() && !$lastItem) {
                        $this->setExtra('tight', false);
                        break;
                    }

                    // Recurse into children of list item, to see if there are
                    // spaces between any of them:
                    $numSubItems = $item->getChildren()->count();
                    $j = 0;
                    while ($j < $numSubItems) {
                        $subItem = $item->getChildren()->get($j);
                        $lastSubItem = $j == $numSubItems - 1;
                        if ($subItem->endsWithBlankLine() && !($lastItem && $lastSubItem)) {
                            $this->setExtra('tight', false);
                            break;
                        }

                        $j++;
                    }

                    $i++;
                }

                break;

            default:
                break;
        }
    }

    /**
     * @param InlineParser $inlineParser
     * @param ReferenceMap $refMap
     */
    public function processInlines(CommonMark_InlineParser $inlineParser, CommonMark_Reference_ReferenceMap $refMap)
    {
        switch ($this->getType()) {
            case self::TYPE_PARAGRAPH:
            case self::TYPE_SETEXT_HEADER:
            case self::TYPE_ATX_HEADER:
                $this->inlineContent = $inlineParser->parse(trim($this->stringContent), $refMap);
                $this->stringContent = '';
                break;
            default:
                break;
        }

        if ($this->hasChildren()) {
            foreach ($this->getChildren() as $child) {
                $child->processInlines($inlineParser, $refMap);
            }
        }
    }
}
