<?php
session_start();
require_once("global_config.php");
// Create connection person_central DB
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
// Check connection
if ($conn->connect_error) {
  	die("Connection failed: " . $conn->connect_error);
}
// Change character set to utf8
mysqli_set_charset($conn,"utf8");



$conn1 = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($conn1->connect_error) {
  	die("Connection failed: " . $conn1->connect_error);
}
mysqli_set_charset($conn1,"utf8");

?>