<?php

////////////////////////////////////////////////////////////////////////////////
// @Kaom Vibolrith Senior Software Developer
// Date: 18.04.2014
// Am Stollheen 18, 55120 Mainz, Germany
////////////////////////////////////////////////////////////////////////////////
require_once 'models/assessment/EvaluationSubjectAssessment.php';

class jsonEvaluationSubjectAssessment extends EvaluationSubjectAssessment {

    public function __construct()
    {
        
    }

    public function setParams($params)
    {
        if (isset($params["start"]))
            $this->start = (int) $params["start"];

        if (isset($params["limit"]))
            $this->limit = (int) $params["limit"];

        if (isset($params["date"]))
            $this->date = addText($params["date"]);

        if (isset($params["monthyear"]))
            $this->monthyear = addText($params["monthyear"]);

        if (isset($params["setId"]))
            $this->setId = addText($params["setId"]);

        if (isset($params["term"]))
            $this->term = addText($params["term"]);

        if (isset($params["academicId"]))
            $this->academicId = (int) $params["academicId"];

        if (isset($params["studentId"]))
            $this->studentId = addText($params["studentId"]);

        if (isset($params["subjectId"]))
            $this->subjectId = (int) $params["subjectId"];

        if (isset($params["assignmentId"]))
            $this->assignmentId = (int) $params["assignmentId"];

        if (isset($params["globalSearch"]))
            $this->globalSearch = addText($params["globalSearch"]);

        if (isset($params["id"]))
            $this->studentId = addText($params["id"]);

        if (isset($params["newValue"]))
        {
            $this->newValue = addText($params["newValue"]);
        }

        if (isset($params["section"]))
            $this->section = addText($params["section"]);

        if (isset($params["field"]))
            $this->actionField = addText($params["field"]);

        if (isset($params["MODIFY_DATE"]))
            $this->modify_date = addText($params["MODIFY_DATE"]);

        if (isset($params["CONTENT"]))
            $this->content = addText($params["CONTENT"]);

        if (isset($params["tmp_name"]))
            $this->tmp_name = $params["tmp_name"];

        if (isset($params["comboValue"]))
        {
            $this->comboValue = addText($params["comboValue"]);
        }

        if (isset($params["target"]))
        {
            $this->target = addText($params["target"]);
        }

        if (isset($params["oldValue"]))
            $this->oldValue = addText($params["oldValue"]);
    }

    public function jsonListStudentSubjectAssignments($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $data = $this->getListStudentSubjectAssignments();

        $a = array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        return array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
    }

    public function jsonActionTeacherScoreEnter($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);
        $facette = $this->actionTeacherScoreEnter();

        if ($facette)
        {
            if ($facette->POINTS_REPEAT)
            {
                $SCORE = $facette->POINTS_REPEAT;
                $SCORE_REPEAT = $facette->POINTS;
            }
            else
            {
                $SCORE = $facette->POINTS;
                $SCORE_REPEAT = "---";
            }
        }
        else
        {
            $SCORE = "---";
            $SCORE_REPEAT = "---";
        }

        return array(
            "success" => true
            , "SCHORE_DATE" => $this->countTeacherScoreDate()
            , "SCORE" => $SCORE
            , "SCORE_REPEAT" => $SCORE_REPEAT
        );
    }

    public function jsonSubjectResultsByMonth($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $data = $this->getDisplaySubjectResultsByMonth();

        $a = array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        return array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
    }

    public function jsonSubjectResultsByTerm($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $data = $this->getDisplaySubjectResultsByTerm();

        $a = array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        return array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
    }

    public function jsonSubjectResultsByYear($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $data = $this->getDisplaySubjectResultsByYear();

        $a = array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        return array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
    }

    public function jsonActionStudentSubjectAssessment($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);
        $facette = $this->actionStudentSubjectAssessment();

        $data = array();

        if ($facette)
        {
            $data["DISPLAY_REPEAT"] = $facette->SUBJECT_VALUE_REPEAT;
        }

        return array("success" => true, "data" => $data);
    }

    public function jsonActionPublishSubjectAssessment($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);
        $this->actionPublishSubjectAssessment();

        return array("success" => true);
    }

    public function jsonListStudentsTeacherScoreEnter($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $data = $this->getListStudentsTeacherScoreEnter();

        $a = array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        return array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
    }

    public function jsonActionDeleteOneStudentTeacherScoreEnter($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $this->actionDeleteOneStudentTeacherScoreEnter();

        return array("success" => true);
    }

    public function jsonActionDeleteAllStudentsTeacherScoreEnter($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $this->actionDeleteAllStudentsTeacherScoreEnter();

        return array("success" => true);
    }

    public function jsonActionDeleteSubjectScoreAssessment($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $this->actionDeleteSubjectScoreAssessment();

        return array("success" => true);
    }

    public function jsonAcitonSubjectAssignmentModifyScoreDate($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $this->acitonSubjectAssignmentModifyScoreDate();

        return array("success" => true);
    }

    public function jsonActionContentTeacherScoreInputDate($params)
    {
        $this->setParams($params);
        $this->actionContentTeacherScoreInputDate();

        return array("success" => true);
    }

    public function jsonLoadContentTeacherScoreInputDate($params)
    {
        $this->setParams($params);

        return Array(
            "success" => true
            , "data" => $this->loadContentTeacherScoreInputDate()
        );
    }
    
    public function jsonScoreImport($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);
        $params["tmp_name"] = $_FILES["xlsfile"]['tmp_name'];

        if (in_array($_FILES["xlsfile"]["type"], mimeTypes("EXCEL")))
        {
            $this->setParams($params);
            $this->actionScoreImport();
        }

        return array("success" => true);
    }

    public function jsonCreditSubjectStatus($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);
        $this->setParams($params);

        $data = $this->getDisplayCreditSubjectStatus();

        $a = array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        return array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
    }

}

?>