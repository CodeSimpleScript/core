<?php
//token is not set yet so we will make it
if (!isset($settings["admin_authtoken"])){
  $settings["admin_authtoken"]=codegenerate(100);
  $settings["admin_password"]="falsenow";
  $settings_update=true;
}

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
  echo '<div class="form-header"><h3>Connect Code</h3></div><div class="form-content">';
  echo '<pre><code>'.$settings["admin_authtoken"].'</code></pre>';
  echo '</div>';
  include("admin/bottom.php");

}

?>
