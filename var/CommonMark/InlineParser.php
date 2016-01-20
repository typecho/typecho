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
 * Parses inline elements
 */
class CommonMark_InlineParser
{
    /**
     * @var string
     */
    protected $subject;

    /**
     * @var int
     */
    protected $labelNestLevel = 0; // Used by parseLinkLabel method

    /**
     * @var int
     */
    protected $pos = 0;

    /**
     * @var ReferenceMap
     */
    protected $refmap;

    /**
     * @var RegexHelper
     */
    protected $regexHelper;

    /**
     * Constrcutor
     */
    public function __construct()
    {
        $this->refmap = new CommonMark_Reference_ReferenceMap();
    }

    /**
     * If re matches at current position in the subject, advance
     * position in subject and return the match; otherwise return null
     * @param string $re
     *
     * @return string|null The match (if found); null otherwise
     */
    protected function match($re)
    {
        $matches = array();
        $subject = substr($this->subject, $this->pos);
        if (!preg_match($re, $subject, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        // [0][0] contains the matched text
        // [0][1] contains the index of that match
        $this->pos += $matches[0][1] + strlen($matches[0][0]);

        return $matches[0][0];
    }

    /**
     * Returns the character at the current subject position, or null if
     * there are no more characters
     *
     * @return string|null
     */
    protected function peek()
    {
        $ch = substr($this->subject, $this->pos, 1);
        return false !== $ch && strlen($ch) > 0 ? $ch : null;
    }

    /**
     * Parse zero or more space characters, including at most one newline
     *
     * @return int
     */
    protected function spnl()
    {
        $this->match('/^ *(?:\n *)?/');

        return 1;
    }

    // All of the parsers below try to match something at the current position
    // in the subject.  If they succeed in matching anything, they
    // push an inline element onto the 'inlines' list.  They return the
    // number of characters parsed (possibly 0).

    /**
     * Attempt to parse backticks, adding either a backtick code span or a
     * literal sequence of backticks to the 'inlines' list.
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int Number of characters parsed
     */
    protected function parseBackticks(CommonMark_Util_ArrayCollection $inlines)
    {
        $startpos = $this->pos;
        $ticks = $this->match('/^`+/');
        if (!$ticks) {
            return 0;
        }

        $afterOpenTicks = $this->pos;
        $foundCode = false;
        $match = null;
        while (!$foundCode && ($match = $this->match('/`+/m'))) {
            if ($match == $ticks) {
                $c = substr($this->subject, $afterOpenTicks, $this->pos - $afterOpenTicks - strlen($ticks));
                $c = preg_replace('/[ \n]+/', ' ', $c);
                $inlines->add(CommonMark_Element_InlineCreator::createCode(trim($c)));

                return ($this->pos - $startpos);
            }
        }

        // If we go here, we didn't match a closing backtick sequence
        $inlines->add(CommonMark_Element_InlineCreator::createString($ticks));
        $this->pos = $afterOpenTicks;

        return ($this->pos - $startpos);
    }

    /**
     * Parse a backslash-escaped special character, adding either the escaped
     * character, a hard line break (if the backslash is followed by a newline),
     * or a literal backslash to the 'inlines' list.
     *
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseEscaped(CommonMark_Util_ArrayCollection $inlines)
    {
        $subject = $this->subject;
        $pos = $this->pos;
        if ($subject[$pos] === '\\') {
            if (isset($subject[$pos + 1]) && $subject[$pos + 1] === "\n") {
                $inlines->add(CommonMark_Element_InlineCreator::createHardbreak());
                $this->pos = $this->pos + 2;

                return 2;
            } elseif (isset($subject[$pos + 1]) && preg_match(
                    '/' . CommonMark_Util_RegexHelper::REGEX_ESCAPABLE . '/',
                    $subject[$pos + 1]
                )
            ) {
                $inlines->add(CommonMark_Element_InlineCreator::createString($subject[$pos + 1]));
                $this->pos = $this->pos + 2;

                return 2;
            } else {
                $this->pos++;
                $inlines->add(CommonMark_Element_InlineCreator::createString('\\'));

                return 1;
            }
        } else {
            return 0;
        }
    }

    /**
     * Attempt to parse an autolink (URL or email in pointy brackets)
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseAutolink(CommonMark_Util_ArrayCollection $inlines)
    {
        $emailRegex = '/^<([a-zA-Z0-9.!#$%&\'*+\\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*)>/';
        $otherLinkRegex = '/^<(?:coap|doi|javascript|aaa|aaas|about|acap|cap|cid|crid|data|dav|dict|dns|file|ftp|geo|go|gopher|h323|http|https|iax|icap|im|imap|info|ipp|iris|iris.beep|iris.xpc|iris.xpcs|iris.lwz|ldap|mailto|mid|msrp|msrps|mtqp|mupdate|news|nfs|ni|nih|nntp|opaquelocktoken|pop|pres|rtsp|service|session|shttp|sieve|sip|sips|sms|snmp|soap.beep|soap.beeps|tag|tel|telnet|tftp|thismessage|tn3270|tip|tv|urn|vemmi|ws|wss|xcon|xcon-userid|xmlrpc.beep|xmlrpc.beeps|xmpp|z39.50r|z39.50s|adiumxtra|afp|afs|aim|apt|attachment|aw|beshare|bitcoin|bolo|callto|chrome|chrome-extension|com-eventbrite-attendee|content|cvs|dlna-playsingle|dlna-playcontainer|dtn|dvb|ed2k|facetime|feed|finger|fish|gg|git|gizmoproject|gtalk|hcp|icon|ipn|irc|irc6|ircs|itms|jar|jms|keyparc|lastfm|ldaps|magnet|maps|market|message|mms|ms-help|msnim|mumble|mvn|notes|oid|palm|paparazzi|platform|proxy|psyc|query|res|resource|rmi|rsync|rtmp|secondlife|sftp|sgn|skype|smb|soldat|spotify|ssh|steam|svn|teamspeak|things|udp|unreal|ut2004|ventrilo|view-source|webcal|wtai|wyciwyg|xfire|xri|ymsgr):[^<>\x00-\x20]*>/i';

        if ($m = $this->match($emailRegex)) {
            $email = substr($m, 1, -1);
            $inlines->add(CommonMark_Element_InlineCreator::createLink('mailto:' . $email, $email));

            return strlen($m);
        } elseif ($m = $this->match($otherLinkRegex)) {
            $dest = substr($m, 1, -1);
            $inlines->add(CommonMark_Element_InlineCreator::createLink($dest, $dest));

            return strlen($m);
        } else {
            return 0;
        }
    }

    /**
     * Attempt to parse a raw HTML tag
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseHtmlTag(CommonMark_Util_ArrayCollection $inlines)
    {
        if ($m = $this->match(CommonMark_Util_RegexHelper::getInstance()->getHtmlTagRegex())) {
            $inlines->add(CommonMark_Element_InlineCreator::createHtml($m));

            return strlen($m);
        } else {
            return 0;
        }
    }

    /**
     * Scan a sequence of characters == c, and return information about
     * the number of delimiters and whether they are positioned such that
     * they can open and/or close emphasis or strong emphasis.  A utility
     * function for strong/emph parsing.
     *
     * @param string $char
     *
     * @return array
     */
    protected function scanDelims($char)
    {
        $numDelims = 0;
        $startPos = $this->pos;

        $charBefore = $this->pos === 0 ? "\n" : $this->subject[$this->pos - 1];

        while ($this->peek() === $char) {
            $numDelims++;
            $this->pos++;
        }

        $peek = $this->peek();
        $charAfter = $peek ? $peek : "\n";

        $canOpen = $numDelims > 0 && $numDelims <= 3 && !preg_match('/\s/', $charAfter);
        $canClose = $numDelims > 0 && $numDelims <= 3 && !preg_match('/\s/', $charBefore);
        if ($char === '_') {
            $canOpen = $canOpen && !preg_match('/[a-z0-9]/i', $charBefore);
            $canClose = $canClose && !preg_match('/[a-z0-9]/i', $charAfter);
        }

        $this->pos = $startPos;

        return compact('numDelims', 'canOpen', 'canClose');
    }

    /**
     * @param ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseEmphasis(CommonMark_Util_ArrayCollection $inlines)
    {
        $startPos = $this->pos;
        $firstClose = 0;
        $nxt = $this->peek();
        if ($nxt == '*' || $nxt == '_') {
            $c = $nxt;
        } else {
            return 0;
        }

        // Get opening delimiters
        $res = $this->scanDelims($c);
        $numDelims = $res['numDelims'];
        $this->pos += $numDelims;

        // We provisionally add a literal string.  If we match appropriate
        // closing delimiters, we'll change this to Strong or Emph.
        $inlines->add(CommonMark_Element_InlineCreator::createString(substr($this->subject, $this->pos - $numDelims, $numDelims)));
        // Record the position of this opening delimiter:
        $delimPos = $inlines->count() - 1;

        if (!$res['canOpen'] || $numDelims === 0) {
            return 0;
        }

        $firstCloseDelims = 0;
        switch ($numDelims) {
            case 1: // we started with * or _
                while (true) {
                    $res = $this->scanDelims($c);
                    if ($res['numDelims'] >= 1 && $res['canClose']) {
                        $this->pos += 1;
                        // Convert the inline at delimpos, currently a string with the delim,
                        // into an Emph whose contents are the succeeding inlines
                        $inlines->get($delimPos)->setType(CommonMark_Element_InlineElement::TYPE_EMPH);
                        $inlines->get($delimPos)->setContents($inlines->slice($delimPos + 1));
                        $inlines->splice($delimPos + 1);
                        break;
                    } else {
                        if ($this->parseInline($inlines) === 0) {
                            break;
                        }
                    }
                }

                return ($this->pos - $startPos);

            case 2: // We started with ** or __
                while (true) {
                    $res = $this->scanDelims($c);
                    if ($res['numDelims'] >= 2 && $res['canClose']) {
                        $this->pos += 2;
                        $inlines->get($delimPos)->setType(CommonMark_Element_InlineElement::TYPE_STRONG);
                        $inlines->get($delimPos)->setContents($inlines->slice($delimPos + 1));
                        $inlines->splice($delimPos + 1);
                        break;
                    } else {
                        if ($this->parseInline($inlines) === 0) {
                            break;
                        }
                    }
                }

                return ($this->pos - $startPos);

            case 3: // We started with *** or ___
                while (true) {
                    $res = $this->scanDelims($c);
                    if ($res['numDelims'] >= 1 && $res['numDelims'] <= 3 && $res['canClose'] && $res['numDelims'] != $firstCloseDelims) {
                        if ($firstCloseDelims === 1 && $numDelims > 2) {
                            $res['numDelims'] = 2;
                        } elseif ($firstCloseDelims === 2) {
                            $res['numDelims'] = 1;
                        } elseif ($res['numDelims'] === 3) {
                            // If we opened with ***, then we interpret *** as ** followed by *
                            // giving us <strong><em>
                            $res['numDelims'] = 1;
                        }

                        $this->pos += $res['numDelims'];

                        if ($firstClose > 0) { // if we've already passed the first closer:
                            $targetInline = $inlines->get($delimPos);
                            if ($firstCloseDelims === 1) {
                                $targetInline->setType(CommonMark_Element_InlineElement::TYPE_STRONG);
                                $targetInline->setContents(
                                    array(
                                        CommonMark_Element_InlineCreator::createEmph(
                                            $inlines->slice($delimPos + 1, $firstClose - $delimPos - 1)
                                        )
                                    )
                                );
                            } else {
                                $targetInline->setType(CommonMark_Element_InlineElement::TYPE_EMPH);
                                $targetInline->setContents(
                                    array(
                                        CommonMark_Element_InlineCreator::createStrong(
                                            $inlines->slice($delimPos + 1, $firstClose - $delimPos - 1)
                                        )
                                    )
                                );
                            }

                            $targetInline->setContents($targetInline->getContents() + $inlines->slice($firstClose + 1));
                            $inlines->splice($delimPos + 1);
                            break;
                        } else {
                            // this is the first closer; for now, add literal string;
                            // we'll change this when he hit the second closer
                            $str = substr($this->subject, $this->pos - $res['numDelims'], $this->pos);
                            $inlines->add(CommonMark_Element_InlineCreator::createString($str));
                            $firstClose = $inlines->count() - 1;
                            $firstCloseDelims = $res['numDelims'];
                        }
                    } else {
                        // Parse another inline element, til we hit the end
                        if ($this->parseInline($inlines) === 0) {
                            break;
                        }
                    }
                }

                return ($this->pos - $startPos);
        }

        return 0;
    }

    /**
     * Attempt to parse link title (sans quotes)
     *
     * @return null|string The string, or null if no match
     */
    protected function parseLinkTitle()
    {
        if ($title = $this->match(CommonMark_Util_RegexHelper::getInstance()->getLinkTitleRegex())) {
            // Chop off quotes from title and unescape
            return CommonMark_Util_RegexHelper::unescape(substr($title, 1, strlen($title) - 2));
        } else {
            return null;
        }
    }

    /**
     * Attempt to parse link destination
     *
     * @return null|string The string, or null if no match
     */
    protected function parseLinkDestination()
    {
        if ($res = $this->match(CommonMark_Util_RegexHelper::getInstance()->getLinkDestinationBracesRegex())) {
            // Chop off surrounding <..>:
            return CommonMark_Util_RegexHelper::unescape(substr($res, 1, strlen($res) - 2));
        } else {
            $res = $this->match(CommonMark_Util_RegexHelper::getInstance()->getLinkDestinationRegex());
            if ($res !== null) {
                return CommonMark_Util_RegexHelper::unescape($res);
            } else {
                return null;
            }
        }
    }

    /**
     * @return int
     */
    protected function parseLinkLabel()
    {
        if ($this->peek() != '[') {
            return 0;
        }

        $startPos = $this->pos;
        $nestLevel = 0;
        if ($this->labelNestLevel > 0) {
            // If we've already checked to the end of this subject
            // for a label, even with a different starting [, we
            // know we won't find one here and we can just return.
            // This avoids lots of backtracking.
            // Note:  nest level 1 would be: [foo [bar]
            //        nest level 2 would be: [foo [bar [baz]
            $this->labelNestLevel--;

            return 0;
        }

        $this->pos++; // Advance past [
        while (($c = $this->peek()) !== null && ($c != ']' || $nestLevel > 0)) {
            switch ($c) {
                case '`':
                    $this->parseBackticks(new CommonMark_Util_ArrayCollection());
                    break;
                case '<':
                    $this->parseAutolink(new CommonMark_Util_ArrayCollection()) || $this->parseHtmlTag(
                        new CommonMark_Util_ArrayCollection()
                    ) || $this->parseString(new CommonMark_Util_ArrayCollection()); // TODO: Does PHP support this use of "||"?
                    break;
                case '[': // nested []
                    $nestLevel++;
                    $this->pos++;
                    break;
                case ']': //nested []
                    $nestLevel--;
                    $this->pos++;
                    break;
                case '\\':
                    $this->parseEscaped(new CommonMark_Util_ArrayCollection());
                    break;
                default:
                    $this->parseString(new CommonMark_Util_ArrayCollection());
            }
        }

        if ($c === ']') {
            $this->labelNestLevel = 0;
            $this->pos++; // advance past ]

            return $this->pos - $startPos;
        } else {
            if ($c === null) {
                $this->labelNestLevel = $nestLevel;
            }

            $this->pos = $startPos;

            return 0;
        }
    }

    /**
     * Parse raw link label, including surrounding [], and return
     * inline contents.
     *
     * @param string $s
     *
     * @return ArrayCollection|InlineElementInterface[] Inline contents
     */
    private function parseRawLabel($s)
    {
        // note:  parse without a refmap; we don't want links to resolve
        // in nested brackets!
        $parser = new self();
        $substring = substr($s, 1, strlen($s) - 2);

        return $parser->parse($substring, new CommonMark_Reference_ReferenceMap());
    }

    /**
     * Attempt to parse a link.  If successful, add the link to inlines.
     * @param ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseLink(CommonMark_Util_ArrayCollection $inlines)
    {
        $startPos = $this->pos;
        $n = $this->parseLinkLabel();
        if ($n === 0) {
            return 0;
        }

        $rawLabel = substr($this->subject, $startPos, $n);

        // if we got this far, we've parsed a label.
        // Try to parse an explicit link: [label](url "title")
        if ($this->peek() == '(') {
            $this->pos++;
            if ($this->spnl() &&
                (($dest = $this->parseLinkDestination()) !== null) &&
                $this->spnl()
            ) {
                // make sure there's a space before the title:
                if (preg_match('/^\\s/', $this->subject[$this->pos - 1])) {
                    $title = $this->parseLinkTitle();
                    $title = $title ? $title : '';
                } else {
                    $title = null;
                }

                if ($this->spnl() && $this->match('/^\\)/')) {
                    $inlines->add(CommonMark_Element_InlineCreator::createLink($dest, $this->parseRawLabel($rawLabel), $title));

                    return $this->pos - $startPos;
                }
            }

            $this->pos = $startPos;

            return 0;
        }

        // If we're here, it wasn't an explicit link. Try to parse a reference link.
        // first, see if there's another label
        $savePos = $this->pos;
        $this->spnl();
        $beforeLabel = $this->pos;
        $n = $this->parseLinkLabel();
        if ($n == 2) {
            // empty second label
            $refLabel = $rawLabel;
        } elseif ($n > 0) {
            $refLabel = substr($this->subject, $beforeLabel, $n);
        } else {
            $this->pos = $savePos;
            $refLabel = $rawLabel;
        }

        // Lookup rawLabel in refmap
        if ($link = $this->refmap->getReference($refLabel)) {
            $inlines->add(
                CommonMark_Element_InlineCreator::createLink($link->getDestination(), $this->parseRawLabel($rawLabel), $link->getTitle())
            );

            return $this->pos - $startPos;
        }

        // Nothing worked, rewind:
        $this->pos = $startPos;

        return 0;
    }

    /**
     * Attempt to parse an entity, adding to inlines if successful
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseEntity(CommonMark_Util_ArrayCollection $inlines)
    {
        if ($m = $this->match('/^&(?:#x[a-f0-9]{1,8}|#[0-9]{1,8}|[a-z][a-z0-9]{1,31});/i')) {
            $inlines->add(CommonMark_Element_InlineCreator::createEntity($m));

            return strlen($m);
        }

        return 0;
    }

    /**
     * Parse a run of ordinary characters, or a single character with
     * a special meaning in markdown, as a plain string, adding to inlines.
     *
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseString(CommonMark_Util_ArrayCollection $inlines)
    {
        if ($m = $this->match(CommonMark_Util_RegexHelper::getInstance()->getMainRegex())) {
            $inlines->add(CommonMark_Element_InlineCreator::createString($m));

            return strlen($m);
        }

        return 0;
    }

    /**
     * Parse a newline.  If it was preceded by two spaces, return a hard
     * line break; otherwise a soft line break.
     *
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseNewline(CommonMark_Util_ArrayCollection $inlines)
    {
        if ($this->peek() == "\n") {
            $this->pos++;
            $last = $inlines->last();
            if ($last && $last->getType() == CommonMark_Element_InlineElement::TYPE_STRING && substr($last->getContents(), -2) == '  ') {
                $last->setContents(rtrim($last->getContents(), ' '));
                $inlines->add(CommonMark_Element_InlineCreator::createHardbreak());
            } else {
                if ($last && $last->getType() == CommonMark_Element_InlineElement::TYPE_STRING && substr(
                        $last->getContents(),
                        -1
                    ) == ' '
                ) {
                    $last->setContents(substr($last->getContents(), 0, -1));
                }
                $inlines->add(CommonMark_Element_InlineCreator::createSoftbreak());
            }

            return 1;
        }

        return 0;
    }

    /**
     * @param ArrayCollection $inlines
     *
     * @return int
     *
     * @throws \RuntimeException
     */
    protected function parseImage(CommonMark_Util_ArrayCollection $inlines)
    {
        if ($this->match('/^!/')) {
            $n = $this->parseLink($inlines);
            if ($n === 0) {
                $inlines->add(CommonMark_Element_InlineCreator::createString('!'));

                return 1;
            }

            /** @var InlineElementInterface $last */
            $last = $inlines->last();

            if ($last && $last->getType() == CommonMark_Element_InlineElement::TYPE_LINK) {
                $last->setType(CommonMark_Element_InlineElement::TYPE_IMAGE);

                return $n + 1;
            } else {
                // This shouldn't happen
                throw new RuntimeException('Unknown error occurred while attempting to parse an image');
            }
        } else {
            return 0;
        }
    }

    /**
     * Parse the next inline element in subject, advancing subject position
     * and adding the result to 'inlines'.
     *
     * @param \ColinODell\CommonMark\Util\ArrayCollection $inlines
     *
     * @return int
     */
    protected function parseInline(CommonMark_Util_ArrayCollection $inlines)
    {
        $c = $this->peek();
        $res = null;

        switch ($c) {
            case "\n":
                $res = $this->parseNewline($inlines);
                break;
            case '\\':
                $res = $this->parseEscaped($inlines);
                break;
            case '`':
                $res = $this->parseBackticks($inlines);
                break;
            case '*':
            case '_':
                $res = $this->parseEmphasis($inlines);
                break;
            case '[':
                $res = $this->parseLink($inlines);
                break;
            case '!':
                $res = $this->parseImage($inlines);
                break;
            case '<':
                $res = $this->parseAutolink($inlines);
                $res = $res ? $res : $this->parseHtmlTag($inlines);
                break;
            case '&':
                $res = $this->parseEntity($inlines);
                break;
            default:
                // Nothing
        }

        return $res ? $res : $this->parseString($inlines);
    }

    /**
     * Parse s as a list of inlines, using refmap to resolve references.
     *
     * @param string $s
     * @param ReferenceMap $refMap
     *
     * @return ArrayCollection|InlineElementInterface[]
     */
    protected function parseInlines($s, CommonMark_Reference_ReferenceMap $refMap)
    {
        $this->subject = $s;
        $this->pos = 0;
        $this->refmap = $refMap;
        $inlines = new CommonMark_Util_ArrayCollection();
        while ($this->parseInline($inlines)) {
            ;
        }

        return $inlines;
    }

    /**
     * @param string       $s
     * @param ReferenceMap $refMap
     *
     * @return ArrayCollection|Element\InlineElementInterface[]
     */
    public function parse($s, CommonMark_Reference_ReferenceMap $refMap)
    {
        return $this->parseInlines($s, $refMap);
    }

    /**
     * Attempt to parse a link reference, modifying refmap.
     * @param string       $s
     * @param ReferenceMap $refMap
     *
     * @return int
     */
    public function parseReference($s, CommonMark_Reference_ReferenceMap $refMap)
    {
        $this->subject = $s;
        $this->pos = 0;
        $startPos = $this->pos;

        // label:
        $matchChars = $this->parseLinkLabel();
        if ($matchChars === 0) {
            return 0;
        } else {
            $label = substr($this->subject, 0, $matchChars);
        }

        // colon:
        if ($this->peek() === ':') {
            $this->pos++;
        } else {
            $this->pos = $startPos;

            return 0;
        }

        // link url
        $this->spnl();

        $destination = $this->parseLinkDestination();
        if ($destination === null || strlen($destination) === 0) {
            $this->pos = $startPos;

            return 0;
        }

        $beforeTitle = $this->pos;
        $this->spnl();
        $title = $this->parseLinkTitle();
        if ($title === null) {
            $title = '';
            // rewind before spaces
            $this->pos = $beforeTitle;
        }

        // make sure we're at line end:
        if ($this->match('/^ *(?:\n|$)/') === null) {
            $this->pos = $startPos;

            return 0;
        }

        if (!$refMap->contains($label)) {
            $refMap->addReference(new CommonMark_Reference_Reference($label, $destination, $title));
        }

        return $this->pos - $startPos;
    }
}
