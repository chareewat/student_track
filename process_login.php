<?php
    require_once('dbconfig.inc.php');
    $en_username = mysqli_real_escape_string($conn,$_POST['username']);
    $en_password = mysqli_real_escape_string($conn,$_POST['password']);
    $enpasswd = MD5($en_password);
    $sql = "SELECT * FROM `psn_member` WHERE `psn_mem_username` = '$en_username' AND `psn_mem_password` ='$enpasswd' AND 	psn_memberstatus_id = 1";
    $query = mysqli_query($conn,$sql);
    if($query->num_rows>0)
    {
        $result = mysqli_fetch_assoc($query);
        $_SESSION['psn_mem_id'] = $result['psn_mem_id'];
        $_SESSION['psn_mem_name'] = $result['psn_mem_name'];
        $_SESSION['psn_mem_surname'] = $result['psn_mem_surname'];
        $_SESSION['psn_permit_id'] = $result['psn_permit_id'];
        $_SESSION['admin_type_id'] = $result['admin_type_id'];
        $_SESSION['psn_mem_vichakan_permit'] = $result['psn_mem_vicharkan_permit'];
        $_SESSION['psn_mem_behavior_permit'] = $result['psn_mem_behavior_permit'];
        $_SESSION['sch_key'] = $sch_key;
        $title_name = "";
        $sql = "SELECT psn_title_name FROM `psn_title` WHERE `psn_title_id` = ".$result['psn_title_id'];
        $ans = $conn->query($sql);
        if($ans->num_rows>0){
            $row = $ans->fetch_assoc();
            $title_name = $row['psn_title_name'];
        }
        $_SESSION['psn_title_name'] = $title_name;
        $conn->close();
        echo "<script>location.replace('lobby.php');</script>";
    }
    else{
        $conn->close();
        $_SESSION['loginfalse']=true;
        header('Location: login.php');
    }
?>
