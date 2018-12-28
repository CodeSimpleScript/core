<?php

logincheck();
echo '<h1>Updating...</h1>';
if (!copy("admin/update.php", "ss-run.php")){
  echo "Install run failed";
}else{
  //--Patch Data File
  if ($system_data["core_branch"]=="master"){ $system_data["core_branch"]="stable"; }

  $data=json_decode(utf8_encode(get_contents('https://downloads.codesimplescript.com/version_'.$system_data["core_branch"].'.json')), true); //Fetch Config Data
  if (isset($system_data["version"])){ unset($system_data["version"]); }
  $system_data["core_version"]=$data["version"];
  $system_data_update=true;
  echo "Just wait, installing...<script>window.setTimeout(function(){ modl(\"ss-run.php?zip=".urlencode("https://downloads.codesimplescript.com/".$system_data["core_branch"].".zip")."&admin_url=".urlencode($settings["admin_url"])."&page=update_done&ui_nostyle=true\"); }, 500);</script>";
}

?>
