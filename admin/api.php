<?php

$response=[];
$response["alive"]=true;
$response["data"]=[];

$domain=str_replace("www.", "", $_SERVER['HTTP_HOST']);

if (isset($_GET["command"])){
  if (canmakechange()==true){


    if ($_GET["command"]=="hello"){
      $response["data"]["domain"]=$domain;
      if (has_ssl($_SERVER['HTTP_HOST'])==true){
        $response["data"]["https"]=true;
      }else{
        $response["data"]["https"]=false;
      }
      $response["data"]["version"]=$system_data["core_version"];
    }

    if ($_GET["command"]=="update"){
      if (!copy("admin/update.php", "ss-run.php")){
        $response["data"]["update"]="error";
      }else{
        //--Patch Data File
        $data=json_decode(utf8_encode(get_contents('https://raw.githubusercontent.com/CodeSimpleScript/core/master/version.json')), true);
        if (isset($system_data["version"])){ unset($system_data["version"]); }
        $system_data["core_version"]=$data["version"];
        $system_data_update=true;
        $response=get_contents("http://".$domain."/ss-run.php?zip=".urlencode("https://github.com/CodeSimpleScript/core/archive/master.zip")."";
        $response["data"]["update"]=$response;
      }
    }

  }
}

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

?>
