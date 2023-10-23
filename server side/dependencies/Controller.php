<?php
require('database.php');


class Control extends RegisterAccount {
  private $search_result;

  //a search function to search for a guardian.
  public function search_guardian($val) {
    $this->search_result = parent::check_for_duplicate('unique_otp', $val);
    if ($this->search_result == false) {
      return false;
    }
    //if the search wasn't empty.....
    else {
      return json_encode($this->search_result);
    }
  }

  /*
  users_list: this is a column for guardians which contains an array that stores the details of the user they're to protect
  guardians_list: this is a column for users which contains an array thrstores the detaila of th guardian tht is to protect them
  */

  //add guardian to user's guardians_list column
  public function add_guardian($user_otp, $guardian_otp) {
    try {
      $this->preparedStatement = $this->prepare("SELECT `guardians_list` FROM `accounts` WHERE `unique_otp`=$user_otp");
      $guardian_list = unserialize($this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC)['guardians_list']);
      //var_dump($guardian_list);
      if (array_key_exists($guardian_otp, $guardian_list)) {
        return "Guardian Already Added";
      }
      $this->preparedStatement = $this->prepare("SELECT full_name,email,gender,mobile_num,local_gov FROM accounts WHERE unique_otp=$guardian_otp");
      $new_guardian_details = $this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC);
      if (!isset($new_guardian_details['mobile_num'])) {
        return "This user doesn't exist"; //Prevents users from trying to add guardian otp that doesn't even exist in the database
      }
      
      $guardian_list[$guardian_otp] = $new_guardian_details;
      $guardian_list_for_frontend = json_encode($guardian_list);
      $guardian_list_for_db = serialize($guardian_list);
      //be committing changes ,we do the opposite
      if(!$this->add_guardian_helper($user_otp,$guardian_otp)){
       return "Error occurred while adding guardian";
      }
      $this->preparedStatement = $this->prepare("UPDATE accounts SET guardians_list=:guardians_list WHERE unique_otp=$user_otp");
      $this->preparedStatement->bindValue(':guardians_list', $guardian_list_for_db, SQLITE3_BLOB);
      $this->preparedStatement->execute();
      //now user can see from their updted guardian list that they just added a guardian.
      //but the guardian can't see this...//so we would need to update the users_list of the guardian too,so that the guardian knows he/she became a guardian of someone

      return "Guardian Added";
    } catch(Exception $e) {
      //echo $e->getMessage();
      return "Failed To Add Guardian";
    }
  }

  //removes guardian from users end
  public function remove_guardian($user_otp, $guardian_otp) {
    try {
      $this->preparedStatement = $this->prepare("SELECT guardians_list FROM accounts WHERE unique_otp=$user_otp");
      $guardian_list = unserialize($this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC)['guardians_list']);
      unset($guardian_list[$guardian_otp]);
      $guardian_list_for_db = serialize($guardian_list);
      //before committing changes,we would need to do the opposite
      if(!$this->remove_guardian_helper($user_otp,$guardian_otp)){
       return "Error occurred while adding guardian";
      }
      $this->preparedStatement = $this->prepare("UPDATE accounts SET guardians_list=:guardians_list WHERE unique_otp=$user_otp");
      $this->preparedStatement->bindValue(':guardians_list', $guardian_list_for_db, SQLITE3_BLOB);
      $this->preparedStatement->execute();
      return "Guardian Removed";
    }catch(Exception $e) {
      //echo $e->getMessage();
      return "Failed To Remove Guardian";
    }
  }


  //this is a direct inverse of what add_guardian() does.It adds the user to the guardian's user_list column
  public function add_guardian_helper($user_otp, $guardian_otp) {
    try {
      $this->preparedStatement = $this->prepare("SELECT users_list FROM accounts WHERE unique_otp=$guardian_otp");
      $users_list = unserialize($this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC)['users_list']);

      $this->preparedStatement = $this->prepare("SELECT full_name,email,gender,mobile_num,local_gov FROM accounts WHERE `unique_otp`=$user_otp");
      $new_user_details = $this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC);
      if (!array_key_exists($user_otp, $users_list)) {
        $users_list[$user_otp] = $new_user_details;
        $users_list_for_db = serialize($users_list);

        $this->preparedStatement = $this->prepare("UPDATE accounts SET users_list=:users_list WHERE unique_otp=$guardian_otp");
        $this->preparedStatement->bindValue(':users_list', $users_list_for_db, SQLITE3_BLOB);
        $this->preparedStatement->execute();


        //return "This Guardian will be notified about your SOS distress signal";
        return true;
      } else {
        //return "You have added this guardian before ";
        return true ; //return true nevertheless
      }
    } catch(Exception $e) {
      //echo $e->getMessage();
      return false;
    }
  }


  public function remove_guardian_helper($user_otp, $guardian_otp) {
    try {
      $this->preparedStatement = $this->prepare("SELECT users_list FROM accounts WHERE unique_otp=$guardian_otp");
      $users_list = unserialize($this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC)['users_list']);
      if(array_key_exists($user_otp,$users_list)){
       unset($users_list[$user_otp]);
      }else{
       return "User Not Added"; 
      }
      $users_list_for_db = serialize($users_list);
      $this->preparedStatement = $this->prepare("UPDATE accounts SET users_list=:users_list WHERE unique_otp=$guardian_otp");
      $this->preparedStatement->bindValue(':users_list', $users_list_for_db, SQLITE3_BLOB);
      $this->preparedStatement->execute();
      return "User Removed";
    }catch(Exception $e) {
      //echo $e->getMessage();
      return false;
    }
  }

  public function load_guardian_list($user_otp) {
    $this->preparedStatement = $this->prepare("SELECT `guardians_list` FROM accounts WHERE `unique_otp`=$user_otp");
    $guardians_list = unserialize($this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC)['guardians_list']);
    if(empty($guardians_list)){
     return "No Guardian Records";
    }else{
     return $guardians_list;
    }
  }

  public function alert_guardians($user_otp, $location, $timeStamp) {
    try {
      $this->preparedStatement = $this->prepare("INSERT INTO Whereabouts(user_otp,location,timestamp) VALUES(:user_unique_otp,:location,:timeStamp)");
      $this->preparedStatement->bindValue(':user_unique_otp', $user_otp, SQLITE3_INTEGER);
      $this->preparedStatement->bindValue(':location',$location, SQLITE3_TEXT);
      $this->preparedStatement->bindValue(':timeStamp', $timeStamp, SQLITE3_INTEGER);
      //$result = $this->preparedStatement->execute();
      $this->preparedStatement->execute();
      return "Location Saved";
    }catch(Exception $e) {
      return $e->getMessage();
    }
  }

  //this function loads the list of people that the Guardian is ought to protect
  public function load_users($guardian_otp,$timestamp) {
    try {
      $this->preparedStatement = $this->prepare("SELECT users_list FROM accounts WHERE unique_otp=$guardian_otp");
      $users_list = unserialize($this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC)['users_list']);
      if (!empty($users_list)) {
        //now we try to get users from the users list that have sounded their beacon
        $user_list_otps = array_keys($users_list);//we obtain the users unique otp
        $beacon_activated_users_otps_with_last_seen_location = $this->get_beacon_activated_users($user_list_otps,$timestamp);//we try to obtain the user otps whose beacon was sounded after that timestamp
        $beacon_activated_users_otps = array_keys($beacon_activated_users_otps_with_last_seen_location);
        if(!empty($beacon_activated_users_otps)){
          foreach($users_list as $user_otp=>$user_details){
            $user_otp =  (string) $user_otp;
            if(in_array($user_otp,$beacon_activated_users_otps)){
              $users_list[$user_otp]["SOS"] = 1;
              $users_list[$user_otp]["last_seen_location"] = $beacon_activated_users_otps_with_last_seen_location[$user_otp];
              //it adds an SOS key to every user that has checked clicked the SOS signal
            }
          }
          return $users_list;
        }else{
         return $users_list; 
        }
      } else {
        return "No User Records";
      }
    }catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  //gets a list of user unique otps and creates a list o the ones who sounded their beacon/beacon as be activated after the provided timestamp
  private function get_beacon_activated_users($user_otps, $timestamp) {

    $user_otps = implode(',', $user_otps);
    $query = "SELECT DISTINCT `user_otp`,location,MAX(`timestamp`) AS timestamp FROM Whereabouts WHERE `timestamp` > $timestamp AND `user_otp` IN (".$user_otps.") GROUP BY `user_otp`";

    $results = $this->query($query);
    $list_of_activated_beacon_users = [];

    while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
      $user_otp = (string) $result["user_otp"];
      $list_of_activated_beacon_users[$user_otp] =  urldecode($result["location"]);//because the location string is always encoded at the backend before it's stored
      //$list_of_activated_beacon_users[$result["user_otp"]] = $result["timestamp"];
    }
    
    return $list_of_activated_beacon_users;
    
  }
  
  public function get_location_history($user_otp){
    try{
      $this->preparedStatement = $this->query("SELECT mt.* FROM Whereabouts mt INNER JOIN (SELECT DISTINCT location FROM Whereabouts) dt ON mt.location=dt.location WHERE mt.user_otp = $user_otp ORDER BY timestamp DESC");
      $location_list = [];
      while($location_update_of_user = $this->preparedStatement->fetchArray(SQLITE3_ASSOC)){
        $location_list[] = ["location" => $location_update_of_user["location"],"timestamp" =>  $location_update_of_user["timestamp"]];
      }
      return $location_list;
    }catch(Exception $e){
      echo $e->getMessage();
    }
  }
  
  public function get_user_latest_location($user_otp){
    try{
      $this->preparedStatement = $this->query("SELECT `user_otp`,`location`,MAX(`timestamp`) AS `timestamp` FROM Whereabouts WHERE user_otp=$user_otp LIMIT 1");
      return $this->preparedStatement->fetchArray(SQLITE3_ASSOC);
    }catch(Exception $e){
      echo $e->getMessage();
    }
  }
  
}




//$guardian_details = new UserControl();
//echo $guardian_details->add_guardian_helper(123,124)
//echo $add_guardian_details->remove_guardian(555555, 124);

?>