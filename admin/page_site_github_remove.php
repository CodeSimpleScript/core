<?php

logincheck();
  $package=makesafe($_GET["package"]);
  $package_date=time();
  if (isset($system_data["gitgub_packages"]["".md5($package).""])){
    unset($system_data["gitgub_packages"]["".md5($package).""]);
    $system_data_update=true;
    echo "<h1>Package removed</h1>Remove finished for package ".$package." with id code ".md5($package)."";
    //print_r($system_data);
    echo "<script>window.setTimeout(function(){ modl(\"close\"); window.location.href = \"".$settings["admin_url"]."?page=site_github\"; }, 500);</script>";
  }else{
    echo "Package is not installed.";
  }

?>
