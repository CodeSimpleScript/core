<?php

function data_add($value,$data){
  global $admin_datajson_updated;
  global $system_data;
  $admin_datajson_updated=true;

  $system_data["".$value.""]=$data;
}

function data_update($value,$data){
  global $admin_datajson_updated;
  global $system_data;
  $admin_datajson_updated=true;

  $system_data["".$value.""]=$data;
}

function redirectnow($url){
  header("Location: ".$url."");
  shutdown();
  die("Error 22");
}

function logincheck(){
  global $settings;
  if ($_SESSION["admin_loggedin"]==false){
    redirectnow("".$settings["admin_url"]."?page=login");
    die("Error 66");
  }
}

?>
