<?php

////////////////////////////////////////////////////////////////////////////////
// @Kaom Vibolrith Senior Software Developer
// Date: 18.04.2014
// Am Stollheen 18, 55120 Mainz, Germany
////////////////////////////////////////////////////////////////////////////////

require_once 'models/assessment/AssessmentProperties.php';
require_once 'models/assessment/SQLEvaluationStudentAssignment.php';
require_once 'models/assessment/SQLEvaluationStudentSubject.php';
require_once "models/" . Zend_Registry::get('MODUL_API_PATH') . "/SpecialDBAccess.php";

class EvaluationSubjectAssessment extends AssessmentProperties {

    CONST NO_MONTH = false;
    CONST NO_YEAR = false;
    CONST NO_TERM = false;
    CONST NO_ASSIGNMENT = false;
    CONST NO_SECTION = false;
    CONST NO_SCHOOLYEAR_ID = false;
    CONST SCORE_NUMBER = 1;
    CONST SCORE_CHAR = 2;
    CONST INCLUDE_IN_MONTH = 1;
    CONST INCLUDE_IN_TERM = 2;
    CONST SCORE_TYPE_NUMBER = 1;
    CONST SCORE_TYPE_CHAR = 2;

    /**
     * Evaluation type: (number, percent)
     */
    const EVALUATION_TYPE_COEFF = 0;
    const EVALUATION_TYPE_PERCENT = 1;

    /**
     * Formular for year result of subject
     */
    CONST AVG_S1 = 1;
    CONST AVG_S2 = 2;
    CONST AVG_T1 = 1;
    CONST AVG_T2 = 2;
    CONST AVG_T3 = 3;
    CONST AVG_Q1 = 1;
    CONST AVG_Q2 = 2;
    CONST AVG_Q3 = 3;
    CONST AVG_Q4 = 4;
    CONST WITH_FORMAT = true;
    CONST WITHOUT_FORMAT = false;

    function __construct() {
        parent::__construct();
    }

    protected function listStudentsData() {

        $data = array();

        if ($this->listClassStudents()) {
            $i = 0;
            foreach ($this->listClassStudents() as $value) {
                $studentId = $value->ID;

                $STATUS_DATA = StudentStatusDBAccess::getCurrentStudentStatus($studentId);
                $data[$i]["ID"] = $studentId;
                $data[$i]["STATUS_KEY"] = isset($STATUS_DATA["SHORT"]) ? $STATUS_DATA["SHORT"] : "";
                $data[$i]["BG_COLOR"] = isset($STATUS_DATA["COLOR"]) ? $STATUS_DATA["COLOR"] : "";
                $data[$i]["BG_COLOR_FONT"] = isset($STATUS_DATA["COLOR_FONT"]) ? $STATUS_DATA["COLOR_FONT"] : "";
                $data[$i]["CODE"] = $value->CODE;
                $data[$i]["GENDER"] = getGenderName($value->GENDER);
                $data[$i]["STUDENT"] = getFullName($value->FIRSTNAME, $value->LASTNAME);
                $data[$i]["STUDENT_SCHOOL_ID"] = $value->STUDENT_SCHOOL_ID;
                $data[$i]["FIRSTNAME_LATIN"] = $value->FIRSTNAME_LATIN;
                $data[$i]["LASTNAME_LATIN"] = $value->LASTNAME_LATIN;

                $i++;
            }
        }

        return $data;
    }

    public function getListStudentSubjectAssignments() {

        $data = array();

        $stdClass = (object) array(
                    "studentId" => $this->studentId
                    , "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "term" => $this->term
                    , "month" => $this->getMonth()
                    , "year" => $this->getYear()
                    , "section" => $this->getSection()
                    , "schoolyearId" => $this->getSchoolyearId()
        );

        $entries = SQLEvaluationStudentAssignment::getQueryStudentSubjectAssignments($stdClass);

        if ($entries) {
            $i = 0;
            foreach ($entries as $value) {
                $data[$i]["ID"] = $value->ID;
                $data[$i]["ASSIGNMENT"] = setShowText($value->ASSIGNMENT);
                $data[$i]["POINTS"] = $value->POINTS;
                $data[$i]["CREATED_DATE"] = getShowDateTime($value->CREATED_DATE);
                $data[$i]["TEACHER_COMMENTS"] = setShowText($value->TEACHER_COMMENTS);

                $i++;
            }
        }

        return $data;
    }

    ////////////////////////////////////////////////////////////////////////////
    // MONTH CLASS SUBJECT RESULT...
    ////////////////////////////////////////////////////////////////////////////
    public function getSubjectMonthResult() {

        $data = array();

        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "term" => $this->term
                    , "month" => $this->getMonth()
                    , "year" => $this->getYear()
                    , "section" => $this->getSection()
                    , "in_clude_in_evaluation" => self::INCLUDE_IN_MONTH
                    , "schoolyearId" => $this->getSchoolyearId()
                    , "evaluationType" => $this->getSettingEvaluationType()
        );

        if ($this->listClassStudents()) {

            $scoreList = $this->getScoreListSubjectMonthResult($stdClass);

            $data = $this->listStudentsData();

            $i = 0;
            foreach ($this->listClassStudents() as $value) {

                $stdClass->studentId = $value->ID;

                switch ($this->getSubjectScoreType()) {
                    case self::SCORE_NUMBER:
                        $AVERAGE = $this->averageMonthSubjectResult($stdClass, self::WITH_FORMAT);
                        $data[$i]["RANK"] = getScoreRank($scoreList, $AVERAGE);
                        $data[$i]["AVERAGE"] = $AVERAGE;
                        $data[$i]["ASSESSMENT"] = $this->getSubjectMonthAssessment($stdClass)->LETTER_GRADE_NUMBER;
                        $data[$i]["ASSESSMENT_ID"] = $this->getSubjectMonthAssessment($stdClass)->LETTER_GRADE_NUMBER;
                        break;
                    case self::SCORE_CHAR:
                        $data[$i]["ASSESSMENT"] = $this->getSubjectMonthAssessment($stdClass)->LETTER_GRADE_CHAR;
                        $data[$i]["ASSESSMENT_ID"] = $this->getSubjectMonthAssessment($stdClass)->ASSESSMENT_ID;
                        break;
                }

                if ($this->getCurrentClassAssignments()) {
                    foreach ($this->getCurrentClassAssignments() as $v) {
                        $stdClass->assignmentId = $v->ASSIGNMENT_ID;
                        $data[$i][$v->ASSIGNMENT_ID] = $this->getImplodeMonthSubjectAssignment($stdClass);
                    }
                }

                $i++;
            }
        }

        return $data;
    }

    ////////////////////////////////////////////////////////////////////////////
    // TERM CLASS SUBJECT RESULT...
    ////////////////////////////////////////////////////////////////////////////
    public function getSubjectTermResult() {

        $data = array();

        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "term" => $this->term
                    , "month" => self::NO_MONTH
                    , "year" => self::NO_YEAR
                    , "section" => "SEMESTER"
                    , "schoolyearId" => $this->getSchoolyearId()
                    , "evaluationType" => $this->getSettingEvaluationType()
        );

        if ($this->listClassStudents()) {

            $scoreList = $this->getScoreListSubjectTermResult($stdClass);

            $data = $this->listStudentsData();
            $i = 0;
            foreach ($this->listClassStudents() as $value) {

                $stdClass->studentId = $value->ID;

                switch ($this->getSubjectScoreType()) {
                    case self::SCORE_NUMBER:
                        $AVERAGE = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                        $data[$i]["RANK"] = getScoreRank($scoreList, $AVERAGE);
                        $data[$i]["AVERAGE"] = $AVERAGE;

                        $data[$i]["MONTH_RESULT"] = $this->averageTermSubjectAssignmentByAllMonths($stdClass, self::WITH_FORMAT);

                        $data[$i]["TERM_RESULT"] = $this->averageTermSubjectResult($stdClass, self::WITH_FORMAT);

                        $data[$i]["ASSESSMENT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_NUMBER;
                        $data[$i]["ASSESSMENT_ID"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_NUMBER;
                        break;
                    case self::SCORE_CHAR:
                        $data[$i]["ASSESSMENT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                        $data[$i]["ASSESSMENT_ID"] = $this->getSubjectTermAssessment($stdClass)->ASSESSMENT_ID;
                        break;
                }

                if ($this->isDisplayMonthResult()) {
                    $stdClass->include_in_evaluation = self::INCLUDE_IN_MONTH;
                    $data[$i]["ASSIGNMENT_MONTH"] = $this->getImplodeSubjectAssignmentByAllMonths($stdClass);
                }

                $stdClass->include_in_evaluation = self::INCLUDE_IN_TERM;
                $data[$i]["ASSIGNMENT_TERM"] = $this->getImplodeSubjectAssignmentByTerm($stdClass);


                $i++;
            }
        }

        return $data;
    }

    ////////////////////////////////////////////////////////////////////////////
    // YEAR CLASS SUBJECT RESULT...
    ////////////////////////////////////////////////////////////////////////////
    public function getSubjectYearResult() {

        $data = array();

        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "term" => $this->term
                    , "month" => self::NO_MONTH
                    , "year" => self::NO_YEAR
                    , "section" => "YEAR"
                    , "schoolyearId" => $this->getSchoolyearId()
                    , "evaluationType" => $this->getSettingEvaluationType()
        );

        if ($this->listClassStudents()) {

            $scoreList = $this->getScoreListSubjectYearResult($stdClass);

            $data = $this->listStudentsData();
            $i = 0;
            foreach ($this->listClassStudents() as $value) {

                $stdClass->studentId = $value->ID;

                switch ($this->getSubjectScoreType()) {
                    case self::SCORE_NUMBER:
                        $AVERAGE = $this->calculatedAverageYearSubjectResult($stdClass, self::WITH_FORMAT);
                        $data[$i]["RANK"] = getScoreRank($scoreList, $AVERAGE);
                        $data[$i]["AVERAGE"] = $AVERAGE;

                        switch ($this->getTermNumber()) {
                            case 1:
                                $stdClass->term = "FIRST_TERM";
                                $data[$i]["FIRST_TERM_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                $stdClass->term = "SECOND_TERM";
                                $data[$i]["SECOND_TERM_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                $stdClass->term = "THIRD_TERM";
                                $data[$i]["THIRD_TERM_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                break;
                            case 2:
                                $stdClass->term = "FIRST_QUARTER";
                                $data[$i]["FIRST_QUARTER_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                $stdClass->term = "SECOND_QUARTER";
                                $data[$i]["SECOND_QUARTER_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                $stdClass->term = "THIRD_QUARTER";
                                $data[$i]["THIRD_QUARTER_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                $stdClass->term = "FOURTH_QUARTER";
                                $data[$i]["FOURTH_QUARTER_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                break;
                            default:
                                $stdClass->term = "FIRST_SEMESTER";
                                $data[$i]["FIRST_SEMESTER_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                $stdClass->term = "SECOND_SEMESTER";
                                $data[$i]["SECOND_SEMESTER_RESULT"] = $this->calculatedAverageTermSubjectResult($stdClass, self::WITH_FORMAT);
                                break;
                        }

                        $data[$i]["ASSESSMENT"] = $this->getSubjectYearAssessment($stdClass)->LETTER_GRADE_NUMBER;
                        $data[$i]["ASSESSMENT_ID"] = $this->getSubjectYearAssessment($stdClass)->LETTER_GRADE_NUMBER;
                        break;
                    case self::SCORE_CHAR:

                        switch ($this->getTermNumber()) {
                            case 1:
                                $stdClass->term = "FIRST_TERM";
                                $data[$i]["FIRST_TERM_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                $stdClass->term = "SECOND_TERM";
                                $data[$i]["SECOND_TERM_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                $stdClass->term = "THIRD_TERM";
                                $data[$i]["THIRD_TERM_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                break;
                            case 2:
                                $stdClass->term = "FIRST_QUARTER";
                                $data[$i]["FIRST_QUARTER_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                $stdClass->term = "SECOND_QUARTER";
                                $data[$i]["SECOND_QUARTER_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                $stdClass->term = "THIRD_QUARTER";
                                $data[$i]["THIRD_QUARTER_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                $stdClass->term = "FOURTH_QUARTER";
                                $data[$i]["FOURTH_QUARTER_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                break;
                            default:
                                $stdClass->term = "FIRST_SEMESTER";
                                $data[$i]["FIRST_SEMESTER_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                $stdClass->term = "SECOND_SEMESTER";
                                $data[$i]["SECOND_SEMESTER_RESULT"] = $this->getSubjectTermAssessment($stdClass)->LETTER_GRADE_CHAR;
                                break;
                        }
                        $data[$i]["ASSESSMENT"] = $this->getSubjectYearAssessment($stdClass)->LETTER_GRADE_CHAR;
                        $data[$i]["ASSESSMENT_ID"] = $this->getSubjectYearAssessment($stdClass)->ASSESSMENT_ID;
                        break;
                }

                $i++;
            }
        }

        return $data;
    }

    ////////////////////////////////////////////////////////////////////////////

    public function calculatedAverageTermSubjectResult($stdClass, $withFormat = false) {

        $stdClass->include_in_evaluation = self::INCLUDE_IN_TERM;
        $TERM_RESULT = $this->averageTermSubjectResult($stdClass, false);

        if ($this->isDisplayMonthResult()) {

            $stdClass->include_in_evaluation = self::INCLUDE_IN_MONTH;
            $MONTH_RESULT = $this->averageAllMonthsSubjectResult($stdClass);

            if ($MONTH_RESULT && !$TERM_RESULT) {
                $result = $MONTH_RESULT;
            } elseif (!$MONTH_RESULT && $TERM_RESULT) {
                $result = $TERM_RESULT;
            } elseif ($MONTH_RESULT && $TERM_RESULT) {
                $result = ($MONTH_RESULT + $TERM_RESULT) / 2;
            } else {
                $result = 0;
            }
        } else {
            $result = $TERM_RESULT;
        }

        if ($withFormat) {
            $COUNT = SQLEvaluationStudentAssignment::checkExistStudentSubjectAssignment($stdClass);

            if (!$COUNT) {
                $output = "---";
            } else {
                $output = displayRound($result);
            }
        } else {
            $output = $result;
        }

        return $output;
    }

    public function calculatedAverageYearSubjectResult($stdClass) {

        switch ($this->getTermNumber()) {
            case 1:

                switch ($this->getSettingYearTermResult()) {
                    case self::AVG_T1:
                        $stdClass->term = "FIRST_TERM";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    case self::AVG_T2:
                        $stdClass->term = "SECOND_TERM";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    case self::AVG_T3:
                        $stdClass->term = "THIRD_TERM";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    default:
                        $stdClass->term = "FIRST_TERM";
                        $FIRST = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        $stdClass->term = "SECOND_TERM";
                        $SECOND = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        $stdClass->term = "THIRD_TERM";
                        $THIRD = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);

                        if ($FIRST && !$SECOND && !$THIRD) {
                            $result = $FIRST;
                        } elseif (!$FIRST && $SECOND && !$THIRD) {
                            $result = $SECOND;
                        } elseif (!$FIRST && !$SECOND && $THIRD) {
                            $result = $THIRD;
                        } elseif ($FIRST && $SECOND && $THIRD) {

                            $NUMERATOR = $this->getFirstTermCoeff() * $FIRST + $this->getSecondTermCoeff() * $SECOND + $this->getThirdTermCoeff() * $THIRD;
                            $DEVISOR = $this->getFirstTermCoeff() + $this->getSecondTermCoeff() + $this->getThirdTermCoeff();
                            $result = ($NUMERATOR / $DEVISOR);
                        } else {
                            $result = 0;
                        }
                        break;
                }

                break;
            case 2:

                switch ($this->getSettingYearTermResult()) {
                    case self::AVG_Q1:
                        $stdClass->term = "FIRST_QUARTER";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    case self::AVG_Q2:
                        $stdClass->term = "SECOND_QUARTER";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    case self::AVG_Q3:
                        $stdClass->term = "THIRD_QUARTER";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    case self::AVG_Q4:
                        $stdClass->term = "FOURTH_QUARTER";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    default:
                        $stdClass->term = "FIRST_QUARTER";
                        $FIRST = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        $stdClass->term = "SECOND_QUARTER";
                        $SECOND = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        $stdClass->term = "THIRD_QUARTER";
                        $THIRD = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        $stdClass->term = "FOURTH_QUARTER";
                        $FOURTH = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);

                        if ($FIRST && !$SECOND && !$THIRD && !$FOURTH) {
                            $result = $FIRST;
                        } elseif (!$FIRST && $SECOND && !$THIRD && !$FOURTH) {
                            $result = $SECOND;
                        } elseif (!$FIRST && !$SECOND && $THIRD && !$FOURTH) {
                            $result = $THIRD;
                        } elseif (!$FIRST && !$SECOND && !$THIRD && $FOURTH) {
                            $result = $FOURTH;
                        } elseif ($FIRST && $SECOND && $THIRD && $FOURTH) {

                            $NUMERATOR = $this->getFirstQuarterCoeff() * $FIRST + $this->getSecondQuarterCoeff() * $SECOND + $this->getThirdQuarterCoeff() * $THIRD + $this->getFourthQuarterCoeff() * $FOURTH;
                            $DEVISOR = $this->getFirstQuarterCoeff() + $this->getSecondQuarterCoeff() + $this->getThirdQuarterCoeff() + $this->getFourthQuarterCoeff();
                            $result = ($NUMERATOR / $DEVISOR);
                        } else {
                            $result = 0;
                        }
                        break;
                }

                break;
            default:

                switch ($this->getSettingYearTermResult()) {
                    case self::AVG_S1:
                        $stdClass->term = "FIRST_SEMESTER";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    case self::AVG_S2:
                        $stdClass->term = "SECOND_SEMESTER";
                        $result = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        break;
                    default:
                        $stdClass->term = "FIRST_SEMESTER";
                        $FIRST = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);
                        $stdClass->term = "SECOND_SEMESTER";
                        $SECOND = $this->calculatedAverageTermSubjectResult($stdClass, self::WITHOUT_FORMAT);

                        if ($FIRST && !$SECOND) {
                            $result = $FIRST;
                        } elseif (!$FIRST && $SECOND) {
                            $result = $SECOND;
                        } elseif ($FIRST && $SECOND) {

                            $NUMERATOR = $this->getFirstSemesterCoeff() * $FIRST + $this->getSecondSemesterCoeff() * $SECOND;
                            $DEVISOR = $this->getFirstSemesterCoeff() + $this->getSecondSemesterCoeff();
                            $result = ($NUMERATOR / $DEVISOR);
                        } else {
                            $result = 0;
                        }
                        break;
                }
                break;
        }

        $COUNT = SQLEvaluationStudentAssignment::checkExistStudentSubjectAssignmentByYear($stdClass);

        if (!$COUNT) {
            $output = "---";
        } else {
            if ($result == 0) {
                $output = 0;
            } else {
                $output = displayRound($result);
            }
        }

        return $output;
    }

    public function averageMonthSubjectResult($stdClass, $withFormat = false) {

        $result = SQLEvaluationStudentAssignment::calculatedAverageSubjectResult($stdClass);

        if ($withFormat) {
            $COUNT = SQLEvaluationStudentAssignment::checkExistStudentSubjectAssignment($stdClass);
            if (!$COUNT) {
                $output = "---";
            } else {
                $output = $result;
            }
        } else {
            $output = $result;
        }

        return $output;
    }

    public function averageAllMonthsSubjectResult($stdClass, $withFormat = false) {

        $result = SQLEvaluationStudentAssignment::calculatedAverageSubjectResult($stdClass);

        if ($withFormat) {
            $COUNT = SQLEvaluationStudentAssignment::checkExistStudentSubjectAssignment($stdClass);
            if (!$COUNT) {
                $output = "---";
            } else {
                $output = $result;
            }
        } else {
            $output = $result;
        }

        return $output;
    }

    public function averageTermSubjectResult($stdClass, $withFormat = false) {

        $stdClass->include_in_evaluation = self::INCLUDE_IN_TERM;

        $result = SQLEvaluationStudentAssignment::calculatedAverageSubjectResult($stdClass);

        if ($withFormat) {
            $COUNT = SQLEvaluationStudentAssignment::checkExistStudentSubjectAssignment($stdClass);
            
            if (!$COUNT) {
                $output = "---";
            } else {
                $output = $result;
            }
        } else {
            $output = $result;
        }

        return $output;
    }

    public function averageTermSubjectAssignmentByAllMonths($stdClass) {

        $stdClass->include_in_evaluation = self::INCLUDE_IN_MONTH;
        $COUNT = SQLEvaluationStudentAssignment::checkExistStudentSubjectAssignment($stdClass);
        $result = SQLEvaluationStudentAssignment::calculatedAverageSubjectResult($stdClass);

        if (!$COUNT) {
            $output = "---";
        } else {
            $output = $result;
        }

        return $output;
    }

    public function getImplodeMonthSubjectAssignment($stdClass) {

        return SQLEvaluationStudentAssignment::getImplodeQuerySubjectAssignment($stdClass);
    }

    public function getImplodeSubjectAssignmentByAllMonths($stdClass) {

        return SQLEvaluationStudentAssignment::getImplodeQuerySubjectAssignment($stdClass);
    }

    public function getImplodeSubjectAssignmentByTerm($stdClass) {

        return SQLEvaluationStudentAssignment::getImplodeQuerySubjectAssignment($stdClass);
    }

    protected function getScoreListSubjectMonthResult($stdClass) {

        $data = array();
        if ($this->listClassStudents()) {
            foreach ($this->listClassStudents() as $value) {
                $stdClass->studentId = $value->ID;
                $data[] = $this->averageMonthSubjectResult($stdClass);
            }
        }
        return $data;
    }

    protected function getScoreListSubjectTermResult($stdClass) {

        $data = array();
        if ($this->listClassStudents()) {
            foreach ($this->listClassStudents() as $value) {
                $stdClass->studentId = $value->ID;
                $data[] = $this->calculatedAverageTermSubjectResult($stdClass);
            }
        }
        return $data;
    }

    public function getSubjectMonthAssessment($stdClass) {

        return SQLEvaluationStudentSubject::getCallStudentSubjectEvaluation($stdClass);
    }

    protected function getScoreListSubjectYearResult($stdClass) {

        $data = array();
        if ($this->listClassStudents()) {
            foreach ($this->listClassStudents() as $value) {
                $stdClass->studentId = $value->ID;
                $data[] = $this->calculatedAverageYearSubjectResult($stdClass);
            }
        }
        return $data;
    }

    public function getSubjectTermAssessment($stdClass) {

        return SQLEvaluationStudentSubject::getCallStudentSubjectEvaluation($stdClass);
    }

    public function getSubjectYearAssessment($stdClass) {

        return SQLEvaluationStudentSubject::getCallStudentSubjectEvaluation($stdClass);
    }

    public function actionStudentSubjectAssessment() {

        $defaultObject = (object) array(
                    "studentId" => $this->studentId
                    , "academicId" => $this->academicId
                    , "section" => $this->getSection()
                    , "subjectId" => $this->subjectId
                    , "scoreType" => $this->getSubjectScoreType()
                    , "month" => $this->getMonth()
                    , "year" => $this->getYear()
                    , "term" => $this->term
                    , "assessmentId" => $this->actionValue
                    , "schoolyearId" => $this->getSchoolyearId()
                    , "educationSystem" => $this->getEducationSystem()
                    , "evaluationType" => $this->getSettingEvaluationType()
        );

        $stdClass = $defaultObject;
        $stdClass->actionValue = $this->getSubjectValue($defaultObject);

        return SQLEvaluationStudentSubject::setActionStudentSubjectEvaluation($stdClass);
    }

    public function actionPublishSubjectAssessment() {

        $data = array(
            "academicId" => $this->academicId
            , "section" => $this->getSection()
            , "subjectId" => $this->subjectId
            , "scoreType" => $this->getSubjectScoreType()
            , "month" => $this->getMonth()
            , "year" => $this->getYear()
            , "term" => $this->term
            , "actionField" => "SUBJECT_VALUE"
            , "schoolyearId" => $this->getSchoolyearId()
            , "evaluationType" => $this->getSettingEvaluationType()
            , "educationSystem" => $this->getEducationSystem()
        );

        switch ($this->getSection()) {
            case "MONTH":
                $result = $this->getSubjectMonthResult();
                break;
            case "TERM":
            case "QUARTER":
            case "SEMESTER":
                $result = $this->getSubjectTermResult();
                break;
            case "YEAR":
                $result = $this->getSubjectYearResult();
                break;
        }

        for ($i = 0; $i <= count($result); $i++) {

            $data["studentId"] = isset($result[$i]["ID"]) ? $result[$i]["ID"] : "";
            $data["actionRank"] = isset($result[$i]["RANK"]) ? $result[$i]["RANK"] : "";
            $data["assessmentId"] = isset($result[$i]["ASSESSMENT_ID"]) ? $result[$i]["ASSESSMENT_ID"] : "";

            switch ($this->getSubjectScoreType()) {
                case self::SCORE_NUMBER:
                    $data["actionValue"] = isset($result[$i]["AVERAGE"]) ? $result[$i]["AVERAGE"] : "";
                    break;
                case self::SCORE_CHAR:
                    $data["actionValue"] = isset($result[$i]["ASSESSMENT"]) ? $result[$i]["ASSESSMENT"] : "";
                    break;
            }

            SQLEvaluationStudentSubject::setActionStudentSubjectEvaluation((object) $data);
        }
    }

    public function getSubjectValue($stdClass) {

        $result = "";
        switch ($this->getSubjectScoreType()) {
            case self::SCORE_NUMBER:
                switch ($this->getSection()) {
                    case "MONTH":
                        $result = $this->averageMonthSubjectResult($stdClass);
                        break;
                    case "TERM":
                    case "QUARTER":
                    case "SEMESTER":
                        $result = $this->calculatedAverageTermSubjectResult($stdClass);
                        break;
                    case "YEAR":
                        $result = $this->calculatedAverageYearSubjectResult($stdClass);
                        break;
                }
                break;
            case self::SCORE_CHAR:
                $gradingObject = SpecialDBAccess::findGradingSystemFromId($this->actionValue);
                $result = $gradingObject ? $gradingObject->LETTER_GRADE : "";
                break;
        }
        return $result;
    }

    public function actionTeacherScoreEnter() {
        $stdClass = (object) array(
                    "studentId" => $this->studentId
                    , "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "scoreType" => $this->getSubjectScoreType()
                    , "month" => $this->getMonth()
                    , "year" => $this->getYear()
                    , "term" => $this->term
                    , "date" => $this->date
                    , "assignmentId" => $this->assignmentId
                    , "actionField" => $this->actionField
                    , "actionValue" => $this->actionValue
                    , "coeffValue" => $this->getAssignmentCoeff()
                    , "evaluationType" => $this->getSettingEvaluationType()
                    , "include_in_valuation" => $this->getAssignmentInCludeEvaluation()
                    , "educationSystem" => $this->getEducationSystem()
        );
        SQLEvaluationStudentAssignment::setActionStudentScoreSubjectAssignment($stdClass);
    }

    public function countTeacherScoreDate() {

        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "term" => $this->term
                    , "date" => $this->date
                    , "assignmentId" => $this->assignmentId
        );

        return SQLEvaluationStudentAssignment::getCountTeacherScoreDate($stdClass);
    }

    public function getListStudentsTeacherScoreEnter() {

        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "term" => $this->term
                    , "date" => $this->date
                    , "assignmentId" => $this->assignmentId
        );

        $data = array();

        if ($this->listClassStudents()) {

            $data = $this->listStudentsData();
            $i = 0;
            foreach ($this->listClassStudents() as $value) {

                $stdClass->studentId = $value->ID;
                $facette = SQLEvaluationStudentAssignment::getScoreSubjectAssignment($stdClass);

                $data[$i]["SCORE"] = $facette ? $facette->POINTS : "";
                $data[$i]["TEACHER_COMMENTS"] = $facette ? $facette->TEACHER_COMMENTS : "";

                $i++;
            }
        }

        return $data;
    }

    public function actionDeleteAllStudentsTeacherScoreEnter() {

        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
        );

        SQLEvaluationStudentAssignment::getActionDeleteAllStudentsTeacherScoreEnter();
    }

    public function actionDeleteOneStudentTeacherScoreEnter() {

        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "assignmentId" => $this->assignmentId
                    , "date" => $this->date
                    , "studentId" => $this->studentId
                    , "evaluationType" => $this->getSettingEvaluationType()
        );
        SQLEvaluationStudentAssignment::getActionDeleteOneStudentTeacherScoreEnter($stdClass);
    }

    public function actionDeleteSubjectScoreAssessment() {
        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
        );

        SQLEvaluationStudentSubject::getActionDeleteSubjectScoreAssessment($stdClass);
    }

    public function acitonSubjectAssignmentModifyScoreDate() {
        $stdClass = (object) array(
                    "academicId" => $this->academicId
                    , "subjectId" => $this->subjectId
                    , "setId" => $this->setId
                    , "modify_date" => $this->modify_date
        );
        SQLEvaluationStudentAssignment::getAcitonSubjectAssignmentModifyScoreDate($stdClass);
    }

    public function actionContentTeacherScoreInputDate() {
        $stdClass = (object) array(
                    "setId" => $this->setId
                    , "content" => $this->content
        );
        SQLEvaluationStudentAssignment::getActionContentTeacherScoreInputDate($stdClass);
    }

    public function loadContentTeacherScoreInputDate() {
        $stdClass = (object) array(
                    "setId" => $this->setId
        );

        $facette = SQLEvaluationStudentAssignment::findScoreInputDate($stdClass);

        $data = array();

        if ($facette) {
            $data["NAME"] = setShowText($facette->NAME);
            $data["SHORT"] = setShowText($facette->SHORT);
            $data["CONTENT"] = setShowText($facette->CONTENT);
            $data["SCORE_INPUT_DATE"] = getShowDate($facette->SCORE_INPUT_DATE);
        }

        return $data;
    }

}

?>