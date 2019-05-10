<!DOCTYPE HTML>
<html>
	<head>
		<title>SimpleScript Admin</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<!--[if lte IE 8]><script src="<?php echo $settings["admin_url"]; ?>/assets/js/ie/html5shiv.js"></script><![endif]-->
		<link rel="stylesheet" href="<?php echo $settings["admin_url"]; ?>/assets/css/main.css" />
		<!--[if lte IE 9]><link rel="stylesheet" href="<?php echo $settings["admin_url"]; ?>/assets/css/ie9.css" /><![endif]-->
		<!--[if lte IE 8]><link rel="stylesheet" href="<?php echo $settings["admin_url"]; ?>/assets/css/ie8.css" /><![endif]-->
		<style>
			  .modl_case{
	background:rgba(255,255,255,0.9);
	z-index: 8000;
	position: fixed;
	top: 0px;
	left: 0px;
	width: 100%;
	height: 100%;
    color:#555555;
    text-align:center;
}

.form-group{margin-top:10px;margin-bottom:10px;}

.modl_frame{
	overflow: auto;
	-webkit-overflow-scrolling: touch;
	overflow-x: hidden;
	max-height: 100%;
	max-height: -moz-calc(100% - 75px);
	max-height: -webkit-calc(100% - 75px);
	max-height: calc(100% - 75px);
	padding: 10px;
	padding-bottom:65px;
	max-width: 600px;
	margin: 0px auto;
	margin-top: 0px;
	margin-bottom: 0px;
}

  .modl_case *{
    color:#555555;
  }

.modl_frame.wide{
	max-width: 1000px;
	margin: 0px auto;
}

.modl_frame.normal{
	max-width: 600px;
	margin: 0px auto;
}

.modl_close{
	display:block;
	position:fixed;
	bottom:15px;
	border-radius:15px;
	left:50%;
	width:200px;
	margin-left:-100px;
	text-align:center;
	padding:10px;
	background:#f56a6a;
	color:#ffffff;
	z-index:100;
}

.modl_in{
		-webkit-animation: fadeInUp 200ms linear 1 forwards !important;
		animation: fadeInUp 200ms linear 1 forwards !important;
	}

	.modl_out{
		-webkit-animation: fadeOutDown 200ms linear 1 forwards !important;
		animation: fadeOutDown 200ms linear 1 forwards !important;
	}

  	@-webkit-keyframes fadeIn {
		from {
			opacity: 0;
		}

		to {
			opacity: 1;
		}
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
		}

		to {
			opacity: 1;
		}
	}

	@-webkit-keyframes fadeInUp {
		from {
			opacity: 0;
			-webkit-transform: translate3d(0, 100%, 0);
			transform: translate3d(0, 100%, 0);
		}

		to {
			opacity: 1;
			-webkit-transform: none;
			transform: none;
		}
	}

	@keyframes fadeInUp {
		from {
			opacity: 0;
			-webkit-transform: translate3d(0, 100%, 0);
			transform: translate3d(0, 100%, 0);
		}

		to {
			opacity: 1;
			-webkit-transform: none;
			transform: none;
		}
	}

	@-webkit-keyframes fadeOutDown {
		from {
			opacity: 1;
			-webkit-transform: none;
			transform: none;
		}

		to {
			opacity: 0;
			-webkit-transform: translate3d(0, 100%, 0);
			transform: translate3d(0, 100%, 0);
		}
	}

	@keyframes fadeOutDown {
		from {
			opacity: 1;
			-webkit-transform: none;
			transform: none;
		}

		to {
			opacity: 0;
			-webkit-transform: translate3d(0, 100%, 0);
			transform: translate3d(0, 100%, 0);
		}
	}
</style>
	</head>
	<body>
		<div id="modl_ajax"></div>

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Main -->
					<div id="main">
						<div class="inner">

							<!-- Content -->
								<section>
