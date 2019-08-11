<?php
//token is not set yet so we will make it
if (!isset($settings["admin_authtoken"])){
  $settings["admin_authtoken"]=codegenerate(60);
}

$settings["admin_password"]="authdonesetupdone";
$settings_update=true;

include("admin/functions.php");

//Call home our key so we know we can be used
$domain=str_replace("www.", "", $_SERVER['HTTP_HOST']);
$checkin=get_contents("https://codewithss.org/api/instance_callhome?domain=".$domain."&authtoken=".$settings["admin_authtoken"]."");

//See if we are a API call
if (isset($_GET["command"])){
  include("admin/api.php");
}

//If we are not API call we are a page response to show the key
if (!isset($_GET["command"])){

  include("admin/top.php");
  echo '<style>.inner{ max-width: 600px !important; }</style>';
  echo '<div class="form-header"><h3>Connect Code</h3></div><div class="form-content">To setup this new instance and control it\'s updates and more go to <a href=\'http://codewithss.org/connect\' target=\'_new\'>http://codewithss.org/connect</a> and enter the code below to connect with your account.<BR><BR>';
  echo '<pre><code>'.$settings["admin_authtoken"].'</code></pre>';
  echo '</div>';
  include("admin/bottom.php");

}

?>
