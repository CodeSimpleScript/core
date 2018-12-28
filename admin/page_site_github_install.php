<?php

logincheck();
if (!copy("admin/update.php", "ss-run.php")){
  echo "Install run failed";
}else{
  if ($system_data["gitgub_packages"]=="none"){
    $system_data["gitgub_packages"]=array();
  }
  
  $package=makesafe($_GET["package"]);
  $package_date=time();
  
  //--Get current version from GITHUB
  $options  = array('http' => array('user_agent'=> $_SERVER['HTTP_USER_AGENT']));
  $context  = stream_context_create($options);
  $response = file_get_contents('https://api.github.com/repos/'.$package.'', false, $context);
  
  $data=json_decode(utf8_encode($response), true); //Fetch Config Data
  if (isset($data["full_name"])){

    if (!isset($system_data["gitgub_packages"]["".md5($package).""])){
      $system_data["gitgub_packages"]["".md5($package).""]=array();
    }

    $system_data["gitgub_packages"]["".md5($package).""]["name"]=$package;
    $system_data["gitgub_packages"]["".md5($package).""]["date"]=$package_date;
    $system_data["gitgub_packages"]["".md5($package).""]["updatecode"]=$data["pushed_at"];
    $system_data["gitgub_packages"]["".md5($package).""]["description"]=$data["description"];
    $system_data_update=true;
    echo "<h1>Running Update</h1><script>window.setTimeout(function(){ modl(\"ss-run.php?zip=".urlencode("https://github.com/".$package."/archive/master.zip")."&admin_url=".urlencode($settings["admin_url"])."&page=site_github_done&folder=".urlencode($settings["location_code"])."&ui_nostyle=true\"); }, 500);</script>";
  }else{
    echo "<h1>ERROR</h1>The package you are trying to install cant be found on Github.";
    echo "<BR><BR>Package: ".$package."";
    echo "<BR>";
    print_r($data);
  }
}

?>
