/**
 * Dokuwki Survey Plugin Frontend Script
 */

function Dokuwiki_Survey (surveyId, surveyConfig) {
    
    this.surveyId = surveyId;
    this.surveyConfig = surveyConfig;
    
    // Skip root
    
    this.currentSurvey = this.surveyConfig._children[0];
    this.currentSurvey.lastSurvey = this.surveyConfig;
    
};

Dokuwiki_Survey.prototype.goToSurvey = function (childId) {
    
    var tmpLastSurvey;
    
    if (childId == -1) {
        
        this.currentSurvey = this.currentSurvey.lastSurvey;
        
    } else {
        
        tmpLastSurvey = this.currentSurvey;
        
        this.currentSurvey = this.currentSurvey._children[childId]._children[0];
        this.currentSurvey.lastSurvey = tmpLastSurvey;
        
    }
    
    this.makeSurvey();
    
};

Dokuwiki_Survey.prototype.makeSurvey = function () {
    
    var answerHtml,
        lastSurvey,
        surveyElement,
        surveyConfig,
        surveyQuestion,
        surveyAnswers,
        currentAnswer;
        
    surveyConfig = this.currentSurvey;
    
    surveyElement = document.getElementById("survey_" + this.surveyId);
    lastSurvey = surveyElement.getElementsByClassName("lastSurvey")[0];
    surveyQuestion = surveyElement.getElementsByClassName("surveyQuestion")[0];
    surveyAnswers = surveyElement.getElementsByClassName("surveyAnswers")[0];
    
    if (surveyConfig.lastSurvey._name !== "root") {
    
        lastSurvey.onClick = "survey[this.surveyId].goToSurvey(-1)";
        lastSurvey.innerHTML = "<p onClick=\"" +
                               "survey[" +
                               this.surveyId +
                               "].goToSurvey(-1)\">" +
                               this.lang.back +
                               "</p>\n";
                              
        lastSurvey.style.display = "block";
        
    } else {
        
        lastSurvey.innerHTML = "";
        lastSurvey.style.display = "none";
        
    }
        
    surveyQuestion.innerHTML = surveyConfig._name;
    answerHtml = "<ul>";
    
    for (currentAnswer in surveyConfig._children) {
        
        if (surveyConfig._children.hasOwnProperty(currentAnswer)) {
            
            if (surveyConfig._children[currentAnswer]["_hasChildren"]) {
            
                answerHtml = answerHtml +
                    "  <li onClick=\"" +
                    "survey[" +
                    this.surveyId +
                    "].goToSurvey(" +
                    currentAnswer +
                    ")\">" +
                    surveyConfig._children[currentAnswer]["_name"] +
                    "</li>\n";
            } else {
                
                answerHtml = answerHtml +
                    "  <li>" +
                    surveyConfig._children[currentAnswer]["_name"] +
                    "</li>\n";
                
            }
        }
        
    }
    
    answerHtml = answerHtml + "</ul>";
    
    surveyAnswers.innerHTML = answerHtml;
    
};