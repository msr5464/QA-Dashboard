<!DOCTYPE html>
<html lang="en">
   <head>
      <?php
         $pageName = "testrail";
         require "html-components/head-title.php";
      ?>
   </head>
   <body data-gr-c-s-loaded="true">
      <div id="root">
         <div data-reactroot="">
            <div class="container-fluid">
               <div class="row flex-xl-nowrap">
                  <?php
                     $activateTestrailPage = "active";
                     require "html-components/left-menu.php";
                  ?>
                  <div id="content-body" class="col-12 col-md-9 col-xl-10 pl-4 pr-4 bd-content">
                     <?php
                        require "html-components/header.php";
                     ?>
                     <div class="row mt-3 db-chart">
                        <div id="parent1" class="col-lg-6 col-xl-4">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">Full Automation Coverage Percentage</div>
                              <div id="chart1" class="chart">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container1">Project not selected.<br>No data to display!</label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent2" class="col-lg-6 col-xl-4" style="display: block; width: auto; height: auto;">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">P0 Cases Coverage Percentage</div>
                              <div id="chart2" class="chart">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container2">Project not selected.<br>No data to display!</label>
                                 </center>
                              </div>
                           </div>
                        </div>
                        <div id="parent3" class="col-lg-6 col-xl-4" style="display: block; width: auto; height: auto;">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">P1 Cases Coverage Percentage</div>
                              <div id="chart3" class="chart">
                                 <center>
                                    <label class="gauge custom-text-2" id="gauge-chart-container3">Project not selected.<br>No data to display!</label>
                                 </center>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div id="chart-container">
                        <center>
                           <div id="warning">
                              <label class="paragraph1" style="color:yellow">Currently showing graphs combining all the projects together!</label><br>
                              <label class="paragraph2" style="color:orange">For any project specific data points, first select any project from the above shown dropdown!</label>
                           </div>
                           <div class="defaultChart">
                              <br>
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
                           <!--div class="defaultChart">
                              <br>
                              <label id="column-chart-container4">Chart is loading here...</label>
                           </div-->
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
                              <label id="column-chart-container7">Chart is loading here...</label>
                           </div>
                           <div class="defaultChart">
                              <br>
                              <label id="column-chart-container8">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="pie-chart-container1">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="column-chart-container9">Chart is loading here...</label>
                           </div>
                           <div class="projectChart">
                              <br>
                              <label id="line-chart-container1">Chart is loading here...</label>
                           </div>
                        </center>
                     </div>
                     <?php
                        require "html-components/footer.php";
                     ?>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </body>
</html>