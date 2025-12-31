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
      <?php
         $pageName = "bugs-total";
         $dbTableName = "jira_bugs";
         $bugCategory = "total";
         require "components/head-title.php";
      ?>
   </head>
   <body data-gr-c-s-loaded="true">
      <div id="root">
         <div data-reactroot="">
            <div class="container-fluid">
               <div class="row flex-xl-nowrap">
                  <?php
                     $activateTotalBugsPage = "active";
                     require "components/left-menu.php";
                  ?>
                  <div id="content-body" class="col-12 col-md-9 col-xl-10 pl-4 pr-4 bd-content">
                     <?php
                        require "components/header.php";
                     ?>
                     <div class="row mt-3 db-chart">
                        <div id="parent1" class="col-lg-6 col-xl-3 column">
                           <div class="chart-card mb-4">
                              <div id="gauge1" class="chart-title">Total Bugs / PRD+FCT+STG</div>
                              <div id="chart1" class="chart gaugeContainer" style="height:120px">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container1"><img src="../images/loader.gif" height="100" /></label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent2" class="col-lg-6 col-xl-3 column">
                           <div class="chart-card mb-4">
                              <div id="gauge2" class="chart-title">PRD / Production Bugs</div>
                              <div id="chart2" class="chart gaugeContainer" style="height:120px">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container2"><img src="../images/loader.gif" height="100" /></label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent3" class="col-lg-6 col-xl-3 column">
                           <div class="chart-card mb-4">
                              <div id="gauge3" class="chart-title">FCT / Regression Bugs</div>
                              <div id="chart3" class="chart gaugeContainer" style="height:120px">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container3"><img src="../images/loader.gif" height="100" /></label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent3" class="col-lg-6 col-xl-3 column">
                           <div class="chart-card mb-4">
                              <div id="gauge3" class="chart-title">STG / Feature Bugs</div>
                              <div id="chart3" class="chart gaugeContainer" style="height:120px">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container4"><img src="../images/loader.gif" height="100" /></label>
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
                              <label id="column-chart-container3">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container4">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container5">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container6">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container11">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container12">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container13">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <label id="column-chart-container7">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <label id="column-chart-container8">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <label id="column-chart-container9">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <label id="column-chart-container10">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <label id="line-chart-container1">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <label id="line-chart-container2">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <label id="line-chart-container3">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="line-chart-container4">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <table id="issuesList" style="width:100%">
                                 <thead>
                                    <tr>
                                       <th colspan="9" class="tableMainHeading">
                                          <center
                                             <label id="headerRow">Issues List</label>
                                          </center>
                                       </th>
                                    </tr>
                                    <tr style="height:25px;">
                                        <th style='color:#ea1212'>TicketId</th>
                                        <th style='color:#ea1212'>CreatedAt</th>
                                        <th style='color:#ea1212'>Bug Summary</th>
                                        <th style='color:#ea1212'>Priority</th>
                                        <th style='color:#ea1212'>Team Name</th>
                                        <th style='color:#ea1212'>Product Area</th>
                                        <th style='color:#ea1212'>Bug Type</th>
                                        <th style='color:#ea1212'>Root Cause</th>
                                        <th style='color:#ea1212'>Status</th>
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
                           <strong>Bug Category Definitions:</strong>
                           <ul>
                              <li>STG : Bugs found before FCT ~ all the PaymentGateway bugs found during Feature testing stage</li>
                              <li>FCT : Bugs found during FCT ~ all the PaymentGateway bugs found during Full Cycle Testing stage</li>
                              <li>PRD : Bugs found after FCT ~ any valid bug reported on 'Production' (including Internal/Partner/Alpha/Beta bugs)</li>
                              <br>
                           </ul>
                        </li>
                        <li>
                           <strong>Quality Score Definition:</strong>
                           <ul>
                              <li>Quality Score = (Bug Score * 100) / Story Points</li>
                              <li>Bug Score = (P0 * 12) + (P1 * 6) + (P2 * 2) + (P3 * 1)</li>
                              <li>Story Points = Total story points utilised by Story & Task tickets</li>
                              <li>Lower Quality Score indicates better quality (ie. less bugs)</li>
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