<?php
class EmployeeData {
    private $con;
    private $candidateID;
    
    public function __construct($connection, $candidateID) {
        $this->con = $connection;
        $this->candidateID = $candidateID;
    }
    public function getBasicInfo() {
        return $this->getData('candidates');
    }
    public function getEmail() {
        $emailQuery = "SELECT login_identifier FROM user_accounts WHERE account_id = {$this->candidateID}";
        $result = mysqli_query($this->con, $emailQuery);
        return ($result && mysqli_num_rows($result) > 0) ? $result : false;
    }
    public function getEducation() {
        return $this->getData('educational_background');
    }
    
    public function getCertifications() {
        return $this->getData('certifications');
    }
    
    public function getSkills() {
        return $this->getData('skills');
    }
    
    public function getExperience() {
        return $this->getData('work_experience');
    }
    
    public function getAllData() {
        return [
            'education' => $this->getEducation(),
            'certifications' => $this->getCertifications(),
            'skills' => $this->getSkills(),
            'experience' => $this->getExperience()
        ];
    }
    
    private function getData($table) {
        $query = "SELECT * FROM $table WHERE candidate_id = {$this->candidateID}";
        $result = mysqli_query($this->con, $query);
        return ($result && mysqli_num_rows($result) > 0) ? $result : false;
    }
}

