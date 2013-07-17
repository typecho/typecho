<?php
// @(#) $Id: Textile.php,v 1.13 2005/03/21 15:26:55 jhriggs Exp $

/* This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  USA
 */

/**
 * The Textile class serves as a wrapper for all Textile
 * functionality. It is not inherently necessary that Textile be a
 * class; however, this is as close as one can get to a namespace in
 * PHP. Wrapping the functionality in a class prevents name
 * collisions and dirtying of the global namespace. The Textile class
 * uses no global variables and will not have any side-effects on
 * other code.
 *
 * @brief Class wrapper for the Textile functionality.
 */
class Textile {
  /**
   * The @c array containing all of the Textile options for this
   * object.
   *
   * @private
   */
  var $options = array();

  /**
   * The @c string containing the regular expression pattern for a
   * URL. This variable is initialized by @c _create_re() which is
   * called in the contructor.
   *
   * @private
   */
  var $urlre;

  /**
   * The @c string containing the regular expression pattern for
   * punctuation characters. This variable is initialized by
   * @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $punct;

  /**
   * The @c string containing the regular expression pattern for the
   * valid vertical alignment codes. This variable is initialized by
   * @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $valignre;

  /**
   * The @c string containing the regular expression pattern for the
   * valid table alignment codes. This variable is initialized by
   * @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $tblalignre;

  /**
   * The @c string containing the regular expression pattern for the
   * valid horizontal alignment codes. This variable is initialized by
   * @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $halignre;

  /**
   * The @c string containing the regular expression pattern for the
   * valid alignment codes. This variable is initialized by
   * @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $alignre;

  /**
   * The @c string containing the regular expression pattern for the
   * valid image alignment codes. This variable is initialized by
   * @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $imgalignre;

  /**
   * The @c string containing the regular expression pattern for a
   * class, ID, and/or padding specification. This variable is
   * initialized by @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $clstypadre;

  /**
   * The @c string containing the regular expression pattern for a
   * class and/or ID specification. This variable is initialized by
   * @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $clstyre;

  /**
   * The @c string containing the regular expression pattern for a
   * class, ID, and/or filter specification. This variable is
   * initialized by @c _create_re() which is called in the contructor.
   *
   * @private
   */
  var $clstyfiltre;

  /**
   * The @c string containing the regular expression pattern for a
   * code block. This variable is initialized by @c _create_re() which
   * is called in the contructor.
   *
   * @private
   */
  var $codere;

  /**
   * The @c string containing the regular expression pattern for all
   * block tags. This variable is initialized by @c _create_re() which
   * is called in the contructor.
   *
   * @private
   */
  var $blocktags;

  /**
   * The @c array containing the list of lookup links.
   *
   * @private
   */
  var $links = array();

  /**
   * The @c array containing <code>array</code>s of replacement blocks
   * of text that are temporary removed from the input text to avoid
   * processing. Different functions use this replacement
   * functionality, and each shifts its own replacement array into
   * position 0 and removes it when finished. This avoids having
   * several replacement variables and/or functions clobbering
   * eachothers' replacement blocks.
   *
   * @private
   */
  var $repl = array();

  /**
   * The @c array containing temporary <code>string</code>s used in
   * replacement callbacks. *JHR*
   *
   * @private
   */
  var $tmp = array();

  /**
   * Instantiates a new Textile object. Optional options
   * can be passed to initialize the object. Attributes for the
   * options key are the same as the get/set method names
   * documented here.
   *
   * @param $options The @c array specifying the options to use for
   *        this object.
   *
   * @public
   */
  function Textile($options = array()) {
    $this->options = $options;
    $this->options['filters'] = ($this->options['filters'] ? $this->options['filters'] : array());
    $this->options['charset'] = ($this->options['charset'] ? $this->options['charset'] : 'iso-8859-1');
    $this->options['char_encoding'] = (isset($this->options['char_encoding']) ? $this->options['char_encoding'] : 1);
    $this->options['do_quotes'] = (isset($this->options['do_quotes']) ? $this->options['do_quotes'] : 1);
    $this->options['trim_spaces'] = (isset($this->options['trim_spaces']) ? $this->options['trim_spaces'] : 0);
    $this->options['smarty_mode'] = (isset($this->options['smarty_mode']) ? $this->options['smarty_mode'] : 1);
    $this->options['preserve_spaces'] = (isset($this->options['preserve_spaces']) ? $this->options['preserve_spaaces'] : 0);
    $this->options['head_offset'] = (isset($this->options['head_offset']) ? $this->options['head_offset'] : 0);

    if (is_array($this->options['css'])) {
      $this->css($this->options['css']);
    }
    $this->options['macros'] = ($this->options['macros'] ? $this->options['macros'] : $this->default_macros());
    if (isset($this->options['flavor'])) {
      $this->flavor($this->options['flavor']);
    } else {
      $this->flavor('xhtml1/css');
    }
    $this->_create_re();
  } // function Textile

  // getter/setter methods...

  /**
   * Used to set Textile attributes. Attribute names are the same
   * as the get/set method names documented here.
   *
   * @param $opt A @c string specifying the name of the option to
   *        change or an @c array specifying options and values.
   * @param $value The value for the provided option name.
   *
   * @public
   */
  function set($opt, $value = NULL) {
    if (is_array($opt)) {
      foreach ($opt as $opt => $value) {
        $this->set($opt, $value);
      }
    } else {
      // the following options have special set methods
      // that activate upon setting:
      if ($opt == 'charset') {
        $this->charset($value);
      } elseif ($opt == 'css') {
        $this->css($value);
      } elseif ($opt == 'flavor') {
        $this->flavor($value);
      } else {
        $this->options[$opt] = $value;
      }
    }
  } // function set

  /**
   * Used to get Textile attributes. Attribute names are the same
   * as the get/set method names documented here.
   *
   * @param $opt A @c string specifying the name of the option to get.
   *
   * @return The value for the provided option.
   *
   * @public
   */
  function get($opt) {
    return $this->options[$opt];
  } // function get

  /**
   * Gets or sets the "disable html" control, which allows you to
   * prevent HTML tags from being used within the text processed.
   * Any HTML tags encountered will be removed if disable html is
   * enabled. Default behavior is to allow HTML.
   *
   * @param $disable_html If provided, a @c bool indicating whether or
   *        not this object should disable HTML.
   *
   * @return A true value if this object disables HTML; a false value
   *         otherwise.
   *
   * @public
   */
  function disable_html($disable_html = NULL) {
    if ($disable_html != NULL) {
      $this->options['disable_html'] = $disable_html;
    }
    return ($this->options['disable_html'] ? $this->options['disable_html'] : 0);
  } // function disable_html

  /**
   * Gets or sets the relative heading offset, which allows you to
   * change the heading level used within the text processed. For
   * example, if the heading offset is '2' and the text contains an
   * 'h1' block, an \<h3\> block will be output.
   *
   * @param $head_offset If provided, an @c integer specifying the
   *        heading offset for this object.
   *
   * @return An @c integer containing the heading offset for this
   *         object.
   *
   * @public
   */
  function head_offset($head_offset = NULL) {
    if ($head_offset != NULL) {
      $this->options['head_offset'] = $head_offset;
    }
    return ($this->options['head_offset'] ? $this->options['head_offset'] : 0);
  } // function head_offset

  /**
   * Assigns the HTML flavor of output from Textile. Currently
   * these are the valid choices: html, xhtml (behaves like "xhtml1"),
   * xhtml1, xhtml2. Default flavor is "xhtml1".
   *
   * Note that the xhtml2 flavor support is experimental and incomplete
   * (and will remain that way until the XHTML 2.0 draft becomes a
   * proper recommendation).
   *
   * @param $flavor If provided, a @c string specifying the flavor to
   *        be used for this object.
   *
   * @return A @c string containing the flavor for this object.
   *
   * @public
   */
  function flavor($flavor = NULL) {
    if ($flavor != NULL) {
      $this->options['flavor'] = $flavor;
      if (preg_match('/^xhtml(\d)?(\D|$)/', $flavor, $matches)) {
        if ($matches[1] == '2') {
          $this->options['_line_open'] = '<l>';
          $this->options['_line_close'] = '</l>';
          $this->options['_blockcode_open'] = '<blockcode>';
          $this->options['_blockcode_close'] = '</blockcode>';
          $this->options['css_mode'] = 1;
        } else {
          // xhtml 1.x
          $this->options['_line_open'] = '';
          $this->options['_line_close'] = '<br />';
          $this->options['_blockcode_open'] = '<pre><code>';
          $this->options['_blockcode_close'] = '</code></pre>';
          $this->options['css_mode'] = 1;
        }
      } elseif (preg_match('/^html/', $flavor)) {
        $this->options['_line_open'] = '';
        $this->options['_line_close'] = '<br>';
        $this->options['_blockcode_open'] = '<pre><code>';
        $this->options['_blockcode_close'] = '</code></pre>';
        $this->options['css_mode'] = preg_match('/\/css/', $flavor);
      }
      if ($this->options['css_mode'] && !isset($this->options['css'])) { $this->_css_defaults(); }
    }
    return $this->options['flavor'];
  } // function flavor

  /**
   * Gets or sets the css support for Textile. If css is enabled,
   * Textile will emit CSS rules. You may pass a 1 or 0 to enable
   * or disable CSS behavior altogether. If you pass an associative array,
   * you may assign the CSS class names that are used by
   * Textile. The following key names for such an array are
   * recognized:
   *
   * <ul>
   * <li><b>class_align_right</b>
   *
   * defaults to 'right'</li>
   *
   * <li><b>class_align_left</b>
   *
   * defaults to 'left'</li>
   *
   * <li><b>class_align_center</b>
   *
   * defaults to 'center'</li>
   *
   * <li><b>class_align_top</b>
   *
   * defaults to 'top'</li>
   *
   * <li><b>class_align_bottom</b>
   *
   * defaults to 'bottom'</li>
   *
   * <li><b>class_align_middle</b>
   *
   * defaults to 'middle'</li>
   *
   * <li><b>class_align_justify</b>
   *
   * defaults to 'justify'</li>
   *
   * <li><b>class_caps</b>
   *
   * defaults to 'caps'</li>
   *
   * <li><b>class_footnote</b>
   *
   * defaults to 'footnote'</li>
   *
   * <li><b>id_footnote_prefix</b>
   *
   * defaults to 'fn'</li>
   *
   * </ul>
   *
   * @param $css If provided, either a @c bool indicating whether or
   *        not this object should use css or an associative @c array
   *        specifying class names to use.
   *
   * @return Either an associative @c array containing class names
   *         used by this object, or a true or false value indicating
   *         whether or not this object uses css.
   *
   * @public
   */
  function css($css = NULL) {
    if ($css != NULL) {
      if (is_array($css)) {
        $this->options['css'] = $css;
        $this->options['css_mode'] = 1;
      } else {
        $this->options['css_mode'] = $css;
        if ($this->options['css_mode'] && !isset($this->options['css'])) { $this->_css_defaults(); }
      }
    }
    return ($this->options['css_mode'] ? $this->options['css'] : 0);
  } // function css

  /**
   * Gets or sets the character set targetted for publication.
   * At this time, Textile only changes its behavior
   * if the 'utf-8' character set is assigned.
   *
   * Specifically, if utf-8 is requested, any special characters
   * created by Textile will be output as native utf-8 characters
   * rather than HTML entities.
   *
   * @param $charset If provided, a @c string specifying the
   *        characater set to be used for this object.
   *
   * @return A @c string containing the character set for this object.
   *
   * @public
   */
  function charset($charset = NULL) {
    if ($charset != NULL) {
        $this->options['charset'] = $charset;
        if (preg_match('/^utf-?8$/i', $this->options['charset'])) {
          $this->char_encoding(0);
        } else {
          $this->char_encoding(1);
        }
    }
    return $this->options['charset'];
  } // function charset

  /**
   * Gets or sets the physical file path to root of document files.
   * This path is utilized when images are referenced and size
   * calculations are needed (the getimagesize() function is used to read
   * the image dimensions).
   *
   * @param $docroot If provided, a @c string specifying the document
   *        root to use for this object.
   *
   * @return A @c string containing the docroot for this object.
   *
   * @public
   */
  function docroot($docroot = NULL) {
    if ($docroot != NULL) {
      $this->options['docroot'] = $docroot;
    }
    return $this->options['docroot'];
  } // function docroot

  /**
   * Gets or sets the 'trim spaces' control flag. If enabled, this
   * will clear any lines that have only spaces on them (the newline
   * itself will remain).
   *
   * @param $trim_spaces If provided, a @c bool indicating whether or
   *        not this object should trim spaces.
   *
   * @return A true value if this object trims spaces; a false value
   *         otherwise.
   *
   * @public
   */
  function trim_spaces($trim_spaces = NULL) {
    if ($trim_spaces != NULL) {
      $this->options['trim_spaces'] = $trim_spaces;
    }
    return $this->options['trim_spaces'];
  } // function trim_spaces

  /**
   * Gets or sets a parameter that is passed to filters.
   *
   * @param $filter_param If provided, a parameter that this object
   *        should pass to filters.
   *
   * @return The parameter this object passes to filters.
   *
   * @public
   */
  function filter_param($filter_param = NULL) {
    if ($filter_param != NULL) {
      $this->options['filter_param'] = $filter_param;
    }
    return $this->options['filter_param'];
  } // function filter_param

  /**
   * Gets or sets the 'preserve spaces' control flag. If enabled, this
   * will replace any double spaces within the paragraph data with the
   * \&amp;#8195; HTML entity (wide space). The default is 0. Spaces will
   * pass through to the browser unchanged and render as a single space.
   * Note that this setting has no effect on spaces within \<pre\>,
   * \<code\> blocks or \<script\> sections.
   *
   * @param $preserve_spaces If provided, a @c bool indicating whether
   *        or not this object should preserve spaces.
   *
   * @return A true value if this object preserves spaces; a false
   *         value otherwise.
   *
   * @public
   */
  function preserve_spaces($preserve_spaces = NULL) {
    if ($preserve_spaces != NULL) {
      $this->options['preserve_spaces'] = $preserve_spaces;
    }
    return $this->options['preserve_spaces'];
  } // function preserve_spaces

  /**
   * Gets or sets a list of filters to make available for
   * Textile to use. Returns a hash reference of the currently
   * assigned filters.
   *
   * @param $filters If provided, an @c array of filters to be used
   *        for this object.
   *
   * @return An @c array containing the filters for this object.
   *
   * @public
   */
  function filters($filters = NULL) {
    if ($filters != NULL) {
      $this->options['filters'] = $filters;
    }
    return $this->options['filters'];
  } // function filters

  /**
   * Gets or sets the character encoding logical flag. If character
   * encoding is enabled, the htmlentities function is used to
   * encode special characters. If character encoding is disabled,
   * only \<, \>, " and & are encoded to HTML entities.
   *
   * @param $char_encoding If provided, a @c bool indicating whether
   *        or not this object should encode special characters.
   *
   * @return A true value if this object encodes special characters; a
   *         false value otherwise.
   *
   * @public
   */
  function char_encoding($char_encoding = NULL) {
    if ($char_encoding != NULL) {
      $this->options['char_encoding'] = $char_encoding;
    }
    return $this->options['char_encoding'];
  } // function char_encoding

  /**
   * Gets or sets the "smart quoting" control flag. Returns the
   * current setting.
   *
   * @param $do_quotes If provided, a @c bool indicating whether or
   *        not this object should use smart quoting.
   *
   * @return A true value if this object uses smart quoting; a false
   *         value otherwise.
   *
   * @public
   */
  function handle_quotes($do_quotes = NULL) {
    if ($do_quotes != NULL) {
      $this->options['do_quotes'] = $do_quotes;
    }
    return $this->options['do_quotes'];
  } // function handle_quotes

  // end of getter/setter methods

  /**
   * Creates the class variable regular expression patterns used by
   * Textile. They are not initialized in the declaration, because
   * some rely on the others, requiring a @c $this reference.
   *
   * PHP does not have the Perl qr operator to quote or precompile
   * patterns, so to avoid escaping and matching problems, all
   * patterns must use the same delimiter; this implementation uses
   * {}. Every use of these patterns within this class has been
   * changed to use these delimiters. *JHR*
   *
   * @private
   */
  function _create_re() {
    // a URL discovery regex. This is from Mastering Regex from O'Reilly.
    // Some modifications by Brad Choate <brad at bradchoate dot com>
    $this->urlre = '(?:
    # Must start out right...
    (?=[a-zA-Z0-9./#])
    # Match the leading part (proto://hostname, or just hostname)
    (?:
        # ftp://, http://, or https:// leading part
        (?:ftp|https?|telnet|nntp)://(?:\w+(?::\w+)?@)?[-\w]+(?:\.\w[-\w]*)+
        |
        (?:mailto:)?[-\+\w]+@[-\w]+(?:\.\w[-\w]*)+
        |
        # or, try to find a hostname with our more specific sub-expression
        (?i: [a-z0-9] (?:[-a-z0-9]*[a-z0-9])? \. )+ # sub domains
        # Now ending .com, etc. For these, require lowercase
        (?-i: com\b
            | edu\b
            | biz\b
            | gov\b
            | in(?:t|fo)\b # .int or .info
            | mil\b
            | net\b
            | org\b
            | museum\b
            | aero\b
            | coop\b
            | name\b
            | pro\b
            | [a-z][a-z]\b # two-letter country codes
        )
    )?

    # Allow an optional port number
    (?: : \d+ )?

    # The rest of the URL is optional, and begins with / . . .
    (?:
     /?
     # The rest are heuristics for what seems to work well
     [^.!,?;:"\'<>()\[\]{}\s\x7F-\xFF]*
     (?:
        [.!,?;:]+  [^.!,?;:"\'<>()\[\]{}\s\x7F-\xFF]+ #\'"
     )*
    )?
)';

    $this->punct = '[\!"\#\$%&\'()\*\+,\-\./:;<=>\?@\[\\\\\]\^_`{\|}\~]';
    $this->valignre = '[\-^~]';
    $this->tblalignre = '[<>=]';
    $this->halignre = '(?:<>|[<>=])';
    $this->alignre = '(?:(?:' . $this->valignre . '|<>' . $this->valignre . '?|' . $this->valignre . '?<>|' . $this->valignre . '?' . $this->halignre . '?|' . $this->halignre . '?' . $this->valignre . '?)(?!\w))';
    $this->imgalignre = '(?:(?:[<>]|' . $this->valignre . '){1,2})';

    $this->clstypadre = '(?:
  (?:\([A-Za-z0-9_\- \#]+\))
  |
  (?:{
      (?: \( [^)]+ \) | [^\}] )+
     })
  |
  (?:\(+? (?![A-Za-z0-9_\-\#]) )
  |
  (?:\)+?)
  |
  (?: \[ [a-zA-Z\-]+? \] )
)';

    $this->clstyre = '(?:
  (?:\([A-Za-z0-9_\- \#]+\))
  |
  (?:{
      [A-Za-z0-9_\-](?: \( [^)]+ \) | [^\}] )+
     })
  |
  (?: \[ [a-zA-Z\-]+? \] )
)';

    $this->clstyfiltre = '(?:
  (?:\([A-Za-z0-9_\- \#]+\))
  |
  (?:{
      [A-Za-z0-9_\-](?: \( [^)]+ \) | [^\}] )+
     })
  |
  (?:\|[^\|]+\|)
  |
  (?:\(+?(?![A-Za-z0-9_\-\#]))
  |
  (?:\)+)
  |
  (?: \[ [a-zA-Z]+? \] )
)';

    $this->codere = '(?:
    (?:
      [\[{]
      @                           # opening
      (?:\[([A-Za-z0-9]+)\])?     # $1: language id
      (.+?)                       # $2: code
      @                           # closing
      [\]}]
    )
    |
    (?:
      (?:^|(?<=[\s\(]))
      @                           # opening
      (?:\[([A-Za-z0-9]+)\])?     # $3: language id
      ([^\s].+?[^\s])             # $4: code itself
      @                           # closing
      (?:$|(?=' . $this->punct . '{1,2}|\s))
    )
)';

    $this->blocktags = '
    <
    (( /? ( h[1-6]
     | p
     | pre
     | div
     | table
     | t[rdh]
     | [ou]l
     | li
     | block(?:quote|code)
     | form
     | input
     | select
     | option
     | textarea
     )
    [ >]
    )
    | !--
    )
';
  } // function _create_re

  /**
   * Transforms the provided text using Textile markup rules.
   *
   * @param $str The @c string specifying the text to process.
   *
   * @return A @c string containing the processed (X)HTML.
   *
   * @public
   */
  function process($str) {
    /*
     * Function names in PHP are case insensitive, so function
     * textile() cannot be redefined.  Thus, this PHP implementation
     * will only use process().
     *
     *   return $this->textile($str);
     * } // function process
     *
     * function textile($str) {
     */

    // quick translator for abbreviated block names
    // to their tag
    $macros = array('bq' => 'blockquote');

    // an array to hold any portions of the text to be preserved
    // without further processing by Textile
    array_unshift($this->repl, array());

    // strip out extra newline characters. we're only matching for \n herein
    //$str = preg_replace('!(?:\r?\n|\r)!', "\n", $str);
    $str = preg_replace('!(?:\015?\012|\015)!', "\n", $str);

    // optionally remove trailing spaces
    if ($this->options['trim_spaces']) { $str = preg_replace('/ +$/m', '', $str); }

    // preserve contents of the '==', 'pre', 'blockcode' sections
    $str = preg_replace_callback('{(^|\n\n)==(.+?)==($|\n\n)}s',
                                 $this->_cb('"$m[1]\n\n" . $me->_repl($me->repl[0], $me->format_block(array("text" => $m[2]))) . "\n\n$m[3]"'), $str);

    if (!$this->disable_html()) {
      // preserve style, script tag contents
      $str = preg_replace_callback('!(<(style|script)(?:>| .+?>).*?</\2>)!s', $this->_cb('$me->_repl($me->repl[0], $m[1])'), $str);

      // preserve HTML comments
      $str = preg_replace_callback('|(<!--.+?-->)|s', $this->_cb('$me->_repl($me->repl[0], $m[1])'), $str);

      // preserve pre block contents, encode contents by default
      $pre_start = count($this->repl[0]);
      $str = preg_replace_callback('{(<pre(?: [^>]*)?>)(.+?)(</pre>)}s',
                                   $this->_cb('"\n\n" . $me->_repl($me->repl[0], $m[1] . $me->encode_html($m[2], 1) . $m[3]) . "\n\n"'), $str);
      // fix code tags within pre blocks we just saved.
      for ($i = $pre_start; $i < count($this->repl[0]); $i++) {
        $this->repl[0][$i] = preg_replace('|&lt;(/?)code(.*?)&gt;|s', '<$1code$2>', $this->repl[0][$i]);
      }

      // preserve code blocks by default, encode contents
      $str = preg_replace_callback('{(<code(?: [^>]+)?>)(.+?)(</code>)}s',
                                   $this->_cb('$me->_repl($me->repl[0], $m[1] . $me->encode_html($m[2], 1) . $m[3])'), $str);

      // encode blockcode tag (an XHTML 2 tag) and encode it's
      // content by default
      $str = preg_replace_callback('{(<blockcode(?: [^>]+)?>)(.+?)(</blockcode>)}s',
                                   $this->_cb('"\n\n" . $me->_repl($me->repl[0], $m[1] . $me->encode_html($m[2], 1) . $m[3]) . "\n\n"'), $str);

      // preserve PHPish, ASPish code
      $str = preg_replace_callback('!(<([\?%]).*?(\2)>)!s', $this->_cb('$me->_repl($me->repl[0], $m[1])'), $str);
    }

    // pass through and remove links that follow this format
    // [id_without_spaces (optional title text)]url
    // lines like this are stripped from the content, and can be
    // referred to using the "link text":id_without_spaces syntax
    //$links = array();
    $str = preg_replace_callback('{(?:\n|^) [ ]* \[ ([^ ]+?) [ ]*? (?:\( (.+?) \) )?  \] ((?:(?:ftp|https?|telnet|nntp)://|/)[^ ]+?) [ ]* (\n|$)}mx',
                                 $this->_cb('($me->links[$m[1]] = array("url" => $m[3], "title" => $m[2])) ? $m[4] : $m[4]'), $str);
    //$this->links = $links;

    // eliminate starting/ending blank lines
    $str = preg_replace('/^\n+/s', '', $str, 1);
    $str = preg_replace('/\n+$/s', '', $str, 1);

    // split up text into paragraph blocks, capturing newlines too
    $para = preg_split('/(\n{2,})/', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
    unset($block, $bqlang, $filter, $class, $sticky, $lines,
          $style, $stickybuff, $lang, $clear);

    $out = '';

    foreach ($para as $para) {
      if (preg_match('/^\n+$/s', $para)) {
        if ($sticky && $stickybuff) {
          $stickybuff .= $para;
        } else {
          $out .= $para;
        }
        continue;
      }

      if ($sticky) {
        $sticky++;
      } else {
        unset($block);
        unset($class);
        $style = '';
        unset($lang);
      }

      unset($id, $cite, $align, $padleft, $padright, $lines, $buffer);
      if (preg_match('{^(h[1-6]|p|bq|bc|fn\d+)
                        ((?:' . $this->clstyfiltre . '*|' . $this->halignre . ')*)
                        (\.\.?)
                        (?::(\d+|' . $this->urlre . '))?\ (.*)$}sx', $para, $matches)) {
        if ($sticky) {
          if ($block == 'bc') {
            // close our blockcode section
            $out = preg_replace('/\n\n$/', '', $out, 1);
            $out .= $this->options['_blockcode_close'] . "\n\n";
          } elseif ($block == 'bq') {
            $out = preg_replace('/\n\n$/', '', $out, 1);
            $out .= '</blockquote>' . "\n\n";
          } elseif ($block == 'table') {
            $table_out = $this->format_table(array('text' => $stickybuff));
            if (!$table_out) { $table_out = ''; }
            $out .= $table_out;
            unset($stickybuff);
          } elseif ($block == 'dl') {
            $dl_out = $this->format_deflist(array('text' => $stickybuff));
            if (!$dl_out) { $dl_out = ''; }
            $out .= $dl_out;
            unset($stickybuff);
          }
          $sticky = 0;
        }
        // block macros: h[1-6](class)., bq(class)., bc(class)., p(class).
        //warn "paragraph: [[$para]]\n\tblock: $1\n\tparams: $2\n\tcite: $4";
        $block = $matches[1];
        $params = $matches[2];
        $cite = $matches[4];
        if ($matches[3] == '..') {
          $sticky = 1;
        } else {
          $sticky = 0;
          unset($class);
          unset($bqlang);
          unset($lang);
          $style = '';
          unset($filter);
        }
        if (preg_match('/^h([1-6])$/', $block, $matches2)) {
          if ($this->options['head_offset']) {
            $block = 'h' . ($matches2[1] + $this->options['head_offset']);
          }
        }
        if (preg_match('{(' . $this->halignre . '+)}', $params, $matches2)) {
          $align = $matches2[1];
          $params = preg_replace('{' . $this->halignre . '+}', '', $params, 1);
        }
        if ($params) {
          if (preg_match('/\|(.+)\|/', $params, $matches2)) {
            $filter = $matches2[1];
            $params = preg_replace('/\|.+?\|/', '', $params, 1);
          }
          if (preg_match('/{([^}]+)}/', $params, $matches2)) {
            $style = $matches2[1];
            $style = preg_replace('/\n/', ' ', $style);
            $params = preg_replace('/{[^}]+}/', '', $params);
          }
          if (preg_match('/\(([A-Za-z0-9_\-\ ]+?)(?:\#(.+?))?\)/', $params, $matches2) ||
              preg_match('/\(([A-Za-z0-9_\-\ ]+?)?(?:\#(.+?))\)/', $params, $matches2)) {
            if ($matches2[1] || $matches2[2]) {
              $class = $matches2[1];
              $id = $matches2[2];
              if ($class) {
                $params = preg_replace('/\([A-Za-z0-9_\-\ ]+?(#.*?)?\)/', '', $params);
              } elseif ($id) {
                $params = preg_replace('/\(#.+?\)/', '', $params);
              }
            }
          }
          if (preg_match('/(\(+)/', $params, $matches2)) {
            $padleft = strlen($matches2[1]);
            $params = preg_replace('/\(+/', '', $params, 1);
          }
          if (preg_match('/(\)+)/', $params, $matches2)) {
            $padright = strlen($matches2[1]);
            $params = preg_replace('/\)+/', '', $params, 1);
          }
          if (preg_match('/\[(.+?)\]/', $params, $matches2)) {
            $lang = $matches2[1];
            if ($block == 'bc') {
              $bqlang = $lang;
              unset($lang);
            }
            $params = preg_replace('/\[.+?\]/', '', $params, 1);
          }
        }
        // warn "settings:\n\tblock: $block\n\tpadleft: $padleft\n\tpadright: $padright\n\tclass: $class\n\tstyle: $style\n\tid: $id\n\tfilter: $filter\n\talign: $align\n\tlang: $lang\n\tsticky: $sticky";
        $para = $matches[5];
      } elseif (preg_match('|^<textile#(\d+)>$|', $para, $matches)) {
        $buffer = $this->repl[0][$matches[1] - 1];
      } elseif (preg_match('/^clear([<>]+)?\.$/', $para, $matches)) {
        if ($matches[1] == '<') {
          $clear = 'left';
        } elseif ($matches[1] == '>') {
          $clear = 'right';
        } else {
          $clear = 'both';
        }
        continue;
      } elseif ($sticky && $stickybuff &&
                ($block == 'table' || $block == 'dl')) {
        $stickybuff .= $para;
        continue;
      } elseif (preg_match('{^(?:' . $this->halignre . '|' . $this->clstypadre . '*)*
                              [\*\#]
                              (?:' . $this->halignre . '|' . $this->clstypadre . '*)*
                              \ }x', $para)) {
        // '*', '#' prefix means a list
        $buffer = $this->format_list(array('text' => $para));
      } elseif (preg_match('{^(?:table(?:' . $this->tblalignre . '|' . $this->clstypadre . '*)*
                              (\.\.?)\s+)?
                              (?:_|' . $this->alignre . '|' . $this->clstypadre . '*)*\|}x', $para, $matches)) {
        // handle wiki-style tables
        if ($matches[1] && ($matches[1] == '..')) {
          $block = 'table';
          $stickybuff = $para;
          $sticky = 1;
          continue;
        } else {
          $buffer = $this->format_table(array('text' => $para));
        }
      } elseif (preg_match('{^(?:dl(?:' . $this->clstyre . ')*(\.\.?)\s+)}x', $para, $matches)) {
        // handle definition lists
        if ($matches[1] && ($matches[1] == '..')) {
          $block = 'dl';
          $stickybuff = $para;
          $sticky = 1;
          continue;
        } else {
          $buffer = $this->format_deflist(array('text' => $para));
        }
      }
      if ($buffer) {
        $out .= $buffer;
        continue;
      }
      $lines = preg_split('/\n/', $para);
      if ((count($lines) == 1) && ($lines[0] == '')) {
        continue;
      }

      $block = ($block ? $block : 'p');

      $buffer = '';
      $pre = '';
      $post = '';

      if ($block == 'bc') {
        if ($sticky <= 1) {
          $pre .= $this->options['_blockcode_open'];
          $pre = preg_replace('/>$/s', '', $pre, 1);
          if ($bqlang) { $pre .= " language=\"$bqlang\""; }
          if ($align) {
            $alignment = $this->_halign($align);
            if ($this->options['css_mode']) {
              if (($padleft || $padright) &&
                  (($alignment == 'left') || ($alignment == 'right'))) {
                $style .= ';float:' . $alignment;
              } else {
                $style .= ';text-align:' . $alignment;
              }
              $class .= ' ' . ($this->options['css']["class_align_$alignment"] ? $this->options['css']["class_align_$alignment"] : $alignment);
            } else {
              if ($alignment) { $pre .= " align=\"$alignment\""; }
            }
          }
          if ($padleft) { $style .= ";padding-left:${padleft}em"; }
          if ($padright) { $style .= ";padding-right:${padright}em"; }
          if ($clear) { $style .= ";clear:${clear}"; }
          if ($class) { $class = preg_replace('/^ /', '', $class, 1); }
          if ($class) { $pre .= " class=\"$class\""; }
          if ($id) { $pre .= " id=\"$id\""; }
          if ($style) { $style = preg_replace('/^;/', '', $style, 1); }
          if ($style) { $pre .= " style=\"$style\""; }
          if ($lang) { $pre .= " lang=\"$lang\""; }
          $pre .= '>';
          unset($lang);
          unset($bqlang);
          unset($clear);
        }
        $para = preg_replace_callback('{(?:^|(?<=[\s>])|([{[]))
                                        ==(.+?)==
                                        (?:$|([\]}])|(?=' . $this->punct . '{1,2}|\s))}sx',
                                      $this->_cb('$me->_repl($me->repl[0], $me->format_block(array("text" => $m[2], "inline" => 1, "pre" => $m[1], "post" => $m[3])))'), $para);
        $buffer .= $this->encode_html_basic($para, 1);
        $buffer = preg_replace('/&lt;textile#(\d+)&gt;/', '<textile#$1>', $buffer);
        if ($sticky == 0) {
          $post .= $this->options['_blockcode_close'];
        }
        $out .= $pre . $buffer . $post;
        continue;
      } elseif ($block == 'bq') {
        if ($sticky <= 1) {
          $pre .= '<blockquote';
          if ($align) {
            $alignment = $this->_halign($align);
            if ($this->options['css_mode']) {
              if (($padleft || $padright) &&
                  (($alignment == 'left') || ($alignment == 'right'))) {
                $style .= ';float:' . $alignment;
              } else {
                $style .= ';text-align:' . $alignment;
              }
              $class .= ' ' . ($this->options['css']["class_align_$alignment"] ? $this->options['css']["class_align_$alignment"] : $alignment);
            } else {
              if ($alignment) { $pre .= " align=\"$alignment\""; }
            }
          }
          if ($padleft) { $style .= ";padding-left:${padleft}em"; }
          if ($padright) { $style .= ";padding-right:${padright}em"; }
          if ($clear) { $style .= ";clear:${clear}"; }
          if ($class) { $class = preg_replace('/^ /', '', $class, 1); }
          if ($class) { $pre .= " class=\"$class\""; }
          if ($id) { $pre .= " id=\"$id\""; }
          if ($style) { $style = preg_replace('/^;/', '', $style, 1); }
          if ($style) { $pre .= " style=\"$style\""; }
          if ($lang) { $pre .= " lang=\"$lang\""; }
          if ($cite) { $pre .= ' cite="' . $this->format_url(array('url' => $cite)) . '"'; }
          $pre .= '>';
          unset($clear);
        }
        $pre .= '<p>';
      } elseif (preg_match('/fn(\d+)/', $block, $matches)) {
        $fnum = $matches[1];
        $pre .= '<p';
        if ($this->options['css']['class_footnote']) { $class .= ' ' . $this->options['css']['class_footnote']; }
        if ($align) {
          $alignment = $this->_halign($align);
          if ($this->options['css_mode']) {
            if (($padleft || $padright) &&
                (($alignment == 'left') || ($alignment == 'right'))) {
              $style .= ';float:' . $alignment;
            } else {
              $style .= ';text-align:' . $alignment;
            }
            $class .= ($this->options['css']["class_align_$alignment"] ? $this->options['css']["class_align_$alignment"] : $alignment);
          } else {
            $pre .= " align=\"$alignment\"";
          }
        }
        if ($padleft) { $style .= ";padding-left:${padleft}em"; }
        if ($padright) { $style .= ";padding-right:${padright}em"; }
        if ($clear) { $style .= ";clear:${clear}"; }
        if ($class) { $class = preg_replace('/^ /', '', $class, 1); }
        if ($class) { $pre .= " class=\"$class\""; }
        $pre .= ' id="' . ($this->options['css']['id_footnote_prefix'] ? $this->options['css']['id_footnote_prefix'] : 'fn') . $fnum . '"';
        if ($style) { $style = preg_replace('/^;/', '', $style, 1); }
        if ($style) { $pre .= " style=\"$style\""; }
        if ($lang) { $pre .= " lang=\"$lang\""; }
        $pre .= '>';
        $pre .= '<sup>' . $fnum . '</sup> ';
        // we can close like a regular paragraph tag now
        $block = 'p';
        unset($clear);
      } else {
        $pre .= '<' . ($macros[$block] ? $macros[$block] : $block);
        if ($align) {
          $alignment = $this->_halign($align);
          if ($this->options['css_mode']) {
            if (($padleft || $padright) &&
                (($alignment == 'left') || ($alignment == 'right'))) {
              $style .= ';float:' . $alignment;
            } else {
              $style .= ';text-align:' . $alignment;
            }
            $class .= ' ' . ($this->options['css']["class_align_$alignment"] ? $this->options['css']["class_align_$alignment"] : $alignment);
          } else {
            $pre .= " align=\"$alignment\"";
          }
        }
        if ($padleft) { $style .= ";padding-left:${padleft}em"; }
        if ($padright) { $style .= ";padding-right:${padright}em"; }
        if ($clear) { $style .= ";clear:${clear}"; }
        if ($class) { $class = preg_replace('/^ /', '', $class, 1); }
        if ($class) { $pre .= " class=\"$class\""; }
        if ($id) { $pre .= " id=\"$id\""; }
        if ($style) { $style = preg_replace('/^;/', '', $style, 1); }
        if ($style) { $pre .= " style=\"$style\""; }
        if ($lang) { $pre .= " lang=\"$lang\""; }
        if ($cite && ($block == 'bq')) { $pre .= ' cite="' . $this->format_url(array('url' => $cite)) . '"'; }
        $pre .= '>';
        unset($clear);
      }

      $buffer = $this->format_paragraph(array('text' => $para));

      if ($block == 'bq') {
        if (!preg_match('/<p[ >]/', $buffer)) { $post .= '</p>'; }
        if ($sticky == 0) {
          $post .= '</blockquote>';
        }
      } else {
        $post .= '</' . $block . '>';
      }

      if (preg_match('{' . $this->blocktags . '}x', $buffer)) {
        $buffer = preg_replace('/^\n\n/s', '', $buffer, 1);
        $out .= $buffer;
      } else {
        if ($filter) { $buffer = $this->format_block(array('text' => "|$filter|" . $buffer, 'inline' => 1)); }
        $out .= $pre . $buffer . $post;
      }
    }

    if ($sticky) {
      if ($block == 'bc') {
        // close our blockcode section
        $out .= $this->options['_blockcode_close']; // . "\n\n";
      } elseif ($block == 'bq') {
        $out .= '</blockquote>'; // . "\n\n";
      } elseif (($block == 'table') && $stickybuff) {
        $table_out = $this->format_table(array('text' => $stickybuff));
        if ($table_out) { $out .= $table_out; }
      } elseif (($block == 'dl') && $stickybuff) {
        $dl_out = $this->format_deflist(array('text' => $stickybuff));
        if ($dl_out) { $out .= $dl_out; }
      }
    }

    // cleanup-- restore preserved blocks
    for ($i = count($this->repl[0]); $i > 0; $i--) {
      $out = preg_replace('!(?:<|&lt;)textile#' . $i . '(?:>|&gt;)!', str_replace('$', '\\$', $this->repl[0][$i - 1]), $out, 1);
    }
    array_shift($this->repl);

    // scan for br, hr tags that are not closed and close them
    // only for xhtml! just the common ones -- don't fret over input
    // and the like.
    if (preg_match('/^xhtml/i', $this->flavor())) {
      $out = preg_replace('/(<(?:img|br|hr)[^>]*?(?<!\/))>/', '$1 />', $out);
    }

    return $out;
  } // function process

  /**
   * Processes a single paragraph. The following attributes are
   * allowed:
   *
   * <ul>
   *
   * <li><b>text</b>
   *
   * The text to be processed.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the paragraph.
   *
   * @return A @c string containing the formatted paragraph.
   *
   * @private
   */
  function format_paragraph($args) {
    $buffer = (isset($args['text']) ? $args['text'] : '');

    array_unshift($this->repl, array());
    $buffer = preg_replace_callback('{(?:^|(?<=[\s>])|([{[]))
                                      ==(.+?)==
                                      (?:$|([\]}])|(?=' . $this->punct . '{1,2}|\s))}sx',
                                    $this->_cb('$me->_repl($me->repl[0], $me->format_block(array("text" => $m[2], "inline" => 1, "pre" => $m[1], "post" => $m[3])))'), $buffer);

    unset($tokens);
    if (preg_match('/</', $buffer) && (!$this->disable_html())) {  // optimization -- no point in tokenizing if we
                                       // have no tags to tokenize
      $tokens = $this->_tokenize($buffer);
    } else {
      $tokens = array(array('text', $buffer));
    }
    $result = '';
    foreach ($tokens as $token) {
      $text = $token[1];
      if ($token[0] == 'tag') {
        $text = preg_replace('/&(?!amp;)/', '&amp;', $text);
        $result .= $text;
      } else {
        $text = $this->format_inline(array('text' => $text));
        $result .= $text;
      }
    }

    // now, add line breaks for lines that contain plaintext
    $lines = preg_split('/\n/', $result);
    $result = '';
    $needs_closing = 0;
    foreach ($lines as $line) {
      if (!preg_match('{(' . $this->blocktags . ')}x', $line)
          && ((preg_match('/^[^<]/', $line) || preg_match('/>[^<]/', $line))
              || !preg_match('/<img /', $line))) {
        if ($this->options['_line_open']) {
          if ($result != '') { $result .= "\n"; }
          $result .= $this->options['_line_open'] . $line . $this->options['_line_close'];
        } else {
          if ($needs_closing) {
            $result .= $this->options['_line_close'] . "\n";
          } else {
            $needs_closing = 1;
            if ($result != '') { $result .= "\n"; }
          }
          $result .= $line;
        }
      } else {
        if ($needs_closing) {
          $result .= $this->options['_line_close'] . "\n";
        } else {
          if ($result != '') { $result .= "\n"; }
        }
        $result .= $line;
        $needs_closing = 0;
      }
    }

    // at this point, we will restore the \001's to \n's (reversing
    // the step taken in _tokenize).
    //$result = preg_replace('/\r/', "\n", $result);
    $result = preg_replace('/\001/', "\n", $result);

    for ($i = count($this->repl[0]); $i > 0; $i--) {
      $result = preg_replace("|<textile#$i>|", str_replace('$', '\\$', $this->repl[0][$i - 1]), $result, 1);
    }
    array_shift($this->repl);

    // quotalize
    if ($this->options['do_quotes']) {
      $result = $this->process_quotes($result);
    }

    return $result;
  } // function format_paragraph

  /**
   * Processes an inline string (plaintext) for Textile syntax.
   * The following attributes are allowed:
   *
   * <ul>
   *
   * <li><b>text</b>
   *
   * The text to be processed.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the inline string.
   *
   * @return A @c string containing the formatted inline string.
   *
   * @private
   */
  function format_inline($args) {
    $qtags = array(array('**', 'b',      '(?<!\*)\*\*(?!\*)', '\*'),
                   array('__', 'i',      '(?<!_)__(?!_)', '_'),
                   array('??', 'cite',   '\?\?(?!\?)', '\?'),
                   array('*',  'strong', '(?<!\*)\*(?!\*)', '\*'),
                   array('_',  'em',     '(?<!_)_(?!_)', '_'),
                   array('-',  'del',    '(?<!\-)\-(?!\-)', '-'),
                   array('+',  'ins',    '(?<!\+)\+(?!\+)', '\+'),
                   array('++', 'big',    '(?<!\+)\+\+(?!\+)', '\+\+'),
                   array('--', 'small',  '(?<!\-)\-\-(?!\-)', '\-\-'),
                   array('~',  'sub',    '(?<!\~)\~(?![\\\\/~])', '\~'));
    $text = (isset($args['text']) ? $args['text'] : '');

    array_unshift($this->repl, array());

    $text = preg_replace_callback('{' . $this->codere . '}mx', $this->_cb('$me->_repl($me->repl[0], $me->format_code(array("text" => $m[2] . $m[4], "lang" => $m[1] . $m[3])))'), $text);

    // images must be processed before encoding the text since they might
    // have the <, > alignment specifiers...

    // !blah (alt)! -> image
    $text = preg_replace_callback('{(?:^|(?<=[\s>])|([{[]))      # $1: open brace/bracket
                                    !                            # opening
                                    (' . $this->imgalignre . '?) # $2: optional alignment
                                    (' . $this->clstypadre . '*) # $3: optional CSS class/id
                                    (' . $this->imgalignre . '?) # $4: optional alignment
                                    (?:\s*)                      # space between alignment/css stuff
                                    ([^\s\(!]+)                  # $5: filename
                                    (\s*[^\(!]*(?:\([^\)]+\))?[^!]*) # $6: extras (alt text)
                                    !                            # closing
                                    (?::(\d+|' . $this->urlre . '))? # $7: optional URL
                                    (?:$|([\]}])|(?=' . $this->punct . '{1,2}|\s)) # $8: closing brace/bracket
                                   }mx', $this->_cb('$me->_repl($me->repl[0], $me->format_image(array("pre" => $m[1], "src" => $m[5], "align" => ($m[2] ? $m[2] : $m[4]), "extra" => $m[6], "url" => $m[7], "clsty" => $m[3], "post" => $m[8])))'), $text);

    $text = preg_replace_callback('{(?:^|(?<=[\s>])|([{[]))     # $1: open brace/bracket
                                    %                           # opening
                                    (' . $this->halignre . '?)  # $2: optional alignment
                                    (' . $this->clstyre . '*)   # $3: optional CSS class/id
                                    (' . $this->halignre . '?)  # $4: optional alignment
                                    (?:\s*)                     # spacing
                                    ([^%]+?)                    # $5: text
                                    %                           # closing
                                    (?::(\d+|' . $this->urlre . '))? # $6: optional URL
                                    (?:$|([]}])|(?=' . $this->punct . '{1,2}|\s)) # $7: closing brace/bracket
                                   }mx', $this->_cb('$me->_repl($me->repl[0], $me->format_span(array("pre" => $m[1], "text" => $m[5], "align" => ($m[2] ? $m[2] : $m[4]), "cite" => $m[6], "clsty" => $m[3], "post" => $m[7])))'), $text);

    $text = $this->encode_html($text);
    $text = preg_replace('!&lt;textile#(\d+)&gt;!', '<textile#$1>', $text);
    $text = preg_replace('!&amp;quot;!', '&#34;', $text);
    $text = preg_replace('!&amp;(([a-z]+|#\d+);)!', '&$1', $text);
    $text = preg_replace('!&quot;!', '"', $text);

    // These create markup with entities. Do first and 'save' result for later:
    // "text":url -> hyperlink
    // links with brackets surrounding
    $parenre = '\( (?: [^()] )* \)';
    $text = preg_replace_callback('{(
                                    [{[]
                                    (?:
                                        (?:"                                         # quote character
                                           (' . $this->clstyre . '*)?                # $2: optional CSS class/id
                                           ([^"]+?)                                  # $3: link text
                                           (?:\( ( (?:[^()]|' . $parenre . ')*) \))? # $4: optional link title
                                           "                                         # closing quote
                                        )
                                        |
                                        (?:\'                                        # open single quote
                                           (' . $this->clstyre . '*)?                # $5: optional CSS class/id
                                           ([^\']+?)                                 # $6: link text
                                           (?:\( ( (?:[^()]|' . $parenre . ')*) \))? # $7: optional link title
                                           \'                                        # closing quote
                                        )
                                    )
                                    :(.+?)                                           # $8: URL suffix
                                    [\]}]
                                   )
                                   }mx', $this->_cb('$me->_repl($me->repl[0], $me->format_link(array("text" => $m[1], "linktext" => $m[3] . $m[6], "title" => $me->encode_html_basic($m[4] . $m[7]), "url" => $m[8], "clsty" => $m[2] . $m[5])))'), $text);

    $text = preg_replace_callback('{((?:^|(?<=[\s>\(]))                              # $1: open brace/bracket
                                    (?: (?:"                                         # quote character "
                                           (' . $this->clstyre . '*)?                # $2: optional CSS class/id
                                           ([^"]+?)                                  # $3: link text "
                                           (?:\( ( (?:[^()]|' . $parenre . ')*) \))? # $4: optional link title
                                           "                                         # closing quote # "
                                        )
                                        |
                                        (?:\'                                        # open single quote \'
                                           (' . $this->clstyre . '*)?                # $5: optional CSS class/id
                                           ([^\']+?)                                 # $6: link text \'
                                           (?:\( ( (?:[^()]|' . $parenre . ')*) \))? # $7: optional link title
                                           \'                                        # closing quote \'
                                        )
                                    )
                                    :(\d+|' . $this->urlre . ')                      # $8: URL suffix
                                    (?:$|(?=' . $this->punct . '{1,2}|\s)))          # $9: closing brace/bracket
                                   }mx', $this->_cb('$me->_repl($me->repl[0], $me->format_link(array("text" => $m[1], "linktext" => $m[3] . $m[6], "title" => $me->encode_html_basic($m[4] . $m[7]), "url" => $m[8], "clsty" => $m[2] . $m[5])))'), $text);

    if (preg_match('/^xhtml2/', $this->flavor())) {
      // citation with cite link
      $text = preg_replace_callback('{(?:^|(?<=[\s>\'"\(])|([{[]))                   # $1: open brace/bracket \'
                                      \?\?                                           # opening \'??\'
                                      ([^\?]+?)                                      # $2: characters (can\'t contain \'?\')
                                      \?\?                                           # closing \'??\'
                                      :(\d+|' . $this->urlre . ')                    # $3: optional citation URL
                                      (?:$|([\]}])|(?=' . $this->punct . '{1,2}|\s)) # $4: closing brace/bracket
                                     }mx', $this->_cb('$me->_repl($me->repl[0], $me->format_cite(array("pre" => $m[1], "text" => $m[2], "cite" => $m[3], "post" => $m[4])))'), $text);
    }

    // footnotes
    if (preg_match('/[^ ]\[\d+\]/', $text)) {
      $fntag = '<sup';
      if ($this->options['css']['class_footnote']) { $fntag .= ' class="' . $this->options['css']['class_footnote'] . '"'; }
      $fntag .= '><a href="#' . ($this->options['css']['id_footnote_prefix'] ? $this->options['css']['id_footnote_prefix'] : 'fn');
      $text = preg_replace('|([^ ])\[(\d+)\]|', '$1' . $fntag . '$2">$2</a></sup>', $text);
    }

    // translate macros:
    $text = preg_replace_callback('{(\{)(.+?)(\})}x',
                                  $this->_cb('$me->format_macro(array("pre" => $m[1], "post" => $m[3], "macro" => $m[2]))'), $text);

    // these were present with textile 1 and are common enough
    // to not require macro braces...
    // (tm) -> &trade;
    $text = preg_replace('|[\(\[]TM[\)\]]|i', '&#8482;', $text);
    // (c) -> &copy;
    $text = preg_replace('|[\(\[]C[\)\]]|i', '&#169;', $text);
    // (r) -> &reg;
    $text = preg_replace('|[\(\[]R[\)\]]|i', '&#174;', $text);

    if ($this->preserve_spaces()) {
      // replace two spaces with an em space
      $text = preg_replace('/(?<!\s)\ \ (?!=\s)/', '&#8195;', $text);
    }

    $redo = preg_match('/[\*_\?\-\+\^\~]/', $text);
    $last = $text;
    while ($redo) {
      // simple replacements...
      $redo = 0;
      foreach ($qtags as $tag) {
        list ($this->tmp['f'][], $this->tmp['r'][], $qf, $cls) = $tag;
        if ($last != ($text = preg_replace_callback('{(?:^|(?<=[\s>\'"])|([{[]))                     # "\' $1 - pre
                                                      ' . $qf . '                                    #
                                                      (?:(' . $this->clstyre . '*))?                 # $2 - attributes
                                                      ([^' . $cls . '\s].*?)                         # $3 - content
                                                      (?<=\S)' . $qf . '                             #
                                                      (?:$|([\]}])|(?=' . $this->punct . '{1,2}|\s)) # $4 - post
                                                     }mx', $this->_cb('$me->format_tag(array("tag" => end($me->tmp["r"]), "marker" => end($me->tmp["f"]), "pre" => $m[1], "text" => $m[3], "clsty" => $m[2], "post" => $m[4]))'), $text))) {
          $redo = ($redo || ($last != $text));
          $last = $text;
        }
        array_pop($this->tmp['f']); array_pop($this->tmp['r']);
      }
    }

    // superscript is an even simpler replacement...
    $text = preg_replace('/(?<!\^)\^(?!\^)(.+?)(?<!\^)\^(?!\^)/', '<sup>$1</sup>', $text);

    // ABC(Aye Bee Cee) -> acronym
    $text = preg_replace_callback('{\b([A-Z][A-Za-z0-9]*?[A-Z0-9]+?)\b(?:[(]([^)]*)[)])}',
                                  $this->_cb('$me->_repl($me->repl[0],"<acronym title=\"" . $me->encode_html_basic($m[2]) . "\">$m[1]</acronym>")'), $text);

    // ABC -> 'capped' span
    if ($this->tmp['caps'][] = $this->options['css']['class_caps']) {
      $text = preg_replace_callback('/(^|[^"][>\s])  # "
                                      ((?:[A-Z](?:[A-Z0-9\.,\']|\&amp;){2,}\ *)+?) # \'
                                      (?=[^A-Z\.0-9]|$)
                                     /mx', $this->_cb('$m[1] . $me->_repl($me->repl[0], "<span class=\"" . end($me->tmp["caps"]) . "\">$m[2]</span>")'), $text);
    }
    array_pop($this->tmp['caps']);

    // nxn -> n&times;n
    $text = preg_replace('!((?:[0-9\.]0|[1-9]|\d[\'"])\ ?)x(\ ?\d)!', '$1&#215;$2', $text);

    // translate these entities to the Unicode equivalents:
    $text = preg_replace('/&#133;/', '&#8230;', $text);
    $text = preg_replace('/&#145;/', '&#8216;', $text);
    $text = preg_replace('/&#146;/', '&#8217;', $text);
    $text = preg_replace('/&#147;/', '&#8220;', $text);
    $text = preg_replace('/&#148;/', '&#8221;', $text);
    $text = preg_replace('/&#150;/', '&#8211;', $text);
    $text = preg_replace('/&#151;/', '&#8212;', $text);

    // Restore replacements done earlier:
    for ($i = count($this->repl[0]); $i > 0; $i--) {
      $text = preg_replace("|<textile#$i>|", str_replace('$', '\\$', $this->repl[0][$i - 1]), $text);
    }
    array_shift($this->repl);

    // translate entities to characters for highbit stuff since
    // we're using utf8
    // removed for backward compatability with older versions of Perl
    //if (preg_match('/^utf-?8$/i', $this->options['charset'])) {
    //    // translate any unicode entities to native UTF-8
    //    $text = preg_replace('/\&\#(\d+);/e', '($1 > 127) ? pack('U', $1) : chr($1)', $text);
    //}

    return $text;
  } // function format_inline

  /**
   * Responsible for processing a particular macro. Arguments passed
   * include:
   *
   * <ul>
   *
   * <li><b>pre</b>
   *
   * open brace character</li>
   *
   * <li><b>post</b>
   *
   * close brace character</li>
   *
   * <li><b>macro</b>
   *
   * the macro to be executed</li>
   *
   * </ul>
   *
   * The return value from this method would be the replacement
   * text for the macro given. If the macro is not defined, it will
   * return pre + macro + post, thereby preserving the original
   * macro string.
   *
   * @param $attrs An @c array containing the attributes for
   *        formatting the macro.
   *
   * @return A @c string containing the formatted macro.
   *
   * @private
   */
  function format_macro($attrs) {
    $macro = $attrs['macro'];
    if ($this->options['macros'][$macro]) {
      return $this->options['macros'][$macro];
    }

    return $attrs['pre'] . $macro . $attrs['post'];
  } // function format_macro

  /**
   * Processes text for a citation tag. The following attributes
   * are allowed:
   *
   * <ul>
   *
   * <li><b>pre</b>
   *
   * Any text that comes before the citation.</li>
   *
   * <li><b>text</b>
   *
   * The text that is being cited.</li>
   *
   * <li><b>cite</b>
   *
   * The URL of the citation.</li>
   *
   * <li><b>post</b>
   *
   * Any text that follows the citation.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the citation.
   *
   * @return A @c string containing the formatted citation.
   *
   * @private
   */
  function format_cite($args) {
    $pre = (isset($args['pre']) ? $args['pre'] : '');
    $text = (isset($args['text']) ? $args['text'] : '');
    $cite = $args['cite'];
    $post = (isset($args['post']) ? $args['post'] : '');
    $this->_strip_borders($pre, $post);
    $tag = $pre . '<cite';
    if (preg_match('/^xhtml2/', $this->flavor()) && $cite) {
      $cite = $this->format_url(array('url' => $cite));
      $tag .= " cite=\"$cite\"";
    } else {
      $post .= ':';
    }
    $tag .= '>';
    return $tag . $this->format_inline(array('text' => $text)) . '</cite>' . $post;
  } // function format_cite

  /**
   * Processes '@...@' type blocks (code snippets). The following
   * attributes are allowed:
   *
   * <ul>
   *
   * <li><b>text</b>
   *
   * The text of the code itself.</li>
   *
   * <li><b>lang</b>
   *
   * The language (programming language) for the code.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the code.
   *
   * @return A @c string containing the formatted code.
   *
   * @private
   */
  function format_code($args) {
    $code = (isset($args['text']) ? $args['text'] : '');
    $lang = $args['lang'];
    $code = $this->encode_html($code, 1);
    $code = preg_replace('/&lt;textile#(\d+)&gt;/', '<textile#$1>', $code);
    $tag = '<code';
    if ($lang) { $tag .= " language=\"$lang\""; }
    return $tag . '>' . $code . '</code>';
  } // function format_code

  /**
   * Returns a string of tag attributes to accomodate the class,
   * style and symbols present in @c $clsty.
   *
   * @c $clsty is checked for:
   *
   * <ul>
   *
   * <li><b><code>{...}</code></b>
   *
   * style rules. If present, they are appended to <code>$style</code>.</li>
   *
   * <li><b><code>(...#...)</code></b>
   *
   * class and/or ID name declaration</li>
   *
   * <li><b><code>(</code> (one or more)</b>
   *
   * pad left characters</li>
   *
   * <li><b><code>)</code> (one or more)</b>
   *
   * pad right characters</li>
   *
   * <li><b><code>[ll]</code></b>
   *
   * language declaration</li>
   *
   * </ul>
   *
   * The attribute string returned will contain any combination
   * of class, id, style and/or lang attributes.
   *
   * @param $clsty A @c string specifying the class/style to process.
   * @param $class A @c string specifying the predetermined class.
   * @param $style A @c string specifying the predetermined style.
   *
   * @return A @c string containing the formatted class, ID, style,
   *         and/or language.
   *
   * @private
   */
  function format_classstyle($clsty = NULL, $class = NULL, $style = NULL) {
    $class = preg_replace('/^ /', '', $class, 1);

    unset($lang, $padleft, $padright, $id);
    if ($clsty && preg_match('/{([^}]+)}/', $clsty, $matches)) {
      $_style = $matches[1];
      $_style = preg_replace('/\n/', ' ', $_style);
      $style .= ';' . $_style;
      $clsty = preg_replace('/{[^}]+}/', '', $clsty);
    }
    if ($clsty && (preg_match('/\(([A-Za-z0-9_\- ]+?)(?:#(.+?))?\)/', $clsty, $matches) ||
                   preg_match('/\(([A-Za-z0-9_\- ]+?)?(?:#(.+?))\)/', $clsty, $matches))) {
      if ($matches[1] || $matches[2]) {
        if ($class) {
          $class = $matches[1] . ' ' . $class;
        } else {
          $class = $matches[1];
        }
        $id = $matches[2];
        if ($class) {
          $clsty = preg_replace('/\([A-Za-z0-9_\- ]+?(#.*?)?\)/', '', $clsty);
        }
        if ($id) {
          $clsty = preg_replace('/\(#.+?\)/', '', $clsty);
        }
      }
    }
    if ($clsty && preg_match('/(\(+)/', $clsty, $matches)) {
      $padleft = strlen($matches[1]);
      $clsty = preg_replace('/\(+/', '', $clsty, 1);
    }
    if ($clsty && preg_match('/(\)+)/', $clsty, $matches)) {
      $padright = strlen($matches[1]);
      $clsty = preg_replace('/\)+/', '', $clsty, 1);
    }
    if ($clsty && preg_match('/\[(.+?)\]/', $clsty, $matches)) {
      $lang = $matches[1];
      $clsty = preg_replace('/\[.+?\]/', '', $clsty);
    }
    $attrs = '';
    if ($padleft) { $style .= ";padding-left:${padleft}em"; }
    if ($padright) { $style .= ";padding-right:${padright}em"; }
    $style = preg_replace('/^;/', '', $style, 1);
    $class = preg_replace('/^ /', '', $class, 1);
    $class = preg_replace('/ $/', '', $class, 1);
    if ($class) { $attrs .= " class=\"$class\""; }
    if ($id) { $attrs .= " id=\"$id\""; }
    if ($style) { $attrs .= " style=\"$style\""; }
    if ($lang) { $attrs .= " lang=\"$lang\""; }
    $attrs = preg_replace('/^ /', '', $attrs, 1);
    return $attrs;
  } // function format_classstyle

  /**
   * Constructs an HTML tag. Accepted arguments:
   *
   * <ul>
   *
   * <li><b>tag</b>
   *
   * the tag to produce</li>
   *
   * <li><b>text</b>
   *
   * the text to output inside the tag</li>
   *
   * <li><b>pre</b>
   *
   * text to produce before the tag</li>
   *
   * <li><b>post</b>
   *
   * text to produce following the tag</li>
   *
   * <li><b>clsty</b>
   *
   * class and/or style attributes that should be assigned to the tag.</li>
   *
   * </ul>
   *
   * @param $args @c array specifying the attributes for formatting
   *        the tag.
   *
   * @return A @c string containing the formatted tag.
   *
   * @private
   */
  function format_tag($args) {
    $tagname = $args['tag'];
    $text = (isset($args['text']) ? $args['text'] : '');
    $pre = (isset($args['pre']) ? $args['pre'] : '');
    $post = (isset($args['post']) ? $args['post'] : '');
    $clsty = (isset($args['clsty']) ? $args['clsty'] : '');
    $this->_strip_borders($pre, $post);
    $tag = "<$tagname";
    $attr = $this->format_classstyle($clsty);
    if ($attr) { $tag .= " $attr"; }
    $tag .= ">$text</$tagname>";
    return $pre . $tag . $post;
  } // function format_tag

  /**
   * Takes a Textile formatted definition list and
   * returns the markup for it. Arguments accepted:
   *
   * <ul>
   *
   * <li><b>text</b>
   *
   * The text to be processed.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the definition list.
   *
   * @return A @c string containing the formatted definition list.
   *
   * @private
   */
  function format_deflist($args) {
    $str = (isset($args['text']) ? $args['text'] : '');
    unset($clsty);
    $lines = preg_split('/\n/', $str);
    if (preg_match('{^(dl(' . $this->clstyre . '*?)\.\.?(?:\ +|$))}x', $lines[0], $matches)) {
      $clsty = $matches[2];
      $lines[0] = substr($lines[0], strlen($matches[1]));
    }

    unset($dt, $dd);
    $out = '';
    foreach ($lines as $line) {
      if (preg_match('{^((?:' . $this->clstyre . '*)(?:[^\ ].*?)(?<!["\'\ ])):([^\ \/].*)$}x', $line, $matches)) {
        if ($dt && $dd) { $out .= $this->add_term($dt, $dd); }
        $dt = $matches[1];
        $dd = $matches[2];
      } else {
        $dd .= "\n" . $line;
      }
    }
    if ($dt && $dd) { $out .= $this->add_term($dt, $dd); }

    $tag = '<dl';
    if ($clsty) { $attr = $this->format_classstyle($clsty); }
    if ($attr) { $tag .= " $attr"; }
    $tag .= '>' . "\n";

    return $tag . $out . "</dl>\n";
  } // function format_deflist

  /**
   * Processes a single definition list item from the provided term
   * and definition.
   *
   * @param $dt A @c string specifying the term to be defined.
   * @param $dd A @c string specifying the definition for the term.
   *
   * @return A @c string containing the formatted definition list
   *         item.
   *
   * @private
   */
  function add_term($dt, $dd) {
    unset($dtattr, $ddattr);
    unset($dtlang);
    if (preg_match('{^(' . $this->clstyre . '*)}x', $dt, $matches)) {
      $param = $matches[1];
      $dtattr = $this->format_classstyle($param);
      if (preg_match('/\[([A-Za-z]+?)\]/', $param, $matches)) {
        $dtlang = $matches[1];
      }
      $dt = substr($dt, strlen($param));
    }
    if (preg_match('{^(' . $this->clstyre . '*)}x', $dd, $matches)) {
      $param = $matches[1];
      // if the language was specified for the term,
      // then apply it to the definition as well (unless
      // already specified of course)
      if ($dtlang && preg_match('/\[([A-Za-z]+?)\]/', $param)) {
        unset($dtlang);
      }
      $ddattr = $this->format_classstyle(($dtlang ? "[$dtlang]" : '') . $param);
      $dd = substr($dd, strlen($param));
    }
    $out = '<dt';
    if ($dtattr) { $out .= " $dtattr"; }
    $out .= '>' . $this->format_inline(array('text' => $dt)) . '</dt>' . "\n";
    if (preg_match('/\n\n/', $dd)) {
      if (preg_match('/\n\n/', $dd)) { $dd = $this->process($dd); }
    } else {
      $dd = $this->format_paragraph(array('text' => $dd));
    }
    $out .= '<dd';
    if ($ddattr) { $out .= " $ddattr"; }
    $out .= '>' . $dd . '</dd>' . "\n";
    return $out;
  } // function add_term

  /**
   * Takes a Textile formatted list (numeric or bulleted) and
   * returns the markup for it. Text that is passed in requires
   * substantial parsing, so the @c format_list method is a little
   * involved. But it should always produce a proper ordered
   * or unordered list. If it cannot (due to misbalanced input),
   * it will return the original text. Arguments accepted:
   *
   * <ul>
   *
   * <li><b>text</b>
   *
   * The text to be processed.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the list.
   *
   * @return A @c string containing the formatted list.
   *
   * @private
   */
  function format_list($args) {
    $str = (isset($args['text']) ? $args['text'] : '');

    $list_tags = array('*' => 'ul', '#' => 'ol');

    $lines = preg_split('/\n/', $str);

    unset($stack);
    $last_depth = 0;
    $item = '';
    $out = '';
    foreach ($lines as $line) {
      if (preg_match('{^((?:' . $this->clstypadre . '*|' . $this->halignre . ')*)
                       ([\#\*]+)
                       ((?:' . $this->halignre . '|' . $this->clstypadre . '*)*)
                       \ (.+)$}x', $line, $matches)) {
        if ($item != '') {
          if (preg_match('/\n/', $item)) {
            if ($this->options['_line_open']) {
              $item = preg_replace('/(<li[^>]*>|^)/m', '$1' . $this->options['_line_open'], $item);
              $item = preg_replace('/(\n|$)/s', $this->options['_line_close'] . '$1', $item);
            } else {
              $item = preg_replace('/(\n)/s', $this->options['_line_close'] . '$1', $item);
            }
          }
          $out .= $item;
          $item = '';
        }
        $type = substr($matches[2], 0, 1);
        $depth = strlen($matches[2]);
        $blockparam = $matches[1];
        $itemparam = $matches[3];
        $line = $matches[4];
        unset ($blockclsty, $blockalign, $blockattr, $itemattr, $itemclsty,
               $itemalign);
        if (preg_match('{(' . $this->clstypadre . '+)}x', $blockparam, $matches)) {
          $blockclsty = $matches[1];
        }
        if (preg_match('{(' . $this->halignre . '+)}', $blockparam, $matches)) {
          $blockalign = $matches[1];
        }
        if (preg_match('{(' . $this->clstypadre . '+)}x', $itemparam, $matches)) {
          $itemclsty = $matches[1];
        }
        if (preg_match('{(' . $this->halignre . '+)}', $itemparam, $matches)) {
          $itemalign = $matches[1];
        }
        if ($itemclsty) { $itemattr = $this->format_classstyle($itemclsty); }
        if ($depth > $last_depth) {
          for ($j = $last_depth; $j < $depth; $j++) {
            $out .= "\n<$list_tags[$type]";
            $stack[] = $type;
            if ($blockclsty) {
              $blockattr = $this->format_classstyle($blockclsty);
              if ($blockattr) { $out .= ' ' . $blockattr; }
            }
            $out .= ">\n<li";
            if ($itemattr) { $out .= " $itemattr"; }
            $out .= ">";
          }
        } elseif ($depth < $last_depth) {
          for ($j = $depth; $j < $last_depth; $j++) {
            if ($j == $depth) { $out .= "</li>\n"; }
            $type = array_pop($stack);
            $out .= "</$list_tags[$type]>\n</li>\n";
          }
          if ($depth) {
            $out .= '<li';
            if ($itemattr) { $out .= " $itemattr"; }
            $out .= '>';
          }
        } else {
          $out .= "</li>\n<li";
          if ($itemattr) { $out .= " $itemattr"; }
          $out .= '>';
        }
        $last_depth = $depth;
      }
      if ($item != '') { $item .= "\n"; }
      $item .= $this->format_paragraph(array('text' => $line));
    }

    if (preg_match('/\n/', $item, $matches)) {
      if ($this->options['_line_open']) {
        $item = preg_replace('/(<li[^>]*>|^)/m', '$1' . $this->options['_line_open'], $item);
        $item = preg_replace('/(\n|$)/s', $this->options['_line_close'] . '$1', $item);
      } else {
        $item = preg_replace('/(\n)/s', $this->options['_line_close'] . '$1', $item);
      }
    }
    $out .= $item;

    for ($j = 1; $j <= $last_depth; $j++) {
      if ($j == 1) { $out .= '</li>'; }
      $type = array_pop($stack);
      $out .= "\n" . '</' . $list_tags[$type] . '>' . "\n";
      if ($j != $last_depth) { $out .= '</li>'; }
    }

    return $out . "\n";
  } // function format_list

  /**
   * Processes '==xxxxx==' type blocks for filters. A filter
   * would follow the open '==' sequence and is specified within
   * pipe characters, like so:
   * <pre>
   *     ==|filter|text to be filtered==
   * </pre>
   * You may specify multiple filters in the filter portion of
   * the string. Simply comma delimit the filters you desire
   * to execute. Filters are defined using the filters method.
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the block.
   *
   * @return A @c string containing the formatted block.
   *
   * @private
   */
  function format_block($args) {
    $str = (isset($args['text']) ? $args['text'] : '');
    $inline = $args['inline'];
    $pre = (isset($args['pre']) ? $args['pre'] : '');
    $post = (isset($args['post']) ? $args['post'] : '');
    $this->_strip_borders($pre, $post);
    $filters = (preg_match('/^(\|(?:(?:[a-z0-9_\-]+)\|)+)/', $str, $matches) ? $matches[1] : '');
    if ($filters) {
      $filtreg = preg_replace('/[^A-Za-z0-9]/', '\\\\$1', $filters);
      $str = preg_replace('/^' . $filtreg . '/', '', $str, 1);
      $filters = preg_replace('/^\|/', '', $filters, 1);
      $filters = preg_replace('/\|$/', '', $filter, 1);
      $filters = preg_split('/\|/', $filters);
      $str = $this->apply_filters(array('text' => $str, 'filters' => $filters));
      $count = count($filters);
      if ($str = preg_replace('!(<p>){' . $count . '}!se', '(++$i ? "$1" : "$1")', $str) && $i) {
        $str = preg_replace('!(</p>){' . $count . '}!s', '$1', $str);
        $str = preg_replace('!(<br( /)?>){' . $count . '}!s', '$1', $str);
      }
    }
    if ($inline) {
      // strip off opening para, closing para, since we're
      // operating within an inline block
      $str = preg_replace('/^\s*<p[^>]*>/', '', $str, 1);
      $str = preg_replace('/<\/p>\s*$/', '', $str, 1);
    }
    return $pre . $str . $post;
  } // function format_block

  /**
   * Takes the Textile link attributes and transforms them into
   * a hyperlink.
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the link.
   *
   * @return A @c string containing the formatted link.
   *
   * @private
   */
  function format_link($args) {
    $text = (isset($args['text']) ? $args['text'] : '');
    $linktext = (isset($args['linktext']) ? $args['linktext'] : '');
    $title = $args['title'];
    $url = $args['url'];
    $clsty = $args['clsty'];

    if (!$url || ($url == '')) {
      return $text;
    }
    if (isset($this->links) && isset($this->links[$url])) {
      $title = ($title ? $title : $this->links[$url]['title']);
      $url = $this->links[$url]['url'];
    }
    $linktext = preg_replace('/ +$/', '', $linktext, 1);
    $linktext = $this->format_paragraph(array('text' => $linktext));
    $url = $this->format_url(array('linktext' => $linktext, 'url' => $url));
    $tag = "<a href=\"$url\"";
    $attr = $this->format_classstyle($clsty);
    if ($attr) { $tag .= " $attr"; }
    if ($title) {
      $title = preg_replace('/^\s+/', '', $title, 1);
      if (strlen($title)) { $tag .= " title=\"$title\""; }
    }
    $tag .= ">$linktext</a>";
    return $tag;
  } // function format_link

  /**
   * Takes the given @c $url and transforms it appropriately.
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the url.
   *
   * @return A @c string containing the formatted url.
   *
   * @private
   */
  function format_url($args) {
    $url = ($args['url'] ? $args['url'] : '');
    if (preg_match('/^(mailto:)?([-\+\w]+@[-\w]+(\.\w[-\w]*)+)$/', $url, $matches)) {
      $url = 'mailto:' . $this->mail_encode($matches[2]);
    }
    if (!preg_match('!^(/|\./|\.\./|#)!', $url)) {
      if (!preg_match('!^(https?|ftp|mailto|nntp|telnet)!', $url)) { $url = "http://$url"; }
    }
    $url = preg_replace('/&(?!amp;)/', '&amp;', $url);
    $url = preg_replace('/\ /', '+', $url);
    $url = preg_replace_callback('/^((?:.+?)\?)(.+)$/', $this->_cb('$m[1] . $me->encode_url($m[2])'), $url);
    return $url;
  } // function format_url

  /**
   * Takes a Textile formatted span and returns the markup for it.
   *
   * @return A @c string containing the formatted span.
   *
   * @private
   */
  function format_span($args) {
    $text = (isset($args['text']) ? $args['text'] : '');
    $pre = (isset($args['pre']) ? $args['pre'] : '');
    $post = (isset($args['post']) ? $args['post'] : '');
    $align = $args['align'];
    $cite = (isset($args['cite']) ? $args['cite'] : '');
    $clsty = $args['clsty'];
    $this->_strip_borders($pre, $post);
    unset($class, $style);
    $tag  = "<span";
    $style = '';
    if ($align) {
      if ($self->options['css_mode']) {
        $alignment = $this->_halign($align);
        if ($alignment) { $style .= ";float:$alignment"; }
        if ($alignment) { $class .= ' ' . $this->options['css']["class_align_$alignment"]; }
      } else {
        $alignment = ($this->_halign($align) ? $this->_halign($align) : $this->_valign($align));
        if ($alignment) { $tag .= " align=\"$alignment\""; }
      }
    }
    $attr = $this->format_classstyle($clsty, $class, $style);
    if ($attr) { $tag .= " $attr"; }
    if ($cite) {
      $cite = preg_replace('/^:/', '', $cite, 1);
      $cite = $this->format_url(array('url' => $cite));
      $tag .= " cite=\"$cite\"";
    }
    return $pre . $tag . '>' . $this->format_paragraph(array('text' => $text)) . '</span>' . $post;
  } // function format_span

  /**
   * Returns markup for the given image. @c $src is the location of
   * the image, @c $extra contains the optional height/width and/or
   * alt text. @c $url is an optional hyperlink for the image. @c $class
   * holds the optional CSS class attribute.
   *
   * Arguments you may pass:
   *
   * <ul>
   *
   * <li><b>src</b>
   *
   * The 'src' (URL) for the image. This may be a local path,
   * ideally starting with a '/'. Images can be located within
   * the file system if the docroot method is used to specify
   * where the docroot resides. If the image can be found, the
   * image_size method is used to determine the dimensions of
   * the image.</li>
   *
   * <li><b>extra</b>
   *
   * Additional parameters for the image. This would include
   * alt text, height/width specification or scaling instructions.</li>
   *
   * <li><b>align</b>
   *
   * Alignment attribute.</li>
   *
   * <li><b>pre</b>
   *
   * Text to produce prior to the tag.</li>
   *
   * <li><b>post</b>
   *
   * Text to produce following the tag.</li>
   *
   * <li><b>link</b>
   *
   * Optional URL to connect with the image tag.</li>
   *
   * <li><b>clsty</b>
   *
   * Class and/or style attributes.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the image.
   *
   * @return A @c string containing the formatted image.
   *
   * @private
   */
  function format_image($args) {
    $src = (isset($args['src']) ? $args['src'] : '');
    $extra = $args['extra'];
    $align = $args['align'];
    $pre = (isset($args['pre']) ? $args['pre'] : '');
    $post = (isset($args['post']) ? $args['post'] : '');
    $link = $args['url'];
    $clsty = $args['clsty'];
    $this->_strip_borders($pre, $post);
    if (strlen($src) == 0) { return $pre . '!!' . $post; }
    unset($tag);
    if (preg_match('/^xhtml2/', $this->options['flavor'])) {
      unset($type); // poor man's mime typing. need to extend this externally
      if (preg_match('/(?:\.jpeg|\.jpg)$/i', $src)) {
        $type = 'image/jpeg';
      } elseif (preg_match('/\.gif$/i', $src)) {
        $type = 'image/gif';
      } elseif (preg_match('/\.png$/i', $src)) {
        $type = 'image/png';
      } elseif (preg_match('/\.tiff$/i', $src)) {
        $type = 'image/tiff';
      }
      $tag = "<object";
      if ($type) { $tag .= " type=\"$type\""; }
      $tag .= " data=\"$src\"";
    } else {
      $tag = "<img src=\"$src\"";
    }
    unset($class, $style);
    if ($align) {
      if ($this->options['css_mode']) {
        $alignment = $this->_halign($align);
        if ($alignment) { $style .= ";float:$alignment"; }
        if ($alignment) { $class .= ' ' . $alignment; }
        $alignment = $this->_valign($align);
        if ($alignment) {
          $imgvalign = (preg_match('/(top|bottom)/', $alignment) ? 'text-' . $alignment : $alignment);
          if ($imgvalign) { $style .= ";vertical-align:$imgvalign"; }
          if ($alignment) { $class .= ' ' . $this->options['css']["class_align_$alignment"]; }
        }
      } else {
        $alignment = ($this->_halign($align) ? $this->_halign($align) : $this->_valign($align));
        if ($alignment) { $tag .= " align=\"$alignment\""; }
      }
    }
    unset($pctw, $pcth, $w, $h, $alt);
    if ($extra) {
      $alt = (preg_match('/\(([^\)]+)\)/', $extra, $matches) ? $matches[1] : '');
      $extra = preg_replace('/\([^\)]+\)/', '', $extra, 1);
      $pct = (preg_match('/(^|\s)(\d+)%(\s|$)/', $extra, $matches) ? $matches[2] : '');
      if (!$pct) {
        list($pctw, $pcth) = (preg_match('/(^|\s)(\d+)%x(\d+)%(\s|$)/', $extra, $matches) ? array($matches[2], $matches[3]) : NULL);
      } else {
        $pctw = $pcth = $pct;
      }
      if (!$pctw && !$pcth) {
        list($w,$h) = (preg_match('/(^|\s)(\d+|\*)x(\d+|\*)(\s|$)/', $extra, $matches) ? array($matches[2], $matches[3]) : NULL);
        if ($w == '*') { $w = ''; }
        if ($h == '*') { $h = ''; }
        if (!$w) {
          $w = (preg_match('/(^|[,\s])(\d+)w([\s,]|$)/', $extra, $matches) ? $matches[2] : '');
        }
        if (!$h) {
          $h = (preg_match('/(^|[,\s])(\d+)h([\s,]|$)/', $extra, $matches) ? $matches[2] : '');
        }
      }
    }
    $alt = ($alt ? $alt : '');
    if (!preg_match('/^xhtml2/', $this->options['flavor'])) {
      $tag .= ' alt="' . $this->encode_html_basic($alt) . '"';
    }
    if ($w && $h) {
      if (!preg_match('/^xhtml2/', $this->options['flavor'])) {
        $tag .= " height=\"$h\" width=\"$w\"";
      } else {
        $style .= ";height:${h}px;width:${w}px";
      }
    } else {
      list($image_w, $image_h) = $this->image_size($src);
      if (($image_w && $image_h) && ($w || $h)) {
        // image size determined, but only width or height specified
        if ($w && !$h) {
          // width defined, scale down height proportionately
          $h = intval($image_h * ($w / $image_w));
        } elseif ($h && !$w) {
          $w = intval($image_w * ($h / $image_h));
        }
      } else {
        $w = $image_w;
        $h = $image_h;
      }
      if ($w && $h) {
        if ($pctw || $pcth) {
          $w = intval($w * $pctw / 100);
          $h = intval($h * $pcth / 100);
        }
        if (!preg_match('/^xhtml2/', $this->options['flavor'])) {
          $tag .= " height=\"$h\" width=\"$w\"";
        } else {
          $style .= ";height:{$h}px;width:{$w}px";
        }
      }
    }
    $attr = $this->format_classstyle($clsty, $class, $style);
    if ($attr) { $tag .= " $attr"; }
    if (preg_match('/^xhtml2/', $this->options['flavor'])) {
      $tag .= '><p>' . $this->encode_html_basic($alt) . '</p></object>';
    } elseif (preg_match('/^xhtml/', $this->options['flavor'])) {
      $tag .= ' />';
    } else {
      $tag .= '>';
    }
    if ($link) {
      $link = preg_replace('/^:/', '', $link, 1);
      $link = $this->format_url(array('url' => $link));
      $tag = '<a href="' . $link . '">' . $tag . '</a>';
    }
    return $pre . $tag . $post;
  } // function format_image

  /**
   * Takes a Wiki-ish string of data and transforms it into a full
   * table.
   *
   * @param $args An @c array specifying the attributes for formatting
   *        the table.
   *
   * @return A @c string containing the formatted table.
   *
   * @private
   */
  function format_table($args) {
    $str = (isset($args['text']) ? $args['text'] : '');

    $lines = preg_split('/\n/', $str);
    unset($rows);
    $line_count = count($lines);
    for ($i = 0; $i < $line_count; $i++) {
      if (!preg_match('/\|\s*$/', $lines[$i])) {
        if ($i + 1 < $line_count) {
          if ($i + 1 <= count($lines) - 1) { $lines[$i + 1] = $lines[$i] . "\n" . $lines[$i + 1]; }
        } else {
          $rows[] = $lines[$i];
        }
      } else {
        $rows[] = $lines[$i];
      }
    }
    unset($tid, $tpadl, $tpadr, $tlang);
    $tclass = '';
    $tstyle = '';
    $talign = '';
    if (preg_match('/^table[^\.]/', $rows[0])) {
      $row = $rows[0];
      $row = preg_replace('/^table/', '', $row, 1);
      $params = 1;
      // process row parameters until none are left
      while ($params) {
        if (preg_match('{^(' . $this->tblalignre . ')}', $row, $matches)) {
          // found row alignment
          $talign .= $matches[1];
          if ($matches[1]) { $row = substr($row, strlen($matches[1])); }
          if ($matches[1]) { continue; }
        }
        if (preg_match('{^(' . $this->clstypadre . ')}x', $row, $matches)) {
          // found a class/id/style/padding indicator
          $clsty = $matches[1];
          if ($clsty) { $row = substr($row, strlen($clsty)); }
          if (preg_match('/{([^}]+)}/', $clsty, $matches)) {
            $tstyle = $matches[1];
            $clsty = preg_replace('/{([^}]+)}/', '', $clsty, 1);
            if ($tstyle) { continue; }
          }
          if (preg_match('/\(([A-Za-z0-9_\- ]+?)(?:#(.+?))?\)/', $clsty, $matches) ||
              preg_match('/\(([A-Za-z0-9_\- ]+?)?(?:#(.+?))\)/', $clsty, $matches)) {
            if ($matches[1] || $matches[2]) {
              $tclass = $matches[1];
              $tid = $matches[2];
              continue;
            }
          }
          if (preg_match('/(\(+)/', $clsty, $matches)) { $tpadl = strlen($matches[1]); }
          if (preg_match('/(\)+)/', $clsty, $matches)) { $tpadr = strlen($matches[1]); }
          if (preg_match('/\[(.+?)\]/', $clsty, $matches)) { $tlang = $matches[1]; }
          if ($clsty) { continue; }
        }
        $params = 0;
      }
      $row = preg_replace('/\.\s+/', '', $row, 1);
      $rows[0] = $row;
    }
    $out = '';
    $cols = preg_split('/\|/', $rows[0] . ' ');
    unset($colaligns, $rowspans);
    foreach ($rows as $row) {
      $cols = preg_split('/\|/', $row . ' ');
      $colcount = count($cols) - 1;
      array_pop($cols);
      $colspan = 0;
      $row_out = '';
      unset($rowclass, $rowid, $rowalign, $rowstyle, $rowheader);
      if (!$cols[0]) { $cols[0] = ''; }
      if (preg_match('/_/', $cols[0])) {
        $cols[0] = preg_replace('/_/', '', $cols[0]);
        $rowheader = 1;
      }
      if (preg_match('/{([^}]+)}/', $cols[0], $matches)) {
        $rowstyle = $matches[1];
        $cols[0] = preg_replace('/{[^}]+}/', '', $cols[0]);
      }
      if (preg_match('/\(([^\#]+?)?(#(.+))?\)/', $cols[0], $matches)) {
        $rowclass = $matches[1];
        $rowid = $matches[3];
        $cols[0] = preg_replace('/\([^\)]+\)/', '', $cols[0]);
      }
      if (preg_match('{(' . $this->alignre . ')}', $cols[0], $matches)) { $rowalign = $matches[1]; }
      for ($c = $colcount - 1; $c > 0; $c--) {
        if ($rowspans[$c]) {
          $rowspans[$c]--;
          if ($rowspans[$c] > 1) { continue; }
        }
        unset($colclass, $colid, $header, $colparams, $colpadl, $colpadr, $collang);
        $colstyle = '';
        $colalign = $colaligns[$c];
        $col = array_pop($cols);
        $col = ($col ? $col : '');
        $attrs = '';
        if (preg_match('{^(((_|[/\\\\]\d+|' . $this->alignre . '|' . $this->clstypadre . ')+)\.\ )}x', $col, $matches)) {
          $colparams = $matches[2];
          $col = substr($col, strlen($matches[1]));
          $params = 1;
          // keep processing column parameters until there
          // are none left...
          while ($params) {
            if (preg_match('{^(_|' . $this->alignre . ')(.*)$}', $colparams, $matches)) {
              // found alignment or heading indicator
              $attrs .= $matches[1];
              if ($matches[1]) { $colparams = $matches[2]; }
              if ($matches[1]) { continue; }
            }
            if (preg_match('{^(' . $this->clstypadre . ')(.*)$}x', $colparams, $matches)) {
              // found a class/id/style/padding marker
              $clsty = $matches[1];
              if ($clsty) { $colparams = $matches[2]; }
              if (preg_match('/{([^}]+)}/', $clsty, $matches)) {
                $colstyle = $matches[1];
                $clsty = preg_replace('/{([^}]+)}/', '', $clsty, 1);
              }
              if (preg_match('/\(([A-Za-z0-9_\- ]+?)(?:#(.+?))?\)/', $clsty, $matches) ||
                  preg_match('/\(([A-Za-z0-9_\- ]+?)?(?:#(.+?))\)/', $clsty, $matches)) {
                if ($matches[1] || $matches[2]) {
                  $colclass = $matches[1];
                  $colid = $matches[2];
                  if ($colclass) {
                    $clsty = preg_replace('/\([A-Za-z0-9_\- ]+?(#.*?)?\)/', '', $clsty);
                  } elseif ($colid) {
                    $clsty = preg_replace('/\(#.+?\)/', '', $clsty);
                  }
                }
              }
              if (preg_match('/(\(+)/', $clsty, $matches)) {
                $colpadl = strlen($matches[1]);
                $clsty = preg_replace('/\(+/', '', $clsty, 1);
              }
              if (preg_match('/(\)+)/', $clsty, $matches)) {
                $colpadr = strlen($matches[1]);
                $clsty = preg_replace('/\)+/', '', $clsty, 1);
              }
              if (preg_match('/\[(.+?)\]/', $clsty, $matches)) {
                $collang = $matches[1];
                $clsty = preg_replace('/\[.+?\]/', '', $clsty, 1);
              }
              if ($clsty) { continue; }
            }
            if (preg_match('/^\\\\(\d+)/', $colparams, $matches)) {
              $colspan = $matches[1];
              $colparams = substr($colparams, strlen($matches[1]) + 1);
              if ($matches[1]) { continue; }
            }
            if (preg_match('/\/(\d+)/', $colparams, $matches)) {
              if ($matches[1]) { $rowspans[$c] = $matches[1]; }
              $colparams = substr($colparams, strlen($matches[1]) + 1);
              if ($matches[1]) { continue; }
            }
            $params = 0;
          }
        }
        if (strlen($attrs)) {
          if (preg_match('/_/', $attrs)) { $header = 1; }
          if (preg_match('{(' . $this->alignre . ')}', $attrs, $matches) && strlen($matches[1])) { $colalign = ''; }
          // determine column alignment
          if (preg_match('/<>/', $attrs)) {
            $colalign .= '<>';
          } elseif (preg_match('/</', $attrs)) {
            $colalign .= '<';
          } elseif (preg_match('/=/', $attrs)) {
            $colalign = '=';
          } elseif (preg_match('/>/', $attrs)) {
            $colalign = '>';
          }
          if (preg_match('/\^/', $attrs)) {
            $colalign .= '^';
          } elseif (preg_match('/~/', $attrs)) {
            $colalign .= '~';
          } elseif (preg_match('/-/', $attrs)) {
            $colalign .= '-';
          }
        }
        if ($rowheader) { $header = 1; }
        if ($header) { $colaligns[$c] = $colalign; }
        $col = preg_replace('/^ +/', '', $col, 1); $col = preg_replace('/ +$/', '', $col, 1);
        if (strlen($col)) {
          // create one cell tag
          $rowspan = ($rowspans[$c] ? $rowspans[$c] : 0);
          $col_out = '<' . ($header ? 'th' : 'td');
          if ($colalign) {
            // horizontal, vertical alignment
            $halign = $this->_halign($colalign);
            if ($halign) { $col_out .= " align=\"$halign\""; }
            $valign = $this->_valign($colalign);
            if ($valign) { $col_out .= " valign=\"$valign\""; }
          }
          // apply css attributes, row, column spans
          if ($colpadl) { $colstyle .= ";padding-left:${colpadl}em"; }
          if ($colpadr) { $colstyle .= ";padding-right:${colpadr}em"; }
          if ($colclass) { $col_out .= " class=\"$colclass\""; }
          if ($colid) { $col_out .= " id=\"$colid\""; }
          if ($colstyle) { $colstyle = preg_replace('/^;/', '', $colstyle, 1); }
          if ($colstyle) { $col_out .= " style=\"$colstyle\""; }
          if ($collang) { $col_out .= " lang=\"$collang\""; }
          if ($colspan > 1) { $col_out .= " colspan=\"$colspan\""; }
          if ($rowspan > 1) { $col_out .= " rowspan=\"$rowspan\""; }
          $col_out .= '>';
          // if the content of this cell has newlines OR matches
          // our paragraph block signature, process it as a full-blown
          // textile document
          if (preg_match('/\n\n/', $col) ||
              preg_match('{^(?:' . $this->halignre . '|' . $this->clstypadre . '*)*
                            [\*\#]
                            (?:' . $this->clstypadre . '*|' . $this->halignre . ')*\ }x', $col)) {
            $col_out .= $this->process($col);
          } else {
            $col_out .= $this->format_paragraph(array('text' => $col));
          }
          $col_out .= '</' . ($header ? 'th' : 'td') . '>';
          $row_out = $col_out . $row_out;
          if ($colspan) { $colspan = 0; }
        } else {
          if ($colspan == 0) { $colspan = 1; }
          $colspan++;
        }
      }
      if ($colspan > 1) {
        // handle the spanned column if we came up short
        $colspan--;
        $row_out = "<td"
                 . ($colspan > 1 ? " colspan=\"$colspan\"" : '')
                 . "></td>$row_out";
      }

      // build one table row
      $out .= "<tr";
      if ($rowalign) {
        $valign = $this->_valign($rowalign);
        if ($valign) { $out .= " valign=\"$valign\""; }
      }
      if ($rowclass) { $out .= " class=\"$rowclass\""; }
      if ($rowid) { $out .= " id=\"$rowid\""; }
      if ($rowstyle) { $out .= " style=\"$rowstyle\""; }
      $out .= ">$row_out</tr>";
    }

    // now, form the table tag itself
    $table = '';
    $table .= "<table";
    if ($talign) {
      if ($this->options['css_mode']) {
        // horizontal alignment
        $alignment = $this->_halign($talign);
        if ($talign == '=') {
          $tstyle .= ';margin-left:auto;margin-right:auto';
        } else {
          if ($alignment) { $tstyle .= ';float:' . $alignment; }
        }
        if ($alignment) { $tclass .= ' ' . $alignment; }
      } else {
        $alignment = $this->_halign($talign);
        if ($alignment) { $table .= " align=\"$alignment\""; }
      }
    }
    if ($tpadl) { $tstyle .= ";padding-left:${tpadl}em"; }
    if ($tpadr) { $tstyle .= ";padding-right:${tpadr}em"; }
    if ($tclass) { $tclass = preg_replace('/^ /', '', $tclass, 1); }
    if ($tclass) { $table .= " class=\"$tclass\""; }
    if ($tid) { $table .= " id=\"$tid\""; }
    if ($tstyle) { $tstyle = preg_replace('/^;/', '', $tstyle, 1); }
    if ($tstyle) { $table .= " style=\"$tstyle\""; }
    if ($tlang) { $table .= " lang=\"$tlang\""; }
    if ($tclass || $tid || $tstyle) { $table .= " cellspacing=\"0\""; }
    $table .= ">$out</table>";

    if (preg_match('|<tr></tr>|', $table)) {
      // exception -- something isn't right so return fail case
      return NULL;
    }

    return $table;
  } // function format_table

  /**
   * The following attributes are allowed:
   *
   * <ul>
   *
   * <li><b>text</b>
   *
   * The text to be processed.</li>
   *
   * <li><b>filters</b>
   *
   * An array reference of filter names to run for the given text.</li>
   *
   * </ul>
   *
   * @param $args An @c array specifying the text and filters to
   *        apply.
   *
   * @return A @c string containing the filtered text.
   *
   * @private
   */
  function apply_filters($args) {
    $text = $args['text'];
    if (!$text) { return ''; }
    $list = $args['filters'];
    $filters = $this->options['filters'];
    if (!is_array($filters)) { return $text; }

    $param = $this->filter_param();
    foreach ($list as $filter) {
      if (!isset($filters[$filter])) { continue; }
      if (is_string($filters[$filter])) {
        $text = (($f = create_function('$text, $param', $filters[$filter])) ? $f($text, $param) : $text);
      }
    }
    return $text;
  } // function apply_filters

  // minor utility / formatting routines

  var $Have_Entities = 1;

  /**
   * Encodes input @c $html string, escaping characters as needed
   * to HTML entities. This relies on the @c htmlentities function
   * for full effect. If unavailable, @c encode_html_basic is used
   * as a fallback technique. If the "char_encoding" flag is
   * set to false, @c encode_html_basic is used exclusively.
   *
   * @param $html A @c string specifying the HTML to be encoded.
   * @param $can_double_encode If provided, a @c bool indicating
   *        whether or not ampersand characters should be
   *        unconditionally encoded.
   *
   * @return A @c string containing the encoded HTML.
   *
   * @private
   */
  function encode_html($html, $can_double_encode = FALSE) {
    if (!$html) { return ''; }
    if ($this->Have_Entities && $this->options['char_encoding']) {
      $html = htmlentities($html, ENT_QUOTES, $this->options['char_encoding']);
    } else {
      $html = $this->encode_html_basic($html, $can_double_encode);
    }
    return $html;
  } // function encode_html

  /**
   * Decodes HTML entities in @c $html to their natural character
   * equivalents.
   *
   * @param $html A @c string specifying the HTML to be decoded.
   *
   * @return A @c string containing the decode HTML
   *
   * @private
   */
  function decode_html($html) {
    $html = preg_replace('!&quot;!', '"', $html);
    $html = preg_replace('!&amp;!', '&', $html);
    $html = preg_replace('!&lt;!', '<', $html);
    $html = preg_replace('!&gt;!', '>', $html);
    return $html;
  } // function decode_html

  /**
   * Encodes the input @c $html string for the following characters:
   * \<, \>, & and ". If @c $can_double_encode is true, all
   * ampersand characters are escaped even if they already were.
   * If @c $can_double_encode is false, ampersands are only escaped
   * when they aren't part of a HTML entity already.
   *
   * @param $html A @c string specifying the HTML to be encoded.
   * @param $can_double_encode If provided, a @c bool indicating
   *        whether or not ampersand characters should be
   * unconditionally encoded.
   *
   * @return A @c string containing the encoded HTML.
   *
   * @private
   */
  function encode_html_basic($html, $can_double_encode = FALSE) {
    if (!$html) { return ''; }
    if (!preg_match('/[^\w\s]/', $html)) { return $html; }
    if ($can_double_encode) {
      $html = preg_replace('!&!', '&amp;', $html);
    } else {
      // Encode any & not followed by something that looks like
      // an entity, numeric or otherwise.
      $html = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w{1,8});)/', '&amp;', $html);
    }
    $html = preg_replace('!"!', '&quot;', $html);
    $html = preg_replace('!<!', '&lt;', $html);
    $html = preg_replace('!>!', '&gt;', $html);
    return $html;
  } // function encode_html_basic

  /**
   * Returns the size for the image identified in @c $file. This
   * method relies upon the @c getimagesize function. If unavailable,
   * @c image_size will return @c NULL. Otherwise, the expected return
   * value is an array of the width and height (in that order), in
   * pixels.
   *
   * @param $file A @c string specifying the path or URL for the image
   *        file.
   *
   * @return An @c array containing the width and height
   *         (respectively) of the image.
   *
   * @private
   */
  function image_size($file) {
    $Have_ImageSize = function_exists('getimagesize');

    if ($Have_ImageSize) {
      if (file_exists($file)) {
        return @getimagesize($file);
      } else {
        if ($docroot = ($this->docroot() ? $this->docroot() : $_SERVER['DOCUMENT_ROOT'])) {
          $fullpath = $docroot . preg_replace('|^/*(.*)$|', '/$1', $file);
          if (file_exists($fullpath)) {
            return @getimagesize($fullpath);
          }
        }
      }
    }
    return @getimagesize($file);
  } // function image_size

  /**
   * Encodes the query portion of a URL, escaping characters
   * as necessary.
   *
   * @param $str A @c string specifying the URL to be encoded.
   *
   * @return A @c string containing the encoded URL.
   *
   * @private
   */
  function encode_url($str) {
    $str = preg_replace_callback('!([^A-Za-z0-9_\.\-\+\&=%;])!x',
                            $this->_cb('ord($m[1]) > 255 ? \'%u\' . sprintf("%04X", ord($m[1]))
                                                       : \'%\'  . sprintf("%02X", ord($m[1]))'), $str);
    return $str;
  } // function encode_url

  /**
   * Encodes the email address in @c $addr for 'mailto:' links.
   *
   * @param $addr A @c string specifying the email address to encode.
   *
   * @return A @c string containing the encoded email address.
   *
   * @private
   */
  function mail_encode($addr) {
    // granted, this is simple, but it gives off warm fuzzies
    $addr = preg_replace_callback('!([^\$])!x',
                             $this->_cb('ord($m[1]) > 255 ? \'%u\' . sprintf("%04X", ord($m[1]))
                                                        : \'%\'  . sprintf("%02X", ord($m[1]))'), $addr);
    return $addr;
  } // function mail_encode

  /**
   * Processes string, formatting plain quotes into curly quotes.
   *
   * @param $str A @c string specifying the text to process.
   *
   * @return A @c string containing the processed text.
   *
   * @private
   */
  function process_quotes($str) {
    // stub routine for now. subclass and implement.
    return $str;
  } // function process_quotes

  // a default set of macros for the {...} macro syntax
  // just a handy way to write a lot of the international characters
  // and some commonly used symbols

  /**
   * Returns an associative @c array of macros that are assigned to be processed by
   * default within the @c format_inline method.
   *
   * @return An @c array containing the default macros.
   *
   * @private
   */
  function default_macros() {
    // <, >, " must be html entities in the macro text since
    // those values are escaped by the time they are processed
    // for macros.
    return array(
      'c|' => '&#162;', // CENT SIGN
      '|c' => '&#162;', // CENT SIGN
      'L-' => '&#163;', // POUND SIGN
      '-L' => '&#163;', // POUND SIGN
      'Y=' => '&#165;', // YEN SIGN
      '=Y' => '&#165;', // YEN SIGN
      '(c)' => '&#169;', // COPYRIGHT SIGN
      '&lt;&lt;' => '&#171;', // LEFT-POINTING DOUBLE ANGLE QUOTATION
      '(r)' => '&#174;', // REGISTERED SIGN
      '+_' => '&#177;', // PLUS-MINUS SIGN
      '_+' => '&#177;', // PLUS-MINUS SIGN
      '&gt;&gt;' => '&#187;', // RIGHT-POINTING DOUBLE ANGLE QUOTATION
      '1/4' => '&#188;', // VULGAR FRACTION ONE QUARTER
      '1/2' => '&#189;', // VULGAR FRACTION ONE HALF
      '3/4' => '&#190;', // VULGAR FRACTION THREE QUARTERS
      'A`' => '&#192;', // LATIN CAPITAL LETTER A WITH GRAVE
      '`A' => '&#192;', // LATIN CAPITAL LETTER A WITH GRAVE
      'A\'' => '&#193;', // LATIN CAPITAL LETTER A WITH ACUTE
      '\'A' => '&#193;', // LATIN CAPITAL LETTER A WITH ACUTE
      'A^' => '&#194;', // LATIN CAPITAL LETTER A WITH CIRCUMFLEX
      '^A' => '&#194;', // LATIN CAPITAL LETTER A WITH CIRCUMFLEX
      'A~' => '&#195;', // LATIN CAPITAL LETTER A WITH TILDE
      '~A' => '&#195;', // LATIN CAPITAL LETTER A WITH TILDE
      'A"' => '&#196;', // LATIN CAPITAL LETTER A WITH DIAERESIS
      '"A' => '&#196;', // LATIN CAPITAL LETTER A WITH DIAERESIS
      'Ao' => '&#197;', // LATIN CAPITAL LETTER A WITH RING ABOVE
      'oA' => '&#197;', // LATIN CAPITAL LETTER A WITH RING ABOVE
      'AE' => '&#198;', // LATIN CAPITAL LETTER AE
      'C,' => '&#199;', // LATIN CAPITAL LETTER C WITH CEDILLA
      ',C' => '&#199;', // LATIN CAPITAL LETTER C WITH CEDILLA
      'E`' => '&#200;', // LATIN CAPITAL LETTER E WITH GRAVE
      '`E' => '&#200;', // LATIN CAPITAL LETTER E WITH GRAVE
      'E\'' => '&#201;', // LATIN CAPITAL LETTER E WITH ACUTE
      '\'E' => '&#201;', // LATIN CAPITAL LETTER E WITH ACUTE
      'E^' => '&#202;', // LATIN CAPITAL LETTER E WITH CIRCUMFLEX
      '^E' => '&#202;', // LATIN CAPITAL LETTER E WITH CIRCUMFLEX
      'E"' => '&#203;', // LATIN CAPITAL LETTER E WITH DIAERESIS
      '"E' => '&#203;', // LATIN CAPITAL LETTER E WITH DIAERESIS
      'I`' => '&#204;', // LATIN CAPITAL LETTER I WITH GRAVE
      '`I' => '&#204;', // LATIN CAPITAL LETTER I WITH GRAVE
      'I\'' => '&#205;', // LATIN CAPITAL LETTER I WITH ACUTE
      '\'I' => '&#205;', // LATIN CAPITAL LETTER I WITH ACUTE
      'I^' => '&#206;', // LATIN CAPITAL LETTER I WITH CIRCUMFLEX
      '^I' => '&#206;', // LATIN CAPITAL LETTER I WITH CIRCUMFLEX
      'I"' => '&#207;', // LATIN CAPITAL LETTER I WITH DIAERESIS
      '"I' => '&#207;', // LATIN CAPITAL LETTER I WITH DIAERESIS
      'D-' => '&#208;', // LATIN CAPITAL LETTER ETH
      '-D' => '&#208;', // LATIN CAPITAL LETTER ETH
      'N~' => '&#209;', // LATIN CAPITAL LETTER N WITH TILDE
      '~N' => '&#209;', // LATIN CAPITAL LETTER N WITH TILDE
      'O`' => '&#210;', // LATIN CAPITAL LETTER O WITH GRAVE
      '`O' => '&#210;', // LATIN CAPITAL LETTER O WITH GRAVE
      'O\'' => '&#211;', // LATIN CAPITAL LETTER O WITH ACUTE
      '\'O' => '&#211;', // LATIN CAPITAL LETTER O WITH ACUTE
      'O^' => '&#212;', // LATIN CAPITAL LETTER O WITH CIRCUMFLEX
      '^O' => '&#212;', // LATIN CAPITAL LETTER O WITH CIRCUMFLEX
      'O~' => '&#213;', // LATIN CAPITAL LETTER O WITH TILDE
      '~O' => '&#213;', // LATIN CAPITAL LETTER O WITH TILDE
      'O"' => '&#214;', // LATIN CAPITAL LETTER O WITH DIAERESIS
      '"O' => '&#214;', // LATIN CAPITAL LETTER O WITH DIAERESIS
      'O/' => '&#216;', // LATIN CAPITAL LETTER O WITH STROKE
      '/O' => '&#216;', // LATIN CAPITAL LETTER O WITH STROKE
      'U`' =>  '&#217;', // LATIN CAPITAL LETTER U WITH GRAVE
      '`U' =>  '&#217;', // LATIN CAPITAL LETTER U WITH GRAVE
      'U\'' => '&#218;', // LATIN CAPITAL LETTER U WITH ACUTE
      '\'U' => '&#218;', // LATIN CAPITAL LETTER U WITH ACUTE
      'U^' => '&#219;', // LATIN CAPITAL LETTER U WITH CIRCUMFLEX
      '^U' => '&#219;', // LATIN CAPITAL LETTER U WITH CIRCUMFLEX
      'U"' => '&#220;', // LATIN CAPITAL LETTER U WITH DIAERESIS
      '"U' => '&#220;', // LATIN CAPITAL LETTER U WITH DIAERESIS
      'Y\'' => '&#221;', // LATIN CAPITAL LETTER Y WITH ACUTE
      '\'Y' => '&#221;', // LATIN CAPITAL LETTER Y WITH ACUTE
      'a`' => '&#224;', // LATIN SMALL LETTER A WITH GRAVE
      '`a' => '&#224;', // LATIN SMALL LETTER A WITH GRAVE
      'a\'' => '&#225;', // LATIN SMALL LETTER A WITH ACUTE
      '\'a' => '&#225;', // LATIN SMALL LETTER A WITH ACUTE
      'a^' => '&#226;', // LATIN SMALL LETTER A WITH CIRCUMFLEX
      '^a' => '&#226;', // LATIN SMALL LETTER A WITH CIRCUMFLEX
      'a~' => '&#227;', // LATIN SMALL LETTER A WITH TILDE
      '~a' => '&#227;', // LATIN SMALL LETTER A WITH TILDE
      'a"' => '&#228;', // LATIN SMALL LETTER A WITH DIAERESIS
      '"a' => '&#228;', // LATIN SMALL LETTER A WITH DIAERESIS
      'ao' => '&#229;', // LATIN SMALL LETTER A WITH RING ABOVE
      'oa' => '&#229;', // LATIN SMALL LETTER A WITH RING ABOVE
      'ae' => '&#230;', // LATIN SMALL LETTER AE
      'c,' => '&#231;', // LATIN SMALL LETTER C WITH CEDILLA
      ',c' => '&#231;', // LATIN SMALL LETTER C WITH CEDILLA
      'e`' => '&#232;', // LATIN SMALL LETTER E WITH GRAVE
      '`e' => '&#232;', // LATIN SMALL LETTER E WITH GRAVE
      'e\'' => '&#233;', // LATIN SMALL LETTER E WITH ACUTE
      '\'e' => '&#233;', // LATIN SMALL LETTER E WITH ACUTE
      'e^' => '&#234;', // LATIN SMALL LETTER E WITH CIRCUMFLEX
      '^e' => '&#234;', // LATIN SMALL LETTER E WITH CIRCUMFLEX
      'e"' => '&#235;', // LATIN SMALL LETTER E WITH DIAERESIS
      '"e' => '&#235;', // LATIN SMALL LETTER E WITH DIAERESIS
      'i`' => '&#236;', // LATIN SMALL LETTER I WITH GRAVE
      '`i' => '&#236;', // LATIN SMALL LETTER I WITH GRAVE
      'i\'' => '&#237;', // LATIN SMALL LETTER I WITH ACUTE
      '\'i' => '&#237;', // LATIN SMALL LETTER I WITH ACUTE
      'i^' => '&#238;', // LATIN SMALL LETTER I WITH CIRCUMFLEX
      '^i' => '&#238;', // LATIN SMALL LETTER I WITH CIRCUMFLEX
      'i"' => '&#239;', // LATIN SMALL LETTER I WITH DIAERESIS
      '"i' => '&#239;', // LATIN SMALL LETTER I WITH DIAERESIS
      'n~' => '&#241;', // LATIN SMALL LETTER N WITH TILDE
      '~n' => '&#241;', // LATIN SMALL LETTER N WITH TILDE
      'o`' => '&#242;', // LATIN SMALL LETTER O WITH GRAVE
      '`o' => '&#242;', // LATIN SMALL LETTER O WITH GRAVE
      'o\'' => '&#243;', // LATIN SMALL LETTER O WITH ACUTE
      '\'o' => '&#243;', // LATIN SMALL LETTER O WITH ACUTE
      'o^' => '&#244;', // LATIN SMALL LETTER O WITH CIRCUMFLEX
      '^o' => '&#244;', // LATIN SMALL LETTER O WITH CIRCUMFLEX
      'o~' => '&#245;', // LATIN SMALL LETTER O WITH TILDE
      '~o' => '&#245;', // LATIN SMALL LETTER O WITH TILDE
      'o"' => '&#246;', // LATIN SMALL LETTER O WITH DIAERESIS
      '"o' => '&#246;', // LATIN SMALL LETTER O WITH DIAERESIS
      ':-' => '&#247;', // DIVISION SIGN
      '-:' => '&#247;', // DIVISION SIGN
      'o/' => '&#248;', // LATIN SMALL LETTER O WITH STROKE
      '/o' => '&#248;', // LATIN SMALL LETTER O WITH STROKE
      'u`' => '&#249;', // LATIN SMALL LETTER U WITH GRAVE
      '`u' => '&#249;', // LATIN SMALL LETTER U WITH GRAVE
      'u\'' => '&#250;', // LATIN SMALL LETTER U WITH ACUTE
      '\'u' => '&#250;', // LATIN SMALL LETTER U WITH ACUTE
      'u^' => '&#251;', // LATIN SMALL LETTER U WITH CIRCUMFLEX
      '^u' => '&#251;', // LATIN SMALL LETTER U WITH CIRCUMFLEX
      'u"' => '&#252;', // LATIN SMALL LETTER U WITH DIAERESIS
      '"u' => '&#252;', // LATIN SMALL LETTER U WITH DIAERESIS
      'y\'' => '&#253;', // LATIN SMALL LETTER Y WITH ACUTE
      '\'y' => '&#253;', // LATIN SMALL LETTER Y WITH ACUTE
      'y"' => '&#255;', // LATIN SMALL LETTER Y WITH DIAERESIS
      '"y' => '&#255;', // LATIN SMALL LETTER Y WITH DIAERESIS
      'OE' => '&#338;', // LATIN CAPITAL LIGATURE OE
      'oe' => '&#339;', // LATIN SMALL LIGATURE OE
      '*' => '&#8226;', // BULLET
      'Fr' => '&#8355;', // FRENCH FRANC SIGN
      'L=' => '&#8356;', // LIRA SIGN
      '=L' => '&#8356;', // LIRA SIGN
      'Rs' => '&#8360;', // RUPEE SIGN
      'C=' => '&#8364;', // EURO SIGN
      '=C' => '&#8364;', // EURO SIGN
      'tm' => '&#8482;', // TRADE MARK SIGN
      '&lt;-' => '&#8592;', // LEFTWARDS ARROW
      '-&gt;' => '&#8594;', // RIGHTWARDS ARROW
      '&lt;=' => '&#8656;', // LEFTWARDS DOUBLE ARROW
      '=&gt;' => '&#8658;', // RIGHTWARDS DOUBLE ARROW
      '=/' => '&#8800;', // NOT EQUAL TO
      '/=' => '&#8800;', // NOT EQUAL TO
      '&lt;_' => '&#8804;', // LESS-THAN OR EQUAL TO
      '_&lt;' => '&#8804;', // LESS-THAN OR EQUAL TO
      '&gt;_' => '&#8805;', // GREATER-THAN OR EQUAL TO
      '_&gt;' => '&#8805;', // GREATER-THAN OR EQUAL TO
      ':(' => '&#9785;', // WHITE FROWNING FACE
      ':)' => '&#9786;', // WHITE SMILING FACE
      'spade' => '&#9824;', // BLACK SPADE SUIT
      'club' => '&#9827;', // BLACK CLUB SUIT
      'heart' => '&#9829;', // BLACK HEART SUIT
      'diamond' => '&#9830;', // BLACK DIAMOND SUIT
    );
  } // function default_macros

  // "private", internal routines

  /**
   * Sets the default CSS names for CSS controlled markup. This
   * is an internal function that should not be called directly.
   *
   * @private
   */
  function _css_defaults() {
    $css_defaults = array(
      'class_align_right' => 'right',
      'class_align_left' => 'left',
      'class_align_center' => 'center',
      'class_align_top' => 'top',
      'class_align_bottom' => 'bottom',
      'class_align_middle' => 'middle',
      'class_align_justify' => 'justify',
      'class_caps' => 'caps',
      'class_footnote' => 'footnote',
      'id_footnote_prefix' => 'fn',
    );
    $this->css($css_defaults);
  } // function _css_defaults

  /**
   * Returns the alignment keyword depending on the symbol passed.
   *
   * <ul>
   *
   * <li><b><code>\<\></code></b>
   *
   * becomes 'justify'</li>
   *
   * <li><b><code>\<</code></b>
   *
   * becomes 'left'</li>
   *
   * <li><b><code>\></code></b>
   *
   * becomes 'right'</li>
   *
   * <li><b><code>=</code></b>
   *
   * becomes 'center'</li>
   *
   * </ul>
   *
   * @param $align A @c string specifying the alignment code.
   *
   * @return A @c string containing the alignment text.
   *
   * @private
   */
  function _halign($align) {
    if (preg_match('/<>/', $align)) {
      return 'justify';
    } elseif (preg_match('/</', $align)) {
      return 'left';
    } elseif (preg_match('/>/', $align)) {
      return 'right';
    } elseif (preg_match('/=/', $align)) {
      return 'center';
    }
    return '';
  } // function _halign

  /**
   * Returns the alignment keyword depending on the symbol passed.
   *
   * <ul>
   *
   * <li><b><code>^</code></b>
   *
   * becomes 'top'</li>
   *
   * <li><b><code>~</code></b>
   *
   * becomes 'bottom'</li>
   *
   * <li><b><code>-</code></b>
   *
   * becomes 'middle'</li>
   *
   * </ul>
   *
   * @param $align A @c string specifying the alignment code.
   *
   * @return A @c string containing the alignment text.
   *
   * @private
   */
  function _valign($align) {
    if (preg_match('/\^/', $align)) {
      return 'top';
    } elseif (preg_match('/~/', $align)) {
      return 'bottom';
    } elseif (preg_match('/-/', $align)) {
      return 'middle';
    }
    return '';
  } // function _valign

  /**
   * Returns the alignment keyword depending on the symbol passed.
   * The following alignment symbols are recognized, and given
   * preference in the order listed:
   *
   * <ul>
   *
   * <li><b><code>^</code></b>
   *
   * becomes 'top'</li>
   *
   * <li><b><code>~</code></b>
   *
   * becomes 'bottom'</li>
   *
   * <li><b><code>-</code></b>
   *
   * becomes 'middle'</li>
   *
   * <li><b><code>\<</code></b>
   *
   * becomes 'left'</li>
   *
   * <li><b><code>\></code></b>
   *
   * becomes 'right'</li>
   *
   * </ul>
   *
   * @param $align A @c string containing the alignment code.
   *
   * @return A @c string containing the alignment text.
   *
   * @private
   */
  function _imgalign($align) {
    $align = preg_replace('/(<>|=)/', '', $align);
    return ($this->_valign($align) ? $this->_valign($align) : $this->_halign($align));
  } // function _imgalign

  /**
   * This utility routine will take 'border' characters off of
   * the given @c $pre and @c $post strings if they match one of these
   * conditions:
   * <pre>
   *     $pre starts with '[', $post ends with ']'
   *     $pre starts with '{', $post ends with '}'
   * </pre>
   * If neither condition is met, then the @c $pre and @c $post
   * values are left untouched.
   *
   * @param $pre A @c string specifying the prefix.
   * @param $post A @c string specifying the postfix.
   *
   * @private
   */
  function _strip_borders(&$pre, &$post) {
    if ($post && $pre && preg_match('/[{[]/', ($open = substr($pre, 0, 1)))) {
      $close = substr($post, 0, 1);
      if ((($open == '{') && ($close == '}')) ||
          (($open == '[') && ($close == ']'))) {
        $pre = substr($pre, 1);
        $post = substr($post, 1);
      } else {
        if (!preg_match('/[}\]]/', $close)) { $close = substr($post, -1, 1); }
        if ((($open == '{') && ($close == '}')) ||
            (($open == '[') && ($close == ']'))) {
          $pre = substr($pre, 1);
          $post = substr($post, 0, strlen($post) - 1);
        }
      }
    }
  } // function _strip_borders

  /**
   * An internal routine that takes a string and appends it to an array.
   * It returns a marker that is used later to restore the preserved
   * string.
   *
   * @param $array The @c array in which to store the replacement
   *        text.
   * @param $str A @c string specifying the replacement text.
   *
   * @return A @c string containing a temporary marker for the
   *         replacement.
   *
   * @private
   */
  function _repl(&$array, $str) {
    $array[] = $str;
    return '<textile#' . count($array) . '>';
  } // function _repl

  /**
   * An internal routine responsible for breaking up a string into
   * individual tag and plaintext elements.
   *
   * @param $str A @c string specifying the text to tokenize.
   *
   * @return An @c array containing the tag and text tokens.
   *
   * @private
   */
  function _tokenize($str) {
    $pos = 0;
    $len = strlen($str);
    unset($tokens);

    $depth = 6;
    $nested_tags = substr(str_repeat('(?:</?[A-Za-z0-9:]+ \s? (?:[^<>]|', $depth), 0, -1)
      . str_repeat(')*>)', $depth);
    $match = '(?s: <! ( -- .*? -- \s* )+ > )|  # comment
              (?s: <\? .*? \?> )|              # processing instruction
              (?s: <% .*? %> )|                # ASP-like
              (?:' . $nested_tags . ')|
              (?:' . $this->codere . ')';     // nested tags

    while (preg_match('{(' . $match . ')}x', substr($str, $pos), $matches, PREG_OFFSET_CAPTURE)) {
      $whole_tag = $matches[1][0];
      $sec_start = $pos + $matches[1][1] + strlen($whole_tag);
      $tag_start = $sec_start - strlen($whole_tag);
      if ($pos < $tag_start) {
        $tokens[] = array('text', substr($str, $pos, $tag_start - $pos));
      }
      if (preg_match('/^[[{]?@/', $whole_tag)) {
        $tokens[] = array('text', $whole_tag);
      } else {
        // this clever hack allows us to preserve \n within tags.
        // this is restored at the end of the format_paragraph method
        //$whole_tag = preg_replace('/\n/', "\r", $whole_tag);
        $whole_tag = preg_replace('/\n/', "\001", $whole_tag);
        $tokens[] = array('tag', $whole_tag);
      }
      $pos = $sec_start;
    }
    if ($pos < $len) { $tokens[] = array('text', substr($str, $pos, $len - $pos)); }
    return $tokens;
  } // function _tokenize

  /**
   * Returns the version of this release of Textile.php. *JHR*
   *
   * @return An @c array with keys 'text' and 'build' containing the
   *         text version and build ID of this release, respectively.
   *
   * @static
   */
  function version() {
    /* Why text and an ID?  Well, the text is easier for the user to
     * read and understand while the build ID, being a number (a date
     * with a serial, specifically), is easier for the developer to
     * use to determine newer/older versions for upgrade and
     * installation purposes.
     */
    return array("text" => "2.0.8", "build" => 2005032100);
  } // function version

/**
   * Creates a custom callback function from the provided PHP
   * code. The result is used as the callback in
   * @c preg_replace_callback calls. *JHR*
   *
   * @param $function A @c string specifying the PHP code for the
   *        function body.
   *
   * @return A @c function to be used for the callback.
   *
   * @private
   */
  function _cb($function) {
    $current =& Textile::_current_store($this);
    return create_function('$m', '$me =& Textile::_current(); return ' . $function . ';');
  } // function _cb

  /**
   * Stores a static variable for the Textile class. This helper
   * function is used by @c _current to simulate a static
   * class variable in PHP. *JHR*
   *
   * @param $new If a non-@c NULL object reference, the Textile object
   *        to be set as the current object.
   *
   * @return The @c array containing a reference to the current
   *         Textile object at index 0. An array is used because PHP
   *         does not allow static variables to be references.
   *
   * @static
   * @private
   */
 /* static */ function &_current_store(&$new) {
   static $current = array();

   if ($new != NULL) {
     $current = array(&$new);
   }

   return $current;
 } // function _current_store

  /**
   * Returns the "current" Textile object. This is used within
   * anonymous callback functions which cannot have the scope of a
   * specific object. *JHR*
   *
   * @return An @c object reference to the current Textile object.
   *
   * @static
   * @private
   */
 /* static */ function &_current() {
   $current =& Textile::_current_store($null = NULL);
   return $current[0];
 } // function _current
} // class Textile

/**
 * Brad Choate's mttextile Movable Type plugin adds some additional
 * functionality to the Textile.pm Perl module. This includes optional
 * "SmartyPants" processing of text to produce smart quotes, dashes,
 * etc., code colorizing using Beautifier, and some special lookup
 * links (imdb, google, dict, and amazon). The @c MTLikeTextile class
 * is a subclass of @c Textile that provides an MT-like implementation
 * of Textile to produce results similar to that of the mttextile
 * plugin. Currently only the SmartyPants and special lookup links are
 * implemented.
 *
 * Using the @c MTLikeTextile class is exactly the same as using @c
 * Textile. Simply use <code>$textile = new MTLikeTextile;</code>
 * instead of <code>$textile = new Textile;</code> to create a Textile
 * object.  This will enable the special lookup links.  To enable
 * SmartyPants processing, you must install the SmartyPants-PHP
 * implementation available at
 * <a
 * href="http://monauraljerk.org/smartypants-php/">http://monauraljerk.org/smartypants-php/</a>
 * and include the
 * SmartyPants-PHP.inc file.
 *
 * <pre><code>
 * include_once("Textile.php");
 * include_once("SmartyPants-PHP.inc");
 * $text = \<\<\<EOT
 * h1. Heading
 *
 * A _simple_ demonstration of Textile markup.
 *
 * * One
 * * Two
 * * Three
 *
 * "More information":http://www.textism.com/tools/textile is available.
 * EOT;
 *
 * $textile = new MTLikeTextile;
 * $html = $textile->process($text);
 * print $html;
 * </code></pre>
 *
 * @brief A Textile implementation providing additional
 *        Movable-Type-like formatting to produce results similar to
 *        the mttextile plugin.
 *
 * @author Jim Riggs \<textile at jimandlisa dot com\>
 */
class MTLikeTextile extends Textile {
  /**
   * Instantiates a new MTLikeTextile object. Optional options
   * can be passed to initialize the object. Attributes for the
   * options key are the same as the get/set method names
   * documented here.
   *
   * @param $options The @c array specifying the options to use for
   *        this object.
   *
   * @public
   */
  function MTLikeTextile($options = array()) {
    parent::Textile($options);
  } // function MTLikeTextile

  /**
   * @private
   */
  function process_quotes($str) {
    if (!$this->options['do_quotes'] || !function_exists('SmartyPants')) {
      return $str;
    }

    return SmartyPants($str, $this->options['smarty_mode']);
  } // function process_quotes

  /**
   * @private
   */
  function format_url($args) {
    $url = ($args['url'] ? $args['url'] : '');

    if (preg_match('/^(imdb|google|dict|amazon)(:(.+))?$/x', $url, $matches)) {
      $term = $matches[3];
      $term = ($term ? $term : strip_tags($args['linktext']));

      switch ($matches[1]) {
        case 'imdb':
          $args['url'] = 'http://www.imdb.com/Find?for=' . $term;
          break;

        case 'google':
          $args['url'] = 'http://www.google.com/search?q=' . $term;
          break;

        case 'dict':
          $args['url'] = 'http://www.dictionary.com/search?q=' . $term;
          break;

        case 'amazon':
          $args['url'] = 'http://www.amazon.com/exec/obidos/external-search?index=blended&keyword=' . $term;
          break;
      }
    }

    return parent::format_url($args);
  } // function format_url
} // class MTLikeTextile

/**
 * @mainpage
 * Textile - A Humane Web Text Generator.
 *
 * @section synopsis SYNOPSIS
 *
 * <pre><code>
 * include_once("Textile.php");
 * $text = \<\<\<EOT
 * h1. Heading
 *
 * A _simple_ demonstration of Textile markup.
 *
 * * One
 * * Two
 * * Three
 *
 * "More information":http://www.textism.com/tools/textile is available.
 * EOT;
 *
 * $textile = new Textile;
 * $html = $textile->process($text);
 * print $html;
 * </code></pre>
 *
 * @section abstract ABSTRACT
 *
 * Textile.php is a PHP-based implementation of Dean Allen's Textile
 * syntax. Textile is shorthand for doing common formatting tasks.
 *
 * @section syntax SYNTAX
 *
 * Textile processes text in units of blocks and lines.
 * A block might also be considered a paragraph, since blocks
 * are separated from one another by a blank line. Blocks
 * can begin with a signature that helps identify the rest
 * of the block content. Block signatures include:
 *
 * <ul>
 *
 * <li><b>p</b>
 *
 * A paragraph block. This is the default signature if no
 * signature is explicitly given. Paragraphs are formatted
 * with all the inline rules (see inline formatting) and
 * each line receives the appropriate markup rules for
 * the flavor of HTML in use. For example, newlines for XHTML
 * content receive a \<br /\> tag at the end of the line
 * (with the exception of the last line in the paragraph).
 * Paragraph blocks are enclosed in a \<p\> tag.</li>
 *
 * <li><b>pre</b>
 *
 * A pre-formatted block of text. Textile will not add any
 * HTML tags for individual lines. Whitespace is also preserved.
 * 
 * Note that within a "pre" block, \< and \> are
 * translated into HTML entities automatically.</li>
 *
 * <li><b>bc</b>
 *
 * A "bc" signature is short for "block code", which implies
 * a preformatted section like the 'pre' block, but it also
 * gets a \<code\> tag (or for XHTML 2, a \<blockcode\>
 * tag is used instead).
 * 
 * Note that within a "bc" block, \< and \> are
 * translated into HTML entities automatically.</li>
 *
 * <li><b>table</b>
 *
 * For composing HTML tables. See the "TABLES" section for more
 * information.</li>
 *
 * <li><b>bq</b>
 *
 * A "bq" signature is short for "block quote". Paragraph text
 * formatting is applied to these blocks and they are enclosed
 * in a \<blockquote\> tag as well as \<p\> tags
 * within.</li>
 *
 * <li><b>h1, h2, h3, h4, h5, h6</b>
 *
 * Headline signatures that produce \<h1\>, etc. tags.
 * You can adjust the relative output of these using the
 * head_offset attribute.</li>
 *
 * <li><b>clear</b>
 *
 * A 'clear' signature is simply used to indicate that the next
 * block should emit a CSS style attribute that clears any
 * floating elements. The default behavior is to clear "both",
 * but you can use the left (\< or right \>) alignment
 * characters to indicate which side to clear.</li>
 *
 * <li><b>dl</b>
 *
 * A "dl" signature is short for "definition list". See the
 * "LISTS" section for more information.</li>
 *
 * <li><b>fn</b>
 *
 * A "fn" signature is short for "footnote". You add a number
 * following the "fn" keyword to number the footnote. Footnotes
 * are output as paragraph tags but are given a special CSS
 * class name which can be used to style them as you see fit.</li>
 *
 * </ul>
 *
 * All signatures should end with a period and be followed
 * with a space. Inbetween the signature and the period, you
 * may use several parameters to further customize the block.
 * These include:
 *
 * <ul>
 *
 * <li><b><code>{style rule}</code></b>
 *
 * A CSS style rule. Style rules can span multiple lines.</li>
 *
 * <li><b><code>[ll]</code></b>
 *
 * A language identifier (for a "lang" attribute).</li>
 *
 * <li><b><code>(class)</code> or <code>(#id)</code> or <code>(class#id)</code></b>
 *
 * For CSS class and id attributes.</li>
 *
 * <li><b><code>\></code>, <code>\<</code>, <code>=</code>, <code>\<\></code></b>
 *
 * Modifier characters for alignment. Right-justification, left-justification,
 * centered, and full-justification.</li>
 *
 * <li><b><code>(</code> (one or more)</b>
 *
 * Adds padding on the left. 1em per "(" character is applied.
 * When combined with the align-left or align-right modifier,
 * it makes the block float.</li>
 *
 * <li><b><code>)</code> (one or more)</b>
 *
 * Adds padding on the right. 1em per ")" character is applied.
 * When combined with the align-left or align-right modifier,
 * it makes the block float.</li>
 *
 * <li><b><code>|filter|</code> or <code>|filter|filter|filter|</code></b>
 *
 * A filter may be invoked to further format the text for this
 * signature. If one or more filters are identified, the text
 * will be processed first using the filters and then by
 * Textile's own block formatting rules.</li>
 *
 * </ul>
 *
 * @subsection extendedblocks Extended Blocks
 *
 * Normally, a block ends with the first blank line encountered.
 * However, there are situations where you may want a block to continue
 * for multiple paragraphs of text. To cause a given block signature
 * to stay active, use two periods in your signature instead of one.
 * This will tell Textile to keep processing using that signature
 * until it hits the next signature is found.
 *
 * For example:
 * <pre>
 *     bq.. This is paragraph one of a block quote.
 *
 *     This is paragraph two of a block quote.
 *
 *     p. Now we're back to a regular paragraph.
 * </pre>
 * You can apply this technique to any signature (although for
 * some it doesn't make sense, like "h1" for example). This is
 * especially useful for "bc" blocks where your code may
 * have many blank lines scattered through it.
 *
 * @subsection escaping Escaping
 *
 * Sometimes you want Textile to just get out of the way and
 * let you put some regular HTML markup in your document. You
 * can disable Textile formatting for a given block using the '=='
 * escape mechanism:
 * <pre>
 *     p. Regular paragraph
 *
 *     ==
 *     Escaped portion -- will not be formatted
 *     by Textile at all
 *     ==
 *
 *     p. Back to normal.
 * </pre>
 * You can also use this technique within a Textile block,
 * temporarily disabling the inline formatting functions:
 * <pre>
 *     p. This is ==*a test*== of escaping.
 * </pre>
 * @subsection inlineformatting Inline Formatting
 *
 * Formatting within a block of text is covered by the "inline"
 * formatting rules. These operators must be placed up against
 * text/punctuation to be recognized. These include:
 *
 * <ul>
 *
 * <li><b><code>*strong*</code></b>
 *
 * Translates into \<strong\>strong\</strong\>.</li>
 *
 * <li><b>_emphasis_</b>
 *
 * Translates into \<em\>emphasis\</em\>.</li>
 *
 * <li><b><code>**bold**</code></b>
 *
 * Translates into \<b\>bold\</b\>.</li>
 *
 * <li><b><code>__italics__</code></b>
 *
 * Translates into \<i\>italics\</i\>.</li>
 *
 * <li><b><code>++bigger++</code></b>
 *
 * Translates into \<big\>bigger\</big\>.</li>
 *
 * <li><b><code>--smaller--</code></b>
 *
 * Translates into: \<small\>smaller\</small\>.</li>
 *
 * <li><b><code>-deleted text-</code></b>
 *
 * Translates into \<del\>deleted text\</del\>.</li>
 *
 * <li><b><code>+inserted text+</code></b>
 *
 * Translates into \<ins\>inserted text\</ins\>.</li>
 *
 * <li><b><code>^superscript^</code></b>
 *
 * Translates into \<sup\>superscript\</sup\>.</li>
 *
 * <li><b><code>~subscript~</code></b>
 *
 * Translates into \<sub\>subscript\</sub\>.</li>
 *
 * <li><b><code>\%span\%</code></b>
 *
 * Translates into \<span\>span\</span\>.</li>
 *
 * <li><b><code>\@code\@</code></b>
 *
 * Translates into \<code\>code\</code\>. Note
 * that within a '\@...\@' section, \< and \> are
 * translated into HTML entities automatically.</li>
 *
 * </ul>
 *
 * Inline formatting operators accept the following modifiers:
 *
 * <ul>
 *
 * <li><b><code>{style rule}</code></b>
 *
 * A CSS style rule.</li>
 *
 * <li><b><code>[ll]</code></b>
 *
 * A language identifier (for a "lang" attribute).</li>
 *
 * <li><b><code>(class)</code> or <code>(#id)</code> or <code>(class#id)</code></b>
 *
 * For CSS class and id attributes.</li>
 *
 * </ul>
 *
 * @subsubsection examples Examples
 * <pre>
 *     Textile is *way* cool.
 *
 *     Textile is *_way_* cool.
 * </pre>
 * Now this won't work, because the formatting
 * characters need whitespace before and after
 * to be properly recognized.
 * <pre>
 *     Textile is way c*oo*l.
 * </pre>
 * However, you can supply braces or brackets to
 * further clarify that you want to format, so
 * this would work:
 * <pre>
 *     Textile is way c[*oo*]l.
 * </pre>
 * @subsection footnotes Footnotes
 *
 * You can create footnotes like this:
 * <pre>
 *     And then he went on a long trip[1].
 * </pre>
 * By specifying the brackets with a number inside, Textile will
 * recognize that as a footnote marker. It will replace that with
 * a construct like this:
 * <pre>
 *     And then he went on a long
 *     trip<sup class="footnote"><a href="#fn1">1</a></sup>
 * </pre>
 * To supply the content of the footnote, place it at the end of your
 * document using a "fn" block signature:
 * <pre>
 *     fn1. And there was much rejoicing.
 * </pre>
 * Which creates a paragraph that looks like this:
 * <pre>
 *     <p class="footnote" id="fn1"><sup>1</sup> And there was
 *     much rejoicing.</p>
 * </pre>
 * @subsection links Links
 *
 * Textile defines a shorthand for formatting hyperlinks.
 * The format looks like this:
 * <pre>
 *     "Text to display":http://example.com
 * </pre>
 * In addition to this, you can add 'title' text to your link:
 * <pre>
 *     "Text to display (Title text)":http://example.com
 * </pre>
 * The URL portion of the link supports relative paths as well
 * as other protocols like ftp, mailto, news, telnet, etc.
 * <pre>
 *     "E-mail me please":mailto:someone\@example.com
 * </pre>
 * You can also use single quotes instead of double-quotes if
 * you prefer. As with the inline formatting rules, a hyperlink
 * must be surrounded by whitespace to be recognized (an
 * exception to this is common punctuation which can reside
 * at the end of the URL). If you have to place a URL next to
 * some other text, use the bracket or brace trick to do that:
 * <pre>
 *     You["gotta":http://example.com]seethis!
 * </pre>
 * Textile supports an alternate way to compose links. You can
 * optionally create a lookup list of links and refer to them
 * separately. To do this, place one or more links in a block
 * of it's own (it can be anywhere within your document):
 * <pre>
 *     [excom]http://example.com
 *     [exorg]http://example.org
 * </pre>
 * For a list like this, the text in the square brackets is
 * used to uniquely identify the link given. To refer to that
 * link, you would specify it like this:
 * <pre>
 *     "Text to display":excom
 * </pre>
 * Once you've defined your link lookup table, you can use
 * the identifiers any number of times.
 *
 * @subsection images Images
 *
 * Images are identified by the following pattern:
 * <pre>
 *     !/path/to/image!
 * </pre>
 * Image attributes may also be specified:
 * <pre>
 *     !/path/to/image 10x20!
 * </pre>
 * Which will render an image 10 pixels wide and 20 pixels high.
 * Another way to indicate width and height:
 * <pre>
 *     !/path/to/image 10w 20h!
 * </pre>
 * You may also redimension the image using a percentage.
 * <pre>
 *     !/path/to/image 20%x40%!
 * </pre>
 * Which will render the image at 20% of it's regular width
 * and 40% of it's regular height.
 *
 * Or specify one percentage to resize proprotionately:
 * <pre>
 *     !/path/to/image 20%!
 * </pre>
 * Alt text can be given as well:
 * <pre>
 *     !/path/to/image (Alt text)!
 * </pre>
 * The path of the image may refer to a locally hosted image or
 * can be a full URL.
 *
 * You can also use the following modifiers after the opening '!'
 * character:
 *
 * <ul>
 *
 * <li><b><code>\<</code></b>
 *
 * Align the image to the left (causes the image to float if
 * CSS options are enabled).</li>
 *
 * <li><b><code>\></code></b>
 *
 * Align the image to the right (causes the image to float if
 * CSS options are enabled).</li>
 *
 * <li><b><code>-</code> (dash)</b>
 *
 * Aligns the image to the middle.</li>
 *
 * <li><b><code>^</code></b>
 *
 * Aligns the image to the top.</li>
 *
 * <li><b><code>~</code> (tilde)</b>
 *
 * Aligns the image to the bottom.</li>
 *
 * <li><b><code>{style rule}</code></b>
 *
 * Applies a CSS style rule to the image.</li>
 *
 * <li><b><code>(class)</code> or <code>(#id)</code> or <code>(class#id)</code></b>
 *
 * Applies a CSS class and/or id to the image.</li>
 *
 * <li><b><code>(</code> (one or more)</b>
 *
 * Pads 1em on the left for each '(' character.</li>
 *
 * <li><b><code>)</code> (one or more)</b>
 *
 * Pads 1em on the right for each ')' character.</li>
 *
 * </ul>
 *
 * @subsection characterreplacements Character Replacements
 *
 * A few simple, common symbols are automatically replaced:
 * <pre>
 *     (c)
 *     (r)
 *     (tm)
 * </pre>
 * In addition to these, there are a whole set of character
 * macros that are defined by default. All macros are enclosed
 * in curly braces. These include:
 * <pre>
 *     {c|} or {|c} cent sign
 *     {L-} or {-L} pound sign
 *     {Y=} or {=Y} yen sign
 * </pre>
 * Many of these macros can be guessed. For example:
 * <pre>
 *     {A'} or {'A}
 *     {a"} or {"a}
 *     {1/4}
 *     {*}
 *     {:)}
 *     {:(}
 * </pre>
 * @subsection lists Lists
 *
 * Textile also supports ordered and unordered lists.
 * You simply place an asterisk or pound sign, followed
 * with a space at the start of your lines.
 *
 * Simple lists:
 * <pre>
 *     * one
 *     * two
 *     * three
 * </pre>
 * Multi-level lists:
 * <pre>
 *     * one
 *     ** one A
 *     ** one B
 *     *** one B1
 *     * two
 *     ** two A
 *     ** two B
 *     * three
 * </pre>
 * Ordered lists:
 * <pre>
 *     # one
 *     # two
 *     # three
 * </pre>
 * Styling lists:
 * <pre>
 *     (class#id)* one
 *     * two
 *     * three
 * </pre>
 * The above sets the class and id attributes for the \<ul\>
 * tag.
 * <pre>
 *     *(class#id) one
 *     * two
 *     * three
 * </pre>
 * The above sets the class and id attributes for the first \<li\>
 * tag.
 *
 * Definition lists:
 * <pre>
 *     dl. textile:a cloth, especially one manufactured by weaving
 *     or knitting; a fabric
 *     format:the arrangement of data for storage or display.
 * </pre>
 * Note that there is no space between the term and definition. The
 * term must be at the start of the line (or following the "dl"
 * signature as shown above).
 *
 * @subsection tables Tables
 *
 * Textile supports tables. Tables must be in their own block and
 * must have pipe characters delimiting the columns. An optional
 * block signature of "table" may be used, usually for applying
 * style, class, id or other options to the table element itself.
 *
 * From the simple:
 * <pre>
 *     |a|b|c|
 *     |1|2|3|
 * </pre>
 * To the complex:
 * <pre>
 *     table(fig). {color:red}_|Top|Row|
 *     {color:blue}|/2. Second|Row|
 *     |_{color:green}. Last|
 * </pre>
 * Modifiers can be specified for the table signature itself,
 * for a table row (prior to the first '|' character) and
 * for any cell (following the '|' for that cell). Note that for
 * cells, a period followed with a space must be placed after
 * any modifiers to distinguish the modifier from the cell content.
 *
 * Modifiers allowed are:
 *
 * <ul>
 *
 * <li><b><code>{style rule}</code></b>
 *
 * A CSS style rule.</li>
 *
 * <li><b><code>(class)</code> or <code>(#id)</code> or <code>(class#id)</code></b>
 *
 * A CSS class and/or id attribute.</li>
 *
 * <li><b><code>(</code> (one or more)</b>
 *
 * Adds 1em of padding to the left for each '(' character.</li>
 *
 * <li><b><code>)</code> (one or more)</b>
 *
 * Adds 1em of padding to the right for each ')' character.</li>
 *
 * <li><b><code>\<</code></b>
 *
 * Aligns to the left (floats to left for tables if combined with the
 * ')' modifier).</li>
 *
 * <li><b><code>\></code></b>
 *
 * Aligns to the right (floats to right for tables if combined with
 * the '(' modifier).</li>
 *
 * <li><b><code>=</code></b>
 *
 * Aligns to center (sets left, right margins to 'auto' for tables).</li>
 *
 * <li><b><code>\<\></code></b>
 *
 * For cells only. Justifies text.</li>
 *
 * <li><b><code>^</code></b>
 *
 * For rows and cells only. Aligns to the top.</li>
 *
 * <li><b><code>~</code> (tilde)</b>
 *
 * For rows and cells only. Aligns to the bottom.</li>
 *
 * <li><b><code>_</code> (underscore)</b>
 *
 * Can be applied to a table row or cell to indicate a header
 * row or cell.</li>
 *
 * <li><b><code>\\2</code> or <code>\\3</code> or <code>\\4</code>, etc.</b>
 *
 * Used within cells to indicate a colspan of 2, 3, 4, etc. columns.
 * When you see "\\", think "push forward".</li>
 *
 * <li><b><code>/2</code> or <code>/3</code> or <code>/4</code>, etc.</b>
 *
 * Used within cells to indicate a rowspan of 2, 3, 4, etc. rows.
 * When you see "/", think "push downward".</li>
 *
 * </ul>
 *
 * When a cell is identified as a header cell and an alignment
 * is specified, that becomes the default alignment for
 * cells below it. You can always override this behavior by
 * specifying an alignment for one of the lower cells.
 *
 * @subsection cssnotes CSS Notes
 *
 * When CSS is enabled (and it is by default), CSS class names
 * are automatically applied in certain situations.
 *
 * <ul>
 *
 * <li>Aligning a block or span or other element to
 * left, right, etc.
 *
 * "left" for left justified, "right" for right justified,
 * "center" for centered text, "justify" for full-justified
 * text.</li>
 *
 * <li>Aligning an image to the top or bottom
 *
 * "top" for top alignment, "bottom" for bottom alignment,
 * "middle" for middle alignment.</li>
 *
 * <li>Footnotes
 *
 * "footnote" is applied to the paragraph tag for the
 * footnote text itself. An id of "fn" plus the footnote
 * number is placed on the paragraph for the footnote as
 * well. For the footnote superscript tag, a class of
 * "footnote" is used.</li>
 *
 * <li>Capped text
 *
 * For a series of characters that are uppercased, a
 * span is placed around them with a class of "caps".</li>
 *
 * </ul>
 *
 * @subsection miscellaneous Miscellaneous
 *
 * Textile tries to do it's very best to ensure proper XHTML
 * syntax. It will even attempt to fix errors you may introduce
 * writing in HTML yourself. Unescaped '&' characters within
 * URLs will be properly escaped. Singlet tags such as br, img
 * and hr are checked for the '/' terminator (and it's added
 * if necessary). The best way to make sure you produce valid
 * XHTML with Textile is to not use any HTML markup at all--
 * use the Textile syntax and let it produce the markup for you.
 *
 * @section license LICENSE
 *
 * Text::Textile is licensed under the same terms as Perl
 * itself. Textile.php is licensed under the terms of the GNU General
 * Public License.
 *
 * @section authorandcopyright AUTHOR & COPYRIGHT
 *
 * Text::Textile was written by Brad Choate, \<brad at bradchoate dot com\>.
 * It is an adaptation of Textile, developed by Dean Allen of Textism.com.
 *
 * Textile.php is a PHP port of Brad Choate's Text::Textile
 * (Textile.pm) Perl module.
 *
 * Textile.php was ported by Jim Riggs \<textile at jimandlissa dot
 * com\>. Great care has been taken to leave the Perl code in much the
 * same form as Textile.pm. While changes were required due to
 * syntactical differences between Perl and PHP, much of the code was
 * left intact (even if alternative syntax or code optimizations could
 * have been made in PHP), even to the point where one can compare
 * functions/subroutines side by side between the two implementations.
 * This has been done to ensure compatibility, reduce the possibility
 * of introducing errors, and simplify maintainance as one version or
 * the other is updated.
 *
 * @author Jim Riggs \<textile at jimandlissa dot com\>
 * @author Brad Choate \<brad at bradchoate dot com\>
 * @copyright Copyright &copy; 2004 Jim Riggs and Brad Choate
 * @version @(#) $Id: Textile.php,v 1.13 2005/03/21 15:26:55 jhriggs Exp $
 */
?>
