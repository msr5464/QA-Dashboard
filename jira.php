<?php 
 require("utils/config.php");
 $fullData = getVerticalTableData();
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <script type='text/javascript'>
      var verticalTableData = <?php echo json_encode($fullData); ?>;
      </script>
      <?php
         $pageName = "jira";
         require "components/head-title.php";
      ?>
   </head>
   <body data-gr-c-s-loaded="true">
      <div id="root">
         <div data-reactroot="">
            <div class="container-fluid">
               <div class="row flex-xl-nowrap">
                  <?php
                     $activateJiraPage = "active";
                     require "components/left-menu.php";
                  ?>
                  <div id="content-body" class="col-12 col-md-9 col-xl-10 pl-4 pr-4 bd-content">
                     <?php
                        require "components/header.php";
                     ?>
                     <div class="row mt-3 db-chart">
                        <div id="parent1" class="col-lg-6 col-xl-4 column">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">Total Tickets Tested</div>
                              <div id="chart1" class="chart gaugeContainer" style="height:120px">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container1"><img src="../images/loader.gif" height="100" /></label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent2" class="col-lg-6 col-xl-4 column">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">Staging Bugs Found</div>
                              <div id="chart2" class="chart gaugeContainer" style="height:120px">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container2"><img src="../images/loader.gif" height="100" /></label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent3" class="col-lg-6 col-xl-4 column">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">Production Bugs Found</div>
                              <div id="chart3" class="chart gaugeContainer" style="height:120px">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container3"><img src="../images/loader.gif" height="100" /></label>
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
                           <div class="projectChart">
                              <br>
                              <label id="pie-chart-container1">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="pie-chart-container2">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="line-chart-container1">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="line-chart-container2">Chart is loading here...</label>
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
