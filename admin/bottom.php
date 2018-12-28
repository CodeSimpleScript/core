							</section>

						</div>
					</div>

				<!-- Sidebar -->
					<?php if ($_SESSION["admin_loggedin"]==true){ ?>
					<div id="sidebar">
						<div class="inner">

							<!-- Search -->
								<section id="search" class="alt">
									<form method="post" action="#">
										<input type="text" name="query" id="query" placeholder="Search" />
									</form>
								</section>

							<!-- Menu -->
								<nav id="menu">
									<header class="major">
										<h2>Menu</h2>
									</header>
									<ul>

										<li><a href="<?php echo $settings["admin_url"]; ?>?page=dash">Dashboard</a></li>
										<li>
											<span class="opener">Github Packages</span>
											<ul>
												<li><a href="<?php echo $settings["admin_url"]; ?>?page=site_github">Dashboard</a></li>
											</ul>
										</li>
										<li>
											<span class="opener">Updates</span>
											<ul>
												<li><a href="<?php echo $settings["admin_url"]; ?>?page=update">Check for updates</a></li>
												<li><a href="<?php echo $settings["admin_url"]; ?>?page=update_branch">Change update branch</a></li>
											</ul>
										</li>
									</ul>
								</nav>

							<!-- Footer -->
								<footer id="footer">
									<p class="copyright">&copy; SimpleScript. SimpleScript is a free coding lanuage, to learn more visit <a href="https://www.codesimplescript.com" target="_blank">codesimplescript.com</a>. Admin design by <a href="https://html5up.net">HTML5 UP</a>.</p>
								</footer>

						</div>
					</div>
				<?php } ?>

			</div>

		<!-- Scripts -->
			<script src="<?php echo $settings["admin_url"]; ?>/assets/js/jquery.min.js"></script>
			<script src="<?php echo $settings["admin_url"]; ?>/assets/js/skel.min.js"></script>
			<script src="<?php echo $settings["admin_url"]; ?>/assets/js/util.js"></script>
			<!--[if lte IE 8]><script src="<?php echo $settings["admin_url"]; ?>/assets/js/ie/respond.min.js"></script><![endif]-->
			<script src="<?php echo $settings["admin_url"]; ?>/assets/js/main.js"></script>

		<script>
			var modl_open=false;

			function modl(url,mode='normal'){
				if (url=="close"){
					modl_open=false;
					$("#modl_frameid").removeClass("modl_in");
					$("#modl_frameid").addClass("modl_out");
					$("#modl_caseid").addClass("modl_out");
				}else{
					modl_open=true;
					document.getElementById("modl_ajax").innerHTML="<div id='modl_caseid' class='modl_case'><div id='modl_frameid' class='modl_frame modl_in " + mode + "'><div id='modl_content'></div></div><a href=\"javascript:modl('close');\"><div class='modl_close'>Close</div></a></div>";
					app_switch_div(url,"modl_content");
				}
			}

			function modl_content(content,mode='normal'){
				if (content=="close"){
					modl_open=false;
					$("#modl_frameid").removeClass("modl_in");
					$("#modl_frameid").addClass("modl_out");
					$("#modl_caseid").addClass("modl_out");
				}else{
					modl_open=true;
					document.getElementById("modl_ajax").innerHTML="<div id='modl_caseid' class='modl_case'><div id='modl_frameid' class='modl_frame modl_in " + mode + "'><div id='modl_content'>" + content + "</div></div><a href=\"javascript:modl('close');\"><div class='modl_close'>Close</div></a></div>";
				}
			}

				//---------------------------------Switch Pages System
			function app_switch_div(url,div){
				document.getElementById(div).innerHTML="<h1>Loading</h1>Working on it...";
				$.ajax({
					type : "POST",
					url : "" + url + "",
					dataType: "html",
					timeout: 100000,
					async: true,
					cache: false,
					success : function(data, textStatus, jqXHR) {
						document.getElementById(div).innerHTML=data;
						var arr = document.getElementById(div).getElementsByTagName('script')
						for (var n = 0; n < arr.length; n++){
								eval(arr[n].innerHTML);
						}
					},
					error : function(jqXHR, textStatus, errorThrown){
						document.getElementById(div).innerHTML='Load Error';
					}
				});
			}
		</script>
	</body>
</html>
