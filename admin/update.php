<?php
  //ss update file
  ini_set('memory_limit','60M');
  ini_set('date.timezone', 'America/New_York');
  set_time_limit(1200);

  if (isset($_GET['folder'])){
  	$folder=urldecode($_GET['folder']);
  	$folder=substr($folder, strpos($folder, "/") + 1);
  	$folder=$folder."/";
  }else{
  	$folder="";
  }

	function nhlog($line){
		$today = date("m.d.y");
		error_log("- ".$line."\n", 3, "system_update_".$today.".log");
	}
	function percent($num_amount, $num_total) {
		$count1 = $num_amount / $num_total;
		$count2 = $count1 * 100;
		$count = number_format($count2, 0);
		return $count;
	}
	nhlog("** Install Started **");
	nhlog("Install Time: ".date("Y-m-d H:i:s")."");

  if (isset($_GET["zip"])){
    $source = urldecode($_GET["zip"]."?version=".time()."");
    $dest = "ss-update-package.zip";
    copy($source, $dest);

    $options  = array('http' => array('user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.2 Safari/605.1.15'));
    $context  = stream_context_create($options);
    $response = file_get_contents($source, false, $context);
    if($response === false){
      nhlog("Download failed $source");
      die("error");
    }
    $save = file_put_contents($dest, $response);
    if($save === false){
      nhlog("Save failed $source");
      die("error");
    }

  }else{
    nhlog("No source fail");
    die("error");
  }

	$path = 'ss-update-package.zip';
	$zip = new ZipArchive;
	$i=0;
	$p=500;
	$d=false;
	if ($zip->open($path) === true){
		$files=$zip->numFiles;
		while ($p >= 1){
			$filename = $zip->getNameIndex($i);
			if ($filename!=""){
				$fileinfo = pathinfo($filename);
				$whatIWant = substr($filename, strpos($filename, "/") + 1);

				$move=true;

				if ($folder==""){
					if ($whatIWant=="conf.json"){ $move=false; }
					if ($whatIWant=="data.json"){ $move=false; }
					if ($whatIWant=="storage.json"){ $move=false; }
					if ($whatIWant==""){ $move=false; }
					if (preg_match("|www|i", $whatIWant, $var)){ $move=false; }
				}else{
					$whatIWant=$folder.$whatIWant;
				}

				if ($move==true){
					$copy=false;
					if (file_exists($whatIWant)){
						if (is_dir($whatIWant)){
							//--Already made folder
						}else{
              chmod($whatIWant, 755);
							unlink($whatIWant);
							$copy=true;
						}
					}else{
						$copy=true;
					}
					if ($copy==true){
						if ($whatIWant!=""){
							mkdir(dirname($whatIWant), 0777, true);
            						chmod($whatIWant, 755);
							copy("zip://".$path."#".$filename, $whatIWant);
							nhlog("File copy ".$filename." with ID ".$i.", part ".$p."");
						}
					}
				}else{
					nhlog("File skip ".$filename." with ID ".$i.", part ".$p.". - We dont install the following files");
				}
				$p=$p-1;
				$i=$i+1;
			}else{
				$p=0;
				$d=true;
				nhlog("All files installed with final file count of ".$i."");
			}
		}
		$zip->close();
		if ($d==true){
			echo "done";
      unlink("ss-run.php");
		}else{
			echo "error";
      nhlog("Unknown fail");
      unlink("ss-run.php");
		}
	}else{
		echo "error";
		nhlog("Not able to open installer zip file");
    unlink("ss-run.php");
	}
?>
