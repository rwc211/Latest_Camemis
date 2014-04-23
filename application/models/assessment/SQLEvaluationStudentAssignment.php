<?php

////////////////////////////////////////////////////////////////////////////////
// @Kaom Vibolrith Senior Software Developer
// Date: 18.04.2014
// Am Stollheen 18, 55120 Mainz, Germany
////////////////////////////////////////////////////////////////////////////////

class SQLEvaluationStudentAssignment {

    public static function dbAccess()
    {
        return Zend_Registry::get('DB_ACCESS');
    }

    public static function dbSelectAccess()
    {
        return false::dbAccess()->select();
    }

    public static function getScoreSubjectAssignment($studentId, $classId, $subjectId, $assignmentId, $date)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from("t_student_assignment", array("*"))
                ->where("CLASS_ID = '" . $classId . "'")
                ->where("SUBJECT_ID = '" . $subjectId . "'")
                ->where("STUDENT_ID = '" . $studentId . "'")
                ->where("ASSIGNMENT_ID = '" . $assignmentId . "'")
                ->where("SCORE_DATE = '" . $date . "'");
        //error_log($SQL->__toString());
        $result = self::dbAccess()->fetchRow($SQL);
        return $result ? $result->POINTS : "";
    }

    public static function getAverageSubjectAssignment($studentId, $classId, $subjectId, $assignmentId, $term, $month, $year, $include)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from(array('A' => 't_student_assignment'), array("AVG(POINTS) AS AVG"))
                ->joinInner(array('B' => 't_assignment'), 'B.ID=A.ASSIGNMENT_ID', array())
                ->where("A.CLASS_ID = '" . $classId . "'")
                ->where("A.SUBJECT_ID = '" . $subjectId . "'")
                ->where("A.STUDENT_ID = '" . $studentId . "'")
                ->where("A.ASSIGNMENT_ID = '" . $assignmentId . "'")
                ->where("A.SCORE_TYPE = '1'");

        if ($month)
            $SQL->where("A.MONTH = '" . $month . "'");

        if ($year)
            $SQL->where("A.YEAR = '" . $year . "'");

        if ($term)
            $SQL->where("A.TERM = '" . $term . "'");

        $SQL->where("B.INCLUDE_IN_EVALUATION = '" . $include . "'");

        //error_log($SQL->__toString());
        $result = self::dbAccess()->fetchRow($SQL);

        return $result ? $result->AVG : "";
    }

    public static function getListStudentAssignmentScoreDate($studentId, $classId, $subjectId, $term, $month, $year, $include)
    {
        $SELECTION_A = array("ASSIGNMENT_ID");

        $SELECTION_B = array(
            "COEFF_VALUE AS COEFF_VALUE"
            , "INCLUDE_IN_EVALUATION AS INCLUDE_IN_EVALUATION"
        );

        $SQL = self::dbAccess()->select();
        $SQL->distinct();
        $SQL->from(array('A' => "t_student_assignment"), $SELECTION_A)
                ->joinLeft(array('B' => 't_assignment'), 'A.ASSIGNMENT_ID=B.ID', $SELECTION_B)
                ->where("A.CLASS_ID = '" . $classId . "'")
                ->where("A.SUBJECT_ID = '" . $subjectId . "'");

        $SQL->where("A.STUDENT_ID = '" . $studentId . "'");

        if ($month)
            $SQL->where("A.MONTH = '" . $month . "'");

        if ($year)
            $SQL->where("A.YEAR = '" . $year . "'");

        if ($term)
            $SQL->where("A.TERM = '" . $term . "'");

        if ($include)
            $SQL->where("B.INCLUDE_IN_EVALUATION = '" . $include . "'");

        $SQL->group("A.ASSIGNMENT_ID");
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchAll($SQL);
    }

    public static function calculatedAverageSubjectResult($studentId, $classId, $subjectId, $term, $month, $year, $include)
    {
        $SUM_VALUE = "";
        $SUM_COEFF_VALUE = "";
        $output = "";

        $enties = self::getListStudentAssignmentScoreDate(
                        $studentId
                        , $classId
                        , $subjectId
                        , $term
                        , $month
                        , $year
                        , $include);

        if ($enties)
        {
            foreach ($enties as $value)
            {

                $_VALUE = self::getAverageSubjectAssignment(
                                $studentId
                                , $classId
                                , $subjectId
                                , $value->ASSIGNMENT_ID
                                , $term
                                , $month
                                , $year
                                , $include);

                $COEFF_VALUE = $value->COEFF_VALUE ? $value->COEFF_VALUE : 1;
                $VALUE = $_VALUE ? $_VALUE : 0;
                $SUM_VALUE += $VALUE * $COEFF_VALUE;
                $SUM_COEFF_VALUE += $COEFF_VALUE;
            }
        }

        if (is_numeric($SUM_COEFF_VALUE))
        {
            if ($SUM_COEFF_VALUE)
            {
                $output = displayRound($SUM_VALUE / $SUM_COEFF_VALUE);
            }
            else
            {
                $output = 0;
            }
        }
        else
        {
            $output = 0;
        }

        return $output;
    }

    public static function getImplodeQuerySubjectAssignment($studentId, $classId, $subjectId, $assignmentId, $term, $month, $year, $include)
    {
        $SQL = self::dbAccess()->select();
        $SQL->from(array('A' => 't_student_assignment'), array("*"));
        $SQL->joinInner(array('B' => 't_assignment'), 'B.ID=A.ASSIGNMENT_ID', array());
        $SQL->where("A.CLASS_ID = '" . $classId . "'");
        $SQL->where("A.SUBJECT_ID = '" . $subjectId . "'");
        $SQL->where("A.STUDENT_ID = '" . $studentId . "'");

        if ($assignmentId)
        {
            $SQL->where("A.ASSIGNMENT_ID = '" . $assignmentId . "'");
        }

        if ($month)
            $SQL->where("A.MONTH = '" . $month . "'");

        if ($year)
            $SQL->where("A.YEAR = '" . $year . "'");

        if ($term)
            $SQL->where("A.TERM = '" . $term . "'");

        $SQL->where("B.INCLUDE_IN_EVALUATION = '" . $include . "'");

        //error_log($SQL->__toString());
        $result = self::dbAccess()->fetchAll($SQL);

        $data = array();

        if ($result)
        {
            foreach ($result as $value)
            {
                $data[] = $value->POINTS;
            }
        }

        return $data ? implode("|", $data) : "---";
    }

}

?>