<?php
require('../dependencies/Controller.php');
if(empty($_GET)){
  die('Invalid URL');
}

$data = $_GET;
try{
  if(isset($_GET['load_users_list'])){
  //load the list of users the guardian ought to protect 
    $load_users = new Control();
    echo json_encode($load_users->load_users($data['guardian_otp'],$data['timestamp']));
  }else if(isset($_GET['sos_activated_users'])){
  //load the list of users who have activated their SOS Beacon
    $sos = new Control();
    $sos_activated_users_list = $sos->load_users($data['guardian_otp'],$data['timestamp']);
    if(!is_array($sos_activated_users_list)){
    //this code block runs if no account is protected by this particular account making the request
     echo json_encode([]);
     return;
    }
    //load users gets the list of user along with their details, including whether they sounded their beacon or not
    //so now i would extract those only sounded their beacon..
    $sos_activated_usernames = [];
    foreach($sos_activated_users_list as $user_otp=>$user_details){
      if(isset($user_details['SOS'])){
       if($user_details['SOS'] == 1){
         $sos_activated_usernames[] = $user_details["full_name"];
       }
      }
    }
    echo json_encode($sos_activated_usernames);
  }else if(isset($_GET['remove_user'])){
    //guardians cant add users just remove,users can add/remove guardians
    $user_otp = $_GET['user_otp'];
    $guardian_otp = $_GET['guardian_otp'];
    $remove_user = new Control();
    echo $remove_user->remove_guardian_helper($data['user_otp'],$data['guardian_otp']);
  }else if(isset($_GET['location_history'])){
  //gives location history about a user who has his/her SOS Beacon 
    $location_update = new Control();
    echo json_encode($location_update->get_location_history($data['user_otp']));
  }else if(isset($_GET['latest_location'])){
    $location_update = new Control();
    echo json_encode($location_update->get_user_latest_location($data['user_otp']));
  }
  
}catch(Exception $e){
  echo $e->getMesage();
}


?>