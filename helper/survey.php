<?php
/**
 * DokuWiki Plugin survey (Helper Component)
 * 
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Dennis Ploeger <develop@dieploegers.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class helper_plugin_survey_survey extends DokuWiki_Plugin {
    
    public function getMethods() {
        
        return array(
        
            array(
            
                "name" => "sanitizeSyntax",
                "desc" => "Clean syntax before interpreting it",
                "params" => array(
                
                    "The syntax to sanitize" => "string"
                
                ),
                "return" => array(
                
                    "Sanitized Syntaxstring" => "string"
                
                )
            
            ),
            array(
            
                "name" => "interpretSurvey",
                "desc" => "Interprets survey syntax text and returns " 
                          . "a survey configuration",
                "params" => array(
                
                    "Syntax text for the survey" => "string",
                    "Current line in the syntax" => "int",
                    "Current level of survey" => "int"
                
                ),
                "return" => array(
                
                    "The survey configuration as a hash" => "array"
                
                )
            
            )
        
        );
        
    }
    
    /**
     * Clean syntax before interpreting it
     * 
     * @param String $surveySyntax The syntax to sanitize
     * 
     * @return String Sanitized Syntaxstring
     */
    
    public function sanitizeSyntax($surveySyntax) {
        
        // Remove \r's from the syntax.
        
        $surveySyntax = preg_replace("/\r/", "", $surveySyntax);
        
        // Sanitize syntax. Remove empty lines and such
        
        $tmp = array();
        
        $surveyArray = explode("\n", $surveySyntax);
        
        foreach ($surveyArray as $syntaxLine) {
            
            // Only use good syntax, discard other lines
            
            if (preg_match('/^ *  \* .*$/', $syntaxLine)) {
                
                $tmp[] = $syntaxLine;
                
            } else {
                
                dbglog(
                    "Discarded survey syntaxline: " . $syntaxLine,
                    "Survey-Plugin SanitizeSyntax"
                );
                
            }
            
        }
        
        return implode("\n", $tmp);
        
    }
    
    public function renderText($lineText) {
        
        // Link
        
        if (preg_match("/\[\[(.*)\]\]/", $lineText, $matches)) {
            
            if (preg_match("/^([^|]*)\|(.*)$/", $matches[1], $titleMatches)) {
                
                $link = $titleMatches[1];
                $name = $titleMatches[2];
                
            } else {
                
                $link = $name = $matches[1];
                
            }
            
            if (preg_match("/^(http|ftp)/", $link)) {
                
                // External link
                
                $lineText = str_replace(
                    $matches[0], 
                    '<a href="'
                    . $link
                    . '">'
                    . $name
                    . '</a>',
                    $lineText
                );
                
            } else {
                
                // Internal link
                
                $lineText = str_replace(
                    $matches[0],
                    html_wikilink($link, $name),
                    $lineText
                );
                
            }
            
        } else {
        
            $lineText = preg_replace(
                "/\*\*([^\*]*)\*\*/",
                "<b>$1</b>",
                $lineText
            );
            $lineText = preg_replace(
                "/_([^_]*)_/",
                "<u>$1</u>",
                $lineText
            );
            $lineText = preg_replace(
                "/\/\/([^\/]*)\/\//",
                "<i>$1</i>",
                $lineText
            );

        }
        
        return $lineText;
        
    }
    
    /**
     * 
     * 
     * As an example, it interprets the following source text into
     * this survey configuration array:
     * 
     *   * Question A
     *      * Answer A-A
     *      * Question A-B
     *          * Answer A-B-A
     *          * Answer A-B-B
     *   * Question B
     *      * Answer B-A
     *      * Answer B-B
     *
     * array(
     *     "_name": "root",
     *     "_hasChildren": true,
     *     "_children": array(
     *         array(
     *             "_name": "Question A",
     *             "_hasChildren": true,
     *             "_children": array(
     *                 array(
     *                     "_name": "Answer A-A",
     *                     "_hasChildren": false
     *                 ),
     *                 array(
     *                     "_name": "Question A-B",
     *                     "_hasChildren": true,
     *                     "_children": array(
     *                         array(
     *                             "_name": "Answer A-B-A",
     *                             "_hasChildren": false
     *                         ),
     *                         array(
     *                             "_name: "Answer A-B-B",
     *                             "_hasChildren": false
     *                         )
     *                     )
     *                 )
     *             )
     *         ),
     *         array(
     *             "_name:"Question B",
     *             "_hasChildren": true,
     *             "_children": array(
     *                 array(
     *                     "_name": "Answer B-A",
     *                     "_hasChildren": false
     *                 ),
     *                 array(
     *                     "_name": "Answer B-B",
     *                     "_hasChildren": false
     *                 )
     *             )
     *         )
     *     )
     * );
     * 
     * @param String $syntaxText   Syntax text for the survey
     * @param int    $currentLine  Current line in the syntax
     * @param int    $currentLevel Current level of survey
     * 
     * @return Array The survey configuration as a hash
     */
    
    public function interpretSurvey(
        $syntaxText, 
        $currentLine = 0,
        $currentLevel = -1
    ) {
        
        $syntaxArray = explode("\n", $syntaxText);
        
        $returnArray = array();
        
        if ($currentLevel == -1) {
            
            $returnArray["_name"] = "root";
            $returnArray["_children"] = array();
            
        } else {
            
            $workLine = $syntaxArray[$currentLine];
            
            preg_match('/^( *)  \* (.*)$/', $workLine, $lineMatch);
            
            $returnArray["_name"] = $this->renderText($lineMatch[2]);
            $returnArray["_children"] = array();
            
            $currentLine++;
            
        }
            
        while ($currentLine < count($syntaxArray)) {

            $workLine = $syntaxArray[$currentLine];
            
            preg_match('/^( *)  \* (.*)$/', $workLine, $lineMatch);
            
            $nextLine = $syntaxArray[$currentLine + 1];
            
            preg_match('/^( *)  \* (.*)$/', $nextLine, $nextLineMatch);
            
            $lineLevel = strlen($lineMatch[1]) / 2;
            $nextLineLevel = strlen($nextLineMatch[1]) / 2;
            
            if ($lineLevel == $nextLineLevel) {
                
                // Add a child
                
                $returnArray["_children"][] = array(
                    "_name" => $this->renderText($lineMatch[2]),
                    "_hasChildren" => false
                );
                
            } elseif ($lineLevel < $nextLineLevel) {
                
                // Add a child with children
                
                $subArray = $this->interpretSurvey(
                    $syntaxText,
                    $currentLine,
                    $lineLevel
                );
                
                $returnArray["_children"][] = $subArray;
                
                $currentLine = $subArray["_currentLine"];
                
                $newNextLine = $syntaxArray[$currentLine + 1];
            
                preg_match(
                    '/^( *)  \* (.*)$/', 
                    $newNextLine, 
                    $newNextLineMatch
                );
                
                $newNextLineLevel = strlen($newNextLineMatch[1]) / 2;
                    
                if ($nextLineLevel > $newNextLineLevel + 1) {

                    $returnArray["_hasChildren"] = true;
                    $returnArray["_currentLine"] = $currentLine;
                    
                    return $returnArray;
                    
                }
                
            } else {
                
                // We're done here. Return.
                
                // Add a child
                
                $returnArray["_children"][] = array(
                    "_name" => $this->renderText($lineMatch[2]),
                    "_hasChildren" => false
                );
                
                $returnArray["_hasChildren"] = true;
                $returnArray["_currentLine"] = $currentLine;
                
                return $returnArray;
                
            }
            
            $currentLine++;
            
        }
        
        // We're through
        
        if (count($returnArray["_children"]) > 0) {
                    
            $returnArray["_hasChildren"] = true;
            
        }
        
        $returnArray["_currentLine"] = $currentLine;
        
        return $returnArray;
        
    }

}

// vim:ts=4:sw=4:et:
