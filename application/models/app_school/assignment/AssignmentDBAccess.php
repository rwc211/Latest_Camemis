<?php

///////////////////////////////////////////////////////////
// @Kaom Vibolrith Senior Software Developer
// Date: 28.06.2009
// Am Stollheen 18, 55120 Mainz, Germany
///////////////////////////////////////////////////////////

require_once("Zend/Loader.php");
require_once 'utiles/Utiles.php';
require_once 'include/Common.inc.php';
require_once 'models/app_school/academic/AcademicDBAccess.php';
require_once setUserLoacalization();

class AssignmentDBAccess {

    private $dataforjson = null;
    public $savedata = Array();
    public $wheredata = Array();

    static function getInstance()
    {
        static $me;

        if ($me == null)
        {
            $me = new AssignmentDBAccess();
        }

        return $me;
    }

    public function __construct()
    {

        //
    }

    public static function dbAccess()
    {
        return Zend_Registry::get('DB_ACCESS');
    }

    public function getAssignmentDataFromId($Id)
    {

        $data = Array();
        $facette = self::findAssignmentFromId($Id);

        if ($facette)
        {

            $data["ID"] = $facette->ID;
            $data["NAME"] = setShowText($facette->NAME);
            $data["SUBJECT"] = $facette->SUBJECT;
            $data["DESCRIPTION"] = $facette->DESCRIPTION;
            $data["SMS_SEND"] = $facette->SMS_SEND;
            $data["TASK"] = $facette->TASK;
            $data["COEFF_VALUE"] = $facette->COEFF_VALUE;
            $data["POINTS_POSSIBLE"] = $facette->POINTS_POSSIBLE;
            $data["INCLUDE_IN_EVALUATION"] = $facette->INCLUDE_IN_EVALUATION;
            $data["EVALUATION_TYPE"] = $facette->EVALUATION_TYPE;
            $data["REMOVE_STATUS"] = $this->checkRemoveAssignment($facette->ID);
            $data["SMS_SEND"] = $facette->SMS_SEND;
            $data["STATUS"] = $facette->STATUS;
            $data["SHORT"] = setShowText($facette->SHORT);
            $data["SORTKEY"] = setShowText($facette->SORTKEY);
            $data["CREATED_DATE"] = getShowDateTime($facette->CREATED_DATE);
            $data["MODIFY_DATE"] = getShowDateTime($facette->MODIFY_DATE);
            $data["ENABLED_DATE"] = getShowDateTime($facette->ENABLED_DATE);
            $data["DISABLED_DATE"] = getShowDateTime($facette->DISABLED_DATE);
            $data["CREATED_BY"] = setShowText($facette->CREATED_BY);
            $data["MODIFY_BY"] = setShowText($facette->MODIFY_BY);
            $data["ENABLED_BY"] = setShowText($facette->ENABLED_BY);
            $data["DISABLED_BY"] = setShowText($facette->DISABLED_BY);
        }

        return $data;
    }

    /**
     * JSON: Student by StudentId....
     */
    public function loadAssignmentFromId($Id)
    {

        $result = self::findAssignmentFromId($Id);

        if ($result)
        {
            $o = array(
                "success" => true
                , "data" => $this->getAssignmentDataFromId($Id)
            );
        }
        else
        {
            $o = array(
                "success" => true
                , "data" => Array()
            );
        }
        return $o;
    }

    public static function findAssignmentFromId($Id)
    {

        $SQL = self::dbAccess()->select();
        $SQL->from('t_assignment', '*');
        $SQL->where("ID = ?", $Id);
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchRow($SQL);
    }

    public static function findAssignmentJoinCategory($Id)
    {

        $SQL = "SELECT         
            A.ID AS ID 
            ,A.STATUS AS STATUS
            ,A.SORTKEY AS SORTKEY
            ,A.NAME AS NAME
            ,A.SHORT AS SHORT
            ,A.TASK AS TASK
            ,A.INCLUDE_IN_EVALUATION AS INCLUDE_IN_EVALUATION
            ,A.COEFF_VALUE AS COEFF_VALUE
            ,A.DESCRIPTION AS DESCRIPTION
            ,A.EVALUATION_TYPE AS EVALUATION_TYPE";

        $SQL .= " FROM t_assignment AS A ";
        $SQL .= " WHERE";
        $SQL .= " A.ID = '" . $Id . "'";
        //error_log($SQL);
        return self::dbAccess()->fetchRow($SQL);
    }

    public function removeitem($params)
    {

        if (isset($params["removeId"]))
        {

            if (!$this->checkRemoveAssignment($params["removeId"]))
            {
                self::dbAccess()->delete('t_assignment', array("ID='" . $params["removeId"] . "'"));
            }
        }
    }

    public function actionAssignment($params)
    {

        if (substr($params["objectId"], 8))
        {
            $objectId = str_replace('CAMEMIS_', '', $params["objectId"]);
        }
        else
        {
            $objectId = $params["objectId"];
        }

        $facette = self::findAssignmentFromId($objectId);

        if (isset($params["NAME"]))
            $SAVEDATA['NAME'] = addText($params["NAME"]);

        if (isset($params["SORTKEY"]))
            $SAVEDATA['SORTKEY'] = addText($params["SORTKEY"]);

        if (isset($params["DESCRIPTION"]))
            $SAVEDATA['DESCRIPTION'] = addText($params["DESCRIPTION"]);

        if (isset($params["subjectId"]))
            $SAVEDATA['SUBJECT'] = $params["subjectId"];

        if (isset($params["TASK"]))
            $SAVEDATA['TASK'] = $params["TASK"];

        if (isset($params["SHORT"]))
            $SAVEDATA['SHORT'] = addText($params["SHORT"]);

        if (isset($params["COEFF_VALUE"]))
            $SAVEDATA["COEFF_VALUE"] = addText($params["COEFF_VALUE"]);

        if (isset($params["POINTS_POSSIBLE"]))
            $SAVEDATA["POINTS_POSSIBLE"] = addText($params["POINTS_POSSIBLE"]);

        if (isset($params["EVALUATION_TYPE"]))
            $SAVEDATA["EVALUATION_TYPE"] = $params["EVALUATION_TYPE"];

        if (isset($params["INCLUDE_IN_EVALUATION"]))
            $SAVEDATA["INCLUDE_IN_EVALUATION"] = (int) $params["INCLUDE_IN_EVALUATION"];

        if (isset($params["SMS_SEND"]))
            $SAVEDATA['SMS_SEND'] = addText($params["SMS_SEND"]);

        if ($facette)
        {
            $SAVEDATA['MODIFY_DATE'] = getCurrentDBDateTime();
            $SAVEDATA['MODIFY_BY'] = Zend_Registry::get('USER')->CODE;
            $WHERE[] = "ID = '" . $facette->ID . "'";
            self::dbAccess()->update('t_assignment', $SAVEDATA, $WHERE);
            self::updateStudentAssignment($objectId);
        }
        else
        {

            if (Zend_Registry::get('SCHOOL')->ENABLE_ITEMS_BY_DEFAULT)
            {
                $SAVEDATA['STATUS'] = 1;
            }

            $SAVEDATA['CREATED_DATE'] = getCurrentDBDateTime();
            $SAVEDATA['CREATED_BY'] = Zend_Registry::get('USER')->CODE;

            self::dbAccess()->insert('t_assignment', $SAVEDATA);
            $objectId = self::dbAccess()->lastInsertId();
        }

        return array(
            "success" => true
            , "objectId" => $objectId
        );
    }

    public function searchAssignment($params)
    {

        $subjectId = $params["subjectId"] ? addText($params["subjectId"]) : "0";
        $classId = $params["classId"] ? (int) $params["classId"] : "0";

        $classObject = AcademicDBAccess::findGradeFromId($classId);

        $SQL = self::dbAccess()->select();
        $SQL->from('t_assignment', '*');
        if ($subjectId)
            $SQL->where("SUBJECT = ?", $subjectId);
        if ($classObject)
            $SQL->where("GRADE = '" . $classObject->GRADE_ID . "'");
        $SQL->where("STATUS = '1'");
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchAll($SQL);
    }

    public function jsonAssignmentsByGrade($params, $isJson = true)
    {

        $data = Array();
        $start = isset($params["start"]) ? (int) $params["start"] : "0";
        $limit = isset($params["limit"]) ? (int) $params["limit"] : "50";

        $assignmentId = isset($params["assignmentId"]) ? addText($params["assignmentId"]) : "0";
        $gradeId = isset($params["gradeId"]) ? (int) $params["gradeId"] : "0";
        $classId = isset($params["classId"]) ? (int) $params["classId"] : "0";
        $schoolyearId = isset($params["schoolyearId"]) ? addText($params["schoolyearId"]) : "0";
        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "0";
        $assessmentType = isset($params["assessmentType"]) ? $params["assessmentType"] : "";

        $SQL = "";
        $SQL .= " SELECT
            A.ID AS ID 
            ,A.STATUS AS STATUS
            ,A.SORTKEY AS SORTKEY
            ,A.NAME AS NAME
            ,A.INCLUDE_IN_EVALUATION AS INCLUDE_IN_EVALUATION
            ,A.EVALUATION_TYPE AS EVALUATION_TYPE
            ,A.COEFF_VALUE AS COEFF_VALUE
            ,A.DESCRIPTION AS DESCRIPTION
            ";
        $SQL .= " FROM t_assignment AS A";
        $SQL .= " WHERE 1=1";
        $SQL .= " AND A.GRADE='" . $gradeId . "'";
        $SQL .= " AND A.SCHOOLYEAR='" . $schoolyearId . "'";
        $SQL .= " AND A.SUBJECT='" . $subjectId . "'";
        if ($classId)
            $SQL .= " AND A.CLASS='" . $classId . "'";
        if ($assignmentId)
        {
            $SQL .= " AND A.ID='" . $assignmentId . "'";
        }

        if ($assessmentType == "ST")
        {
            $SQL .= " AND B.ASSESSMENT_TYPE = 'ST'";
        }

        $SQL .= " ORDER BY A.NAME";
        //error_log($SQL);
        $result = self::dbAccess()->fetchAll($SQL);

        $i = 0;
        if ($result)
            foreach ($result as $value)
            {

                $data[$i]["ID"] = $value->ID;
                $data[$i]["NAME"] = $value->NAME;
                $data[$i]["OBJECT_NAME"] = $value->NAME;
                $data[$i]["DESCRIPTION"] = setShowText($value->DESCRIPTION);
                $data[$i]["ASSESSMENT_TYPE"] = setShowText($value->ASSESSMENT_TYPE);
                $data[$i]["STATUS_KEY"] = iconStatus($value->STATUS);
                $data[$i]["STATUS"] = $value->STATUS;

                $i++;
            }

        $a = Array();
        for ($i = $start; $i < $start + $limit; $i++)
        {
            if (isset($data[$i]))
                $a[] = $data[$i];
        }

        $this->dataforjson = array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $a
        );
        if ($isJson)
            return $this->dataforjson;
        else
            return $data;
    }

    public function releaseObject($params)
    {

        $objectId = isset($params["objectId"]) ? addText($params["objectId"]) : 0;

        $facette = self::findAssignmentFromId($objectId);
        $status = $facette->STATUS;
        $data = array();
        switch ($status)
        {
            case 0:
                $newStatus = 1;
                $data['STATUS'] = 1;
                $data['ENABLED_DATE'] = "'" . getCurrentDBDateTime() . "'";
                $data['ENABLED_BY'] = "'" . Zend_Registry::get('USER')->CODE . "'";
                break;
            case 1:
                $newStatus = 0;
                $data['STATUS'] = 0;
                $data['DISABLED_DATE'] = "'" . getCurrentDBDateTime() . "'";
                $data['DISABLED_BY'] = "'" . Zend_Registry::get('USER')->CODE . "'";
                break;
        }
        self::dbAccess()->update("t_assignment", $data, "ID=" . $objectId);
        return array("success" => true, "status" => $newStatus);
    }

    public function checkStudentsByAssignment($Id)
    {

        $SQL = self::dbAccess()->select();
        $SQL->from('t_student_assignment', 'COUNT(*) AS C');
        $SQL->where("ASSIGNMENT_ID = ?", $Id);
        //error_log($SQL->__toString());
        $result = self::dbAccess()->fetchRow($SQL);
        return $result ? $result->C : 0;
    }

    public function removeObject($params)
    {

        $objectId = isset($params["objectId"]) ? addText($params["objectId"]) : "";
        if (!$this->checkRemoveAssignment($objectId))
        {
            self::dbAccess()->delete('t_assignment', array("ID='" . $objectId . "'"));
            self::dbAccess()->delete('t_student_assignment', array("ASSIGNMENT_ID='" . $objectId . "'"));
            self::dbAccess()->delete('t_student_score_date', array("ASSIGNMENT_ID='" . $objectId . "'"));
        }
        return array("success" => true);
    }

    public function checkRemoveAssignment($Id)
    {

        $CHECK = $this->checkStudentsByAssignment($Id);

        if ($CHECK)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function getAllAssignmentQuery($params)
    {

        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";
        $academicId = isset($params["academicId"]) ? addText($params["academicId"]) : "";
        $gradeId = isset($params["gradeId"]) ? (int) $params["gradeId"] : "";
        $schoolyearId = isset($params["schoolyearId"]) ? addText($params["schoolyearId"]) : "";
        $academicObject = AcademicDBAccess::findGradeFromId($academicId);
        $subjectObject = SubjectDBAccess::findSubjectFromId($subjectId);

        if ($academicObject)
        {

            if ($academicObject->EDUCATION_SYSTEM)
            {
                $classId = "";
                $schoolyearId = $academicObject->SCHOOL_YEAR;
                $USED_IN_CLASS = 0;
            }
            else
            {
                switch ($academicObject->OBJECT_TYPE)
                {
                    case "CLASS":
                        $classId = $academicObject->ID;
                        $gradeId = $academicObject->GRADE_ID;
                        $schoolyearId = $academicObject->SCHOOL_YEAR;
                        $USED_IN_CLASS = 1;
                        break;
                    case "SCHOOLYEAR":
                        $classId = "";
                        $gradeId = $academicObject->GRADE_ID;
                        $schoolyearId = $academicObject->SCHOOL_YEAR;
                        $USED_IN_CLASS = 0;
                        break;
                }
            }
        }

        if ($subjectObject)
        {
            $subjectId = $subjectObject->ID;
        }

        $SELECTION_A = array(
            "SORTKEY"
            , "ID AS ASSIGNMENT_ID"
            , "NAME"
            , "SHORT"
            , "GRADE"
            , "SUBJECT"
            , "CLASS_IDS"
            , "INCLUDE_IN_EVALUATION"
            , "COEFF_VALUE"
            , "SCHOOLYEAR"
            , "EVALUATION_TYPE"
            , "TASK"
            , "SMS_SEND"
            , "TASK"
        );
        $SELECTION_B = array(
            "ID AS SUBJECT_ID"
            , "NAME AS SUBJECT_NAME"
        );

        $SQL = self::dbAccess()->select();
        $SQL->from(array('A' => 't_assignment'), $SELECTION_A);
        $SQL->joinLeft(array('B' => 't_subject'), 'A.SUBJECT=B.ID', $SELECTION_B);
        $SQL->joinLeft(array('D' => 't_grade'), 'A.GRADE=D.ID', Array());

        if ($classId)
            $SQL->where("A.CLASS = ?", $classId);
        if ($subjectId)
            $SQL->where("A.SUBJECT = ?", $subjectId);
        if ($gradeId)
            $SQL->where("A.GRADE = ?", $gradeId);

        if ($academicObject)
        {

            if ($academicObject->EDUCATION_SYSTEM)
            {
                $SQL->where("A.EDUCATION_SYSTEM = 1");
            }
            else
            {
                $SQL->where("A.EDUCATION_SYSTEM = 0");
            }
        }

        if ($schoolyearId)
            $SQL->where("A.SCHOOLYEAR = ?", $schoolyearId);

        $SQL->where("A.USED_IN_CLASS = '" . $USED_IN_CLASS . "'");
        $SQL->where("A.STATUS = '1'");
        $SQL->group("A.ID");
        $SQL->order('A.SORTKEY DESC');

        //error_log($SQL);
        return self::dbAccess()->fetchAll($SQL);
    }

    public function treeAssignmentBySubject($params)
    {

        $result = self::getAllAssignmentQuery($params);

        $data = Array();

        $i = 0;

        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";

        if ($subjectId)
            if ($result)
            {
                foreach ($result as $value)
                {

                    $data[$i]['id'] = $value->ID;
                    $data[$i]['leaf'] = true;
                    $data[$i]['iconCls'] = "icon-flag_blue";
                    $i++;
                }
            }

        return $data;
    }

    public function jsonClassInAssignment($params)
    {

        $data = Array();
        $start = $params["start"] ? (int) $params["start"] : "0";
        $limit = $params["limit"] ? (int) $params["limit"] : "50";

        $objectId = $params["objectId"] ? addText($params["objectId"]) : "0";

        $facette = self::findAssignmentFromId($objectId);

        $DB_ACADEMIC = AcademicDBAccess::getInstance();
        $RESULT = $DB_ACADEMIC->searchClass($params);

        $CLASS_IDS = explode(",", $facette->CLASS_IDS);

        $i = 0;

        if ($RESULT)
        {
            foreach ($RESULT as $value)
            {

                $data[$i]["ID"] = $value->ID;

                if (in_array($value->ID, $CLASS_IDS))
                {
                    $data[$i]["IN_CLASS"] = YES;
                }
                else
                {
                    $data[$i]["IN_CLASS"] = NO;
                }

                $data[$i]["NAME"] = setShowText($value->NAME);

                $i++;
            }
        }

        $a = Array();
        for ($i = $start; $i < $start + $limit; $i++)
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

    public function jsonSaveClassInAssignment($params)
    {

        $SAVEDATA = Array();
        $WHERE = Array();
        $assignmentId = $params["assignmentId"] ? addText($params["assignmentId"]) : "0";
        $classIds = $params["classIds"] ? addText($params["classIds"]) : "0";

        $SAVEDATA['CLASS_IDS'] = $classIds;
        $WHERE[] = "ID = '" . $assignmentId . "'";
        self::dbAccess()->update('t_assignment', $SAVEDATA, $WHERE);

        return array(
            "success" => true
        );
    }

    public static function findCountMinAssignments($studentId, $subjectId, $classId, $term, $min)
    {

        $SQL = "
            SELECT COUNT(*) AS C  
            FROM t_assignment AS A
            LEFT JOIN t_student_assignment AS B ON B.ASSIGNMENT_ID = A.ID
            WHERE 1=1
            AND B.STUDENT_ID= '" . $studentId . "'
            AND B.CLASS_ID = '" . $classId . "' 
            AND B.SUBJECT_ID = '" . $subjectId . "'
            AND B.TERM = '" . $term . "'
            AND A.STATUS = 1
            AND B.POINTS IN ($min);
            ";
        //error_log($SQL);
        $result = self::dbAccess()->fetchRow($SQL);

        return $result ? $result->C : 0;
    }

    public static function deleteStudentFromAssignment($studentId, $assignmentId, $subjectId, $classId)
    {

        $condition = array(
            'STUDENT_ID = ? ' => $studentId
            , 'CLASS_ID = ? ' => $classId
            , 'SUBJECT_ID = ? ' => $subjectId
            , 'ASSIGNMENT_ID = ? ' => $assignmentId
        );
        self::dbAccess()->delete('t_student_assignment', $condition);
    }

    public function jsonTreeAssignmentsBySubjctClass($encrypParams)
    {

        $data = Array();

        $params = Utiles::setPostDecrypteParams($encrypParams);

        $node = isset($params["node"]) ? addText($params["node"]) : 0;
        $subjectId = isset($params["subjectId"]) ? addText($params["subjectId"]) : "";
        $academicId = isset($params["academicId"]) ? addText($params["academicId"]) : "";

        $academicObject = AcademicDBAccess::findGradeFromId($academicId);
        $subjectObject = SubjectDBAccess::findSubjectFromId($subjectId);

        switch ($academicObject->EDUCATION_SYSTEM)
        {
            case 1:
                $newAcademicId = $academicObject->PARENT;
                $newSubjectId = $academicObject->SUBJECT_ID;
                break;
            default:
                $newAcademicId = $academicObject->ID;
                $newSubjectId = $subjectObject->ID;
                break;
        }

        $facette = self::findAssignmentFromId($node);

        if ($facette)
        {
            $entries = $this->getAllScoreDate($node, $academicId, $newSubjectId);
        }
        else
        {

            $searchParams["subjectId"] = $newSubjectId;
            $searchParams["academicId"] = $newAcademicId;
            $searchParams["gradeId"] = $academicObject->GRADE_ID;
            $searchParams["schoolyearId"] = $academicObject->SCHOOL_YEAR;
            $searchParams["term"] = $node;
            $entries = self::getAllAssignmentQuery($searchParams);
        }

        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {

                if (!$facette)
                {

                    $data[$i]['id'] = "" . $value->ASSIGNMENT_ID . "";
                    $data[$i]['text'] = "(" . $value->SHORT . ") " . $value->NAME . "";
                    $data[$i]['leaf'] = false;
                    $data[$i]['isClick'] = true;
                    switch ($value->INCLUDE_IN_EVALUATION)
                    {
                        case 1:
                            $data[$i]['iconCls'] = "icon-flag_blue";
                            break;
                        case 3:
                            $data[$i]['iconCls'] = "icon-flag_red";
                            break;
                        case 2:
                            $data[$i]['iconCls'] = "icon-flag_yellow";
                            break;
                    }
                }
                else
                {
                    $data[$i]['cls'] = "nodeTextBlue";
                    $facette = self::findAssignmentFromId($node);
                    $data[$i]['leaf'] = true;
                    $data[$i]['isClick'] = true;
                    $data[$i]['setId'] = $value->ID;
                    $data[$i]['id'] = "" . $value->ASSIGNMENT_ID . "_" . $value->SCORE_INPUT_DATE;
                    $data[$i]['display'] = "" . $facette->NAME;
                    $data[$i]['display'] .= ": " . getShowDate($value->SCORE_INPUT_DATE) . "";
                    $data[$i]['text'] = getShowDate($value->SCORE_INPUT_DATE) . "";
                    $data[$i]['iconCls'] = "icon-date_edit";
                }
                $i++;
            }
        }
        return $data;
    }

    public static function findAssignmentDateFromId($Id)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_score_date", array('*'));
        $SQL->where("ID = ?", $Id);
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchRow($SQL);
    }

    public function checkCountScoreDate($Id, $subjectId, $classId)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_score_date", array("C" => "COUNT(*)"));
        $SQL->where("ASSIGNMENT_ID = ?", $Id);
        $SQL->where("SUBJECT_ID = ?", $subjectId);
        $SQL->where("CLASS_ID = ?", $classId);
        $SQL->group('SCORE_INPUT_DATE');
        //error_log($SQL->__toString());
        $result = self::dbAccess()->fetchRow($SQL);
        return $result ? $result->C : 0;
    }

    public static function getAllScoreDate($assignmentId, $classId, $subjectId)
    {

        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_score_date", array('*'));
        if ($assignmentId)
            $SQL->where("ASSIGNMENT_ID = ?", $assignmentId);
        $SQL->where("SUBJECT_ID = ?", $subjectId);
        $SQL->where("CLASS_ID = ?", $classId);
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchAll($SQL);
    }

    public static function checkAssignmentInClass($subjectId, $classId)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from("t_assignment", array("C" => "COUNT(*)"));
        $SQL->where("SUBJECT = ?", $subjectId);
        $SQL->where("CLASS = ?", $classId);
        $result = self::dbAccess()->fetchRow($SQL);
        return $result ? $result->C : 0;
    }

    public static function mappingAcademicEvaluationType($type, $Id)
    {
        $WHERE = Array();
        $SAVEDATA['EVALUATION_TYPE'] = $type;
        $WHERE[] = "ID = '" . $Id . "'";
        self::dbAccess()->update('t_assignment', $SAVEDATA, $WHERE);
    }

    public static function getListAssignmentsToAcademic($classId, $subjectId)
    {

        $academicObject = AcademicDBAccess::findGradeFromId($classId);
        $subjectObject = SubjectDBAccess::findSubjectFromId($subjectId);

        if ($academicObject && $subjectObject)
        {
            $searchParams["gradeId"] = $academicObject->GRADE_ID;
            $searchParams["academicId"] = $academicObject->ID;
            $searchParams["subjectId"] = $subjectObject->ID;
            $searchParams["schoolyearId"] = $academicObject->SCHOOL_YEAR;
            return self::getAllAssignmentQuery($searchParams);
        }
    }

    public static function updateStudentAssignment($objectId)
    {

        $facette = self::findAssignmentFromId($objectId);

        $WHERE = Array();
        $SAVEDATA['EVALUATION_TYPE'] = $facette->EVALUATION_TYPE;
        $SAVEDATA['COEFF_VALUE'] = $facette->COEFF_VALUE;
        $SAVEDATA['POINTS_POSSIBLE'] = $facette->POINTS_POSSIBLE;
        $SAVEDATA['INCLUDE_IN_EVALUATION'] = $facette->INCLUDE_IN_EVALUATION;
        $WHERE[] = "ASSIGNMENT_ID = '" . $facette->ID . "'";
        self::dbAccess()->update('t_student_assignment', $SAVEDATA, $WHERE);
    }

}

?>