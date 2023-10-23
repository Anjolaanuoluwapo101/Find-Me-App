<?php
//only verify_id aspect of script has been written...
require("../dependencies/database.php");
require("../dependencies/phpMailer.php");


//actually this should also recieve an image of the person signing up 
if(isset($_GET) && !empty($_GET)){
  $data = $_GET;
  
  $db = new RegisterAccount();
  echo $db->confirm_otp($data["otp"],$data["mobile_num"]);
}
?>