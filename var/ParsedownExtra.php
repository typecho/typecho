<?php

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class Parsedown
{
    #
    # Philosophy

    # Parsedown recognises that the Markdown syntax is optimised for humans so
    # it tries to read like one. It goes through text line by line. It looks at
    # how lines start to identify blocks. It looks for special characters to
    # identify inline elements.

    #
    # ~

    function text($text)
    {
        # make sure no definitions are set
        $this->Definitions = array();

        # standardize line breaks
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        # replace tabs with spaces
        $text = str_replace("\t", '    ', $text);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # iterate through lines to identify blocks
        $markup = $this->lines($lines);

        # trim line breaks
        $markup = trim($markup, "\n");

        return $markup;
    }

    #
    # Setters
    #

    private $breaksEnabled;

    function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    #
    # Lines
    #

    protected $BlockTypes = array(
        '#' => array('Atx'),
        '*' => array('Rule', 'List'),
        '+' => array('List'),
        '-' => array('Setext', 'Table', 'Rule', 'List'),
        '0' => array('List'),
        '1' => array('List'),
        '2' => array('List'),
        '3' => array('List'),
        '4' => array('List'),
        '5' => array('List'),
        '6' => array('List'),
        '7' => array('List'),
        '8' => array('List'),
        '9' => array('List'),
        ':' => array('Table'),
        '<' => array('Comment', 'Markup'),
        '=' => array('Setext'),
        '>' => array('Quote'),
        '_' => array('Rule'),
        '`' => array('FencedCode'),
        '|' => array('Table'),
        '~' => array('FencedCode'),
    );

    # ~

    protected $DefinitionTypes = array(
        '[' => array('Reference'),
    );

    # ~

    protected $unmarkedBlockTypes = array(
        'CodeBlock',
    );

    #
    # Blocks
    #

    private function lines(array $lines)
    {
        $CurrentBlock = null;

        foreach ($lines as $line)
        {
            if (chop($line) === '')
            {
                if (isset($CurrentBlock))
                {
                    $CurrentBlock['interrupted'] = true;
                }

                continue;
            }

            $indent = 0;

            while (isset($line[$indent]) and $line[$indent] === ' ')
            {
                $indent ++;
            }

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # ~

            if (isset($CurrentBlock['incomplete']))
            {
                $Block = $this->{'addTo'.$CurrentBlock['type']}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $CurrentBlock = $Block;

                    continue;
                }
                else
                {
                    if (method_exists($this, 'complete'.$CurrentBlock['type']))
                    {
                        $CurrentBlock = $this->{'complete'.$CurrentBlock['type']}($CurrentBlock);
                    }

                    unset($CurrentBlock['incomplete']);
                }
            }

            # ~

            $marker = $text[0];

            if (isset($this->DefinitionTypes[$marker]))
            {
                foreach ($this->DefinitionTypes[$marker] as $definitionType)
                {
                    $Definition = $this->{'identify'.$definitionType}($Line, $CurrentBlock);

                    if (isset($Definition))
                    {
                        $this->Definitions[$definitionType][$Definition['id']] = $Definition['data'];

                        continue 2;
                    }
                }
            }

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->BlockTypes[$marker]))
            {
                foreach ($this->BlockTypes[$marker] as $blockType)
                {
                    $blockTypes []= $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType)
            {
                $Block = $this->{'identify'.$blockType}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $Block['type'] = $blockType;

                    if ( ! isset($Block['identified']))
                    {
                        $Elements []= $CurrentBlock['element'];

                        $Block['identified'] = true;
                    }

                    if (method_exists($this, 'addTo'.$blockType))
                    {
                        $Block['incomplete'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and ! isset($CurrentBlock['type']) and ! isset($CurrentBlock['interrupted']))
            {
                $CurrentBlock['element']['text'] .= "\n".$text;
            }
            else
            {
                $Elements []= $CurrentBlock['element'];

                $CurrentBlock = $this->buildParagraph($Line);

                $CurrentBlock['identified'] = true;
            }
        }

        # ~

        if (isset($CurrentBlock['incomplete']) and method_exists($this, 'complete'.$CurrentBlock['type']))
        {
            $CurrentBlock = $this->{'complete'.$CurrentBlock['type']}($CurrentBlock);
        }

        # ~

        $Elements []= $CurrentBlock['element'];

        unset($Elements[0]);

        # ~

        $markup = $this->elements($Elements);

        # ~

        return $markup;
    }

    #
    # Atx

    protected function identifyAtx($Line)
    {
        if (isset($Line['text'][1]))
        {
            $level = 1;

            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#')
            {
                $level ++;
            }

            $text = trim($Line['text'], '# ');

            $Block = array(
                'element' => array(
                    'name' => 'h' . min(6, $level),
                    'text' => $text,
                    'handler' => 'line',
                ),
            );

            return $Block;
        }
    }

    #
    # Code

    protected function identifyCodeBlock($Line)
    {
        if ($Line['indent'] >= 4)
        {
            $text = substr($Line['body'], 4);

            $Block = array(
                'element' => array(
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => array(
                        'name' => 'code',
                        'text' => $text,
                    ),
                ),
            );

            return $Block;
        }
    }

    protected function addToCodeBlock($Line, $Block)
    {
        if ($Line['indent'] >= 4)
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['text']['text'] .= "\n";

                unset($Block['interrupted']);
            }

            $Block['element']['text']['text'] .= "\n";

            $text = substr($Line['body'], 4);

            $Block['element']['text']['text'] .= $text;

            return $Block;
        }
    }

    protected function completeCodeBlock($Block)
    {
        $text = $Block['element']['text']['text'];

        $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

        $Block['element']['text']['text'] = $text;

        return $Block;
    }

    #
    # Comment

    protected function identifyComment($Line)
    {
        if (isset($Line['text'][3]) and $Line['text'][3] === '-' and $Line['text'][2] === '-' and $Line['text'][1] === '!')
        {
            $Block = array(
                'element' => $Line['body'],
            );

            if (preg_match('/-->$/', $Line['text']))
            {
                $Block['closed'] = true;
            }

            return $Block;
        }
    }

    protected function addToComment($Line, array $Block)
    {
        if (isset($Block['closed']))
        {
            return;
        }

        $Block['element'] .= "\n" . $Line['body'];

        if (preg_match('/-->$/', $Line['text']))
        {
            $Block['closed'] = true;
        }

        return $Block;
    }

    #
    # Fenced Code

    protected function identifyFencedCode($Line)
    {
        if (preg_match('/^(['.$Line['text'][0].']{3,})[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches))
        {
            $Element = array(
                'name' => 'code',
                'text' => '',
            );

            if (isset($matches[2]))
            {
                $class = 'language-'.$matches[2];

                $Element['attributes'] = array(
                    'class' => $class,
                );
            }

            $Block = array(
                'char' => $Line['text'][0],
                'element' => array(
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => $Element,
                ),
            );

            return $Block;
        }
    }

    protected function addToFencedCode($Line, $Block)
    {
        if (isset($Block['complete']))
        {
            return;
        }

        if (isset($Block['interrupted']))
        {
            $Block['element']['text']['text'] .= "\n";

            unset($Block['interrupted']);
        }

        if (preg_match('/^'.$Block['char'].'{3,}[ ]*$/', $Line['text']))
        {
            $Block['element']['text']['text'] = substr($Block['element']['text']['text'], 1);

            $Block['complete'] = true;

            return $Block;
        }

        $Block['element']['text']['text'] .= "\n".$Line['body'];;

        return $Block;
    }

    protected function completeFencedCode($Block)
    {
        $text = $Block['element']['text']['text'];

        $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

        $Block['element']['text']['text'] = $text;

        return $Block;
    }

    #
    # List

    protected function identifyList($Line)
    {
        list($name, $pattern) = $Line['text'][0] <= '-' ? array('ul', '[*+-]') : array('ol', '[0-9]+[.]');

        if (preg_match('/^('.$pattern.'[ ]+)(.*)/', $Line['text'], $matches))
        {
            $Block = array(
                'indent' => $Line['indent'],
                'pattern' => $pattern,
                'element' => array(
                    'name' => $name,
                    'handler' => 'elements',
                ),
            );

            $Block['li'] = array(
                'name' => 'li',
                'handler' => 'li',
                'text' => array(
                    $matches[2],
                ),
            );

            $Block['element']['text'] []= & $Block['li'];

            return $Block;
        }
    }

    protected function addToList($Line, array $Block)
    {
        if ($Block['indent'] === $Line['indent'] and preg_match('/^'.$Block['pattern'].'[ ]+(.*)/', $Line['text'], $matches))
        {
            if (isset($Block['interrupted']))
            {
                $Block['li']['text'] []= '';

                unset($Block['interrupted']);
            }

            unset($Block['li']);

            $Block['li'] = array(
                'name' => 'li',
                'handler' => 'li',
                'text' => array(
                    $matches[1],
                ),
            );

            $Block['element']['text'] []= & $Block['li'];

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);

            $Block['li']['text'] []= $text;

            return $Block;
        }

        if ($Line['indent'] > 0)
        {
            $Block['li']['text'] []= '';

            $text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);

            $Block['li']['text'] []= $text;

            unset($Block['interrupted']);

            return $Block;
        }
    }

    #
    # Quote

    protected function identifyQuote($Line)
    {
        if (preg_match('/^>[ ]?(.*)/', $Line['text'], $matches))
        {
            $Block = array(
                'element' => array(
                    'name' => 'blockquote',
                    'handler' => 'lines',
                    'text' => (array) $matches[1],
                ),
            );

            return $Block;
        }
    }

    protected function addToQuote($Line, array $Block)
    {
        if ($Line['text'][0] === '>' and preg_match('/^>[ ]?(.*)/', $Line['text'], $matches))
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['text'] []= '';

                unset($Block['interrupted']);
            }

            $Block['element']['text'] []= $matches[1];

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $Block['element']['text'] []= $Line['text'];

            return $Block;
        }
    }

    #
    # Rule

    protected function identifyRule($Line)
    {
        if (preg_match('/^(['.$Line['text'][0].'])([ ]{0,2}\1){2,}[ ]*$/', $Line['text']))
        {
            $Block = array(
                'element' => array(
                    'name' => 'hr'
                ),
            );

            return $Block;
        }
    }

    #
    # Setext

    protected function identifySetext($Line, array $Block = null)
    {
        if ( ! isset($Block) or isset($Block['type']) or isset($Block['interrupted']))
        {
            return;
        }

        if (chop($Line['text'], $Line['text'][0]) === '')
        {
            $Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';

            return $Block;
        }
    }

    #
    # Markup

    protected function identifyMarkup($Line)
    {
        if (preg_match('/^<(\w[\w\d]*)(?:[ ][^>]*)?(\/?)[ ]*>/', $Line['text'], $matches))
        {
            if (in_array($matches[1], $this->textLevelElements))
            {
                return;
            }

            $Block = array(
                'element' => $Line['body'],
            );

            if ($matches[2] or $matches[1] === 'hr' or preg_match('/<\/'.$matches[1].'>[ ]*$/', $Line['text']))
            {
                $Block['closed'] = true;
            }
            else
            {
                $Block['depth'] = 0;
                $Block['name'] = $matches[1];
            }

            return $Block;
        }
    }

    protected function addToMarkup($Line, array $Block)
    {
        if (isset($Block['closed']))
        {
            return;
        }

        if (preg_match('/<'.$Block['name'].'([ ][^\/]+)?>/', $Line['text'])) # opening tag
        {
            $Block['depth'] ++;
        }

        if (stripos($Line['text'], '</'.$Block['name'].'>') !== false) # closing tag
        {
            if ($Block['depth'] > 0)
            {
                $Block['depth'] --;
            }
            else
            {
                $Block['closed'] = true;
            }
        }

        $Block['element'] .= "\n".$Line['body'];

        return $Block;
    }

    #
    # Table

    protected function identifyTable($Line, array $Block = null)
    {
        if ( ! isset($Block) or isset($Block['type']) or isset($Block['interrupted']))
        {
            return;
        }

        if (strpos($Block['element']['text'], '|') !== false and chop($Line['text'], ' -:|') === '')
        {
            $alignments = array();

            $divider = $Line['text'];

            $divider = trim($divider);
            $divider = trim($divider, '|');

            $dividerCells = explode('|', $divider);

            foreach ($dividerCells as $dividerCell)
            {
                $dividerCell = trim($dividerCell);

                if ($dividerCell === '')
                {
                    continue;
                }

                $alignment = null;

                if ($dividerCell[0] === ':')
                {
                    $alignment = 'left';
                }

                if (substr($dividerCell, -1) === ':')
                {
                    $alignment = $alignment === 'left' ? 'center' : 'right';
                }

                $alignments []= $alignment;
            }

            # ~

            $HeaderElements = array();

            $header = $Block['element']['text'];

            $header = trim($header);
            $header = trim($header, '|');

            $headerCells = explode('|', $header);

            foreach ($headerCells as $index => $headerCell)
            {
                $headerCell = trim($headerCell);

                $HeaderElement = array(
                    'name' => 'th',
                    'text' => $headerCell,
                    'handler' => 'line',
                );

                if (isset($alignments[$index]))
                {
                    $alignment = $alignments[$index];

                    $HeaderElement['attributes'] = array(
                        'align' => $alignment,
                    );
                }

                $HeaderElements []= $HeaderElement;
            }

            # ~

            $Block = array(
                'alignments' => $alignments,
                'identified' => true,
                'element' => array(
                    'name' => 'table',
                    'handler' => 'elements',
                ),
            );

            $Block['element']['text'] []= array(
                'name' => 'thead',
                'handler' => 'elements',
            );

            $Block['element']['text'] []= array(
                'name' => 'tbody',
                'handler' => 'elements',
                'text' => array(),
            );

            $Block['element']['text'][0]['text'] []= array(
                'name' => 'tr',
                'handler' => 'elements',
                'text' => $HeaderElements,
            );

            return $Block;
        }
    }

    protected function addToTable($Line, array $Block)
    {
        if ($Line['text'][0] === '|' or strpos($Line['text'], '|'))
        {
            $Elements = array();

            $row = $Line['text'];

            $row = trim($row);
            $row = trim($row, '|');

            $cells = explode('|', $row);

            foreach ($cells as $index => $cell)
            {
                $cell = trim($cell);

                $Element = array(
                    'name' => 'td',
                    'handler' => 'line',
                    'text' => $cell,
                );

                if (isset($Block['alignments'][$index]))
                {
                    $Element['attributes'] = array(
                        'align' => $Block['alignments'][$index],
                    );
                }

                $Elements []= $Element;
            }

            $Element = array(
                'name' => 'tr',
                'handler' => 'elements',
                'text' => $Elements,
            );

            $Block['element']['text'][1]['text'] []= $Element;

            return $Block;
        }
    }

    #
    # Definitions
    #

    protected function identifyReference($Line)
    {
        if (preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/', $Line['text'], $matches))
        {
            $Definition = array(
                'id' => strtolower($matches[1]),
                'data' => array(
                    'url' => $matches[2],
                ),
            );

            if (isset($matches[3]))
            {
                $Definition['data']['title'] = $matches[3];
            }

            return $Definition;
        }
    }

    #
    # ~
    #

    protected function buildParagraph($Line)
    {
        $Block = array(
            'element' => array(
                'name' => 'p',
                'text' => $Line['text'],
                'handler' => 'line',
            ),
        );

        return $Block;
    }

    #
    # ~
    #

    protected function element(array $Element)
    {
        $markup = '<'.$Element['name'];

        if (isset($Element['attributes']))
        {
            foreach ($Element['attributes'] as $name => $value)
            {
                $markup .= ' '.$name.'="'.$value.'"';
            }
        }

        if (isset($Element['text']))
        {
            $markup .= '>';

            if (isset($Element['handler']))
            {
                $markup .= $this->$Element['handler']($Element['text']);
            }
            else
            {
                $markup .= $Element['text'];
            }

            $markup .= '</'.$Element['name'].'>';
        }
        else
        {
            $markup .= ' />';
        }

        return $markup;
    }

    protected function elements(array $Elements)
    {
        $markup = '';

        foreach ($Elements as $Element)
        {
            if ($Element === null)
            {
                continue;
            }

            $markup .= "\n";

            if (is_string($Element)) # because of Markup
            {
                $markup .= $Element;

                continue;
            }

            $markup .= $this->element($Element);
        }

        $markup .= "\n";

        return $markup;
    }

    #
    # Spans
    #

    protected $SpanTypes = array(
        '!' => array('Link'), # ?
        '&' => array('Ampersand'),
        '*' => array('Emphasis'),
        '/' => array('Url'),
        '<' => array('UrlTag', 'EmailTag', 'Tag', 'LessThan'),
        '[' => array('Link'),
        '_' => array('Emphasis'),
        '`' => array('InlineCode'),
        '~' => array('Strikethrough'),
        '\\' => array('EscapeSequence'),
    );

    # ~

    protected $spanMarkerList = '*_!&[</`~\\';

    #
    # ~
    #

    public function line($text)
    {
        $markup = '';

        $remainder = $text;

        $markerPosition = 0;

        while ($excerpt = strpbrk($remainder, $this->spanMarkerList))
        {
            $marker = $excerpt[0];

            $markerPosition += strpos($remainder, $marker);

            $Excerpt = array('text' => $excerpt, 'context' => $text);

            foreach ($this->SpanTypes[$marker] as $spanType)
            {
                $handler = 'identify'.$spanType;

                $Span = $this->$handler($Excerpt);

                if ( ! isset($Span))
                {
                    continue;
                }

                # The identified span can be ahead of the marker.

                if (isset($Span['position']) and $Span['position'] > $markerPosition)
                {
                    continue;
                }

                # Spans that start at the position of their marker don't have to set a position.

                if ( ! isset($Span['position']))
                {
                    $Span['position'] = $markerPosition;
                }

                $plainText = substr($text, 0, $Span['position']);

                $markup .= $this->readPlainText($plainText);

                $markup .= isset($Span['markup']) ? $Span['markup'] : $this->element($Span['element']);

                $text = substr($text, $Span['position'] + $Span['extent']);

                $remainder = $text;

                $markerPosition = 0;

                continue 2;
            }

            $remainder = substr($excerpt, 1);

            $markerPosition ++;
        }

        $markup .= $this->readPlainText($text);

        return $markup;
    }

    #
    # ~
    #

    protected function identifyUrl($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '/')
        {
            return;
        }

        if (preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE))
        {
            $url = str_replace(array('&', '<'), array('&amp;', '&lt;'), $matches[0][0]);

            return array(
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
        }
    }

    protected function identifyAmpersand($Excerpt)
    {
        if ( ! preg_match('/^&#?\w+;/', $Excerpt['text']))
        {
            return array(
                'markup' => '&amp;',
                'extent' => 1,
            );
        }
    }

    protected function identifyStrikethrough($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return;
        }

        if ($Excerpt['text'][1] === '~' and preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'del',
                    'text' => $matches[1],
                    'handler' => 'line',
                ),
            );
        }
    }

    protected function identifyEscapeSequence($Excerpt)
    {
        if (isset($Excerpt['text'][1]) and in_array($Excerpt['text'][1], $this->specialCharacters))
        {
            return array(
                'markup' => $Excerpt['text'][1],
                'extent' => 2,
            );
        }
    }

    protected function identifyLessThan()
    {
        return array(
            'markup' => '&lt;',
            'extent' => 1,
        );
    }

    protected function identifyUrlTag($Excerpt)
    {
        if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<(https?:[\/]{2}[^\s]+?)>/i', $Excerpt['text'], $matches))
        {
            $url = str_replace(array('&', '<'), array('&amp;', '&lt;'), $matches[1]);

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
        }
    }

    protected function identifyEmailTag($Excerpt)
    {
        if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<(\S+?@\S+?)>/', $Excerpt['text'], $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => array(
                        'href' => 'mailto:'.$matches[1],
                    ),
                ),
            );
        }
    }

    protected function identifyTag($Excerpt)
    {
        if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<\/?\w.*?>/', $Excerpt['text'], $matches))
        {
            return array(
                'markup' => $matches[0],
                'extent' => strlen($matches[0]),
            );
        }
    }

    protected function identifyInlineCode($Excerpt)
    {
        $marker = $Excerpt['text'][0];

        if (preg_match('/^('.$marker.'+)[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/', $Excerpt['text'], $matches))
        {
            $text = $matches[2];
            $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'code',
                    'text' => $text,
                ),
            );
        }
    }

    protected function identifyLink($Excerpt)
    {
        $extent = $Excerpt['text'][0] === '!' ? 1 : 0;

        if (strpos($Excerpt['text'], ']') and preg_match('/\[((?:[^][]|(?R))*)\]/', $Excerpt['text'], $matches))
        {
            $Link = array('text' => $matches[1], 'label' => strtolower($matches[1]));

            $extent += strlen($matches[0]);

            $substring = substr($Excerpt['text'], $extent);

            if (preg_match('/^\s*\[([^][]+)\]/', $substring, $matches))
            {
                $Link['label'] = strtolower($matches[1]);

                if (isset($this->Definitions['Reference'][$Link['label']]))
                {
                    $Link += $this->Definitions['Reference'][$Link['label']];

                    $extent += strlen($matches[0]);
                }
                else
                {
                    return;
                }
            }
            elseif (isset($this->Definitions['Reference'][$Link['label']]))
            {
                $Link += $this->Definitions['Reference'][$Link['label']];

                if (preg_match('/^[ ]*\[\]/', $substring, $matches))
                {
                    $extent += strlen($matches[0]);
                }
            }
            elseif (preg_match('/^\([ ]*(.*?)(?:[ ]+[\'"](.+?)[\'"])?[ ]*\)/', $substring, $matches))
            {
                $Link['url'] = $matches[1];

                if (isset($matches[2]))
                {
                    $Link['title'] = $matches[2];
                }

                $extent += strlen($matches[0]);
            }
            else
            {
                return;
            }
        }
        else
        {
            return;
        }

        $url = str_replace(array('&', '<'), array('&amp;', '&lt;'), $Link['url']);

        if ($Excerpt['text'][0] === '!')
        {
            $Element = array(
                'name' => 'img',
                'attributes' => array(
                    'alt' => $Link['text'],
                    'src' => $url,
                ),
            );
        }
        else
        {
            $Element = array(
                'name' => 'a',
                'handler' => 'line',
                'text' => $Link['text'],
                'attributes' => array(
                    'href' => $url,
                ),
            );
        }

        if (isset($Link['title']))
        {
            $Element['attributes']['title'] = $Link['title'];
        }

        return array(
            'extent' => $extent,
            'element' => $Element,
        );
    }

    protected function identifyEmphasis($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return;
        }

        $marker = $Excerpt['text'][0];

        if ($Excerpt['text'][1] === $marker and preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'strong';
        }
        elseif (preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'em';
        }
        else
        {
            return;
        }

        return array(
            'extent' => strlen($matches[0]),
            'element' => array(
                'name' => $emphasis,
                'handler' => 'line',
                'text' => $matches[1],
            ),
        );
    }

    #
    # ~

    protected function readPlainText($text)
    {
        $breakMarker = $this->breaksEnabled ? "\n" : "  \n";

        $text = str_replace($breakMarker, "<br />\n", $text);

        return $text;
    }

    #
    # ~
    #

    protected function li($lines)
    {
        $markup = $this->lines($lines);

        $trimmedMarkup = trim($markup);

        if ( ! in_array('', $lines) and substr($trimmedMarkup, 0, 3) === '<p>')
        {
            $markup = $trimmedMarkup;
            $markup = substr($markup, 3);

            $position = strpos($markup, "</p>");

            $markup = substr_replace($markup, '', $position, 4);
        }

        return $markup;
    }

    #
    # Multiton
    #

    static function instance($name = 'default')
    {
        if (isset(self::$instances[$name]))
        {
            return self::$instances[$name];
        }

        $instance = new self();

        self::$instances[$name] = $instance;

        return $instance;
    }

    private static $instances = array();

    #
    # Deprecated Methods
    #

    /**
     * @deprecated in favor of "text"
     */
    function parse($text)
    {
        $markup = $this->text($text);

        return $markup;
    }

    #
    # Fields
    #

    protected $Definitions;

    #
    # Read-only

    protected $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!',
    );

    protected $StrongRegex = array(
        '*' => '/^[*]{2}((?:[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:[^_]|_[^_]*_)+?)__(?!_)/us',
    );

    protected $EmRegex = array(
        '*' => '/^[*]((?:[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:[^_]|__[^_]*__)+?)_(?!_)\b/us',
    );

    protected $textLevelElements = array(
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'del', 'code',          'strike', 'marquee',
        'q', 'rt', 'ins', 'font',          'strong',
        's', 'tt', 'sub', 'mark',
        'u', 'xm', 'sup', 'nobr',
                   'var', 'ruby',
                   'wbr', 'span',
                          'time',
    );
}

#
#
# Parsedown Extra
# https://github.com/erusev/parsedown-extra
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class ParsedownExtra extends Parsedown
{
    #
    # ~

    function __construct()
    {
        $this->BlockTypes[':'] []= 'DefinitionList';

        $this->DefinitionTypes['*'] []= 'Abbreviation';

        # identify footnote definitions before reference definitions
        array_unshift($this->DefinitionTypes['['], 'Footnote');

        # identify footnote markers before before links
        array_unshift($this->SpanTypes['['], 'FootnoteMarker');
    }

    #
    # ~

    function text($text)
    {
        $markup = parent::text($text);

        # merge consecutive dl elements

        $markup = preg_replace('/<\/dl>\s+<dl>\s+/', '', $markup);

        # add footnotes

        if (isset($this->Definitions['Footnote']))
        {
            $Element = $this->buildFootnoteElement();

            $markup .= "\n" . $this->element($Element);
        }

        return $markup;
    }

    #
    # Blocks
    #

    #
    # Atx

    protected function identifyAtx($Line)
    {
        $Block = parent::identifyAtx($Line);

        if (preg_match('/[ ]*'.$this->attributesPattern.'[ ]*$/', $Block['element']['text'], $matches, PREG_OFFSET_CAPTURE))
        {
            $attributeString = $matches[1][0];

            $Block['element']['attributes'] = $this->parseAttributes($attributeString);

            $Block['element']['text'] = substr($Block['element']['text'], 0, $matches[0][1]);
        }

        return $Block;
    }

    #
    # Definition List

    protected function identifyDefinitionList($Line, $Block)
    {
        if (isset($Block['type']))
        {
            return;
        }

        $Element = array(
            'name' => 'dl',
            'handler' => 'elements',
            'text' => array(),
        );

        $terms = explode("\n", $Block['element']['text']);

        foreach ($terms as $term)
        {
            $Element['text'] []= array(
                'name' => 'dt',
                'handler' => 'line',
                'text' => $term,
            );
        }

        $Element['text'] []= array(
            'name' => 'dd',
            'handler' => 'line',
            'text' => ltrim($Line['text'], ' :'),
        );

        $Block['element'] = $Element;

        return $Block;
    }

    protected function addToDefinitionList($Line, array $Block)
    {
        if ($Line['text'][0] === ':')
        {
            $Block['element']['text'] []= array(
                'name' => 'dd',
                'handler' => 'line',
                'text' => ltrim($Line['text'], ' :'),
            );

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $Element = array_pop($Block['element']['text']);

            $Element['text'] .= "\n" . chop($Line['text']);

            $Block['element']['text'] []= $Element;

            return $Block;
        }
    }

    #
    # Setext

    protected function identifySetext($Line, array $Block = null)
    {
        $Block = parent::identifySetext($Line, $Block);

        if (preg_match('/[ ]*'.$this->attributesPattern.'[ ]*$/', $Block['element']['text'], $matches, PREG_OFFSET_CAPTURE))
        {
            $attributeString = $matches[1][0];

            $Block['element']['attributes'] = $this->parseAttributes($attributeString);

            $Block['element']['text'] = substr($Block['element']['text'], 0, $matches[0][1]);
        }

        return $Block;
    }

    #
    # Markup

    protected function completeMarkup($Block)
    {
        $DOMDocument = new DOMDocument;

        $DOMDocument->loadXML($Block['element'], LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($DOMDocument->documentElement === null)
        {
            return $Block;
        }

        $result = $DOMDocument->documentElement->getAttribute('markdown');

        if ($result !== '1')
        {
            return $Block;
        }

        $DOMDocument->documentElement->removeAttribute('markdown');

        $index = 0;
        $texts = array();

        foreach ($DOMDocument->documentElement->childNodes as $Node)
        {
            if ($Node instanceof DOMText)
            {
                $texts [] = $this->text($Node->nodeValue);

                # replaces the text of the node with a placeholder
                $Node->nodeValue = '\x1A'.$index ++;
            }
        }

        $markup = $DOMDocument->saveXML($DOMDocument->documentElement);

        foreach ($texts as $index => $text)
        {
            $markup = str_replace('\x1A'.$index, $text, $markup);
        }

        $Block['element'] = $markup;

        return $Block;
    }

    #
    # Definitions
    #

    #
    # Abbreviation

    protected function identifyAbbreviation($Line)
    {
        if (preg_match('/^\*\[(.+?)\]:[ ]*(.+?)[ ]*$/', $Line['text'], $matches))
        {
            $Abbreviation = array(
                'id' => $matches[1],
                'data' => $matches[2],
            );

            return $Abbreviation;
        }
    }

    #
    # Footnote

    protected function identifyFootnote($Line)
    {
        if (preg_match('/^\[\^(.+?)\]:[ ]?(.+)$/', $Line['text'], $matches))
        {
            $Footnote = array(
                'id' => $matches[1],
                'data' => array(
                    'text' => $matches[2],
                    'count' => null,
                    'number' => null,
                ),
            );

            return $Footnote;
        }
    }

    #
    # Spans
    #

    #
    # Footnote Marker

    protected function identifyFootnoteMarker($Excerpt)
    {
        if (preg_match('/^\[\^(.+?)\]/', $Excerpt['text'], $matches))
        {
            $name = $matches[1];

            if ( ! isset($this->Definitions['Footnote'][$name]))
            {
                return;
            }

            $this->Definitions['Footnote'][$name]['count'] ++;

            if ( ! isset($this->Definitions['Footnote'][$name]['number']))
            {
                $this->Definitions['Footnote'][$name]['number'] = ++ $this->footnoteCount; # » &
            }

            $Element = array(
                'name' => 'sup',
                'attributes' => array('id' => 'fnref'.$this->Definitions['Footnote'][$name]['count'].':'.$name),
                'handler' => 'element',
                'text' => array(
                    'name' => 'a',
                    'attributes' => array('href' => '#fn:'.$name, 'class' => 'footnote-ref'),
                    'text' => $this->Definitions['Footnote'][$name]['number'],
                ),
            );

            return array(
                'extent' => strlen($matches[0]),
                'element' => $Element,
            );
        }
    }

    private $footnoteCount = 0;

    #
    # Link

    protected function identifyLink($Excerpt)
    {
        $Span = parent::identifyLink($Excerpt);

        $remainder = substr($Excerpt['text'], $Span['extent']);

        if (preg_match('/^[ ]*'.$this->attributesPattern.'/', $remainder, $matches))
        {
            $Span['element']['attributes'] += $this->parseAttributes($matches[1]);

            $Span['extent'] += strlen($matches[0]);
        }

        return $Span;
    }

    #
    # ~

    protected function readPlainText($text)
    {
        $text = parent::readPlainText($text);

        if (isset($this->Definitions['Abbreviation']))
        {
            foreach ($this->Definitions['Abbreviation'] as $abbreviation => $phrase)
            {
                $text = str_replace($abbreviation, '<abbr title="'.$phrase.'">'.$abbreviation.'</abbr>', $text);
            }
        }

        return $text;
    }

    #
    # ~
    #

    protected function buildFootnoteElement()
    {
        $Element = array(
            'name' => 'div',
            'attributes' => array('class' => 'footnotes'),
            'handler' => 'elements',
            'text' => array(
                array(
                    'name' => 'hr',
                ),
                array(
                    'name' => 'ol',
                    'handler' => 'elements',
                    'text' => array(),
                ),
            ),
        );

        usort($this->Definitions['Footnote'], function($A, $B) {
            return $A['number'] - $B['number'];
        });

        foreach ($this->Definitions['Footnote'] as $name => $Data)
        {
            if ( ! isset($Data['number']))
            {
                continue;
            }

            $text = $Data['text'];

            foreach (range(1, $Data['count']) as $number)
            {
                $text .= '&#160;<a href="#fnref'.$number.':'.$name.'" rev="footnote" class="footnote-backref">&#8617;</a>';
            }

            $Element['text'][1]['text'] []= array(
                'name' => 'li',
                'attributes' => array('id' => 'fn:'.$name),
                'handler' => 'elements',
                'text' => array(
                    array(
                        'name' => 'p',
                        'text' => $text,
                    ),
                ),
            );
        }

        return $Element;
    }

    #
    # Private
    #

    private function parseAttributes($attributeString)
    {
        $Data = array();

        $attributes = preg_split('/[ ]+/', $attributeString, - 1, PREG_SPLIT_NO_EMPTY);

        foreach ($attributes as $attribute)
        {
            if ($attribute[0] === '#')
            {
                $Data['id'] = substr($attribute, 1);
            }
            else # "."
            {
                $classes []= substr($attribute, 1);
            }
        }

        if (isset($classes))
        {
            $Data['class'] = implode(' ', $classes);
        }

        return $Data;
    }

    private $attributesPattern = '{((?:[#.][-\w]+[ ]*)+)}';
}

