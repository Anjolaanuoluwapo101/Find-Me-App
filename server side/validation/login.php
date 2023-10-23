<?php
require("../dependencies/database.php");

$array = $_GET;

$accountLogin = new LoginAccount();
echo $accountLogin->confirm_login($array);


?>