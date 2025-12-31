<?php
require("utils/config.php");
$fullData = getEntityTableData();
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <script type='text/javascript'>
      var entityTableData = <?php echo json_encode($fullData); ?>;
   </script>
   <style>
      #bugsReviewTable tbody tr:last-child td {
         background-color: lightslategray;
         font-weight: bold;
         font-size: 16px;
         color: white;
         /* Dark text color */
      }
   </style>
   <?php
   $pageName = "bugs-prod";
   $dbTableName = "jira_bugs";
   $bugCategory = "PRD";
   require "components/head-title.php";
   ?>
</head>

<body data-gr-c-s-loaded="true">
   <div id="root">
      <div data-reactroot="">
         <div class="container-fluid">
            <div class="row flex-xl-nowrap">
               <?php
               $activateProdBugsPage = "active";
               require "components/left-menu.php";
               ?>
               <div id="content-body" class="col-12 col-md-9 col-xl-10 pl-4 pr-4 bd-content">
                  <?php
                  require "components/header.php";
                  ?>
                  <div class="row mt-3 db-chart">
                     <div id="parent1" class="col-lg-6 col-xl-3 column">
                        <div class="chart-card mb-4">
                           <div id="gauge1" class="chart-title">Total Prod Bugs</div>
                           <div id="chart1" class="chart gaugeContainer" style="height:120px">
                              <center>
                                 <label class="gauge custom-text-2" id="gauge-chart-container1"><img
                                       src="../images/loader.gif" height="100" /></label>
                              </center>
                           </div>
                        </div>
                     </div>
                     <div id="parent2" class="col-lg-6 col-xl-3 column">
                        <div class="chart-card mb-4">
                           <div id="gauge2" class="chart-title">PaymentGateway Bugs</div>
                           <div id="chart2" class="chart gaugeContainer" style="height:120px">
                              <center>
                                 <label class="gauge custom-text-2" id="gauge-chart-container2"><img
                                       src="../images/loader.gif" height="100" /></label>
                              </center>
                           </div>
                        </div>
                     </div>
                     <div id="parent3" class="col-lg-6 col-xl-3 column">
                        <div class="chart-card mb-4">
                           <div id="gauge3" class="chart-title">Invalid Bugs</div>
                           <div id="chart3" class="chart gaugeContainer" style="height:120px">
                              <center>
                                 <label class="gauge custom-text-2" id="gauge-chart-container3"><img
                                       src="../images/loader.gif" height="100" /></label>
                              </center>
                           </div>
                        </div>
                     </div>
                     <div id="parent4" class="col-lg-6 col-xl-3 column">
                        <div class="chart-card mb-4">
                           <div id="gauge4" class="chart-title">Other Bugs</div>
                           <div id="chart4" class="chart gaugeContainer" style="height:120px">
                              <center>
                                 <label class="gauge custom-text-2" id="gauge-chart-container4"><img
                                       src="../images/loader.gif" height="100" /></label>
                              </center>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div id="chart-container">
                     <center>
                        <div class="defaultChart">
                           <label id="column-chart-container1">Chart is loading here...</label>
                        </div>
                        <div class="defaultChart">
                           <br>
                           <label id="column-chart-container2">Chart is loading here...</label>
                        </div>
                        <div class="defaultChart">
                           <br>
                           <label id="column-chart-container5">Chart is loading here...</label>
                        </div>
                        <div class="projectChart">
                           <label id="column-chart-container3">Chart is loading here...</label>
                        </div>
                        <div class="projectChart">
                           <br>
                           <label id="column-chart-container4">Chart is loading here...</label>
                        </div>
                        <div class="projectChart">
                           <br>
                           <label id="column-chart-container6">Chart is loading here...</label>
                        </div>
                        <div class="projectChart">
                           <br>
                           <label id="column-chart-container7">Chart is loading here...</label>
                        </div>
                        <div class="projectChart">
                           <br>
                           <label id="column-chart-container8">Chart is loading here...</label>
                        </div>
                        <div class="projectChart">
                           <br>
                           <label id="line-chart-container1">Chart is loading here...</label>
                        </div>
                        <div class="projectChart">
                           <br>
                           <table id="bugsReviewTable" style="width:100%">
                              <thead>
                                 <tr>
                                    <th colspan="7" class="tableMainHeading">
                                       <center <label id="bugsReviewTableHeaderRow">PRD Bugs - Tech Root Cause
                                          Summary</label>
                                       </center>
                                    </th>
                                 </tr>
                                 <tr>
                                    <th style='color:#ea1212'>Root Cause Classification</th>
                                    <th style='color:#ea1212'>P0 Bug Count</th>
                                    <th style='color:#ea1212'>P1 Bug Count</th>
                                    <th style='color:#ea1212'>P2 Bug Count</th>
                                    <th style='color:#ea1212'>P3 Bug Count</th>
                                    <th style='color:#ea1212'>Total Count</th>
                                    <th style='color:#ea1212'>Bug Score</th>
                                 </tr>
                              </thead>
                              <tbody id="bugsReviewTableBody">
                              </tbody>
                           </table>
                        </div>
                        <div class="projectChart">
                           <br>
                           <table id="issuesList" style="width:100%">
                              <thead>
                                 <tr style="height:45px;">
                                    <th colspan="14" class="tableMainHeading">
                                       <center <label id="headerRow">Issues List</label>
                                       </center>
                                    </th>
                                 </tr>
                                 <tr style="height:25px;">
                                    <th style='color:#ea1212'>TicketId</th>
                                    <th style='color:#ea1212'>CreatedAt</th>
                                    <th style='color:#ea1212'>Bug Summary</th>
                                    <th style='color:#ea1212'>Priority</th>
                                    <th style='color:#ea1212'>Team Name</th>
                                    <th style='color:#ea1212'>Bug Type</th>
                                    <th style='color:#ea1212'>Root Cause</th>
                                    <th style='color:#ea1212'>Environment</th>
                                    <th style='color:#ea1212'>Status</th>
                                    <th style='color:#ea1212'>First Review Time</th>
                                    <th style='color:#ea1212'>Within Review SLA</th>
                                    <th style='color:#ea1212'>Dev Time</th>
                                    <th style='color:#ea1212'>Total Time</th>
                                    <th style='color:#ea1212'>Within Overall SLA?</th>
                                 </tr>
                              </thead>
                              <tbody id="issuesBody">
                              </tbody>
                           </table>
                        </div>
                     </center>
                  </div>
                  <div class="footNote" style="
                              ul {
                              list-style-type: none;
                              padding: 0;
                           }
                           li {
                              margin-bottom: 10px;
                           }">
                     <ul>
                        <li>
                           <strong>'Overall' SLA Time used:</strong>
                           <ul>
                              <li>P0: Resolve within 1 day (including weekends)</li>
                              <li>P1: Resolve within 2 days (including weekends)</li>
                              <li>P2: Resolve within 6 days (including weekends)</li>
                              <li>P3: Resolve within 60 days (including weekends)</li>
                              <br>
                           </ul>
                        </li>
                        <li>
                           <strong>'First Review' SLA Time used:</strong>
                           <ul>
                              <li>P0: Bug triaged within 0.5 hours (only working hours)</li>
                              <li>P1: Bug triaged within 2.5 hours (only working hours)</li>
                              <li>P2: Bug triaged within 08 hours (only working hours)</li>
                              <li>P3: Bug triaged within 25 hours (only working hours)</li>
                           </ul>
                        </li>
                     </ul>
                  </div>
                  <?php
                  require "components/footer.php";
                  ?>
               </div>
            </div>
         </div>
      </div>
   </div>
</body>

</html>