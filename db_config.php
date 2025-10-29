<?php
$servername = "localhost"; 
$username = "root"; 
$password = "root"; 
$dbname = "database"; 

$conn = new mysqli($servername, $username, $password, $dbname);

 if(mysqli_connect_errno()){
 die(mysqli_connect_errno()) ; 
         }

?>
