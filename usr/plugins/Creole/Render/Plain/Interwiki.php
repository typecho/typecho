<?php

class Text_Wiki_Render_Plain_Interwiki extends Text_Wiki_Render {
    
    /**
    * 
    * Renders a token into text matching the requested format.
    * 
    * @access public
    * 
    * @param array $options The "options" portion of the token (second
    * element).
    * 
    * @return string The text rendered from the token options.
    * 
    */
    
    function token($options)
    {
        if (isset($options['url'])) {
            // calculated by the parser (e.g. Mediawiki)
            $href = $options['url'];
        } else {
            $href = $options['site'] . ':' . $options['page'];
        }
        return $options['text'] . ' (' . $href . ')';
    }
}
?>
