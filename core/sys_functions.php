<?php

//#########################################################################################################
//######################################################################################################### - SYSTEM FUNCTIONS
//#########################################################################################################
//############################## - $id = ID of the function/area for variable storage
//############################## - $t = The content we are checking for functions
$mysql=null;
$mysql_connections=array();
$mysql_databases=array();
$sftp_connections=array();
$sftp_streams=array();
$tfa=null;

function ss_sys_function($id,$t,$process=false,$sandbox=false){
	global $system;
	global $mysql;
	global $mysql_connections;
	global $sftp_connections;
	global $sftp_streams;
	global $mysql_databases;
	global $tfa;
	global $storage;
	global $storage_update;

	if (checkpreg("/s\.([A-Za-z0-9_\-]*)\((.*)\)/i",$t)==true){ //--Check if we have a match for s.[A-Za-z0-9_-]()
		preg_match_all("/s\.([A-Za-z0-9_\-]*)\((.*)\)/i",$t, $got); //--Fetch each instance of a function on it's own so we dont mix them up
		foreach ($got[0] as $script){ //--For each found function that matches return only contained patern
			$func=fetchpreg("/s\.([A-Za-z0-9_\-]*)\(/i",$script); //--Take that patern that was returned and fetch from it the function name.
			$code=fetchpreg("/s\.".$func."\((.*)\)/i",$t); //--Take that patern that was returned and fetch from it the function content.

			$code_raw=trim_clean($code);
			$code_part=ss_sys_function_inputarray($id,$code,$process,$sandbox);//--break comma seperate parts into an array
			$code=decode_makesafe_ss_input(ss_code_variables_string_replace($id,trim_clean($code),$process,$sandbox));

			if ($system["debug"]==true){ $system["debug_log"].="\r\n> Run Function S - ".$func." | ".$t.""; }

			//-------------------------------------------------------------- ECHO
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.echo.md
			if ($func=="echo"){
				return $code;
			}

			//-------------------------------------------------------------- Am I a sandbox?
			if ($func=="ami_sandbox"){
				if ($sandbox==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- SYSTEM_DEBUG
			if ($func=="system_debug" && $sandbox==false){
				if ($code=="true"){
					$system["debug"]=true;
				}
				if ($code=="false"){
					$system["debug"]=false;
				}
			}

			//-------------------------------------------------------------- SYSTEM_MAXTIME
			if ($func=="system_maxtime"){
				set_time_limit(intval($code));
			}

			//-------------------------------------------------------------- SYSTEM_memory
			if ($func=="system_memory"){
				ini_set('memory_limit',''.$code.'M');
			}

			//-------------------------------------------------------------- SYSTEM_url
			if ($func=="system_url"){
				return $_SERVER["REQUEST_URI"];
			}

			//-------------------------------------------------------------- system_referral
			if ($func=="system_referral"){
				if (isset($_SERVER["HTTP_REFERER"])){
					return $_SERVER["HTTP_REFERER"];
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- system_request_method
			if ($func=="system_request_method"){
				return $_SERVER["REQUEST_METHOD"];
			}

			//-------------------------------------------------------------- SYSTEM_file
			if ($func=="system_file"){
				return $system["url"];
			}

			//-------------------------------------------------------------- SYSTEM_file
			if ($func=="system_file_original"){
				return $system["url_original"];
			}

			//-------------------------------------------------------------- SYSTEM_SLEEP_SECOND
			if ($func=="system_sleep_second"){
				sleep(intval($code));
			}

			//-------------------------------------------------------------- SYSTEM_SLEEP_MS
			if ($func=="system_sleep_ms"){
				usleep(((intval($code))*1000));
			}

			//-------------------------------------------------------------- SYSTEM_SLEEP_MS
			if ($func=="service_pwnedpasswords"){
				$passcheck=$code;

				$hash = sha1($passcheck);
				$first5 = substr($hash, 0, 5);
				if ($first5 == "da39a"){ //First5 For SHA1 Blank Space
					$first5 = null;
				}
				$lastbit = substr($hash, -35);
				$hash = null;
				$api = "https://api.pwnedpasswords.com/range/";
				$url = $api . $first5;
				$contents = file_get_contents($url);

				//Check data
				$rsuff = ":[0-9]{1,9}/mi";
				$regex = "/" . $lastbit . $rsuff;
				preg_match($regex, $contents, $matches);
				$pwnedpass = ($matches[0]);
				$numatch = "/:[0-9]{0,9}/mi";
				preg_match($numatch, $pwnedpass, $matches);
				$finmatch = substr($matches[0], 1);
				if ($finmatch==0){
					return "false";
				}else{
					return $finmatch;
				}
			}

			//-------------------------------------------------------------- url_exist
			if ($func=="url_exist"){
				$ch = curl_init($code);
				curl_setopt_array($ch, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code,
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_NOBODY => true
				));
		    curl_exec($ch);
		    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		    if($code == 200){
		       $status = true;
		    }else{
		      $status = false;
		    }
		    curl_close($ch);
		   	return $status;
			}

			//-------------------------------------------------------------- 2FA
			if ($func=="2fa" && $sandbox==false){
				if ($tfa==null){
					$tfa = new TwoFactorAuth($code);
					return true;
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- 2FA_verifycode
			if ($func=="2fa_verifycode" && $sandbox==false){
				if ($tfa!=null){
					$codeverify=$tfa->verifyCode($code_part[0], $code_part[1]);
					return $codeverify;
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- 2FA_createsecret
			if ($func=="2fa_createsecret" && $sandbox==false){
				if ($tfa!=null){
					$codesecret=$tfa->createSecret(160);
					return $codesecret;
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- 2FA_qrcode
			if ($func=="2fa_qrcode" && $sandbox==false){
				if ($tfa!=null){
					$qrcode=$tfa->getQRCodeImageAsDataUri($code_part[0],$code_part[1]);
					return $qrcode;
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- MATH
			if ($func=="math"){
				$cal = new Field_calculate();
				$result = $cal->calculate($code);
				return $result;
			}

			//-------------------------------------------------------------- convert_html_entities
			if ($func=="convert_html_entities"){
				return htmlentities($code, ENT_QUOTES, "UTF-8");
			}

			//-------------------------------------------------------------- SHA1
			if ($func=="sha1"){
				return sha1($code);
			}

			//-------------------------------------------------------------- MD5
			if ($func=="md5"){
				return md5($code);
			}

			//-------------------------------------------------------------- COOKIE_SET
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.cookie_set.md
			if ($func=="cookie_set" && $sandbox==false){
				if (!isset($code_part[3])){
					$code_part[3]="/";
				}
				$code_part[3]=trim($code_part[3],"{}");
				setcookie($code_part[0], $code_part[1], time() + ($code_part[2]), "/", $code_part[3]); // 86400 = 1 day
			}

			//-------------------------------------------------------------- COOKIE_DELETE
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.cookie_delete.md
			if ($func=="cookie_delete" && $sandbox==false){
				setcookie($code, "", time() - 3600);
			}

			//-------------------------------------------------------------- HEADER
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.header.md
			if ($func=="header" && $sandbox==false){
				header(''.$code.'');
			}

			//-------------------------------------------------------------- HEADER
			if ($func=="get_header" && $sandbox==false){
				$search=strtoupper($code);
				$search=str_replace("-", "_", $search);
				if (isset($_SERVER['HTTP_'.$search.''])){
					return $_SERVER['HTTP_'.$search.''];
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- HEADER
			if ($func=="get_header_auth" && $sandbox==false){
				$token = false;
				if (isset($_SERVER['HTTP_AUTHORIZATION'])){
					$token=$_SERVER['HTTP_AUTHORIZATION'];
				}
			  $headers = apache_request_headers();
			  if(isset($headers['Authorization'])){
			    $token=$headers['Authorization'];
			  }
				return $token;
			}

			//-------------------------------------------------------------- MARKDOWN
			if ($func=="markdown"){
				$Parsedown = new Parsedown();
				return $Parsedown->text($code);
			}

			//-------------------------------------------------------------- RANDOM_STRING
			if ($func=="random_string"){
				return codegenerate($code);
			}

			//-------------------------------------------------------------- request_body
			if ($func=="request_body" && $sandbox==false){
				$body = @file_get_contents('php://input');
				return $body;
			}

			//-------------------------------------------------------------- RANDOM_STRING_PHRASE
			if ($func=="random_string_phrase"){

				$set=["node"];
				$set=array_merge_recursive($set,file('core/words/animals.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
				$set=array_merge_recursive($set,file('core/words/colours.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
				$set=array_merge_recursive($set,file('core/words/computer.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
				$set=array_merge_recursive($set,file('core/words/landforms.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
				$set=array_merge_recursive($set,file('core/words/trees.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
				$set=array_merge_recursive($set,file('core/words/art.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
				$set=array_merge_recursive($set,file('core/words/astronomy.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
				$set=array_merge_recursive($set,file('core/words/videogames.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));


				if ($code!=""){
					$numbertimes=intval($code);
				}else{
					$numbertimes=mt_rand(2,3);
				}

				$random_name="";

				while ($numbertimes!=0){
					if ($random_name!=""){ $random_name.="-"; }
					if (mt_rand(1,3)==2){
						$random_name.=$set[mt_rand(0, sizeof($set) - 1)];
					}else{
						$adjectives = file('core/words/adjectives.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
						$random_name.=$adjectives[mt_rand(0, sizeof($adjectives) - 1)];
					}
					$numbertimes=$numbertimes-1;
				}

				return strtolower($random_name);

			}

			//-------------------------------------------------------------- SESSION
			if ($func=="session" && $sandbox==false){
				return $system["session"];
			}

			//-------------------------------------------------------------- SESSION
			if ($func=="session_reset" && $sandbox==false){
				session_unset();
				return true;
			}

			//-------------------------------------------------------------- SESSION
			if ($func=="session_destroy" && $sandbox==false){
				session_destroy();
				return true;
			}

			//-------------------------------------------------------------- STORAGE_GET
			if ($func=="storage_get" && $sandbox==false){
				return $storage["".$code.""];
			}

			//-------------------------------------------------------------- STORAGE_SET
			if ($func=="storage_set" && $sandbox==false){
				$storage["".$code_part[0].""]=$code_part[1];
				$storage_update=true;
				return true;
			}

			//-------------------------------------------------------------- PERCENT / PERCENTAGE
			if ($func=="percent" || $func=="percentage"){
				$myNumber = $code_part[1];
				//I want to get 25% of 928.
				$percentToGet = $code_part[0];
				//Convert our percentage value into a decimal.
				$percentInDecimal = $percentToGet / 100;
				//Get the result.
				$percent = $percentInDecimal * $myNumber;
				return $percent;
			}

			//-------------------------------------------------------------- PERCENT_of / PERCENTAGE_of
			if ($func=="percent_of" || $func=="percentage_of"){
				return ((100.0*$code_part[0])/$code_part[1]);
			}

			//-------------------------------------------------------------- TIMESTAMP_UNIX
			if ($func=="timestamp_unix"){
				if ($code==""){
					return time();
				}else{
					return (time()+intval($code));
				}
			}

			//-------------------------------------------------------------- convert_timestamp_string_unix
			if ($func=="convert_timestamp_string_unix"){
				return date($code_part[0],$code_part[1]);
			}

			//-------------------------------------------------------------- convert_timestamp_string
			if ($func=="convert_timestamp_string"){
				$oldcode=$code_part[1];
				$y=substr($oldcode, 0, 4); //[2018]0218105347
				$m=substr($oldcode, 4, 2); //[2018][02]18105347
				$d=substr($oldcode, 6, 2); //[2018][02][18]105347
				$h=substr($oldcode, 8, 2); //[2018][02][18][10]5347
				$mi=substr($oldcode, 10, 2); //[2018][02][18][10][53]47
				$s=substr($oldcode, 12, 2); //[2018][02][18][10][53][47]
				$stringdate="$y-$m-$d $h:$mi:$s";
				$timestampmake = strtotime($stringdate);
				return date($code_part[0],$timestampmake);
			}

			//-------------------------------------------------------------- convert_timestamp_ago
			if ($func=="convert_timestamp_ago"){
				$oldcode=$code_part[0];
				$full=$code_part[1];
				$y=substr($oldcode, 0, 4); //[2018]0218105347
				$m=substr($oldcode, 4, 2); //[2018][02]18105347
				$d=substr($oldcode, 6, 2); //[2018][02][18]105347
				$h=substr($oldcode, 8, 2); //[2018][02][18][10]5347
				$mi=substr($oldcode, 10, 2); //[2018][02][18][10][53]47
				$s=substr($oldcode, 12, 2); //[2018][02][18][10][53][47]
				$stringdate="$y-$m-$d $h:$mi:$s";

				$now = new DateTime;
		    $ago = new DateTime($stringdate);
		    $diff = $now->diff($ago);

		    $diff->w = floor($diff->d / 7);
		    $diff->d -= $diff->w * 7;

		    $string = array(
		        'y' => 'year',
		        'm' => 'month',
		        'w' => 'week',
		        'd' => 'day',
		        'h' => 'hour',
		        'i' => 'minute',
		        's' => 'second',
		    );
		    foreach ($string as $k => &$v) {
		        if ($diff->$k) {
		            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		        } else {
		            unset($string[$k]);
		        }
		    }

				if (isset($full)){
					if ($full=="true"){
						$string = array_slice($string, 0, 1);
					}
				}
		    return $string ? implode(', ', $string) . ' ago' : 'just now';
			}

			//-------------------------------------------------------------- covert_unix_timestamp
			if ($func=="covert_unix_timestamp"){
				return date('YmdHis',$code);
			}

			//-------------------------------------------------------------- TIMESTAMP
			if ($func=="timestamp"){
				if ($code==""){
					return date('YmdHis');
				}else{
					return date('YmdHis',time()+intval($code));
				}
			}

			//-------------------------------------------------------------- NUMBER_ROUND
			if ($func=="number_round"){
				return round($code);
			}

			//-------------------------------------------------------------- string_brokenhtml
			if ($func=="string_brokenhtml"){
				$doc = new DOMDocument();
				@$doc->loadHTML(mb_convert_encoding($code, 'HTML-ENTITIES', 'UTF-8'));
				return $doc->saveHTML();
			}

			//-------------------------------------------------------------- STRING_CLEAN
			if ($func=="string_clean"){
				$search = array(
		    	'@<script[^>]*?>.*?</script>@si',   // Strip out javascript
		    	'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
		    	'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
		    	'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
		 		);
				$code = str_replace('×', 'x', $code);
		    $output = preg_replace($search, '', $code);

				//delete sys functions from string
				if (checkpreg("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$output)==true){ //--Check if function
					preg_match_all("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$output, $got);
					foreach ($got[0] as $func){
						$output = str_replace("f.".$func."", "", $output);
					}
				}
				if (checkpreg("|s\.([A-Za-z0-9_\-]*)\((.*)\)|i",$output)==true){ //--Check if function
					preg_match_all("|s\.([A-Za-z0-9_\-]*)\((.*)\)|i",$output, $got);
					foreach ($got[0] as $func){
						$output = str_replace("s.".$func."", "", $output);
					}
				}

				if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)gv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output)==true){
					$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)gv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output);
					$va=ss_code_variables_get("global",$var,$raw,$sandbox);
					if ($va!==false){
						$output = str_replace("gv.".$var."", "", $output);
					}
				}

				if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)pv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output)==true){
					$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)pv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output);
					$va=ss_code_variables_get("post",$var,$raw,$sandbox);
					if ($va!==false){
						$output = str_replace("pv.".$var."", "", $output);
					}
				}

				if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)uv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output)==true){
					$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)uv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output);
					$va=ss_code_variables_get("url",$var,$raw,$sandbox);
					if ($va!==false){
						$output = str_replace("uv.".$var."", "", $output);
					}
				}

				if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)sv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output)==true){
					$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)sv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output);
					$va=ss_code_variables_get("session",$var,$raw,$sandbox);
					if ($va!==false){
						$output = str_replace("sv.".$var."", "", $output);
					}
				}

				if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)cv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output)==true){
					$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)cv\.([A-Za-z0-9\.\[\]_\-]*)/i",$output);
					$va=ss_code_variables_get("cookie",$var,$raw,$sandbox);
					if ($va!==false){
						$output = str_replace("cv.".$var."", "", $output);
					}
				}

				if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$output)==true){
					$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$output);
					$va=ss_code_variables_get($id,$var,$raw,$sandbox);
					if ($va!==false){
						$output = str_replace("v.".$var."", "", $output);
					}
				}

				$output=makesafe($output);
		    return $output;
			}

			//-------------------------------------------------------------- STRING_URL
			if ($func=="string_url"){
				if (filter_var($code, FILTER_VALIDATE_URL)) {
		    	return true;
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- STRING_SPLIT_ARRAY
			if ($func=="string_split_array"){
				$datasend=explode($code_part[0], $code_part[1]);
				$obj=strtolower(codegenerate(50));
				convert_phparray_ssarray($id,$datasend,$obj);
				return "v.".$obj."";
			}

			//-------------------------------------------------------------- STRING_SPLITLINES_ARRAY
			if ($func=="string_splitlines_array"){
				$datasend= preg_split('/\r\n|\r|\n/', $code);
				$obj=strtolower(codegenerate(50));
				convert_phparray_ssarray($id,$datasend,$obj);
				return "v.".$obj."";
			}

			//-------------------------------------------------------------- string_email_domain
			if ($func=="string_email_domain"){
				$stringuse=substr($code, strpos($code, "@") + 1);
				return $stringuse;
			}

			//-------------------------------------------------------------- STRING_CONTAINS
			if ($func=="string_contains"){
				if (strpos($code_part[1], $code_part[0]) !== false){
					return true;
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- string_split_sentence
			if ($func=="string_split_sentence"){
				return preg_replace('/(.*?[?!.](?=\s|$)).*/', '\\1', preg_replace( '/\s+/', ' ', $code));
			}

			//-------------------------------------------------------------- SFTP_CONNECT
			if ($func=="sftp_connect"){

				//get the server we are connecting to
				if (isset($code_part[3])){
					if ($code_part[3]!=""){
						$sftp_table=$code_part[3];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp_connections["".$sftp_table.""] = ssh2_connect($code_part[0],22);
				if ($sftp_connections["".$sftp_table.""]==false){
					log_error('ftp_connect failed');
					return "false";
				}else{
					if (ssh2_auth_password($sftp_connections["".$sftp_table.""], $code_part[1], $code_part[2])) {
						return "true";
					}else{
						log_error('ftp_login failed');
						return "false";
					}
				}
			}

			//-------------------------------------------------------------- SFTP_CLOSE
			if ($func=="sftp_close"){
				//get the server we are connecting to
				if (isset($code_part[0])){
					if ($code_part[0]!=""){
						$sftp_table=$code_part[0];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				ssh2_disconnect($sftp_connections["".$sftp_table.""]);

				return "true";
			}

			//-------------------------------------------------------------- SFTP_LIST
			if ($func=="sftp_list"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$files=scandir("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."");

				if ($files!=false){
					$obj=strtolower(codegenerate(50));
					convert_phparray_ssarray($id,$files,$obj);
					return "v.".$obj."";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- SFTP_LIST_opendir
			if ($func=="sftp_list_opendir"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$dh=opendir("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."");
				$files=[];
				while (false !== ($filename = readdir($dh))) {
					$files[] = $filename;
				}

				sort($files);


				if ($files!=false){
					$obj=strtolower(codegenerate(50));
					convert_phparray_ssarray($id,$files,$obj);
					return "v.".$obj."";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- SFTP_FILE_EXISTS
			if ($func=="sftp_file_exists"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				//$filesize = filesize("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."");
				$filesize = ssh2_sftp_stat($sftp, "".$sftp_path."".$code_part[0]."");

				if ($filesize>=1){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- SFTP_FILE_EXISTS
			if ($func=="sftp_file_info"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				//$filesize = filesize("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."");
				$stat = ssh2_sftp_stat($sftp, "".$sftp_path."".$code_part[0]."");

				$refdata=array();
				$refdata["filename"]=basename($code_part[0]);
				$refdata["filesize"]=$stat['size'];
				$refdata["group"]=$stat['gid'];
				$refdata["owner"]=$stat['uid'];
				$refdata["atime"]=$stat['atime'];
				$refdata["mtime"]=$stat['mtime'];
				$refdata["mode"]=$stat['mode'];

				$obj=strtolower(codegenerate(50));
				convert_phparray_ssarray($id,$refdata,$obj);
				return "v.".$obj."";

			}


			//-------------------------------------------------------------- sftp_file_contents
			if ($func=="sftp_file_contents"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$contents = file_get_contents("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."");

				return $contents;
			}

			//-------------------------------------------------------------- sftp_file_type
			if ($func=="sftp_file_type"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$contents = mime_content_type("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."");

				return $contents;
			}

			//-------------------------------------------------------------- sftp_file_send
			if ($func=="sftp_file_send"){
				//get the server we are connecting to
				if (isset($code_part[2])){
					if ($code_part[2]!=""){
						$sftp_table=$code_part[2];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$content=file_get_contents($system["runpath"].$code_part[0]);
				$myfile = fopen("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[1]."", "w");
				fwrite($myfile, $content);
				fclose($myfile);

				if ($myfile!=false){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- sftp_file_delete
			if ($func=="sftp_file_delete"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$delete=ssh2_sftp_unlink($sftp,"".$sftp_path."".$code_part[0]."");
				if ($delete==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- sftp_folder_create
			if ($func=="sftp_folder_create"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$delete=ssh2_sftp_mkdir($sftp,"".$sftp_path."".$code_part[0]."");
				if ($delete==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- sftp_folder_delete
			if ($func=="sftp_folder_delete"){
				//get the server we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$sftp_table=$code_part[1];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$delete=ssh2_sftp_rmdir($sftp,"".$sftp_path."".$code_part[0]."");
				if ($delete==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- sftp_chmod
			if ($func=="sftp_chmod"){
				//get the server we are connecting to
				if (isset($code_part[2])){
					if ($code_part[2]!=""){
						$sftp_table=$code_part[2];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$delete=ssh2_sftp_chmod($sftp,"".$sftp_path."".$code_part[0]."",$code_part[1]);
				if ($delete==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- sftp_chmod
			if ($func=="sftp_rename"){
				//get the server we are connecting to
				if (isset($code_part[2])){
					if ($code_part[2]!=""){
						$sftp_table=$code_part[2];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				$delete=ssh2_sftp_rename($sftp,"".$sftp_path."".$code_part[0]."","".$sftp_path."".$code_part[1]."");
				if ($delete==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- sftp_chmod
			if ($func=="sftp_zip"){
				//get the server we are connecting to
				if (isset($code_part[2])){
					if ($code_part[2]!=""){
						$sftp_table=$code_part[2];
					}else{
						$sftp_table="default";
					}
				}else{
					$sftp_table="default";
				}
				if ($sandbox==true){
					$sftp_table="sandbox_sftp_".$id."";
				}

				$sftp = ssh2_sftp($sftp_connections["".$sftp_table.""]);
				$sftp_fd = intval($sftp);
				$sftp_path=ssh2_sftp_realpath($sftp,".");

				// Create new zip class
				$zip = new ZipArchive;
				$zipcreated="ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[1]."/".basename($code_part[0]).".zip";
				if ($zip -> open($zipcreated, ZipArchive::CREATE ) === TRUE) {

				    // Store the path into the variable
				    $dir = opendir("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."");

				    while($file = readdir($dir)) {
				        if(is_file("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."/".$file)) {
				            $zip -> addFile("ssh2.sftp://$sftp_fd".$sftp_path."".$code_part[0]."/".$file, $file);
				        }
				    }

					closedir($dir);
				    $zip ->close();
				}
			}

			//-------------------------------------------------------------- MYSQL_CONNECT
			if ($func=="mysql_connect"){
				//get the database we are connecting to
				if (isset($code_part[4])){
					if ($code_part[4]!=""){
						$mysql_table=$code_part[4];
					}else{
						$mysql_table="default";
					}
				}else{
					$mysql_table="default";
				}
				if ($sandbox==true){
					$mysql_table="sandbox_db_tableonly_".$id."";
				}

				//--Server - Username - Password - Database
				//$mysql_connections["".$mysql_table.""] = new mysqli($code_part[0], $code_part[1], $code_part[2], $code_part[3]);
				//Moved to using a connection timeout

				$mysql_connections["".$mysql_table.""] = mysqli_init();
				if (!$mysql_connections["".$mysql_table.""]){
				    log_error('mysqli_init failed');
						return "false";
				}

				if (!$mysql_connections["".$mysql_table.""]->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3)) {
				    log_error('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
						return "false";
				}

				//Connect to database now
				$mysql_connections["".$mysql_table.""]->real_connect($code_part[0], $code_part[1], $code_part[2], $code_part[3]);

				//mysqli_set_charset($mysql_connections["".$mysql_table.""],"utf8mb4");
				$mysql_connections["".$mysql_table.""]->set_charset('utf8');

				if ($mysql_connections["".$mysql_table.""]->connect_error) {
					log_error("MYSQL CONNECT Error: ".$mysql_connections["".$mysql_table.""]->connect_error."", 0);
					return "false";
				}else{
					$mysql_databases["".$mysql_table.""]=true;
					return "true";
				}
			}

			//-------------------------------------------------------------- MYSQL_CLOSE
			if ($func=="mysql_close"){
				//get the database we are connecting to
				if (isset($code_part[0])){
					if ($code_part[0]!=""){
						$mysql_table=$code_part[0];
					}else{
						$mysql_table="default";
					}
				}else{
					$mysql_table="default";
				}
				if ($sandbox==true){
					$mysql_table="sandbox_db_tableonly_".$id."";
				}

				//check if this database is connected, if not return false
				if ($mysql_databases["".$mysql_table.""]==true){
					$mysql_connections["".$mysql_table.""]->close();
					$mysql_databases["".$mysql_table.""]=false;
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- MYSQL_LASTID
			if ($func=="mysql_lastid"){
				//get the database we are connecting to
				if (isset($code_part[0])){
					if ($code_part[0]!=""){
						$mysql_table=$code_part[0];
					}else{
						$mysql_table="default";
					}
				}else{
					$mysql_table="default";
				}
				if ($sandbox==true){
					$mysql_table="sandbox_db_tableonly_".$id."";
				}

				//check if this database is connected, if not return false
				if ($mysql_databases["".$mysql_table.""]==true){
					return $mysql_connections["".$mysql_table.""]->insert_id;
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- MYSQL_INSERT
			if ($func=="mysql_insert"){
				//get the database we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$mysql_table=$code_part[1];
					}else{
						$mysql_table="default";
					}
				}else{
					$mysql_table="default";
				}
				if ($sandbox==true){
					$mysql_table="sandbox_db_tableonly_".$id."";
				}

				//check if this database is connected, if not return false
				if ($mysql_databases["".$mysql_table.""]==true){
					if ($mysql_connections["".$mysql_table.""]->query($code_part[0]) === TRUE) {
						return $mysql_connections["".$mysql_table.""]->insert_id;
					}else{
						log_error("MYSQL INSERT Error: On table $mysql_table got error '".$mysql_connections["".$mysql_table.""]->error."' | ".$code_part[0]."", 0);
						return "false";
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- MYSQL_UPDATE
			if ($func=="mysql_update"){
				//get the database we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$mysql_table=$code_part[1];
					}else{
						$mysql_table="default";
					}
				}else{
					$mysql_table="default";
				}
				if ($sandbox==true){
					$mysql_table="sandbox_db_tableonly_".$id."";
				}

				//check if this database is connected, if not return false
				if ($mysql_databases["".$mysql_table.""]==true){
					if ($mysql_connections["".$mysql_table.""]->query($code_part[0]) === TRUE) {
						return $mysql_connections["".$mysql_table.""]->insert_id;
					}else{
						if (isset($mysql_connections["".$mysql_table.""]->error)){
							log_error("MYSQL UPDATE Error: ".$mysql->error." | ".$code_part[0]."", 0);
							return "false";
						}else{
							return "true";
						}
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- MYSQL_DELETE
			if ($func=="mysql_delete"){
				//get the database we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$mysql_table=$code_part[1];
					}else{
						$mysql_table="default";
					}
				}else{
					$mysql_table="default";
				}
				if ($sandbox==true){
					$mysql_table="sandbox_db_tableonly_".$id."";
				}

				//check if this database is connected, if not return false
				if ($mysql_databases["".$mysql_table.""]==true){
					if ($mysql_connections["".$mysql_table.""]->query($code_part[0]) === TRUE) {
						return "true";
					}else{
						log_error("MYSQL DELETE Error: ".$mysql->error." | ".$code_part[0]."", 0);
						return "false";
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- MYSQL_SELECT
			if ($func=="mysql_select"){
				//get the database we are connecting to
				if (isset($code_part[1])){
					if ($code_part[1]!=""){
						$mysql_table=$code_part[1];
					}else{
						$mysql_table="default";
					}
				}else{
					$mysql_table="default";
				}
				if ($sandbox==true){
					$mysql_table="sandbox_db_tableonly_".$id."";
				}

				//check if this database is connected, if not return false
				if ($mysql_databases["".$mysql_table.""]==true){
					$result = $mysql_connections["".$mysql_table.""]->query($code_part[0]);
					if ($result->num_rows > 0){
						if ($result->num_rows == 1){
							$array=$result->fetch_assoc();
						}else{
							while ($row = $result->fetch_assoc()) {
								$array[]=$row;
							}
						}
						//$array=utf8ize($array);
						$obj=strtolower(codegenerate(50));
						convert_phparray_ssarray($id,$array,$obj);
						return "v.".$obj."";
					}else{
						return "false";
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- string_utf8_encode
			if ($func=="string_utf8_encode"){
				$valuex=ss_code_variables_string_value($id,$code_raw,true,false,$sandbox);
				$encoded=iconv(mb_detect_encoding($valuex, mb_detect_order(), true), "UTF-8", $valuex);
				if ($encoded==false){
					$encoded=mb_convert_encoding($valuex, "UTF-8");
				}

				return $encoded;
			}

			//-------------------------------------------------------------- JSON_ENCODE - usage s.json_encode(arrayvar)
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.json_encode.md
			if ($func=="json_encode"){
				$array2=ss_code_variables_string_value($id,$code_raw,true,false,$sandbox);
				if ($array2!=false){
					if (is_array($array2)){
						//return json_encode(utf8ize($array2), JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);
						return json_encode($array2, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);
					}else{
						return "false";
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- JSON_DECODE - usage s.json_decode(jsonstring)
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.json_decode.md
			if ($func=="json_decode"){
				$obj=strtolower(codegenerate(50));
				$json_obj=json_decode($code, true);
				if (is_array($json_obj)){
					convert_phparray_ssarray($id,$json_obj,$obj);
					return "v.".$obj."";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- JSON_DECODE
			if ($func=="json_decode_reverse"){
				$obj=strtolower(codegenerate(50));
				$json_obj=json_decode($code, true);
				$json_obj=array_reverse($json_obj);
				if (is_array($json_obj)){
					convert_phparray_ssarray($id,$json_obj,$obj);
					return "v.".$obj."";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- STRING_BASE64_ENCODE
			if ($func=="string_base64_encode" OR $func=="base64_encode"){
				$base64 = base64_encode($code);
				$base64url = strtr($base64, '+/=', '-_,');
				return $base64url;
			}

			//-------------------------------------------------------------- STRING_BASE64_ENCODE_raw
			if ($func=="string_base64_encode_raw" OR $func=="base64_encode_raw"){
				$code_got=ss_code_variables_string_replace($id,$code_raw,false,$sandbox);
				$base64 = base64_encode($code_got);
				$base64url = strtr($base64, '+/=', '-_,');
				return $base64url;
			}

			//-------------------------------------------------------------- STRING_BASE64_DECODE
			if ($func=="string_base64_decode" OR $func=="base64_decode"){
				$base64url = strtr($code, '-_,', '+/=');
				$base64 = base64_decode($base64url);
				return $base64;
			}

			//-------------------------------------------------------------- STRING_URL_ENCODE
			if ($func=="string_url_encode"){
				return urlencode($code);
			}

			//-------------------------------------------------------------- STRING_URL_DECODE
			if ($func=="string_url_decode"){
				return urldecode($code);
			}

			//-------------------------------------------------------------- GET_IP
			if ($func=="get_ip" && $sandbox==false){
				return $system["ip"];
			}

			//-------------------------------------------------------------- GET_IP
			if ($func=="get_ip_forwarded" && $sandbox==false){
				return $_SERVER['X-Forwarded-For'];
			}

			//-------------------------------------------------------------- GET_IP
			if ($func=="get_ip_cf" && $sandbox==false){
				return $_SERVER['HTTP_CF_CONNECTING_IP'];
			}

			//-------------------------------------------------------------- GET_IP
			if ($func=="get_ip_cf_ipv4" && $sandbox==false){
				return $_SERVER['Cf-Connecting-IP'];
			}

			//-------------------------------------------------------------- GET_IP
			if ($func=="get_ip_cf_ipv6" && $sandbox==false){
				return $_SERVER['Cf-Connecting-IPv6'];
			}

			//-------------------------------------------------------------- format_phonenumber
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.format_phonenumber.md
			if ($func=="format_phonenumber"){
				$phone = preg_replace("/[^0-9]/", "", $code);
 				if(strlen($phone) == 7){
					return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
				}else{
					if(strlen($phone) == 10){
						return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
					}else{
						return $phone;
					}
				}
			}

			//-------------------------------------------------------------- NUMBER_ORDINAL
			if ($func=="number_ordinal"){
				$test_c = abs($code) % 10;
		    $ext = ((abs($code) %100 < 21 && abs($code) %100 > 4) ? 'th'
		            : (($test_c < 4) ? ($test_c < 3) ? ($test_c < 2) ? ($test_c < 1)
		            ? 'th' : 'st' : 'nd' : 'rd' : 'th'));
		    return $code.$ext;
			}

			//-------------------------------------------------------------- PASSWORD_HASH
			if ($func=="password_hash"){
				$crypt_options = array(
					'cost' => 5
				);
				return password_hash($code, PASSWORD_BCRYPT, $crypt_options);
			}

			//-------------------------------------------------------------- CHECK_VALID_EMAIL
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.check_valid_email.md
			if ($func=="check_valid_email"){
				if (filter_var($code, FILTER_VALIDATE_EMAIL)) {
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- CHECK_VALID_URL
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.check_valid_url.md
			if ($func=="check_valid_url"){
				$var=filter_var($code, FILTER_VALIDATE_URL);
				if ($var==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- CHECK_VALID_DOMAIN
			if ($func=="check_valid_domain"){
    		$validgo=(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $code) //valid chars check
      	&& preg_match("/^.{1,253}$/", $code) //overall length check
        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $code)); //length of each label

				if ($validgo==true){
					return "true";
				}else{
					//check with out * wildcard
					$codecheckwild=str_replace("*.","",$code);
					$validgoagain=(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $codecheckwild) //valid chars check
	        && preg_match("/^.{1,253}$/", $codecheckwild) //overall length check
	        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $codecheckwild)); //length of each label
					if ($validgoagain==true){
						return "true";
					}else{
						return "false";
					}
				}
			}

			//-------------------------------------------------------------- CHECK_VALID_DOMAIN_NONLOCAL
			if ($func=="check_valid_domain_nonlocal"){
    		$validgo=(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $code) //valid chars check
      	&& preg_match("/^.{1,253}$/", $code) //overall length check
        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $code)); //length of each label

				if ($validgo==true){
					if (strpos($code, ".") !== false) {
						return "true";
					}else{
						return "false";
					}
				}else{
					//check with out * wildcard
					$codecheckwild=str_replace("*.","",$code);
					$validgoagain=(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $codecheckwild) //valid chars check
	        && preg_match("/^.{1,253}$/", $codecheckwild) //overall length check
	        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $codecheckwild)); //length of each label
					if ($validgoagain==true){
						if (strpos($code, ".") !== false) {
							return "true";
						}else{
							return "false";
						}
					}else{
						return "false";
					}
				}
			}

			//-------------------------------------------------------------- CHECK_VALID_NUMBER
			if ($func=="check_valid_number"){
				if (is_numeric($code)==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- CHECK_VALID_IPV4
			if ($func=="check_valid_ipv4"){
    		if (filter_var($code, FILTER_VALIDATE_IP)) {
					return "true";
				}else{
					return "false";
				}
			}


			//-------------------------------------------------------------- CHECK_VALID_IPV6
			if ($func=="check_valid_ipv6"){
				if (filter_var($code, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- CHECK_VALID_SSL
			//https://discuss.codewithss.org/d/85-s-check-valid-ssl-checks-if-a-domain-has-a-valid-ssl-certificate
			if ($func=="check_valid_ssl"){
				$res = false;
				$orignal_parse = $code;
				$stream = @stream_context_create( array( 'ssl' => array( 'capture_peer_cert' => true ) ) );
				$socket = @stream_socket_client( 'ssl://' . $orignal_parse . ':443', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $stream );

				// If we got a ssl certificate we check here, if the certificate domain
				// matches the website domain.
				if ( $socket ){
					$cont = stream_context_get_params( $socket );
					$cert_ressource = $cont['options']['ssl']['peer_certificate'];
					$cert = openssl_x509_parse( $cert_ressource );
					$listdomains=explode(',', $cert["extensions"]["subjectAltName"]);

					foreach ($listdomains as $v) {
						if (strpos($v, $orignal_parse) !== false) {
							$res=true;
						}
					}
				}
				if ($res==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- CHECK_EXPIRED_SSL
			//https://discuss.codewithss.org/d/86-s-check-expired-ssl-checks-if-the-ssl-certificate-by-a-domain-is-expired
			if ($func=="check_expired_ssl"){
		    $res = false;
				$orignal_parse = $code;
				$get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
				$read = stream_socket_client("ssl://".$orignal_parse.":443", $errno, $errstr,
				30, STREAM_CLIENT_CONNECT, $get);
				$cert = stream_context_get_params($read);
				$certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
				if ($certinfo["validTo_time_t"]<=time()){
					$res=true;
				}
				if ($certinfo["validTo_time_t"]>=time()){
					$res=false;
				}
				if ($res==true){
		    	return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- PASSWORD_VERIFY
			if ($func=="password_verify"){
				$var=password_verify($code_part[0], $code_part[1]);
				if ($var==true){
					return "true";
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- PING
			if ($func=="ping"){
				if (!isset($code_part[1])){
					$port=80;
				}else{
					$port=$code_part[1];
				}
		    $starttime = microtime(true);
		    $file      = fsockopen ($code_part[0], $port, $errno, $errstr, 1);
		    $stoptime  = microtime(true);
		    $status    = "false";

		    if (!$file){
					$status = "false";
				}else{
	        fclose($file);
	        $status = ($stoptime - $starttime) * 1000;
	        $status = floor($status);
		    }
		    return $status;
			}

			//-------------------------------------------------------------- STRING_LENGTH
			if ($func=="string_length"){
				return strlen($code);
			}

			//-------------------------------------------------------------- STRING_WORD_COUNT
			if ($func=="string_word_count"){
				return str_word_count($code);
			}

			//-------------------------------------------------------------- STRING_INVERT
			if ($func=="string_invert"){
				return strrev($code);
			}

			//-------------------------------------------------------------- STRING_WORD_UPPERCASE
			if ($func=="string_word_uppercase"){
				return ucwords($code);
			}

			//-------------------------------------------------------------- string_first_word_uppercase
			if ($func=="string_first_word_uppercase"){
				return ucfirst($code);
			}

			//-------------------------------------------------------------- STRING_UPPERCASE
			if ($func=="string_uppercase"){
				return strtoupper($code);
			}

			//-------------------------------------------------------------- STRING_LOWERCASE
			if ($func=="string_lowercase"){
				return strtolower($code);
			}

			//-------------------------------------------------------------- STRING_REPLACE
			if ($func=="string_replace"){
				return str_replace($code_part[0],$code_part[1],$code_part[2]);
			}

			//-------------------------------------------------------------- STRING_TRIM
			if ($func=="string_trim"){
				return substr($code_part[0],0,$code_part[1]);
			}

			//-------------------------------------------------------------- STRING_TRIM
			if ($func=="string_trim_utf8"){
				return mb_substr($code_part[0],0,$code_part[1], "utf-8");
			}

			//-------------------------------------------------------------- STRING_FullTRIM
			if ($func=="string_fulltrim"){
				return trim($code_part[0],$code_part[1]);
			}

			//-------------------------------------------------------------- STRING_TRIM_CHARACTER
			if ($func=="string_trim_character"){
				return rtrim($code_part[0],$code_part[1]);
			}

			//-------------------------------------------------------------- string_replace_pattern
			if ($func=="string_replace_pattern"){
				return preg_replace("/".$code_part[0]."/",$code_part[1],$code_part[2]);
			}

			//-------------------------------------------------------------- STRING_USERNAME
			if ($func=="string_username"){
				return preg_replace("/[^0-9a-z-_]/", "", $code);
			}

			//-------------------------------------------------------------- STRING_REPEAT
			if ($func=="string_repeat"){
				return str_repeat($code_part[0],0,$code_part[1]);
			}

			//-------------------------------------------------------------- NUMBER_FORMAT
			if ($func=="number_format"){
				return number_format($code);
			}

			//-------------------------------------------------------------- FILE_UPLOADED
			if ($func=="file_uploaded" && $sandbox==false){
				if ($system["uploaded_file"]!=false){
					return true;
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_UPLOADED_NAME
			if ($func=="file_uploaded_name" && $sandbox==false){
				if ($system["uploaded_file"]!=false){
					return $system["uploaded_file"]['name'];
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_UPLOADED_SIZE
			if ($func=="file_uploaded_size" && $sandbox==false){
				if ($system["uploaded_file"]!=false){
					return $system["uploaded_file"]['size'];
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_UPLOADED_TYPE
			if ($func=="file_uploaded_type" && $sandbox==false){
				if ($system["uploaded_file"]!=false){
					return $system["uploaded_file"]['type'];
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_UPLOADED_IMAGE
			if ($func=="file_uploaded_image" && $sandbox==false){
				if ($system["uploaded_file"]!=false){
					$realimage=false;
					if (filesize($system["uploaded_file"]['tmp_name']) > 11){
						if (exif_imagetype($system["uploaded_file"]['tmp_name'])==IMAGETYPE_GIF){ $realimage=true; }
						if (exif_imagetype($system["uploaded_file"]['tmp_name'])==IMAGETYPE_JPEG){ $realimage=true; }
						if (exif_imagetype($system["uploaded_file"]['tmp_name'])==IMAGETYPE_PNG){ $realimage=true; }
					}
					if ($realimage==false){
						return "false";
					}else{
						return "true";
					}
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_UPLOADED_SAVE
			if ($func=="file_uploaded_save" && $sandbox==false){
				if ($system["uploaded_file"]!=false){
					if (move_uploaded_file($system["uploaded_file"]['tmp_name'],$system["runpath"].$code)){
						chmod($system["runpath"].$code, 0777);
						return true;
					}else{
						return false;
					}
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_UPLOADED_SAVE
			if ($func=="file_image_resize_square" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					$target_path=$system["runpath"].$code_part[0];
					if (isset($code_part[2])){
						if ($code_part[2]!=""){
							$save_path=$system["runpath"].$code_part[2];
						}else{
							$save_path=$target_path;
						}
					}else{
						$save_path=$target_path;
					}
					$realimage=false;
					$imagetype="jpeg";
					if (filesize($target_path) > 11){ //--Must check as images lower then 11 bytes dont give image type data
						if (exif_imagetype($target_path)==IMAGETYPE_GIF){
							$imagetype="gif";
							$im=imagecreatefromgif($target_path);
							$realimage=true;
						}
						if (exif_imagetype($target_path)==IMAGETYPE_JPEG){
							$imagetype="jpeg";
							$im=imagecreatefromjpeg($target_path);
							$realimage=true;
						}
						if (exif_imagetype($target_path)==IMAGETYPE_PNG){
							$imagetype="png";
							$im=imagecreatefrompng($target_path);
							$realimage=true;
						}
					}
					if ($realimage==true){
						//get image and convert
						list($width, $height)=getimagesize($target_path);

						// calculating the part of the image to use for thumbnail
						if ($width > $height) {
							$y = 0;
							$x = ($width - $height) / 2;
							$smallestSide = $height;
						} else {
							$x = 0;
							$y = ($height - $width) / 2;
							$smallestSide = $width;
						}
						// copying the part into thumbnail
						$thumbSize = intval($code_part[1]);
						$thumb = imagecreatetruecolor($thumbSize, $thumbSize);
						$backgroundColor=imagecolorallocate($thumb, 255, 255, 255); imagefill($thumb, 0, 0, $backgroundColor);
						imagecopyresampled($thumb, $im, 0, 0, $x, $y, $thumbSize, $thumbSize, $smallestSide, $smallestSide);

						//delete old file first
						if (unlink($save_path)){

							// save image to disk
							if ($imagetype=="jpeg"){
								imagejpeg($thumb, $save_path, 95);
							}
							if ($imagetype=="png"){
								imagepng($thumb, $save_path);
							}
							if ($imagetype=="gif"){
								imagegif($thumb, $save_path);
							}

							imagedestroy($thumb);
							imagedestroy($tn);
							imagedestroy($im);
							return true;
						}else{
							imagedestroy($thumb);
							imagedestroy($tn);
							imagedestroy($im);
							return false;
						}
					}else{
						return false;
					}
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_UPLOADED_SAVE
			if ($func=="file_image_resize" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					$target_path=$system["runpath"].$code_part[0];
					if (isset($code_part[2])){
						if ($code_part[2]!=""){
							$save_path=$system["runpath"].$code_part[2];
						}else{
							$save_path=$target_path;
						}
					}else{
						$save_path=$target_path;
					}
					$realimage=false;
					$imagetype="jpeg";
					if (filesize($target_path) > 11){ //--Must check as images lower then 11 bytes dont give image type data
						if (exif_imagetype($target_path)==IMAGETYPE_GIF){
							$imagetype="gif";
							$im=imagecreatefromgif($target_path);
							$realimage=true;
						}
						if (exif_imagetype($target_path)==IMAGETYPE_JPEG){
							$imagetype="jpeg";
							$im=imagecreatefromjpeg($target_path);
							$realimage=true;
						}
						if (exif_imagetype($target_path)==IMAGETYPE_PNG){
							$imagetype="png";
							$im=imagecreatefrompng($target_path);
							$realimage=true;
						}
					}
					if ($realimage==true){
						//get image and convert
						list($width, $height)=getimagesize($target_path);

						$maxsize=$code_part[1];

						// calculating the part of the image to use
						if ($width > $maxsize){ $newwidth=$maxsize; $math=$width/$maxsize; $newheight=$height/$math; }else{ $newwidth=$width; $newheight=$height; }
						if ($newheight > $maxsize){ $newheightr=$maxsize; $math=$newheight/$newheightr; $newheight=$newheightr; $newwidth=$newwidth/$math; }

						// scaling down the image
						$thumbSize = intval($code_part[1]);
						$thumb = imagecreatetruecolor($newwidth, $newheight);
						$backgroundColor=imagecolorallocate($thumb, 255, 255, 255); imagefill($thumb, 0, 0, $backgroundColor);
						imagecopyresampled($thumb, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

						//delete old file first
						if (unlink($save_path)){

							// save image to disk
							if ($imagetype=="jpeg"){
								imagejpeg($thumb, $save_path, 95);
							}
							if ($imagetype=="png"){
								imagepng($thumb, $save_path);
							}
							if ($imagetype=="gif"){
								imagegif($thumb, $save_path);
							}

							imagedestroy($thumb);
							imagedestroy($tn);
							imagedestroy($im);
							return true;
						}else{
							imagedestroy($thumb);
							imagedestroy($tn);
							imagedestroy($im);
							return false;
						}
					}else{
						return false;
					}
				}else{
					return false;
				}
			}

			//-------------------------------------------------------------- FILE_COPY
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_copy.md
			if ($func=="file_copy" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						if (copy($system["runpath"].$code_part[0],$system["runpath"].$code_part[1])){
							return "true";
						}else{
							return "false";
							log_error("System Function (file_copy): unable to copy file",$t);
						}
					}else{
						log_error("System Function (file_copy): cant run file copy on a folder",$t);
						return "false";
					}
				}else{
					log_error("System Function (file_copy): cant original find file given",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_EXISTS
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_exists.md
			if ($func=="file_exists" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						return "true";
					}else{
						return "false";
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_NAME
			if ($func=="file_name" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (isset($code_part[1])){
						return basename($code_part[0], $code_part[1]);
					}else{
						return basename($code_part[0]);
					}
				}
			}

			//-------------------------------------------------------------- FILE_DELETE
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_delete.md
			if ($func=="file_delete" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						if (unlink($system["runpath"].$code_part[0])){
							return "true";
						}else{
							log_error("System Function (file_delete): unable to delete file",$t);
							return "false";
						}
					}else{
						log_error("System Function (file_delete): cant run file delete on a folder",$t);
						return "false";
					}
				}else{
					log_error("System Function (file_delete): cant find file given",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_WRITE
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_write.md
			if ($func=="file_write" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						if (file_put_contents($system["runpath"].$code_part[0], decode_makesafe_ss_input($code_part[1]))){
							return "true";
						}else{
							log_error("System Function (file_write): unable to write to the file",$t);
							return "false";
						}
					}else{
						log_error("System Function (file_write): cant write on a folder",$t);
						return "false";
					}
				}else{
					log_error("System Function (file_write): cant find file given",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_ADD
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_add.md
			if ($func=="file_add" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						if (file_put_contents($system["runpath"].$code_part[0], $code_part[1], FILE_APPEND | LOCK_EX)){
							return "true";
						}else{
							log_error("System Function (file_add): unable to add content to the file",$t);
							return "false";
						}
					}else{
						log_error("System Function (file_add): cant write on a folder",$t);
						return "false";
					}
				}else{
					log_error("System Function (file_add): cant find file given",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_CREATE
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_create.md
			if ($func=="file_create" && $sandbox==false){
				if (!file_exists($system["runpath"].$code_part[0])){
					$file = fopen($system["runpath"].$code_part[0], 'w') or log_error("System Function (file_create): file create failed",$t);
					fclose($file);
					return "true";
				}else{
					log_error("System Function (file_create): we already have a file with this name",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_READ
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_read.md
			if ($func=="file_read" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						$data=encode_makesafe_ss_input(file_get_contents($system["runpath"].$code_part[0]));
						return $data;
					}else{
						log_error("System Function (file_read): cant read folder path",$t);
						return "false";
					}
				}else{
					log_error("System Function (file_read): cant find file to read",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_READ_BASE64
			if ($func=="file_read_base64" OR $func=="file_uri"){
				if ($sandbox==false){
					if (file_exists($system["runpath"].$code_part[0])){
						if (!is_dir($system["runpath"].$code_part[0])){
							$type = mime_content_type($system["runpath"].$code_part[0]);

							if (isset($code_part[1])){
								if ($code_part[1]=="true"){
									$base64 = 'data:'.$type.';base64,'.base64_encode(file_get_contents($system["runpath"].$code_part[0]));
								}else{
									$base64 = base64_encode(file_get_contents($system["runpath"].$code_part[0]));
								}
							}else{
								$base64 = 'data:'.$type.';base64,'.base64_encode(file_get_contents($system["runpath"].$code_part[0]));
							}
							return $base64;
						}else{
							log_error("System Function (file_read): cant read folder path",$t);
							return "false";
						}
					}else{
						log_error("System Function (file_read): cant find file to read",$t);
						return "false";
					}
				}
			}

			//-------------------------------------------------------------- FILE_DOWNLOAD_BASE64
			if ($func=="file_download_base64" && $sandbox==false){
				// Get cURL resource
				$curl = curl_init();
				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true
				));
				// Send the request & save response to $resp
				$data = curl_exec($curl);
				$header=curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
				$parts = explode(";",$header);
				$type=$parts[0];
				if ($type==""){
					$type=$header;
				}
				// Close request to clear up some resources
				curl_close($curl);

				if (isset($code_part[1])){
					if ($code_part[1]=="true"){
						$base64 = 'data:'.$type.';base64,'.base64_encode($data);
					}else{
						$base64 = base64_encode($data);
					}
				}else{
					$base64 = 'data:'.$type.';base64,'.base64_encode($data);
				}
				unset($data);
				return $base64;

			}

			//-------------------------------------------------------------- FILE_DOWNLOAD
			if ($func=="file_download" && $sandbox==false){
				// Get cURL resource
				$curl = curl_init();
				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true
				));
				// Send the request & save response to $resp
				$data = curl_exec($curl);
				$header=curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
				$parts = explode(";",$header);
				$type=$parts[0];
				if ($type==""){
					$type=$header;
				}
				// Close request to clear up some resources
				curl_close($curl);

				if (!file_exists($system["runpath"].$code_part[1])){
					$file = fopen($system["runpath"].$code_part[1], 'w') or log_error("System Function (file_create): file create failed",$t);
					fclose($file);
					if (file_put_contents($system["runpath"].$code_part[1], $data, FILE_APPEND | LOCK_EX)){
						return "true";
					}else{
						log_error("System Function (file_add): unable to add content to the file",$t);
						return "false";
					}
				}else{
					log_error("System Function (file_create): we already have a file with this name",$t);
					return "false";
				}

			}

			//-------------------------------------------------------------- FILE_SIZE
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_size.md
			if ($func=="file_size" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						$data=filesize($system["runpath"].$code_part[0]);
						return $data;
					}else{
						log_error("System Function (file_size): cant read folder path",$t);
						return "false";
					}
				}else{
					log_error("System Function (file_size): cant find file to read",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FILE_RENAME
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.file_rename.md
			if ($func=="file_rename" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						if (!file_exists($system["runpath"].$code_part[1])){
							if (rename($system["runpath"].$code_part[0],$system["runpath"].$code_part[1])){
								return true;
							}else{
								log_error("System Function (folder_rename): unable to rename file",$t);
								return "false";
							}
						}else{
							log_error("System Function (folder_rename): a folder or file already has the new name",$t);
							return "false";
						}
					}else{
						log_error("System Function (folder_rename): filename given is folder not a file",$t);
						return "false";
					}
				}else{
					log_error("System Function (folder_rename): no folder found to renmae",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FOLDER_DELETE
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.folder_delete.md
			if ($func=="folder_delete" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (is_dir($system["runpath"].$code_part[0])){
						if (rmdir($system["runpath"].$code_part[0])){
							return "true";
						}else{
							log_error("System Function (folder_delete): unable to delete folder",$t);
							return "false";
						}
					}else{
						log_error("System Function (folder_delete): cant run folder delete on a file",$t);
						return "false";
					}
				}else{
					log_error("System Function (folder_delete): cant find folder given",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FOLDER_CREATE
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.folder_create.md
			if ($func=="folder_create" && $sandbox==false){
				if (!file_exists($system["runpath"].$code_part[0])){
					if (!is_dir($system["runpath"].$code_part[0])){
						if (mkdir($system["runpath"].$code_part[0])){
							return "true";
						}else{
							log_error("System Function (folder_create): unable to create folder",$t);
							return "false";
						}
					}else{
						log_error("System Function (folder_create): a folder already has this name",$t);
						return "false";
					}
				}else{
					log_error("System Function (folder_create): a file already has this name",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FOLDER_RENAME
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.folder_rename.md
			if ($func=="folder_rename" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (is_dir($system["runpath"].$code_part[0])){
						if (!file_exists($system["runpath"].$code_part[1])){
							if (rename($system["runpath"].$code_part[0],$system["runpath"].$code_part[1])){
								return "true";
							}else{
								log_error("System Function (folder_rename): unable to rename folder",$t);
								return "false";
							}
						}else{
							log_error("System Function (folder_rename): a folder or file already has the new name",$t);
							return "false";
						}
					}else{
						log_error("System Function (folder_rename): filename given is not a folder",$t);
						return "false";
					}
				}else{
					log_error("System Function (folder_rename): no folder found to renmae",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- FOLDER_EXISTS
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.folder_exists.md
			if ($func=="folder_exists" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (is_dir($system["runpath"].$code_part[0])){
						return "true";
					}else{
						return "false";
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- FOLDER_CONTENT
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.folder_content.md
			if ($func=="folder_content" && $sandbox==false){
				if (file_exists($system["runpath"].$code_part[0])){
					if (is_dir($system["runpath"].$code_part[0])){
						if ($code_part[1]=="invert"){
							$array=scandir($system["runpath"].$code_part[0],1);
						}else{
							$array=scandir($system["runpath"].$code_part[0]);
						}
						//--Clean non needed files read
						$array = array_diff($array, array('..', '.', '.DS_Store','.htaccess','.temp'));
						foreach ($array as &$value){
    					$value = $code_part[0]."/".$value;
						}
						unset($value);
						$obj=strtolower(codegenerate(50));
						convert_phparray_ssarray($id,$array,$obj);
						return "v.".$obj."";
					}else{
						return "false";
					}
				}else{
					return "false";
				}
			}

			//-------------------------------------------------------------- ARRAY
			if ($func=="array"){
				$onnow=0;
				$namepart="array";
				foreach ($code_part as $parts){
					if ($onnow==0){
						$namepart=$parts;
					}else{
						ss_code_variables_save($id,"".$namepart."[".$onnow."]",$parts,false,$sandbox);
					}
					$onnow+=1;
				}
			}

			//-------------------------------------------------------------- ARRAY_LOOP
			if ($func=="array_loop"){
				$codepart=convert_ssarray_phparray($id,$code_part[1],$sandbox);
				$return="";
				if (!is_array($codepart)){
						$codepart=array($code_part[1]);
				}
				foreach ($codepart as $parts){
					$parts=base64_encode(json_encode($parts));
					$linecode="f.".$code_part[0]."(".$parts.")";
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> Run Function S - ".$func." | ".$t." | ".$linecode.""; }
					$return.=ss_code_function_run($id,$linecode,true,$sandbox);
				}
				return $return;
			}

			//-------------------------------------------------------------- ARRAY_KEYS
			if ($func=="array_keys"){
				$codepart=convert_ssarray_phparray($id,$code,$sandbox);
				$arraykey=array_keys($codepart);
				$obj=strtolower(codegenerate(50));
				convert_phparray_ssarray($id,$arraykey,$obj);
				return "v.".$obj."";
			}

			//-------------------------------------------------------------- ARRAY_reverse
			if ($func=="array_reverse"){
				$codepart=convert_ssarray_phparray($id,$code,$sandbox);
				$arraykey=array_reverse($codepart);
				$obj=strtolower(codegenerate(50));
				convert_phparray_ssarray($id,$arraykey,$obj);
				return "v.".$obj."";
			}

			//-------------------------------------------------------------- ARRAY_COUNT
			if ($func=="array_count"){
				$codepart=convert_ssarray_phparray($id,$code,$sandbox);
				$codeammount=count($codepart);
				return $codeammount;
			}

			if ($func=="array_add"){
				$codepart=convert_ssarray_phparray($id,$code_part[0],$sandbox);
				if (!is_array($codepart)){
					$codepart=array();
				}
				array_push($codepart,$code_part[1]);
				$obj=strtolower(codegenerate(50));
				convert_phparray_ssarray($id,$codepart,$obj);
				return "v.".$obj."";
			}

			//-------------------------------------------------------------- TEMPLATE
			if ($func=="template" && $sandbox==false){
				ss_template_set($code);
			}

			//-------------------------------------------------------------- GET_USERAGENT
			if ($func=="get_useragent" && $sandbox==false){
				return $system["useragent"];
			}

			//-------------------------------------------------------------- RUN
			if ($func=="run" && $sandbox==false){
				if (file_exists($system["runpath"].$code)){
					if (!is_dir($system["runpath"].$code)){
						return ss_run_linebyline(file_get_contents($system["runpath"].$code, FILE_USE_INCLUDE_PATH));
					}else{
						log_error("System Function (run): cant run folder path",$t);
						return "false";
					}
				}else{
					log_error("System Function (run): cant find file to run",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- RUN
			if ($func=="run_sandbox_file" && $sandbox==false){
				if (file_exists($system["runpath"].$code)){
					if (!is_dir($system["runpath"].$code)){
						$response=ss_run_linebyline(file_get_contents($system["runpath"].$code, FILE_USE_INCLUDE_PATH),false,true);

						//De register any functions from sandbox for next sandbox run
						ss_code_functions_purge_sandbox();
						return $response;
					}else{
						log_error("System Function (run_sandbox): cant run folder path",$t);
						return "false";
					}
				}else{
					log_error("System Function (run_sandbox): cant find file to run",$t);
					return "false";
				}
			}

			//-------------------------------------------------------------- RUN
			if ($func=="run_sandbox_code" && $sandbox==false){
				$codesandrun=base64_decode(strtr($code, '-_,','+/='));

				$response=ss_run_linebyline($codesandrun,false,true);
				//De register any functions from sandbox for next sandbox run
				ss_code_functions_purge_sandbox();

				return $response;
			}

			//-------------------------------------------------------------- http_request_httpcode
			if ($func=="http_request_httpcode"){
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $code_part[0]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_USERAGENT, 'CodeSimpleScript SSC Script HTTP_REQUEST'); // set browser/user agent
				curl_exec( $ch );
				$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
				curl_close( $ch );
				return $response_code;
			}

			//-------------------------------------------------------------- http_request_httpcode
			if ($func=="http_request_metatags"){
				$curl = curl_init();
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true
				));
				$resp = curl_exec($curl);
				curl_close($curl);

				$outputarray=array();

				$doc = new DOMDocument();
				@$doc->loadHTML($resp);
				$nodes = $doc->getElementsByTagName('title');
				$title = $nodes->item(0)->nodeValue;
				$outputarray["title"]=$title;

				$links = $doc->getElementsByTagName('link');
				for ($i = 0; $i < $links->length; $i++){
				  $link = $links->item($i);
				  if($link->getAttribute('rel') == 'apple-touch-icon'){
				    $outputarray["apple_icon"] = $link->getAttribute('href');
					}
					if($link->getAttribute('rel') == 'icon'){
				    $outputarray["icon"] = $link->getAttribute('href');
					}
				}

				$metas = $doc->getElementsByTagName('meta');
				for ($i = 0; $i < $metas->length; $i++){
				  $meta = $metas->item($i);
				  if($meta->getAttribute('name') == 'description'){
				    $outputarray["description"] = $meta->getAttribute('content');
					}
				  if($meta->getAttribute('name') == 'keywords'){
				  	$outputarray["keywords"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('property') == 'og:type'){
				  	$outputarray["og_type"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('property') == 'og:title'){
				  	$outputarray["og_title"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('property') == 'og:image'){
				  	$outputarray["og_image"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('property') == 'og:logo'){
				  	$outputarray["og_logo"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('property') == 'og:description'){
				  	$outputarray["og_description"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('property') == 'og:site_name'){
				  	$outputarray["og_site_name"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('name') == 'twitter:site'){
				  	$outputarray["twitter_site"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('name') == 'twitter:title'){
				  	$outputarray["twitter_title"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('name') == 'twitter:description'){
				  	$outputarray["twitter_description"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('name') == 'twitter:image:src'){
				  	$outputarray["twitter_img"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('name') == 'twitter:image'){
				  	$outputarray["twitter_img"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('name') == 'twitter:card'){
				  	$outputarray["twitter_card"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('name') == 'apple-touch-icon'){
				  	$outputarray["apple_touch_icon"] = $meta->getAttribute('content');
					}
					if($meta->getAttribute('itemprop') == 'image'){
				  	$outputarray["icon"] = $meta->getAttribute('content');
					}
				}

				$obj=strtolower(codegenerate(50));
				convert_phparray_ssarray($id,$outputarray,$obj);
				return "v.".$obj."";
			}

			//-------------------------------------------------------------- HTTP_REQUEST_GET
			//DOC: https://codewithss.org/documentation?page=system-functions%2Fs.http_request_get.md
			if ($func=="http_request_get"){
				// Get cURL resource
				$curl = curl_init();
				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_CONNECTTIMEOUT => 10,
						CURLOPT_TIMEOUT => 10
				));
				// Send the request & save response to $resp
				$resp = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);
				return $resp;
			}

			//-------------------------------------------------------------- HTTP_REQUEST_POST
			if ($func=="http_request_post"){
				// Get cURL resource
				$curl = curl_init();
				$postarray=array();
				if (is_array($code_part[1])){
					$postarray=$code_part[1];
				}else{
					$postarray["ss_post"]=true;
				}

				$postdata=http_build_query($postarray);
				$postdata=str_replace("%26ssquote%3B","\"",$postdata);

				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_SSL_VERIFYPEER  => false,
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_POST => true,
						CURLOPT_CONNECTTIMEOUT => 15,
						CURLOPT_TIMEOUT => 15,
						CURLOPT_POSTFIELDS => $postdata
				));

				// Send the request & save response to $resp
				$resp = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);

				if (isset($postarray["ss_post"])){
					return "nodata";
				}else{
					if ($resp==""){
						return "noresponse";
					}else{
						return $resp;
					}
				}
			}

			//-------------------------------------------------------------- HTTP_REQUEST_POST_JSON
			if ($func=="http_request_post_json"){
				// Get cURL resource
				$curl = curl_init();
				$postarray=array();
				if (is_array($code_part[1])){
					$postarray=$code_part[1];
				}else{
					$postarray["ss_post"]=true;
				}

				$postdata = http_build_query($postarray);

				$headr = array();
				$headr[] = 'Content-Type: application/json';

				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_SSL_VERIFYPEER  => false,
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_POST => true,
						CURLOPT_CONNECTTIMEOUT => 15,
						CURLOPT_TIMEOUT => 15,
						CURLOPT_POSTFIELDS => json_encode(utf8ize($postarray), JSON_NUMERIC_CHECK | JSON_HEX_QUOT | JSON_HEX_TAG),
						CURLOPT_HTTPHEADER => $headr
				));

				// Send the request & save response to $resp
				$resp = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);

				if (isset($postarray["ss_post"])){
					return "nodata";
				}else{
					if ($resp==""){
						return "noresponse";
					}else{
						return $resp;
					}
				}
			}

			//-------------------------------------------------------------- HTTP_REQUEST_POST
			if ($func=="http_request_post_auth"){
				// Get cURL resource
				$curl = curl_init();
				$postarray=array();
				if (is_array($code_part[1])){
					$postarray=$code_part[1];
				}else{
					$postarray["ss_post"]=true;
				}

				$postdata = http_build_query($postarray);

				$headr = array();
				$headr[] = 'Authorization: '.$code_part[2];

				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_SSL_VERIFYPEER  => false,
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_POST => true,
						CURLOPT_CONNECTTIMEOUT => 15,
						CURLOPT_TIMEOUT => 15,
						CURLOPT_POSTFIELDS => $postdata,
						CURLOPT_HTTPHEADER => $headr
				));

				// Send the request & save response to $resp
				$resp = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);

				if (isset($postarray["ss_post"])){
					return "nodata";
				}else{
					if ($resp==""){
						return "noresponse";
					}else{
						return $resp;
					}
				}
			}

			//-------------------------------------------------------------- HTTP_REQUEST_POST_AUTH_JSON
			if ($func=="http_request_post_auth_json"){
				// Get cURL resource
				$curl = curl_init();
				$postarray=array();
				if (is_array($code_part[1])){
					$postarray=$code_part[1];
				}else{
					$postarray["ss_post"]=true;
				}

				$postdata = http_build_query($postarray);

				$headr = array();
				$headr[] = 'Authorization: '.$code_part[2];
				$headr[] = 'Content-Type: application/json';

				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_SSL_VERIFYPEER  => false,
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_POST => true,
						CURLOPT_CONNECTTIMEOUT => 15,
						CURLOPT_TIMEOUT => 15,
						CURLOPT_POSTFIELDS => json_encode(utf8ize($postarray), JSON_NUMERIC_CHECK | JSON_HEX_QUOT | JSON_HEX_TAG),
						CURLOPT_HTTPHEADER => $headr
				));

				// Send the request & save response to $resp
				$resp = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);

				if (isset($postarray["ss_post"])){
					return "nodata";
				}else{
					if ($resp==""){
						return "noresponse";
					}else{
						return $resp;
					}
				}
			}

			//-------------------------------------------------------------- HTTP_REQUEST_POST_FILE
			if ($func=="http_request_post_file" && $sandbox==false){
				// Get cURL resource
				$curl = curl_init();
				// Set some options - we are passing in a useragent too here
				$code_part[0] = str_replace(' ', '%20', $code_part[0]);
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $code_part[0],
						CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1 Safari/605.1.15 CSS',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => array('file' => new CURLFile($system["runpath"].$code_part[1]))
				));

				// Send the request & save response to $resp
				$resp = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);
				return $resp;
			}

			//-------------------------------------------------------------- service_sparkpost_email
			if ($func=="service_sparkpost_email"){
				// Get cURL resource
				$curl = curl_init();
				$postarray=array();

				$apikey=$code_part[0];
				$email_from=$code_part[1];
				$email_from_name=$code_part[2];
				$email_to=$code_part[3];
				$subject=$code_part[4];
				$email_text=$code_part[5];
				if (isset($code_part[6])){
					$email_html=$code_part[6];
				}else{
					$email_html=$code_part[5]."<BR><BR>This email does not have a HTML version at this time";
				}

	      $postarray["content"]["from"]["name"]=$email_from_name;
				$postarray["content"]["from"]["email"]=$email_from;
	      $postarray["content"]["subject"]=$subject;
	      $postarray["content"]["text"]=$email_text;
	      $postarray["content"]["html"]=$email_html;
	      $postarray["recipients"][0]["address"]=$email_to;

				$headr = array();
				$headr[] = 'Authorization: '.$apikey;
				$headr[] = 'Content-Type: application/json';

				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => "https://api.sparkpost.com/api/v1/transmissions",
						CURLOPT_SSL_VERIFYPEER  => false,
						CURLOPT_USERAGENT => 'CodeSimpleScript SSC Script HTTP_REQUEST',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_POST => true,
						CURLOPT_CONNECTTIMEOUT => 15,
						CURLOPT_TIMEOUT => 15,
						CURLOPT_POSTFIELDS => json_encode($postarray),
						CURLOPT_HTTPHEADER => $headr
				));

				// Send the request & save response to $resp
				$resp = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);
				if ($resp==""){
					return "noresponse";
				}else{
					return $resp;
				}
			}

			//-------------------------------------------------------------- QUIT
			if ($func=="quit" && $sandbox==false){
				shutdown(); //shutdown systme before we die
				die($code);
			}

			//######################################################################
			//Tyler's stuff
			//######################################################################

			//-------------------------------------------------------------- RANDINT
			//s.randint(min, max)
			//Generates a random integer given a range.
			if ($func == "randint" OR $func == "random_number") {
				return mt_rand($code_part[0], $code_part[1]);
			}

			//-------------------------------------------------------------- RAND
			//s.rand()
			//Generates a random float from 0 to 1
			if ($func == "rand" OR $func == "random") {
				return mt_rand()/mt_getrandmax();
			}

			//-------------------------------------------------------------- TO_CHARACTER
			//s.to_character(NUM)
			//Converts a number into a character
			if ($func == "to_character") {
				return chr($code_part[0]);
			}

			//-------------------------------------------------------------- TO_ASCII
			//s.to_ascii(character)
			//Converts a character into its ASCII equivalent in decimal
			if ($func == "to_ascii") {
				return ord($code_part[0]);
			}

			//-------------------------------------------------------------- ECHO_LINE
			//s.echo_line(text)
			//Echos a given string with a line_break
			if ($func == "echo_line") {
				return $code . "<br />";
			}
		}
	}
}

//#########################################################################################################
//######################################################################################################### - SYSTEM FUNCTIONS INPUTARRAY
//#########################################################################################################
//######################################################################################################### - Splits comma seperated parts into an array for use by muti part detect functions
//############################## - $id = ID of the function/area for variable storage
//############################## - $code = The content we are seperating

function ss_sys_function_inputarray($id,$code,$process=false,$sandbox=false){
	$code_split="{{".$code."}}";
	$code_part=array();
	$string=preg_match_all("/(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))/is",$code_split,$match); //--Match quoted areas and skipping slashed out quites matching all ex ('yay it's working')
	$temp_part=array(); //--We are storing "" matched areas for later so we dont capture string commas for replacement
	foreach ($match[0] as $part){ //--Save all quote seperated parts and save for later
		$key=codegenerate(20); //--Generate key so we can recall later
		$temp_part[$key]=trim_clean($part); //--Save with key in our temp parts array
		$code_split=str_replace($part,"{{key:".$key."}}",$code_split); //--Place temp code in our string
	}
	//--Comma split the string, and then clean it from blank areas
	//$code_split=str_replace("\",\"","(*))(*&))",$code_split); //protect comma seperated stuff
	//$code_split=str_replace("\",\"","(*))(*&))",$code_split); //protect comma seperated stuff
	$code_split=str_replace(",","}}{{",$code_split);
	$code_split=str_replace("}}}}","}}",$code_split);
	$code_split=str_replace("{{{{","{{",$code_split);
	//--Place Content back in
	preg_match_all("|\{\{key:([A-Za-z0-9]*)\}\}|i",$code_split, $match); //--Fetch each instance of a function on it's own so we dont mix them up
	foreach ($match[1] as $keycode){
		$code_split=str_replace("{{key:".$keycode."}}","{{".$temp_part[$keycode]."}}",$code_split);
	}

	//--Seperate {{}} areas into array
	preg_match_all("|\{\{([^\}]*)\}\}|i",$code_split, $match); //--Fetch each instance of a function on it's own so we dont mix them up
	$code_part_on=0;
	foreach ($match[1] as $splits){
		$value=ss_code_variables_string_replace($id,$splits,$process,$sandbox);
		$value=str_replace("\s"," ",$value);
		$code_part[$code_part_on]=decode_makesafe_ss_input($value);
		$code_part_on+=1;
	}
	return $code_part;
}

function convert_ssarray_phparray($id,$name,$sandbox){
	return ss_code_variables_string_value($id,$name,true,false,$sandbox);
}

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            unset($d[$k]);
    $d[utf8ize($k)] = utf8ize($v);
        }
    } else if (is_object($d)) {
    $objVars = get_object_vars($d);
    foreach($objVars as $key => $value) {
    $d->$key = utf8ize($value);
    }
} else if (is_string ($d)) {
        return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($d));
    }
    return $d;
}

function convert_phparray_ssarray($id,$json_obj,$obj_set){
	global $system;
	global $ss_variables;
	if (is_array($json_obj)){
		$ss_variables["".$id.""]["".$obj_set.""]=$json_obj;
		//foreach($json_obj as $i => $objcon){
		//	if (is_array($json_obj[$i])){
		//		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Backend Convert PHPARRAY To SS Array New Sub Array Found ".$obj_set."[".$i."]"; }
		//		convert_phparray_ssarray($id,$json_obj[$i],"".$obj_set."[".$i."]");
		//	}else{
		//		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Backend Convert PHPARRAY To SS Array Object Found: ".$obj_set."[".$i."] - [".$json_obj[$i]."]"; }
		//		if ($json_obj[$i]!=""){ ss_code_variables_save($id,"".$obj_set."[".$i."]",$json_obj[$i],false,$sandbox); }else{ if ($system["debug"]==true){ $system["debug_log"].="\r\n> Backend Convert PHPARRAY To SS Array Object Found: ".$obj_set."[".$i."] - [".$json_obj[$i]."] but not saved as blank"; } }
		//	}
		//}
		return true;
	}else{
		return false;
	}
}

//#########################################################################################################
//######################################################################################################### - SYSTEM FUNCTIONS PRE RUN
//#########################################################################################################
//######################################################################################################### - Pre run runs befor the line by line for things like include
//############################## - $t = The content we are checking for functions
function ss_sys_function_prerun($t){
	global $system;
	$foundsomething=false;

	if (checkpreg("|s\.include\(([^\)]*)\)|i",$t)==true){
		$code_org=fetchpreg("|s\.include\(([^\)]*)\)|i",$t);
		$code=trim_clean($code_org);
		if (file_exists($system["runpath"].$code)){
			if (!is_dir($system["runpath"].$code)){
				$data=file_get_contents($system["runpath"].$code, FILE_USE_INCLUDE_PATH);
				$t=str_replace("s.include(".$code_org.")",$data,$t);
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> Prerun Function S - s.include | ".$code.""; }
				$foundsomething=true;
			}
		}
	}

	if ($foundsomething==true){
		$t=ss_sys_function_prerun($t);
	}

	return $t;
}

?>
