<?php
  header('Content-type: application/json');
  require "db-config.php";
  
  $jsonArray = array();
  if (!isset($_GET['functionname'])) 
  {
    $jsonArray['error'] = 'No function name!';
  }

  if (!isset($jsonArray['error'])) 
  {
    switch ($_GET['functionname']) 
    {
      case 'getAvgPercentage':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 3) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select projectName,round(AVG(percentage),0) as percentage from results where environment='".$_GET['arguments'][1]."' and groupName='".$_GET['arguments'][2]."' and createdAt>=DATE_SUB(now(), INTERVAL ".$_GET['arguments'][0]." DAY) group by projectName order by projectName desc;";
        
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['percentage'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getAvgExecutionTime':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 3) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select projectName,round(AVG(TIME_TO_SEC(duration))/60,2) as duration from results where environment='".$_GET['arguments'][1]."' and groupName='".$_GET['arguments'][2]."' and createdAt>=DATE_SUB(now(), INTERVAL ".$_GET['arguments'][0]." DAY) group by projectName order by projectName desc;";
        
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['duration'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getLast7Records':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $counter = 1;
        $sql = "select buildTag,percentage,Date(createdAt) as createdAt from results where projectName='".$_GET['arguments'][0]."' order by id desc limit ".$_GET['arguments'][1];
        
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['createdAt']."\n".$row['buildTag'];
          $jsonArrayItem['value'] = $row['percentage'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getAvgPercentage_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select environment,round(AVG(percentage),0) as percentage from results where projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now(), INTERVAL ".$_GET['arguments'][1]." DAY) group by environment;";
        
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArraySubSet1 = array();
          $jsonArrayItem = array();
          
          $jsonArrayItem['label'] = "Fail Percentage";
          $jsonArrayItem['value'] = 100 - $row['percentage'];
          array_push($jsonArraySubSet1, $jsonArrayItem);

          $jsonArrayItem['label'] = "Pass Percentage";
          $jsonArrayItem['value'] = $row['percentage'];
          array_push($jsonArraySubSet1, $jsonArrayItem);

          array_push($jsonArray, array($row['environment']."-data"=>$jsonArraySubSet1));
        }
      break;

      case 'getDailyAvgPercentage_Project':
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
        $sql = "SELECT  DATE(createdAt) as createdAt, avg(percentage) as percentage, environment FROM `results` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now(), INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt),environment;";

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

      case 'getDailyAvgExecutionTime_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 3) )
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
        $sql = "SELECT  DATE(createdAt) as createdAt, round(AVG(TIME_TO_SEC(duration))/60,2) as duration, environment FROM `results` WHERE projectName='".$_GET['arguments'][0]."' and groupName in (".$_GET['arguments'][2].") and createdAt>=DATE_SUB(now(), INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt),environment;";

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
              $jsonArrayItem1['value'] = $row['duration'];
              $lastProd = $row['duration'];
              array_pop($jsonArraySubSet1);
              array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if($row['environment'] == "Sandbox")
            {
              $jsonArrayItem2['value'] = $row['duration'];
              $lastSbx = $row['duration'];
              array_pop($jsonArraySubSet2);
              array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
            else if($row['environment'] == "Staging")
            {
              $jsonArrayItem3['value'] = $row['duration'];
              $lastStg = $row['duration'];
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
              $lastProd = $row['duration'];
            }

            if($row['environment'] == "Sandbox")
            {
              $lastSbx = $row['duration'];
            }          

            if($row['environment'] == "Staging")
            {
              $lastStg = $row['duration'];
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

case 'getTotalCasesGroupwise_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $lastDate = '2010-01-01';
        $lastRegression = 0;
        $lastProduction = 0;
        $lastP0 = 0;
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $sql = "SELECT  DATE(createdAt) as createdAt, max(totalCases) as totalCases, groupName FROM `results` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now(), INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt),groupName;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();

          if($lastDate == $row['createdAt'])
          {
            if($row['groupName'] == "regression")
            {
              $jsonArrayItem1['value'] = $row['totalCases'];
              $lastRegression = $row['totalCases'];
              array_pop($jsonArraySubSet1);
              array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if($row['groupName'] == "production")
            {
              $jsonArrayItem2['value'] = $row['totalCases'];
              $lastProduction = $row['totalCases'];
              array_pop($jsonArraySubSet2);
              array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
            else if($row['groupName'] == "p0")
            {
              $jsonArrayItem3['value'] = $row['totalCases'];
              $lastP0 = $row['totalCases'];
              array_pop($jsonArraySubSet3);
              array_push($jsonArraySubSet3, $jsonArrayItem3);
            }
          }
          else
          {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if($row['groupName'] == "regression")
            {
              $lastRegression = $row['totalCases'];
            }

            if($row['groupName'] == "production")
            {
              $lastProduction = $row['totalCases'];
            }          

            if($row['groupName'] == "p0")
            {
              $lastP0 = $row['totalCases'];
            }
            $jsonArrayItem1['value'] = $lastRegression;
            $jsonArrayItem2['value'] = $lastProduction;
            $jsonArrayItem3['value'] = $lastP0;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
          }
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        //array_push($jsonArrayDataSet, array("seriesname"=>"PO Cases", "data"=>$jsonArraySubSet3));
        array_push($jsonArrayDataSet, array("seriesname"=>"Regression Cases", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production Cases", "data"=>$jsonArraySubSet2));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;
    }
    echo json_encode($jsonArray);
  }
?>