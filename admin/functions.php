<?php

function data_add($value,$data){
  global $admin_datajson_updated;
  global $system_data;
  $admin_datajson_updated=true;

  $system_data["".$value.""]=$data;
}

function data_update($value,$data){
  global $admin_datajson_updated;
  global $system_data;
  $admin_datajson_updated=true;

  $system_data["".$value.""]=$data;
}

function redirectnow($url){
  header("Location: ".$url."");
  shutdown();
  die("Error 22");
}

function logincheck(){
  global $settings;
  if ($_SESSION["admin_loggedin"]==false){
    redirectnow("".$settings["admin_url"]."?page=login");
    die("Error 66");
  }
}

function canmakechange(){
  global $settings;
	$proxcode=$settings["admin_authtoken"];
	$oldtoken = !empty($_GET["authtoken"]) ? $_GET["authtoken"] : false;
	if ($oldtoken==$proxcode){
		return true;
	}else{
		return false;
	}
}

function get_contents($url, $ua='Mozilla/5.0 (Windows NT 5.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1', $referer='http://www.google.com/'){
  if (function_exists('curl_exec')){
    $header[0] = "Accept-Language: en-us,en;q=0.5";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_REFERER, $referer);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    $content = curl_exec($curl);
    curl_close($curl);
  }else{
    $options=array('http' => array('user_agent' => $ua));
    $context=stream_context_create($options);
    $content=file_get_contents($url, false, $context);
  }
  return $content;
}

function has_ssl($domain) {
	//Function that when given a domain will validate if it has a SSL certificate
	$res = false;
	$orignal_parse = $domain;
	$stream = @stream_context_create( array( 'ssl' => array( 'capture_peer_cert' => true ) ) );
	$socket = @stream_socket_client( 'ssl://' . $orignal_parse . ':443', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $stream );

	// If we got a ssl certificate we check here, if the certificate domain
	// matches the website domain.
	if ($socket){
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
	return $res;
}

?>
