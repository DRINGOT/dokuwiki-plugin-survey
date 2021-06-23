<?php
/**
 * DokuWiki Plugin survey (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  DRINGOT <ddonovan.ringot@hotmail.fr>
 */

// must be run within Dokuwiki
if (!defined("DOKU_INC")) {
    die();
}

if (!defined("DOKU_LF")) {
    define("DOKU_LF", "\n");
}
if (!defined("DOKU_TAB")) {
    define("DOKU_TAB", "\t");
}
if (!defined("DOKU_PLUGIN")) {
    define("DOKU_PLUGIN", DOKU_INC . "lib/plugins/");
}

require_once DOKU_PLUGIN . "syntax.php";

class syntax_plugin_survey_survey extends DokuWiki_Syntax_Plugin
{
    public $readingEnabled = false;

    public $readingTemp = "";

    public $surveyNumber = 0;

    public function getType()
    {
        return "protected";
    }

    public function getPType()
    {
        return "normal";
    }

    public function getSort()
    {
        return 35;
    }

    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern(
            "<survey>",
            $mode,
            "plugin_survey_survey"
        );
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern("</survey>", "plugin_survey_survey");
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $this->readingTemp = "";
                break;

            case DOKU_LEXER_EXIT:
                return [$this->readingTemp];

            case DOKU_LEXER_UNMATCHED:
                $this->readingTemp .= $match;
                break;
        }

        return [];
    }

    public function render($format, Doku_Renderer $renderer, $data)
    {
        if ($mode != "xhtml") {
            return false;
        }

        if (count($data) > 0) {
            $helper = plugin_load("helper", "survey_survey");

            $surveySyntax = $helper->sanitizeSyntax($data[0]);

            $survey = $helper->interpretSurvey($surveySyntax);

            $renderer->doc .=
                '<div class="DokuwikiSurvey" id="survey_' .
                $this->surveyNumber .
                "\">\n";
            $renderer->doc .= "    <p class=\"lastSurvey\" />\n";
            $renderer->doc .= "    <p class=\"surveyQuestion\" />\n";
            $renderer->doc .= "    <p class=\"surveyAnswers\" />\n";
            $renderer->doc .= "</div>";

            $renderer->doc .= "<script type=\"text/javascript\">\n";
            $renderer->doc .=
                "    var surveyConfig_" .
                $this->surveyNumber .
                " = " .
                json_encode($survey) .
                ";\n";
            $renderer->doc .= "    if (!survey) {\n";
            $renderer->doc .= "      var survey = [];\n";
            $renderer->doc .= "    }\n";
            $renderer->doc .=
                "    survey[" .
                $this->surveyNumber .
                "] = new Dokuwiki_Survey(" .
                $this->surveyNumber .
                ", surveyConfig_" .
                $this->surveyNumber .
                ");\n";
            $renderer->doc .=
                "    survey[" .
                $this->surveyNumber .
                '].lang = { "back": ' .
                '"' .
                $this->getLang("Back") .
                '" }' .
                "\n";

            $renderer->doc .=
                "    survey[" . $this->surveyNumber . "].makeSurvey()";
            $renderer->doc .= "</script>\n";

            $this->surveyNumber++;
        }

        return true;
    }
}
