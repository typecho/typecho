<?php

/**
 *
 * Parses for table markup.
 *
 * This class implements a Text_Wiki_Parse to find source text marked as
 * a set of table rows, where a line start (and optionally ends) with a
 * single-pipe (|) and uses single-pipes to separate table cells.
 * The rows must be on sequential lines (no blank lines between them).
 * A blank line indicates the beginning of other text or another table.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 * @author Paul M. Jones <pmjones@php.net>
 *
 * @license LGPL
 *
 * @version $Id: Table.php 182 2008-09-14 15:56:00Z i.feelinglucky $
 *
 */


class Text_Wiki_Parse_Table extends Text_Wiki_Parse {


    /**
     *
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     *
     * @var string
     *
     * @see parse()
     *
     */

    var $regex = '/\n((\|).*)(\n)(?!(\|))/Us';


    /**
     *
     * Generates a replacement for the matched text.
     *
     * Token options are:
     *
     * 'type' =>
     *     'table_start' : the start of a bullet list
     *     'table_end'   : the end of a bullet list
     *     'row_start' : the start of a number list
     *     'row_end'   : the end of a number list
     *     'cell_start'   : the start of item text (bullet or number)
     *     'cell_end'     : the end of item text (bullet or number)
     *
     * 'cols' => the number of columns in the table (for 'table_start')
     *
     * 'rows' => the number of rows in the table (for 'table_start')
     *
     * 'span' => column span (for 'cell_start')
     *
     * 'attr' => column attribute flag (for 'cell_start')
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return A series of text and delimited tokens marking the different
     * table elements and cell text.
     *
     */

    function process(&$matches)
    {
        // our eventual return value
        $return = '';

        // the number of columns in the table
        $num_cols = 0;

        // the number of rows in the table
        $num_rows = 0;

        // rows are separated by newlines in the matched text
        $rows = explode("\n", $matches[1]);

        // loop through each row
        foreach ($rows as $row) {

            // increase the row count
            $num_rows ++;

            // remove first and last (optional) pipe
            $row = substr($row, 1);
            if ($row[strlen($row) - 1] == '|') {
                $row = substr($row, 0, -1);
            }

            // cells are separated by pipes
            $cells = explode("|", $row);
            
            if (count($cells) == 1 && $cells[0][0] == '=' && ($num_rows == 1 || $num_rows == count($rows)) && ! $caption) {
                $caption = trim(trim($cells[0], '='));
            
                // start the caption...
                $return .= $this->wiki->addToken(
                    $this->rule,
                    array ('type' => 'caption_start')
                );

                // ...add the content...
                $return .= $caption;

                // ...and end the caption.
                $return .= $this->wiki->addToken(
                    $this->rule,
                    array ('type' => 'caption_end')
                );
            }
            else {

                // update the column count
                if (count($cells) > $num_cols) {
                    $num_cols = count($cells);
                }

                // start a new row
                $return .= $this->wiki->addToken(
                    $this->rule,
                    array('type' => 'row_start')
                );

                for ($i = 0; $i < count($cells); $i++) {
                    $cell = $cells[$i];

                    // by default, cells span only one column (their own)
                    $span = 1;
                    $attr = '';
                    
                    while ($i + 1 < count($cells) && ! strlen($cells[$i + 1])) {
                        $i++;
                        $span++;
                    }

                    if ($cell[0] == '=') {
                        $attr = 'header';
                        $cell = trim($cell, '=');
                    }

                    // start a new cell...
                    $return .= $this->wiki->addToken(
                        $this->rule,
                        array (
                            'type' => 'cell_start',
                            'attr' => $attr,
                            'span' => $span
                        )
                    );

                    // ...add the content...
                    $return .= trim($cell);

                    // ...and end the cell.
                    $return .= $this->wiki->addToken(
                        $this->rule,
                        array (
                            'type' => 'cell_end',
                            'attr' => $attr,
                            'span' => $span
                        )
                    );
                }

                // end the row
                $return .= $this->wiki->addToken(
                    $this->rule,
                    array('type' => 'row_end')
                );
            }
        }

        // we're done!
        return
            "\n\n".
            $this->wiki->addToken(
                $this->rule,
                array(
                    'type' => 'table_start',
                    'rows' => $num_rows,
                    'cols' => $num_cols
                )
            ).
            $return.
            $this->wiki->addToken(
                $this->rule,
                array(
                    'type' => 'table_end'
                )
            ).
            "\n\n";
    }
}
?>