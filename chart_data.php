<?php
  header('Content-type: application/json');
  require "db_config.php";
  $sql = "Select projectName,round(AVG(percentage),0) as percentage from results group by projectName;";
  $jsonArray = array();
  foreach ($dbo->query($sql) as $row) 
  {
    $jsonArrayItem = array();
    $jsonArrayItem['label'] = $row['projectName'];
    $jsonArrayItem['value'] = $row['percentage'];
    //append the above created object into the main array.
    array_push($jsonArray, $jsonArrayItem);
  }
echo json_encode($jsonArray);
?>
