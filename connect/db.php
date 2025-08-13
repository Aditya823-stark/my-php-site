<?php 

 class connect 
 {
        public $hostname  = 'localhost';
        private $username = 'root';
        private $password = '';
        private $dbname = 'rail_sys';

    public function myconnect()
    {
        $db = mysqli_connect($this->hostname, $this->username, $this->password, $this->dbname);
        return $db;
    }
 }
?>

