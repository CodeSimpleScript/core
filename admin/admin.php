<?php

function get_contents($url, $ua = 'Mozilla/5.0 (Windows NT 5.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1', $referer = 'http://www.google.com/') {
  if (function_exists('curl_exec')) {
    $header[0] = "Accept-Language: en-us,en;q=0.5";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_REFERER, $referer);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    $content = curl_exec($curl);
    curl_close($curl);
  }
  else {
    $content = file_get_contents($url);
  }
  return $content;
}

if (!isset($_GET["ui_nostyle"])){
  include ("admin/top.php");
}
$admin_datajson_updated=false;

//------------------------------------------Set Vars
if (!isset($_SESSION["admin_loggedin"])){
  $_SESSION["admin_loggedin"]=false;
}

include("functions.php");

//------------------------------------------Check Password Sent In
if (isset($_GET["password"])){
  if ($settings["admin_password"]=="changeme"){
    $_SESSION["admin_loggedin"]=false;
    redirectnow("".$settings["admin_url"]."?page=login&message=You must change the default password in the CONF.JSON file first.");
  }else{
    if (password_verify($_GET["password"], $settings["admin_password"])==true){
      $_SESSION["admin_loggedin"]=true;
      redirectnow("".$settings["admin_url"]."?page=dash");
    }else{
      $_SESSION["admin_loggedin"]=false;
      redirectnow("".$settings["admin_url"]."?page=login&message=Login Incorrect");
    }
  }
}

//------------------------------------------Set Password
if (isset($_GET["set_password"])){
  if ($settings["admin_password"]=="changeme" OR $settings["admin_password"]=="test"){
    $_SESSION["admin_loggedin"]=false;

    $crypt_options = array(
      'cost' => 5
    );
    $passwordset=password_hash($_GET["set_password"], PASSWORD_BCRYPT, $crypt_options);

    $settings["admin_password"]=$passwordset;
    $settings_update=true;
    redirectnow("".$settings["admin_url"]."?page=login&message=Your password is set, you can login now.");
  }else{
    redirectnow("".$settings["admin_url"]."?page=login");
  }
}

//------------------------------------------Get Page Info
if (!isset($_GET["page"])){
  if ($_SESSION["admin_loggedin"]==true){
    redirectnow("".$settings["admin_url"]."?page=dash");
  }else{
    redirectnow("".$settings["admin_url"]."?page=login");
  }
}else{
  $page=makesafe($_GET["page"]);
}

//------------------------------------------Display Messages
if (isset($_GET["message"])){
  echo "<div class=\"form-group\"><label>Hey You!</label>".makesafe($_GET["message"])."</div>";
}

include("page_".$page.".php");

if (!isset($_GET["ui_nostyle"])){
  include ("admin/bottom.php");
}
?>
