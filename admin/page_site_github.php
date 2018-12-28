<?php

logincheck();
echo '<div class="form-header"><h1>Github Packages</h1></div><div class="form-content">';
echo '<div class="form-group">Github packages allows you to easily install packages and files from a Github repository and keep them updated. All packages you add will be installed in your website folder (default /www). If you need to reset the web dir files at any point to fix broken installs or remove the default site <a href="javascript:modl(\''.$settings["admin_url"].'?page=site_github_dirclear&ui_nostyle=true&clear=ask\');">click here</a>.</div>';

$packages=0;

if (isset($system_data["gitgub_packages"])){
  if ($system_data["gitgub_packages"]!="none"){
    echo "<div style='margin-top:30px;margin-bottom:30px;'>";
    $a=$system_data["gitgub_packages"];
    foreach ($a as $v){
      $packages+=1;
      echo '<div class="form-group" style="padding:5px;background:#f2f2f2;border-radius:3px;"><h2 style="margin-top:0px;">'.$v["name"].'</h2>'.$v["description"].'<BR>Actions: <a href="javascript:modl(\''.$settings["admin_url"].'?page=site_github_remove&ui_nostyle=true&package='.$v["name"].'\');">Remove</a> - <a href="javascript:modl(\''.$settings["admin_url"].'?page=site_github_install&ui_nostyle=true&package='.$v["name"].'\');">Refresh install</a>';

      //Check for updates
      $options  = array('http' => array('user_agent'=> $_SERVER['HTTP_USER_AGENT']));
      $context  = stream_context_create($options);
      $response = file_get_contents('https://api.github.com/repos/'.$v["name"].'', false, $context);

      $data=json_decode(utf8_encode($response), true); //Fetch Config Data
      if (isset($data["full_name"])){
        if ($data["pushed_at"]!=$v["updatecode"]){
          echo '<div onclick="modl(\''.$settings["admin_url"].'?page=site_github_install&ui_nostyle=true&package='.$v["name"].'\');" style="background:rgb(231,76,60);color:#ffffff;padding:5px;margin-top:10px;margin-bottom:5px;border-radius:3px;margin-left:-5px;margin-right:-5px;margin-bottom:-5px;">An update is avalable for '.$v["name"].', click to update.</div>';
        }else{
          echo '<div style="background:rgb(39,174,96);color:#ffffff;padding:5px;margin-top:10px;margin-bottom:5px;border-radius:3px;margin-left:-5px;margin-right:-5px;margin-bottom:-5px;">'.$v["name"].' is running the latest version.</div>';
        }
      }
     echo '</div>';
    }
    echo "</div>";
  }
}

if ($packages==0){
  echo '<div class="form-group"><div style="background:rgb(231,76,60);color:#ffffff;padding:5px;margin-top:5px;margin-bottom:5px;border-radius:2px;">You have no packages yet, install one below first.</div></div>'; 
}

echo '<form action="'.$settings["admin_url"].'?page=site_github" method="post"><div class="form-group"><h1>Install new package</h1><label>Github Package (codesimplescript/website)</label><input type="text" id="package" name="package" required="required" /></div><div class="form-group"><button type="submit">Install</button></div></form>';

if (isset($_POST["package"])){
  echo '<script>window.setTimeout(function(){ modl(\''.$settings["admin_url"].'?page=site_github_install&ui_nostyle=true&package='.makesafe($_POST["package"]).'\'); }, 1000);</script>';
}

echo '</div>';

?>
