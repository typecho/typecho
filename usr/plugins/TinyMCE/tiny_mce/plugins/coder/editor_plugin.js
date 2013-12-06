/**
 * $Id: editor_plugin_src.js 201 2007-02-12 15:56:56Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	tinymce.create('tinymce.plugins.CoderPlugin', {
		init : function(ed, url) {
            
			ed.onClick.add(function(ed, e) {
				e = e.target;

				if (e.nodeName === 'CODE' || e.nodeName === 'PRE' || e.className.indexOf("typecho-plugin") >= 0)
					ed.selection.select(e);
			});

            
			ed.onBeforeSetContent.add(function(ed, o) {
				
                var _replace = function (g, a, b, c) {
                    
                    c = c.trim().replace(/( |<|>|\r\n|\r|\n)/g, function (e) {
                    
                        switch (e) {
                        
                            case "<":
                                return "&lt;";
                                
                            case ">":
                                return "&gt;";
                            
                            case "\r\n":
                            case "\r":
                            case "\n":
                                return '<br />';
                                
                            case " ":
                                return '&nbsp;';
                                
                            default:
                                return;
                        
                        }
                    
                    });
                    
                    return '<' + a + b + '>' + c + '</' + a + '>';
                };
                
                o.content = o.content.replace(/<(code)([^>]*)>([\s\S]*?)<\/(code)>/ig, _replace);
                o.content = o.content.replace(/<(pre)([^>]*)>([\s\S]*?)<\/(pre)>/ig, _replace);
			});
            
            /*
			ed.onPostProcess.add(function(ed, o) {
				if (o.get) {
					o.content = o.content.replace(/<textarea([^>]*)>/ig, '<code$1>');
					o.content = o.content.replace(/<\/textarea>/ig, '</code>');
                }
			});
            */
		},

		getInfo : function() {
			return {
				longname : 'Coder',
				author : 'Typecho Team',
				authorurl : 'http://typecho.org',
				infourl : 'http://typecho.org',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('coder', tinymce.plugins.CoderPlugin);
})();
