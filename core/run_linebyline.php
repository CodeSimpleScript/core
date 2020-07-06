<?php

//#########################################################################################################
//######################################################################################################### - LINE BY LINE RUN
//#########################################################################################################
//############################## - $t = The content that this is checking
function ss_run_linebyline($t,$data=false,$sandbox=false,$sandboxencode=true){
	global $system;

	$time_start = microtime_float();
	$r=""; //--Data to be sent back in return
	$system["id"]=$system["id"]+1;
	$id=$system["id"]; //--Process id, used for varible and external function memory during linebyline

	//Run over processes first!!!

	$t=ss_code_functions_register($id,$t,$sandbox);

	if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Invoke Start with ID ".$id.""; }

	if (is_array($data)){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Invoke Start with ID ".$id." - Got data to use passed"; }
		ss_code_variables_save($id,"function_passed",$data,false,$sandbox);
	}

	$v=array();
	$v["function"]=false;
	$v["ran"]=false;
	$v["backquote"]=false;
	$v["if_on"]=0;
	$v["if_child"]=0; //--When we are running in a IF thats diabled and come across a IF, we mark it down so we know how many END's we must pass beore our own.
	$v["if"]=array(); //--Turn true if in a IF statement
	$v["if_disabled"]=array(); //--Turn true if first part of statement is false so the code until end of IF does not run
	$v["if_line"]=array(); //--Line ID for the IF statement when it started.
	$v["if_type"]=array(); //--If type for use on non normal if statements like WHILE

	// Match data initialization
	$v["match_on"] = false;	//-- Turn true if match statement is activated
	$v["match_made"] = false;	//-- Turn true if a match statement has been...matched
	$v["match_data"] = "";	//--  Stores the data from the variable being matched

	// For loop initialization
	$v["for_counter"] = -1; //-- To keep track of indices in for arrays
	$v["for_varname"] = array(); //-- Stores variable names for the for iterator
	$v["for_ending_num"] = array(); //-- Stores the number to end the for loop on
	$v["for_beginning_line"] = array(); //-- Stores the line number for the for loop beginning
	$v["for_ending_line"] = array(); //-- Stores the line numbers for the ending of for loops
	$v["for_direction"] = array(); //-- Stores the direction for the for loop iterator
	$v["for_if_level"] = array(); //-- Stores the if levels of the for loops for easy reference

	$var=array();
	$running=true;
	$line=0; //--Current line number
	$array = preg_split("/\r\n|\n|\r/", $t); //--Break up lines into array
	while ($running==true){
		if (isset($array[$line])){
			$l=ltrim($array[$line]);
		}else{
			$l="";
			$running=false;
		}

		//--Comment's remove content so they don't run
		if (strpos(substr($l, 0, 2), '//') !== false){
			$l="";
		}

		if ($l!=""){

			if (!isset($v["if"][$v["if_on"]])){
				$v["if"][$v["if_on"]]=false;
				$v["if_disabled"][$v["if_on"]]=false;
			}

			//--Backquote areas are auto return without processing
			if (strpos($l, '````') !== false && $v["if_disabled"][$v["if_on"]]==false){
				$v["ran"]=true;
				if ($v["backquote"]==false){
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> Backquote ON with backtrack to ````"; } $v["backquote"]=true; $r.=substr($l, strpos($l, "````") + 1);
				}else{
					if ($system["debug"]==true){ $system["debug_log"].="\r\n> Backquote OFF with forward check before ````"; } $v["backquote"]=false; $r.=strtok($l, "````");
				}
			}else{
				if ($v["backquote"]==true){ $v["ran"]=true; $r.=$l; }
			}

			//--Find IF statements when already in blocked IF do child count
			if ($v["backquote"]==false && $v["if_disabled"][$v["if_on"]]==true && $v["ran"]==false){
				if (strpos(substr($l, 0, 6), 'if ') !== false){
					$ifcheck=ss_run_linebyline_if($id,$l,$sandbox); //--Check if if statement to verify that it's real
					if ($ifcheck!=false){
						$v["if_child"]++;
						$v["ran"]=true;
					}
				}
			}

			//--Find IF statements when already in blocked IF (while) do child count
			if ($v["backquote"]==false && $v["if_disabled"][$v["if_on"]]==true && $v["ran"]==false){
				if (strpos(substr($l, 0, 6), 'while ') !== false){
					$l2=str_replace("while ", "if ", $l); //--We want this to run like a normal IF statment (with changes)
					$ifcheck=ss_run_linebyline_if($id,$l2,$sandbox); //--Check if while statement
					if ($ifcheck!=false){
						$v["if_child"]++;
						$v["ran"]=true;
					}
				}
			}

			//--Standard processing
			if ($v["backquote"]==false && $v["if_disabled"][$v["if_on"]]==false && $v["ran"]==false){
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine RUN (".$l.")"; }
				//####################################### START STANDARD PROCESSING

				//--------------------------------------- IF CODE - IF
				if (strpos(substr($l, 0, 4), 'if ') !== false){
					$ifcheck=ss_run_linebyline_if($id,$l,$sandbox); //--Check if if statement (I know) (Do you really?)
					if ($ifcheck!=false && $v["ran"]==false){
						if ($ifcheck=="yes"){
							$v["if_on"]=$v["if_on"]+1;
							$v["ran"]=true;
							$v["if"][$v["if_on"]]=true;
							$v["if_disabled"][$v["if_on"]]=false;
						}
						if ($ifcheck=="no"){
							$v["if_on"]=$v["if_on"]+1;
							$v["ran"]=true;
							$v["if"][$v["if_on"]]=true;
							$v["if_disabled"][$v["if_on"]]=true;
						}
						if ($system["debug"]==true){ $system["debug_log"].="\r\n> IF Statement ID now ".$v["if_on"].""; }
						$v["if_line"][$v["if_on"]]=$line;
						$v["if_type"][$v["if_on"]]="if";
					}
				}

				//--------------------------------------- IF CODE - WHILE
				if (strpos(substr($l, 0, 7), 'while ') !== false){
					$l2=str_replace("while ", "if ", $l); //--We want this to run like a normal IF statment (with changes)
					$ifcheck=ss_run_linebyline_if($id,$l2,$sandbox); //--Check if while statement
					if ($ifcheck!=false && $v["ran"]==false){
						if ($ifcheck=="yes"){
							$v["if_on"]=$v["if_on"]+1;
							$v["ran"]=true;
							$v["if"][$v["if_on"]]=true;
							$v["if_disabled"][$v["if_on"]]=false;
						}
						if ($ifcheck=="no"){
							$v["if_on"]=$v["if_on"]+1;
							$v["ran"]=true;
							$v["if"][$v["if_on"]]=true;
							$v["if_disabled"][$v["if_on"]]=true;
						}
						if ($system["debug"]==true){ $system["debug_log"].="\r\n> IF Statement ID now ".$v["if_on"].""; }
						$v["if_line"][$v["if_on"]]=$line;
						$v["if_type"][$v["if_on"]]="while";
					}
				}

				//--------------------------------------- MATCH CODE - BEGIN
				if (strpos(substr($l, 0, 5), "match") !== false) {
					$v["match_on"] = true;

					//Get the variable data and store it in $v["match_data"]
					preg_match("|match (.*)|i", $l, $var);
					$v["match_data"] = ss_code_variables_string_value($id, ltrim($var[1]), false, true, $sandbox);
				}

				//--------------------------------------- MATCH CODE - CASES
				if (strpos(substr($l, 0, 4), "case") !== false) {
					if (!$v["match_made"] && $v["match_on"]) {
						//Split the case line into two and then split string[0]
						//Match against (case 2 => s.echo(hi))
						$comp_data="";
						$comp_code="";

						if (preg_match("|case ([^=]*) => (.*)|i", $l, $comp_match)){
							$comp_data=$comp_match[1];
							$comp_code=$comp_match[2];
						}
						//Match against (case 2 = s.echo(hi))
						if (preg_match("|case ([^=]*) = (.*)|i", $l, $comp_match)){
							$comp_data=$comp_match[1];
							$comp_code=$comp_match[2];
						}
						//Match against (case 2 matches run s.echo(hi))
						if (preg_match("|case ([^=]*) matches run (.*)|i", $l, $comp_match)){
							$comp_data=$comp_match[1];
							$comp_code=$comp_match[2];
						}
						//Match against (case 2 run s.echo(hi))
						if (preg_match("|case ([^=]*) run (.*)|i", $l, $comp_match)){
							$comp_data=$comp_match[1];
							$comp_code=$comp_match[2];
						}

						//Matching against our MATCH varable stored in $v["match_data"]
						//If its not a match the line is deleted before processing.
						if (($v["match_data"] == $comp_data) && !$v["match_made"]) {
							$v["match_made"] = true;
							$v["match_on"] = false;
							$l=$comp_code;
						} elseif (($comp_data == "none") && ($v["match_made"] == false)){
							$v["match_made"] = true;
							$v["match_on"] = false;
							$l=$comp_code;
						} else {
							//Dont keep running as it does not match
							$v["ran"]=true;
						}
					} else {
						//Dont keep running as it does not match
						$v["ran"]=true;
					}
				}

				//--------------------------------------- FOR LOOP - BEGIN
				if (strpos(substr($l, 0, 3), "for") !== false) {
					// Pregmatch info from the line for for loop info (heh)
					if (preg_match("|for (-?[0-9]+) to (-?[0-9]+) in v.([A-Za-z0-9]+)|", $l, $for_match)) {
						// Add things to $v array
						$v["for_counter"]++;
						$v["for_varname"][$v["for_counter"]] = $for_match[3];
						$v["for_ending_num"][$v["for_counter"]] = $for_match[2];
						$v["for_beginning_line"][$v["for_counter"]] = $line;

						//indent the IF level as we act like a IF statements
						$v["if_on"]=$v["if_on"]+1;
						$v["if_type"][$v["if_on"]]="loop";
						$v["if"][$v["if_on"]]=true;
						$v["if_disabled"][$v["if_on"]]=false;

						// Store the IF level
						$v["for_if_level"][$v["for_counter"]] = $v["if_on"];

						// Set the direction of the iterator
						if ($for_match[1] < $for_match[2]){
							$v["for_direction"][$v["for_counter"]] = "R";
						} else {
							$v["for_direction"][$v["for_counter"]] = "L";
						}

						// Get the starting value
						$starting_value = $for_match[1];
					}

					// Create the variable and set it to the first value
					ss_code_variables_save($id, $v["for_varname"][$v["for_counter"]], $starting_value,false,$sandbox);
				}

				//--------------------------------------- FOR LOOP - BREAK
				if (strpos(substr($l, 0, 5), "break") !== false) {
					// Flip "if_disabled" for the for loop's "if_level"
					$for_level = $v["for_if_level"][$v["for_counter"]];
					$v["if_disabled"][$for_level] = true;
				}

				//--------------------------------------- TEMPLATES
				if (checkpreg("|t\.([A-Za-z0-9_\-]*)\(|i",$l)==true && $v["ran"]==false && $sandbox==false){ //--Check if template call
					$output=ss_template($id,$l);
						if ($output==true){
							$v["ran"]=true;
						}
				}

				//--------------------------------------- SYSTEM FUNCTIONS
				if (checkpreg("|s\.([A-Za-z0-9_\-]*)\(|i",$l)==true && $v["ran"]==false){ //--Check if system function
					if (checkpreg("|v\.([A-Za-z0-9\.\[\]_\-]*)\s*=|i",$l)==false){ //--Check if not a varible set
						$output=ss_sys_function($id,$l,true,$sandbox);
						if (is_string($output)){
							$r.=$output;
						}
						$v["ran"]=true;
					}
				}

				//--------------------------------------- CODE FUNCTIONS
				if (checkpreg("|f\.([A-Za-z0-9_\-]*)\(|i",$l)==true && $v["ran"]==false){ //--Check if function
					if (checkpreg("|v\.([A-Za-z0-9\.\[\]_\-]*)\s*=|i",$l)==false){ //--Check if not a varible set
						$output=ss_code_function_run($id,$l,false,$sandbox);
						if (is_string($output)){
							$r.=$output;
						}
						$v["ran"]=true;
					}
				}

				//--------------------------------------- VARIABLE SET/UPDATE

				if ($v["ran"]==false && $sandbox==false){ //--Math for gv.variable
					if (ss_run_linebyline_variable_math($id,$l,"gv","global",$sandbox)==true){
						$v["ran"]=true;
					}
				}

				if ($v["ran"]==false && $sandbox==false){ //--Math for pv.variable
					if (ss_run_linebyline_variable_math($id,$l,"pv","post",$sandbox)==true){
						$v["ran"]=true;
					}
				}

				if ($v["ran"]==false && $sandbox==false){ //--Math for uv.variable
					if (ss_run_linebyline_variable_math($id,$l,"uv","url",$sandbox)==true){
						$v["ran"]=true;
					}
				}

				if ($v["ran"]==false && $sandbox==false){ //--Math for sv.variable
					if (ss_run_linebyline_variable_math($id,$l,"sv","session",$sandbox)==true){
						$v["ran"]=true;
					}
				}

				if ($v["ran"]==false && $sandbox==false){ //--Math for cv.variable
					if (ss_run_linebyline_variable_math($id,$l,"cv","cookie",$sandbox)==true){
						$v["ran"]=true;
					}
				}

				if ($v["ran"]==false){ //--Math for v.variable
					if (ss_run_linebyline_variable_math($id,$l,"v",$id,$sandbox)==true){
						$v["ran"]=true;
					}
				}

			}

			//--------------------------------------- IF CODE - ELSE
			if (strpos(substr($l,0,4), 'else') !== false && $v["backquote"]==false && $v["ran"]==false && $v["if"][$v["if_on"]]==true && $v["if_child"]==0){
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine ELSE (".$v["if_on"].") ".$l.""; }
				if ($v["if"][$v["if_on"]]==true){
					if ($v["if_disabled"][$v["if_on"]]==true){
						$v["if_disabled"][$v["if_on"]]=false;
					}else{
						$v["if_disabled"][$v["if_on"]]=true;
					}
				}
			}

			//--------------------------------------- IF CODE - END
			if (strpos(substr($l,0,3), 'end') !== false && $v["backquote"]==false && $v["ran"]==false && $v["if"][$v["if_on"]]==true && $v["if_child"]==0){
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine END (".$v["if_on"].") ".$l.""; }
				if ($v["if"][$v["if_on"]]==true){
					// Reset the values at the end for the if arrays if true.
					$do_reset = true;

					if ($v["if_type"][$v["if_on"]]=="while"){ //--If it was a WHILE if statement loop we need to go back and run it again from that line incase it needs to be invoked yet again.
						if ($v["if_disabled"][$v["if_on"]]==false){
							$line=$v["if_line"][$v["if_on"]]-1; //--Take one as after this done we are adding one by default
							if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine END for CHILD (".$v["if_on"]."), Restart at line ".($line+1)." (WHILE IF LOOP)"; }

							$do_reset = false;  //-- Disables the reset to save operation runs
						}
					}

					// Handler for the end of a for loop
					if ($v["if_type"][$v["if_on"]] == "loop") {
						// Set to make things a bit easier instead of calling $v["for_counter"] constantly
						$i = $v["for_counter"];

						//Get the iterator's value
						$iterator = ss_code_variables_get($id, $v["for_varname"][$i], false, $sandbox);

						// If the value of the iterator var does not equal the end value, go back
						if ($iterator != $v["for_ending_num"][$i] && $v["if_disabled"][$v["if_on"]] == false) {
							$line = $v["for_beginning_line"][$i];  // Goes back the beginning for statement

							// Increment/decrement the counter depending on the direction of the loop
							if ($v["for_direction"][$i] == "R") {
								ss_code_variables_save($id, $v["for_varname"][$i], $iterator + 1, false, $sandbox);
							} else {
								ss_code_variables_save($id, $v["for_varname"][$i], $iterator - 1, false, $sandbox);
							}

							// Do not reset the if arrays since the for loop is still active
							$do_reset = false;
						} else {  // If the values are the same, end the loop and clear the for values

							// Clear the values and decrement the counter
							$v["for_varname"][$i] = "";
							$v["for_direction"][$i] = "";
							$v["for_beginning_line"][$i] = "";
							$v["for_ending_num"][$i] = "";
							$v["for_counter"]--;
							$v["for_if_level"][$i] = "";
						}
					}


					if ($do_reset) {
						$v["ran"]=true;
						$v["if_disabled"][$v["if_on"]]=false;
						$v["if"][$v["if_on"]]=false;
						$v["if_on"]=$v["if_on"]-1;
					}
				}
			}


			//--------------------------------------- END STATEMENT CHILD CLEANUP
			if ($v["if_disabled"][$v["if_on"]]==true && $v["backquote"]==false && $v["ran"]==false && $v["if_child"]>0){
				if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine END CHILD (".$v["if_on"].") ".$l.""; }
				if (strpos(substr($l,0,3), 'end') !== false){
					$v["if_child"]--;
					$v["ran"]=true;
				}
			}

			//####################################### END STANDARD PROCESSING
		}

		$time_test = microtime_float();
		$time_at=round(($time_test-$time_start),2);
		if ($time_at>=30){
			$running=false;
		}
		$v["ran"]=false;
		$line+=1; //Next Line
	}

	$time_end = microtime_float();
	if ($system["debug"]==true){ $system["debug_log"].="\r\n> LineByLine Invoke Finished, took (".round(($time_end-$time_start),2)." seconds)"; }

	if ($sandbox==false){
		return $r;
	}else{
		if ($sandboxencode==true){
			$retarray=array();
			$retarray["response"]=$r;
			$retarray["cputime"]=round(($time_end-$time_start),6);
			$retjson=json_encode($retarray);
			return $retjson;
		}else{
		 return $r;
		}
	}
}

//#########################################################################################################
//######################################################################################################### - LINE BY LINE IF STATEMENTS
//#########################################################################################################
//############################## - $id = ID of the function/area for variable storage
//############################## - $l = The line content that this is checking
function ss_run_linebyline_if($id,$l,$sandbox=false){
	global $system;
	if ($system["debug"]==true){ $system["debug_log"].="\r\n> Checking For IF In ID Range ".$id.""; }
	$found=false;

	//--Match rule (if not a == b)
	if (preg_match("|if not ([^=]*)==(.*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if not a == b)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		$found2=ss_code_variables_string_value($id,ltrim($var[2]),false,true,$sandbox);
		if ($found1==$found2){
			$found="no";
		}else{
			$found="yes";
		}
	}

	//--Match rule (if set a)
	if (preg_match("|if set (.*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if set a)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,false,$sandbox);
		if ($found1==false){
			$found="no";
		}else{
			$found="yes";
		}
	}

	//--Match rule (if array a)
	if (preg_match("|if array (.*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if array a)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),true,false,$sandbox);
		if (is_array($found1)){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if notarray a)
	if (preg_match("|if notarray (.*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if notarray a)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),true,false,$sandbox);
		if (is_array($found1)){
			$found="no";
		}else{
			$found="yes";
		}
	}

	//--Match rule (if notset a)
	if (preg_match("|if notset (.*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if notset a)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		if ($found1==false){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if not a false)
	if (preg_match("|if not ([^\s]*) false|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if not a false) using var ".ltrim($var[1]).""; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		if ($found1=="false"){
			$found="no";
		}else{
			$found="yes";
		}
	}

	//--Match rule (if a false)
	if (preg_match("|if ([^\s]*) false|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if a false) using var ".ltrim($var[1]).""; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		if ($found1=="false"){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if not a true)
	if (preg_match("|if not ([^\s]*) true|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if not a true) using var ".ltrim($var[1]).""; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		if ($found1=="true"){
			$found="no";
		}else{
			$found="yes";
		}
	}

	//--Match rule (if a true)
	if (preg_match("|if ([^\s]*) true|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if a true) using var ".ltrim($var[1]).""; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		if ($found1=="true"){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if a == b)
	if (preg_match("|if ([^=]*)==(.*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if a == b)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		$found2=ss_code_variables_string_value($id,ltrim($var[2]),false,true,$sandbox);
		if ($found1==$found2){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if a >= b)
	if (preg_match("|if ([^>]*)>=([^=]*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if a >= b)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		$found2=ss_code_variables_string_value($id,ltrim($var[2]),false,true,$sandbox);
		if ($found1>=$found2){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if a <= b)
	if (preg_match("|if ([^<]*)<=([^=]*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if a <= b)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		$found2=ss_code_variables_string_value($id,ltrim($var[2]),false,true,$sandbox);
		if ($found1<=$found2){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if a > b)
	if (preg_match("|if ([^>]*)>([^=]*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if a > b)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		$found2=ss_code_variables_string_value($id,ltrim($var[2]),false,true,$sandbox);
		if ($found1>$found2){
			$found="yes";
		}else{
			$found="no";
		}
	}

	//--Match rule (if a < b)
	if (preg_match("|if ([^<]*)<([^=]*)|i", $l, $var) && $found==false){
		if ($system["debug"]==true){ $system["debug_log"].="\r\n> Found IF Statement Match - (if a < b)"; }
		$found1=ss_code_variables_string_value($id,ltrim($var[1]),false,true,$sandbox);
		$found2=ss_code_variables_string_value($id,ltrim($var[2]),false,true,$sandbox);
		if ($found1<$found2){
			$found="yes";
		}else{
			$found="no";
		}
	}

	if ($system["debug"]==true){ $system["debug_log"].="\r\n> IF Statement Match Result (".$found.")"; }
	return $found;
}

//#########################################################################################################
//######################################################################################################### - LINE BY LINE VAR MATH
//#########################################################################################################
//############################## - $id = ID of the function/area for variable storage (dont set with GLOBAL as set and fetch is $scope)
//############################## - $l = The line content that this is checking
//############################## - $tag = Variable tag, we run this in a specific order or only want to replace a specific variable type
//############################## - $scope = The variable storage area used for the main saving and varible calling (GLOBAL, or ID of varible storage)
function ss_run_linebyline_variable_math($id,$l,$tag,$scope,$sandbox=false){
	global $system;
	$ran=false;

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\-]*)==/i",$l)==true && $ran==false){ //--Check if variable set == force move values
		$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\-]*)==/i",$l);
		$value=trim_clean(substr($l, strpos($l, "".$tag.".".$var."==") + strlen("".$tag.".".$var."==")));
		$value=ss_code_variables_string_value($id,$value,true,true,$sandbox); //--Check for values from other values and functions with data in line
		ss_code_variables_save($scope,$var,$value,false,$sandbox);
		$ran=true;
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\-]*)\s*=/i",$l)==true && $ran==false){ //--Check if variable set
		$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\- ]*)=/i",$l);
		$value=trim_clean(substr($l, strpos($l, "".$tag.".".$var."=") + strlen("".$tag.".".$var."=")));

		$value=ss_code_variables_string_replace($id,$value,true,$sandbox); //--Check for values from other values and functions with data in line
		ss_code_variables_save($scope,$var,$value,false,$sandbox);
		$ran=true;
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\-]*)\s*\+/i",$l)==true && $ran==false){ //--Check if variable add
		$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\- ]*)\+/i",$l);
		$value=trim_clean(substr($l, strpos($l, "".$tag.".".$var."+") + strlen("".$tag.".".$var."+")));
		$value=ss_code_variables_get($scope,$var,false,$sandbox)+ss_code_variables_string_replace($id,$value,true,$sandbox);
		ss_code_variables_save($scope,$var,$value,false,$sandbox);
		$ran=true;
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\-]*)\s*-/i",$l)==true && $ran==false){ //--Check if variable take
		$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\- ]*)-/i",$l);
		$value=trim_clean(substr($l, strpos($l, "".$tag.".".$var."-") + strlen("".$tag.".".$var."-")));
		$value=ss_code_variables_get($scope,$var,false,$sandbox)-ss_code_variables_string_replace($id,$value,true,$sandbox);
		ss_code_variables_save($scope,$var,$value,false,$sandbox);
		$ran=true;
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\-]*)\s*\//i",$l)==true && $ran==false){ //--Check if variable devide
		$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\- ]*)\//i",$l);
		$value=trim_clean(substr($l, strpos($l, "".$tag.".".$var."/") + strlen("".$tag.".".$var."/")));
		$value=ss_code_variables_get($scope,$var,false,$sandbox)/ss_code_variables_string_replace($id,$value,true,$sandbox);
		ss_code_variables_save($scope,$var,$value,false,$sandbox);
		$ran=true;
	}

	if (checkpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\-]*)\s*\*/i",$l)==true && $ran==false){ //--Check if variable multiply
		$var=fetchpreg("/(\A|\r|\n|\r\n|\.|\_|\-|\!|\?|\s|\W)".$tag."\.([A-Za-z0-9\.\[\]_\- ]*)\*/i",$l);
		$value=trim_clean(substr($l, strpos($l, "".$tag.".".$var."*") + strlen("".$tag.".".$var."*")));
		$value=ss_code_variables_get($scope,$var,false,$sandbox)*ss_code_variables_string_replace($id,$value,true,$sandbox);
		ss_code_variables_save($scope,$var,$value,false,$sandbox);
		$ran=true;
	}

	return $ran;
}

?>
