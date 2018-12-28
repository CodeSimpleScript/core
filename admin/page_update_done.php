<?php

echo '<h1>Updating Finshed</h1>';
echo 'Yay the system has been updated.';
echo "<script>window.setTimeout(function(){ modl(\"close\"); window.location.href = \"".$settings["admin_url"]."?page=update\"; }, 500);</script>";

?>
