<?php

logincheck();
echo '<h1>Updating...</h1>';

if (!copy("admin/update.php", "ss-run.php")){
  echo "Install run failed";
}else{
  echo "Just wait, installing...<script>window.setTimeout(function(){ modl(\"ss-run.php?zip=".urlencode("https://github.com/codesimplescript/core/archive/master.zip")."&admin_url=".urlencode($settings["admin_url"])."&page=site_github_install_all&ui_nostyle=true\"); }, 500);</script>";
}

?>
