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
 * Parses Markdown into an AST
 */
class CommonMark_DocParser
{
    const CODE_INDENT = 4;

    /**
     * @var BlockElement
     */
    protected $tip;

    /**
     * @var BlockElement
     */
    protected $doc;

    /**
     * @var InlineParser
     */
    protected $inlineParser;

    /**
     * @var ReferenceMap
     */
    protected $refMap;

    /**
     * Convert tabs to spaces on each line using a 4-space tab stop
     * @param string $string
     *
     * @return string
     */
    protected static function detabLine($string)
    {
        if (strpos($string, "\t") === false) {
            return $string;
        }

        // Split into different parts
        $parts = explode("\t", $string);
        // Add each part to the resulting line
        // The first one is done here; others are prefixed
        // with the necessary spaces inside the loop below
        $line = $parts[0];
        unset($parts[0]);

        foreach ($parts as $part) {
            // Calculate number of spaces; insert them followed by the non-tab contents
            $amount = 4 - Typecho_Common::strLen($line, 'UTF-8') % 4;
            $line .= str_repeat(' ', $amount) . $part;
        }

        return $line;
    }

    /**
     * Break out of all containing lists, resetting the tip of the
     * document to the parent of the highest list, and finalizing
     * all the lists.  (This is used to implement the "two blank lines
     * break of of all lists" feature.)
     *
     * @param BlockElement $block
     * @param int          $lineNumber
     */
    protected function breakOutOfLists(CommonMark_Element_BlockElement $block, $lineNumber)
    {
        $b = $block;
        $lastList = null;
        do {
            if ($b->getType() === CommonMark_Element_BlockElement::TYPE_LIST) {
                $lastList = $b;
            }

            $b = $b->getParent();
        } while ($b);

        if ($lastList) {
            while ($block != $lastList) {
                $this->finalize($block, $lineNumber);
                $block = $block->getParent();
            }
            $this->finalize($lastList, $lineNumber);
            $this->tip = $lastList->getParent();
        }
    }

    /**
     * @param string $ln
     * @param int    $offset
     *
     * @throws \RuntimeException
     */
    protected function addLine($ln, $offset)
    {
        $s = substr($ln, $offset);
        if ($s === false) {
            $s = '';
        }

        if (!$this->tip->getIsOpen()) {
            throw new RuntimeException(sprintf('Attempted to add line (%s) to closed container.', $ln));
        }

        $this->tip->getStrings()->add($s);
    }

    /**
     * @param string $tag
     * @param int    $lineNumber
     * @param int    $offset
     *
     * @return BlockElement
     */
    protected function addChild($tag, $lineNumber, $offset)
    {
        while (!$this->tip->canContain($tag)) {
            $this->finalize($this->tip, $lineNumber);
        }

        $columnNumber = $offset + 1; // offset 0 = column 1
        $newBlock = new CommonMark_Element_BlockElement($tag, $lineNumber, $columnNumber);
        $this->tip->getChildren()->add($newBlock);
        $newBlock->setParent($this->tip);
        $this->tip = $newBlock;

        return $newBlock;
    }

    /**
     * @param string $ln
     * @param int    $offset
     *
     * @return array|null
     */
    protected function parseListMarker($ln, $offset)
    {
        $rest = substr($ln, $offset);
        $data = array();

        if (preg_match(CommonMark_Util_RegexHelper::getInstance()->getHRuleRegex(), $rest)) {
            return null;
        }

        if ($matches = CommonMark_Util_RegexHelper::matchAll('/^[*+-]( +|$)/', $rest)) {
            $spacesAfterMarker = strlen($matches[1]);
            $data['type'] = CommonMark_Element_BlockElement::LIST_TYPE_UNORDERED;
            $data['delimiter'] = null;
            $data['bullet_char'] = $matches[0][0];
        } elseif ($matches = CommonMark_Util_RegexHelper::matchAll('/^(\d+)([.)])( +|$)/', $rest)) {
            $spacesAfterMarker = strlen($matches[3]);
            $data['type'] = CommonMark_Element_BlockElement::LIST_TYPE_ORDERED;
            $data['start'] = intval($matches[1]);
            $data['delimiter'] = $matches[2];
            $data['bullet_char'] = null;
        } else {
            return null;
        }

        $blankItem = strlen($matches[0]) === strlen($rest);
        if ($spacesAfterMarker >= 5 ||
            $spacesAfterMarker < 1 ||
            $blankItem
        ) {
            $data['padding'] = strlen($matches[0]) - $spacesAfterMarker + 1;
        } else {
            $data['padding'] = strlen($matches[0]);
        }

        return $data;
    }

    /**
     * @param array $listData
     * @param array $itemData
     *
     * @return bool
     */
    protected function listsMatch($listData, $itemData)
    {
        return ($listData['type'] === $itemData['type'] &&
            $listData['delimiter'] === $itemData['delimiter'] &&
            $listData['bullet_char'] === $itemData['bullet_char']);
    }

    /**
     * @param DocParser $self 
     * @param mixed $oldTip 
     * @param mixed $lastMatchedContainer 
     * @param mixed $lineNumber 
     * @param bool $closeUnmatchedBlocksAlreadyDone 
     */
    protected function closeUnmatchedBlocks(CommonMark_DocParser $self, $oldTip, $lastMatchedContainer, $lineNumber, &$closeUnmatchedBlocksAlreadyDone)
    {
        // finalize any blocks not matched
        while (!$closeUnmatchedBlocksAlreadyDone && $oldTip != $lastMatchedContainer && $oldTip !== null) {
            $self->finalize($oldTip, $lineNumber);
            $oldTip = $oldTip->getParent();
        }
        $closeUnmatchedBlocksAlreadyDone = true;
    }

    /**
     * @param string $ln
     * @param int    $lineNumber
     */
    protected function incorporateLine($ln, $lineNumber)
    {
        $allMatched = true;
        $offset = 0;
        $blank = false;
        $container = $this->doc;
        $oldTip = $this->tip;

        // Convert tabs to spaces:
        $ln = self::detabLine($ln);

        // For each containing block, try to parse the associated line start.
        // Bail out on failure: container will point to the last matching block.
        // Set all_matched to false if not all containers match.
        while ($container->hasChildren()) {
            /** @var BlockElement $lastChild */
            $lastChild = $container->getChildren()->last();
            if (!$lastChild->getIsOpen()) {
                break;
            }

            $container = $lastChild;

            $match = CommonMark_Util_RegexHelper::matchAt('/[^ ]/', $ln, $offset);
            if ($match === null) {
                $firstNonSpace = strlen($ln);
                $blank = true;
            } else {
                $firstNonSpace = $match;
                $blank = false;
            }

            $indent = $firstNonSpace - $offset;

            switch ($container->getType()) {
                case CommonMark_Element_BlockElement::TYPE_BLOCK_QUOTE:
                    $matched = $indent <= 3 && isset($ln[$firstNonSpace]) && $ln[$firstNonSpace] === '>';
                    if ($matched) {
                        $offset = $firstNonSpace + 1;
                        if (isset($ln[$offset]) && $ln[$offset] === ' ') {
                            $offset++;
                        }
                    } else {
                        $allMatched = false;
                    }
                    break;

                case CommonMark_Element_BlockElement::TYPE_LIST_ITEM:
                    $listData = $container->getExtra('list_data');
                    $increment = $listData['marker_offset'] + $listData['padding'];
                    if ($indent >= $increment) {
                        $offset += $increment;
                    } elseif ($blank) {
                        $offset = $firstNonSpace;
                    } else {
                        $allMatched = false;
                    }
                    break;

                case CommonMark_Element_BlockElement::TYPE_INDENTED_CODE:
                    if ($indent >= self::CODE_INDENT) {
                        $offset += self::CODE_INDENT;
                    } elseif ($blank) {
                        $offset = $firstNonSpace;
                    } else {
                        $allMatched = false;
                    }
                    break;

                case CommonMark_Element_BlockElement::TYPE_ATX_HEADER:
                case CommonMark_Element_BlockElement::TYPE_SETEXT_HEADER:
                case CommonMark_Element_BlockElement::TYPE_HORIZONTAL_RULE:
                    // a header can never contain > 1 line, so fail to match:
                    $allMatched = false;
                    break;

                case CommonMark_Element_BlockElement::TYPE_FENCED_CODE:
                    // skip optional spaces of fence offset
                    $i = $container->getExtra('fence_offset');
                    while ($i > 0 && $ln[$offset] === ' ') {
                        $offset++;
                        $i--;
                    }
                    break;

                case CommonMark_Element_BlockElement::TYPE_HTML_BLOCK:
                    if ($blank) {
                        $allMatched = false;
                    }
                    break;

                case CommonMark_Element_BlockElement::TYPE_PARAGRAPH:
                    if ($blank) {
                        $container->setIsLastLineBlank(true);
                        $allMatched = false;
                    }
                    break;

                default:
                    // Nothing
            }

            if (!$allMatched) {
                $container = $container->getParent(); // back up to the last matching block
                break;
            }
        }

        $lastMatchedContainer = $container;

        // This function is used to finalize and close any unmatched
        // blocks.  We aren't ready to do this now, because we might
        // have a lazy paragraph continuation, in which case we don't
        // want to close unmatched blocks.  So we store this closure for
        // use later, when we have more information.
        $closeUnmatchedBlocksAlreadyDone = false;

        // Check to see if we've hit 2nd blank line; if so break out of list:
        if ($blank && $container->getIsLastLineBlank()) {
            $this->breakOutOfLists($container, $lineNumber);
        }

        // Unless last matched container is a code block, try new container starts,
        // adding children to the last matched container:
        while ($container->getType() != CommonMark_Element_BlockElement::TYPE_FENCED_CODE &&
            $container->getType() != CommonMark_Element_BlockElement::TYPE_INDENTED_CODE &&
            $container->getType() != CommonMark_Element_BlockElement::TYPE_HTML_BLOCK &&
            // this is a little performance optimization
            CommonMark_Util_RegexHelper::matchAt('/^[ #`~*+_=<>0-9-]/', $ln, $offset) !== null
        ) {
            $match = CommonMark_Util_RegexHelper::matchAt('/[^ ]/', $ln, $offset);
            if ($match === null) {
                $firstNonSpace = strlen($ln);
                $blank = true;
            } else {
                $firstNonSpace = $match;
                $blank = false;
            }

            $indent = $firstNonSpace - $offset;

            if ($indent >= self::CODE_INDENT) {
                // indented code
                if ($this->tip->getType() != CommonMark_Element_BlockElement::TYPE_PARAGRAPH && !$blank) {
                    $offset += self::CODE_INDENT;
                    $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                    $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_INDENTED_CODE, $lineNumber, $offset);
                } else { // ident > 4 in a lazy paragraph continuation
                    break;
                }
            } elseif (!$blank && $ln[$firstNonSpace] === '>') {
                // blockquote
                $offset = $firstNonSpace + 1;
                // optional following space
                if (isset($ln[$offset]) && $ln[$offset] === ' ') {
                    $offset++;
                }
                $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_BLOCK_QUOTE, $lineNumber, $offset);
            } elseif ($match = CommonMark_Util_RegexHelper::matchAll('/^#{1,6}(?: +|$)/', $ln, $firstNonSpace)) {
                // ATX header
                $offset = $firstNonSpace + strlen($match[0]);
                $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_ATX_HEADER, $lineNumber, $firstNonSpace);
                $container->setExtra('level', strlen(trim($match[0]))); // number of #s
                // remove trailing ###s
                $container->getStrings()->add(
                    preg_replace(
                        '/(?:(\\\\#) *#*| *#+) *$/',
                        '$1',
                        substr($ln, $offset)
                    )
                );
                break;
            } elseif ($match = CommonMark_Util_RegexHelper::matchAll('/^`{3,}(?!.*`)|^~{3,}(?!.*~)/', $ln, $firstNonSpace)) {
                // fenced code block
                $fenceLength = strlen($match[0]);
                $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_FENCED_CODE, $lineNumber, $firstNonSpace);
                $container->setExtra('fence_length', $fenceLength);
                $container->setExtra('fence_char', $match[0][0]);
                $container->setExtra('fence_offset', $firstNonSpace - $offset);
                $offset = $firstNonSpace + $fenceLength;
                break;
            } elseif (CommonMark_Util_RegexHelper::matchAt(
                    CommonMark_Util_RegexHelper::getInstance()->getHtmlBlockOpenRegex(),
                    $ln,
                    $firstNonSpace
                ) !== null
            ) {
                // html block
                $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_HTML_BLOCK, $lineNumber, $firstNonSpace);
                // note, we don't adjust offset because the tag is part of the text
                break;
            } elseif ($container->getType() === CommonMark_Element_BlockElement::TYPE_PARAGRAPH &&
                $container->getStrings()->count() === 1 &&
                ($match = CommonMark_Util_RegexHelper::matchAll('/^(?:=+|-+) *$/', $ln, $firstNonSpace))
            ) {
                // setext header line
                $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                $container->setType(CommonMark_Element_BlockElement::TYPE_SETEXT_HEADER);
                $container->setExtra('level', $match[0][0] === '=' ? 1 : 2);
                $offset = strlen($ln);
            } elseif (CommonMark_Util_RegexHelper::matchAt(CommonMark_Util_RegexHelper::getInstance()->getHRuleRegex(), $ln, $firstNonSpace) !== null) {
                // hrule
                $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_HORIZONTAL_RULE, $lineNumber, $firstNonSpace);
                $offset = strlen($ln) - 1;
                break;
            } elseif (($data = $this->parseListMarker($ln, $firstNonSpace))) {
                // list item
                $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);
                $data['marker_offset'] = $indent;
                $offset = $firstNonSpace + $data['padding'];

                // add the list if needed
                if ($container->getType() !== CommonMark_Element_BlockElement::TYPE_LIST ||
                    !($this->listsMatch($container->getExtra('list_data'), $data))
                ) {
                    $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_LIST, $lineNumber, $firstNonSpace);
                    $container->setExtra('list_data', $data);
                }

                // add the list item
                $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_LIST_ITEM, $lineNumber, $firstNonSpace);
                $container->setExtra('list_data', ($data));
            } else {
                break;
            }

            if ($container->acceptsLines()) {
                // if it's a line container, it can't contain other containers
                break;
            }
        }

        // What remains at the offset is a text line.  Add the text to the appropriate container.

        $match = CommonMark_Util_RegexHelper::matchAt('/[^ ]/', $ln, $offset);
        if ($match === null) {
            $firstNonSpace = strlen($ln);
            $blank = true;
        } else {
            $firstNonSpace = $match;
            $blank = false;
        }

        $indent = $firstNonSpace - $offset;

        // First check for a lazy paragraph continuation:
        if ($this->tip !== $lastMatchedContainer &&
            !$blank &&
            $this->tip->getType() == CommonMark_Element_BlockElement::TYPE_PARAGRAPH &&
            $this->tip->getStrings()->count() > 0
        ) {
            // lazy paragraph continuation
            $this->lastLineBlank = false; // TODO: really? (see line 1152)
            $this->addLine($ln, $offset);
        } else { // not a lazy continuation
            //finalize any blocks not matched
            $this->closeUnmatchedBlocks($this, $oldTip, $lastMatchedContainer, $lineNumber, $closeUnmatchedBlocksAlreadyDone);

            // Block quote lines are never blank as they start with >
            // and we don't count blanks in fenced code for purposes of tight/loose
            // lists or breaking out of lists.  We also don't set last_line_blank
            // on an empty list item.
            $container->setIsLastLineBlank(
                $blank &&
                !(
                    $container->getType() == CommonMark_Element_BlockElement::TYPE_BLOCK_QUOTE ||
                    $container->getType() == CommonMark_Element_BlockElement::TYPE_FENCED_CODE ||
                    ($container->getType() == CommonMark_Element_BlockElement::TYPE_LIST_ITEM &&
                        $container->getChildren()->count() === 0 &&
                        $container->getStartLine() == $lineNumber
                    )
                )
            );

            $cont = $container;
            while ($cont->getParent()) {
                $cont->getParent()->setIsLastLineBlank(false);
                $cont = $cont->getParent();
            }

            switch ($container->getType()) {
                case CommonMark_Element_BlockElement::TYPE_INDENTED_CODE:
                case CommonMark_Element_BlockElement::TYPE_HTML_BLOCK:
                    $this->addLine($ln, $offset);
                    break;

                case CommonMark_Element_BlockElement::TYPE_FENCED_CODE:
                    // check for closing code fence
                    $test = ($indent <= 3 &&
                        isset($ln[$firstNonSpace]) &&
                        $ln[$firstNonSpace] == $container->getExtra('fence_char') &&
                        $match = CommonMark_Util_RegexHelper::matchAll('/^(?:`{3,}|~{3,})(?= *$)/', $ln, $firstNonSpace)
                    );
                    if ($test && strlen($match[0]) >= $container->getExtra('fence_length')) {
                        // don't add closing fence to container; instead, close it:
                        $this->finalize($container, $lineNumber);
                    } else {
                        $this->addLine($ln, $offset);
                    }
                    break;

                case CommonMark_Element_BlockElement::TYPE_ATX_HEADER:
                case CommonMark_Element_BlockElement::TYPE_SETEXT_HEADER:
                case CommonMark_Element_BlockElement::TYPE_HORIZONTAL_RULE:
                    // nothing to do; we already added the contents.
                    break;

                default:
                    if ($container->acceptsLines()) {
                        $this->addLine($ln, $firstNonSpace);
                    } elseif ($blank) {
                        // do nothing
                    } elseif ($container->getType() != CommonMark_Element_BlockElement::TYPE_HORIZONTAL_RULE && $container->getType(
                        ) != CommonMark_Element_BlockElement::TYPE_SETEXT_HEADER
                    ) {
                        // create paragraph container for line
                        $container = $this->addChild(CommonMark_Element_BlockElement::TYPE_PARAGRAPH, $lineNumber, $firstNonSpace);
                        $this->addLine($ln, $firstNonSpace);
                    } else {
                        // TODO: throw exception?
                    }
            }
        }
    }

    /**
     * @param BlockElement $block
     * @param int          $lineNumber
     */
    public function finalize(CommonMark_Element_BlockElement $block, $lineNumber)
    {
        $block->finalize($lineNumber, $this->inlineParser, $this->refMap);

        $this->tip = $block->getParent(); // typo on 1310?
    }

    /**
     * The main parsing function. Returns a parsed document AST.
     * @param string $input
     *
     * @return BlockElement
     *
     * @api
     */
    public function parse($input)
    {
        $this->doc = new CommonMark_Element_BlockElement(CommonMark_Element_BlockElement::TYPE_DOCUMENT, 1, 1);
        $this->tip = $this->doc;

        $this->inlineParser = new CommonMark_InlineParser();
        $this->refMap = new CommonMark_Reference_ReferenceMap();

        // Remove any /n which appears at the very end of the string
        if (substr($input, -1) == "\n") {
            $input = substr($input, 0, -1);
        }

        $lines = preg_split('/\r\n|\n|\r/', $input);
        $len = count($lines);
        for ($i = 0; $i < $len; $i++) {
            $this->incorporateLine($lines[$i], $i + 1);
        }

        while ($this->tip) {
            $this->finalize($this->tip, $len - 1);
        }

        $this->doc->processInlines($this->inlineParser, $this->refMap);

        return $this->doc;
    }
}
