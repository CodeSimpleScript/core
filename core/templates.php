<?php

$settings_template=array();
$storage_template=array();

//#########################################################################################################
//######################################################################################################### - TEMPLATE
//#########################################################################################################
//############################## - $id = ID of the function/area for variable storage
//############################## - $t = The content we are checking for functions
function ss_template($id,$t){
	global $system;
	global $storage_template;
	global $settings_template;

	if (checkpreg("|t\.([A-Za-z0-9_\-]*)\((.*)\)|i",$t)==true){ //--Check if we have a match for t.[A-Za-z0-9_-]()
		preg_match_all("|t\.([A-Za-z0-9_\-]*)\((.*)\)|i",$t, $got); //--Fetch each instance of the template call
		foreach ($got[0] as $script){ //--For each found template function that matches return only contained patern
			$func=fetchpreg("|t\.([A-Za-z0-9_\-]*)\(|i",$t); //--Take that patern that was returned and fetch from it the template name.
			$code=trim_clean(fetchpreg("|t\.".$func."\((.*)\)|i",$t)); //--Take that patern that was returned and fetch from it the template content.
			if ($system["debug"]==true){ $system["debug_log"].="\r\n> Run Template Add - ".$func.""; }
			//--Store the content added onto the template storage for post build
			if ($code==""){
				$storage_template[$func]=""; //--Value Reset
			}else{
				$content=ss_code_variables_string_replace($id,$code,true);
				if (isset($storage_template[$func])){
					$storage_template[$func].=$content;
					return true;
				}else{
					$storage_template[$func]=$content;
					return true;
				}
			}

		}
	}
}

//#########################################################################################################
//######################################################################################################### - TEMPLATE SET
//#########################################################################################################
//############################## - $f = the file out template is in for later
function ss_template_set($f){
	global $system;
	global $storage_template;
	global $settings_template;

	$settings_template["file"]=$f;
}

//#########################################################################################################
//######################################################################################################### - TEMPLATE POST RUN
//#########################################################################################################
//############################## - $t = The content we are checking for functions
function ss_template_postrun($t){
	global $system;
	global $storage_template;
	global $settings_template;

	if (isset($settings_template["file"])){
		$template=file_get_contents($system["runpath"].$settings_template["file"], FILE_USE_INCLUDE_PATH); //--Fetch template file

		if (checkpreg("|\{\{([A-Za-z0-9_\-]*)\}\}|i",$template)==true){ //--Check if we have a match for {{name}}
			preg_match_all("|\{\{([A-Za-z0-9_\-]*)\}\}|i",$template, $got); //--Fetch each instance of a place marker
			foreach ($got[1] as $place){ //--For each found
				if ($place!=""){
					if (isset($storage_template[$place])){
						$template=str_replace("{{".$place."}}",decode_makesafe_ss_input($storage_template[$place]),$template);
					}else{
						$template=str_replace("{{".$place."}}","",$template);
					}
				}
			}
			$t.=$template;
		}
	}
	return $t;
}

?>
