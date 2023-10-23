<?php
 require("../dependencies/database.php");
 require("../dependencies/phpMailer.php");


//there should be a data validation here.... forthe $_GET data
$data = $_GET;

if(!empty($data)){
  $db = new RegisterAccount();
  echo $db->add_account($data);
}else{
  echo "Empty Data,please ensure fields are filled";
}

?>