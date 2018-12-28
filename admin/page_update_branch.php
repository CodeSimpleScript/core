<?php

logincheck();
echo '<div class="form-header"><h1>Update Branch</h1></div><div class="form-content">';
echo '<div class="form-group">Below you can pick the current update branch for your SimpleScript install. This allows you to switch to BETA or STABLE. After you switch just visit <a href="'.$settings["admin_url"].'?page=update">update</a> to install.</div>';

if (isset($_GET["setmode"])){
  if ($_GET["setmode"]=="stable"){
    $system_data["core_branch"]="stable";
    $system_data_update=true;
  }
  if ($_GET["setmode"]=="beta"){
    $system_data["core_branch"]="beta";
    $system_data_update=true;
  }
  if ($_GET["setmode"]=="development"){
    $system_data["core_branch"]="development";
    $system_data_update=true;
  }
}

if (!isset($system_data["core_branch"])){
  $system_data["core_branch"]="stable";
  $system_data_update=true;
}

if ($system_data["core_branch"]=="stable"){
  $branchversion="STABLE";
}
if ($system_data["core_branch"]=="beta"){
  $branchversion="BETA";
}
if ($system_data["core_branch"]=="development"){
  $branchversion="DEVELOPMENT";
}

$data=json_decode(utf8_encode(get_contents('https://downloads.codesimplescript.com/version_'.$system_data["core_branch"].'.json')), true); //Fetch Config Data

echo '<div class="form-group" style="padding:5px;background:#f2f2f2;border-radius:3px;"><h2 style="margin-top:0px;">'.$branchversion.'</h2>Current version on this is '.$data["version"].'.<br>Switch to: <a href="'.$settings["admin_url"].'?page=update_branch&setmode=stable">STABLE</a> - <a href="'.$settings["admin_url"].'?page=update_branch&setmode=beta">BETA</a> - <a href="'.$settings["admin_url"].'?page=update_branch&setmode=development">DEVELOPMENT</a></div></div>';

?>
