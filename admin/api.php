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
      $response["data"]["migration"]="2";
    }

    if ($_GET["command"]=="update"){
      if (!copy("admin/update.php", "ss-run.php")){
        $response["data"]["update"]="error";
      }else{
        //--Patch Data File
        $data=json_decode(utf8_encode(get_contents('https://raw.githubusercontent.com/CodeSimpleScript/core/master/version.json')), true);

        $zip="http://".$domain."/ss-run.php?zip=".urlencode("https://github.com/CodeSimpleScript/core/archive/master.zip")."";
        $updatenow=get_contents($zip);
        $response["data"]["update"]=$updatenow;
        $response["data"]["version"]=$data["version"];

        if ($updatenow=="good"){
          $system_data_update=true;
          $system_data["core_version"]=$data["version"];
          $ap=dirname(dirname(__FILE__));
          file_put_contents(''.$ap.'/data.json', json_encode($system_data, JSON_PRETTY_PRINT));
        }

      }
    }

  }else{
    $response["fail"]="authfail";
  }
}

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

?>
