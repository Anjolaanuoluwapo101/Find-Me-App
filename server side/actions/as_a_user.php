<?php
require('../dependencies/Controller.php');
try {
  if (isset($_GET['load_guardian_list'])) {
    $unique_otp = $_GET['unique_otp'];
    $load_guardians = new Control();
    echo json_encode($load_guardians->load_guardian_list($unique_otp));
  } else if (isset($_GET['update_user_location_SOS'])) {
    $data = $_GET;
    $alert_guardians = new Control();
    //echo $data['location'];
    echo $alert_guardians->alert_guardians($data['user_unique_otp'], $data['location'], $data['timeStamp']);

  }else if(isset($_GET["add_guardian"])){
    $user_otp = $_GET["user_otp"];
    $guardian_otp = $_GET["guardian_otp"];
    $add_guardian = new Control();
    echo $add_guardian->add_guardian($user_otp,$guardian_otp);
  }else if(isset($_GET["remove_guardian"])){
    $user_otp = $_GET["user_otp"];
    $guardian_otp = $_GET["guardian_otp"];
    $remove_guardian = new Control();
    echo $remove_guardian->remove_guardian($user_otp,$guardian_otp);
  } else {
    echo "No data";
  }
}catch(Exception $e) {
  echo $e->getMessage();
}


?>