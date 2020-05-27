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
      case 'getTotalBugsFound':
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
        $sql = "SELECT a.projectName,a.totalTicketsTested as newTotalTicketsTested,b.totalTicketsTested as oldTotalTicketsTested, a.totalBugs as newTotalBugs,b.totalBugs as oldTotalBugs,a.totalProdBugs as newTotalProdBugs,b.totalProdBugs as oldTotalProdBugs FROM jira a JOIN jira b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN jira c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id
 WHERE a.id in (select max(id) from jira group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][0]." + 1 DAY) group by projectName order by (a.totalBugs - b.totalBugs) desc;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem1 = array();
          $jsonArrayItem2 = array();
          $jsonArrayItem3 = array();

          $jsonArrayItem['label'] = $row['projectName'];
          array_push($jsonArraySubCategory, $jsonArrayItem);

          $totalTicketsTested = $row['newTotalTicketsTested'] - $row['oldTotalTicketsTested'];
          $increasedBugs = $row['newTotalBugs'] - $row['oldTotalBugs'];
          $increasedProdBugs = $row['newTotalProdBugs'] - $row['oldTotalProdBugs'];

          $jsonArrayItem1['value'] = $totalTicketsTested;
          $jsonArrayItem2['value'] = $increasedBugs;
          $jsonArrayItem3['value'] = $increasedProdBugs;
          array_push($jsonArraySubSet1, $jsonArrayItem1);
          array_push($jsonArraySubSet2, $jsonArrayItem2);
          array_push($jsonArraySubSet3, $jsonArrayItem3);

        }
        array_push($jsonArrayCategory, array("category"=>$jsonArraySubCategory));

        //array_push($jsonArrayDataSet, array("seriesname"=>"Tickets Tested", "data"=>$jsonArraySubSet1));
        array_push($jsonArrayDataSet, array("seriesname"=>"Total Bugs found", "data"=>$jsonArraySubSet2));
        array_push($jsonArrayDataSet, array("seriesname"=>"Production Bugs", "renderas"=>"line", "data"=>$jsonArraySubSet3));
        $jsonArray = array("categories"=>$jsonArrayCategory,"dataset"=>$jsonArrayDataSet);
      break;

      case 'getBugPercentage':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 1) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $sql = "SELECT * FROM `jira` WHERE id in(select max(id) from jira GROUP BY projectName) order by bugPercentage desc;";

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

      case 'getTotalTicketsTested_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "SELECT (a.totalTicketsTested-b.totalTicketsTested) as totalTicketsTested,(a.totalBugs-b.totalBugs) as totalBugs,(a.totalProdBugs-b.totalProdBugs) as totalProdBugs FROM jira a JOIN jira b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN jira c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from jira group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][1]." + 1 DAY) and a.projectName='".$_GET['arguments'][0]."' group by a.projectName order by a.projectName desc;";

        foreach ($dbo->query($sql) as $row) 
        {
          $jsonArrayItem = array();
          $jsonArrayItem['totalTicketsTested'] = $row['totalTicketsTested'];
          $jsonArrayItem['totalBugs'] = $row['totalBugs'];
          $jsonArrayItem['totalProdBugs'] = $row['totalProdBugs'];
          array_push($jsonArray, $jsonArrayItem);
        }
      break;

      case 'getBugPriorityBreakdown_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $sql = "SELECT (a.totalP0Bugs-b.totalP0Bugs) as totalP0Bugs,(a.totalP1Bugs-b.totalP1Bugs) as totalP1Bugs,(a.totalP2Bugs-b.totalP2Bugs) as totalP2Bugs,(a.totalOtherBugs-b.totalOtherBugs) as totalOtherBugs FROM jira a JOIN jira b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN jira c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from jira group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL ".$_GET['arguments'][1]." + 1 DAY) and a.projectName='".$_GET['arguments'][0]."' group by a.projectName order by a.projectName desc;";

        foreach ($dbo->query($sql) as $row) 
        {
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

      case 'getBugPercentageTrend_Project':
        if( !is_array($_GET['arguments']) || (count($_GET['arguments']) < 2) )
        {
          $jsonArray['error'] = 'Error in passed arguments!';
        }
        $jsonArrayCategory = array();
        $jsonArraySubCategory = array();
        $jsonArrayDataSet = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $sql = "SELECT DATE(createdAt) as createdAt, max(bugPercentage) as bugPercentage, max(prodBugPercentage) as prodBugPercentage FROM `jira` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." + 1 DAY) GROUP BY DATE(createdAt);";

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

      case 'getBugCountTrend_Project':
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
        $sql = "SELECT DATE(createdAt) as createdAt, max(totalTicketsTested) as totalTicketsTested, max(totalBugs) as totalBugs, max(totalProdBugs) as totalProdBugs, max(totalP0Bugs) as totalP0Bugs, max(p0ProdBugs) as p0ProdBugs,max(totalP1Bugs) as totalP1Bugs, max(p1ProdBugs) as p1ProdBugs FROM `jira` WHERE projectName='".$_GET['arguments'][0]."' and createdAt>=DATE_SUB(now() , INTERVAL ".$_GET['arguments'][1]." + 1 DAY) GROUP BY DATE(createdAt);";

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
    }
    echo json_encode($jsonArray);
  }
?>