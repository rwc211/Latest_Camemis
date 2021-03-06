<?

require_once 'include/Common.inc.php';
require_once setUserLoacalization();

Class SpecialDBAccess {

    public static function dbAccess()
    {
        return Zend_Registry::get('DB_ACCESS');
    }

    public static function dbSelectAccess()
    {
        return self::dbAccess()->select();
    }

    public static function findGradingSystemFromId($Id)
    {

        $SQL = self::dbAccess()->select();
        $SQL->from("t_gradingsystem", array('*'));
        $SQL->where("ID = ?", $Id);
        //error_log($SQL->__toString());
        return self::dbAccess()->fetchRow($SQL);
    }

    ////////////////////////////////////////////////////////////////////////////
    //Gradingsystem...
    ////////////////////////////////////////////////////////////////////////////
    public static function jsonListGradingSystems($params)
    {

        $data = array();

        $start = $params["start"] ? (int) $params["start"] : "0";
        $limit = $params["limit"] ? (int) $params["limit"] : "50";
        $isActive = isset($params["isActive"]) ? $params["isActive"] : 0;
        $eduSystem = isset($params["eduSystem"]) ? $params["eduSystem"] : 0;

        $SQL = "SELECT * FROM t_gradingsystem";
        $SQL .= " WHERE 1=1";
        if ($isActive)
            $SQL .= " AND ACTIVE=1";
        $SQL .= " AND EDUCATION_TYPE='" . $eduSystem . "'";
        $SQL .= " ORDER BY SORTKEY ASC";
        $resultRows = self::dbAccess()->fetchAll($SQL);

        $i = 0;
        if ($resultRows)
            foreach ($resultRows as $value)
            {

                $data[$i]["ID"] = $value->ID;
                $data[$i]["LETTER_GRADE"] = $value->LETTER_GRADE ? setShowText($value->LETTER_GRADE) : "---";
                $data[$i]["GRADE_POINTS"] = $value->GRADE_POINTS ? setShowText($value->GRADE_POINTS) : "---";
                $data[$i]["MARK"] = $value->MARK ? setShowText($value->MARK) : "---";
                $data[$i]["ENGLISH"] = $value->ENGLISH ? setShowText($value->ENGLISH) : "---";
                $data[$i]["DESCRIPTION"] = $value->DESCRIPTION ? setShowText($value->DESCRIPTION) : "---";

                switch ($value->SCORE_TYPE)
                {
                    case 1:
                        $data[$i]["SCORE_TYPE"] = SCORE_ON_NUMBER;
                        break;
                    case 2:
                        $data[$i]["SCORE_TYPE"] = SCORE_ON_ALPHABET;
                        break;
                }

                $i++;
            }

        $a = array();
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

    public static function jsonGrading($edutype)
    {
        $SQL = "SELECT * FROM t_gradingsystem";
        $SQL .= " WHERE EDUCATION_TYPE=" . $edutype;
        $SQL .= " ORDER BY SORTKEY ASC";
        $result = self::dbAccess()->fetchAll($SQL);
        $data = array();
        if ($result)
        {
            $i = 0;
            foreach ($result as $value)
            {
                $data[$i]["ID"] = $value->ID;
                $data[$i]["NAME"] = $value->DESCRIPTION;
                $i++;
            }
        }

        return array(
            "success" => true
            , "totalCount" => sizeof($data)
            , "rows" => $data
        );
    }

    public static function jsonActonRemove($Id)
    {
        self::dbAccess()->delete('t_gradingsystem', array("ID='" . $Id . "'"));
        return array("success" => true);
    }

    public static function jsonActionGradingSystem($params)
    {

        $objectId = isset($params["objectId"]) ? addText($params["objectId"]) : "";
        $eduSystem = isset($params["eduSystem"]) ? $params["eduSystem"] : "";

        $SAVEDATA["DESCRIPTION"] = isset($params["DESCRIPTION"]) ? addText($params["DESCRIPTION"]) : "---";
        $SAVEDATA["ENGLISH"] = isset($params["ENGLISH"]) ? addText($params["ENGLISH"]) : "---";
        $SAVEDATA["LETTER_GRADE"] = isset($params["LETTER_GRADE"]) ? addText($params["LETTER_GRADE"]) : "---";
        $SAVEDATA["MARK"] = isset($params["MARK"]) ? addText($params["MARK"]) : "---";

        if (isset($params["GRADE_POINTS"]))
            $SAVEDATA["GRADE_POINTS"] = addText($params["GRADE_POINTS"]);

        if (isset($params["SCORE_TYPE"]))
            $SAVEDATA["SCORE_TYPE"] = addText($params["SCORE_TYPE"]);

        if (isset($params["IS_FAIL"]))
            $SAVEDATA["IS_FAIL"] = addText($params["IS_FAIL"]);

        if (isset($params["SORTKEY"]))
            $SAVEDATA["SORTKEY"] = addText($params["SORTKEY"]);

        $facette = self::findGradingSystemFromId($objectId);
        if ($facette)
        {
            $WHERE = array();
            $WHERE[] = "ID = '" . $objectId . "'";
            self::dbAccess()->update('t_gradingsystem', $SAVEDATA, $WHERE);
        }
        else
        {
            $SAVEDATA["EDUCATION_TYPE"] = addText($eduSystem);
            if ($eduSystem)
                self::dbAccess()->insert('t_gradingsystem', $SAVEDATA);
            $objectId = self::dbAccess()->lastInsertId();
        }

        return array("success" => true, "objectId" => $objectId);
    }

    ////////////////////////////////////////////////////////////////////////////
    //Log Academic...
    ////////////////////////////////////////////////////////////////////////////
    public static function jsonActionLogAcademic($objctId, $const)
    {

        $SAVE_DATA = array();
        $SAVE_DATA["ACADEMIC_ID"] = $objctId;
        $SAVE_DATA["ACTION_CONST"] = $const;
        $SAVE_DATA["ACTION_DATE"] = getCurrentDBDateTime();
        $SAVE_DATA["USER"] = Zend_Registry::get('USER')->ID;

        if ($objctId && $const)
            self::dbAccess()->insert('t_logacademic', $SAVE_DATA);
    }

    public static function jsonLoadLogAcademic($params)
    {

        $data = array();

        $start = $params["start"] ? (int) $params["start"] : "0";
        $limit = $params["limit"] ? (int) $params["limit"] : "50";
        $objectId = isset($params["objectId"]) ? addText($params["objectId"]) : 0;

        $SQL = "SELECT B.CODE AS ACTION_USER";
        $SQL .= " ,A.ACTION_CONST AS ACTION_CONST";
        $SQL .= " ,A.ACTION_DATE AS ACTION_DATE";
        $SQL .= " FROM t_logacademic AS A";
        $SQL .= " LEFT JOIN t_staff AS B ON A.USER=B.ID";
        $SQL .= " WHERE 1=1";
        $SQL .= " AND A.ACADEMIC_ID='" . $objectId . "'";
        $SQL .= " ORDER BY A.ACTION_DATE DESC";

        $resultRows = self::dbAccess()->fetchAll($SQL);

        $i = 0;
        if ($resultRows)
            foreach ($resultRows as $value)
            {

                $data[$i]["ACTION_CONST"] = $value->ACTION_CONST;
                $data[$i]["ACTION_USER"] = $value->ACTION_USER;
                $data[$i]["ACTION_DATE"] = $value->ACTION_DATE;

                $i++;
            }

        $a = array();
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

    public static function jsonLoadGradingSystem($Id)
    {

        $facette = self::findGradingSystemFromId($Id);

        $data = Array();
        if ($facette)
        {
            $data['ENGLISH'] = $facette->ENGLISH ? $facette->ENGLISH : "---";
            $data['LETTER_GRADE'] = $facette->LETTER_GRADE ? setShowText($facette->LETTER_GRADE) : "---";
            $data['SCORE_TYPE'] = $facette->SCORE_TYPE ? $facette->SCORE_TYPE : "---";
            $data['DESCRIPTION'] = $facette->DESCRIPTION ? setShowText($facette->DESCRIPTION) : "---";
            $data['MARK'] = $facette->MARK ? setShowText($facette->MARK) : "---";
            $data['GRADE_POINTS'] = $facette->GRADE_POINTS ? $facette->GRADE_POINTS : "---";
            $data['SORTKEY'] = $facette->SORTKEY;
            $data['IS_FAIL'] = $facette->IS_FAIL;
            $data["SCORE_MIN"] = displayNumberFormat($facette->SCORE_MIN);
            $data["SCORE_MAX"] = displayNumberFormat($facette->SCORE_MAX);
        }

        return array(
            "success" => true
            , "data" => $data
        );
    }

}

?>