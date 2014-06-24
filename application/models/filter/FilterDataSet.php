<?php

////////////////////////////////////////////////////////////////////////////////
// @Sor Veasna
// Date: 21.05.2014
////////////////////////////////////////////////////////////////////////////////
require_once 'models/filter/FilterData.php';
require_once 'include/Common.inc.php';

class FilterDataSet extends FilterData{

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
        $this->dataType='religion';
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->dataValue = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value->NAME . "'";
                $DATASET .= ",'y':'" . $this->getCountStudentPersonalInformation() . "'";
                
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;      
    }
    
    public function getDataSetSMS(){
        
        $entries = array('0'=>'NO','1'=>'YES');
        $this->dataType='SMS';
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $key=>$value)
            {
                $this->dataValue = $key;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value . "'";
                $DATASET .= ",'y':'" . $this->getCountStudentPersonalInformation() . "'";
                
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;      
    }
    
    public function getDataSetNationality(){
        
        $entries = FilterProperties::getCamemisType('NATIONALITY_TYPE');
        $this->dataType='nationality';
        $DATASET = "[{";
        $DATASET .= "key: '".NATIONALITY."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->dataValue = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'" . $value->NAME . "'";
                $DATASET .= ",'value':'" . $this->getCountStudentPersonalInformation() . "'";
                
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
        $this->dataType='ethnicity';
        $DATASET = "[{";
        $DATASET .= "key: '".ETHNICITY."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->dataValue = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'" . $value->NAME . "'";
                $DATASET .= ",'value':'" . $this->getCountStudentPersonalInformation() . "'";
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
        $this->dataType='country_province';
        $DATASET = "[{";
        $DATASET .= "key: '".CITY_PROVINCE."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->dataValue = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'" . $value->NAME . "'";
                $DATASET .= ",'value':'" . $this->getCountStudentPersonalInformation() . "'";
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        $DATASET .= "}]";
        
        return $DATASET;      
    }
    
    public function getDataSetStaffGender(){
        $entries = array('1' => MALE, '2' => FEMALE);
        $this->dataType='gender';
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $key=>$value)
            {
                $this->dataValue=$key;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value . "'";
                $DATASET .= ",'y':'" . $this->getCountStaffPersonalInformation() . "'";
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;     
    }
    
    public function getDataSetStaffEthnicity(){
        
        $entries = FilterProperties::getCamemisType('ETHNICITY_TYPE');
        $this->dataType='ethnicity';
        $DATASET = "[{";
        $DATASET .= "key: '".ETHNICITY."',";
        $DATASET .= "values: [";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->dataValue = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'label':'" . $value->NAME . "'";
                $DATASET .= ",'value':'" . $this->getCountStaffPersonalInformation() . "'";
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        $DATASET .= "}]";
        
        return $DATASET;      
    }
    
    public function getDataSetStaffReligion(){
        
        $entries = FilterProperties::getCamemisType('RELIGION_TYPE');
        $this->dataType='religion';
        $DATASET = "[";
        if ($entries)
        {
            $i = 0;
            foreach ($entries as $value)
            {
                $this->dataValue = $value->ID;
                $DATASET .= $i ? "," : "";
                $DATASET .= "{";
                $DATASET .= "'key':'" . $value->NAME . "'";
                $DATASET .= ",'y':'" . $this->getCountStaffPersonalInformation() . "'";
                $DATASET .= "}";
                $i++;
            }
        }
        $DATASET .= "]";
        return $DATASET;      
    }
    
    
}

?>