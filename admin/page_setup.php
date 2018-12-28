<?php

if ($settings["admin_password"]=="changeme" OR $settings["admin_password"]=="test"){
  echo '<div class="form-header"><h1>Setup</h1></div><div class="form-content">';
  echo '<form><div class="form-group"><label for="password">Password</label>Set the admin password for this new SimpleScript install.<input type="password" id="password" name="set_password" required="required"/></div><div class="form-group"><button type="submit">Set Password</button></div></form>';
  echo '</div>';
}

?>
