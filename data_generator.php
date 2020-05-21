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
      case 'getLatestResultsData_Production':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select projectName,round(AVG(percentage),0) as percentage from results where environment='Production' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][0]." DAY) group by projectName order by projectName desc;";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['percentage'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getLatestResultsData_Staging':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select projectName,round(AVG(percentage),0) as percentage from results where environment='Staging' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][0]." DAY) group by projectName order by projectName desc;";
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
        $sql = "select * from results where projectName='".$_GET['arguments'][0]."' order by id desc limit ".$_GET['arguments'][1];

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
        $sql = "select environment,round(AVG(percentage),0) as percentage from results where projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) group by environment;";
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
        $sql = "SELECT  DATE(createdAt) as createdAt, avg(percentage) as percentage, environment FROM `results` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt),environment;";

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

case 'getTotalCasesResultsData_Project':
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
        $sql = "SELECT  DATE(createdAt) as createdAt, max(totalCases) as totalCases, groupName FROM `results` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt),groupName;";

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
        array_push($jsonArrayDataSet, array("seriesname"=>"PO Cases", "data"=>$jsonArraySubSet3));
        array_push($jsonArrayDataSet, array("seriesname"=>"Regression Cases", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production Cases", "data"=>$jsonArraySubSet2));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getLatestTestrailData_All':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
      $sql = "select * from testrail where id in(select max(id) from testrail group by projectName) and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][0]." DAY);";
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $jsonArraySubSet4 = array();
        $jsonArraySubSet5 = array();
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();
          $jsonArrayItem4 = array();
          $jsonArrayItem5 = array();
          $jsonArrayItem1['value'] = $row['p0Cases'];
          $jsonArrayItem2['value'] = $row['p1Cases'];
          $jsonArrayItem3['value'] = $row['p2Cases'];
          $otherCases = $row['totalAutomationCases'] - ($row['p0Cases']+$row['p1Cases']+$row['p2Cases']);
          $jsonArrayItem4['value'] = $otherCases;
          $jsonArrayItem5['value'] = $row['totalCases'] - $row['totalAutomationCases'];
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
          array_push($jsonArraySubSet4, $jsonArrayItem4);
          array_push($jsonArraySubSet5, $jsonArrayItem5);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"P0 Cases", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"P1 Cases", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"P2 Cases", "data"=>$jsonArraySubSet3));
        array_push($jsonArrayDataSet, array("seriesname"=>"Low Priority", "data"=>$jsonArraySubSet4));
        array_push($jsonArrayDataSet, array("seriesname"=>"Manual Cases", "data"=>$jsonArraySubSet5));
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

      case 'getTestRailData_P0':
        $sql = "select * from testrail where id in(select max(id) from testrail group by projectName);";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['p0CoveragePerc'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getTestRailData_P1':
        $sql = "select * from testrail where id in(select max(id) from testrail group by projectName);";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['p1CoveragePerc'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getTestRailData_P2':
        $sql = "select * from testrail where id in(select max(id) from testrail group by projectName);";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['projectName'];
          $jsonArrayItem['value'] = $row['p2CoveragePerc'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getTotalCasesTestrailData_Project_Pie':
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
          $jsonArrayItem['label'] = "Low Priority";
          $jsonArrayItem['value'] = $otherCases;
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
case 'getTotalCasesTestrailData_Project_Line':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $jsonArraySubSet4 = array();
        $jsonArraySubSet5 = array();
        $jsonArraySubSet6 = array();
        $jsonArraySubSet7 = array();
        $sql = "SELECT  DATE(createdAt) as createdAt, max(totalCases) as totalCases, max(totalAutomationCases) as totalAutomationCases, max(p0Cases) as p0Cases, max(p1Cases) as p1Cases, max(p2Cases) as p2Cases FROM `testrail` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt);";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();
          $jsonArrayItem4 = array();
          $jsonArrayItem5 = array();
          $jsonArrayItem6 = array();
          $jsonArrayItem7 = array();
          
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['createdAt'];
          array_push($jsonArraySubCategory, $jsonArrayItem);

          $jsonArrayItem1['value'] = $row['totalCases'];
          $jsonArrayItem2['value'] = $row['totalAutomationCases'];
          $jsonArrayItem3['value'] = $row['p0Cases'];
          $jsonArrayItem4['value'] = $row['p1Cases'];
          $jsonArrayItem5['value'] = $row['p2Cases'];
          $otherCases = $row['totalAutomationCases'] - ($row['p0Cases']+$row['p1Cases']+$row['p2Cases']);
          $jsonArrayItem6['value'] = $otherCases;
          $manualCases = $row['totalCases'] - $row['totalAutomationCases'];
          $jsonArrayItem7['value'] = $manualCases;
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
          array_push($jsonArraySubSet4, $jsonArrayItem4);
          array_push($jsonArraySubSet5, $jsonArrayItem5);
          array_push($jsonArraySubSet6, $jsonArrayItem6);
          array_push($jsonArraySubSet7, $jsonArrayItem7);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Total Cases", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Manual Cases", "data"=>$jsonArraySubSet7));
        array_push($jsonArrayDataSet, array("seriesname"=>"Automation Cases", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"P0 Cases", "data"=>$jsonArraySubSet3));
        array_push($jsonArrayDataSet, array("seriesname"=>"P1 Cases", "data"=>$jsonArraySubSet4));
        array_push($jsonArrayDataSet, array("seriesname"=>"P2 Cases", "data"=>$jsonArraySubSet5));
        array_push($jsonArrayDataSet, array("seriesname"=>"Other Cases", "data"=>$jsonArraySubSet6));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getTestrailCoverageData_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "select * from testrail where id = (select max(id) from testrail where projectName='".$_GET['arguments'][0]."');";
        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem["totalCoverage"] = $row['automationCoveragePerc'];
          $jsonArrayItem["P0Coverage"] = $row['p0CoveragePerc'];
          $jsonArrayItem["P1Coverage"] = $row['p1CoveragePerc'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getTestRailIncrement_P0':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $sql = "SELECT a.projectName as projectName, a.p0CoveragePerc as newP0CoveragePerc,b.p0CoveragePerc as oldP0CoveragePerc FROM testrail a JOIN testrail b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN testrail c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from testrail group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][0]." DAY) and (a.p0CoveragePerc > b.p0CoveragePerc or a.p0CoveragePerc < b.p0CoveragePerc) group by projectName;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();
          
          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);
          $jsonArrayItem1['value'] = $row['oldP0CoveragePerc'];
          $increment = $row['newP0CoveragePerc'] - $row['oldP0CoveragePerc'];

          if($increment>0)
          {
            $jsonArrayItem2['value'] = $increment;
            
          }
          else
          {
            $jsonArrayItem3['value'] = $increment;
            
          }
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Previous P0 Coverage", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Incremented", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"Decremented", "data"=>$jsonArraySubSet3));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getTestRailIncrement_P1':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $sql = "SELECT a.projectName as projectName, a.p1CoveragePerc as newP1CoveragePerc,b.p1CoveragePerc as oldP1CoveragePerc FROM testrail a JOIN testrail b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN testrail c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from testrail group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][0]." DAY) and (a.p1CoveragePerc > b.p1CoveragePerc or a.p1CoveragePerc < b.p1CoveragePerc) group by projectName";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();

          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);
          $jsonArrayItem1['value'] = $row['oldP1CoveragePerc'];
          $increment = $row['newP1CoveragePerc'] - $row['oldP1CoveragePerc'];

          if($increment>0)
          {
            $jsonArrayItem2['value'] = $increment;
            
          }
          else
          {
            $jsonArrayItem3['value'] = $increment;
            
          }
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Previous P1 Coverage", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Incremented", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"Decremented", "data"=>$jsonArraySubSet3));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

case 'getTestRailIncrement_OnlyAutomated':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $sql = "SELECT a.projectName as projectName, a.alreadyAutomated as newAlreadyAutomated,b.alreadyAutomated as oldAlreadyAutomated FROM testrail a JOIN testrail b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN testrail c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from testrail group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][0]." DAY) and (a.alreadyAutomated > b.alreadyAutomated or a.alreadyAutomated < b.alreadyAutomated) group by projectName;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();

          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);
          $jsonArrayItem1['value'] = $row['oldAlreadyAutomated'];
          $increment = $row['newAlreadyAutomated'] - $row['oldAlreadyAutomated'];

          if($increment>0)
          {
            $jsonArrayItem2['value'] = $increment;
            
          }
          else
          {
            $jsonArrayItem3['value'] = $increment;
            
          }
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Previous Count", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Incremented", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"Decremented", "data"=>$jsonArraySubSet3));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getJiraData_BugPercentage_All':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();

        $sql = "SELECT projectName, max(bugPercentage) as bugPercentage, max(prodBugPercentage) as prodBugPercentage FROM `jira` WHERE createdAt>=DATE_SUB(now(), INTERVAL ".$_GET['arguments'][0]." DAY) GROUP BY projectName order by projectName desc;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();

          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);

          $jsonArrayItem1['value'] = $row['bugPercentage'];
          $jsonArrayItem2['value'] = $row['prodBugPercentage'];
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Total Bug Percentage", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production Bug Percentage", "renderas"=>"line","data"=>$jsonArraySubSet2));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getJiraData_TotalBugs_All':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();

        $sql = "SELECT a.projectName,a.totalBugs as newTotalBugs,b.totalBugs as oldTotalBugs,a.totalProdBugs as newTotalProdBugs,b.totalProdBugs as oldTotalProdBugs FROM jira a JOIN jira b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN jira c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id
 WHERE a.id in (select max(id) from jira group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][0]." DAY) group by projectName order by a.projectName desc;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();

          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);

          $increasedBugs = $row['newTotalBugs'] - $row['oldTotalBugs'];
          $increasedProdBugs = $row['newTotalProdBugs'] - $row['oldTotalProdBugs'];

          $jsonArrayItem2['value'] = $increasedBugs;
          $jsonArrayItem3['value'] = $increasedProdBugs;
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);

        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));

        array_push($jsonArrayDataSet, array("seriesname"=>"Total Bugs found", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production Bugs", "renderas"=>"line","data"=>$jsonArraySubSet3));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getJiraData_AllPercentages_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
    
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();

        $sql = "SELECT DATE(createdAt) as createdAt, max(bugPercentage) as bugPercentage, max(prodBugPercentage) as prodBugPercentage FROM `jira` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt);";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();

          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['createdAt'];
          array_push($jsonArraySubCategory, $jsonArrayItem);

          $jsonArrayItem1['value'] = $row['bugPercentage'];
          $jsonArrayItem2['value'] = $row['prodBugPercentage'];
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Bug Percentage", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production Bug Percentage", "data"=>$jsonArraySubSet2));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getJiraData_AllNumbers_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
    
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $jsonArraySubSet4 = array();
        $jsonArraySubSet5 = array();
        $jsonArraySubSet6 = array();
        $jsonArraySubSet7 = array();
        $sql = "SELECT DATE(createdAt) as createdAt, max(totalTicketsTested) as totalTicketsTested, max(totalBugs) as totalBugs, max(totalProdBugs) as totalProdBugs, max(totalP0Bugs) as totalP0Bugs, max(p0ProdBugs) as p0ProdBugs,max(totalP1Bugs) as totalP1Bugs, max(p1ProdBugs) as p1ProdBugs FROM `jira` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." DAY) GROUP BY DATE(createdAt);";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();
          $jsonArrayItem4 = array();
          $jsonArrayItem5 = array();
          $jsonArrayItem6 = array();
          $jsonArrayItem7 = array();
          
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = $row['createdAt'];
          array_push($jsonArraySubCategory, $jsonArrayItem);

          $jsonArrayItem1['value'] = $row['totalTicketsTested'];
          $jsonArrayItem2['value'] = $row['totalBugs'];
          $jsonArrayItem3['value'] = $row['totalProdBugs'];
          $jsonArrayItem4['value'] = $row['totalP0Bugs'];
          $jsonArrayItem5['value'] = $row['p0ProdBugs'];
          $jsonArrayItem6['value'] = $row['totalP1Bugs'];
          $jsonArrayItem7['value'] = $row['p1ProdBugs'];

          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);
          array_push($jsonArraySubSet4, $jsonArrayItem4);
          array_push($jsonArraySubSet5, $jsonArrayItem5);
          array_push($jsonArraySubSet6, $jsonArrayItem6);
          array_push($jsonArraySubSet7, $jsonArrayItem7);
        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));
        array_push($jsonArrayDataSet, array("seriesname"=>"Tickets Tested", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Bugs Found", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production Bugs", "data"=>$jsonArraySubSet3));
        array_push($jsonArrayDataSet, array("seriesname"=>"P0 Bugs", "data"=>$jsonArraySubSet4));
        array_push($jsonArrayDataSet, array("seriesname"=>"P0 Prod Bugs", "data"=>$jsonArraySubSet5));
        array_push($jsonArrayDataSet, array("seriesname"=>"P1 Bugs", "data"=>$jsonArraySubSet6));
        array_push($jsonArrayDataSet, array("seriesname"=>"P1 Prod Bugs", "data"=>$jsonArraySubSet7));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getJiraData_Project_Pie':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "SELECT (a.totalP0Bugs-b.totalP0Bugs) as totalP0Bugs,(a.totalP1Bugs-b.totalP1Bugs) as totalP1Bugs,(a.totalP2Bugs-b.totalP2Bugs) as totalP2Bugs,(a.totalOtherBugs-b.totalOtherBugs) as totalOtherBugs FROM jira a JOIN jira b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN jira c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from jira group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][1]." DAY) and a.projectName='".$_GET['arguments'][0]."' group by a.projectName order by a.projectName desc;";

        foreach ($dbo->query($sql) as $row) 
        {
          $isEmpty = false;
          $jsonArrayItem = array();
          $jsonArrayItem['label'] = "P0 Bugs";
          $jsonArrayItem['value'] = $row['totalP0Bugs'];
          array_push($jsonArray, $jsonArrayItem);
          $jsonArrayItem['label'] = "P1 Bugs";
          $jsonArrayItem['value'] = $row['totalP1Bugs'];
          array_push($jsonArray, $jsonArrayItem);
          $jsonArrayItem['label'] = "P2 Bugs";
          $jsonArrayItem['value'] = $row['totalP2Bugs'];
          array_push($jsonArray, $jsonArrayItem);
          $jsonArrayItem['label'] = "Low Priority";
          $jsonArrayItem['value'] = $row['totalOtherBugs'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;
    }
    echo json_encode($jsonArray);
  }
?>