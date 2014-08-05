<?php

////////////////////////////////////////////////////////////////////////////////
// @Kaom Vibolrith Senior Software Developer
// Date: 28.03.2012
// Am Stollheen 18, 55120 Mainz, Germany
////////////////////////////////////////////////////////////////////////////////
require_once 'utiles/Utiles.php';
require_once 'include/Common.inc.php';
require_once 'models/UserAuth.php';
require_once 'models/training/TrainingDBAccess.php';
require_once "models/" . Zend_Registry::get('MODUL_API_PATH') . "/student/StudentDBAccess.php";
require_once "models/" . Zend_Registry::get('MODUL_API_PATH') . "/finance/StudentFeeDBAccess.php";
require_once "models/training/TrainingSubjectDBAccess.php";
require_once "models/" . Zend_Registry::get('MODUL_API_PATH') . "/student/StudentDBAccess.php";
require_once 'models/assessment/AssessmentConfig.php';
require_once "models/" . Zend_Registry::get('MODUL_API_PATH') . "/assignment/AssignmentTempDBAccess.php"; //@CHHE Vathana
require_once setUserLoacalization();

class TrainingAssessmentDBAccess extends StudentTrainingDBAccess {

    public $data = Array();
    //
    public $assignmentObject = null;
    //
    public $trainingSubject = null;
    //
    public $subjectId = null;
    public $trainingObject = null;

    static function getInstance()
    {

        return new TrainingAssessmentDBAccess();
    }

    public function __construct($trainingId = false, $subjectId = false, $assignmentId = false)
    {

        $this->DB_ASSIGNMENT = AssignmentTempDBAccess::getInstance();
        $this->trainingId = $trainingId;
        $this->subjectId = $subjectId;
        $this->assignmentId = $assignmentId;
    }

    public function getTrainingSubject()
    {

        if ($this->subjectId && $this->getTrainingObject())
        {
            return TrainingSubjectDBAccess::findSubjectTraining($this->subjectId, $this->getTrainingObject());
        }
    }

    //$veasna
    public static function findTrainingAssignmentStudent($trainingSubjectId, $studentId)
    {
        $facette = self::findTrainingSubject($trainingSubjectId);
        $SELECT_A = array(
            'SHORT AS SHORT'
            , 'NAME AS NAME'
        );
        $SELECT_B = array(
            'SCORE AS SCORE'
        );
        $SQL = self::dbAccess()->select();
        $SQL->from(array('A' => 't_assignment_temp'), $SELECT_A);
        $SQL->joinLeft(array('B' => 't_student_training_assignment'), 'A.ID=B.ASSIGNMENT', $SELECT_B);
        $SQL->where("B.STUDENT = '" . $studentId . "'");
        $SQL->where("B.SUBJECT = '" . $facette->SUBJECT . "'");
        $SQL->where("B.ASSIGNMENT = '" . $facette->ASSIGNMENT . "'");
        $results = self::dbAccess()->fetchAll($SQL);
        //return $results;
        $data = array();

        if ($results)
        {
            $data["NAME"] = $results[0]->NAME;
            $data["SHORT"] = $results[0]->SHORT;
            $data["SCORE"] = displayNumberFormat($results[0]->SCORE);
            $data["SCORE_MIN"] = displayNumberFormat($facette->SCORE_MIN) ? $facette->SCORE_MIN : 0;
            $data["SCORE_MAX"] = displayNumberFormat($facette->SCORE_MAX) ? $facette->SCORE_MAX : 0;
            $data["DESCRIPTION"] = setShowText($facette->DESCRIPTION);
            $data["GOALS"] = setShowText($facette->GOALS);
            $data["MATERIALS"] = setShowText($facette->MATERIALS);
            $data["EVALUATION_TYPE"] = setShowText($facette->EVALUATION_TYPE);
            $data["OBJECTIVES"] = setShowText($facette->OBJECTIVES);
        }
        else
        {
            $data["NAME"] = $facette->ASSIGNMENTNAME;
            $data["SCORE"] = "---";
            $data["SCORE_MIN"] = displayNumberFormat($facette->SCORE_MIN) ? $facette->SCORE_MIN : 0;
            $data["SCORE_MAX"] = displayNumberFormat($facette->SCORE_MAX) ? $facette->SCORE_MAX : 0;
            $data["DESCRIPTION"] = setShowText($facette->DESCRIPTION);
            $data["GOALS"] = setShowText($facette->GOALS);
            $data["MATERIALS"] = setShowText($facette->MATERIALS);
            $data["EVALUATION_TYPE"] = setShowText($facette->EVALUATION_TYPE);
            $data["OBJECTIVES"] = setShowText($facette->OBJECTIVES);
        }
        return array(
            "success" => true
            , "data" => $data
        );
    }

    public function getStudentTrainingScoreAssignment($studentId, $trainingId, $subjectId, $assignmentId, $date)
    {

        $SQL = self::dbAccess()->select();
        $SQL->from(Array('A' => "t_student_training_assignment"), Array("*"));
        $SQL->joinLeft(Array('B' => 't_training_subject'), 'A.ASSIGNMENT=B.ASSIGNMENT', Array("MAX_POSSIBLE_SCORE"));

        if ($assignmentId)
            $SQL->where("A.ASSIGNMENT = '" . $assignmentId . "'");
        if ($subjectId)
            $SQL->where("A.SUBJECT = '" . $subjectId . "'");
        if ($trainingId)
            $SQL->where("A.TRAINING = '" . $trainingId . "'");
        if ($studentId)
            $SQL->where("A.STUDENT = '" . $studentId . "'");
        if ($date)
            $SQL->where("A.SCORE_DATE = '" . $date . "'");
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchRow($SQL);
    }

    public function jsonActionPublishTrainingSubjectAssessment($encrypParams)
    {
        $params = Utiles::setPostDecrypteParams($encrypParams);

        $this->trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $this->subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";


        return array(
            "success" => true
        );
    }

    protected function scoreListSubjectByTraining($subjectId, $trainingObject, $listAssignments)
    {

        $data = Array();
        $entries = $this->listStudentsByTraining();

        if ($entries)
        {
            foreach ($entries as $value)
            {
                $data[] = $this->getStudentTrainingSubjectAverage(
                        $value->STUDENT_ID
                        , $subjectId
                        , $trainingObject
                        , $listAssignments);
            }
        }
        return $data;
    }

    public function getCountScoreEnterByStudentTraining($studentId, $subjectId, $setIncludeInValuation)
    {

        $SQL = self::dbAccess()->select();
        $SQL->distinct();
        $SQL->from(Array('A' => "t_student_training_assignment"), Array("COUNT(*) AS C"));
        $SQL->joinLeft(Array('B' => 't_assignment_temp'), 'A.ASSIGNMENT_ID=B.ID', Array());

        if ($subjectId)
        {
            $SQL->where("A.SUBJECT = ?", $subjectId);
        }

        if ($studentId)
        {
            $SQL->where("A.STUDENT = '" . $studentId . "'");
        }
        if ($this->trainingId)
        {
            $SQL->where("A.TRAINING = '" . $this->trainingId . "'");
        }

        if ($setIncludeInValuation)
        {

            $SQL->where("B.INCLUDE_IN_EVALUATION IN (1)");
        }

        $result = self::dbAccess()->fetchRow($SQL);
        //error_log($SQL->__toString());
        return $result ? $result->C : 0;
    }

    public function jsonActionContentTeacherScoreInputDateTraining($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);

        $comment = isset($params["name"]) ? addText($params["name"]) : "";
        $studentId = isset($params["studentId"]) ? addText($params["studentId"]) : "";
        $date = isset($params["date"]) ? addText($params["date"]) : "";
        $trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";
        $assignmentId = isset($params["assignmentId"]) ? addText($params["assignmentId"]) : "";

        $SAVEDATA['TEACHER_COMMENTS'] = addText($comment);

        $WHERE = Array();
        $WHERE[] = "STUDENT = '" . $studentId . "'";
        $WHERE[] = "TRAINING = '" . $trainingId . "'";
        $WHERE[] = "SUBJECT = '" . $subjectId . "'";
        $WHERE[] = "ASSIGNMENT = '" . $assignmentId . "'";
        $WHERE[] = "SCORE_DATE = '" . $date . "'";
        self::dbAccess()->update('t_student_training_assignment', $SAVEDATA, $WHERE);

        return Array(
            "success" => true
        );
    }

    public function jsonActionDeleteSingleScoreTraining($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);

        $studentId = isset($params["studentId"]) ? addText($params["studentId"]) : "";
        $date = isset($params["date"]) ? addText($params["date"]) : "";
        $trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";
        $assignmentId = isset($params["assignmentId"]) ? addText($params["assignmentId"]) : "";

        $WHERE = Array();
        $WHERE[] = self::dbAccess()->quoteInto('STUDENT = ?', $studentId);
        $WHERE[] = self::dbAccess()->quoteInto('ASSIGNMENT = ?', $assignmentId);
        $WHERE[] = self::dbAccess()->quoteInto('SUBJECT = ?', $subjectId);
        $WHERE[] = self::dbAccess()->quoteInto('TRAINING = ?', $trainingId);
        $WHERE[] = self::dbAccess()->quoteInto('SCORE_DATE = ?', $date);
        self::dbAccess()->delete('t_student_training_assignment', $WHERE);

        return Array("success" => true);
    }

    public function jsonActionDeleteAllScoresAssignmentTraining($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);

        $date = isset($params["date"]) ? addText($params["date"]) : "";
        $trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";
        $assignmentId = isset($params["assignmentId"]) ? addText($params["assignmentId"]) : "";

        $WHERE_A = Array();
        $WHERE_A[] = self::dbAccess()->quoteInto('ASSIGNMENT = ?', $assignmentId);
        $WHERE_A[] = self::dbAccess()->quoteInto('SUBJECT= ?', $subjectId);
        $WHERE_A[] = self::dbAccess()->quoteInto('TRAINING = ?', $trainingId);
        $WHERE_A[] = self::dbAccess()->quoteInto('SCORE_DATE = ?', $date);
        self::dbAccess()->delete('t_student_training_assignment', $WHERE_A);

        $WHERE_B = Array();
        $WHERE_B[] = self::dbAccess()->quoteInto('ASSIGNMENT_ID = ?', $assignmentId);
        $WHERE_B[] = self::dbAccess()->quoteInto('SUBJECT_ID = ?', $subjectId);
        $WHERE_B[] = self::dbAccess()->quoteInto('TRAINING_ID = ?', $trainingId);
        $WHERE_B[] = self::dbAccess()->quoteInto('SCORE_INPUT_DATE = ?', $date);
        self::dbAccess()->delete('t_student_score_date', $WHERE_B);

        return Array("success" => true);
    }

    public function jsonActionDeleteAllScoresSubjectTraining($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);

        $trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";

        $WHERE = Array();
        $WHERE[] = "TRAINING_ID ='" . $trainingId . "'";
        $WHERE[] = "SUBJECT_ID ='" . $subjectId . "'";

        $WHERE_A = Array();
        $WHERE_A[] = "TRAINING ='" . $trainingId . "'";
        $WHERE_A[] = "SUBJECT='" . $subjectId . "'";

        self::dbAccess()->delete('t_student_training_assignment', $WHERE_A);
        self::dbAccess()->delete('t_student_subject_training_assessment', $WHERE);
        self::dbAccess()->delete('t_student_score_date', $WHERE);

        return Array(
            "success" => true
        );
    }

    public function getStudentSubjectAssessmentTraining($studentId, $subjectId, $actionType = false)
    {

        $SELECTION_A = Array('SUBJECT_VALUE', 'RANK', 'TEACHER_COMMENT');
        $SELECTION_B = Array('DESCRIPTION', 'LETTER_GRADE');

        $SQL = self::dbAccess()->select();
        $SQL->from(array('A' => "t_student_subject_training_assessment"), $SELECTION_A);
        $SQL->joinLeft(array('B' => 't_gradingsystem'), 'A.ASSESSMENT_ID=B.ID', $SELECTION_B);
        $SQL->where("A.STUDENT_ID = ?", $studentId);
        $SQL->where("A.SUBJECT_ID = '" . $subjectId . "'");
        $SQL->where("A.TRAINING_ID = '" . $this->trainingId . "'");

        if (!$actionType)
        {
            $SQL->where("A.ACTION_TYPE = 'ASSESSMENT'");
        }
        else
        {
            $SQL->where("A.ACTION_TYPE = '" . $actionType . "'");
        }

        //error_log($SQL->__toString());
        return self::dbAccess()->fetchRow($SQL);
    }

    public function jsonActionStudentSubjectAssessmentTraining($encrypParams, $noJson = false)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);
        $studentId = isset($params["id"]) ? addText($params["id"]) : "";
        $trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $subjectId = isset($params["subjectId"]) ? (int) $params["subjectId"] : "";
        $field = isset($params["field"]) ? addText($params["field"]) : "";

        $stdClass = (object) array(
                    "studentId" => $studentId
                    , "trainingId" => $trainingId
                    , "subjectId" => $subjectId
        );

        switch ($field)
        {
            case "ASSESSMENT":
                $stdClass->subjectAssessment = isset($params["comboValue"]) ? addText($params["comboValue"]) : "";
                break;
        }

        $stdClass->subjectValue = isset($params["avg"]) ? addText($params["avg"]) : "";
        $stdClass->subjectRank = isset($params["rank"]) ? addText($params["rank"]) : "";

        self::setStudentTrainingSubjectAssessment($stdClass);

        return Array(
            "success" => true, "data" => array()
        );
    }

    public function jsonStudentTrainingSubjectAssignment($params, $isJson = true)
    {

        $params = Utiles::setPostDecrypteParams($params);

        $start = isset($params["start"]) ? (int) $params["start"] : 0;
        $limit = isset($params["limit"]) ? (int) $params["limit"] : 100;
        $globalSearch = isset($params["query"]) ? addText($params["query"]) : "";

        $this->assignmentId = isset($params["assignmentId"]) ? addText($params["assignmentId"]) : "";
        $this->date = isset($params["date"]) ? addText($params["date"]) : "";
        $this->trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $this->subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";

        $this->trainingObject = $this->getTrainingObject();
        $this->trainingSubject = $this->getTrainingSubject();

        $this->assignmentObject = self::getTrainingSubjectAssignment(
                        $this->trainingObject->PARENT
                        , $this->subjectId
                        , $this->assignmentId);

        $this->scoreType = $this->trainingSubject ? $this->trainingSubject->SCORE_TYPE : "";

        switch ($this->trainingObject->EVALUATION_TYPE)
        {
            case 1:
                $this->scoreMaxe = $this->assignmentObject->MAX_POSSIBLE_SCORE;
                break;
            default:
                $this->scoreMaxe = $this->trainingSubject->SCORE_MAX;
                break;
        }

        $data = Array();

        if ($this->listStudentsByTraining())
        {

            $data = $this->listStudentsData();

            $i = 0;
            foreach ($this->listStudentsByTraining() as $value)
            {

                $this->studentId = $value->STUDENT_ID;

                $STUDENT_SCORE = $this->getStudentTrainingScoreAssignment(
                        $value->STUDENT_ID
                        , $this->trainingId
                        , $this->subjectId
                        , $this->assignmentId
                        , $this->date);

                $data[$i]["POINTS_POSSIBLE"] = $this->scoreMaxe ? $this->scoreMaxe : "---";

                if ($this->scoreType == 1)
                {
                    if ($STUDENT_SCORE)
                    {
                        if ($STUDENT_SCORE->SCORE == 0)
                        {
                            $data[$i]["SCORE"] = 0;
                        }
                        else
                        {
                            $data[$i]["SCORE"] = $STUDENT_SCORE->SCORE;
                        }
                        $data[$i]["TEACHER_COMMENTS"] = setShowText($STUDENT_SCORE->TEACHER_COMMENTS);
                    }
                    else
                    {
                        $data[$i]["SCORE"] = '---';
                        $data[$i]["TEACHER_COMMENTS"] = "---";
                    }
                }
                else
                {
                    if ($STUDENT_SCORE)
                    {
                        $data[$i]["SCORE"] = $STUDENT_SCORE->SCORE;
                        $data[$i]["TEACHER_COMMENTS"] = setShowText($STUDENT_SCORE->TEACHER_COMMENTS);
                    }
                    else
                    {
                        $data[$i]["SCORE"] = '---';
                        $data[$i]["TEACHER_COMMENTS"] = "---";
                    }
                }

                $i++;
            }
        }

        $a = Array();
        for ($i = $start; $i < $start + $limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        if ($isJson)
        {
            return Array("success" => true, "totalCount" => sizeof($data), "rows" => $a);
        }
        else
        {
            return $data;
        }
    }

    public function getListStudentsSubjectResultTraining()
    {

        ini_set('memory_limit', '50M');

        $data = Array();
        $listAssignments = self::getTrainingListAssignmentScoreDate($this->trainingId, $this->subjectId, true);
        $listAssignmentsScoreDate = self::getTrainingListAssignmentScoreDate($this->trainingId, $this->subjectId, false);

        $scoreList = $this->scoreListSubjectByTraining(
                $this->subjectId
                , $this->trainingObject
                , $listAssignments);

        if ($this->listStudentsByTraining())
        {
            $i = 0;

            $data = $this->listStudentsData();

            foreach ($this->listStudentsByTraining() as $value)
            {

                $this->studentId = $value->STUDENT_ID;

                $AVERAGE = $this->getStudentTrainingSubjectAverage(
                        $value->STUDENT_ID
                        , $this->trainingSubject
                        , $this->trainingObject
                        , $listAssignments
                );
                $data[$i]["AVG"] = $AVERAGE;
                $data[$i]["RANK"] = AssessmentConfig::findRank($scoreList, $AVERAGE);

                $STUDENT_ASSESSMENT = self::getStudentTrainingSubjectAssessment(
                                $value->STUDENT_ID
                                , $this->subjectId
                                , $this->trainingId);
                $data[$i]["ASSESSMENT"] = self::displayAssessment($STUDENT_ASSESSMENT);

                if ($listAssignmentsScoreDate)
                {
                    foreach ($listAssignmentsScoreDate as $v)
                    {
                        $STUDENT_SCORE = $this->getStudentTrainingScoreAssignment(
                                $value->STUDENT_ID
                                , $this->trainingId
                                , $this->subjectId
                                , $v->ID
                                , $v->SCORE_INPUT_DATE);

                        switch ($this->trainingObject->EVALUATION_TYPE)
                        {
                            case 1:
                                $data[$i]["A_" . $v->OBJECT_ID . ""] = $STUDENT_SCORE ? $STUDENT_SCORE->SCORE . "/" . $STUDENT_SCORE->MAX_POSSIBLE_SCORE : "---";
                                break;
                            default:
                                $data[$i]["A_" . $v->OBJECT_ID . ""] = $STUDENT_SCORE ? $STUDENT_SCORE->SCORE : "---";
                                break;
                        }
                    }
                }

                $i++;
            }
        }


        $a = Array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        return Array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
    }

    public function getSQLStudentAssignmentTraining($studentId, $subjectId, $assignmentId)
    {

        $SELECTION_A = Array(
            "SCORE"
            , "TEACHER_COMMENTS"
        );

        $SELECTION_B = Array(
            "COEFF_VALUE"
            , "NAME AS ASSIGNMENT_NAME"
            , "EVALUATION_TYPE"
        );
        $SELECTION_C = Array(
            "INCLUDE_IN_EVALUATION"
        );
        $SQL = self::dbAccess()->select();
        $SQL->distinct();
        $SQL->from(Array('A' => "t_student_training_assignment"), $SELECTION_A);
        $SQL->joinLeft(Array('B' => 't_assignment_temp'), 'A.ASSIGNMENT=B.ID', $SELECTION_B);
        $SQL->joinLeft(Array('C' => 't_training_subject'), 'B.ID=C.ASSIGNMENT', $SELECTION_C);

        if ($assignmentId)
        {
            $SQL->where("A.ASSIGNMENT = '" . $assignmentId . "'");
        }

        if ($subjectId)
        {
            $SQL->where("A.SUBJECT= '" . $subjectId . "'");
        }

        if ($studentId)
        {
            $SQL->where("A.STUDENT = '" . $studentId . "'");
        }
        if ($this->trainingId)
        {
            $SQL->where("A.TRAINING = '" . $this->trainingId . "'");
        }

        //error_log($SQL->__toString());
        return self::dbAccess()->fetchAll($SQL);
    }

    public function jsonSubjectResultTraining($encrypParams)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);

        $this->start = isset($params["start"]) ? (int) $params["start"] : 0;
        $this->limit = isset($params["limit"]) ? (int) $params["limit"] : 100;
        $this->trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $this->subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";

        $this->trainingObject = $this->getTrainingObject();
        $this->trainingSubjectObject = $this->getTrainingSubject();
        $this->trainingSubject = $this->trainingSubjectObject->SUBJECT;
        $this->scoreType = $this->trainingSubjectObject ? $this->trainingSubjectObject->SCORE_TYPE : "";

        return $this->getListStudentsSubjectResultTraining();
    }

    public function jsonListStudentsClassPerformanceTraining($encrypParams, $isJson = true)
    {

        $params = Utiles::setPostDecrypteParams($encrypParams);

        $this->section = isset($params["section"]) ? addText($params["section"]) : "";
        $this->start = isset($params["start"]) ? (int) $params["start"] : 0;
        $this->limit = isset($params["limit"]) ? (int) $params["limit"] : 100;
        $this->isJson = $isJson;
        $this->subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";
        $this->trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $this->trainingObject = $this->getTrainingObject();
        $this->trainingSubject = $this->getTrainingSubject();

        return $this->resultClassPerformanceTraining();
    }

    public function resultClassPerformanceTraining()
    {

        $data = array();

        $scoreList = $this->scoreListClassPerformanceTraining();

        if ($this->listStudentsByTraining())
        {

            $data = $this->listStudentsData();

            $i = 0;
            foreach ($this->listStudentsByTraining() as $value)
            {
                $this->studentId = $value->STUDENT_ID;
                $AVERAGE_TOTAL = $this->getAvgClassPerformanceTraining(
                        $value->STUDENT_ID
                );

                $data[$i]["AVERAGE"] = $AVERAGE_TOTAL;
                $data[$i]["RANK"] = AssessmentConfig::findRank($scoreList, $AVERAGE_TOTAL);

                if ($this->listSubjectsTraining())
                {
                    foreach ($this->listSubjectsTraining() as $subjectObject)
                    {
                        $SUBJECT_ASSESSMENT = $this->getStudentSubjectAssessmentTraining(
                                $value->STUDENT_ID
                                , $subjectObject->SUBJECT_ID
                                , false);

                        $data[$i][$subjectObject->SUBJECT_ID] = $SUBJECT_ASSESSMENT ? $SUBJECT_ASSESSMENT->SUBJECT_VALUE : "---";
                    }
                }
                $i++;
            }
        }

        $a = array();
        for ($i = $this->start; $i < $this->start + $this->limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }
        if ($this->isJson)
        {
            return array(
                "success" => true
                , "totalCount" => sizeof($data)
                , "rows" => $a
            );
        }
        else
        {
            return $data;
        }
    }

    protected function getAvgClassPerformanceTraining($studentId)
    {

        $entries = $this->getSQLClassPerformanceTraining($studentId);

        $sumAvg = "";
        $sumCoeff = "";
        $result = "---";

        if ($entries)
        {
            foreach ($entries as $value)
            {
                if ($value->SCORE_TYPE == 1)
                {
                    if (is_numeric($value->SUBJECT_VALUE))
                    {
                        if ($value->COEFF_VALUE)
                        {
                            $sumAvg += $value->SUBJECT_VALUE * $value->COEFF_VALUE;
                            $sumCoeff += $value->COEFF_VALUE;
                        }
                    }
                }
            }
        }
        if (is_numeric($sumAvg) && is_numeric($sumCoeff))
        {
            $result = displayRound($sumAvg / $sumCoeff);
        }

        return $result;
    }

    protected function scoreListClassPerformanceTraining()
    {

        $data = Array();
        $entries = $this->listStudentsByTraining();
        if ($entries)
        {
            foreach ($entries as $value)
            {
                $data[] = $this->getAvgClassPerformanceTraining($value->STUDENT_ID, false);
            }
        }
        return $data;
    }

    protected function getSQLClassPerformanceTraining($studentId)
    {
        $SELECTION_A = Array(
            'SUBJECT_VALUE'
            , 'SUBJECT_ID'
            , 'RANK'
        );

        $SELECTION_B = Array(
            'INCLUDE_IN_EVALUATION'
            , 'SCORE_TYPE'
            , 'COEFF_VALUE'
        );

        $SQL = self::dbAccess()->select();
        $SQL->distinct();
        $SQL->from(Array('A' => "t_student_subject_training_assessment"), $SELECTION_A);
        $SQL->joinLeft(Array('B' => 't_training_subject'), 'A.SUBJECT_ID=B.SUBJECT', $SELECTION_B);

        if ($studentId)
        {
            $SQL->where("A.STUDENT_ID = ?", $studentId);
        }

        if ($this->trainingId)
        {
            $SQL->where("A.TRAINING_ID = '" . $this->trainingId . "'");
        }

        $SQL->group("A.SUBJECT_ID");
        // error_log($SQL);
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchAll($SQL);
    }

    public function checkStudentAssignmentTraining($studentId, $subjectId, $setInclude)
    {

        $SQL = self::dbAccess()->select();
        $SQL->distinct();
        $SQL->from(Array('A' => "t_student_training_assignment"), Array("COUNT(*) AS C"));
        $SQL->joinLeft(Array('B' => 't_assignment_temp'), 'A.ASSIGNMENT=B.ID', Array());

        if ($subjectId)
        {
            $SQL->where("A.SUBJECT = ?", $subjectId);
        }

        if ($studentId)
        {
            $SQL->where("A.STUDENT = '" . $studentId . "'");
        }
        if ($this->trainingId)
        {
            $SQL->where("A.TRAINING = '" . $this->trainingId . "'");
        }

        if ($setInclude)
        {
            $SQL->where("B.INCLUDE_IN_EVALUATION IN (0,2)");
        }
        $SQL->group("A.STUDENT");
        $result = self::dbAccess()->fetchRow($SQL);
        //error_log($SQL->__toString());
        return $result ? $result->C : 0;
    }

    public function getAVGTrainingSubjectAssignment($studentId, $trainingId, $subjectId, $assignmentId)
    {

        $SQL = self::dbAccess()->select();
        $SQL->distinct();
        $SQL->from(Array('A' => "t_student_training_assignment"), Array('AVG(SCORE) AS AVG_SCORE'));
        $SQL->joinLeft(Array('B' => 't_training_subject'), 'A.ASSIGNMENT=B.ASSIGNMENT', Array('AVG(MAX_POSSIBLE_SCORE) AS AVG_MAX_POSSIBLE_SCORE'));

        if ($assignmentId)
        {
            $SQL->where("A.ASSIGNMENT = '" . $assignmentId . "'");
        }

        $SQL->where("A.SUBJECT= '" . $subjectId . "'");
        $SQL->where("A.STUDENT = '" . $studentId . "'");
        $SQL->where("A.TRAINING = '" . $trainingId . "'");

        //error_log($SQL->__toString());
        return self::dbAccess()->fetchRow($SQL);
    }

    protected function getStudentTrainingSubjectAverage($studentId, $subjectId, $trainingObject, $listAssignments)
    {
        $result = "";
        $CHECK = $this->checkStudentAssignmentTraining($studentId, $subjectId, false);
        if ($CHECK)
        {
            if ($listAssignments)
            {
                foreach ($listAssignments as $value)
                {
                    $COEFF_VALUE = $value->COEFF_VALUE ? $value->COEFF_VALUE : 1;
                    $avgObject = $this->getAVGTrainingSubjectAssignment(
                            $studentId
                            , $trainingObject->ID
                            , $subjectId
                            , $value->ID);
                    if ($avgObject)
                    {
                        switch ($trainingObject->EVALUATION_TYPE)
                        {
                            case 1:
                                $result += ($avgObject->AVG_SCORE / $avgObject->AVG_MAX_POSSIBLE_SCORE) * 100 * $COEFF_VALUE / 100;
                                break;
                            default:
                                $SUM_COEFF_VALUE += $COEFF_VALUE;
                                $SUM_AVG += $avgObject->AVG_SCORE * $COEFF_VALUE;
                                break;
                        }
                    }
                }

                if (!$trainingObject->EVALUATION_TYPE)
                {
                    $result = $SUM_AVG / $SUM_COEFF_VALUE;
                }

                return displayRound($result);
            }
        }
        else
        {
            return "---";
        }
    }

    public static function jsonStudentTrainingAssessment($params)
    {
        //
    }

    public function actionTrainingStudentAssignment($params)
    {

        $params = Utiles::setPostDecrypteParams($params);

        $this->scoreInput = isset($params["newValue"]) ? addText($params["newValue"]) : "";
        $this->studentId = isset($params["id"]) ? addText($params["id"]) : "";
        $this->trainingId = isset($params["trainingId"]) ? (int) $params["trainingId"] : "";
        $this->assignmentId = isset($params["assignmentId"]) ? addText($params["assignmentId"]) : "";

        $this->subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";
        $this->date = isset($params["date"]) ? addText($params["date"]) : "";

        $this->trainingObject = $this->getTrainingObject();
        $this->assignmentObject = self::getTrainingSubjectAssignment(
                        $this->trainingObject->PARENT
                        , $this->subjectId
                        , $this->assignmentId
        );

        $this->trainingSubject = $this->getTrainingSubject();
        $this->maxScore = $this->trainingSubject ? $this->trainingSubject->SCORE_MAX : "";
        $this->scoreType = $this->trainingSubject ? $this->trainingSubject->SCORE_TYPE : "";
        $this->teacherId = Zend_Registry::get('USER')->ID;

        if ($this->date)
        {
            $explode = explode('-', $this->date);
        }

        $ERROR = 0;
        $SCHORE_DATE = 0;

        if ($this->assignmentObject)
        {

            if ($this->scoreType == 1)
            {

                if ($this->scoreInput <= $this->maxScore)
                {

                    $ERROR = 0;
                }
                else
                {

                    $ERROR = 1;
                }
            }
            else
            {
                $ERROR = 0;
            }
        }
        else
        {
            $ERROR = 1;
        }

        if (!$ERROR)
        {
            $this->setStudentScoreSubjectAssignment();
            $SCHORE_DATE = $this->getCountScoreInputDate();
        }
        else
        {
            $this->setStudentScoreSubjectAssignment();
            $SCHORE_DATE = $this->getCountScoreInputDate();
        }

        return Array(
            "success" => true
            , "ERROR" => $ERROR
            , "SCHORE_DATE" => $SCHORE_DATE
        );
    }

    protected function setStudentScoreSubjectAssignment()
    {

        $SAVEDATA = Array();

        $SAVEDATA["SCORE"] = $this->scoreInput;

        if ($this->checkStudentScoreSubjectAssignment())
        {
            $WHERE[] = "ASSIGNMENT = '" . $this->assignmentId . "'";
            $WHERE[] = "SUBJECT= '" . $this->subjectId . "'";
            $WHERE[] = "STUDENT = '" . $this->studentId . "'";
            $WHERE[] = "TRAINING= '" . $this->trainingId . "'";
            $WHERE[] = "SCORE_DATE = '" . $this->date . "'";
            self::dbAccess()->update('t_student_training_assignment', $SAVEDATA, $WHERE);
        }
        else
        {
            $SAVEDATA["COEFF_VALUE"] = $this->assignmentObject->COEFF_VALUE;
            $SAVEDATA["EVALUATION_TYPE"] = $this->assignmentObject->EVALUATION_TYPE;
            $SAVEDATA["ASSIGNMENT"] = $this->assignmentId;
            $SAVEDATA["STUDENT"] = $this->studentId;
            $SAVEDATA["SUBJECT"] = $this->subjectId;
            $SAVEDATA["TRAINING"] = $this->trainingId;
            $SAVEDATA["SCORE_DATE"] = $this->date;
            $SAVEDATA["SCORE_TYPE"] = $this->scoreType;
            $SAVEDATA["TEACHER"] = $this->teacherId;
            self::dbAccess()->insert("t_student_training_assignment", $SAVEDATA);
            $this->addScoreDate();
        }
    }

    protected function checkStudentScoreSubjectAssignment()
    {

        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_training_assignment", Array("C" => "COUNT(*)"));
        $SQL->where("ASSIGNMENT = '" . $this->assignmentId . "'");
        $SQL->where("SUBJECT = '" . $this->subjectId . "'");
        $SQL->where("TRAINING= '" . $this->trainingId . "'");
        $SQL->where("STUDENT= '" . $this->studentId . "'");
        $SQL->where("SCORE_DATE = '" . $this->date . "'");
        //error_log($SQL->__toString());
        $result = self::dbAccess()->fetchRow($SQL);
        return $result ? $result->C : 0;
    }

    protected function getCountScoreInputDate()
    {

        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_score_date", Array("C" => "COUNT(*)"));
        $SQL->where("ASSIGNMENT_ID = '" . $this->assignmentId . "'");
        $SQL->where("SUBJECT_ID= '" . $this->subjectId . "'");
        $SQL->where("TRAINING_ID = '" . $this->trainingId . "'");
        $SQL->where("SCORE_INPUT_DATE = '" . $this->date . "'");
        //error_log($SQL->__toString());
        $result = self::dbAccess()->fetchRow($SQL);

        return $result ? $result->C : 0;
    }

    protected function addScoreDate()
    {

        if (!$this->getCountScoreInputDate())
        {
            $SAVEDATA = Array();
            $SAVEDATA["ASSIGNMENT_ID"] = $this->assignmentId;
            $SAVEDATA["SUBJECT_ID"] = $this->subjectId;
            $SAVEDATA["TRAINING_ID"] = $this->trainingId;
            $SAVEDATA["SCORE_INPUT_DATE"] = $this->date;
            self::dbAccess()->insert("t_student_score_date", $SAVEDATA);
        }
    }

    public static function loadScoreStudentTraining($studentId, $training, $subjectId, $asssignmentId)
    {

        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_training_assignment");
        $SQL->where("STUDENT = ?", $studentId);
        $SQL->where("TRAINING = '" . $training . "'");
        $SQL->where("SUBJECT = ?", $subjectId);
        $SQL->where("ASSIGNMENT = '" . $asssignmentId . "'");
        //error_log($SQL->__toString());
        $stmt = self::dbAccess()->query($SQL);
        $result = $stmt->fetch();
        return $result ? $result->SCORE : "---";
    }

    public static function jsonAssessemntByTrainingSubjects($params)
    {
        //
    }

    public static function getTrainingListAssignmentScoreDate($trainingId, $subjectId, $setGroup = false)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from(Array('A' => 't_assignment_temp'), array("ID", "SHORT"));
        $SQL->joinLeft(Array('B' => 't_training_subject'), 'A.ID=B.ASSIGNMENT', array("COEFF_VALUE"));
        $SQL->joinLeft(Array('C' => 't_student_score_date'), 'A.ID=C.ASSIGNMENT_ID', array("ID AS OBJECT_ID", "SCORE_INPUT_DATE"));
        $SQL->where("C.SUBJECT_ID = ?", $subjectId);
        $SQL->where("C.TRAINING_ID = ?", $trainingId);
        $SQL->order('A.SORTKEY ASC');
        if ($setGroup)
            $SQL->group("C.ASSIGNMENT_ID");
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchAll($SQL);
    }

    public static function getAssignmentCountScoreDate($assignmentId, $trainingId, $subjectId)
    {
        $SQL = UserAuth::dbAccess()->select();
        $SQL->from(Array('A' => 't_assignment_temp'), Array("C" => "COUNT(*)"));
        $SQL->joinLeft(Array('B' => 't_student_score_date'), 'A.ID=B.ASSIGNMENT_ID', array());
        $SQL->where("B.SUBJECT_ID = ?", $subjectId);
        $SQL->where("B.TRAINING_ID = ?", $trainingId);
        $SQL->where("B.ASSIGNMENT_ID = ?", $assignmentId);
        $result = UserAuth::dbAccess()->fetchRow($SQL);
        return $result ? $result->C : 0;
    }

    protected static function setStudentTrainingSubjectAssessment($stdClass)
    {
        $SAVEDATA = Array();

        if (isset($stdClass->subjectValue))
            $SAVEDATA["SUBJECT_VALUE"] = $stdClass->subjectValue;
        if (isset($stdClass->subjectRank))
            $SAVEDATA["RANK"] = $stdClass->subjectRank;
        if (isset($stdClass->subjectAssessment))
            $SAVEDATA["ASSESSMENT_ID"] = $stdClass->subjectAssessment;
        if (isset($stdClass->teacherComment))
            $SAVEDATA["TEACHER_COMMENT"] = $stdClass->teacherComment;

        $SAVEDATA["PUBLISHED_DATE"] = getCurrentDBDateTime();
        $SAVEDATA["PUBLISHED_BY"] = Zend_Registry::get('USER')->CODE;

        if (self::getStudentTrainingSubjectAssessment($stdClass->studentId, $stdClass->subjectId, $stdClass->trainingId))
        {
            $WHERE[] = "STUDENT_ID = '" . $stdClass->studentId . "'";
            $WHERE[] = "SUBJECT_ID = '" . $stdClass->subjectId . "'";
            $WHERE[] = "TRAINING_ID = '" . $stdClass->trainingId . "'";
            self::dbAccess()->update('t_student_subject_training_assessment', $SAVEDATA, $WHERE);
        }
        else
        {
            $SAVEDATA["STUDENT_ID"] = $stdClass->studentId;
            $SAVEDATA["SUBJECT_ID"] = $stdClass->subjectId;
            $SAVEDATA["TRAINING_ID"] = $stdClass->trainingId;
            self::dbAccess()->insert("t_student_subject_training_assessment", $SAVEDATA);
        }
    }

    protected static function getStudentTrainingSubjectAssessment($studentId, $subjectId, $trainingId)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_subject_training_assessment", Array("*"));
        $SQL->where("STUDENT_ID = '" . $studentId . "'");
        $SQL->where("SUBJECT_ID= '" . $subjectId . "'");
        $SQL->where("TRAINING_ID = '" . $trainingId . "'");
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchRow($SQL);
    }

    protected static function displayAssessment($object)
    {
        $result = "---";
        if ($object)
        {
            $result = AssessmentConfig::makeGrade($object->ASSESSMENT_ID, false);
        }

        return $result;
    }

}

?>