<?php

////////////////////////////////////////////////////////////////////////////////
// @Sor Veasna
// Date: 21.05.2014
////////////////////////////////////////////////////////////////////////////////
require_once 'models/filter/FilterData.php';
require_once 'include/Common.inc.php';

class FilterStudentDataSet extends FilterData{

    function __construct() {
        parent::__construct();
    }
    
    
    public function getDataSetStudentMaleFemale(){
        $entries = array('1' => MALE, '2' => FEMALE);
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $key=>$value)
            {
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value . "'";
                if($key==1)
                $DATASET .= ",'y':'" . $this->getCountStudentMale() . "'";
                if($key==2)
                $DATASET .= ",'y':'" . $this->getCountStudentFemale() . "'";
                
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;     
    }
    
    public function getDataSetStudentActiveAndDeactive(){
        
        $entries = array('ACTIVE' => ACTIVE, 'DEACTIVE' => 'Deactive');
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $key=>$value)
            {
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value . "'";
                if($key=='ACTIVE')
                $DATASET .= ",'y':'" . $this->getCountStudentActive() . "'";
                if($key=='DEACTIVE')
                $DATASET .= ",'y':'" . $this->getCountStudentDeactive() . "'";
                
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;    
    }
    
    public function getDataSetReligion(){
        
        $entries = FilterProperties::getCamemisType('RELIGION_TYPE');
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->religion = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value->NAME . "'";
                $DATASET .= ",'y':'" . $this->getCountStudentReligion() . "'";
                
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;      
    }
    
    public function getDataSetSMS(){
        
        $entries = array('0'=>'NO','1'=>'YES');
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $key=>$value)
            {
                $this->SMS = $key;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value . "'";
                $DATASET .= ",'y':'" . $this->getCountStudentSMS() . "'";
                
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;      
    }
    
    public function getDataSetNationality(){
        
        $entries = FilterProperties::getCamemisType('NATIONALITY_TYPE');
        $DATASET = "[{";
        $DATASET .= "key: '".NATIONALITY."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->nationality = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'" . $value->NAME . "'";
                $DATASET .= ",'value':'" . $this->getCountStudentNationality() . "'";
                
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        $DATASET .= "}]";
        
        return $DATASET;      
    }
    
     public function getDataSetEthnicity(){
        
        $entries = FilterProperties::getCamemisType('ETHNICITY_TYPE');
        $DATASET = "[{";
        $DATASET .= "key: '".ETHNICITY."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->ethnicity = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'" . $value->NAME . "'";
                $DATASET .= ",'value':'" . $this->getCountStudentEthnicity() . "'";
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        $DATASET .= "}]";
        
        return $DATASET;      
    }
    
    public function getDataSetStudentAge(){
        $entries = $this->groupAge();
        $DATASET = "[{";
        $DATASET .= "key: '".AGE."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $key=>$value)
            {
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'".AGE." " . $key . "'";
                $DATASET .= ",'value':'" . count($value) . "'";
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        $DATASET .= "}]";
        
        return $DATASET;      
    }
    
    public function getDataSetCountryProvince(){
        
        $entries = SQLStudentFilterReport::getAllLocation();
        $DATASET = "[{";
        $DATASET .= "key: '".CITY_PROVINCE."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->country_province = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'" . $value->NAME . "'";
                $DATASET .= ",'value':'" . $this->getCountStudentCountryProvince() . "'";
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        $DATASET .= "}]";
        
        return $DATASET;      
    }
}

?>