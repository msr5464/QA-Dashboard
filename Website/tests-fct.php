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
         $pageName = "tests-fct";
         $dbTableName = "tests_fct";
         require "components/head-title.php";
      ?>
   </head>
   <body data-gr-c-s-loaded="true">
      <div id="root">
         <div data-reactroot="">
            <div class="container-fluid">
               <div class="row flex-xl-nowrap">
                  <?php
                     $activateFctTestsPage = "active";
                     require "components/left-menu.php";
                  ?>
                  <div id="content-body" class="col-12 col-md-9 col-xl-10 pl-4 pr-4 bd-content">
                     <?php
                        require "components/header.php";
                     ?>
                     <div class="row mt-3 db-chart">
                        <div id="parent1" class="col-lg-6 col-xl-4 column">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">Overall FCT Coverage</div>
                              <div id="chart1" class="chart gaugeContainer">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container1"><img src="../images/loader.gif" height="100" /></label>
                                    <label class="linearChart" id="linear-chart-container1"></label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent2" class="col-lg-6 col-xl-4 column">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">P0 FCT Coverage</div>
                              <div id="chart2" class="chart gaugeContainer">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container2"><img src="../images/loader.gif" height="100" /></label>
                                    <label class="linearChart" id="linear-chart-container2"></label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent3" class="col-lg-6 col-xl-4 column">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">P1 FCT Coverage</div>
                              <div id="chart3" class="chart gaugeContainer">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container3"><img src="../images/loader.gif" height="100" /></label>
                                    <label class="linearChart" id="linear-chart-container3"></label>
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
                           <!--div class="defaultChart">
                              <br>
                              <label id="column-chart-container2">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container3">Chart is loading here...</label>
                           </div-->
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
                           <div class="projectChart">
                              <label id="column-chart-container7">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="line-chart-container1">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                           <br>
                           <table id="issuesList" style="width:100%">
                              <thead>
                                 <tr style="height:45px;">
                                    <th colspan="11" class="tableMainHeading">
                                       <center
                                          <label id="headerRow">List of Testcases</label>
                                       </center>
                                    </th>
                                 </tr>
                                 <tr style="height:25px;">
                                    <th style='color:#ea1212'>Testrail Id</th>
                                     <th style='color:#ea1212'>Project Name</th>
                                     <th style='color:#ea1212'>Title / Description</th>
                                     <th style='color:#ea1212'>Priority</th>
                                     <th style='color:#ea1212'>Test Type</th>
                                     <th style='color:#ea1212'>Platforms</th>
                                     <th style='color:#ea1212'>ApiAutomation?</th>
                                     <th style='color:#ea1212'>WebAutomation?</th>
                                     <th style='color:#ea1212'>AndroidAutomation?</th>
                                     <th style='color:#ea1212'>IosAutomation?</th>
                                     <th style='color:#ea1212'>References</th>
                                 </tr>
                              </thead>
                              <tbody id="issuesBody">
                              </tbody>
                           </table>
                        </div>
                        </center>
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
