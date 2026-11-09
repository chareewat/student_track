<?php
$memberid = 1;
if( isset($_SESSION['psn_mem_id']) && $_SESSION['sch_key'] == $sch_key ){
    //init
    $memberid = $_SESSION['psn_mem_id'];
}else{
    session_destroy();
    $link = "http://".$ip_server_global."/index.php";
    echo "<script> location.replace ('".$link."'); </script>";
}
?>