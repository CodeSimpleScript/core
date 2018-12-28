<?php

function rrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != ".."){
        if (filetype($dir."/".$object) == "dir"){
           rrmdir($dir."/".$object);
        }else{
          unlink($dir."/".$object);
          echo "<BR>Deleted ".$dir."/".$object."";
        }
      }
    }
    reset($objects);
    rmdir($dir);
  }else{
    echo "<BR>Error not a DIR";
  }
}

logincheck();
  
  if (makesafe($_GET["clear"])=="yes"){
    echo "<h1>Cleared</h1>We have deleted the files in the site folder.<BR><BR>";
    rrmdir($system["location"].$settings["location_code"]);
    if (!file_exists($system["location"].$settings["location_code"])) {
      mkdir($system["location"].$settings["location_code"], 0777, true);
    }
  }else{
    echo '<h1>Do you really want to clear?</h1>Doing this will delete all files in the web folder, all changes and modifications will be removed. This will also delete the default website files.<BR><BR><a href="javascript:modl(\''.$settings["admin_url"].'?page=site_github_dirclear&ui_nostyle=true&clear=yes\');">Clear files</a>';
  }
?>
