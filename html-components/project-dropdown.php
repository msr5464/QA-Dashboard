<?php 
  require "server/db-config.php";
  echo "<select name='projectName' id='projectName'>
  <option value=''>Choose your project</option>";

  $sql = "select projectName from ".$pageName." group by projectName order by projectName asc"; 
  foreach ($dbo->query($sql) as $row) 
  { 
    echo "<option value='$row[projectName]'>$row[projectName]</option>"; 
  }
  echo "</select>";
?>
