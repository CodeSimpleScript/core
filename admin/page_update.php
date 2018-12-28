<?php

logincheck();
echo '<h1>Update SS</h1>';
echo 'Below we will list any important system updates that should be installed. We are checking for updates now.';

if (!isset($system_data["core_branch"])){
  $system_data["core_branch"]="stable";
  $system_data_update=true;
}

if ($system_data["core_branch"]=="master"){
  $system_data["core_branch"]="stable";
  $system_data_update=true;
}

//--Get current version from GITHUB
$data=json_decode(utf8_encode(get_contents('https://downloads.codesimplescript.com/version_'.$system_data["core_branch"].'.json')), true); //Fetch Config Data

if (strval($system_data["core_version"])!=strval($data["version"])){
  echo '<div onclick="modl(\''.$settings["admin_url"].'?page=update_install&ui_nostyle=true\');" style="background:rgb(231,76,60);color:#ffffff;padding:5px;margin-top:5px;margin-bottom:5px;border-radius:2px;">An update is avalable for SimpleScript ('.$data["version"].'), click to update. You are running version ('.$system_data["core_version"].').</div><div><h3>Update Notes</h3>'.$data["message"].'<BR><BR>For more information visit <a href="https://www.codesimplescript.com/changelog" target="_new">codesimplescript.com/changelog</a></div>';
}else{
  echo '<div style="background:rgb(39,174,96);color:#ffffff;padding:5px;margin-top:5px;margin-bottom:5px;border-radius:2px;">SimpleScript is fully updated and running version '.$system_data["core_version"].'.</div>';
  echo '<div style="position: absolute;bottom: 10px;left: 60px;"><a href="javascript:modl(\''.$settings["admin_url"].'?page=update_install&ui_nostyle=true\');">Refresh install</a> (runs like a normal update)</div>';
}

?>
