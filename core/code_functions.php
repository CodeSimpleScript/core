<?php

$ss_functions=array();
$ss_functions_open=array();
$ss_functions_sandbox=array();
$ss_functions_list_sandbox=array();

function ss_code_functions_register($id,$t,$sandbox=false){
	global $system;
	global $ss_functions;
	global $ss_functions_open;
	global $ss_functions_sandbox;
	global $ss_functions_list_sandbox;

	$n="";
	$f="";
	$f_reg=false;
	$f_reg_run=false;
	$name=false;
	$array = preg_split("/\r\n|\n|\r/", $t);
	foreach ($array as $l){

		if (preg_match("|f\.([^\{]*)\s*\{|i", $l, $var)){
			$name=$var[1];
			$f_reg_run=true;
			$f_reg=true;
			if ($system["debug"]==true){ $system["debug_log"].="\r\n> Register Function Start - Found function with name ".$name.""; }
		}

		if ($f_reg==true){
			if (strpos($l, '}') !== false){
				//--register function now
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> Register Function End - End of function found, registered ".$name.""; }
				if (!isset($ss_functions["".$name.""])){
					$ss_functions["".$name.""]=removeblank($f);
					$ss_functions_open["".$name.""]=0;
					$ss_functions_sandbox["".$name.""]=$sandbox;
					if ($sandbox==true){
						array_push($ss_functions_list_sandbox,$name);
					}
				}else{
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> Register Function DUPLICATE - not able to register ".$name.""; }
				}
				$f_reg_run=true;
				$f_reg=false;
				$name=false;
				$f="";
			}
		}

		//--place new line outout
		if ($f_reg==false && $f_reg_run==false){
			$n.="\r\n".$l;
		}

		//--place new line function
		if ($f_reg==true && $f_reg_run==false){
			$f.="\r\n".$l;
		}

		$f_reg_run=false;
	}

	return $n;
}

function ss_code_functions_purge_sandbox(){
	global $system;
	global $ss_functions;
	global $ss_functions_open;
	global $ss_functions_sandbox;
	global $ss_functions_list_sandbox;

	foreach ($ss_functions_list_sandbox as $keycode){
		if (isset($keycode)){
			if ($keycode!==""){
				unset($ss_functions["".$keycode.""]);
				unset($ss_functions_open["".$keycode.""]);
				unset($ss_functions_sandbox["".$keycode.""]);
			}
		}
	}

	unset($ss_functions_list_sandbox);
	$ss_functions_list_sandbox=array();
}

function ss_code_function_run($id,$t,$encoded=false,$sandbox=false){
	global $system;
	global $settings;
	global $ss_functions;
	global $ss_functions_open;
	global $ss_functions_sandbox;

	if (checkpreg("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$t)==true){ //--Check if we have a match for s.[A-Za-z0-9_-]()
		preg_match_all("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$t, $got); //--Fetch each instance of a function on it's own so we dont mix them up
		foreach ($got[0] as $script){ //--For each found function that matches return only contained patern
			$func=fetchpreg("|f\.([A-Za-z0-9_\-]*)\(|i",$script); //--Take that patern that was returned and fetch from it the function name.
			$code=fetchpreg("|f\.".$func."\((.*)\)|i",$script); //--Take that patern that was returned and fetch from it the function content.
			if ($encoded==true){
				$code_parts[]=json_decode(base64_decode($code),true);
			}else{
				$code_parts=ss_sys_function_inputarray($id,$code,$encoded,$sandbox);
			}

			if (isset($ss_functions["".$func.""])){
				if ($ss_functions_open["".$func.""]<=$settings["settings_function_loopmax"]){
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> Run Function F - ".$func.""; }
					$allowed=true;

					//Check sandbox status
					if ($sandbox==true){

						error_log($ss_functions["".$func.""], 0);

						if ($ss_functions_sandbox["".$func.""]==true){
							$allowed=true;
						}else{
							$allowed=false;
						}
					}

					if ($allowed==true){
						$ss_functions_open["".$func.""]++;
						if ($code==""){
							$return=ss_run_linebyline($ss_functions["".$func.""],false,$sandbox,false);
						}else{
							$return=ss_run_linebyline($ss_functions["".$func.""],$code_parts,$sandbox,false);
						}
						$ss_functions_open["".$func.""]--;

						if ($sandbox==true){
							error_log($return, 0);
						}

						return $return;
					}else{
						return "nonscope";
						if ($system["debug"]==true){ $system["debug_log"].="\r\n> Run Function F - ".$func." NOT SANDBOX FUNCTION FAIL"; }
					}
				}else{
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> Run Function F - ".$func." LIMIT HIT OF (".$settings["settings_function_loopmax"].")"; }
				}
			}else{
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> Failed To Run Function F - ".$func.""; }
				return "";
			}

		}
	}
}

function ss_code_function_inputarray($id,$code,$encoded=false,$sandbox=false){
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
		if ($encoded==false){
			$code_part[$code_part_on]=ss_code_variables_string_replace($id,$splits,true,$sandbox);
		}else{
			$code_part[$code_part_on]=json_decode(base64_decode($splits),true);
		}
		$code_part_on+=1;
	}
	return $code_part;
}



?>
