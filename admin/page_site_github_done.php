<?php

echo '<h1>Updating Finshed</h1>';
echo 'The site package has been installed.';
echo "<script>window.setTimeout(function(){ modl(\"close\"); window.location.href = \"".$settings["admin_url"]."?page=site_github\"; }, 500);</script>";

?>
