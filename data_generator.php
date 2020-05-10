<?php
  header('Content-type: application/json');
  require "db_config.php";
  
  $jsonArray = array();
  if (!isset($_GET['functionname'])) 
  {
    $jsonArray['error'] = 'No function name!';
  }

  if (!isset($jsonArray['error'])) 
  {
    switch ($_GET['functionname']) 
    {
      case 'getTestRailData_Latest':
      $sql = "select * from testrail where id in(select max(id) from testrail group by projectName) order by totalAutomationCases desc;";
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();
          $jsonArrayItem1['value'] = $row['p0Cases'];
          $jsonArrayItem2['value'] = $row['p1Cases'];
          $jsonArrayItem3['value'] = $row['p2Cases'];
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"P0 Cases", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"P1 Cases", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"P2 Cases", "data"=>$jsonArraySubSet3));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;
      case 'getResultsData_Latest':
        $sql = "select projectName,round(AVG(percentage),0) as percentage from results group by projectName order by percentage desc;";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['percentage'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
      case 'getTestRailData_Coverage':
        $sql = "select * from testrail where id in(select max(id) from testrail group by projectName) order by automationCoveragePerc desc;";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['automationCoveragePerc'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
      case 'getTestRailData_P0P1':
        $sql = "select * from testrail where id in(select max(id) from testrail group by projectName) order by p0CoveragePerc desc;";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['p0CoveragePerc'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
      case 'getTestRailData_singleProject':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select * from testrail where id = (select max(id) from testrail where projectName='".$_GET['arguments'][0]."');";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = "P0 Cases";
          $jsonArrayItem['value'] = $row['p0Cases'];
          array_push($jsonArray, $jsonArrayItem);
          $jsonArrayItem['label'] = "P1 Cases";
          $jsonArrayItem['value'] = $row['p1Cases'];
          array_push($jsonArray, $jsonArrayItem);
          $jsonArrayItem['label'] = "P2 Cases";
          $jsonArrayItem['value'] = $row['p2Cases'];
          array_push($jsonArray, $jsonArrayItem);
          $otherCases = $row['totalAutomationCases'] - ($row['p0Cases']+$row['p1Cases']+$row['p2Cases']);
          $jsonArrayItem['label'] = "Other Cases";
          $jsonArrayItem['value'] = $otherCases;
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
    }
    echo json_encode($jsonArray);
  }
?>