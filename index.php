<?php
//##############################################################################################################
//##############################################################################################################-- BASE/MAIN Functions
//##############################################################################################################

	function codegenerate($length=8){
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$rs="";
		for ($i = 0; $i < $length; $i++){ $rs .= $characters[rand(0, strlen($characters) - 1)]; }
		return $rs;
	}

	function encode_makesafe_ss_input($d){
		$d = str_replace(".","&period;",$d);
		return $d;
	}

	function decode_makesafe_ss_input($d){
		$d = str_replace("&period;",".",$d);
		$d = str_replace("&amp;period;",".",$d); //--Markdown parser fix
		return $d;
	}

	function trim_clean($value){
		//--Remove back slash from escaped quotes \"
		$type=substr($value,0,1);
		if ($type=="\"" OR $type=="'"){
			$value=str_replace('\\'.$type,$type,$value);
		}

		if ($value!=" "){
			$value=trim($value);
		}
		$value = preg_replace('/^(\'(.*)\'|"(.*)")$/', '$2$3', $value);
		return $value;
	}

	function fetchpreg($fetch,$data){
		if (preg_match($fetch, $data, $var)){
			if (isset($var[2])){
				return $var[2];
			}else{
				return $var[1];
			}
		}
	}

	function fetchpreg_all($fetch,$data){
		if (preg_match($fetch, $data, $var)){
			return $var[0];
		}
	}

	function checkpreg($fetch,$data){
		if (is_array($data)){
			return false;
		}else{
			if (preg_match($fetch, $data, $var)){
				return true;
			}else{
				return false;
			}
		}
	}

	function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	function removeblank($s){
		return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $s);
	}

	function log_error($error, $line){
		global $system;
		global $settings;
		$today = date("Y-m-W");
		$timestamp = date("Y/m/d h:i:s");
		$url=$system["location"].$settings["location_logs"]."/error_".$today.".log";
		error_log("".$timestamp ." | file: ".$system["url"]." | error: ".$error." | code: \"".$line."\"\n", 3, $url);
	}

	function log_access(){
		global $system;
		global $settings;
		$timesend="".round(($system["time_end"]-$system["time_start"]),2)." seconds";
		$today = date("Y-m-W");
		$timestamp = date("Y/m/d h:i:s");
		$url=$system["location"].$settings["location_logs"]."/access_".$today.".log";
		if ($timesend>=1){ log_slow($timesend); }

		//error_log("".$timestamp ." | file: ".$system["url"]." | ip: ".$system["ip"]." | useragent: ".$system["useragent"]." | time: ".$timesend."\n", 3, $url);
	}

	function log_slow($timesend){
		global $system;
		global $settings;
		$today = date("Y-m-W");
		$timestamp = date("Y/m/d h:i:s");
		$url=$system["location"].$settings["location_logs"]."/slow_".$today.".log";

		//error_log("".$timestamp ." | file: ".$system["url"]." | ip: ".$system["ip"]." | useragent: ".$system["useragent"]." | time: ".$timesend."\n", 3, $url);
	}

	function parse_http_head ($str) {
    $result = array();
    // Split into lines
    $lines = explode("\r\n", $str);

    // Handle response line
    $line = explode(' ', array_shift($lines), 3);
    $version = explode('/', $line[0]);
    $result['version'] = (float) $version[1];
    $result['code'] = (int) $line[1];
    $result['text'] = $line[2];

    // Parse headers
    $result['headers'] = array();
    while ($line = trim(array_shift($lines))) {
      list($name, $val) = explode(':', $line, 2);
      $name = strtolower(trim($name));
      if (isset($result['headers'][$name])) {
        $result['headers'][$name] = (array) $result['headers'][$name];
        $result['headers'][$name][] = trim($val);
      } else {
        $result['headers'][$name] = trim($val);
      }
    }
    return $result;
  }

	function customError($errno, $errstr) {
		echo '<!DOCTYPE html><html lang="en"><head>';
		echo '<title>Error '.$errno.'</title>';
		echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width, user-scalable=no">';
		echo '<style>';
		echo 'html{ height:100%; }';
		echo 'body{ display: block; font-weight: 300; background: #fff; color: #909090; font-size: 17px; line-height: 1.37; font-smooth: always; font-family: "Open Sans", Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; height:100%; }';
		echo 'h1, h2, h3{ font-weight: 400; color: #494949; }';
		echo '</style></head><body>';
		echo '<div style="position:fixed;left:50%;top:50%;margin-left:-200px;margin-top:-100px;text-align:center;width:400px;height:200px;box-shadow: 0 0 52px -7px rgba(0, 0, 0, .3);-webkit-box-shadow: 0 0 52px -7px rgba(0, 0, 0, .3);-moz-box-shadow: 0 0 52px -7px rgba(0, 0, 0, .3);">';
		echo '<h1 style="font-size:22px;padding-top:40px;">We have encountered an error</h1>';
		echo '<p>'.$errstr.'</p>';
		echo '</div>';
		echo '</body></html>';
		shutdown();
		die();
	}

	function makesafe($d,$type="basic"){
		$d = str_replace("|","&#124;",$d);
		$d = str_replace("\\","&#92;",$d);
		$d = str_replace("(c)","&#169;",$d);
		$d = str_replace("(r)","&#174;",$d);
		$d = str_replace("\"","&#34;",$d);
		$d = str_replace("'","&#39;",$d);
		$d = str_replace("<","&#60;",$d);
		$d = str_replace(">","&#62;",$d);
		return $d;
	}

	function mime_type($file) {

		$mime_type = array(
			"afdesign"			=>	"application/octet-stream",
			"3dml"			=>	"text/vnd.in3d.3dml",
			"3g2"			=>	"video/3gpp2",
			"3gp"			=>	"video/3gpp",
			"7z"			=>	"application/x-7z-compressed",
			"aab"			=>	"application/x-authorware-bin",
			"aac"			=>	"audio/x-aac",
			"aam"			=>	"application/x-authorware-map",
			"aas"			=>	"application/x-authorware-seg",
			"abw"			=>	"application/x-abiword",
			"ac"			=>	"application/pkix-attr-cert",
			"acc"			=>	"application/vnd.americandynamics.acc",
			"ace"			=>	"application/x-ace-compressed",
			"acu"			=>	"application/vnd.acucobol",
			"adp"			=>	"audio/adpcm",
			"aep"			=>	"application/vnd.audiograph",
			"afp"			=>	"application/vnd.ibm.modcap",
			"ahead"			=>	"application/vnd.ahead.space",
			"ai"			=>	"application/postscript",
			"aif"			=>	"audio/x-aiff",
			"air"			=>	"application/vnd.adobe.air-application-installer-package+zip",
			"ait"			=>	"application/vnd.dvb.ait",
			"ami"			=>	"application/vnd.amiga.ami",
			"apk"			=>	"application/vnd.android.package-archive",
			"application"		=>	"application/x-ms-application",
			"apr"			=>	"application/vnd.lotus-approach",
			"asf"			=>	"video/x-ms-asf",
			"aso"			=>	"application/vnd.accpac.simply.aso",
			"atc"			=>	"application/vnd.acucorp",
			"atom"			=>	"application/atom+xml",
			"atomcat"		=>	"application/atomcat+xml",
			"atomsvc"		=>	"application/atomsvc+xml",
			"atx"			=>	"application/vnd.antix.game-component",
			"au"			=>	"audio/basic",
			"avi"			=>	"video/x-msvideo",
			"aw"			=>	"application/applixware",
			"azf"			=>	"application/vnd.airzip.filesecure.azf",
			"azs"			=>	"application/vnd.airzip.filesecure.azs",
			"azw"			=>	"application/vnd.amazon.ebook",
			"bcpio"			=>	"application/x-bcpio",
			"bdf"			=>	"application/x-font-bdf",
			"bdm"			=>	"application/vnd.syncml.dm+wbxml",
			"bed"			=>	"application/vnd.realvnc.bed",
			"bh2"			=>	"application/vnd.fujitsu.oasysprs",
			"bin"			=>	"application/octet-stream",
			"bmi"			=>	"application/vnd.bmi",
			"bmp"			=>	"image/bmp",
			"box"			=>	"application/vnd.previewsystems.box",
			"btif"			=>	"image/prs.btif",
			"bz"			=>	"application/x-bzip",
			"bz2"			=>	"application/x-bzip2",
			"c"			=>	"text/x-c",
			"c11amc"		=>	"application/vnd.cluetrust.cartomobile-config",
			"c11amz"		=>	"application/vnd.cluetrust.cartomobile-config-pkg",
			"c4g"			=>	"application/vnd.clonk.c4group",
			"cab"			=>	"application/vnd.ms-cab-compressed",
			"car"			=>	"application/vnd.curl.car",
			"cat"			=>	"application/vnd.ms-pki.seccat",
			"ccxml"			=>	"application/ccxml+xml,",
			"cdbcmsg"		=>	"application/vnd.contact.cmsg",
			"cdkey"			=>	"application/vnd.mediastation.cdkey",
			"cdmia"			=>	"application/cdmi-capability",
			"cdmic"			=>	"application/cdmi-container",
			"cdmid"			=>	"application/cdmi-domain",
			"cdmio"			=>	"application/cdmi-object",
			"cdmiq"			=>	"application/cdmi-queue",
			"cdx"			=>	"chemical/x-cdx",
			"cdxml"			=>	"application/vnd.chemdraw+xml",
			"cdy"			=>	"application/vnd.cinderella",
			"cer"			=>	"application/pkix-cert",
			"cgm"			=>	"image/cgm",
			"chat"			=>	"application/x-chat",
			"chm"			=>	"application/vnd.ms-htmlhelp",
			"chrt"			=>	"application/vnd.kde.kchart",
			"cif"			=>	"chemical/x-cif",
			"cii"			=>	"application/vnd.anser-web-certificate-issue-initiation",
			"cil"			=>	"application/vnd.ms-artgalry",
			"cla"			=>	"application/vnd.claymore",
			"class"			=>	"application/java-vm",
			"clkk"			=>	"application/vnd.crick.clicker.keyboard",
			"clkp"			=>	"application/vnd.crick.clicker.palette",
			"clkt"			=>	"application/vnd.crick.clicker.template",
			"clkw"			=>	"application/vnd.crick.clicker.wordbank",
			"clkx"			=>	"application/vnd.crick.clicker",
			"clp"			=>	"application/x-msclip",
			"cmc"			=>	"application/vnd.cosmocaller",
			"cmdf"			=>	"chemical/x-cmdf",
			"cml"			=>	"chemical/x-cml",
			"cmp"			=>	"application/vnd.yellowriver-custom-menu",
			"cmx"			=>	"image/x-cmx",
			"cod"			=>	"application/vnd.rim.cod",
			"cpio"			=>	"application/x-cpio",
			"cpt"			=>	"application/mac-compactpro",
			"crd"			=>	"application/x-mscardfile",
			"crl"			=>	"application/pkix-crl",
			"cryptonote"		=>	"application/vnd.rig.cryptonote",
			"csh"			=>	"application/x-csh",
			"csml"			=>	"chemical/x-csml",
			"csp"			=>	"application/vnd.commonspace",
			"css"			=>	"text/css",
			"csv"			=>	"text/csv",
			"cu"			=>	"application/cu-seeme",
			"curl"			=>	"text/vnd.curl",
			"cww"			=>	"application/prs.cww",
			"dae"			=>	"model/vnd.collada+xml",
			"daf"			=>	"application/vnd.mobius.daf",
			"davmount"		=>	"application/davmount+xml",
			"dcurl"			=>	"text/vnd.curl.dcurl",
			"dd2"			=>	"application/vnd.oma.dd2+xml",
			"ddd"			=>	"application/vnd.fujixerox.ddd",
			"deb"			=>	"application/x-debian-package",
			"der"			=>	"application/x-x509-ca-cert",
			"dfac"			=>	"application/vnd.dreamfactory",
			"dir"			=>	"application/x-director",
			"dis"			=>	"application/vnd.mobius.dis",
			"djvu"			=>	"image/vnd.djvu",
			"dna"			=>	"application/vnd.dna",
			"doc"			=>	"application/msword",
			"docm"			=>	"application/vnd.ms-word.document.macroenabled.12",
			"docx"			=>	"application/vnd.openxmlformats-officedocument.wordprocessingml.document",
			"dotm"			=>	"application/vnd.ms-word.template.macroenabled.12",
			"dotx"			=>	"application/vnd.openxmlformats-officedocument.wordprocessingml.template",
			"dp"			=>	"application/vnd.osgi.dp",
			"dpg"			=>	"application/vnd.dpgraph",
			"dra"			=>	"audio/vnd.dra",
			"dsc"			=>	"text/prs.lines.tag",
			"dssc"			=>	"application/dssc+der",
			"dtb"			=>	"application/x-dtbook+xml",
			"dtd"			=>	"application/xml-dtd",
			"dts"			=>	"audio/vnd.dts",
			"dtshd"			=>	"audio/vnd.dts.hd",
			"dvi"			=>	"application/x-dvi",
			"dwf"			=>	"model/vnd.dwf",
			"dwg"			=>	"image/vnd.dwg",
			"dxf"			=>	"image/vnd.dxf",
			"dxp"			=>	"application/vnd.spotfire.dxp",
			"ecelp4800"		=>	"audio/vnd.nuera.ecelp4800",
			"ecelp7470"		=>	"audio/vnd.nuera.ecelp7470",
			"ecelp9600"		=>	"audio/vnd.nuera.ecelp9600",
			"edm"			=>	"application/vnd.novadigm.edm",
			"edx"			=>	"application/vnd.novadigm.edx",
			"efif"			=>	"application/vnd.picsel",
			"ei6"			=>	"application/vnd.pg.osasli",
			"eml"			=>	"message/rfc822",
			"emma"			=>	"application/emma+xml",
			"eol"			=>	"audio/vnd.digital-winds",
			"eot"			=>	"application/vnd.ms-fontobject",
			"epub"			=>	"application/epub+zip",
			"es"			=>	"application/ecmascript",
			"es3"			=>	"application/vnd.eszigno3+xml",
			"esf"			=>	"application/vnd.epson.esf",
			"etx"			=>	"text/x-setext",
			"exe"			=>	"application/x-msdownload",
			"exi"			=>	"application/exi",
			"ext"			=>	"application/vnd.novadigm.ext",
			"ez2"			=>	"application/vnd.ezpix-album",
			"ez3"			=>	"application/vnd.ezpix-package",
			"f"			=>	"text/x-fortran",
			"f4v"			=>	"video/x-f4v",
			"fbs"			=>	"image/vnd.fastbidsheet",
			"fcs"			=>	"application/vnd.isac.fcs",
			"fdf"			=>	"application/vnd.fdf",
			"fe_launch"		=>	"application/vnd.denovo.fcselayout-link",
			"fg5"			=>	"application/vnd.fujitsu.oasysgp",
			"fh"			=>	"image/x-freehand",
			"fig"			=>	"application/x-xfig",
			"fli"			=>	"video/x-fli",
			"flo"			=>	"application/vnd.micrografx.flo",
			"flv"			=>	"video/x-flv",
			"flw"			=>	"application/vnd.kde.kivio",
			"flx"			=>	"text/vnd.fmi.flexstor",
			"fly"			=>	"text/vnd.fly",
			"fm"			=>	"application/vnd.framemaker",
			"fnc"			=>	"application/vnd.frogans.fnc",
			"fpx"			=>	"image/vnd.fpx",
			"fsc"			=>	"application/vnd.fsc.weblaunch",
			"fst"			=>	"image/vnd.fst",
			"ftc"			=>	"application/vnd.fluxtime.clip",
			"fti"			=>	"application/vnd.anser-web-funds-transfer-initiation",
			"fvt"			=>	"video/vnd.fvt",
			"fxp"			=>	"application/vnd.adobe.fxp",
			"fzs"			=>	"application/vnd.fuzzysheet",
			"g2w"			=>	"application/vnd.geoplan",
			"g3"			=>	"image/g3fax",
			"g3w"			=>	"application/vnd.geospace",
			"gac"			=>	"application/vnd.groove-account",
			"gdl"			=>	"model/vnd.gdl",
			"geo"			=>	"application/vnd.dynageo",
			"gex"			=>	"application/vnd.geometry-explorer",
			"ggb"			=>	"application/vnd.geogebra.file",
			"ggt"			=>	"application/vnd.geogebra.tool",
			"ghf"			=>	"application/vnd.groove-help",
			"gif"			=>	"image/gif",
			"gim"			=>	"application/vnd.groove-identity-message",
			"gmx"			=>	"application/vnd.gmx",
			"gnumeric"		=>	"application/x-gnumeric",
			"gph"			=>	"application/vnd.flographit",
			"gqf"			=>	"application/vnd.grafeq",
			"gram"			=>	"application/srgs",
			"grv"			=>	"application/vnd.groove-injector",
			"grxml"			=>	"application/srgs+xml",
			"gsf"			=>	"application/x-font-ghostscript",
			"gtar"			=>	"application/x-gtar",
			"gtm"			=>	"application/vnd.groove-tool-message",
			"gtw"			=>	"model/vnd.gtw",
			"gv"			=>	"text/vnd.graphviz",
			"gxt"			=>	"application/vnd.geonext",
			"h261"			=>	"video/h261",
			"h263"			=>	"video/h263",
			"h264"			=>	"video/h264",
			"hal"			=>	"application/vnd.hal+xml",
			"hbci"			=>	"application/vnd.hbci",
			"hdf"			=>	"application/x-hdf",
			"hlp"			=>	"application/winhlp",
			"hpgl"			=>	"application/vnd.hp-hpgl",
			"hpid"			=>	"application/vnd.hp-hpid",
			"hps"			=>	"application/vnd.hp-hps",
			"hqx"			=>	"application/mac-binhex40",
			"htke"			=>	"application/vnd.kenameaapp",
			"html"			=>	"text/html",
			"hvd"			=>	"application/vnd.yamaha.hv-dic",
			"hvp"			=>	"application/vnd.yamaha.hv-voice",
			"hvs"			=>	"application/vnd.yamaha.hv-script",
			"i2g"			=>	"application/vnd.intergeo",
			"icc"			=>	"application/vnd.iccprofile",
			"ice"			=>	"x-conference/x-cooltalk",
			"ico"			=>	"image/x-icon",
			"ics"			=>	"text/calendar",
			"ief"			=>	"image/ief",
			"ifm"			=>	"application/vnd.shana.informed.formdata",
			"igl"			=>	"application/vnd.igloader",
			"igm"			=>	"application/vnd.insors.igm",
			"igs"			=>	"model/iges",
			"igx"			=>	"application/vnd.micrografx.igx",
			"iif"			=>	"application/vnd.shana.informed.interchange",
			"imp"			=>	"application/vnd.accpac.simply.imp",
			"ims"			=>	"application/vnd.ms-ims",
			"ipfix"			=>	"application/ipfix",
			"ipk"			=>	"application/vnd.shana.informed.package",
			"irm"			=>	"application/vnd.ibm.rights-management",
			"irp"			=>	"application/vnd.irepository.package+xml",
			"itp"			=>	"application/vnd.shana.informed.formtemplate",
			"ivp"			=>	"application/vnd.immervision-ivp",
			"ivu"			=>	"application/vnd.immervision-ivu",
			"jad"			=>	"text/vnd.sun.j2me.app-descriptor",
			"jam"			=>	"application/vnd.jam",
			"jar"			=>	"application/java-archive",
			"java"			=>	"text/x-java-source,java",
			"jisp"			=>	"application/vnd.jisp",
			"jlt"			=>	"application/vnd.hp-jlyt",
			"jnlp"			=>	"application/x-java-jnlp-file",
			"joda"			=>	"application/vnd.joost.joda-archive",
			"jpeg"			=>	"image/jpeg",
			"jpg"			=>	"image/jpeg",
			"jpgv"			=>	"video/jpeg",
			"jpm"			=>	"video/jpm",
			"js"			=>	"application/javascript",
			"json"			=>	"application/json",
			"karbon"		=>	"application/vnd.kde.karbon",
			"kfo"			=>	"application/vnd.kde.kformula",
			"kia"			=>	"application/vnd.kidspiration",
			"kml"			=>	"application/vnd.google-earth.kml+xml",
			"kmz"			=>	"application/vnd.google-earth.kmz",
			"kne"			=>	"application/vnd.kinar",
			"kon"			=>	"application/vnd.kde.kontour",
			"kpr"			=>	"application/vnd.kde.kpresenter",
			"ksp"			=>	"application/vnd.kde.kspread",
			"ktx"			=>	"image/ktx",
			"ktz"			=>	"application/vnd.kahootz",
			"kwd"			=>	"application/vnd.kde.kword",
			"lasxml"		=>	"application/vnd.las.las+xml",
			"latex"			=>	"application/x-latex",
			"lbd"			=>	"application/vnd.llamagraphics.life-balance.desktop",
			"lbe"			=>	"application/vnd.llamagraphics.life-balance.exchange+xml",
			"les"			=>	"application/vnd.hhe.lesson-player",
			"link66"		=>	"application/vnd.route66.link66+xml",
			"lrm"			=>	"application/vnd.ms-lrm",
			"ltf"			=>	"application/vnd.frogans.ltf",
			"lvp"			=>	"audio/vnd.lucent.voice",
			"lwp"			=>	"application/vnd.lotus-wordpro",
			"m21"			=>	"application/mp21",
			"m3u"			=>	"audio/x-mpegurl",
			"m3u8"			=>	"application/vnd.apple.mpegurl",
			"m4v"			=>	"video/x-m4v",
			"ma"			=>	"application/mathematica",
			"mads"			=>	"application/mads+xml",
			"mag"			=>	"application/vnd.ecowin.chart",
			"map"			=>	"application/json",
			"mathml"		=>	"application/mathml+xml",
			"mbk"			=>	"application/vnd.mobius.mbk",
			"mbox"			=>	"application/mbox",
			"mc1"			=>	"application/vnd.medcalcdata",
			"mcd"			=>	"application/vnd.mcd",
			"mcurl"			=>	"text/vnd.curl.mcurl",
			"md"			=>	"text/x-markdown", // http://bit.ly/1Kc5nUB
			"mdb"			=>	"application/x-msaccess",
			"mdi"			=>	"image/vnd.ms-modi",
			"meta4"			=>	"application/metalink4+xml",
			"mets"			=>	"application/mets+xml",
			"mfm"			=>	"application/vnd.mfmp",
			"mgp"			=>	"application/vnd.osgeo.mapguide.package",
			"mgz"			=>	"application/vnd.proteus.magazine",
			"mid"			=>	"audio/midi",
			"mif"			=>	"application/vnd.mif",
			"mj2"			=>	"video/mj2",
			"mlp"			=>	"application/vnd.dolby.mlp",
			"mmd"			=>	"application/vnd.chipnuts.karaoke-mmd",
			"mmf"			=>	"application/vnd.smaf",
			"mmr"			=>	"image/vnd.fujixerox.edmics-mmr",
			"mny"			=>	"application/x-msmoney",
			"mods"			=>	"application/mods+xml",
			"movie"			=>	"video/x-sgi-movie",
			"mp1"			=>	"audio/mpeg",
			"mp2"			=>	"audio/mpeg",
			"mp3"			=>	"audio/mpeg",
			"mp4"			=>	"video/mp4",
			"mp4a"			=>	"audio/mp4",
			"mpc"			=>	"application/vnd.mophun.certificate",
			"mpeg"			=>	"video/mpeg",
			"mpga"			=>	"audio/mpeg",
			"mpkg"			=>	"application/vnd.apple.installer+xml",
			"mpm"			=>	"application/vnd.blueice.multipass",
			"mpn"			=>	"application/vnd.mophun.application",
			"mpp"			=>	"application/vnd.ms-project",
			"mpy"			=>	"application/vnd.ibm.minipay",
			"mqy"			=>	"application/vnd.mobius.mqy",
			"mrc"			=>	"application/marc",
			"mrcx"			=>	"application/marcxml+xml",
			"mscml"			=>	"application/mediaservercontrol+xml",
			"mseq"			=>	"application/vnd.mseq",
			"msf"			=>	"application/vnd.epson.msf",
			"msh"			=>	"model/mesh",
			"msl"			=>	"application/vnd.mobius.msl",
			"msty"			=>	"application/vnd.muvee.style",
			"mts"			=>	"model/vnd.mts",
			"mus"			=>	"application/vnd.musician",
			"musicxml"		=>	"application/vnd.recordare.musicxml+xml",
			"mvb"			=>	"application/x-msmediaview",
			"mwf"			=>	"application/vnd.mfer",
			"mxf"			=>	"application/mxf",
			"mxl"			=>	"application/vnd.recordare.musicxml",
			"mxml"			=>	"application/xv+xml",
			"mxs"			=>	"application/vnd.triscape.mxs",
			"mxu"			=>	"video/vnd.mpegurl",
			"n3"			=>	"text/n3",
			"nbp"			=>	"application/vnd.wolfram.player",
			"nc"			=>	"application/x-netcdf",
			"ncx"			=>	"application/x-dtbncx+xml",
			"n-gage"		=>	"application/vnd.nokia.n-gage.symbian.install",
			"ngdat"			=>	"application/vnd.nokia.n-gage.data",
			"nlu"			=>	"application/vnd.neurolanguage.nlu",
			"nml"			=>	"application/vnd.enliven",
			"nnd"			=>	"application/vnd.noblenet-directory",
			"nns"			=>	"application/vnd.noblenet-sealer",
			"nnw"			=>	"application/vnd.noblenet-web",
			"npx"			=>	"image/vnd.net-fpx",
			"nsf"			=>	"application/vnd.lotus-notes",
			"oa2"			=>	"application/vnd.fujitsu.oasys2",
			"oa3"			=>	"application/vnd.fujitsu.oasys3",
			"oas"			=>	"application/vnd.fujitsu.oasys",
			"obd"			=>	"application/x-msbinder",
			"oda"			=>	"application/oda",
			"odb"			=>	"application/vnd.oasis.opendocument.database",
			"odc"			=>	"application/vnd.oasis.opendocument.chart",
			"odf"			=>	"application/vnd.oasis.opendocument.formula",
			"odft"			=>	"application/vnd.oasis.opendocument.formula-template",
			"odg"			=>	"application/vnd.oasis.opendocument.graphics",
			"odi"			=>	"application/vnd.oasis.opendocument.image",
			"odm"			=>	"application/vnd.oasis.opendocument.text-master",
			"odp"			=>	"application/vnd.oasis.opendocument.presentation",
			"ods"			=>	"application/vnd.oasis.opendocument.spreadsheet",
			"odt"			=>	"application/vnd.oasis.opendocument.text",
			"oga"			=>	"audio/ogg",
			"ogv"			=>	"video/ogg",
			"ogx"			=>	"application/ogg",
			"onetoc"		=>	"application/onenote",
			"opf"			=>	"application/oebps-package+xml",
			"org"			=>	"application/vnd.lotus-organizer",
			"osf"			=>	"application/vnd.yamaha.openscoreformat",
			"osfpvg"		=>	"application/vnd.yamaha.openscoreformat.osfpvg+xml",
			"otc"			=>	"application/vnd.oasis.opendocument.chart-template",
			"otf"			=>	"application/x-font-otf",
			"otg"			=>	"application/vnd.oasis.opendocument.graphics-template",
			"oth"			=>	"application/vnd.oasis.opendocument.text-web",
			"oti"			=>	"application/vnd.oasis.opendocument.image-template",
			"otp"			=>	"application/vnd.oasis.opendocument.presentation-template",
			"ots"			=>	"application/vnd.oasis.opendocument.spreadsheet-template",
			"ott"			=>	"application/vnd.oasis.opendocument.text-template",
			"oxt"			=>	"application/vnd.openofficeorg.extension",
			"p"			=>	"text/x-pascal",
			"p10"			=>	"application/pkcs10",
			"p12"			=>	"application/x-pkcs12",
			"p7b"			=>	"application/x-pkcs7-certificates",
			"p7m"			=>	"application/pkcs7-mime",
			"p7r"			=>	"application/x-pkcs7-certreqresp",
			"p7s"			=>	"application/pkcs7-signature",
			"p8"			=>	"application/pkcs8",
			"par"			=>	"text/plain-bas",
			"paw"			=>	"application/vnd.pawaafile",
			"pbd"			=>	"application/vnd.powerbuilder6",
			"pbm"			=>	"image/x-portable-bitmap",
			"pcf"			=>	"application/x-font-pcf",
			"pcl"			=>	"application/vnd.hp-pcl",
			"pclxl"			=>	"application/vnd.hp-pclxl",
			"pcurl"			=>	"application/vnd.curl.pcurl",
			"pcx"			=>	"image/x-pcx",
			"pdb"			=>	"application/vnd.palm",
			"pdf"			=>	"application/pdf",
			"pfa"			=>	"application/x-font-type1",
			"pfr"			=>	"application/font-tdpfr",
			"pgm"			=>	"image/x-portable-graymap",
			"pgn"			=>	"application/x-chess-pgn",
			"pgp"			=>	"application/pgp-signature",
			"pic"			=>	"image/x-pict",
			"pki"			=>	"application/pkixcmp",
			"pkipath"		=>	"application/pkix-pkipath",
			"plb"			=>	"application/vnd.3gpp.pic-bw-large",
			"plc"			=>	"application/vnd.mobius.plc",
			"plf"			=>	"application/vnd.pocketlearn",
			"pls"			=>	"application/pls+xml",
			"pml"			=>	"application/vnd.ctc-posml",
			"png"			=>	"image/png",
			"pnm"			=>	"image/x-portable-anymap",
			"portpkg"		=>	"application/vnd.macports.portpkg",
			"potm"			=>	"application/vnd.ms-powerpoint.template.macroenabled.12",
			"potx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.template",
			"ppam"			=>	"application/vnd.ms-powerpoint.addin.macroenabled.12",
			"ppd"			=>	"application/vnd.cups-ppd",
			"ppm"			=>	"image/x-portable-pixmap",
			"ppsm"			=>	"application/vnd.ms-powerpoint.slideshow.macroenabled.12",
			"ppsx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.slideshow",
			"ppt"			=>	"application/vnd.ms-powerpoint",
			"pptm"			=>	"application/vnd.ms-powerpoint.presentation.macroenabled.12",
			"pptx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.presentation",
			"prc"			=>	"application/x-mobipocket-ebook",
			"pre"			=>	"application/vnd.lotus-freelance",
			"prf"			=>	"application/pics-rules",
			"psb"			=>	"application/vnd.3gpp.pic-bw-small",
			"psd"			=>	"image/vnd.adobe.photoshop",
			"psf"			=>	"application/x-font-linux-psf",
			"pskcxml"		=>	"application/pskc+xml",
			"ptid"			=>	"application/vnd.pvi.ptid1",
			"pub"			=>	"application/x-mspublisher",
			"pvb"			=>	"application/vnd.3gpp.pic-bw-var",
			"pwn"			=>	"application/vnd.3m.post-it-notes",
			"pya"			=>	"audio/vnd.ms-playready.media.pya",
			"pyv"			=>	"video/vnd.ms-playready.media.pyv",
			"qam"			=>	"application/vnd.epson.quickanime",
			"qbo"			=>	"application/vnd.intu.qbo",
			"qfx"			=>	"application/vnd.intu.qfx",
			"qps"			=>	"application/vnd.publishare-delta-tree",
			"qt"			=>	"video/quicktime",
			"qxd"			=>	"application/vnd.quark.quarkxpress",
			"ram"			=>	"audio/x-pn-realaudio",
			"rar"			=>	"application/x-rar-compressed",
			"ras"			=>	"image/x-cmu-raster",
			"rcprofile"		=>	"application/vnd.ipunplugged.rcprofile",
			"rdf"			=>	"application/rdf+xml",
			"rdz"			=>	"application/vnd.data-vision.rdz",
			"rep"			=>	"application/vnd.businessobjects",
			"res"			=>	"application/x-dtbresource+xml",
			"rgb"			=>	"image/x-rgb",
			"rif"			=>	"application/reginfo+xml",
			"rip"			=>	"audio/vnd.rip",
			"rl"			=>	"application/resource-lists+xml",
			"rlc"			=>	"image/vnd.fujixerox.edmics-rlc",
			"rld"			=>	"application/resource-lists-diff+xml",
			"rm"			=>	"application/vnd.rn-realmedia",
			"rmp"			=>	"audio/x-pn-realaudio-plugin",
			"rms"			=>	"application/vnd.jcp.javame.midlet-rms",
			"rnc"			=>	"application/relax-ng-compact-syntax",
			"rp9"			=>	"application/vnd.cloanto.rp9",
			"rpss"			=>	"application/vnd.nokia.radio-presets",
			"rpst"			=>	"application/vnd.nokia.radio-preset",
			"rq"			=>	"application/sparql-query",
			"rs"			=>	"application/rls-services+xml",
			"rsd"			=>	"application/rsd+xml",
			"rss"			=>	"application/rss+xml",
			"rtf"			=>	"application/rtf",
			"rtx"			=>	"text/richtext",
			"s"			=>	"text/x-asm",
			"saf"			=>	"application/vnd.yamaha.smaf-audio",
			"sbml"			=>	"application/sbml+xml",
			"sc"			=>	"application/vnd.ibm.secure-container",
			"scd"			=>	"application/x-msschedule",
			"scm"			=>	"application/vnd.lotus-screencam",
			"scq"			=>	"application/scvp-cv-request",
			"scs"			=>	"application/scvp-cv-response",
			"scurl"			=>	"text/vnd.curl.scurl",
			"sda"			=>	"application/vnd.stardivision.draw",
			"sdc"			=>	"application/vnd.stardivision.calc",
			"sdd"			=>	"application/vnd.stardivision.impress",
			"sdkm"			=>	"application/vnd.solent.sdkm+xml",
			"sdp"			=>	"application/sdp",
			"sdw"			=>	"application/vnd.stardivision.writer",
			"see"			=>	"application/vnd.seemail",
			"seed"			=>	"application/vnd.fdsn.seed",
			"sema"			=>	"application/vnd.sema",
			"semd"			=>	"application/vnd.semd",
			"semf"			=>	"application/vnd.semf",
			"ser"			=>	"application/java-serialized-object",
			"setpay"		=>	"application/set-payment-initiation",
			"setreg"		=>	"application/set-registration-initiation",
			"sfd-hdstx"		=>	"application/vnd.hydrostatix.sof-data",
			"sfs"			=>	"application/vnd.spotfire.sfs",
			"sgl"			=>	"application/vnd.stardivision.writer-global",
			"sgml"			=>	"text/sgml",
			"sh"			=>	"application/x-sh",
			"shar"			=>	"application/x-shar",
			"shf"			=>	"application/shf+xml",
			"sis"			=>	"application/vnd.symbian.install",
			"sit"			=>	"application/x-stuffit",
			"sitx"			=>	"application/x-stuffitx",
			"skp"			=>	"application/vnd.koan",
			"sldm"			=>	"application/vnd.ms-powerpoint.slide.macroenabled.12",
			"sldx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.slide",
			"slt"			=>	"application/vnd.epson.salt",
			"sm"			=>	"application/vnd.stepmania.stepchart",
			"smf"			=>	"application/vnd.stardivision.math",
			"smi"			=>	"application/smil+xml",
			"snf"			=>	"application/x-font-snf",
			"spf"			=>	"application/vnd.yamaha.smaf-phrase",
			"spl"			=>	"application/x-futuresplash",
			"spot"			=>	"text/vnd.in3d.spot",
			"spp"			=>	"application/scvp-vp-response",
			"spq"			=>	"application/scvp-vp-request",
			"src"			=>	"application/x-wais-source",
			"sru"			=>	"application/sru+xml",
			"srx"			=>	"application/sparql-results+xml",
			"sse"			=>	"application/vnd.kodak-descriptor",
			"ssf"			=>	"application/vnd.epson.ssf",
			"ssml"			=>	"application/ssml+xml",
			"st"			=>	"application/vnd.sailingtracker.track",
			"stc"			=>	"application/vnd.sun.xml.calc.template",
			"std"			=>	"application/vnd.sun.xml.draw.template",
			"stf"			=>	"application/vnd.wt.stf",
			"sti"			=>	"application/vnd.sun.xml.impress.template",
			"stk"			=>	"application/hyperstudio",
			"stl"			=>	"application/vnd.ms-pki.stl",
			"str"			=>	"application/vnd.pg.format",
			"stw"			=>	"application/vnd.sun.xml.writer.template",
			"sub"			=>	"image/vnd.dvb.subtitle",
			"sus"			=>	"application/vnd.sus-calendar",
			"sv4cpio"		=>	"application/x-sv4cpio",
			"sv4crc"		=>	"application/x-sv4crc",
			"svc"			=>	"application/vnd.dvb.service",
			"svd"			=>	"application/vnd.svd",
			"svg"			=>	"image/svg+xml",
			"swf"			=>	"application/x-shockwave-flash",
			"swi"			=>	"application/vnd.aristanetworks.swi",
			"sxc"			=>	"application/vnd.sun.xml.calc",
			"sxd"			=>	"application/vnd.sun.xml.draw",
			"sxg"			=>	"application/vnd.sun.xml.writer.global",
			"sxi"			=>	"application/vnd.sun.xml.impress",
			"sxm"			=>	"application/vnd.sun.xml.math",
			"sxw"			=>	"application/vnd.sun.xml.writer",
			"t"			=>	"text/troff",
			"tao"			=>	"application/vnd.tao.intent-module-archive",
			"tar"			=>	"application/x-tar",
			"tcap"			=>	"application/vnd.3gpp2.tcap",
			"tcl"			=>	"application/x-tcl",
			"teacher"		=>	"application/vnd.smart.teacher",
			"tei"			=>	"application/tei+xml",
			"tex"			=>	"application/x-tex",
			"texinfo"		=>	"application/x-texinfo",
			"tfi"			=>	"application/thraud+xml",
			"tfm"			=>	"application/x-tex-tfm",
			"thmx"			=>	"application/vnd.ms-officetheme",
			"tiff"			=>	"image/tiff",
			"tmo"			=>	"application/vnd.tmobile-livetv",
			"torrent"		=>	"application/x-bittorrent",
			"tpl"			=>	"application/vnd.groove-tool-template",
			"tpt"			=>	"application/vnd.trid.tpt",
			"tra"			=>	"application/vnd.trueapp",
			"trm"			=>	"application/x-msterminal",
			"tsd"			=>	"application/timestamped-data",
			"tsv"			=>	"text/tab-separated-values",
			"ttf"			=>	"application/x-font-ttf",
			"ttl"			=>	"text/turtle",
			"twd"			=>	"application/vnd.simtech-mindmapper",
			"txd"			=>	"application/vnd.genomatix.tuxedo",
			"txf"			=>	"application/vnd.mobius.txf",
			"txt"			=>	"text/plain",
			"ufd"			=>	"application/vnd.ufdl",
			"umj"			=>	"application/vnd.umajin",
			"unityweb"		=>	"application/vnd.unity",
			"uoml"			=>	"application/vnd.uoml+xml",
			"uri"			=>	"text/uri-list",
			"ustar"			=>	"application/x-ustar",
			"utz"			=>	"application/vnd.uiq.theme",
			"uu"			=>	"text/x-uuencode",
			"uva"			=>	"audio/vnd.dece.audio",
			"uvh"			=>	"video/vnd.dece.hd",
			"uvi"			=>	"image/vnd.dece.graphic",
			"uvm"			=>	"video/vnd.dece.mobile",
			"uvp"			=>	"video/vnd.dece.pd",
			"uvs"			=>	"video/vnd.dece.sd",
			"uvu"			=>	"video/vnd.uvvu.mp4",
			"uvv"			=>	"video/vnd.dece.video",
			"vcd"			=>	"application/x-cdlink",
			"vcf"			=>	"text/x-vcard",
			"vcg"			=>	"application/vnd.groove-vcard",
			"vcs"			=>	"text/x-vcalendar",
			"vcx"			=>	"application/vnd.vcx",
			"vis"			=>	"application/vnd.visionary",
			"viv"			=>	"video/vnd.vivo",
			"vsd"			=>	"application/vnd.visio",
			"vsf"			=>	"application/vnd.vsf",
			"vtu"			=>	"model/vnd.vtu",
			"vxml"			=>	"application/voicexml+xml",
			"wad"			=>	"application/x-doom",
			"wav"			=>	"audio/x-wav",
			"wax"			=>	"audio/x-ms-wax",
			"wbmp"			=>	"image/vnd.wap.wbmp",
			"wbs"			=>	"application/vnd.criticaltools.wbs+xml",
			"wbxml"			=>	"application/vnd.wap.wbxml",
			"weba"			=>	"audio/webm",
			"webm"			=>	"video/webm",
			"webp"			=>	"image/webp",
			"wg"			=>	"application/vnd.pmi.widget",
			"wgt"			=>	"application/widget",
			"wm"			=>	"video/x-ms-wm",
			"wma"			=>	"audio/x-ms-wma",
			"wmd"			=>	"application/x-ms-wmd",
			"wmf"			=>	"application/x-msmetafile",
			"wml"			=>	"text/vnd.wap.wml",
			"wmlc"			=>	"application/vnd.wap.wmlc",
			"wmls"			=>	"text/vnd.wap.wmlscript",
			"wmlsc"			=>	"application/vnd.wap.wmlscriptc",
			"wmv"			=>	"video/x-ms-wmv",
			"wmx"			=>	"video/x-ms-wmx",
			"wmz"			=>	"application/x-ms-wmz",
			"woff"			=>	"application/x-font-woff",
			"woff2"			=>	"application/font-woff2",
			"wpd"			=>	"application/vnd.wordperfect",
			"wpl"			=>	"application/vnd.ms-wpl",
			"wps"			=>	"application/vnd.ms-works",
			"wqd"			=>	"application/vnd.wqd",
			"wri"			=>	"application/x-mswrite",
			"wrl"			=>	"model/vrml",
			"wsdl"			=>	"application/wsdl+xml",
			"wspolicy"		=>	"application/wspolicy+xml",
			"wtb"			=>	"application/vnd.webturbo",
			"wvx"			=>	"video/x-ms-wvx",
			"x3d"			=>	"application/vnd.hzn-3d-crossword",
			"xap"			=>	"application/x-silverlight-app",
			"xar"			=>	"application/vnd.xara",
			"xbap"			=>	"application/x-ms-xbap",
			"xbd"			=>	"application/vnd.fujixerox.docuworks.binder",
			"xbm"			=>	"image/x-xbitmap",
			"xdf"			=>	"application/xcap-diff+xml",
			"xdm"			=>	"application/vnd.syncml.dm+xml",
			"xdp"			=>	"application/vnd.adobe.xdp+xml",
			"xdssc"			=>	"application/dssc+xml",
			"xdw"			=>	"application/vnd.fujixerox.docuworks",
			"xenc"			=>	"application/xenc+xml",
			"xer"			=>	"application/patch-ops-error+xml",
			"xfdf"			=>	"application/vnd.adobe.xfdf",
			"xfdl"			=>	"application/vnd.xfdl",
			"xhtml"			=>	"application/xhtml+xml",
			"xif"			=>	"image/vnd.xiff",
			"xlam"			=>	"application/vnd.ms-excel.addin.macroenabled.12",
			"xls"			=>	"application/vnd.ms-excel",
			"xlsb"			=>	"application/vnd.ms-excel.sheet.binary.macroenabled.12",
			"xlsm"			=>	"application/vnd.ms-excel.sheet.macroenabled.12",
			"xlsx"			=>	"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
			"xltm"			=>	"application/vnd.ms-excel.template.macroenabled.12",
			"xltx"			=>	"application/vnd.openxmlformats-officedocument.spreadsheetml.template",
			"xml"			=>	"application/xml",
			"xo"			=>	"application/vnd.olpc-sugar",
			"xop"			=>	"application/xop+xml",
			"xpi"			=>	"application/x-xpinstall",
			"xpm"			=>	"image/x-xpixmap",
			"xpr"			=>	"application/vnd.is-xpr",
			"xps"			=>	"application/vnd.ms-xpsdocument",
			"xpw"			=>	"application/vnd.intercon.formnet",
			"xslt"			=>	"application/xslt+xml",
			"xsm"			=>	"application/vnd.syncml+xml",
			"xspf"			=>	"application/xspf+xml",
			"xul"			=>	"application/vnd.mozilla.xul+xml",
			"xwd"			=>	"image/x-xwindowdump",
			"xyz"			=>	"chemical/x-xyz",
			"yaml"			=>	"text/yaml",
			"yang"			=>	"application/yang",
			"yin"			=>	"application/yin+xml",
			"zaz"			=>	"application/vnd.zzazz.deck+xml",
			"zip"			=>	"application/zip",
			"zir"			=>	"application/vnd.zul",
			"zmm"			=>	"application/vnd.handheld-entertainment+xml"
		);

		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		if (isset($mime_type[$extension])) {
			return $mime_type[$extension];
		} else {
			//If we cant find a type we are a stream
			return "application/octet-stream";
		}

	}

	function gettrueip(){
		$ip="0.0.0.0";

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){ if (filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)){ if ($ip=="0.0.0.0"){ $ip=$_SERVER['HTTP_X_FORWARDED_FOR']; }}}

		if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])){ if (filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)){ if ($ip=="0.0.0.0"){ $ip=$_SERVER['HTTP_CF_CONNECTING_IP']; }}}

		if (isset($_SERVER['Cf-Connecting-IP'])){ if (filter_var($_SERVER['Cf-Connecting-IP'], FILTER_VALIDATE_IP)){ if ($ip=="0.0.0.0"){ $ip=$_SERVER['Cf-Connecting-IP']; }}}

		if (isset($_SERVER['HTTP_CLIENT_IP'])){ if (filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)){ if ($ip=="0.0.0.0"){ $ip=$_SERVER['HTTP_CLIENT_IP']; }}}

		if (isset($_SERVER['REMOTE_ADDR'])){ if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)){ if ($ip=="0.0.0.0"){ $ip=$_SERVER['REMOTE_ADDR']; }}}

    return $ip;
}

//--Recursively merge two arrays with overwrites
function array_merge_recursive_distinct($arr1, $arr2){
	foreach($arr2 as $key => $value){
  	if (array_key_exists($key, $arr1) && is_array($value)){
      $arr1[$key] = array_merge_recursive_distinct($arr1[$key], $arr2[$key]);
    }else{
      $arr1[$key] = $value;
		}
  }
  return $arr1;
}

//##############################################################################################################
//##############################################################################################################-- Startup, Arrays
//##############################################################################################################
	error_reporting(0);
	//ini_set('display_errors', '0');
	//set_error_handler("customError");
	ini_set('default_charset', 'UTF-8');
	ini_set('display_errors', false);
	ignore_user_abort(true);
	set_time_limit(120);
	session_name('coss');
	$system=array();
	$settings="";
	$system["time_start"] = microtime_float();
	$system["url"]="";
	$system["location"]=dirname(__FILE__);
	$system["url_code"]=false;
	$system["useragent"]=$_SERVER['HTTP_USER_AGENT'];
	$system["ip"]=gettrueip();
	$system["debug"]=true;
	$system["debug_log"]="";
	$system["id"]=0;

	if (isset($_FILES['file'])){
		if (is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['error']==0) {
			$system["uploaded_file"]=$_FILES['file'];
		}else{
			$system["uploaded_file"]=false;
		}
	}else{
		$system["uploaded_file"]=false;
	}

	$cache=array();
	$settings=json_decode(utf8_encode(file_get_contents('./conf.json', FILE_USE_INCLUDE_PATH)), true); //Fetch Config Data
	$settings_update=false;
	$storage=json_decode(utf8_encode(file_get_contents('./storage.json', FILE_USE_INCLUDE_PATH)), true); //Fetch Config Data
	$storage_update=false;
	$system_data=json_decode(utf8_encode(file_get_contents('./data.json', FILE_USE_INCLUDE_PATH)), true); //Fetch Config Data
	$system_data_update=false;

	if (file_exists(dirname(__FILE__).$settings["location_code"].'/ssrules.json')){
		$ssrules=json_decode(utf8_encode(file_get_contents(''.dirname(__FILE__).''.$settings["location_code"].'/ssrules.json', FILE_USE_INCLUDE_PATH)), true); //SS Rules
	}else{
		$ssrules=array();
	}
	if (isset($_COOKIE["coss"])){
		$system["session"]=$_COOKIE["coss"];
	}else{
		$system["session"]=codegenerate(40);
	}
	session_id($system["session"]);
	session_start();
	ob_start("ob_gzhandler");
	header('Access-Control-Allow-Origin: *');
	ini_set('date.timezone', 'America/New_York');
	header('X-Powered-By: SimpleScript');
	//register_shutdown_function('shutdown');
	//--System data
	$system["runpath"]=dirname(__FILE__).$settings["location_code"];
	$system["debug"]=$settings["settings_coredebug"];
	function fixpath($p) {
		$p=str_replace('\\','/',trim($p));
		return (substr($p,-1)!='/') ? $p.='/' : $p;
	}

	//--Break down URL to get file needed
	if (isset($_SERVER["REQUEST_URI"])){
		$s=strtok(urldecode($_SERVER["REQUEST_URI"]),'?');
	}else{
		$s="index.ssc";
	}
	$u=explode("/",ltrim($s, '/'));
	while(list($key,$val)=each($u)){
		if (checkpreg("|([^\.]*)\.ssc|i",$val)==true){ $system["url_code"]=true; }
		if ($val==""){ $val="index.ssc"; $system["url_code"]=true; }
		$system["url"].="/".$val."";
	}

	if (isset($ssrules["redirect_all"])){
		if (checkpreg("|".$settings["admin_url"]."|i",$system["url"])==false){
			if (!file_exists(dirname(__FILE__).$settings["location_code"].$system["url"])){ //--If cant find a file matching lets see if a .ssc file is in place with that name
				$system["url"]=$ssrules["redirect_all"];
				$system["url_code"]=true;
			}
		}
	}else{
		if (!file_exists(dirname(__FILE__).$settings["location_code"].$system["url"])){ //--If cant find a file matching lets see if a .ssc file is in place with that name
			if (file_exists(dirname(__FILE__).$settings["location_code"].$system["url"].".ssc")){
				$system["url"].=".ssc";
				$system["url_code"]=true;
			}else{
				$urlmath=trim($system["url"],"/");
				$urltest=substr($urlmath, 0, strrpos( $urlmath, '/'));
				$urltest="/".$urltest."/ssc_special_catchall.ssc";
				if (file_exists(dirname(__FILE__).$settings["location_code"].$urltest)){
					$system["url_original"]=$system["url"];
					$system["url"]=$urltest;
					$system["url_code"]=true;
				}else{
					$urlmath=trim($system["url"],"/");
					$urlmath=substr($urlmath, 0, strrpos( $urlmath, '/'));
					$urltest=substr($urlmath, 0, strrpos( $urlmath, '/'));
					$urltest="/".$urltest."/ssc_special_catchall.ssc";
					if (file_exists(dirname(__FILE__).$settings["location_code"].$urltest)){
						$system["url_original"]=$system["url"];
						$system["url"]=$urltest;
						$system["url_code"]=true;
					}else{
						$urlmath=trim($system["url"],"/");
						$urlmath=substr($urlmath, 0, strrpos( $urlmath, '/'));
						$urltest=substr($urlmath, 0, strrpos( $urlmath, '/'));
						$urltest=substr($urlmath, 0, strrpos( $urlmath, '/'));
						$urltest="/".$urltest."/ssc_special_catchall.ssc";
						if (file_exists(dirname(__FILE__).$settings["location_code"].$urltest)){
							$system["url_original"]=$system["url"];
							$system["url"]=$urltest;
							$system["url_code"]=true;
						}
					}
				}
			}
		}
	}

//##############################################################################################################
//##############################################################################################################-- SS Functions
//##############################################################################################################
	require 'lib/2fa_auth.php';
	require 'lib/field_compute.php';
	require 'lib/parsedown.php';
	require 'core/sys_functions.php';
	require 'core/templates.php';
	require 'core/code_functions.php';
	require 'core/code_variables.php';
	require 'core/run_linebyline.php';

	function ss_register_system_variables(){

		foreach($_POST as $key => $value){
			ss_code_variables_save("post",$key,encode_makesafe_ss_input($value));
		}

		foreach($_POST as $key => $value){
			ss_code_variables_save("post",$key."_base64",strtr(base64_encode($value), '+/=','-_,'));
		}

		foreach($_GET as $key => $value){
			ss_code_variables_save("url",$key,encode_makesafe_ss_input(urldecode($value)));
		}

		foreach($_SESSION as $key => $value){
			ss_code_variables_save("session",$key,encode_makesafe_ss_input($value));
		}

		foreach($_COOKIE as $key => $value){
			ss_code_variables_save("cookie",$key,encode_makesafe_ss_input($value));
		}

	}

	function ss_cleanup_system_variables(){
		global $ss_variables;

		if (isset($ss_variables["session"])){
			foreach($ss_variables["session"] as $key => $value){
				$_SESSION[$key]=$value;
			}
		}

	}

	function ss_runscript($file){
		if (file_exists($file)){

			//--Prepare system
			ss_register_system_variables(); //--System varibles like _GET and _POST

			$t=file_get_contents($file, FILE_USE_INCLUDE_PATH);
			$t=removeblank($t);
			$t=ss_sys_function_prerun($t);

			$t=ss_run_linebyline($t);

			$t=ss_template_postrun($t);

			echo decode_makesafe_ss_input($t);

			ss_cleanup_system_variables();

		}else{
			customError(404,"Sorry but we are unable to find the file requested.");
		}
	}

	//##############################################################################################################
	//##############################################################################################################-- Check for what page we are loading load content and run or display
	//##############################################################################################################

	//--check for debugging urls
	if (isset($_GET["debug_running_test"])){
		echo "Testing SimpleScript System...<BR><BR>";
		echo "System is running...";
		shutdown();
		die();
	}

	if (checkpreg("|".$settings["admin_url"]."|i",$system["url"])==false){
		//--Setup via the admin page
		if ($settings["admin_password"]=="changeme" OR $settings["admin_password"]=="test"){
			if (strpos($_SERVER['SERVER_NAME'],'.codeanyapp.com') == false){ //--Dont run setup on testing enviroments
				header("Location: ".$settings["admin_url"]."");
				shutdown();
				die();
			}
		}
		if ($system["url_code"]==true){
			ss_runscript(dirname(__FILE__).$settings["location_code"].$system["url"]);
		}else{
			$filedownload=dirname(__FILE__).$settings["location_code"].$system["url"];
			if (file_exists($filedownload)){
				if (is_dir($filedownload)){
					echo "Sorry but you cant view the source of a DIR.";
					header("Location: ".$system["url"]."/");
				}else{
					$type=mime_type($filedownload);
					header("Content-Type: ".$type."; charset=utf-8");
					header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + ((60 * 60)*25))); // 1 day
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize($filedownload));
					readfile($filedownload);
					flush();
					shutdown();
					die();
				}
			}else{
				customError(404,"Sorry but we are unable to find the file requested.");
			}
		}
	}else{
		if (checkpreg("|.php|i",$system["url"])==false){
			if ($system["url"]==$settings["admin_url"]){
				include("admin/admin.php");
			}else{
				$filedownload=str_replace($settings["admin_url"], "", $system["url"]);
				$type=mime_type("admin/".$filedownload);
				header("Content-Type: ".$type."; charset=utf-8");
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize("admin/".$filedownload));
				readfile("admin".$filedownload);
				flush();
				shutdown();
				die();
			}
		}else{
			$urldown=str_replace($settings["admin_url"], "", $system["url"]);
			include("admin".$urldown);
		}
	}


	//##############################################################################################################
	//##############################################################################################################-- Close and end everything
	//##############################################################################################################
	if ($system["debug"]==true){
		echo "<!-- ";
		echo "".$system["debug_log"]."";
		print_r($ss_variables);
		echo " \r\n-->";
	}

	shutdown();

	function shutdown(){
		global $system;
		global $settings;
		global $settings_update;
		global $system_data;
		global $system_data_update;
		global $ss_variables;
		global $ss_functions;
		global $storage_template;
		global $settings_template;
		global $storage;
		global $storage_update;

		$ap = dirname (__FILE__);

		if ($system_data_update==true){
			file_put_contents(''.$ap.'/data.json', json_encode($system_data, JSON_PRETTY_PRINT));
		}

		if ($storage_update==true){
			file_put_contents(''.$ap.'/storage.json', json_encode($storage, JSON_PRETTY_PRINT));
		}

		if ($settings_update==true){
			file_put_contents(''.$ap.'/conf.json', json_encode($settings, JSON_PRETTY_PRINT));
		}

		$system["time_end"] = microtime_float();
		log_access();

		if ($system["uploaded_file"]!=false){
			unset($system["uploaded_file"]['tmp_name']);
		}

		unset($system);
		unset($settings);
		unset($settings_update);
		unset($system_data);
		unset($system_data_update);
		unset($ss_variables);
		unset($ss_functions);
		unset($storage_template);
		unset($settings_template);
		unset($storage);
		unset($storage_update);
	}
	?>
