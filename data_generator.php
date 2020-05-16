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
      case 'getLatestResultsData_All':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select projectName,round(AVG(percentage),0) as percentage from results where environment!='Production' and createdAt>DATE_SUB(now() , INTERVAL ".$_GET['arguments'][0]." DAY) group by projectName order by projectName desc;";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['percentage'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
      case 'getLatestResultsData_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select * from results where projectName='".$_GET['arguments'][0]."' limit ".$_GET['arguments'][1];

        $counter = 1;
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['buildTag'];
          $jsonArrayItem['value'] = $row['percentage'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
      case 'getAvgPassPercentage_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select environment,round(AVG(percentage),0) as percentage from results where projectName='".$_GET['arguments'][0]."' and createdAt>DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) group by environment;";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem[$row['environment']] = $row['percentage'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
      case 'getAvgResultsData_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
    
        $lastDate = '2010-01-01';
        $lastProd = 0;
        $lastSbx = 0;
        $lastStg = 0;
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $sql = "SELECT  DATE(createdAt) as createdAt, avg(percentage) as percentage, environment FROM `results` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt),environment;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();

          if($lastDate == $row['createdAt'])
          {
            if($row['environment'] == "Production")
            {
              $jsonArrayItem1['value'] = $row['percentage'];
              $lastProd = $row['percentage'];
              array_pop($jsonArraySubSet1);
              array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if($row['environment'] == "Sandbox")
            {
              $jsonArrayItem2['value'] = $row['percentage'];
              $lastSbx = $row['percentage'];
              array_pop($jsonArraySubSet2);
              array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
            else if($row['environment'] == "Staging")
            {
              $jsonArrayItem3['value'] = $row['percentage'];
              $lastStg = $row['percentage'];
              array_pop($jsonArraySubSet3);
              array_push($jsonArraySubSet3, $jsonArrayItem3);
            }
          }
          else
          {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if($row['environment'] == "Production")
            {
              $lastProd = $row['percentage'];
            }

            if($row['environment'] == "Sandbox")
            {
              $lastSbx = $row['percentage'];
            }          

            if($row['environment'] == "Staging")
            {
              $lastStg = $row['percentage'];
            }
            $jsonArrayItem1['value'] = $lastProd;
            $jsonArrayItem2['value'] = $lastSbx;
            $jsonArrayItem3['value'] = $lastStg;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
          }
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Sandbox", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"Staging", "data"=>$jsonArraySubSet3));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;
      case 'getLatestTestrailData_All':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
      $sql = "select * from testrail where id in(select max(id) from testrail group by projectName) and totalAutomationCases>0 and createdAt>DATE_SUB(now() , INTERVAL ".$_GET['arguments'][0]." DAY);";
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $jsonArraySubSet4 = array();
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();
          $jsonArrayItem4 = array();
          $jsonArrayItem1['value'] = $row['p0Cases'];
          $jsonArrayItem2['value'] = $row['p1Cases'];
          $jsonArrayItem3['value'] = $row['p2Cases'];
          $otherCases = $row['totalAutomationCases'] - ($row['p0Cases']+$row['p1Cases']+$row['p2Cases']);
          $jsonArrayItem4['value'] = $otherCases;
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
          array_push($jsonArraySubSet4, $jsonArrayItem4);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"P0 Cases", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"P1 Cases", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"P2 Cases", "data"=>$jsonArraySubSet3));
        array_push($jsonArrayDataSet, array("seriesname"=>"Others", "data"=>$jsonArraySubSet4));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;
      case 'getTestRailData_Coverage':
        $sql = "select * from testrail where id in(select max(id) from testrail group by projectName);";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['automationCoveragePerc'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
      case 'getTestRailData_P0P1':
        $sql = "select * from testrail where id in(select max(id) from testrail group by projectName);";
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
          $jsonArrayItem['label'] = "Others";
          $jsonArrayItem['value'] = $otherCases;
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
    }
    echo json_encode($jsonArray);
  }
?>