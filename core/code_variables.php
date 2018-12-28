<?php

$ss_variables=array();

//--Fetch LineByLine VAR
function ss_code_variables_get($id,$var2,$raw=false,$sandbox=false){
	global $system;
	global $ss_variables;
	$var=fetchpreg("|([A-Za-z0-9-_]*)|i",$var2);
	if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Read #".$id." - ".$var2.""; }


	if (isset($ss_variables["".$id.""])){
		if (isset($ss_variables["".$id.""]["".$var.""])){
			if (checkpreg("|\[([A-Za-z0-9_\-\.]*)\]|i",$var2)==true){ //--Check if we have a match for [[A-Za-z0-9_-.]]
				$varscope=$ss_variables["".$id.""]["".$var.""];
				preg_match_all("|\[([A-Za-z0-9_\-\.]*)\]|i",$var2, $got); //--Fetch each instance of the sub items
				foreach ($got[1] as $script){
					$script=trim_clean($script);
					$value=ss_code_variables_string_value($id,$script,false,false,$sandbox);
					if (isset($varscope["".$value.""])){
						$varscope=$varscope["".$value.""];
					}else{
						return false;
					}
				}
				if (isset($varscope)){
					if (is_array($varscope)){
						if ($raw==false){
							return false;
						}else{
							return $varscope;
						}
					}else{
						return $varscope;
					}
				}else{
					return false;
				}
			}else{
				if (is_array($ss_variables["".$id.""]["".$var.""])){
					if ($raw==false){
						return false;
					}else{
						return $ss_variables["".$id.""]["".$var.""];
					}
				}else{
					return $ss_variables["".$id.""]["".$var.""];
				}
			}
		}else{
			return false;
		}
	}else{
		return false;
	}
}

//--Register LineByLine VAR
function ss_code_variables_save($id,$var2,$value,$rules=false,$sandbox=false){
	global $system;
	global $ss_variables;
	$var=fetchpreg("|([A-Za-z0-9-_]*)|i",$var2);
	$allowsave=true;

	if ($value === true || $value === false){ //--If type is TRUE or FALSE we need to replace with a true and false string or when converted will become 1/0.
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2."=".$value." saving a true and false statemet"; }
		if ($value === true){
			$value="true";
		}
		if ($value === false){
			$value="false";
		}
	}

	//--Variable rules
	if ($rules!=false){

	}

	//sandbox rules and checks
	if ($sandbox==true){
		if ($id=="global"){
			$allowsave=false;
		}
		if ($id=="post"){
			$allowsave=false;
		}
		if ($id=="url"){
			$allowsave=false;
		}
		if ($id=="session"){
			$allowsave=false;
		}
		if ($id=="cookie"){
			$allowsave=false;
		}
	}

	if ($allowsave==true){

		if (!isset($ss_variables["".$id.""])){
			$ss_variables["".$id.""]=array();
		}

		if (checkpreg("|\[([A-Za-z0-9_\-\.]*)\]|i",$var2)==true){ //--Check if we have a match for [[A-Za-z0-9_-.]]
			//$ss_variables["".$id.""]["".$var.""]=array();
			$json="";
			$end="";
			//$varscope=$ss_variables["".$id.""]["".$var.""];
			preg_match_all("|\[([A-Za-z0-9_\-\.]*)\]|i",$var2, $got); //--Fetch each instance of of a sub item
			foreach ($got[1] as $script){
					$script=trim_clean($script);
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2." breaking down value parts [".$script."]"; }
					$value2=ss_code_variables_string_value($id,$script,false,false,$sandbox); //--We are turning the items into JSON so we then turn into an array.
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2." found data for part [".$value2."]"; }
					$json=$json."{ \"".$value2."\": ";
					$end=$end." }";
			}
			if (is_array($value)){
				$json=$json."".json_encode($value)."".$end;
			}else{
				$json=$json."\"".$value."\"".$end;
			}
			if (isset($ss_variables["".$id.""]["".$var.""])){ //--Is it already created?
				if (is_array($ss_variables["".$id.""]["".$var.""])){ //--Is it already an array? If so do a merge
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2." array merge"; }
					$ss_variables["".$id.""]["".$var.""]=array_merge_recursive_distinct($ss_variables["".$id.""]["".$var.""],json_decode($json, true));
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2." data was saved..."; }
				}else{ //--Its not an array so do a replace
					$ss_variables["".$id.""]["".$var.""]=json_decode($json, true);
				}
			}else{
				$ss_variables["".$id.""]["".$var.""]=json_decode($json, true);
			}
		}else{
			if (isset($ss_variables["".$id.""]["".$var.""])){ //--Is it already created?
				if (is_array($value)){ //--If it's an array that we are placing we might need to merge content
					$json="{ \"".$var."\": ".json_encode($value)." }";
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2." array merge type 2"; }
					$ss_variables["".$id.""]=array_merge_recursive_distinct($ss_variables["".$id.""],json_decode($json, true));
				}else{ //--Not an array we are adding so it will be a replace value insted.
					$ss_variables["".$id.""]["".$var.""]=$value;
				}
			}else{
				$ss_variables["".$id.""]["".$var.""]=$value;
			}
		}
		if (!is_array($value)){
			if (strlen($value)==0){
				$ss_variables["".$id.""]["".$var.""]=NULL;
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2." data was blank, clearing"; }
			}
		}
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Var Save #".$id." - ".$var2.""; }
	}
}

function ss_code_variables_string_replace($id,$l,$process=false,$sandbox=false){
	global $system;
	$l=trim_clean($l);

	if ($process==true){
		if (checkpreg("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l)==true && $sandbox==false){ //--Check if system function
			preg_match_all("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l, $got);
			foreach ($got[0] as $func){
				$data=ss_code_function_run($id,$func,$sandbox);
				if ($data === true || $data === false){
					return $data;
				}else{
					$l=str_replace($func,$data,$l);
				}
			}
		}

		if (checkpreg("|s\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l)==true){ //--Check if system function
			preg_match_all("|s\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l, $got);
			foreach ($got[0] as $func){
				$data=ss_sys_function($id,$func,false,$sandbox);
				if ($data === true || $data === false){
					return $data;
				}else{
					$l=str_replace($func,$data,$l);
				}
			}
		}
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)gv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
		preg_match_all("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)gv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l, $got);
		foreach ($got[2] as $var){
			$var=trim($var,'.');
			$va=ss_code_variables_get("global",$var,false,$sandbox);
			if ($va!==false){
				$l=str_replace("gv.".$var."",$va,$l);
			}
		}
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)pv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
		preg_match_all("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)pv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l, $got);
		foreach ($got[2] as $var){
			$var=trim($var,'.');
			$va=ss_code_variables_get("post",$var,false,$sandbox);
			if ($va!==false){
				$l=str_replace("pv.".$var."",$va,$l);
			}
		}
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)uv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
		preg_match_all("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)uv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l, $got);
		foreach ($got[2] as $var){
			$var=trim($var,'.');
			$va=ss_code_variables_get("url",$var,false,$sandbox);
			if ($va!==false){
				$l=str_replace("uv.".$var."",$va,$l);
			}
		}
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)sv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
		preg_match_all("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)sv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l, $got);
		foreach ($got[2] as $var){
			$var=trim($var,'.');
			$va=ss_code_variables_get("session",$var,false,$sandbox);
			if ($va!==false){
				$l=str_replace("sv.".$var."",$va,$l);
			}
		}
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)cv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
		preg_match_all("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)cv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l, $got);
		foreach ($got[2] as $var){
			$var=trim($var,'.');
			$va=ss_code_variables_get("cookie",$var,false,$sandbox);
			if ($va!==false){
				$l=str_replace("cv.".$var."",$va,$l);
			}
		}
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true){
		preg_match_all("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$l, $got);
			foreach ($got[2] as $var){
				$var=trim($var,'.');
				$va=ss_code_variables_get($id,$var,false,$sandbox);
				if ($va!==false){
					$l=str_replace("v.".$var."",$va,$l);
				}
			}
	}

	$l=trim_clean($l);
	//before we return if the string value is only a variable then we should run as a value replace so that any arrays get moved over.
	//the line by line calls ss_code_variables_string_replace so it also gets multi variables, but as we are that and we get an array we want to move it over.
	if (ss_code_variables_string_value_variable($l)==true){
		$l=ss_code_variables_string_value($id,$l,true,true);
	}
	return $l;
}

function ss_code_variables_string_value($id,$l,$raw=false,$process=false,$sandbox=false){
	if ($raw==false){
		$l=trim_clean($l);
	}
	$found=false;

	if ($process==true){
		if ($found==false){
			if (checkpreg("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l)==true && $sandbox==false){ //--Check if function
				preg_match_all("|f\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l, $got);
				$found=true;
				foreach ($got[0] as $func){
					$data=ss_code_function_run($id,$func);
					$l=$data;
				}
			}
		}
		if ($found==false){
			if (checkpreg("|s\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l)==true){ //--Check if system function
				preg_match_all("|s\.([A-Za-z0-9_\-]*)\((.*)\)|i",$l, $got);
				$found=true;
				foreach ($got[0] as $func){
					$data=ss_sys_function($id,$func);
					$l=$data;
				}
			}
		}
	}

	if ($found==false){
		if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)gv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
			$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)gv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l);
			$va=ss_code_variables_get("global",$var,$raw,$sandbox);
			$found=true;
			if ($va!==false){
				$l=$va;
			}else{
				$l=false;
			}
		}
	}
	if ($found==false){
		if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)pv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
			$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)pv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l);
			$va=ss_code_variables_get("post",$var,$raw,$sandbox);
			$found=true;
			if ($va!==false){
				$l=$va;
			}else{
				$l=false;
			}
		}
	}
	if ($found==false){
		if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)uv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
			$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)uv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l);
			$va=ss_code_variables_get("url",$var,$raw,$sandbox);
			$found=true;
			if ($va!==false){
				$l=$va;
			}else{
				$l=false;
			}
		}
	}

	if ($found==false){
		if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)sv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
			$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)sv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l);
			$va=ss_code_variables_get("session",$var,$raw,$sandbox);
			$found=true;
			if ($va!==false){
				$l=$va;
			}else{
				$l=false;
			}
		}
	}

	if ($found==false){
		if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)cv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true && $sandbox==false){
			$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)cv\.([A-Za-z0-9\.\[\]_\-]*)/i",$l);
			$va=ss_code_variables_get("cookie",$var,$raw,$sandbox);
			$found=true;
			if ($va!==false){
				$l=$va;
			}else{
				$l=false;
			}
		}
	}

	if ($found==false){
		if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$l)==true){
			$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$l);
			$va=ss_code_variables_get($id,$var,$raw,$sandbox);

			if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$va)==true){
				$varx=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$va);
				$va=ss_code_variables_get($id,$varx,$raw,$sandbox);
			}

			$found=true;
			if ($va!==false){
				$l=$va;
			}else{
				$l=false;
			}
		}
	}

	if ($raw==false){
		return trim_clean($l);
	}else{
		return $l;
	}
}

function ss_code_variables_string_value_variable($value){
	$return=false;
	$value=trim($value);
	if ($value=="v.".fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)v\.([A-Za-z0-9\.\[\]_\-]*)/i",$value).""){ $return=true; }
	if ($value=="cv.".fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)cv\.([A-Za-z0-9\.\[\]_\-]*)/i",$value).""){ $return=true; }
	if ($value=="sv.".fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)sv\.([A-Za-z0-9\.\[\]_\-]*)/i",$value).""){ $return=true; }
	if ($value=="uv.".fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)uv\.([A-Za-z0-9\.\[\]_\-]*)/i",$value).""){ $return=true; }
	if ($value=="pv.".fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)pv\.([A-Za-z0-9\.\[\]_\-]*)/i",$value).""){ $return=true; }
	if ($value=="gv.".fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)gv\.([A-Za-z0-9\.\[\]_\-]*)/i",$value).""){ $return=true; }

	return $return;
}

?>
