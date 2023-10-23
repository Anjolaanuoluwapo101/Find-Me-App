<?php
//this class deals with Registration,OtO confirmation and Login.Note that some class mthods aren't in use.
class RegisterAccount extends SQLite3 {
  public $preparedStatement;
  public $account_type;
  private $result; //not in use
  private $path;
  public $absoluteLink;
  public $unique_otp;

  //modify constructor
  function __construct() {
    $this->open('../database/database.db');
    $this->path = pathinfo($_SERVER['SCRIPT_NAME'])['filename'];
    $this->absoluteLink = "http://".$_SERVER['HTTP_HOST']."Women%20App%20Server/profilepictures/";
 
  }

  //check if details to create account don't already exist in database.
  public function check_for_duplicate($column_name, $column_value) {
    try {
      $check_for_duplicate = $this->prepare("SELECT `$column_name` FROM accounts WHERE `$column_name` = :var");
      $check_for_duplicate->bindValue(':var', $column_value, SQLITE3_TEXT);
      return $check_for_duplicate->execute()->fetchArray(SQLITE3_ASSOC);
    }catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  //creates an account
  public function add_account($array) {

    //communicate with db
    try {
      if ($this->check_for_duplicate('mobile_num', $array['mobile_num']) != false) {
        throw New Exception("Phone number in use");
      } else if ($this->check_for_duplicate('email', $array['email']) != false) {
        throw New Exception("Email in use");
      } else {
        $this->preparedStatement = $this->prepare("INSERT INTO accounts (mobile_num,full_name,email,gender,local_gov,unique_otp,password) VALUES(:mobile_num,:full_name,:email,:gender,:local_gov,:unique_otp,:password)");
        $this->preparedStatement->bindValue(':mobile_num', $array['mobile_num'], SQLITE3_TEXT); //this is due to the country codes.
        $this->preparedStatement->bindValue(':full_name', $array['full_name'], SQLITE3_TEXT);
        $this->preparedStatement->bindValue(':email', $array['email'], SQLITE3_TEXT);
        $this->preparedStatement->bindValue(':gender', $array['gender'], SQLITE3_TEXT);
        $this->preparedStatement->bindValue(':local_gov', $array['local_gov'], SQLITE3_TEXT);
        $this->preparedStatement->bindValue(':password', $array['password'], SQLITE3_BLOB);

        //bind newly generated otp to new account*/
        $this->unique_otp = $this->generate_unique_otp(7);

        //send unique otp to user email
        $send_unique_otp = new SendMailToPerson();
        if ($send_unique_otp->sendmail($array['email'], "Your unique id is ".$this->unique_otp) === true) {
          $this->preparedStatement->bindValue(':unique_otp', $this->unique_otp, SQLITE3_INTEGER);
          $this->preparedStatement->execute();
          return "Please check your Email for your unique ID";
        };
      }


    }catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  
  public function confirm_otp($otp,$mobile_number){
    try{
      $this->preparedStatement = $this->prepare("SELECT `unique_otp` FROM accounts WHERE `mobile_num` = :mobile_num");
      $this->preparedStatement->bindValue(':mobile_num',$mobile_number,SQLITE3_TEXT);
      
      $generateOTPForAccount = $this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC)['unique_otp'];
      //echo $generateOTPForAccount;
      if($otp == $generateOTPForAccount){
        $this->preparedStatement = null;
        $this->preparedStatement = $this->prepare("UPDATE accounts SET `verified`= :verified WHERE `mobile_num` = :mobile_num");
        $this->preparedStatement->bindValue(":verified",1,SQLITE3_INTEGER);
        $this->preparedStatement->bindValue(":mobile_num",$mobile_number,SQLITE3_TEXT);
        $check = $this->preparedStatement->execute();
        echo "Verified";
      }else{
        return "Wrong OTP";
      }
    }catch(Exception $e){
      echo "Error Occurred";
    }
  }

  //this is to upload a picture...not in use currently
  public function save_profilepicture() {
    if (empty($_FILES["selfie"])) {
      throw New Exception ("No file found");
    }
    try {
      if ($_FILES["selfie"]['error'] == 0) {
        //first we validate the image and save
        $temp_name = $_FILES['selfie']['tmp_name'];
        $fileType = $_FILES['selfie']['type'];
        if (preg_match('/image/', $fileType)) {
          $name = basename($_FILES['selfie']['name']);
          move_uploaded_file($temp_name, "../profilepictures".$name);
          //update the image path to the account's row in db
          $this->preparedStatement = $this->prepare("INSERT INTO Users(image_path) VALUES(:image_path) WHERE mobile_num=:mobile_num");
          $this->preparedStatement->bindValue('image_path', $link.$name, SQLITE3_TEXT);
          $this->preparedStatement->bindValue(':mobile_num', "08148030821", SQLITE3_TEXT);
          $this->preparedStatement->execute();
          return "Image Uploaded";
        } else {
          return "Invalid FileType";
        }
      }
    }catch(Exception $e) {
      echo $e->getMessage();
    }
  }


  public function generate_unique_otp($length = 10) {
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

}


class LoginAccount extends RegisterAccount {
  public function confirm_login($array) {
    try{
      $this->preparedStatement = $this->prepare("SELECT mobile_num,local_gov,email,password,full_name,gender,unique_otp,verified FROM `accounts` WHERE `mobile_num` = :mobile_num AND `password` = :password");
      $this->preparedStatement->bindValue(':mobile_num',$array['mobile_num'],SQLITE3_TEXT);
      $this->preparedStatement->bindValue(':password',$array['password'],SQLITE3_BLOB);
      $this->preparedStatement->bindValue(':passworddd',$array['password'],SQLITE3_BLOB);
      $this->preparedStatement->bindValue(':mobile_nummm',$array['mobile_num'],SQLITE3_TEXT);
      $result = $this->preparedStatement->execute()->fetchArray(SQLITE3_ASSOC);
      //check if account is a verified one first
      //var_dump($result);
      if(isset($result['verified']) && $result['verified'] == 0){
        throw new Exception ("Not Verified");
      }else if(empty($result)){
        throw new Exception("NULL");
      }else{
        return json_encode($result); //it returns the users otp
         //echo "Login Successful"; 
      }
    }catch(Exception $e){
      echo $e->getMessage();
    }
  }
}



?>