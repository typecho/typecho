//
// showdown.js -- A javascript port of Markdown.
//
// Copyright (c) 2007 John Fraser.
//
// Original Markdown Copyright (c) 2004-2005 John Gruber
//   <http://daringfireball.net/projects/markdown/>
//
// Redistributable under a BSD-style open source license.
// See license.txt for more information.
//
// The full source distribution is at:
//
//              A A L
//              T C A
//              T K B
//
//   <http://www.attacklab.net/>
//

//
// Wherever possible, Showdown is a straight, line-by-line port
// of the Perl version of Markdown.
//
// This is not a normal parser design; it's basically just a
// series of string substitutions.  It's hard to read and
// maintain this way,  but keeping Showdown close to the original
// design makes it easier to port new features.
//
// More importantly, Showdown behaves like markdown.pl in most
// edge cases.  So web applications can do client-side preview
// in Javascript, and then build identical HTML on the server.
//
// This port needs the new RegExp functionality of ECMA 262,
// 3rd Edition (i.e. Javascript 1.5).  Most modern web browsers
// should do fine.  Even with the new regular expression features,
// We do a lot of work to emulate Perl's regex functionality.
// The tricky changes in this file mostly have the "attacklab:"
// label.  Major or self-explanatory changes don't.
//
// Smart diff tools like Araxis Merge will be able to match up
// this file with markdown.pl in a useful way.  A little tweaking
// helps: in a copy of markdown.pl, replace "#" with "//" and
// replace "$text" with "text".  Be sure to ignore whitespace
// and line endings.
//


//
// Showdown usage:
//
//   var text = "Markdown *rocks*.";
//
//   var converter = new Showdown.converter();
//   var html = converter.makeHtml(text);
//
//   alert(html);
//
// Note: move the sample code to the bottom of this
// file before uncommenting it.
//


//
// Showdown namespace
//
var Showdown = { extensions: {} };

//
// forEach
//
var forEach = Showdown.forEach = function(obj, callback) {
    if (typeof obj.forEach === 'function') {
        obj.forEach(callback);
    } else {
        var i, len = obj.length;
        for (i = 0; i < len; i++) {
            callback(obj[i], i, obj);
        }
    }
};

//
// Standard extension naming
//
var stdExtName = function(s) {
    return s.replace(/[_-]||\s/g, '').toLowerCase();
};

//
// converter
//
// Wraps all "globals" so that the only thing
// exposed is makeHtml().
//
Showdown.converter = function(converter_options) {

//
// Globals:
//

// Global hashes, used by various utility routines
var g_urls;
var g_titles;
var g_html_blocks;

// Used to track when we're inside an ordered or unordered list
// (see _ProcessListItems() for details):
var g_list_level = 0;

// Global extensions
var g_lang_extensions = [];
var g_output_modifiers = [];


//
// Automatic Extension Loading (node only):
//

if (typeof module !== 'undefind' && typeof exports !== 'undefined' && typeof require !== 'undefind') {
    var fs = require('fs');

    if (fs) {
        // Search extensions folder
        var extensions = fs.readdirSync((__dirname || '.')+'/extensions').filter(function(file){
            return ~file.indexOf('.js');
        }).map(function(file){
            return file.replace(/\.js$/, '');
        });
        // Load extensions into Showdown namespace
        Showdown.forEach(extensions, function(ext){
            var name = stdExtName(ext);
            Showdown.extensions[name] = require('./extensions/' + ext);
        });
    }
}

this.postConversion = null;
var self = this;

this.makeHtml = function(text) {
//
// Main function. The order in which other subs are called here is
// essential. Link and image substitutions need to happen before
// _EscapeSpecialCharsWithinTagAttributes(), so that any *'s or _'s in the <a>
// and <img> tags get encoded.
//

    // Clear the global hashes. If we don't clear these, you get conflicts
    // from other articles when generating a page which contains more than
    // one article (e.g. an index page that shows the N most recent
    // articles):
    g_urls = {};
    g_titles = {};
    g_html_blocks = [];

    // attacklab: Replace ~ with ~T
    // This lets us use tilde as an escape char to avoid md5 hashes
    // The choice of character is arbitray; anything that isn't
    // magic in Markdown will work.
    text = text.replace(/~/g,"~T");

    // attacklab: Replace $ with ~D
    // RegExp interprets $ as a special character
    // when it's in a replacement string
    text = text.replace(/\$/g,"~D");

    // Standardize line endings
    text = text.replace(/\r\n/g,"\n"); // DOS to Unix
    text = text.replace(/\r/g,"\n"); // Mac to Unix

    // Make sure text begins and ends with a couple of newlines:
    text = "\n\n" + text + "\n\n";

    // Convert all tabs to spaces.
    text = _Detab(text);

    // Strip any lines consisting only of spaces and tabs.
    // This makes subsequent regexen easier to write, because we can
    // match consecutive blank lines with /\n+/ instead of something
    // contorted like /[ \t]*\n+/ .
    text = text.replace(/^[ \t]+$/mg,"");

    // Run language extensions
    Showdown.forEach(g_lang_extensions, function(x){
        text = _ExecuteExtension(x, text);
    });

    // Handle github codeblocks prior to running HashHTML so that
    // HTML contained within the codeblock gets escaped propertly
    text = _DoGithubCodeBlocks(text);

    // Turn block-level HTML blocks into hash entries
    text = _HashHTMLBlocks(text);

    // Strip link definitions, store in hashes.
    text = _StripLinkDefinitions(text);

    text = _RunBlockGamut(text);

    text = _UnescapeSpecialChars(text);

    // attacklab: Restore dollar signs
    text = text.replace(/~D/g,"$$");

    // attacklab: Restore tildes
    text = text.replace(/~T/g,"~");

    // Run output modifiers
    Showdown.forEach(g_output_modifiers, function(x){
        text = _ExecuteExtension(x, text);
    });

    text = text.replace(/<\/?(\!doctype|html|head|body|link|title|input|select|button|textarea|style|script|noscript|iframe|object|embed)[^>]*>/ig, function (all) {
        return all.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/'/g, '&#039;')
            .replace(/"/g, '&quot;');
        return all;
    });

    if (self.postConversion) {
        text = self.postConversion(text);
    }

    return text;
};
//
// Options:
//

// Parse extensions options into separate arrays
if (converter_options && converter_options.extensions) {

    // Iterate over each plugin
    Showdown.forEach(converter_options.extensions, function(plugin){

        // Assume it's a bundled plugin if a string is given
        if (typeof plugin === 'string') {
            plugin = Showdown.extensions[stdExtName(plugin)];
        }

        if (typeof plugin === 'function') {
            // Iterate over each extension within that plugin
            Showdown.forEach(plugin(self), function(ext){
                // Sort extensions by type
                if (ext.type) {
                    if (ext.type === 'language' || ext.type === 'lang') {
                        g_lang_extensions.push(ext);
                    } else if (ext.type === 'output' || ext.type === 'html') {
                        g_output_modifiers.push(ext);
                    }
                } else {
                    // Assume language extension
                    g_output_modifiers.push(ext);
                }
            });
        } else {
            throw "Extension '" + plugin + "' could not be loaded.  It was either not found or is not a valid extension.";
        }
    });
}


var _ExecuteExtension = function(ext, text) {
    if (ext.regex) {
        var re = new RegExp(ext.regex, 'g');
        return text.replace(re, ext.replace);
    } else if (ext.filter) {
        return ext.filter(text);
    }
};

var _StripLinkDefinitions = function(text) {
//
// Strips link definitions from text, stores the URLs and titles in
// hash references.
//

    // Link defs are in the form: ^[id]: url "optional title"

    /*
        var text = text.replace(/
                ^[ ]{0,3}\[(.+)\]:  // id = $1  attacklab: g_tab_width - 1
                  [ \t]*
                  \n?               // maybe *one* newline
                  [ \t]*
                <?(\S+?)>?          // url = $2
                  [ \t]*
                  \n?               // maybe one newline
                  [ \t]*
                (?:
                  (\n*)             // any lines skipped = $3 attacklab: lookbehind removed
                  ["(]
                  (.+?)             // title = $4
                  [")]
                  [ \t]*
                )?                  // title is optional
                (?:\n+|$)
              /gm,
              function(){...});
    */

    // attacklab: sentinel workarounds for lack of \A and \Z, safari\khtml bug
    text += "~0";

    text = text.replace(/^[ ]{0,3}\[(.+)\]:[ \t]*\n?[ \t]*<?(\S+?)>?[ \t]*\n?[ \t]*(?:(\n*)["(](.+?)[")][ \t]*)?(?:\n+|(?=~0))/gm,
        function (wholeMatch,m1,m2,m3,m4) {
            m1 = m1.toLowerCase();
            g_urls[m1] = _EncodeAmpsAndAngles(m2);  // Link IDs are case-insensitive
            if (m3) {
                // Oops, found blank lines, so it's not a title.
                // Put back the parenthetical statement we stole.
                return m3+m4;
            } else if (m4) {
                g_titles[m1] = m4.replace(/"/g,"&quot;");
            }

            // Completely remove the definition from the text
            return "";
        }
    );

    // attacklab: strip sentinel
    text = text.replace(/~0/,"");

    return text;
}


var _HashHTMLBlocks = function(text) {
    // attacklab: Double up blank lines to reduce lookaround
    text = text.replace(/\n/g,"\n\n");

    // Hashify HTML blocks:
    // We only want to do this for block-level HTML tags, such as headers,
    // lists, and tables. That's because we still want to wrap <p>s around
    // "paragraphs" that are wrapped in non-block-level tags, such as anchors,
    // phrase emphasis, and spans. The list of tags we're looking for is
    // hard-coded:
    var block_tags_a = "p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del|style|section|header|footer|nav|article|aside";
    var block_tags_b = "p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|style|section|header|footer|nav|article|aside";

    // First, look for nested blocks, e.g.:
    //   <div>
    //     <div>
    //     tags for inner block must be indented.
    //     </div>
    //   </div>
    //
    // The outermost tags must start at the left margin for this to match, and
    // the inner nested divs must be indented.
    // We need to do this before the next, more liberal match, because the next
    // match will start at the first `<div>` and stop at the first `</div>`.

    // attacklab: This regex can be expensive when it fails.
    /*
        var text = text.replace(/
        (                       // save in $1
            ^                   // start of line  (with /m)
            <($block_tags_a)    // start tag = $2
            \b                  // word break
                                // attacklab: hack around khtml/pcre bug...
            [^\r]*?\n           // any number of lines, minimally matching
            </\2>               // the matching end tag
            [ \t]*              // trailing spaces/tabs
            (?=\n+)             // followed by a newline
        )                       // attacklab: there are sentinel newlines at end of document
        /gm,function(){...}};
    */
    text = text.replace(/^(<(p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del)\b[^\r]*?\n<\/\2>[ \t]*(?=\n+))/gm,hashElement);

    //
    // Now match more liberally, simply from `\n<tag>` to `</tag>\n`
    //

    /*
        var text = text.replace(/
        (                       // save in $1
            ^                   // start of line  (with /m)
            <($block_tags_b)    // start tag = $2
            \b                  // word break
                                // attacklab: hack around khtml/pcre bug...
            [^\r]*?             // any number of lines, minimally matching
            </\2>               // the matching end tag
            [ \t]*              // trailing spaces/tabs
            (?=\n+)             // followed by a newline
        )                       // attacklab: there are sentinel newlines at end of document
        /gm,function(){...}};
    */
    text = text.replace(/^(<(p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|style|section|header|footer|nav|article|aside)\b[^\r]*?<\/\2>[ \t]*(?=\n+)\n)/gm,hashElement);

    // Special case just for <hr />. It was easier to make a special case than
    // to make the other regex more complicated.

    /*
        text = text.replace(/
        (                       // save in $1
            \n\n                // Starting after a blank line
            [ ]{0,3}
            (<(hr)              // start tag = $2
            \b                  // word break
            ([^<>])*?           //
            \/?>)               // the matching end tag
            [ \t]*
            (?=\n{2,})          // followed by a blank line
        )
        /g,hashElement);
    */
    text = text.replace(/(\n[ ]{0,3}(<(hr)\b([^<>])*?\/?>)[ \t]*(?=\n{2,}))/g,hashElement);

    // Special case for standalone HTML comments:

    /*
        text = text.replace(/
        (                       // save in $1
            \n\n                // Starting after a blank line
            [ ]{0,3}            // attacklab: g_tab_width - 1
            <!
            (--[^\r]*?--\s*)+
            >
            [ \t]*
            (?=\n{2,})          // followed by a blank line
        )
        /g,hashElement);
    */
    text = text.replace(/(\n\n[ ]{0,3}<!(--[^\r]*?--\s*)+>[ \t]*(?=\n{2,}))/g,hashElement);

    // PHP and ASP-style processor instructions (<?...?> and <%...%>)

    /*
        text = text.replace(/
        (?:
            \n\n                // Starting after a blank line
        )
        (                       // save in $1
            [ ]{0,3}            // attacklab: g_tab_width - 1
            (?:
                <([?%])         // $2
                [^\r]*?
                \2>
            )
            [ \t]*
            (?=\n{2,})          // followed by a blank line
        )
        /g,hashElement);
    */
    text = text.replace(/(?:\n\n)([ ]{0,3}(?:<([?%])[^\r]*?\2>)[ \t]*(?=\n{2,}))/g,hashElement);

    // attacklab: Undo double lines (see comment at top of this function)
    text = text.replace(/\n\n/g,"\n");
    return text;
}

var hashElement = function(wholeMatch,m1) {
    var blockText = m1;

    // Undo double lines
    blockText = blockText.replace(/\n\n/g,"\n");
    blockText = blockText.replace(/^\n/,"");

    // strip trailing blank lines
    blockText = blockText.replace(/\n+$/g,"");

    // Replace the element text with a marker ("~KxK" where x is its key)
    blockText = "\n\n~K" + (g_html_blocks.push(blockText)-1) + "K\n\n";

    return blockText;
};

var _RunBlockGamut = function(text) {
//
// These are all the transformations that form block-level
// tags like paragraphs, headers, and list items.
//
    text = _DoHeaders(text);

    // Do Horizontal Rules:
    var key = hashBlock("<hr />");
    text = text.replace(/^[ ]{0,2}([ ]?\*[ ]?){3,}[ \t]*$/gm,key);
    text = text.replace(/^[ ]{0,2}([ ]?\-[ ]?){3,}[ \t]*$/gm,key);
    text = text.replace(/^[ ]{0,2}([ ]?\_[ ]?){3,}[ \t]*$/gm,key);

    text = _DoLists(text);
    text = _DoCodeBlocks(text);
    text = _DoBlockQuotes(text);

    // We already ran _HashHTMLBlocks() before, in Markdown(), but that
    // was to escape raw HTML in the original Markdown source. This time,
    // we're escaping the markup we've just created, so that we don't wrap
    // <p> tags around block-level tags.
    text = _HashHTMLBlocks(text);
    text = _FormParagraphs(text);

    return text;
};


var _RunSpanGamut = function(text) {
//
// These are all the transformations that occur *within* block-level
// tags like paragraphs, headers, and list items.
//

    text = _DoCodeSpans(text);
    text = _EscapeSpecialCharsWithinTagAttributes(text);
    text = _EncodeBackslashEscapes(text);

    // Process anchor and image tags. Images must come first,
    // because ![foo][f] looks like an anchor.
    text = _DoImages(text);
    text = _DoAnchors(text);

    // Make links out of things like `<http://example.com/>`
    // Must come after _DoAnchors(), because you can use < and >
    // delimiters in inline links like [this](<url>).
    text = _DoAutoLinks(text);
    text = _EncodeAmpsAndAngles(text);
    text = _DoItalicsAndBold(text);

    // Do hard breaks:
    text = text.replace(/  +\n/g," <br />\n");

    return text;
}

var _EscapeSpecialCharsWithinTagAttributes = function(text) {
//
// Within tags -- meaning between < and > -- encode [\ ` * _] so they
// don't conflict with their use in Markdown for code, italics and strong.
//

    // Build a regex to find HTML tags and comments.  See Friedl's
    // "Mastering Regular Expressions", 2nd Ed., pp. 200-201.
    var regex = /(<[a-z\/!$]("[^"]*"|'[^']*'|[^'">])*>|<!(--.*?--\s*)+>)/gi;

    text = text.replace(regex, function(wholeMatch) {
        var tag = wholeMatch.replace(/(.)<\/?code>(?=.)/g,"$1`");
        tag = escapeCharacters(tag,"\\`*_");
        return tag;
    });

    return text;
}

var _DoAnchors = function(text) {
//
// Turn Markdown link shortcuts into XHTML <a> tags.
//
    //
    // First, handle reference-style links: [link text] [id]
    //

    /*
        text = text.replace(/
        (                           // wrap whole match in $1
            \[
            (
                (?:
                    \[[^\]]*\]      // allow brackets nested one level
                    |
                    [^\[]           // or anything else
                )*
            )
            \]

            [ ]?                    // one optional space
            (?:\n[ ]*)?             // one optional newline followed by spaces

            \[
            (.*?)                   // id = $3
            \]
        )()()()()                   // pad remaining backreferences
        /g,_DoAnchors_callback);
    */
    text = text.replace(/(\[((?:\[[^\]]*\]|[^\[\]])*)\][ ]?(?:\n[ ]*)?\[(.*?)\])()()()()/g,writeAnchorTag);

    //
    // Next, inline-style links: [link text](url "optional title")
    //

    /*
        text = text.replace(/
            (                       // wrap whole match in $1
                \[
                (
                    (?:
                        \[[^\]]*\]  // allow brackets nested one level
                    |
                    [^\[\]]         // or anything else
                )
            )
            \]
            \(                      // literal paren
            [ \t]*
            ()                      // no id, so leave $3 empty
            <?(.*?)>?               // href = $4
            [ \t]*
            (                       // $5
                (['"])              // quote char = $6
                (.*?)               // Title = $7
                \6                  // matching quote
                [ \t]*              // ignore any spaces/tabs between closing quote and )
            )?                      // title is optional
            \)
        )
        /g,writeAnchorTag);
    */
    text = text.replace(/(\[((?:\[[^\]]*\]|[^\[\]])*)\]\([ \t]*()<?(.*?(?:\(.*?\).*?)?)>?[ \t]*((['"])(.*?)\6[ \t]*)?\))/g,writeAnchorTag);

    //
    // Last, handle reference-style shortcuts: [link text]
    // These must come last in case you've also got [link test][1]
    // or [link test](/foo)
    //

    /*
        text = text.replace(/
        (                           // wrap whole match in $1
            \[
            ([^\[\]]+)              // link text = $2; can't contain '[' or ']'
            \]
        )()()()()()                 // pad rest of backreferences
        /g, writeAnchorTag);
    */
    text = text.replace(/(\[([^\[\]]+)\])()()()()()/g, writeAnchorTag);

    return text;
}

var writeAnchorTag = function(wholeMatch,m1,m2,m3,m4,m5,m6,m7) {
    if (m7 == undefined) m7 = "";
    var whole_match = m1;
    var link_text   = m2;
    var link_id  = m3.toLowerCase();
    var url     = m4;
    var title   = m7;

    if (url == "") {
        if (link_id == "") {
            // lower-case and turn embedded newlines into spaces
            link_id = link_text.toLowerCase().replace(/ ?\n/g," ");
        }
        url = "#"+link_id;

        if (g_urls[link_id] != undefined) {
            url = g_urls[link_id];
            if (g_titles[link_id] != undefined) {
                title = g_titles[link_id];
            }
        }
        else {
            if (whole_match.search(/\(\s*\)$/m)>-1) {
                // Special case for explicit empty url
                url = "";
            } else {
                return whole_match;
            }
        }
    }

    url = escapeCharacters(url,"*_");
    var result = "<a href=\"" + url + "\"";

    if (title != "") {
        title = title.replace(/"/g,"&quot;");
        title = escapeCharacters(title,"*_");
        result +=  " title=\"" + title + "\"";
    }

    result += ">" + link_text + "</a>";

    return result;
}


var _DoImages = function(text) {
//
// Turn Markdown image shortcuts into <img> tags.
//

    //
    // First, handle reference-style labeled images: ![alt text][id]
    //

    /*
        text = text.replace(/
        (                       // wrap whole match in $1
            !\[
            (.*?)               // alt text = $2
            \]

            [ ]?                // one optional space
            (?:\n[ ]*)?         // one optional newline followed by spaces

            \[
            (.*?)               // id = $3
            \]
        )()()()()               // pad rest of backreferences
        /g,writeImageTag);
    */
    text = text.replace(/(!\[(.*?)\][ ]?(?:\n[ ]*)?\[(.*?)\])()()()()/g,writeImageTag);

    //
    // Next, handle inline images:  ![alt text](url "optional title")
    // Don't forget: encode * and _

    /*
        text = text.replace(/
        (                       // wrap whole match in $1
            !\[
            (.*?)               // alt text = $2
            \]
            \s?                 // One optional whitespace character
            \(                  // literal paren
            [ \t]*
            ()                  // no id, so leave $3 empty
            <?(\S+?)>?          // src url = $4
            [ \t]*
            (                   // $5
                (['"])          // quote char = $6
                (.*?)           // title = $7
                \6              // matching quote
                [ \t]*
            )?                  // title is optional
        \)
        )
        /g,writeImageTag);
    */
    text = text.replace(/(!\[(.*?)\]\s?\([ \t]*()<?(\S+?)>?[ \t]*((['"])(.*?)\6[ \t]*)?\))/g,writeImageTag);

    return text;
}

var writeImageTag = function(wholeMatch,m1,m2,m3,m4,m5,m6,m7) {
    var whole_match = m1;
    var alt_text   = m2;
    var link_id  = m3.toLowerCase();
    var url     = m4;
    var title   = m7;

    if (!title) title = "";

    if (url == "") {
        if (link_id == "") {
            // lower-case and turn embedded newlines into spaces
            link_id = alt_text.toLowerCase().replace(/ ?\n/g," ");
        }
        url = "#"+link_id;

        if (g_urls[link_id] != undefined) {
            url = g_urls[link_id];
            if (g_titles[link_id] != undefined) {
                title = g_titles[link_id];
            }
        }
        else {
            return whole_match;
        }
    }

    alt_text = alt_text.replace(/"/g,"&quot;");
    url = escapeCharacters(url,"*_");
    var result = "<img src=\"" + url + "\" alt=\"" + alt_text + "\"";

    // attacklab: Markdown.pl adds empty title attributes to images.
    // Replicate this bug.

    //if (title != "") {
        title = title.replace(/"/g,"&quot;");
        title = escapeCharacters(title,"*_");
        result +=  " title=\"" + title + "\"";
    //}

    result += " />";

    return result;
}


var _DoHeaders = function(text) {

    // Setext-style headers:
    //  Header 1
    //  ========
    //
    //  Header 2
    //  --------
    //
    text = text.replace(/^(.+)[ \t]*\n=+[ \t]*\n+/gm,
        function(wholeMatch,m1){return hashBlock('<h1 id="' + headerId(m1) + '">' + _RunSpanGamut(m1) + "</h1>");});

    text = text.replace(/^(.+)[ \t]*\n-+[ \t]*\n+/gm,
        function(matchFound,m1){return hashBlock('<h2 id="' + headerId(m1) + '">' + _RunSpanGamut(m1) + "</h2>");});

    // atx-style headers:
    //  # Header 1
    //  ## Header 2
    //  ## Header 2 with closing hashes ##
    //  ...
    //  ###### Header 6
    //

    /*
        text = text.replace(/
            ^(\#{1,6})              // $1 = string of #'s
            [ \t]*
            (.+?)                   // $2 = Header text
            [ \t]*
            \#*                     // optional closing #'s (not counted)
            \n+
        /gm, function() {...});
    */

    text = text.replace(/^(\#{1,6})[ \t]*(.+?)[ \t]*\#*\n+/gm,
        function(wholeMatch,m1,m2) {
            var h_level = m1.length;
            return hashBlock("<h" + h_level + ' id="' + headerId(m2) + '">' + _RunSpanGamut(m2) + "</h" + h_level + ">");
        });

    function headerId(m) {
        return m.replace(/[^\w]/g, '').toLowerCase();
    }
    return text;
}

// This declaration keeps Dojo compressor from outputting garbage:
var _ProcessListItems;

var _DoLists = function(text) {
//
// Form HTML ordered (numbered) and unordered (bulleted) lists.
//

    // attacklab: add sentinel to hack around khtml/safari bug:
    // http://bugs.webkit.org/show_bug.cgi?id=11231
    text += "~0";

    // Re-usable pattern to match any entirel ul or ol list:

    /*
        var whole_list = /
        (                                   // $1 = whole list
            (                               // $2
                [ ]{0,3}                    // attacklab: g_tab_width - 1
                ([*+-]|\d+[.])              // $3 = first list item marker
                [ \t]+
            )
            [^\r]+?
            (                               // $4
                ~0                          // sentinel for workaround; should be $
            |
                \n{2,}
                (?=\S)
                (?!                         // Negative lookahead for another list item marker
                    [ \t]*
                    (?:[*+-]|\d+[.])[ \t]+
                )
            )
        )/g
    */
    var whole_list = /^(([ ]{0,3}([*+-]|\d+[.])[ \t]+)[^\r]+?(~0|\n{2,}(?=\S)(?![ \t]*(?:[*+-]|\d+[.])[ \t]+)))/gm;

    if (g_list_level) {
        text = text.replace(whole_list,function(wholeMatch,m1,m2) {
            var list = m1;
            var list_type = (m2.search(/[*+-]/g)>-1) ? "ul" : "ol";

            // Turn double returns into triple returns, so that we can make a
            // paragraph for the last item in a list, if necessary:
            list = list.replace(/\n{2,}/g,"\n\n\n");;
            var result = _ProcessListItems(list);

            // Trim any trailing whitespace, to put the closing `</$list_type>`
            // up on the preceding line, to get it past the current stupid
            // HTML block parser. This is a hack to work around the terrible
            // hack that is the HTML block parser.
            result = result.replace(/\s+$/,"");
            result = "<"+list_type+">" + result + "</"+list_type+">\n";
            return result;
        });
    } else {
        whole_list = /(\n\n|^\n?)(([ ]{0,3}([*+-]|\d+[.])[ \t]+)[^\r]+?(~0|\n{2,}(?=\S)(?![ \t]*(?:[*+-]|\d+[.])[ \t]+)))/g;
        text = text.replace(whole_list,function(wholeMatch,m1,m2,m3) {
            var runup = m1;
            var list = m2;

            var list_type = (m3.search(/[*+-]/g)>-1) ? "ul" : "ol";
            // Turn double returns into triple returns, so that we can make a
            // paragraph for the last item in a list, if necessary:
            var list = list.replace(/\n{2,}/g,"\n\n\n");;
            var result = _ProcessListItems(list);
            result = runup + "<"+list_type+">\n" + result + "</"+list_type+">\n";
            return result;
        });
    }

    // attacklab: strip sentinel
    text = text.replace(/~0/,"");

    return text;
}

_ProcessListItems = function(list_str) {
//
//  Process the contents of a single ordered or unordered list, splitting it
//  into individual list items.
//
    // The $g_list_level global keeps track of when we're inside a list.
    // Each time we enter a list, we increment it; when we leave a list,
    // we decrement. If it's zero, we're not in a list anymore.
    //
    // We do this because when we're not inside a list, we want to treat
    // something like this:
    //
    //    I recommend upgrading to version
    //    8. Oops, now this line is treated
    //    as a sub-list.
    //
    // As a single paragraph, despite the fact that the second line starts
    // with a digit-period-space sequence.
    //
    // Whereas when we're inside a list (or sub-list), that line will be
    // treated as the start of a sub-list. What a kludge, huh? This is
    // an aspect of Markdown's syntax that's hard to parse perfectly
    // without resorting to mind-reading. Perhaps the solution is to
    // change the syntax rules such that sub-lists must start with a
    // starting cardinal number; e.g. "1." or "a.".

    g_list_level++;

    // trim trailing blank lines:
    list_str = list_str.replace(/\n{2,}$/,"\n");

    // attacklab: add sentinel to emulate \z
    list_str += "~0";

    /*
        list_str = list_str.replace(/
            (\n)?                           // leading line = $1
            (^[ \t]*)                       // leading whitespace = $2
            ([*+-]|\d+[.]) [ \t]+           // list marker = $3
            ([^\r]+?                        // list item text   = $4
            (\n{1,2}))
            (?= \n* (~0 | \2 ([*+-]|\d+[.]) [ \t]+))
        /gm, function(){...});
    */
    list_str = list_str.replace(/(\n)?(^[ \t]*)([*+-]|\d+[.])[ \t]+([^\r]+?(\n{1,2}))(?=\n*(~0|\2([*+-]|\d+[.])[ \t]+))/gm,
        function(wholeMatch,m1,m2,m3,m4){
            var item = m4;
            var leading_line = m1;
            var leading_space = m2;

            if (leading_line || (item.search(/\n{2,}/)>-1)) {
                item = _RunBlockGamut(_Outdent(item));
            }
            else {
                // Recursion for sub-lists:
                item = _DoLists(_Outdent(item));
                item = item.replace(/\n$/,""); // chomp(item)
                item = _RunSpanGamut(item);
            }

            return  "<li>" + item + "</li>\n";
        }
    );

    // attacklab: strip sentinel
    list_str = list_str.replace(/~0/g,"");

    g_list_level--;
    return list_str;
}


var _DoCodeBlocks = function(text) {
//
//  Process Markdown `<pre><code>` blocks.
//

    /*
        text = text.replace(text,
            /(?:\n\n|^)
            (                               // $1 = the code block -- one or more lines, starting with a space/tab
                (?:
                    (?:[ ]{4}|\t)           // Lines must start with a tab or a tab-width of spaces - attacklab: g_tab_width
                    .*\n+
                )+
            )
            (\n*[ ]{0,3}[^ \t\n]|(?=~0))    // attacklab: g_tab_width
        /g,function(){...});
    */

    // attacklab: sentinel workarounds for lack of \A and \Z, safari\khtml bug
    text += "~0";

    text = text.replace(/(?:\n\n|^)((?:(?:[ ]{4}|\t).*\n+)+)(\n*[ ]{0,3}[^ \t\n]|(?=~0))/g,
        function(wholeMatch,m1,m2) {
            var codeblock = m1;
            var nextChar = m2;

            codeblock = _EncodeCode( _Outdent(codeblock));
            codeblock = _Detab(codeblock);
            codeblock = codeblock.replace(/^\n+/g,""); // trim leading newlines
            codeblock = codeblock.replace(/\n+$/g,""); // trim trailing whitespace

            codeblock = "<pre><code>" + codeblock + "\n</code></pre>";

            return hashBlock(codeblock) + nextChar;
        }
    );

    // attacklab: strip sentinel
    text = text.replace(/~0/,"");

    return text;
};

var _DoGithubCodeBlocks = function(text) {
//
//  Process Github-style code blocks
//  Example:
//  ```ruby
//  def hello_world(x)
//    puts "Hello, #{x}"
//  end
//  ```
//


    // attacklab: sentinel workarounds for lack of \A and \Z, safari\khtml bug
    text += "~0";

    text = text.replace(/(?:^|\n)```(.*)\n([\s\S]*?)\n```/g,
        function(wholeMatch,m1,m2) {
            var language = m1;
            var codeblock = m2;

            codeblock = _EncodeCode(codeblock);
            codeblock = _Detab(codeblock);
            codeblock = codeblock.replace(/^\n+/g,""); // trim leading newlines
            codeblock = codeblock.replace(/\n+$/g,""); // trim trailing whitespace

            codeblock = "<pre><code" + (language ? " class=\"lang-" + language + '"' : "") + ">" + codeblock + "\n</code></pre>";

            return hashBlock(codeblock);
        }
    );

    // attacklab: strip sentinel
    text = text.replace(/~0/,"");

    return text;
}

var hashBlock = function(text) {
    text = text.replace(/(^\n+|\n+$)/g,"");
    return "\n\n~K" + (g_html_blocks.push(text)-1) + "K\n\n";
}

var _DoCodeSpans = function(text) {
//
//   *  Backtick quotes are used for <code></code> spans.
//
//   *  You can use multiple backticks as the delimiters if you want to
//   include literal backticks in the code span. So, this input:
//
//       Just type ``foo `bar` baz`` at the prompt.
//
//     Will translate to:
//
//       <p>Just type <code>foo `bar` baz</code> at the prompt.</p>
//
//  There's no arbitrary limit to the number of backticks you
//  can use as delimters. If you need three consecutive backticks
//  in your code, use four for delimiters, etc.
//
//  *  You can use spaces to get literal backticks at the edges:
//
//       ... type `` `bar` `` ...
//
//     Turns to:
//
//       ... type <code>`bar`</code> ...
//

    /*
        text = text.replace(/
            (^|[^\\])                   // Character before opening ` can't be a backslash
            (`+)                        // $2 = Opening run of `
            (                           // $3 = The code block
                [^\r]*?
                [^`]                    // attacklab: work around lack of lookbehind
            )
            \2                          // Matching closer
            (?!`)
        /gm, function(){...});
    */

    text = text.replace(/(^|[^\\])(`+)([^\r]*?[^`])\2(?!`)/gm,
        function(wholeMatch,m1,m2,m3,m4) {
            var c = m3;
            c = c.replace(/^([ \t]*)/g,""); // leading whitespace
            c = c.replace(/[ \t]*$/g,"");   // trailing whitespace
            c = _EncodeCode(c);
            return m1+"<code>"+c+"</code>";
        });

    return text;
}

var _EncodeCode = function(text) {
//
// Encode/escape certain characters inside Markdown code runs.
// The point is that in code, these characters are literals,
// and lose their special Markdown meanings.
//
    // Encode all ampersands; HTML entities are not
    // entities within a Markdown code span.
    text = text.replace(/&/g,"&amp;");

    // Do the angle bracket song and dance:
    text = text.replace(/</g,"&lt;");
    text = text.replace(/>/g,"&gt;");

    // Now, escape characters that are magic in Markdown:
    text = escapeCharacters(text,"\*_{}[]\\",false);

// jj the line above breaks this:
//---

//* Item

//   1. Subitem

//            special char: *
//---

    return text;
}


var _DoItalicsAndBold = function(text) {

    // <strong> must go first:
    text = text.replace(/(\*\*|__)(?=\S)([^\r]*?\S[*_]*)\1/g,
        "<strong>$2</strong>");

    text = text.replace(/(\*|_)(?=\S)([^\r]*?\S)\1/g,
        "<em>$2</em>");

    return text;
}


var _DoBlockQuotes = function(text) {

    /*
        text = text.replace(/
        (                               // Wrap whole match in $1
            (
                ^[ \t]*>[ \t]?          // '>' at the start of a line
                .+\n                    // rest of the first line
                (.+\n)*                 // subsequent consecutive lines
                \n*                     // blanks
            )+
        )
        /gm, function(){...});
    */

    text = text.replace(/((^[ \t]*>[ \t]?.+\n(.+\n)*\n*)+)/gm,
        function(wholeMatch,m1) {
            var bq = m1;

            // attacklab: hack around Konqueror 3.5.4 bug:
            // "----------bug".replace(/^-/g,"") == "bug"

            bq = bq.replace(/^[ \t]*>[ \t]?/gm,"~0");   // trim one level of quoting

            // attacklab: clean up hack
            bq = bq.replace(/~0/g,"");

            bq = bq.replace(/^[ \t]+$/gm,"");       // trim whitespace-only lines
            bq = _RunBlockGamut(bq);                // recurse

            bq = bq.replace(/(^|\n)/g,"$1  ");
            // These leading spaces screw with <pre> content, so we need to fix that:
            bq = bq.replace(
                    /(\s*<pre>[^\r]+?<\/pre>)/gm,
                function(wholeMatch,m1) {
                    var pre = m1;
                    // attacklab: hack around Konqueror 3.5.4 bug:
                    pre = pre.replace(/^  /mg,"~0");
                    pre = pre.replace(/~0/g,"");
                    return pre;
                });

            return hashBlock("<blockquote>\n" + bq + "\n</blockquote>");
        });
    return text;
}


var _FormParagraphs = function(text) {
//
//  Params:
//    $text - string to process with html <p> tags
//

    // Strip leading and trailing lines:
    text = text.replace(/^\n+/g,"");
    text = text.replace(/\n+$/g,"");

    var grafs = text.split(/\n{2,}/g);
    var grafsOut = [];

    //
    // Wrap <p> tags.
    //
    var end = grafs.length;
    for (var i=0; i<end; i++) {
        var str = grafs[i];

        // if this is an HTML marker, copy it
        if (str.search(/~K(\d+)K/g) >= 0) {
            grafsOut.push(str);
        }
        else if (str.search(/\S/) >= 0) {
            str = _RunSpanGamut(str);
            str = str.replace(/^([ \t]*)/g,"<p>");
            str += "</p>"
            grafsOut.push(str);
        }

    }

    //
    // Unhashify HTML blocks
    //
    end = grafsOut.length;
    for (var i=0; i<end; i++) {
        // if this is a marker for an html block...
        while (grafsOut[i].search(/~K(\d+)K/) >= 0) {
            var blockText = g_html_blocks[RegExp.$1];
            blockText = blockText.replace(/\$/g,"$$$$"); // Escape any dollar signs
            grafsOut[i] = grafsOut[i].replace(/~K\d+K/,blockText);
        }
    }

    return grafsOut.join("\n\n");
}


var _EncodeAmpsAndAngles = function(text) {
// Smart processing for ampersands and angle brackets that need to be encoded.

    // Ampersand-encoding based entirely on Nat Irons's Amputator MT plugin:
    //   http://bumppo.net/projects/amputator/
    text = text.replace(/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/g,"&amp;");

    // Encode naked <'s
    text = text.replace(/<(?![a-z\/?\$!])/gi,"&lt;");

    return text;
}


var _EncodeBackslashEscapes = function(text) {
//
//   Parameter:  String.
//   Returns:   The string, with after processing the following backslash
//             escape sequences.
//

    // attacklab: The polite way to do this is with the new
    // escapeCharacters() function:
    //
    //  text = escapeCharacters(text,"\\",true);
    //  text = escapeCharacters(text,"`*_{}[]()>#+-.!",true);
    //
    // ...but we're sidestepping its use of the (slow) RegExp constructor
    // as an optimization for Firefox.  This function gets called a LOT.

    text = text.replace(/\\(\\)/g,escapeCharacters_callback);
    text = text.replace(/\\([`*_{}\[\]()>#+-.!])/g,escapeCharacters_callback);
    return text;
}


var _handleTrailingParens = function(wholeMatch, lookbehind, protocol, link) {
    if (lookbehind)
        return wholeMatch;
    if (link.charAt(link.length - 1) !== ")")
        return "<" + protocol + link + ">";
    var parens = link.match(/[()]/g);
    var level = 0;
    for (var i = 0; i < parens.length; i++) {
        if (parens[i] === "(") {
            if (level <= 0)
                level = 1;
            else
                level++;
        }
        else {
            level--;
        }
    }
    var tail = "";
    if (level < 0) {
        var re = new RegExp("\\){1," + (-level) + "}$");
        link = link.replace(re, function (trailingParens) {
                tail = trailingParens;
                return "";
                });
    }

    // console.log(protocol, link, tail);

    return "<" + protocol + link + ">" + tail;
}

var _DoAutoLinks = function(text) {

    // text = text.replace(/<((https?|ftp|dict):[^'">\s]+)>/gi,"<a href=\"$1\">$1</a>");
    text = text.replace(/(="|<)?\b(https?|ftp)(:\/\/[-A-Z0-9+&@#\/%?=~_|\[\]\(\)!:,\.;]*[-A-Z0-9+&@#\/%=~_|\[\])])(?=$|\W)/gi, _handleTrailingParens);
    var replacer = function (wholematch, m1) { return "<a href=\"" + m1 + "\">" + m1 + "</a>"; }
    text = text.replace(/<((https?|ftp):[^'">\s]+)>/gi, replacer);

    // Email addresses: <address@domain.foo>

    /*
        text = text.replace(/
            <
            (?:mailto:)?
            (
                [-.\w]+
                \@
                [-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]+
            )
            >
        /gi, _DoAutoLinks_callback());
    */
    text = text.replace(/<(?:mailto:)?([-.\w]+\@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]+)>/gi,
        function(wholeMatch,m1) {
            return _EncodeEmailAddress( _UnescapeSpecialChars(m1) );
        }
    );

    return text;
}


var _EncodeEmailAddress = function(addr) {
//
//  Input: an email address, e.g. "foo@example.com"
//
//  Output: the email address as a mailto link, with each character
//  of the address encoded as either a decimal or hex entity, in
//  the hopes of foiling most address harvesting spam bots. E.g.:
//
//  <a href="&#x6D;&#97;&#105;&#108;&#x74;&#111;:&#102;&#111;&#111;&#64;&#101;
//     x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;">&#102;&#111;&#111;
//     &#64;&#101;x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;</a>
//
//  Based on a filter by Matthew Wickline, posted to the BBEdit-Talk
//  mailing list: <http://tinyurl.com/yu7ue>
//

    var encode = [
        function(ch){return "&#"+ch.charCodeAt(0)+";";},
        function(ch){return "&#x"+ch.charCodeAt(0).toString(16)+";";},
        function(ch){return ch;}
    ];

    addr = "mailto:" + addr;

    addr = addr.replace(/./g, function(ch) {
        if (ch == "@") {
            // this *must* be encoded. I insist.
            ch = encode[Math.floor(Math.random()*2)](ch);
        } else if (ch !=":") {
            // leave ':' alone (to spot mailto: later)
            var r = Math.random();
            // roughly 10% raw, 45% hex, 45% dec
            ch =  (
                    r > .9  ?   encode[2](ch)   :
                    r > .45 ?   encode[1](ch)   :
                                encode[0](ch)
                );
        }
        return ch;
    });

    addr = "<a href=\"" + addr + "\">" + addr + "</a>";
    addr = addr.replace(/">.+:/g,"\">"); // strip the mailto: from the visible part

    return addr;
}


var _UnescapeSpecialChars = function(text) {
//
// Swap back in all the special characters we've hidden.
//
    text = text.replace(/~E(\d+)E/g,
        function(wholeMatch,m1) {
            var charCodeToReplace = parseInt(m1);
            return String.fromCharCode(charCodeToReplace);
        }
    );
    return text;
}


var _Outdent = function(text) {
//
// Remove one level of line-leading tabs or spaces
//

    // attacklab: hack around Konqueror 3.5.4 bug:
    // "----------bug".replace(/^-/g,"") == "bug"

    text = text.replace(/^(\t|[ ]{1,4})/gm,"~0"); // attacklab: g_tab_width

    // attacklab: clean up hack
    text = text.replace(/~0/g,"")

    return text;
}

var _Detab = function(text) {
// attacklab: Detab's completely rewritten for speed.
// In perl we could fix it by anchoring the regexp with \G.
// In javascript we're less fortunate.

    // expand first n-1 tabs
    text = text.replace(/\t(?=\t)/g,"    "); // attacklab: g_tab_width

    // replace the nth with two sentinels
    text = text.replace(/\t/g,"~A~B");

    // use the sentinel to anchor our regex so it doesn't explode
    text = text.replace(/~B(.+?)~A/g,
        function(wholeMatch,m1,m2) {
            var leadingText = m1;
            var numSpaces = 4 - leadingText.length % 4;  // attacklab: g_tab_width

            // there *must* be a better way to do this:
            for (var i=0; i<numSpaces; i++) leadingText+=" ";

            return leadingText;
        }
    );

    // clean up sentinels
    text = text.replace(/~A/g,"    ");  // attacklab: g_tab_width
    text = text.replace(/~B/g,"");

    return text;
}


//
//  attacklab: Utility functions
//


var escapeCharacters = function(text, charsToEscape, afterBackslash) {
    // First we have to escape the escape characters so that
    // we can build a character class out of them
    var regexString = "([" + charsToEscape.replace(/([\[\]\\])/g,"\\$1") + "])";

    if (afterBackslash) {
        regexString = "\\\\" + regexString;
    }

    var regex = new RegExp(regexString,"g");
    text = text.replace(regex,escapeCharacters_callback);

    return text;
}


var escapeCharacters_callback = function(wholeMatch,m1) {
    var charCodeToEscape = m1.charCodeAt(0);
    return "~E"+charCodeToEscape+"E";
}

} // end of Showdown.converter


// export
if (typeof module !== 'undefined') module.exports = Showdown;

// stolen from AMD branch of underscore
// AMD define happens at the end for compatibility with AMD loaders
// that don't enforce next-turn semantics on modules.
if (typeof define === 'function' && define.amd) {
    define('showdown', function() {
        return Showdown;
    });
}


var Markdown;

if (typeof exports === "object" && typeof require === "function") // we're in a CommonJS (e.g. Node.js) module
    Markdown = exports;
else
    Markdown = {};

// The following text is included for historical reasons, but should
// be taken with a pinch of salt; it's not all true anymore.

//
// Wherever possible, Showdown is a straight, line-by-line port
// of the Perl version of Markdown.
//
// This is not a normal parser design; it's basically just a
// series of string substitutions.  It's hard to read and
// maintain this way,  but keeping Showdown close to the original
// design makes it easier to port new features.
//
// More importantly, Showdown behaves like markdown.pl in most
// edge cases.  So web applications can do client-side preview
// in Javascript, and then build identical HTML on the server.
//
// This port needs the new RegExp functionality of ECMA 262,
// 3rd Edition (i.e. Javascript 1.5).  Most modern web browsers
// should do fine.  Even with the new regular expression features,
// We do a lot of work to emulate Perl's regex functionality.
// The tricky changes in this file mostly have the "attacklab:"
// label.  Major or self-explanatory changes don't.
//
// Smart diff tools like Araxis Merge will be able to match up
// this file with markdown.pl in a useful way.  A little tweaking
// helps: in a copy of markdown.pl, replace "#" with "//" and
// replace "$text" with "text".  Be sure to ignore whitespace
// and line endings.
//


//
// Usage:
//
//   var text = "Markdown *rocks*.";
//
//   var converter = new Markdown.Converter();
//   var html = converter.makeHtml(text);
//
//   alert(html);
//
// Note: move the sample code to the bottom of this
// file before uncommenting it.
//

(function () {

    function identity(x) { return x; }
    function returnFalse(x) { return false; }

    function HookCollection() { }

    HookCollection.prototype = {

        chain: function (hookname, func) {
            var original = this[hookname];
            if (!original)
                throw new Error("unknown hook " + hookname);

            if (original === identity)
                this[hookname] = func;
            else
                this[hookname] = function (text) {
                    var args = Array.prototype.slice.call(arguments, 0);
                    args[0] = original.apply(null, args);
                    return func.apply(null, args);
                };
        },
        set: function (hookname, func) {
            if (!this[hookname])
                throw new Error("unknown hook " + hookname);
            this[hookname] = func;
        },
        addNoop: function (hookname) {
            this[hookname] = identity;
        },
        addFalse: function (hookname) {
            this[hookname] = returnFalse;
        }
    };

    Markdown.HookCollection = HookCollection;

    // g_urls and g_titles allow arbitrary user-entered strings as keys. This
    // caused an exception (and hence stopped the rendering) when the user entered
    // e.g. [push] or [__proto__]. Adding a prefix to the actual key prevents this
    // (since no builtin property starts with "s_"). See
    // http://meta.stackoverflow.com/questions/64655/strange-wmd-bug
    // (granted, switching from Array() to Object() alone would have left only __proto__
    // to be a problem)
    function SaveHash() { }
    SaveHash.prototype = {
        set: function (key, value) {
            this["s_" + key] = value;
        },
        get: function (key) {
            return this["s_" + key];
        }
    };

    Markdown.Converter = function () {
        var pluginHooks = this.hooks = new HookCollection();

        // given a URL that was encountered by itself (without markup), should return the link text that's to be given to this link
        pluginHooks.addNoop("plainLinkText");

        // called with the orignal text as given to makeHtml. The result of this plugin hook is the actual markdown source that will be cooked
        pluginHooks.addNoop("preConversion");

        // called with the text once all normalizations have been completed (tabs to spaces, line endings, etc.), but before any conversions have
        pluginHooks.addNoop("postNormalization");

        // Called with the text before / after creating block elements like code blocks and lists. Note that this is called recursively
        // with inner content, e.g. it's called with the full text, and then only with the content of a blockquote. The inner
        // call will receive outdented text.
        pluginHooks.addNoop("preBlockGamut");
        pluginHooks.addNoop("postBlockGamut");

        // called with the text of a single block element before / after the span-level conversions (bold, code spans, etc.) have been made
        pluginHooks.addNoop("preSpanGamut");
        pluginHooks.addNoop("postSpanGamut");

        // called with the final cooked HTML code. The result of this plugin hook is the actual output of makeHtml
        pluginHooks.addNoop("postConversion");

        //
        // Private state of the converter instance:
        //

        // Global hashes, used by various utility routines
        var g_urls;
        var g_titles;
        var g_html_blocks;

        // Used to track when we're inside an ordered or unordered list
        // (see _ProcessListItems() for details):
        var g_list_level;

        this.makeHtml = function (text) {

            //
            // Main function. The order in which other subs are called here is
            // essential. Link and image substitutions need to happen before
            // _EscapeSpecialCharsWithinTagAttributes(), so that any *'s or _'s in the <a>
            // and <img> tags get encoded.
            //

            // This will only happen if makeHtml on the same converter instance is called from a plugin hook.
            // Don't do that.
            if (g_urls)
                throw new Error("Recursive call to converter.makeHtml");

            // Create the private state objects.
            g_urls = new SaveHash();
            g_titles = new SaveHash();
            g_html_blocks = [];
            g_list_level = 0;

            text = pluginHooks.preConversion(text);

            // attacklab: Replace ~ with ~T
            // This lets us use tilde as an escape char to avoid md5 hashes
            // The choice of character is arbitray; anything that isn't
            // magic in Markdown will work.
            text = text.replace(/~/g, "~T");

            // attacklab: Replace $ with ~D
            // RegExp interprets $ as a special character
            // when it's in a replacement string
            text = text.replace(/\$/g, "~D");

            // Standardize line endings
            text = text.replace(/\r\n/g, "\n"); // DOS to Unix
            text = text.replace(/\r/g, "\n"); // Mac to Unix

            // Make sure text begins and ends with a couple of newlines:
            text = "\n\n" + text + "\n\n";

            // Convert all tabs to spaces.
            text = _Detab(text);

            // Strip any lines consisting only of spaces and tabs.
            // This makes subsequent regexen easier to write, because we can
            // match consecutive blank lines with /\n+/ instead of something
            // contorted like /[ \t]*\n+/ .
            text = text.replace(/^[ \t]+$/mg, "");

            text = pluginHooks.postNormalization(text);

            // Turn block-level HTML blocks into hash entries
            text = _HashHTMLBlocks(text);

            // Strip link definitions, store in hashes.
            text = _StripLinkDefinitions(text);

            text = _RunBlockGamut(text);

            text = _UnescapeSpecialChars(text);

            // attacklab: Restore dollar signs
            text = text.replace(/~D/g, "$$");

            // attacklab: Restore tildes
            text = text.replace(/~T/g, "~");

            text = pluginHooks.postConversion(text);

            g_html_blocks = g_titles = g_urls = null;

            return text;
        };

        function _StripLinkDefinitions(text) {
            //
            // Strips link definitions from text, stores the URLs and titles in
            // hash references.
            //

            // Link defs are in the form: ^[id]: url "optional title"

            /*
            text = text.replace(/
                ^[ ]{0,3}\[(.+)\]:  // id = $1  attacklab: g_tab_width - 1
                [ \t]*
                \n?                 // maybe *one* newline
                [ \t]*
                <?(\S+?)>?          // url = $2
                (?=\s|$)            // lookahead for whitespace instead of the lookbehind removed below
                [ \t]*
                \n?                 // maybe one newline
                [ \t]*
                (                   // (potential) title = $3
                    (\n*)           // any lines skipped = $4 attacklab: lookbehind removed
                    [ \t]+
                    ["(]
                    (.+?)           // title = $5
                    [")]
                    [ \t]*
                )?                  // title is optional
                (?:\n+|$)
            /gm, function(){...});
            */

            text = text.replace(/^[ ]{0,3}\[(.+)\]:[ \t]*\n?[ \t]*<?(\S+?)>?(?=\s|$)[ \t]*\n?[ \t]*((\n*)["(](.+?)[")][ \t]*)?(?:\n+)/gm,
                function (wholeMatch, m1, m2, m3, m4, m5) {
                    m1 = m1.toLowerCase();
                    g_urls.set(m1, _EncodeAmpsAndAngles(m2));  // Link IDs are case-insensitive
                    if (m4) {
                        // Oops, found blank lines, so it's not a title.
                        // Put back the parenthetical statement we stole.
                        return m3;
                    } else if (m5) {
                        g_titles.set(m1, m5.replace(/"/g, "&quot;"));
                    }

                    // Completely remove the definition from the text
                    return "";
                }
            );

            return text;
        }

        function _HashHTMLBlocks(text) {

            // Hashify HTML blocks:
            // We only want to do this for block-level HTML tags, such as headers,
            // lists, and tables. That's because we still want to wrap <p>s around
            // "paragraphs" that are wrapped in non-block-level tags, such as anchors,
            // phrase emphasis, and spans. The list of tags we're looking for is
            // hard-coded:
            var block_tags_a = "p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del"
            var block_tags_b = "p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math"

            // First, look for nested blocks, e.g.:
            //   <div>
            //     <div>
            //     tags for inner block must be indented.
            //     </div>
            //   </div>
            //
            // The outermost tags must start at the left margin for this to match, and
            // the inner nested divs must be indented.
            // We need to do this before the next, more liberal match, because the next
            // match will start at the first `<div>` and stop at the first `</div>`.

            // attacklab: This regex can be expensive when it fails.

            /*
            text = text.replace(/
                (                       // save in $1
                    ^                   // start of line  (with /m)
                    <($block_tags_a)    // start tag = $2
                    \b                  // word break
                                        // attacklab: hack around khtml/pcre bug...
                    [^\r]*?\n           // any number of lines, minimally matching
                    </\2>               // the matching end tag
                    [ \t]*              // trailing spaces/tabs
                    (?=\n+)             // followed by a newline
                )                       // attacklab: there are sentinel newlines at end of document
            /gm,function(){...}};
            */
            text = text.replace(/^(<(p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del)\b[^\r]*?\n<\/\2>[ \t]*(?=\n+))/gm, hashElement);

            //
            // Now match more liberally, simply from `\n<tag>` to `</tag>\n`
            //

            /*
            text = text.replace(/
                (                       // save in $1
                    ^                   // start of line  (with /m)
                    <($block_tags_b)    // start tag = $2
                    \b                  // word break
                                        // attacklab: hack around khtml/pcre bug...
                    [^\r]*?             // any number of lines, minimally matching
                    .*</\2>             // the matching end tag
                    [ \t]*              // trailing spaces/tabs
                    (?=\n+)             // followed by a newline
                )                       // attacklab: there are sentinel newlines at end of document
            /gm,function(){...}};
            */
            text = text.replace(/^(<(p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math)\b[^\r]*?.*<\/\2>[ \t]*(?=\n+)\n)/gm, hashElement);

            // Special case just for <hr />. It was easier to make a special case than
            // to make the other regex more complicated.  

            /*
            text = text.replace(/
                \n                  // Starting after a blank line
                [ ]{0,3}
                (                   // save in $1
                    (<(hr)          // start tag = $2
                        \b          // word break
                        ([^<>])*?
                    \/?>)           // the matching end tag
                    [ \t]*
                    (?=\n{2,})      // followed by a blank line
                )
            /g,hashElement);
            */
            text = text.replace(/\n[ ]{0,3}((<(hr)\b([^<>])*?\/?>)[ \t]*(?=\n{2,}))/g, hashElement);

            // Special case for standalone HTML comments:

            /*
            text = text.replace(/
                \n\n                                            // Starting after a blank line
                [ ]{0,3}                                        // attacklab: g_tab_width - 1
                (                                               // save in $1
                    <!
                    (--(?:|(?:[^>-]|-[^>])(?:[^-]|-[^-])*)--)   // see http://www.w3.org/TR/html-markup/syntax.html#comments and http://meta.stackoverflow.com/q/95256
                    >
                    [ \t]*
                    (?=\n{2,})                                  // followed by a blank line
                )
            /g,hashElement);
            */
            text = text.replace(/\n\n[ ]{0,3}(<!(--(?:|(?:[^>-]|-[^>])(?:[^-]|-[^-])*)--)>[ \t]*(?=\n{2,}))/g, hashElement);

            // PHP and ASP-style processor instructions (<?...?> and <%...%>)

            /*
            text = text.replace(/
                (?:
                    \n\n            // Starting after a blank line
                )
                (                   // save in $1
                    [ ]{0,3}        // attacklab: g_tab_width - 1
                    (?:
                        <([?%])     // $2
                        [^\r]*?
                        \2>
                    )
                    [ \t]*
                    (?=\n{2,})      // followed by a blank line
                )
            /g,hashElement);
            */
            text = text.replace(/(?:\n\n)([ ]{0,3}(?:<([?%])[^\r]*?\2>)[ \t]*(?=\n{2,}))/g, hashElement);

            return text;
        }

        function hashElement(wholeMatch, m1) {
            var blockText = m1;

            // Undo double lines
            blockText = blockText.replace(/^\n+/, "");

            // strip trailing blank lines
            blockText = blockText.replace(/\n+$/g, "");

            // Replace the element text with a marker ("~KxK" where x is its key)
            blockText = "\n\n~K" + (g_html_blocks.push(blockText) - 1) + "K\n\n";

            return blockText;
        }

        var blockGamutHookCallback = function (t) { return _RunBlockGamut(t); }

        function _RunBlockGamut(text, doNotUnhash) {
            //
            // These are all the transformations that form block-level
            // tags like paragraphs, headers, and list items.
            //

            text = pluginHooks.preBlockGamut(text, blockGamutHookCallback);

            text = _DoHeaders(text);

            // Do Horizontal Rules:
            var replacement = "<hr />\n";
            text = text.replace(/^[ ]{0,2}([ ]?\*[ ]?){3,}[ \t]*$/gm, replacement);
            text = text.replace(/^[ ]{0,2}([ ]?-[ ]?){3,}[ \t]*$/gm, replacement);
            text = text.replace(/^[ ]{0,2}([ ]?_[ ]?){3,}[ \t]*$/gm, replacement);

            text = _DoLists(text);
            text = _DoCodeBlocks(text);
            text = _DoBlockQuotes(text);

            text = pluginHooks.postBlockGamut(text, blockGamutHookCallback);

            // We already ran _HashHTMLBlocks() before, in Markdown(), but that
            // was to escape raw HTML in the original Markdown source. This time,
            // we're escaping the markup we've just created, so that we don't wrap
            // <p> tags around block-level tags.
            text = _HashHTMLBlocks(text);
            text = _FormParagraphs(text, doNotUnhash);

            return text;
        }

        function _RunSpanGamut(text) {
            //
            // These are all the transformations that occur *within* block-level
            // tags like paragraphs, headers, and list items.
            //

            text = pluginHooks.preSpanGamut(text);

            text = _DoCodeSpans(text);
            text = _EscapeSpecialCharsWithinTagAttributes(text);
            text = _EncodeBackslashEscapes(text);

            // Process anchor and image tags. Images must come first,
            // because ![foo][f] looks like an anchor.
            text = _DoImages(text);
            text = _DoAnchors(text);

            // Make links out of things like `<http://example.com/>`
            // Must come after _DoAnchors(), because you can use < and >
            // delimiters in inline links like [this](<url>).
            text = _DoAutoLinks(text);

            text = text.replace(/~P/g, "://"); // put in place to prevent autolinking; reset now

            text = _EncodeAmpsAndAngles(text);
            text = _DoItalicsAndBold(text);

            // Do hard breaks:
            text = text.replace(/  +\n/g, " <br>\n");

            text = pluginHooks.postSpanGamut(text);

            return text;
        }

        function _EscapeSpecialCharsWithinTagAttributes(text) {
            //
            // Within tags -- meaning between < and > -- encode [\ ` * _] so they
            // don't conflict with their use in Markdown for code, italics and strong.
            //

            // Build a regex to find HTML tags and comments.  See Friedl's 
            // "Mastering Regular Expressions", 2nd Ed., pp. 200-201.

            // SE: changed the comment part of the regex

            var regex = /(<[a-z\/!$]("[^"]*"|'[^']*'|[^'">])*>|<!(--(?:|(?:[^>-]|-[^>])(?:[^-]|-[^-])*)--)>)/gi;

            text = text.replace(regex, function (wholeMatch) {
                var tag = wholeMatch.replace(/(.)<\/?code>(?=.)/g, "$1`");
                tag = escapeCharacters(tag, wholeMatch.charAt(1) == "!" ? "\\`*_/" : "\\`*_"); // also escape slashes in comments to prevent autolinking there -- http://meta.stackoverflow.com/questions/95987
                return tag;
            });

            return text;
        }

        function _DoAnchors(text) {
            //
            // Turn Markdown link shortcuts into XHTML <a> tags.
            //
            //
            // First, handle reference-style links: [link text] [id]
            //

            /*
            text = text.replace(/
                (                           // wrap whole match in $1
                    \[
                    (
                        (?:
                            \[[^\]]*\]      // allow brackets nested one level
                            |
                            [^\[]           // or anything else
                        )*
                    )
                    \]

                    [ ]?                    // one optional space
                    (?:\n[ ]*)?             // one optional newline followed by spaces

                    \[
                    (.*?)                   // id = $3
                    \]
                )
                ()()()()                    // pad remaining backreferences
            /g, writeAnchorTag);
            */
            text = text.replace(/(\[((?:\[[^\]]*\]|[^\[\]])*)\][ ]?(?:\n[ ]*)?\[(.*?)\])()()()()/g, writeAnchorTag);

            //
            // Next, inline-style links: [link text](url "optional title")
            //

            /*
            text = text.replace(/
                (                           // wrap whole match in $1
                    \[
                    (
                        (?:
                            \[[^\]]*\]      // allow brackets nested one level
                            |
                            [^\[\]]         // or anything else
                        )*
                    )
                    \]
                    \(                      // literal paren
                    [ \t]*
                    ()                      // no id, so leave $3 empty
                    <?(                     // href = $4
                        (?:
                            \([^)]*\)       // allow one level of (correctly nested) parens (think MSDN)
                            |
                            [^()\s]
                        )*?
                    )>?
                    [ \t]*
                    (                       // $5
                        (['"])              // quote char = $6
                        (.*?)               // Title = $7
                        \6                  // matching quote
                        [ \t]*              // ignore any spaces/tabs between closing quote and )
                    )?                      // title is optional
                    \)
                )
            /g, writeAnchorTag);
            */

            text = text.replace(/(\[((?:\[[^\]]*\]|[^\[\]])*)\]\([ \t]*()<?((?:\([^)]*\)|[^()\s])*?)>?[ \t]*((['"])(.*?)\6[ \t]*)?\))/g, writeAnchorTag);

            //
            // Last, handle reference-style shortcuts: [link text]
            // These must come last in case you've also got [link test][1]
            // or [link test](/foo)
            //

            /*
            text = text.replace(/
                (                   // wrap whole match in $1
                    \[
                    ([^\[\]]+)      // link text = $2; can't contain '[' or ']'
                    \]
                )
                ()()()()()          // pad rest of backreferences
            /g, writeAnchorTag);
            */
            text = text.replace(/(\[([^\[\]]+)\])()()()()()/g, writeAnchorTag);

            return text;
        }

        function writeAnchorTag(wholeMatch, m1, m2, m3, m4, m5, m6, m7) {
            if (m7 == undefined) m7 = "";
            var whole_match = m1;
            var link_text = m2.replace(/:\/\//g, "~P"); // to prevent auto-linking withing the link. will be converted back after the auto-linker runs
            var link_id = m3.toLowerCase();
            var url = m4;
            var title = m7;

            if (url == "") {
                if (link_id == "") {
                    // lower-case and turn embedded newlines into spaces
                    link_id = link_text.toLowerCase().replace(/ ?\n/g, " ");
                }
                url = "#" + link_id;

                if (g_urls.get(link_id) != undefined) {
                    url = g_urls.get(link_id);
                    if (g_titles.get(link_id) != undefined) {
                        title = g_titles.get(link_id);
                    }
                }
                else {
                    if (whole_match.search(/\(\s*\)$/m) > -1) {
                        // Special case for explicit empty url
                        url = "";
                    } else {
                        return whole_match;
                    }
                }
            }
            url = encodeProblemUrlChars(url);
            url = escapeCharacters(url, "*_");
            var result = "<a href=\"" + url + "\"";

            if (title != "") {
                title = attributeEncode(title);
                title = escapeCharacters(title, "*_");
                result += " title=\"" + title + "\"";
            }

            result += ">" + link_text + "</a>";

            return result;
        }

        function _DoImages(text) {
            //
            // Turn Markdown image shortcuts into <img> tags.
            //

            //
            // First, handle reference-style labeled images: ![alt text][id]
            //

            /*
            text = text.replace(/
                (                   // wrap whole match in $1
                    !\[
                    (.*?)           // alt text = $2
                    \]

                    [ ]?            // one optional space
                    (?:\n[ ]*)?     // one optional newline followed by spaces

                    \[
                    (.*?)           // id = $3
                    \]
                )
                ()()()()            // pad rest of backreferences
            /g, writeImageTag);
            */
            text = text.replace(/(!\[(.*?)\][ ]?(?:\n[ ]*)?\[(.*?)\])()()()()/g, writeImageTag);

            //
            // Next, handle inline images:  ![alt text](url "optional title")
            // Don't forget: encode * and _

            /*
            text = text.replace(/
                (                   // wrap whole match in $1
                    !\[
                    (.*?)           // alt text = $2
                    \]
                    \s?             // One optional whitespace character
                    \(              // literal paren
                    [ \t]*
                    ()              // no id, so leave $3 empty
                    <?(\S+?)>?      // src url = $4
                    [ \t]*
                    (               // $5
                        (['"])      // quote char = $6
                        (.*?)       // title = $7
                        \6          // matching quote
                        [ \t]*
                    )?              // title is optional
                    \)
                )
            /g, writeImageTag);
            */
            text = text.replace(/(!\[(.*?)\]\s?\([ \t]*()<?(\S+?)>?[ \t]*((['"])(.*?)\6[ \t]*)?\))/g, writeImageTag);

            return text;
        }

        function attributeEncode(text) {
            // unconditionally replace angle brackets here -- what ends up in an attribute (e.g. alt or title)
            // never makes sense to have verbatim HTML in it (and the sanitizer would totally break it)
            return text.replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
        }

        function writeImageTag(wholeMatch, m1, m2, m3, m4, m5, m6, m7) {
            var whole_match = m1;
            var alt_text = m2;
            var link_id = m3.toLowerCase();
            var url = m4;
            var title = m7;

            if (!title) title = "";

            if (url == "") {
                if (link_id == "") {
                    // lower-case and turn embedded newlines into spaces
                    link_id = alt_text.toLowerCase().replace(/ ?\n/g, " ");
                }
                url = "#" + link_id;

                if (g_urls.get(link_id) != undefined) {
                    url = g_urls.get(link_id);
                    if (g_titles.get(link_id) != undefined) {
                        title = g_titles.get(link_id);
                    }
                }
                else {
                    return whole_match;
                }
            }

            alt_text = escapeCharacters(attributeEncode(alt_text), "*_[]()");
            url = escapeCharacters(url, "*_");
            var result = "<img src=\"" + url + "\" alt=\"" + alt_text + "\"";

            // attacklab: Markdown.pl adds empty title attributes to images.
            // Replicate this bug.

            //if (title != "") {
            title = attributeEncode(title);
            title = escapeCharacters(title, "*_");
            result += " title=\"" + title + "\"";
            //}

            result += " />";

            return result;
        }

        function _DoHeaders(text) {

            // Setext-style headers:
            //  Header 1
            //  ========
            //  
            //  Header 2
            //  --------
            //
            text = text.replace(/^(.+)[ \t]*\n=+[ \t]*\n+/gm,
                function (wholeMatch, m1) { return "<h1>" + _RunSpanGamut(m1) + "</h1>\n\n"; }
            );

            text = text.replace(/^(.+)[ \t]*\n-+[ \t]*\n+/gm,
                function (matchFound, m1) { return "<h2>" + _RunSpanGamut(m1) + "</h2>\n\n"; }
            );

            // atx-style headers:
            //  # Header 1
            //  ## Header 2
            //  ## Header 2 with closing hashes ##
            //  ...
            //  ###### Header 6
            //

            /*
            text = text.replace(/
                ^(\#{1,6})      // $1 = string of #'s
                [ \t]*
                (.+?)           // $2 = Header text
                [ \t]*
                \#*             // optional closing #'s (not counted)
                \n+
            /gm, function() {...});
            */

            text = text.replace(/^(\#{1,6})[ \t]*(.+?)[ \t]*\#*\n+/gm,
                function (wholeMatch, m1, m2) {
                    var h_level = m1.length;
                    return "<h" + h_level + ">" + _RunSpanGamut(m2) + "</h" + h_level + ">\n\n";
                }
            );

            return text;
        }

        function _DoLists(text) {
            //
            // Form HTML ordered (numbered) and unordered (bulleted) lists.
            //

            // attacklab: add sentinel to hack around khtml/safari bug:
            // http://bugs.webkit.org/show_bug.cgi?id=11231
            text += "~0";

            // Re-usable pattern to match any entirel ul or ol list:

            /*
            var whole_list = /
                (                                   // $1 = whole list
                    (                               // $2
                        [ ]{0,3}                    // attacklab: g_tab_width - 1
                        ([*+-]|\d+[.])              // $3 = first list item marker
                        [ \t]+
                    )
                    [^\r]+?
                    (                               // $4
                        ~0                          // sentinel for workaround; should be $
                        |
                        \n{2,}
                        (?=\S)
                        (?!                         // Negative lookahead for another list item marker
                            [ \t]*
                            (?:[*+-]|\d+[.])[ \t]+
                        )
                    )
                )
            /g
            */
            var whole_list = /^(([ ]{0,3}([*+-]|\d+[.])[ \t]+)[^\r]+?(~0|\n{2,}(?=\S)(?![ \t]*(?:[*+-]|\d+[.])[ \t]+)))/gm;

            if (g_list_level) {
                text = text.replace(whole_list, function (wholeMatch, m1, m2) {
                    var list = m1;
                    var list_type = (m2.search(/[*+-]/g) > -1) ? "ul" : "ol";

                    var result = _ProcessListItems(list, list_type);

                    // Trim any trailing whitespace, to put the closing `</$list_type>`
                    // up on the preceding line, to get it past the current stupid
                    // HTML block parser. This is a hack to work around the terrible
                    // hack that is the HTML block parser.
                    result = result.replace(/\s+$/, "");
                    result = "<" + list_type + ">" + result + "</" + list_type + ">\n";
                    return result;
                });
            } else {
                whole_list = /(\n\n|^\n?)(([ ]{0,3}([*+-]|\d+[.])[ \t]+)[^\r]+?(~0|\n{2,}(?=\S)(?![ \t]*(?:[*+-]|\d+[.])[ \t]+)))/g;
                text = text.replace(whole_list, function (wholeMatch, m1, m2, m3) {
                    var runup = m1;
                    var list = m2;

                    var list_type = (m3.search(/[*+-]/g) > -1) ? "ul" : "ol";
                    var result = _ProcessListItems(list, list_type);
                    result = runup + "<" + list_type + ">\n" + result + "</" + list_type + ">\n";
                    return result;
                });
            }

            // attacklab: strip sentinel
            text = text.replace(/~0/, "");

            return text;
        }

        var _listItemMarkers = { ol: "\\d+[.]", ul: "[*+-]" };

        function _ProcessListItems(list_str, list_type) {
            //
            //  Process the contents of a single ordered or unordered list, splitting it
            //  into individual list items.
            //
            //  list_type is either "ul" or "ol".

            // The $g_list_level global keeps track of when we're inside a list.
            // Each time we enter a list, we increment it; when we leave a list,
            // we decrement. If it's zero, we're not in a list anymore.
            //
            // We do this because when we're not inside a list, we want to treat
            // something like this:
            //
            //    I recommend upgrading to version
            //    8. Oops, now this line is treated
            //    as a sub-list.
            //
            // As a single paragraph, despite the fact that the second line starts
            // with a digit-period-space sequence.
            //
            // Whereas when we're inside a list (or sub-list), that line will be
            // treated as the start of a sub-list. What a kludge, huh? This is
            // an aspect of Markdown's syntax that's hard to parse perfectly
            // without resorting to mind-reading. Perhaps the solution is to
            // change the syntax rules such that sub-lists must start with a
            // starting cardinal number; e.g. "1." or "a.".

            g_list_level++;

            // trim trailing blank lines:
            list_str = list_str.replace(/\n{2,}$/, "\n");

            // attacklab: add sentinel to emulate \z
            list_str += "~0";

            // In the original attacklab showdown, list_type was not given to this function, and anything
            // that matched /[*+-]|\d+[.]/ would just create the next <li>, causing this mismatch:
            //
            //  Markdown          rendered by WMD        rendered by MarkdownSharp
            //  ------------------------------------------------------------------
            //  1. first          1. first               1. first
            //  2. second         2. second              2. second
            //  - third           3. third                   * third
            //
            // We changed this to behave identical to MarkdownSharp. This is the constructed RegEx,
            // with {MARKER} being one of \d+[.] or [*+-], depending on list_type:

            /*
            list_str = list_str.replace(/
                (^[ \t]*)                       // leading whitespace = $1
                ({MARKER}) [ \t]+               // list marker = $2
                ([^\r]+?                        // list item text   = $3
                    (\n+)
                )
                (?=
                    (~0 | \2 ({MARKER}) [ \t]+)
                )
            /gm, function(){...});
            */

            var marker = _listItemMarkers[list_type];
            var re = new RegExp("(^[ \\t]*)(" + marker + ")[ \\t]+([^\\r]+?(\\n+))(?=(~0|\\1(" + marker + ")[ \\t]+))", "gm");
            var last_item_had_a_double_newline = false;
            list_str = list_str.replace(re,
                function (wholeMatch, m1, m2, m3) {
                    var item = m3;
                    var leading_space = m1;
                    var ends_with_double_newline = /\n\n$/.test(item);
                    var contains_double_newline = ends_with_double_newline || item.search(/\n{2,}/) > -1;

                    if (contains_double_newline || last_item_had_a_double_newline) {
                        item = _RunBlockGamut(_Outdent(item), /* doNotUnhash = */true);
                    }
                    else {
                        // Recursion for sub-lists:
                        item = _DoLists(_Outdent(item));
                        item = item.replace(/\n$/, ""); // chomp(item)
                        item = _RunSpanGamut(item);
                    }
                    last_item_had_a_double_newline = ends_with_double_newline;
                    return "<li>" + item + "</li>\n";
                }
            );

            // attacklab: strip sentinel
            list_str = list_str.replace(/~0/g, "");

            g_list_level--;
            return list_str;
        }

        function _DoCodeBlocks(text) {
            //
            //  Process Markdown `<pre><code>` blocks.
            //  

            /*
            text = text.replace(/
                (?:\n\n|^)
                (                               // $1 = the code block -- one or more lines, starting with a space/tab
                    (?:
                        (?:[ ]{4}|\t)           // Lines must start with a tab or a tab-width of spaces - attacklab: g_tab_width
                        .*\n+
                    )+
                )
                (\n*[ ]{0,3}[^ \t\n]|(?=~0))    // attacklab: g_tab_width
            /g ,function(){...});
            */

            // attacklab: sentinel workarounds for lack of \A and \Z, safari\khtml bug
            text += "~0";

            text = text.replace(/(?:\n\n|^\n?)((?:(?:[ ]{4}|\t).*\n+)+)(\n*[ ]{0,3}[^ \t\n]|(?=~0))/g,
                function (wholeMatch, m1, m2) {
                    var codeblock = m1;
                    var nextChar = m2;

                    codeblock = _EncodeCode(_Outdent(codeblock));
                    codeblock = _Detab(codeblock);
                    codeblock = codeblock.replace(/^\n+/g, ""); // trim leading newlines
                    codeblock = codeblock.replace(/\n+$/g, ""); // trim trailing whitespace

                    codeblock = "<pre><code>" + codeblock + "\n</code></pre>";

                    return "\n\n" + codeblock + "\n\n" + nextChar;
                }
            );

            // attacklab: strip sentinel
            text = text.replace(/~0/, "");

            return text;
        }

        function hashBlock(text) {
            text = text.replace(/(^\n+|\n+$)/g, "");
            return "\n\n~K" + (g_html_blocks.push(text) - 1) + "K\n\n";
        }

        function _DoCodeSpans(text) {
            //
            // * Backtick quotes are used for <code></code> spans.
            // 
            // * You can use multiple backticks as the delimiters if you want to
            //   include literal backticks in the code span. So, this input:
            //     
            //      Just type ``foo `bar` baz`` at the prompt.
            //     
            //   Will translate to:
            //     
            //      <p>Just type <code>foo `bar` baz</code> at the prompt.</p>
            //     
            //   There's no arbitrary limit to the number of backticks you
            //   can use as delimters. If you need three consecutive backticks
            //   in your code, use four for delimiters, etc.
            //
            // * You can use spaces to get literal backticks at the edges:
            //     
            //      ... type `` `bar` `` ...
            //     
            //   Turns to:
            //     
            //      ... type <code>`bar`</code> ...
            //

            /*
            text = text.replace(/
                (^|[^\\])       // Character before opening ` can't be a backslash
                (`+)            // $2 = Opening run of `
                (               // $3 = The code block
                    [^\r]*?
                    [^`]        // attacklab: work around lack of lookbehind
                )
                \2              // Matching closer
                (?!`)
            /gm, function(){...});
            */

            text = text.replace(/(^|[^\\])(`+)([^\r]*?[^`])\2(?!`)/gm,
                function (wholeMatch, m1, m2, m3, m4) {
                    var c = m3;
                    c = c.replace(/^([ \t]*)/g, ""); // leading whitespace
                    c = c.replace(/[ \t]*$/g, ""); // trailing whitespace
                    c = _EncodeCode(c);
                    c = c.replace(/:\/\//g, "~P"); // to prevent auto-linking. Not necessary in code *blocks*, but in code spans. Will be converted back after the auto-linker runs.
                    return m1 + "<code>" + c + "</code>";
                }
            );

            return text;
        }

        function _EncodeCode(text) {
            //
            // Encode/escape certain characters inside Markdown code runs.
            // The point is that in code, these characters are literals,
            // and lose their special Markdown meanings.
            //
            // Encode all ampersands; HTML entities are not
            // entities within a Markdown code span.
            text = text.replace(/&/g, "&amp;");

            // Do the angle bracket song and dance:
            text = text.replace(/</g, "&lt;");
            text = text.replace(/>/g, "&gt;");

            // Now, escape characters that are magic in Markdown:
            text = escapeCharacters(text, "\*_{}[]\\", false);

            // jj the line above breaks this:
            //---

            //* Item

            //   1. Subitem

            //            special char: *
            //---

            return text;
        }

        function _DoItalicsAndBold(text) {

            // <strong> must go first:
            text = text.replace(/([\W_]|^)(\*\*|__)(?=\S)([^\r]*?\S[\*_]*)\2([\W_]|$)/g,
            "$1<strong>$3</strong>$4");

            text = text.replace(/([\W_]|^)(\*|_)(?=\S)([^\r\*_]*?\S)\2([\W_]|$)/g,
            "$1<em>$3</em>$4");

            return text;
        }

        function _DoBlockQuotes(text) {

            /*
            text = text.replace(/
                (                           // Wrap whole match in $1
                    (
                        ^[ \t]*>[ \t]?      // '>' at the start of a line
                        .+\n                // rest of the first line
                        (.+\n)*             // subsequent consecutive lines
                        \n*                 // blanks
                    )+
                )
            /gm, function(){...});
            */

            text = text.replace(/((^[ \t]*>[ \t]?.+\n(.+\n)*\n*)+)/gm,
                function (wholeMatch, m1) {
                    var bq = m1;

                    // attacklab: hack around Konqueror 3.5.4 bug:
                    // "----------bug".replace(/^-/g,"") == "bug"

                    bq = bq.replace(/^[ \t]*>[ \t]?/gm, "~0"); // trim one level of quoting

                    // attacklab: clean up hack
                    bq = bq.replace(/~0/g, "");

                    bq = bq.replace(/^[ \t]+$/gm, "");     // trim whitespace-only lines
                    bq = _RunBlockGamut(bq);             // recurse

                    bq = bq.replace(/(^|\n)/g, "$1  ");
                    // These leading spaces screw with <pre> content, so we need to fix that:
                    bq = bq.replace(
                            /(\s*<pre>[^\r]+?<\/pre>)/gm,
                        function (wholeMatch, m1) {
                            var pre = m1;
                            // attacklab: hack around Konqueror 3.5.4 bug:
                            pre = pre.replace(/^  /mg, "~0");
                            pre = pre.replace(/~0/g, "");
                            return pre;
                        });

                    return hashBlock("<blockquote>\n" + bq + "\n</blockquote>");
                }
            );
            return text;
        }

        function _FormParagraphs(text, doNotUnhash) {
            //
            //  Params:
            //    $text - string to process with html <p> tags
            //

            // Strip leading and trailing lines:
            text = text.replace(/^\n+/g, "");
            text = text.replace(/\n+$/g, "");

            var grafs = text.split(/\n{2,}/g);
            var grafsOut = [];

            var markerRe = /~K(\d+)K/;

            //
            // Wrap <p> tags.
            //
            var end = grafs.length;
            for (var i = 0; i < end; i++) {
                var str = grafs[i];

                // if this is an HTML marker, copy it
                if (markerRe.test(str)) {
                    grafsOut.push(str);
                }
                else if (/\S/.test(str)) {
                    str = _RunSpanGamut(str);
                    str = str.replace(/^([ \t]*)/g, "<p>");
                    str += "</p>"
                    grafsOut.push(str);
                }

            }
            //
            // Unhashify HTML blocks
            //
            if (!doNotUnhash) {
                end = grafsOut.length;
                for (var i = 0; i < end; i++) {
                    var foundAny = true;
                    while (foundAny) { // we may need several runs, since the data may be nested
                        foundAny = false;
                        grafsOut[i] = grafsOut[i].replace(/~K(\d+)K/g, function (wholeMatch, id) {
                            foundAny = true;
                            return g_html_blocks[id];
                        });
                    }
                }
            }
            return grafsOut.join("\n\n");
        }

        function _EncodeAmpsAndAngles(text) {
            // Smart processing for ampersands and angle brackets that need to be encoded.

            // Ampersand-encoding based entirely on Nat Irons's Amputator MT plugin:
            //   http://bumppo.net/projects/amputator/
            text = text.replace(/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/g, "&amp;");

            // Encode naked <'s
            text = text.replace(/<(?![a-z\/?!]|~D)/gi, "&lt;");

            return text;
        }

        function _EncodeBackslashEscapes(text) {
            //
            //   Parameter:  String.
            //   Returns:    The string, with after processing the following backslash
            //               escape sequences.
            //

            // attacklab: The polite way to do this is with the new
            // escapeCharacters() function:
            //
            //     text = escapeCharacters(text,"\\",true);
            //     text = escapeCharacters(text,"`*_{}[]()>#+-.!",true);
            //
            // ...but we're sidestepping its use of the (slow) RegExp constructor
            // as an optimization for Firefox.  This function gets called a LOT.

            text = text.replace(/\\(\\)/g, escapeCharacters_callback);
            text = text.replace(/\\([`*_{}\[\]()>#+-.!])/g, escapeCharacters_callback);
            return text;
        }

        function handleTrailingParens(wholeMatch, lookbehind, protocol, link) {
            if (lookbehind)
                return wholeMatch;
            if (link.charAt(link.length - 1) !== ")")
                return "<" + protocol + link + ">";
            var parens = link.match(/[()]/g);
            var level = 0;
            for (var i = 0; i < parens.length; i++) {
                if (parens[i] === "(") {
                    if (level <= 0)
                        level = 1;
                    else
                        level++;
                }
                else {
                    level--;
                }
            }
            var tail = "";
            if (level < 0) {
                var re = new RegExp("\\){1," + (-level) + "}$");
                link = link.replace(re, function (trailingParens) {
                    tail = trailingParens;
                    return "";
                });
            }

            return "<" + protocol + link + ">" + tail;
        }

        function _DoAutoLinks(text) {

            // note that at this point, all other URL in the text are already hyperlinked as <a href=""></a>
            // *except* for the <http://www.foo.com> case

            // automatically add < and > around unadorned raw hyperlinks
            // must be preceded by a non-word character (and not by =" or <) and followed by non-word/EOF character
            // simulating the lookbehind in a consuming way is okay here, since a URL can neither and with a " nor
            // with a <, so there is no risk of overlapping matches.
            text = text.replace(/(="|<)?\b(https?|ftp)(:\/\/[-A-Z0-9+&@#\/%?=~_|\[\]\(\)!:,\.;]*[-A-Z0-9+&@#\/%=~_|\[\])])(?=$|\W)/gi, handleTrailingParens);

            //  autolink anything like <http://example.com>

            var replacer = function (wholematch, m1) { return "<a href=\"" + m1 + "\">" + pluginHooks.plainLinkText(m1) + "</a>"; }
            text = text.replace(/<((https?|ftp):[^'">\s]+)>/gi, replacer);

            // Email addresses: <address@domain.foo>
            /*
            text = text.replace(/
                <
                (?:mailto:)?
                (
                    [-.\w]+
                    \@
                    [-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]+
                )
                >
            /gi, _DoAutoLinks_callback());
            */

            /* disabling email autolinking, since we don't do that on the server, either
            text = text.replace(/<(?:mailto:)?([-.\w]+\@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]+)>/gi,
                function(wholeMatch,m1) {
                    return _EncodeEmailAddress( _UnescapeSpecialChars(m1) );
                }
            );
            */
            return text;
        }

        function _UnescapeSpecialChars(text) {
            //
            // Swap back in all the special characters we've hidden.
            //
            text = text.replace(/~E(\d+)E/g,
                function (wholeMatch, m1) {
                    var charCodeToReplace = parseInt(m1);
                    return String.fromCharCode(charCodeToReplace);
                }
            );
            return text;
        }

        function _Outdent(text) {
            //
            // Remove one level of line-leading tabs or spaces
            //

            // attacklab: hack around Konqueror 3.5.4 bug:
            // "----------bug".replace(/^-/g,"") == "bug"

            text = text.replace(/^(\t|[ ]{1,4})/gm, "~0"); // attacklab: g_tab_width

            // attacklab: clean up hack
            text = text.replace(/~0/g, "")

            return text;
        }

        function _Detab(text) {
            if (!/\t/.test(text))
                return text;

            var spaces = ["    ", "   ", "  ", " "],
            skew = 0,
            v;

            return text.replace(/[\n\t]/g, function (match, offset) {
                if (match === "\n") {
                    skew = offset + 1;
                    return match;
                }
                v = (offset - skew) % 4;
                skew = offset + 1;
                return spaces[v];
            });
        }

        //
        //  attacklab: Utility functions
        //

        var _problemUrlChars = /(?:["'*()[\]:]|~D)/g;

        // hex-encodes some unusual "problem" chars in URLs to avoid URL detection problems 
        function encodeProblemUrlChars(url) {
            if (!url)
                return "";

            var len = url.length;

            return url.replace(_problemUrlChars, function (match, offset) {
                if (match == "~D") // escape for dollar
                    return "%24";
                if (match == ":") {
                    if (offset == len - 1 || /[0-9\/]/.test(url.charAt(offset + 1)))
                        return ":"
                }
                return "%" + match.charCodeAt(0).toString(16);
            });
        }


        function escapeCharacters(text, charsToEscape, afterBackslash) {
            // First we have to escape the escape characters so that
            // we can build a character class out of them
            var regexString = "([" + charsToEscape.replace(/([\[\]\\])/g, "\\$1") + "])";

            if (afterBackslash) {
                regexString = "\\\\" + regexString;
            }

            var regex = new RegExp(regexString, "g");
            text = text.replace(regex, escapeCharacters_callback);

            return text;
        }


        function escapeCharacters_callback(wholeMatch, m1) {
            var charCodeToEscape = m1.charCodeAt(0);
            return "~E" + charCodeToEscape + "E";
        }

    }; // end of the Markdown.Converter constructor

})();

(function () {
    var output, Converter;
    if (typeof exports === "object" && typeof require === "function") { // we're in a CommonJS (e.g. Node.js) module
        output = exports;
        Converter = require("./Markdown.Converter").Converter;
    } else {
        output = window.Markdown;
        Converter = output.Converter;
    }

    output.getSanitizingConverter = function () {
        var converter = new Converter();
        converter.hooks.chain("postConversion", sanitizeHtml);
        converter.hooks.chain("postConversion", balanceTags);
        return converter;
    }

    function sanitizeHtml(html) {
        return html.replace(/<[^>]*>?/gi, sanitizeTag);
    }

    // (tags that can be opened/closed) | (tags that stand alone)
    var basic_tag_whitelist = /^(<\/?(b|blockquote|code|del|dd|dl|dt|em|h1|h2|h3|i|kbd|li|ol|p|pre|s|sup|sub|strong|strike|ul)>|<(br|hr)\s?\/?>)$/i;
    // <a href="url..." optional title>|</a>
    var a_white = /^(<a\shref="((https?|ftp):\/\/|\/)[-A-Za-z0-9+&@#\/%?=~_|!:,.;\(\)]+"(\stitle="[^"<>]+")?\s?>|<\/a>)$/i;

    // <img src="url..." optional width  optional height  optional alt  optional title
    var img_white = /^(<img\ssrc="(https?:\/\/|\/)[-A-Za-z0-9+&@#\/%?=~_|!:,.;\(\)]+"(\swidth="\d{1,3}")?(\sheight="\d{1,3}")?(\salt="[^"<>]*")?(\stitle="[^"<>]*")?\s?\/?>)$/i;

    function sanitizeTag(tag) {
        if (tag.match(basic_tag_whitelist) || tag.match(a_white) || tag.match(img_white))
            return tag;
        else
            return "";
    }

    /// <summary>
    /// attempt to balance HTML tags in the html string
    /// by removing any unmatched opening or closing tags
    /// IMPORTANT: we *assume* HTML has *already* been 
    /// sanitized and is safe/sane before balancing!
    /// 
    /// adapted from CODESNIPPET: A8591DBA-D1D3-11DE-947C-BA5556D89593
    /// </summary>
    function balanceTags(html) {

        if (html == "")
            return "";

        var re = /<\/?\w+[^>]*(\s|$|>)/g;
        // convert everything to lower case; this makes
        // our case insensitive comparisons easier
        var tags = html.toLowerCase().match(re);

        // no HTML tags present? nothing to do; exit now
        var tagcount = (tags || []).length;
        if (tagcount == 0)
            return html;

        var tagname, tag;
        var ignoredtags = "<p><img><br><li><hr>";
        var match;
        var tagpaired = [];
        var tagremove = [];
        var needsRemoval = false;

        // loop through matched tags in forward order
        for (var ctag = 0; ctag < tagcount; ctag++) {
            tagname = tags[ctag].replace(/<\/?(\w+).*/, "$1");
            // skip any already paired tags
            // and skip tags in our ignore list; assume they're self-closed
            if (tagpaired[ctag] || ignoredtags.search("<" + tagname + ">") > -1)
                continue;

            tag = tags[ctag];
            match = -1;

            if (!/^<\//.test(tag)) {
                // this is an opening tag
                // search forwards (next tags), look for closing tags
                for (var ntag = ctag + 1; ntag < tagcount; ntag++) {
                    if (!tagpaired[ntag] && tags[ntag] == "</" + tagname + ">") {
                        match = ntag;
                        break;
                    }
                }
            }

            if (match == -1)
                needsRemoval = tagremove[ctag] = true; // mark for removal
            else
                tagpaired[match] = true; // mark paired
        }

        if (!needsRemoval)
            return html;

        // delete all orphaned tags from the string

        var ctag = 0;
        html = html.replace(re, function (match) {
            var res = tagremove[ctag] ? "" : match;
            ctag++;
            return res;
        });
        return html;
    }
})();

// needs Markdown.Converter.js at the moment

(function () {

    var util = {},
        position = {},
        ui = {},
        doc = window.document,
        re = window.RegExp,
        nav = window.navigator,
        SETTINGS = { lineLength: 72 },

    // Used to work around some browser bugs where we can't use feature testing.
        uaSniffed = {
            isIE: /msie/.test(nav.userAgent.toLowerCase()),
            isIE_5or6: /msie 6/.test(nav.userAgent.toLowerCase()) || /msie 5/.test(nav.userAgent.toLowerCase()),
            isOpera: /opera/.test(nav.userAgent.toLowerCase())
        };

    var defaultsStrings = {
        bold: "Strong <strong> Ctrl+B",
        boldexample: "strong text",

        italic: "Emphasis <em> Ctrl+I",
        italicexample: "emphasized text",

        link: "Hyperlink <a> Ctrl+L",
        linkdescription: "enter link description here",
        linkdialog: "<p><b>Insert Hyperlink</b></p><p>http://example.com/ \"optional title\"</p>",
        linkname: null,

        quote: "Blockquote <blockquote> Ctrl+Q",
        quoteexample: "Blockquote",

        code: "Code Sample <pre><code> Ctrl+K",
        codeexample: "enter code here",

        image: "Image <img> Ctrl+G",
        imagedescription: "enter image description here",
        imagedialog: "<p><b>Insert Image</b></p><p>http://example.com/images/diagram.jpg \"optional title\"<br>Need <a href='http://www.google.com/search?q=free+image+hosting' target='_blank'>free image hosting?</a></p>",
        imagename: null,

        olist: "Numbered List <ol> Ctrl+O",
        ulist: "Bulleted List <ul> Ctrl+U",
        litem: "List item",

        heading: "Heading <h1>/<h2> Ctrl+H",
        headingexample: "Heading",

        fullscreen: 'FullScreen Ctrl+M',

        hr: "Horizontal Rule <hr> Ctrl+R",

        undo: "Undo - Ctrl+Z",
        redo: "Redo - Ctrl+Y",
        redomac: "Redo - Ctrl+Shift+Z",

        ok: "OK",
        cancel: "Cancel",

        help: "Markdown Editing Help"
    };


    // -------------------------------------------------------------------
    //  YOUR CHANGES GO HERE
    //
    // I've tried to localize the things you are likely to change to
    // this area.
    // -------------------------------------------------------------------

    // The default text that appears in the dialog input box when entering
    // links.
    var imageDefaultText = "http://";
    var linkDefaultText = "http://";

    // -------------------------------------------------------------------
    //  END OF YOUR CHANGES
    // -------------------------------------------------------------------

    // options, if given, can have the following properties:
    //   options.helpButton = { handler: yourEventHandler }
    //   options.strings = { italicexample: "slanted text" }
    // `yourEventHandler` is the click handler for the help button.
    // If `options.helpButton` isn't given, not help button is created.
    // `options.strings` can have any or all of the same properties as
    // `defaultStrings` above, so you can just override some string displayed
    // to the user on a case-by-case basis, or translate all strings to
    // a different language.
    //
    // For backwards compatibility reasons, the `options` argument can also
    // be just the `helpButton` object, and `strings.help` can also be set via
    // `helpButton.title`. This should be considered legacy.
    //
    // The constructed editor object has the methods:
    // - getConverter() returns the markdown converter object that was passed to the constructor
    // - run() actually starts the editor; should be called after all necessary plugins are registered. Calling this more than once is a no-op.
    // - refreshPreview() forces the preview to be updated. This method is only available after run() was called.
    Markdown.Editor = function (markdownConverter, idPostfix, options) {

        options = options || {};

        if (typeof options.handler === "function") { //backwards compatible behavior
            options = { helpButton: options };
        }
        options.strings = options.strings || {};
        if (options.helpButton) {
            options.strings.help = options.strings.help || options.helpButton.title;
        }
        var getString = function (identifier) {
            var string = options.strings[identifier] || defaultsStrings[identifier];

            if ('imagename' == identifier || 'linkname' == identifier) {
                options.strings[identifier] = null;
            }

            return string;
        }

        idPostfix = idPostfix || "";

        var hooks = this.hooks = new Markdown.HookCollection();
        hooks.addNoop("onPreviewRefresh");       // called with no arguments after the preview has been refreshed
        hooks.addNoop("postBlockquoteCreation"); // called with the user's selection *after* the blockquote was created; should return the actual to-be-inserted text
        hooks.addFalse("insertImageDialog");     /* called with one parameter: a callback to be called with the URL of the image. If the application creates
                                                  * its own image insertion dialog, this hook should return true, and the callback should be called with the chosen
                                                  * image url (or null if the user cancelled). If this hook returns false, the default dialog will be used.
                                                  */
        hooks.addFalse("insertLinkDialog");     /* called with one parameter: a callback to be called with the URL of the image. If the application creates
                                                  * its own image insertion dialog, this hook should return true, and the callback should be called with the chosen
                                                  * image url (or null if the user cancelled). If this hook returns false, the default dialog will be used.
                                                  */

        this.getConverter = function () { return markdownConverter; }

        var that = this,
            panels;

        this.run = function () {
            if (panels)
                return; // already initialized

            panels = new PanelCollection(idPostfix);
            var commandManager = new CommandManager(hooks, getString);
            var previewManager = new PreviewManager(markdownConverter, panels, function () { hooks.onPreviewRefresh(); });
            var undoManager, uiManager;

            if (!/\?noundo/.test(doc.location.href)) {
                undoManager = new UndoManager(function () {
                    previewManager.refresh();
                    if (uiManager) // not available on the first call
                        uiManager.setUndoRedoButtonStates();
                }, panels);
                this.textOperation = function (f) {
                    undoManager.setCommandMode();
                    f();
                    that.refreshPreview();
                }
            }

            uiManager = new UIManager(idPostfix, panels, undoManager, previewManager, commandManager, options.helpButton, getString);
            uiManager.setUndoRedoButtonStates();

            var forceRefresh = that.refreshPreview = function () { previewManager.refresh(true); };

            forceRefresh();
        };

    }

    // before: contains all the text in the input box BEFORE the selection.
    // after: contains all the text in the input box AFTER the selection.
    function Chunks() { }

    // startRegex: a regular expression to find the start tag
    // endRegex: a regular expresssion to find the end tag
    Chunks.prototype.findTags = function (startRegex, endRegex) {

        var chunkObj = this;
        var regex;

        if (startRegex) {

            regex = util.extendRegExp(startRegex, "", "$");

            this.before = this.before.replace(regex,
                function (match) {
                    chunkObj.startTag = chunkObj.startTag + match;
                    return "";
                });

            regex = util.extendRegExp(startRegex, "^", "");

            this.selection = this.selection.replace(regex,
                function (match) {
                    chunkObj.startTag = chunkObj.startTag + match;
                    return "";
                });
        }

        if (endRegex) {

            regex = util.extendRegExp(endRegex, "", "$");

            this.selection = this.selection.replace(regex,
                function (match) {
                    chunkObj.endTag = match + chunkObj.endTag;
                    return "";
                });

            regex = util.extendRegExp(endRegex, "^", "");

            this.after = this.after.replace(regex,
                function (match) {
                    chunkObj.endTag = match + chunkObj.endTag;
                    return "";
                });
        }
    };

    // If remove is false, the whitespace is transferred
    // to the before/after regions.
    //
    // If remove is true, the whitespace disappears.
    Chunks.prototype.trimWhitespace = function (remove) {
        var beforeReplacer, afterReplacer, that = this;
        if (remove) {
            beforeReplacer = afterReplacer = "";
        } else {
            beforeReplacer = function (s) { that.before += s; return ""; }
            afterReplacer = function (s) { that.after = s + that.after; return ""; }
        }

        this.selection = this.selection.replace(/^(\s*)/, beforeReplacer).replace(/(\s*)$/, afterReplacer);
    };


    Chunks.prototype.skipLines = function (nLinesBefore, nLinesAfter, findExtraNewlines) {

        if (nLinesBefore === undefined) {
            nLinesBefore = 1;
        }

        if (nLinesAfter === undefined) {
            nLinesAfter = 1;
        }

        nLinesBefore++;
        nLinesAfter++;

        var regexText;
        var replacementText;

        // chrome bug ... documented at: http://meta.stackoverflow.com/questions/63307/blockquote-glitch-in-editor-in-chrome-6-and-7/65985#65985
        if (navigator.userAgent.match(/Chrome/)) {
            "X".match(/()./);
        }

        this.selection = this.selection.replace(/(^\n*)/, "");

        this.startTag = this.startTag + re.$1;

        this.selection = this.selection.replace(/(\n*$)/, "");
        this.endTag = this.endTag + re.$1;
        this.startTag = this.startTag.replace(/(^\n*)/, "");
        this.before = this.before + re.$1;
        this.endTag = this.endTag.replace(/(\n*$)/, "");
        this.after = this.after + re.$1;

        if (this.before) {

            regexText = replacementText = "";

            while (nLinesBefore--) {
                regexText += "\\n?";
                replacementText += "\n";
            }

            if (findExtraNewlines) {
                regexText = "\\n*";
            }
            this.before = this.before.replace(new re(regexText + "$", ""), replacementText);
        }

        if (this.after) {

            regexText = replacementText = "";

            while (nLinesAfter--) {
                regexText += "\\n?";
                replacementText += "\n";
            }
            if (findExtraNewlines) {
                regexText = "\\n*";
            }

            this.after = this.after.replace(new re(regexText, ""), replacementText);
        }
    };

    // end of Chunks

    // A collection of the important regions on the page.
    // Cached so we don't have to keep traversing the DOM.
    // Also holds ieCachedRange and ieCachedScrollTop, where necessary; working around
    // this issue:
    // Internet explorer has problems with CSS sprite buttons that use HTML
    // lists.  When you click on the background image "button", IE will
    // select the non-existent link text and discard the selection in the
    // textarea.  The solution to this is to cache the textarea selection
    // on the button's mousedown event and set a flag.  In the part of the
    // code where we need to grab the selection, we check for the flag
    // and, if it's set, use the cached area instead of querying the
    // textarea.
    //
    // This ONLY affects Internet Explorer (tested on versions 6, 7
    // and 8) and ONLY on button clicks.  Keyboard shortcuts work
    // normally since the focus never leaves the textarea.
    function PanelCollection(postfix) {
        this.buttonBar = doc.getElementById("wmd-button-bar" + postfix);
        this.preview = doc.getElementById("wmd-preview" + postfix);
        this.input = doc.getElementById("text");
    };

    // Returns true if the DOM element is visible, false if it's hidden.
    // Checks if display is anything other than none.
    util.isVisible = function (elem) {

        if (window.getComputedStyle) {
            // Most browsers
            return window.getComputedStyle(elem, null).getPropertyValue("display") !== "none";
        }
        else if (elem.currentStyle) {
            // IE
            return elem.currentStyle["display"] !== "none";
        }
    };


    // Adds a listener callback to a DOM element which is fired on a specified
    // event.
    util.addEvent = function (elem, event, listener) {
        if (elem.attachEvent) {
            // IE only.  The "on" is mandatory.
            elem.attachEvent("on" + event, listener);
        }
        else {
            // Other browsers.
            elem.addEventListener(event, listener, false);
        }
    };


    // Removes a listener callback from a DOM element which is fired on a specified
    // event.
    util.removeEvent = function (elem, event, listener) {
        if (elem.detachEvent) {
            // IE only.  The "on" is mandatory.
            elem.detachEvent("on" + event, listener);
        }
        else {
            // Other browsers.
            elem.removeEventListener(event, listener, false);
        }
    };

    // Converts \r\n and \r to \n.
    util.fixEolChars = function (text) {
        text = text.replace(/\r\n/g, "\n");
        text = text.replace(/\r/g, "\n");
        return text;
    };

    // Extends a regular expression.  Returns a new RegExp
    // using pre + regex + post as the expression.
    // Used in a few functions where we have a base
    // expression and we want to pre- or append some
    // conditions to it (e.g. adding "$" to the end).
    // The flags are unchanged.
    //
    // regex is a RegExp, pre and post are strings.
    util.extendRegExp = function (regex, pre, post) {

        if (pre === null || pre === undefined) {
            pre = "";
        }
        if (post === null || post === undefined) {
            post = "";
        }

        var pattern = regex.toString();
        var flags;

        // Replace the flags with empty space and store them.
        pattern = pattern.replace(/\/([gim]*)$/, function (wholeMatch, flagsPart) {
            flags = flagsPart;
            return "";
        });

        // Remove the slash delimiters on the regular expression.
        pattern = pattern.replace(/(^\/|\/$)/g, "");
        pattern = pre + pattern + post;

        return new re(pattern, flags);
    }

    // UNFINISHED
    // The assignment in the while loop makes jslint cranky.
    // I'll change it to a better loop later.
    position.getTop = function (elem, isInner) {
        var result = elem.offsetTop;
        if (!isInner) {
            while (elem = elem.offsetParent) {
                result += elem.offsetTop;
            }
        }
        return result;
    };

    position.getHeight = function (elem) {
        return elem.offsetHeight || elem.scrollHeight;
    };

    position.getWidth = function (elem) {
        return elem.offsetWidth || elem.scrollWidth;
    };

    position.getPageSize = function () {

        var scrollWidth, scrollHeight;
        var innerWidth, innerHeight;

        // It's not very clear which blocks work with which browsers.
        if (self.innerHeight && self.scrollMaxY) {
            scrollWidth = doc.body.scrollWidth;
            scrollHeight = self.innerHeight + self.scrollMaxY;
        }
        else if (doc.body.scrollHeight > doc.body.offsetHeight) {
            scrollWidth = doc.body.scrollWidth;
            scrollHeight = doc.body.scrollHeight;
        }
        else {
            scrollWidth = doc.body.offsetWidth;
            scrollHeight = doc.body.offsetHeight;
        }

        if (self.innerHeight) {
            // Non-IE browser
            innerWidth = self.innerWidth;
            innerHeight = self.innerHeight;
        }
        else if (doc.documentElement && doc.documentElement.clientHeight) {
            // Some versions of IE (IE 6 w/ a DOCTYPE declaration)
            innerWidth = doc.documentElement.clientWidth;
            innerHeight = doc.documentElement.clientHeight;
        }
        else if (doc.body) {
            // Other versions of IE
            innerWidth = doc.body.clientWidth;
            innerHeight = doc.body.clientHeight;
        }

        var maxWidth = Math.max(scrollWidth, innerWidth);
        var maxHeight = Math.max(scrollHeight, innerHeight);
        return [maxWidth, maxHeight, innerWidth, innerHeight];
    };

    // Handles pushing and popping TextareaStates for undo/redo commands.
    // I should rename the stack variables to list.
    function UndoManager(callback, panels) {

        var undoObj = this;
        var undoStack = []; // A stack of undo states
        var stackPtr = 0; // The index of the current state
        var mode = "none";
        var lastState; // The last state
        var timer; // The setTimeout handle for cancelling the timer
        var inputStateObj;

        // Set the mode for later logic steps.
        var setMode = function (newMode, noSave) {
            if (mode != newMode) {
                mode = newMode;
                if (!noSave) {
                    saveState();
                }
            }

            if (!uaSniffed.isIE || mode != "moving") {
                timer = setTimeout(refreshState, 1);
            }
            else {
                inputStateObj = null;
            }
        };

        var refreshState = function (isInitialState) {
            inputStateObj = new TextareaState(panels, isInitialState);
            timer = undefined;
        };

        this.setCommandMode = function () {
            mode = "command";
            saveState();
            timer = setTimeout(refreshState, 0);
        };

        this.canUndo = function () {
            return stackPtr > 1;
        };

        this.canRedo = function () {
            if (undoStack[stackPtr + 1]) {
                return true;
            }
            return false;
        };

        // Removes the last state and restores it.
        this.undo = function () {

            if (undoObj.canUndo()) {
                if (lastState) {
                    // What about setting state -1 to null or checking for undefined?
                    lastState.restore();
                    lastState = null;
                }
                else {
                    undoStack[stackPtr] = new TextareaState(panels);
                    undoStack[--stackPtr].restore();

                    if (callback) {
                        callback();
                    }
                }
            }

            mode = "none";
            panels.input.focus();
            refreshState();
        };

        // Redo an action.
        this.redo = function () {

            if (undoObj.canRedo()) {

                undoStack[++stackPtr].restore();

                if (callback) {
                    callback();
                }
            }

            mode = "none";
            panels.input.focus();
            refreshState();
        };

        var getFullScreenAdapter = function () {
            var selector = {
                fullScreenChange : ['onfullscreenchange', 'onwebkitfullscreenchange', 'onmozfullscreenchange'],
                requestFullscreen : ['requestFullscreen', 'webkitRequestFullScreen', 'mozRequestFullScreen'],
                cancelFullscreen : ['cancelFullscreen', 'webkitCancelFullScreen', 'mozCancelFullScreen']
            }, adapter = {};

            for (var name in selector) {
                var len = selector[name].length, found = false;

                for (var i = 0; i < len; i ++) {
                    var method = selector[name][i];

                    if ('undefined' != typeof(document[method]) || 'undefined' != typeof(document.body[method])) {
                        adapter[name] = method;
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    return false;
                }
            }

            return adapter;
        }

        var isFullScreen = function () {
            return document.fullScreen || 
               document.mozFullScreen || 
               document.webkitIsFullScreen;
        }

        var fullScreenBind = false;

        // fullscreen
        this.fullscreen = function () {
            var input = panels.input, preview = panels.preview, buttonBar = panels.buttonBar,
                adapter = getFullScreenAdapter();

            if (!adapter) {
                return false;
            }

            if (!fullScreenBind) {
                util.addEvent(document, adapter.fullScreenChange.substring(2), function () {
                    if (!isFullScreen()) {
                        input.style.cssText = 'height:350px;';
                        preview.style.cssText = '';
                        buttonBar.style.cssText = '';
                    } else {
                        var windowHeight = window.screen.height - 32;

                        input.style.cssText = "width:50%;background-color:#000000;color:#ffffff;position:absolute;z-index:999;top:32px;left:0;box-sizing: border-box;";
                        input.style.height = windowHeight + 'px';
                        buttonBar.style.cssText = "width:100%;background-color:#ffffff;position:absolute;z-index:1000;top:0;left:0";
                        preview.style.cssText = "width:50%;background-color:#ffffff;position:absolute;z-index:999;top:32px;right:0;box-sizing: border-box;overflow: auto;";
                        preview.style.height = windowHeight + 'px';
                    }
                });
                
                fullScreenBind = true;
            }

            if (!isFullScreen()) {
                document.body[adapter.requestFullscreen]('webkitRequestFullScreen' == adapter.requestFullscreen 
                    ? Element.ALLOW_KEYBOARD_INPUT : null);
            } else {
                document[adapter.cancelFullscreen]();
            }
        };

        // Push the input area state to the stack.
        var saveState = function () {
            var currState = inputStateObj || new TextareaState(panels);

            if (!currState) {
                return false;
            }
            if (mode == "moving") {
                if (!lastState) {
                    lastState = currState;
                }
                return;
            }
            if (lastState) {
                if (undoStack[stackPtr - 1].text != lastState.text) {
                    undoStack[stackPtr++] = lastState;
                }
                lastState = null;
            }
            undoStack[stackPtr++] = currState;
            undoStack[stackPtr + 1] = null;
            if (callback) {
                callback();
            }
        };

        var handleCtrlYZ = function (event) {

            var handled = false;

            if ((event.ctrlKey || event.metaKey) && !event.altKey) {

                // IE and Opera do not support charCode.
                var keyCode = event.charCode || event.keyCode;
                var keyCodeChar = String.fromCharCode(keyCode);

                switch (keyCodeChar.toLowerCase()) {

                    case "y":
                        undoObj.redo();
                        handled = true;
                        break;

                    case "z":
                        if (!event.shiftKey) {
                            undoObj.undo();
                        }
                        else {
                            undoObj.redo();
                        }
                        handled = true;
                        break;
                }
            }

            if (handled) {
                if (event.preventDefault) {
                    event.preventDefault();
                }
                if (window.event) {
                    window.event.returnValue = false;
                }
                return;
            }
        };

        // Set the mode depending on what is going on in the input area.
        var handleModeChange = function (event) {

            if (!event.ctrlKey && !event.metaKey) {

                var keyCode = event.keyCode;

                if ((keyCode >= 33 && keyCode <= 40) || (keyCode >= 63232 && keyCode <= 63235)) {
                    // 33 - 40: page up/dn and arrow keys
                    // 63232 - 63235: page up/dn and arrow keys on safari
                    setMode("moving");
                }
                else if (keyCode == 8 || keyCode == 46 || keyCode == 127) {
                    // 8: backspace
                    // 46: delete
                    // 127: delete
                    setMode("deleting");
                }
                else if (keyCode == 13) {
                    // 13: Enter
                    setMode("newlines");
                }
                else if (keyCode == 27) {
                    // 27: escape
                    setMode("escape");
                }
                else if ((keyCode < 16 || keyCode > 20) && keyCode != 91) {
                    // 16-20 are shift, etc.
                    // 91: left window key
                    // I think this might be a little messed up since there are
                    // a lot of nonprinting keys above 20.
                    setMode("typing");
                }
            }
        };

        var setEventHandlers = function () {
            util.addEvent(panels.input, "keypress", function (event) {
                // keyCode 89: y
                // keyCode 90: z
                if ((event.ctrlKey || event.metaKey) && !event.altKey && (event.keyCode == 89 || event.keyCode == 90)) {
                    event.preventDefault();
                }
            });

            var handlePaste = function () {
                if (uaSniffed.isIE || (inputStateObj && inputStateObj.text != panels.input.value)) {
                    if (timer == undefined) {
                        mode = "paste";
                        saveState();
                        refreshState();
                    }
                }
            };

            util.addEvent(panels.input, "keydown", handleCtrlYZ);
            util.addEvent(panels.input, "keydown", handleModeChange);
            util.addEvent(panels.input, "mousedown", function () {
                setMode("moving");
            });

            panels.input.onpaste = handlePaste;
            panels.input.ondrop = handlePaste;
        };

        var init = function () {
            setEventHandlers();
            refreshState(true);
            saveState();
        };

        init();
    }

    // end of UndoManager

    // The input textarea state/contents.
    // This is used to implement undo/redo by the undo manager.
    function TextareaState(panels, isInitialState) {

        // Aliases
        var stateObj = this;
        var inputArea = panels.input;
        this.init = function () {
            if (!util.isVisible(inputArea)) {
                return;
            }
            if (!isInitialState && doc.activeElement && doc.activeElement !== inputArea) { // this happens when tabbing out of the input box
                return;
            }

            this.setInputAreaSelectionStartEnd();
            this.scrollTop = inputArea.scrollTop;
            if (!this.text && inputArea.selectionStart || inputArea.selectionStart === 0) {
                this.text = inputArea.value;
            }

        }

        // Sets the selected text in the input box after we've performed an
        // operation.
        this.setInputAreaSelection = function () {

            if (!util.isVisible(inputArea)) {
                return;
            }

            if (inputArea.selectionStart !== undefined && !uaSniffed.isOpera) {

                inputArea.focus();
                inputArea.selectionStart = stateObj.start;
                inputArea.selectionEnd = stateObj.end;
                inputArea.scrollTop = stateObj.scrollTop;
            }
            else if (doc.selection) {

                if (doc.activeElement && doc.activeElement !== inputArea) {
                    return;
                }

                inputArea.focus();
                var range = inputArea.createTextRange();
                range.moveStart("character", -inputArea.value.length);
                range.moveEnd("character", -inputArea.value.length);
                range.moveEnd("character", stateObj.end);
                range.moveStart("character", stateObj.start);
                range.select();
            }
        };

        this.setInputAreaSelectionStartEnd = function () {

            if (!panels.ieCachedRange && (inputArea.selectionStart || inputArea.selectionStart === 0)) {

                stateObj.start = inputArea.selectionStart;
                stateObj.end = inputArea.selectionEnd;
            }
            else if (doc.selection) {

                stateObj.text = util.fixEolChars(inputArea.value);

                // IE loses the selection in the textarea when buttons are
                // clicked.  On IE we cache the selection. Here, if something is cached,
                // we take it.
                var range = panels.ieCachedRange || doc.selection.createRange();

                var fixedRange = util.fixEolChars(range.text);
                var marker = "\x07";
                var markedRange = marker + fixedRange + marker;
                range.text = markedRange;
                var inputText = util.fixEolChars(inputArea.value);

                range.moveStart("character", -markedRange.length);
                range.text = fixedRange;

                stateObj.start = inputText.indexOf(marker);
                stateObj.end = inputText.lastIndexOf(marker) - marker.length;

                var len = stateObj.text.length - util.fixEolChars(inputArea.value).length;

                if (len) {
                    range.moveStart("character", -fixedRange.length);
                    while (len--) {
                        fixedRange += "\n";
                        stateObj.end += 1;
                    }
                    range.text = fixedRange;
                }

                if (panels.ieCachedRange)
                    stateObj.scrollTop = panels.ieCachedScrollTop; // this is set alongside with ieCachedRange

                panels.ieCachedRange = null;

                this.setInputAreaSelection();
            }
        };

        // Restore this state into the input area.
        this.restore = function () {

            if (stateObj.text != undefined && stateObj.text != inputArea.value) {
                inputArea.value = stateObj.text;
            }
            this.setInputAreaSelection();
            inputArea.scrollTop = stateObj.scrollTop;
        };

        // Gets a collection of HTML chunks from the inptut textarea.
        this.getChunks = function () {

            var chunk = new Chunks();
            chunk.before = util.fixEolChars(stateObj.text.substring(0, stateObj.start));
            chunk.startTag = "";
            chunk.selection = util.fixEolChars(stateObj.text.substring(stateObj.start, stateObj.end));
            chunk.endTag = "";
            chunk.after = util.fixEolChars(stateObj.text.substring(stateObj.end));
            chunk.scrollTop = stateObj.scrollTop;

            return chunk;
        };

        // Sets the TextareaState properties given a chunk of markdown.
        this.setChunks = function (chunk) {

            chunk.before = chunk.before + chunk.startTag;
            chunk.after = chunk.endTag + chunk.after;

            this.start = chunk.before.length;
            this.end = chunk.before.length + chunk.selection.length;
            this.text = chunk.before + chunk.selection + chunk.after;
            this.scrollTop = chunk.scrollTop;
        };
        this.init();
    };

    function PreviewManager(converter, panels, previewRefreshCallback) {

        var managerObj = this;
        var timeout;
        var elapsedTime;
        var oldInputText;
        var maxDelay = 3000;
        var startType = "delayed"; // The other legal value is "manual"

        // Adds event listeners to elements
        var setupEvents = function (inputElem, listener) {

            util.addEvent(inputElem, "input", listener);
            inputElem.onpaste = listener;
            inputElem.ondrop = listener;

            util.addEvent(inputElem, "keypress", listener);
            util.addEvent(inputElem, "keydown", listener);
        };

        var getDocScrollTop = function () {

            var result = 0;

            if (window.innerHeight) {
                result = window.pageYOffset;
            }
            else
                if (doc.documentElement && doc.documentElement.scrollTop) {
                    result = doc.documentElement.scrollTop;
                }
                else
                    if (doc.body) {
                        result = doc.body.scrollTop;
                    }

            return result;
        };

        var makePreviewHtml = function () {

            // If there is no registered preview panel
            // there is nothing to do.
            if (!panels.preview)
                return;


            var text = panels.input.value;
            if (text && text == oldInputText) {
                return; // Input text hasn't changed.
            }
            else {
                oldInputText = text;
            }

            var prevTime = new Date().getTime();

            text = converter.makeHtml(text);

            // Calculate the processing time of the HTML creation.
            // It's used as the delay time in the event listener.
            var currTime = new Date().getTime();
            elapsedTime = currTime - prevTime;

            pushPreviewHtml(text);
        };

        // setTimeout is already used.  Used as an event listener.
        var applyTimeout = function () {

            if (timeout) {
                clearTimeout(timeout);
                timeout = undefined;
            }

            if (startType !== "manual") {

                var delay = 0;

                if (startType === "delayed") {
                    delay = elapsedTime;
                }

                if (delay > maxDelay) {
                    delay = maxDelay;
                }
                timeout = setTimeout(makePreviewHtml, delay);
            }
        };

        var getScaleFactor = function (panel) {
            if (panel.scrollHeight <= panel.clientHeight) {
                return 1;
            }
            return panel.scrollTop / (panel.scrollHeight - panel.clientHeight);
        };

        var setPanelScrollTops = function () {
            if (panels.preview) {
                panels.preview.scrollTop = (panels.preview.scrollHeight - panels.preview.clientHeight) * getScaleFactor(panels.preview);
            }
        };

        this.refresh = function (requiresRefresh) {

            if (requiresRefresh) {
                oldInputText = "";
                makePreviewHtml();
            }
            else {
                applyTimeout();
            }
        };

        this.processingTime = function () {
            return elapsedTime;
        };

        var isFirstTimeFilled = true;

        // IE doesn't let you use innerHTML if the element is contained somewhere in a table
        // (which is the case for inline editing) -- in that case, detach the element, set the
        // value, and reattach. Yes, that *is* ridiculous.
        var ieSafePreviewSet = function (text) {
            var preview = panels.preview;
            var parent = preview.parentNode;
            var sibling = preview.nextSibling;
            parent.removeChild(preview);
            preview.innerHTML = text;
            if (!sibling)
                parent.appendChild(preview);
            else
                parent.insertBefore(preview, sibling);
        }

        var nonSuckyBrowserPreviewSet = function (text) {
            panels.preview.innerHTML = text;
        }

        var previewSetter;

        var previewSet = function (text) {
            if (previewSetter)
                return previewSetter(text);

            try {
                nonSuckyBrowserPreviewSet(text);
                previewSetter = nonSuckyBrowserPreviewSet;
            } catch (e) {
                previewSetter = ieSafePreviewSet;
                previewSetter(text);
            }
        };

        var pushPreviewHtml = function (text) {

            var emptyTop = position.getTop(panels.input) - getDocScrollTop();

            if (panels.preview) {
                previewSet(text);
                previewRefreshCallback();
            }

            setPanelScrollTops();

            if (isFirstTimeFilled) {
                isFirstTimeFilled = false;
                return;
            }

            var fullTop = position.getTop(panels.input) - getDocScrollTop();

            if (uaSniffed.isIE) {
                setTimeout(function () {
                    window.scrollBy(0, fullTop - emptyTop);
                }, 0);
            }
            else {
                window.scrollBy(0, fullTop - emptyTop);
            }
        };

        var init = function () {

            setupEvents(panels.input, applyTimeout);
            makePreviewHtml();

            if (panels.preview) {
                panels.preview.scrollTop = 0;
            }
        };

        init();
    };

    // Creates the background behind the hyperlink text entry box.
    // And download dialog
    // Most of this has been moved to CSS but the div creation and
    // browser-specific hacks remain here.
    ui.createBackground = function () {

        var background = doc.createElement("div"),
            style = background.style;

        background.className = "wmd-prompt-background";

        style.position = "absolute";
        style.top = "0";

        style.zIndex = "1000";

        if (uaSniffed.isIE) {
            style.filter = "alpha(opacity=50)";
        }
        else {
            style.opacity = "0.5";
        }

        var pageSize = position.getPageSize();
        style.height = pageSize[1] + "px";

        if (uaSniffed.isIE) {
            style.left = doc.documentElement.scrollLeft;
            style.width = doc.documentElement.clientWidth;
        }
        else {
            style.left = "0";
            style.width = "100%";
        }

        doc.body.appendChild(background);
        return background;
    };

    // This simulates a modal dialog box and asks for the URL when you
    // click the hyperlink or image buttons.
    //
    // text: The html for the input box.
    // defaultInputText: The default value that appears in the input box.
    // callback: The function which is executed when the prompt is dismissed, either via OK or Cancel.
    //      It receives a single argument; either the entered text (if OK was chosen) or null (if Cancel
    //      was chosen).
    ui.prompt = function (text, defaultInputText, callback, ok, cancel) {

        // These variables need to be declared at this level since they are used
        // in multiple functions.
        var dialog;         // The dialog box.
        var input;         // The text box where you enter the hyperlink.


        if (defaultInputText === undefined) {
            defaultInputText = "";
        }

        // Used as a keydown event handler. Esc dismisses the prompt.
        // Key code 27 is ESC.
        var checkEscape = function (key) {
            var code = (key.charCode || key.keyCode);
            if (code === 27) {
                close(true);
            }
        };

        // Dismisses the hyperlink input box.
        // isCancel is true if we don't care about the input text.
        // isCancel is false if we are going to keep the text.
        var close = function (isCancel) {
            util.removeEvent(doc.body, "keydown", checkEscape);
            var text = input.value;

            if (isCancel) {
                text = null;
            }
            else {
                // Fixes common pasting errors.
                text = text.replace(/^http:\/\/(https?|ftp):\/\//, '$1://');
                if (!/^(?:https?|ftp):\/\//.test(text))
                    text = 'http://' + text;
            }

            dialog.parentNode.removeChild(dialog);

            callback(text);
            return false;
        };



        // Create the text input box form/window.
        var createDialog = function () {

            // The main dialog box.
            dialog = doc.createElement("div");
            dialog.className = "wmd-prompt-dialog";
            /*
            dialog.style.padding = "10px;";
            dialog.style.position = "fixed";
            dialog.style.width = "400px";
            dialog.style.zIndex = "1001";
            */

            // The dialog text.
            var question = doc.createElement("div");
            question.innerHTML = text;
            // question.style.padding = "5px";
            dialog.appendChild(question);

            // The web form container for the text box and buttons.
            var form = doc.createElement("form"),
                style = form.style;
            form.onsubmit = function () { return close(false); };
            /*
            style.padding = "0";
            style.margin = "0";
            style.cssFloat = "left";
            style.width = "100%";
            style.textAlign = "center";
            style.position = "relative";
            */
            dialog.appendChild(form);

            // The input text box
            input = doc.createElement("input");
            input.type = "text";
            input.value = defaultInputText;
            /*
            style = input.style;
            style.display = "block";
            style.width = "80%";
            style.marginLeft = style.marginRight = "auto";
            */
            form.appendChild(input);

            // The ok button
            var okButton = doc.createElement("button");
            okButton.type = "button";
            okButton.className = "btn-s primary";
            okButton.onclick = function () { return close(false); };
            okButton.innerHTML = ok;
            /*
            style = okButton.style;
            style.margin = "10px";
            style.display = "inline";
            style.width = "7em";
            */

            // The cancel button
            var cancelButton = doc.createElement("button");
            cancelButton.type = "button";
            cancelButton.className = "btn-s";
            cancelButton.onclick = function () { return close(true); };
            cancelButton.innerHTML = cancel;
            /*
            style = cancelButton.style;
            style.margin = "10px";
            style.display = "inline";
            style.width = "7em";
            */

            form.appendChild(okButton);
            form.appendChild(cancelButton);

            util.addEvent(doc.body, "keydown", checkEscape);
            /*
            dialog.style.top = "50%";
            dialog.style.left = "50%";
            dialog.style.display = "block";
            if (uaSniffed.isIE_5or6) {
                dialog.style.position = "absolute";
                dialog.style.top = doc.documentElement.scrollTop + 200 + "px";
                dialog.style.left = "50%";
            }
            */
            doc.body.appendChild(dialog);

            // This has to be done AFTER adding the dialog to the form if you
            // want it to be centered.
            /*
            dialog.style.marginTop = -(position.getHeight(dialog) / 2) + "px";
            dialog.style.marginLeft = -(position.getWidth(dialog) / 2) + "px";
            */

        };

        // Why is this in a zero-length timeout?
        // Is it working around a browser bug?
        setTimeout(function () {

            createDialog();

            var defTextLen = defaultInputText.length;
            if (input.selectionStart !== undefined) {
                input.selectionStart = 0;
                input.selectionEnd = defTextLen;
            }
            else if (input.createTextRange) {
                var range = input.createTextRange();
                range.collapse(false);
                range.moveStart("character", -defTextLen);
                range.moveEnd("character", defTextLen);
                range.select();
            }

            input.focus();
        }, 0);
    };

    function UIManager(postfix, panels, undoManager, previewManager, commandManager, helpOptions, getString) {

        var inputBox = panels.input,
            buttons = {}; // buttons.undo, buttons.link, etc. The actual DOM elements.

        makeSpritedButtonRow();

        var keyEvent = "keydown";
        if (uaSniffed.isOpera) {
            keyEvent = "keypress";
        }

        util.addEvent(inputBox, keyEvent, function (key) {
            // Check to see if we have a button key and, if so execute the callback.
            if ((key.ctrlKey || key.metaKey) && !key.altKey && !key.shiftKey) {

                var keyCode = key.charCode || key.keyCode;
                var keyCodeStr = String.fromCharCode(keyCode).toLowerCase();

                switch (keyCodeStr) {
                    case "b":
                        doClick(buttons.bold);
                        break;
                    case "i":
                        doClick(buttons.italic);
                        break;
                    case "l":
                        doClick(buttons.link);
                        break;
                    case "q":
                        doClick(buttons.quote);
                        break;
                    case "k":
                        doClick(buttons.code);
                        break;
                    case "g":
                        doClick(buttons.image);
                        break;
                    case "o":
                        doClick(buttons.olist);
                        break;
                    case "u":
                        doClick(buttons.ulist);
                        break;
                    case 'm':
                        doClick(buttons.fullscreen);
                        break;
                    case "h":
                        doClick(buttons.heading);
                        break;
                    case "r":
                        doClick(buttons.hr);
                        break;
                    case "y":
                        doClick(buttons.redo);
                        break;
                    case "z":
                        if (key.shiftKey) {
                            doClick(buttons.redo);
                        }
                        else {
                            doClick(buttons.undo);
                        }
                        break;
                    default:
                        return;
                }


                if (key.preventDefault) {
                    key.preventDefault();
                }

                if (window.event) {
                    window.event.returnValue = false;
                }
            }else if(key.keyCode==9){
                var tab = {};
                tab.textOp = bindCommand("doTab");
                doClick(tab);

                if (key.preventDefault) {
                    key.preventDefault();
                }
                if (window.event) {
                    window.event.returnValue = false;
                }
            }
        });

        // Auto-indent on shift-enter
        util.addEvent(inputBox, "keyup", function (key) {
            if (key.shiftKey && !key.ctrlKey && !key.metaKey) {
                var keyCode = key.charCode || key.keyCode;
                // Character 13 is Enter
                if (keyCode === 13) {
                    var fakeButton = {};
                    fakeButton.textOp = bindCommand("doAutoindent");
                    doClick(fakeButton);
                }
            }
        });

        // special handler because IE clears the context of the textbox on ESC
        if (uaSniffed.isIE) {
            util.addEvent(inputBox, "keydown", function (key) {
                var code = key.keyCode;
                if (code === 27) {
                    return false;
                }
            });
        }


        // Perform the button's action.
        function doClick(button) {

            inputBox.focus();

            if (button.textOp) {

                if (undoManager) {
                    undoManager.setCommandMode();
                }

                var state = new TextareaState(panels);

                if (!state) {
                    return;
                }

                var chunks = state.getChunks();

                // Some commands launch a "modal" prompt dialog.  Javascript
                // can't really make a modal dialog box and the WMD code
                // will continue to execute while the dialog is displayed.
                // This prevents the dialog pattern I'm used to and means
                // I can't do something like this:
                //
                // var link = CreateLinkDialog();
                // makeMarkdownLink(link);
                //
                // Instead of this straightforward method of handling a
                // dialog I have to pass any code which would execute
                // after the dialog is dismissed (e.g. link creation)
                // in a function parameter.
                //
                // Yes this is awkward and I think it sucks, but there's
                // no real workaround.  Only the image and link code
                // create dialogs and require the function pointers.
                var fixupInputArea = function () {

                    inputBox.focus();

                    if (chunks) {
                        state.setChunks(chunks);
                    }

                    state.restore();
                    previewManager.refresh();
                };

                var noCleanup = button.textOp(chunks, fixupInputArea);

                if (!noCleanup) {
                    fixupInputArea();
                }

            }

            if (button.execute) {
                button.execute(undoManager);
            }
        };

        function setupButton(button, isEnabled) {

            var normalYShift = "0px";
            var disabledYShift = "-20px";
            var highlightYShift = "-40px";
            var image = button.getElementsByTagName("span")[0];
            if (isEnabled) {
                image.style.backgroundPosition = button.XShift + " " + normalYShift;
                button.onmouseover = function () {
                    image.style.backgroundPosition = this.XShift + " " + highlightYShift;
                };

                button.onmouseout = function () {
                    image.style.backgroundPosition = this.XShift + " " + normalYShift;
                };

                // IE tries to select the background image "button" text (it's
                // implemented in a list item) so we have to cache the selection
                // on mousedown.
                if (uaSniffed.isIE) {
                    button.onmousedown = function () {
                        if (doc.activeElement && doc.activeElement !== panels.input) { // we're not even in the input box, so there's no selection
                            return;
                        }
                        panels.ieCachedRange = document.selection.createRange();
                        panels.ieCachedScrollTop = panels.input.scrollTop;
                    };
                }

                if (!button.isHelp) {
                    button.onclick = function () {
                        if (this.onmouseout) {
                            this.onmouseout();
                        }
                        doClick(this);
                        return false;
                    }
                }
            }
            else {
                image.style.backgroundPosition = button.XShift + " " + disabledYShift;
                button.onmouseover = button.onmouseout = button.onclick = function () { };
            }
        }

        function bindCommand(method) {
            if (typeof method === "string")
                method = commandManager[method];
            return function () { method.apply(commandManager, arguments); }
        }

        function makeSpritedButtonRow() {

            var buttonBar = panels.buttonBar;

            var normalYShift = "0px";
            var disabledYShift = "-20px";
            var highlightYShift = "-40px";

            var buttonRow = document.createElement("ul");
            buttonRow.id = "wmd-button-row" + postfix;
            buttonRow.className = 'wmd-button-row';
            buttonRow = buttonBar.appendChild(buttonRow);
            var xPosition = 0;
            var makeButton = function (id, title, XShift, textOp) {
                var button = document.createElement("li");
                button.className = "wmd-button";
                button.style.left = xPosition + "px";
                xPosition += 25;
                var buttonImage = document.createElement("span");
                button.id = id + postfix;
                button.appendChild(buttonImage);
                button.title = title;
                button.XShift = XShift;
                if (textOp)
                    button.textOp = textOp;
                setupButton(button, true);
                buttonRow.appendChild(button);
                return button;
            };
            var makeSpacer = function (num) {
                var spacer = document.createElement("li");
                spacer.className = "wmd-spacer wmd-spacer" + num;
                spacer.id = "wmd-spacer" + num + postfix;
                buttonRow.appendChild(spacer);
                xPosition += 25;
            }

            buttons.bold = makeButton("wmd-bold-button", getString("bold"), "0px", bindCommand("doBold"));
            buttons.italic = makeButton("wmd-italic-button", getString("italic"), "-20px", bindCommand("doItalic"));
            makeSpacer(1);
            buttons.link = makeButton("wmd-link-button", getString("link"), "-40px", bindCommand(function (chunk, postProcessing) {
                return this.doLinkOrImage(chunk, postProcessing, false);
            }));
            buttons.quote = makeButton("wmd-quote-button", getString("quote"), "-60px", bindCommand("doBlockquote"));
            buttons.code = makeButton("wmd-code-button", getString("code"), "-80px", bindCommand("doCode"));
            buttons.image = makeButton("wmd-image-button", getString("image"), "-100px", bindCommand(function (chunk, postProcessing) {
                return this.doLinkOrImage(chunk, postProcessing, true);
            }));
            makeSpacer(2);
            buttons.olist = makeButton("wmd-olist-button", getString("olist"), "-120px", bindCommand(function (chunk, postProcessing) {
                this.doList(chunk, postProcessing, true);
            }));
            buttons.ulist = makeButton("wmd-ulist-button", getString("ulist"), "-140px", bindCommand(function (chunk, postProcessing) {
                this.doList(chunk, postProcessing, false);
            }));
            buttons.heading = makeButton("wmd-heading-button", getString("heading"), "-160px", bindCommand("doHeading"));
            buttons.hr = makeButton("wmd-hr-button", getString("hr"), "-180px", bindCommand("doHorizontalRule"));
            makeSpacer(3);
            buttons.undo = makeButton("wmd-undo-button", getString("undo"), "-200px", null);
            buttons.undo.execute = function (manager) { if (manager) manager.undo(); };

            var redoTitle = /win/.test(nav.platform.toLowerCase()) ?
                getString("redo") :
                getString("redomac"); // mac and other non-Windows platforms

            buttons.redo = makeButton("wmd-redo-button", redoTitle, "-220px", null);
            buttons.redo.execute = function (manager) { if (manager) manager.redo(); };

            buttons.fullscreen = makeButton("wmd-fullscreen-button", getString("fullscreen"), "-220px", null);
            buttons.fullscreen.execute = function (manager) {
                if (manager) manager.fullscreen();
            };

            if (helpOptions) {
                var helpButton = document.createElement("li");
                var helpButtonImage = document.createElement("span");
                helpButton.appendChild(helpButtonImage);
                helpButton.className = "wmd-button wmd-help-button";
                helpButton.id = "wmd-help-button" + postfix;
                helpButton.XShift = "-240px";
                helpButton.isHelp = true;
                helpButton.style.right = "0px";
                helpButton.title = getString("help");
                helpButton.onclick = helpOptions.handler;

                setupButton(helpButton, true);
                buttonRow.appendChild(helpButton);
                buttons.help = helpButton;
            }

            setUndoRedoButtonStates();
        }

        function setUndoRedoButtonStates() {
            if (undoManager) {
                setupButton(buttons.undo, undoManager.canUndo());
                setupButton(buttons.redo, undoManager.canRedo());
            }
        };

        this.setUndoRedoButtonStates = setUndoRedoButtonStates;

    }

    function CommandManager(pluginHooks, getString) {
        this.hooks = pluginHooks;
        this.getString = getString;
    }

    var commandProto = CommandManager.prototype;

    // The markdown symbols - 4 spaces = code, > = blockquote, etc.
    commandProto.prefixes = "(?:\\s{4,}|\\s*>|\\s*-\\s+|\\s*\\d+\\.|=|\\+|-|_|\\*|#|\\s*\\[[^\n]]+\\]:)";

    // Remove markdown symbols from the chunk selection.
    commandProto.unwrap = function (chunk) {
        var txt = new re("([^\\n])\\n(?!(\\n|" + this.prefixes + "))", "g");
        chunk.selection = chunk.selection.replace(txt, "$1 $2");
    };

    commandProto.wrap = function (chunk, len) {
        this.unwrap(chunk);
        var regex = new re("(.{1," + len + "})( +|$\\n?)", "gm"),
            that = this;

        chunk.selection = chunk.selection.replace(regex, function (line, marked) {
            if (new re("^" + that.prefixes, "").test(line)) {
                return line;
            }
            return marked + "\n";
        });

        chunk.selection = chunk.selection.replace(/\s+$/, "");
    };

    commandProto.doBold = function (chunk, postProcessing) {
        return this.doBorI(chunk, postProcessing, 2, this.getString("boldexample"));
    };

    commandProto.doItalic = function (chunk, postProcessing) {
        return this.doBorI(chunk, postProcessing, 1, this.getString("italicexample"));
    };

    // chunk: The selected region that will be enclosed with */**
    // nStars: 1 for italics, 2 for bold
    // insertText: If you just click the button without highlighting text, this gets inserted
    commandProto.doBorI = function (chunk, postProcessing, nStars, insertText) {

        // Get rid of whitespace and fixup newlines.
        chunk.trimWhitespace();
        chunk.selection = chunk.selection.replace(/\n{2,}/g, "\n");

        // Look for stars before and after.  Is the chunk already marked up?
        // note that these regex matches cannot fail
        var starsBefore = /(\**$)/.exec(chunk.before)[0];
        var starsAfter = /(^\**)/.exec(chunk.after)[0];

        var prevStars = Math.min(starsBefore.length, starsAfter.length);

        // Remove stars if we have to since the button acts as a toggle.
        if ((prevStars >= nStars) && (prevStars != 2 || nStars != 1)) {
            chunk.before = chunk.before.replace(re("[*]{" + nStars + "}$", ""), "");
            chunk.after = chunk.after.replace(re("^[*]{" + nStars + "}", ""), "");
        }
        else if (!chunk.selection && starsAfter) {
            // It's not really clear why this code is necessary.  It just moves
            // some arbitrary stuff around.
            chunk.after = chunk.after.replace(/^([*_]*)/, "");
            chunk.before = chunk.before.replace(/(\s?)$/, "");
            var whitespace = re.$1;
            chunk.before = chunk.before + starsAfter + whitespace;
        }
        else {

            // In most cases, if you don't have any selected text and click the button
            // you'll get a selected, marked up region with the default text inserted.
            if (!chunk.selection && !starsAfter) {
                chunk.selection = insertText;
            }

            // Add the true markup.
            var markup = nStars <= 1 ? "*" : "**"; // shouldn't the test be = ?
            chunk.before = chunk.before + markup;
            chunk.after = markup + chunk.after;
        }

        return;
    };

    commandProto.stripLinkDefs = function (text, defsToAdd) {

        text = text.replace(/^[ ]{0,3}\[(\d+)\]:[ \t]*\n?[ \t]*<?(\S+?)>?[ \t]*\n?[ \t]*(?:(\n*)["(](.+?)[")][ \t]*)?(?:\n+|$)/gm,
            function (totalMatch, id, link, newlines, title) {
                defsToAdd[id] = totalMatch.replace(/\s*$/, "");
                if (newlines) {
                    // Strip the title and return that separately.
                    defsToAdd[id] = totalMatch.replace(/["(](.+?)[")]$/, "");
                    return newlines + title;
                }
                return "";
            });

        return text;
    };

    commandProto.addLinkDef = function (chunk, linkDef) {

        var refNumber = 0; // The current reference number
        var defsToAdd = {}; //
        // Start with a clean slate by removing all previous link definitions.
        chunk.before = this.stripLinkDefs(chunk.before, defsToAdd);
        chunk.selection = this.stripLinkDefs(chunk.selection, defsToAdd);
        chunk.after = this.stripLinkDefs(chunk.after, defsToAdd);

        var defs = "";
        var regex = /(\[)((?:\[[^\]]*\]|[^\[\]])*)(\][ ]?(?:\n[ ]*)?\[)(\d+)(\])/g;

        var addDefNumber = function (def) {
            refNumber++;
            def = def.replace(/^[ ]{0,3}\[(\d+)\]:/, "  [" + refNumber + "]:");
            defs += "\n" + def;
        };

        // note that
        // a) the recursive call to getLink cannot go infinite, because by definition
        //    of regex, inner is always a proper substring of wholeMatch, and
        // b) more than one level of nesting is neither supported by the regex
        //    nor making a lot of sense (the only use case for nesting is a linked image)
        var getLink = function (wholeMatch, before, inner, afterInner, id, end) {
            inner = inner.replace(regex, getLink);
            if (defsToAdd[id]) {
                addDefNumber(defsToAdd[id]);
                return before + inner + afterInner + refNumber + end;
            }
            return wholeMatch;
        };

        chunk.before = chunk.before.replace(regex, getLink);

        if (linkDef) {
            addDefNumber(linkDef);
        }
        else {
            chunk.selection = chunk.selection.replace(regex, getLink);
        }

        var refOut = refNumber;

        chunk.after = chunk.after.replace(regex, getLink);

        if (chunk.after) {
            chunk.after = chunk.after.replace(/\n*$/, "");
        }
        if (!chunk.after) {
            chunk.selection = chunk.selection.replace(/\n*$/, "");
        }

        chunk.after += "\n\n" + defs;

        return refOut;
    };

    // takes the line as entered into the add link/as image dialog and makes
    // sure the URL and the optinal title are "nice".
    function properlyEncoded(linkdef) {
        return linkdef.replace(/^\s*(.*?)(?:\s+"(.+)")?\s*$/, function (wholematch, link, title) {
            link = link.replace(/\?.*$/, function (querypart) {
                return querypart.replace(/\+/g, " "); // in the query string, a plus and a space are identical
            });
            link = decodeURIComponent(link); // unencode first, to prevent double encoding
            link = encodeURI(link).replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29');
            link = link.replace(/\?.*$/, function (querypart) {
                return querypart.replace(/\+/g, "%2b"); // since we replaced plus with spaces in the query part, all pluses that now appear where originally encoded
            });
            if (title) {
                title = title.trim ? title.trim() : title.replace(/^\s*/, "").replace(/\s*$/, "");
                title = title.replace(/"/g, "quot;").replace(/\(/g, "&#40;").replace(/\)/g, "&#41;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            }
            return title ? link + ' "' + title + '"' : link;
        });
    }

    commandProto.doLinkOrImage = function (chunk, postProcessing, isImage) {

        chunk.trimWhitespace();
        chunk.findTags(/\s*!?\[/, /\][ ]?(?:\n[ ]*)?(\[.*?\])?/);
        var background;

        if (chunk.endTag.length > 1 && chunk.startTag.length > 0) {

            chunk.startTag = chunk.startTag.replace(/!?\[/, "");
            chunk.endTag = "";
            this.addLinkDef(chunk, null);

        }
        else {

            // We're moving start and end tag back into the selection, since (as we're in the else block) we're not
            // *removing* a link, but *adding* one, so whatever findTags() found is now back to being part of the
            // link text. linkEnteredCallback takes care of escaping any brackets.
            chunk.selection = chunk.startTag + chunk.selection + chunk.endTag;
            chunk.startTag = chunk.endTag = "";

            if (/\n\n/.test(chunk.selection)) {
                this.addLinkDef(chunk, null);
                return;
            }
            var that = this;
            // The function to be executed when you enter a link and press OK or Cancel.
            // Marks up the link and adds the ref.
            var linkEnteredCallback = function (link) {

                background.parentNode.removeChild(background);

                if (link !== null) {
                    // (                          $1
                    //     [^\\]                  anything that's not a backslash
                    //     (?:\\\\)*              an even number (this includes zero) of backslashes
                    // )
                    // (?=                        followed by
                    //     [[\]]                  an opening or closing bracket
                    // )
                    //
                    // In other words, a non-escaped bracket. These have to be escaped now to make sure they
                    // don't count as the end of the link or similar.
                    // Note that the actual bracket has to be a lookahead, because (in case of to subsequent brackets),
                    // the bracket in one match may be the "not a backslash" character in the next match, so it
                    // should not be consumed by the first match.
                    // The "prepend a space and finally remove it" steps makes sure there is a "not a backslash" at the
                    // start of the string, so this also works if the selection begins with a bracket. We cannot solve
                    // this by anchoring with ^, because in the case that the selection starts with two brackets, this
                    // would mean a zero-width match at the start. Since zero-width matches advance the string position,
                    // the first bracket could then not act as the "not a backslash" for the second.
                    chunk.selection = (" " + chunk.selection).replace(/([^\\](?:\\\\)*)(?=[[\]])/g, "$1\\").substr(1);

                    var linkDef = " [999]: " + properlyEncoded(link);

                    var num = that.addLinkDef(chunk, linkDef);
                    chunk.startTag = isImage ? "![" : "[";
                    chunk.endTag = "][" + num + "]";

                    if (!chunk.selection) {
                        if (isImage) {
                            chunk.selection = that.getString("imagename") || that.getString("imagedescription");
                        }
                        else {
                            chunk.selection = that.getString("linkname") || that.getString("linkdescription");
                        }
                    }
                }
                postProcessing();
            };

            background = ui.createBackground();

            if (isImage) {
                if (!this.hooks.insertImageDialog(linkEnteredCallback))
                    ui.prompt(this.getString("imagedialog"), imageDefaultText, linkEnteredCallback, this.getString("ok"), this.getString("cancel"));
            }
            else {
                if (!this.hooks.insertLinkDialog(linkEnteredCallback))
                    ui.prompt(this.getString("linkdialog"), linkDefaultText, linkEnteredCallback, this.getString("ok"), this.getString("cancel"));
            }
            return true;
        }
    };

    // When making a list, hitting shift-enter will put your cursor on the next line
    // at the current indent level.
    commandProto.doAutoindent = function (chunk, postProcessing) {

        var commandMgr = this,
            fakeSelection = false;

        chunk.before = chunk.before.replace(/(\n|^)[ ]{0,3}([*+-]|\d+[.])[ \t]*\n$/, "\n\n");
        chunk.before = chunk.before.replace(/(\n|^)[ ]{0,3}>[ \t]*\n$/, "\n\n");
        chunk.before = chunk.before.replace(/(\n|^)[ \t]+\n$/, "\n\n");

        // There's no selection, end the cursor wasn't at the end of the line:
        // The user wants to split the current list item / code line / blockquote line
        // (for the latter it doesn't really matter) in two. Temporarily select the
        // (rest of the) line to achieve this.
        if (!chunk.selection && !/^[ \t]*(?:\n|$)/.test(chunk.after)) {
            chunk.after = chunk.after.replace(/^[^\n]*/, function (wholeMatch) {
                chunk.selection = wholeMatch;
                return "";
            });
            fakeSelection = true;
        }

        if (/(\n|^)[ ]{0,3}([*+-]|\d+[.])[ \t]+.*\n$/.test(chunk.before)) {
            if (commandMgr.doList) {
                commandMgr.doList(chunk);
            }
        }
        if (/(\n|^)[ ]{0,3}>[ \t]+.*\n$/.test(chunk.before)) {
            if (commandMgr.doBlockquote) {
                commandMgr.doBlockquote(chunk);
            }
        }
        if (/(\n|^)(\t|[ ]{4,}).*\n$/.test(chunk.before)) {
            if (commandMgr.doCode) {
                commandMgr.doCode(chunk);
            }
        }

        if (fakeSelection) {
            chunk.after = chunk.selection + chunk.after;
            chunk.selection = "";
        }
    };

    commandProto.doBlockquote = function (chunk, postProcessing) {

        chunk.selection = chunk.selection.replace(/^(\n*)([^\r]+?)(\n*)$/,
            function (totalMatch, newlinesBefore, text, newlinesAfter) {
                chunk.before += newlinesBefore;
                chunk.after = newlinesAfter + chunk.after;
                return text;
            });

        chunk.before = chunk.before.replace(/(>[ \t]*)$/,
            function (totalMatch, blankLine) {
                chunk.selection = blankLine + chunk.selection;
                return "";
            });

        chunk.selection = chunk.selection.replace(/^(\s|>)+$/, "");
        chunk.selection = chunk.selection || this.getString("quoteexample");

        // The original code uses a regular expression to find out how much of the
        // text *directly before* the selection already was a blockquote:

        /*
        if (chunk.before) {
        chunk.before = chunk.before.replace(/\n?$/, "\n");
        }
        chunk.before = chunk.before.replace(/(((\n|^)(\n[ \t]*)*>(.+\n)*.*)+(\n[ \t]*)*$)/,
        function (totalMatch) {
        chunk.startTag = totalMatch;
        return "";
        });
        */

        // This comes down to:
        // Go backwards as many lines a possible, such that each line
        //  a) starts with ">", or
        //  b) is almost empty, except for whitespace, or
        //  c) is preceeded by an unbroken chain of non-empty lines
        //     leading up to a line that starts with ">" and at least one more character
        // and in addition
        //  d) at least one line fulfills a)
        //
        // Since this is essentially a backwards-moving regex, it's susceptible to
        // catstrophic backtracking and can cause the browser to hang;
        // see e.g. http://meta.stackoverflow.com/questions/9807.
        //
        // Hence we replaced this by a simple state machine that just goes through the
        // lines and checks for a), b), and c).

        var match = "",
            leftOver = "",
            line;
        if (chunk.before) {
            var lines = chunk.before.replace(/\n$/, "").split("\n");
            var inChain = false;
            for (var i = 0; i < lines.length; i++) {
                var good = false;
                line = lines[i];
                inChain = inChain && line.length > 0; // c) any non-empty line continues the chain
                if (/^>/.test(line)) {                // a)
                    good = true;
                    if (!inChain && line.length > 1)  // c) any line that starts with ">" and has at least one more character starts the chain
                        inChain = true;
                } else if (/^[ \t]*$/.test(line)) {   // b)
                    good = true;
                } else {
                    good = inChain;                   // c) the line is not empty and does not start with ">", so it matches if and only if we're in the chain
                }
                if (good) {
                    match += line + "\n";
                } else {
                    leftOver += match + line;
                    match = "\n";
                }
            }
            if (!/(^|\n)>/.test(match)) {             // d)
                leftOver += match;
                match = "";
            }
        }

        chunk.startTag = match;
        chunk.before = leftOver;

        // end of change

        if (chunk.after) {
            chunk.after = chunk.after.replace(/^\n?/, "\n");
        }

        chunk.after = chunk.after.replace(/^(((\n|^)(\n[ \t]*)*>(.+\n)*.*)+(\n[ \t]*)*)/,
            function (totalMatch) {
                chunk.endTag = totalMatch;
                return "";
            }
        );

        var replaceBlanksInTags = function (useBracket) {

            var replacement = useBracket ? "> " : "";

            if (chunk.startTag) {
                chunk.startTag = chunk.startTag.replace(/\n((>|\s)*)\n$/,
                    function (totalMatch, markdown) {
                        return "\n" + markdown.replace(/^[ ]{0,3}>?[ \t]*$/gm, replacement) + "\n";
                    });
            }
            if (chunk.endTag) {
                chunk.endTag = chunk.endTag.replace(/^\n((>|\s)*)\n/,
                    function (totalMatch, markdown) {
                        return "\n" + markdown.replace(/^[ ]{0,3}>?[ \t]*$/gm, replacement) + "\n";
                    });
            }
        };

        if (/^(?![ ]{0,3}>)/m.test(chunk.selection)) {
            this.wrap(chunk, SETTINGS.lineLength - 2);
            chunk.selection = chunk.selection.replace(/^/gm, "> ");
            replaceBlanksInTags(true);
            chunk.skipLines();
        } else {
            chunk.selection = chunk.selection.replace(/^[ ]{0,3}> ?/gm, "");
            this.unwrap(chunk);
            replaceBlanksInTags(false);

            if (!/^(\n|^)[ ]{0,3}>/.test(chunk.selection) && chunk.startTag) {
                chunk.startTag = chunk.startTag.replace(/\n{0,2}$/, "\n\n");
            }

            if (!/(\n|^)[ ]{0,3}>.*$/.test(chunk.selection) && chunk.endTag) {
                chunk.endTag = chunk.endTag.replace(/^\n{0,2}/, "\n\n");
            }
        }

        chunk.selection = this.hooks.postBlockquoteCreation(chunk.selection);

        if (!/\n/.test(chunk.selection)) {
            chunk.selection = chunk.selection.replace(/^(> *)/,
            function (wholeMatch, blanks) {
                chunk.startTag += blanks;
                return "";
            });
        }
    };

    commandProto.doCode = function (chunk, postProcessing) {

        var hasTextBefore = /\S[ ]*$/.test(chunk.before);
        var hasTextAfter = /^[ ]*\S/.test(chunk.after);

        // Use 'four space' markdown if the selection is on its own
        // line or is multiline.
        if ((!hasTextAfter && !hasTextBefore) || /\n/.test(chunk.selection)) {

            chunk.before = chunk.before.replace(/[ ]{4}$/,
                function (totalMatch) {
                    chunk.selection = totalMatch + chunk.selection;
                    return "";
                });

            var nLinesBack = 1;
            var nLinesForward = 1;

            if (/(\n|^)(\t|[ ]{4,}).*\n$/.test(chunk.before)) {
                nLinesBack = 0;
            }
            if (/^\n(\t|[ ]{4,})/.test(chunk.after)) {
                nLinesForward = 0;
            }

            chunk.skipLines(nLinesBack, nLinesForward);

            if (!chunk.selection) {
                chunk.startTag = "    ";
                chunk.selection = this.getString("codeexample");
            }
            else {
                if (/^[ ]{0,3}\S/m.test(chunk.selection)) {
                    if (/\n/.test(chunk.selection))
                        chunk.selection = chunk.selection.replace(/^/gm, "    ");
                    else // if it's not multiline, do not select the four added spaces; this is more consistent with the doList behavior
                        chunk.before += "    ";
                }
                else {
                    chunk.selection = chunk.selection.replace(/^(?:[ ]{4}|[ ]{0,3}\t)/gm, "");
                }
            }
        }
        else {
            // Use backticks (`) to delimit the code block.

            chunk.trimWhitespace();
            chunk.findTags(/`/, /`/);

            if (!chunk.startTag && !chunk.endTag) {
                chunk.startTag = chunk.endTag = "`";
                if (!chunk.selection) {
                    chunk.selection = this.getString("codeexample");
                }
            }
            else if (chunk.endTag && !chunk.startTag) {
                chunk.before += chunk.endTag;
                chunk.endTag = "";
            }
            else {
                chunk.startTag = chunk.endTag = "";
            }
        }
    };

    commandProto.doList = function (chunk, postProcessing, isNumberedList) {

        // These are identical except at the very beginning and end.
        // Should probably use the regex extension function to make this clearer.
        var previousItemsRegex = /(\n|^)(([ ]{0,3}([*+-]|\d+[.])[ \t]+.*)(\n.+|\n{2,}([*+-].*|\d+[.])[ \t]+.*|\n{2,}[ \t]+\S.*)*)\n*$/;
        var nextItemsRegex = /^\n*(([ ]{0,3}([*+-]|\d+[.])[ \t]+.*)(\n.+|\n{2,}([*+-].*|\d+[.])[ \t]+.*|\n{2,}[ \t]+\S.*)*)\n*/;

        // The default bullet is a dash but others are possible.
        // This has nothing to do with the particular HTML bullet,
        // it's just a markdown bullet.
        var bullet = "-";

        // The number in a numbered list.
        var num = 1;

        // Get the item prefix - e.g. " 1. " for a numbered list, " - " for a bulleted list.
        var getItemPrefix = function () {
            var prefix;
            if (isNumberedList) {
                prefix = " " + num + ". ";
                num++;
            }
            else {
                prefix = " " + bullet + " ";
            }
            return prefix;
        };

        // Fixes the prefixes of the other list items.
        var getPrefixedItem = function (itemText) {

            // The numbering flag is unset when called by autoindent.
            if (isNumberedList === undefined) {
                isNumberedList = /^\s*\d/.test(itemText);
            }

            // Renumber/bullet the list element.
            itemText = itemText.replace(/^[ ]{0,3}([*+-]|\d+[.])\s/gm,
                function (_) {
                    return getItemPrefix();
                });

            return itemText;
        };

        chunk.findTags(/(\n|^)*[ ]{0,3}([*+-]|\d+[.])\s+/, null);

        if (chunk.before && !/\n$/.test(chunk.before) && !/^\n/.test(chunk.startTag)) {
            chunk.before += chunk.startTag;
            chunk.startTag = "";
        }

        if (chunk.startTag) {

            var hasDigits = /\d+[.]/.test(chunk.startTag);
            chunk.startTag = "";
            chunk.selection = chunk.selection.replace(/\n[ ]{4}/g, "\n");
            this.unwrap(chunk);
            chunk.skipLines();

            if (hasDigits) {
                // Have to renumber the bullet points if this is a numbered list.
                chunk.after = chunk.after.replace(nextItemsRegex, getPrefixedItem);
            }
            if (isNumberedList == hasDigits) {
                return;
            }
        }

        var nLinesUp = 1;

        chunk.before = chunk.before.replace(previousItemsRegex,
            function (itemText) {
                if (/^\s*([*+-])/.test(itemText)) {
                    bullet = re.$1;
                }
                nLinesUp = /[^\n]\n\n[^\n]/.test(itemText) ? 1 : 0;
                return getPrefixedItem(itemText);
            });

        if (!chunk.selection) {
            chunk.selection = this.getString("litem");
        }

        var prefix = getItemPrefix();

        var nLinesDown = 1;

        chunk.after = chunk.after.replace(nextItemsRegex,
            function (itemText) {
                nLinesDown = /[^\n]\n\n[^\n]/.test(itemText) ? 1 : 0;
                return getPrefixedItem(itemText);
            });

        chunk.trimWhitespace(true);
        chunk.skipLines(nLinesUp, nLinesDown, true);
        chunk.startTag = prefix;
        var spaces = prefix.replace(/./g, " ");
        this.wrap(chunk, SETTINGS.lineLength - spaces.length);
        chunk.selection = chunk.selection.replace(/\n/g, "\n" + spaces);

    };

    commandProto.doHeading = function (chunk, postProcessing) {

        // Remove leading/trailing whitespace and reduce internal spaces to single spaces.
        chunk.selection = chunk.selection.replace(/\s+/g, " ");
        chunk.selection = chunk.selection.replace(/(^\s+|\s+$)/g, "");

        // If we clicked the button with no selected text, we just
        // make a level 2 hash header around some default text.
        if (!chunk.selection) {
            chunk.startTag = "## ";
            chunk.selection = this.getString("headingexample");
            chunk.endTag = " ##";
            return;
        }

        var headerLevel = 0;     // The existing header level of the selected text.

        // Remove any existing hash heading markdown and save the header level.
        chunk.findTags(/#+[ ]*/, /[ ]*#+/);
        if (/#+/.test(chunk.startTag)) {
            headerLevel = re.lastMatch.length;
        }
        chunk.startTag = chunk.endTag = "";

        // Try to get the current header level by looking for - and = in the line
        // below the selection.
        chunk.findTags(null, /\s?(-+|=+)/);
        if (/=+/.test(chunk.endTag)) {
            headerLevel = 1;
        }
        if (/-+/.test(chunk.endTag)) {
            headerLevel = 2;
        }

        // Skip to the next line so we can create the header markdown.
        chunk.startTag = chunk.endTag = "";
        chunk.skipLines(1, 1);

        // We make a level 2 header if there is no current header.
        // If there is a header level, we substract one from the header level.
        // If it's already a level 1 header, it's removed.
        var headerLevelToCreate = headerLevel == 0 ? 2 : headerLevel - 1;

        if (headerLevelToCreate > 0) {

            // The button only creates level 1 and 2 underline headers.
            // Why not have it iterate over hash header levels?  Wouldn't that be easier and cleaner?
            var headerChar = headerLevelToCreate >= 2 ? "-" : "=";
            var len = chunk.selection.length;
            if (len > SETTINGS.lineLength) {
                len = SETTINGS.lineLength;
            }
            chunk.endTag = "\n";
            while (len--) {
                chunk.endTag += headerChar;
            }
        }
    };

    commandProto.doHorizontalRule = function (chunk, postProcessing) {
        chunk.startTag = "----------\n";
        chunk.selection = "";
        chunk.skipLines(2, 1, true);
    }

    commandProto.doTab = function (chunk, postProcessing) {
        chunk.startTag = "    ";
        chunk.selection = "";
    }
})();
