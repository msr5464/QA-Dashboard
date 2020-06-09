<!DOCTYPE html>
<html lang="en">
   <head>
      <?php
         $pageName = "index";
         require "html-components/head-title.php";
      ?>
   </head>
   <body data-gr-c-s-loaded="true">
      <div id="root">
         <div data-reactroot="">
            <div class="container-fluid">
               <div class="row flex-xl-nowrap">
                  <?php 
                     require "html-components/left-menu.php";
                  ?>
                  <div id="content-body" class="col-12 col-md-9 col-xl-10 pl-4 pr-4 bd-content">
                     <div class="row">
                        <div class="col-md-12 pt-4 mt-3">
                           <h2></h2>
                        </div>
                     </div>
                     <div class="row mt-3">
                        <div class="col-md-7">
                        </div>
                        <div class="col-md-5 text-right date-indicator" id="date">THANOS</div>
                     </div>
                     <div class="row mt-3 db-chart">
                        <div id="parent1" class="col-lg-6 col-xl-4">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">FOR RESULTS DATA</div>
                              <div id="chart1" class="project-dropdown">
                                 <form name="testform" method="POST" action="results.php">
                                    <center><br>
                                    <?php
                                       $pageName = "results";
                                       require "html-components/project-dropdown.php";
                                    ?>
                                    &nbsp;&nbsp;&nbsp;
                                    <input type="submit" value="submit" />
                                    </center>
                                 </form>
                                 <br>
                              </div>
                           </div>
                        </div>
                        <div id="parent2" class="col-lg-6 col-xl-4" style="display: block; width: auto; height: auto;">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text2">FOR TESTRAIL DATA</div>
                              <div id="chart2" class="project-dropdown">
                                 <form name="testform" method="POST" action="testrail.php">
                                    <center><br>
                                    <?php
                                       $pageName = "testrail";
                                       require "html-components/project-dropdown.php";
                                    ?>
                                    &nbsp;&nbsp;&nbsp;
                                    <input type="submit" value="submit" />
                                    </center>
                                 </form>
                                 <br>
                              </div>
                           </div>
                        </div>
                        <div id="parent3" class="col-lg-6 col-xl-4" style="display: block; width: auto; height: auto;">
                           <div class="chart-card mb-4">
                              <div class="chart-title" id="text3">FOR JIRA DATA</div>
                              <div id="chart3" class="project-dropdown">
                                 <form name="testform" method="POST" action="jira.php">
                                    <center><br>
                                    <?php
                                       $pageName = "jira";
                                       require "html-components/project-dropdown.php";
                                    ?>
                                    &nbsp;&nbsp;&nbsp;
                                    <input type="submit" value="submit" />
                                    </center>
                                 </form>
                                 <br>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div id="chart-container">
                        <center>
                              <p class="paragraph1">This dashboard helps to track the overall QA progress at once centralised place!
                              </p>
                              <br>
                              <p class="paragraph2">To fetch the numbers/metrics, please select your respective project!
                              </p>
                           
                           <br><br>
                           <img class="dashboardImage" src="images/dashboard.svg">
                        </center>
                     </div>
                     <?php
                     $pageName = "results";
                        require "html-components/footer.php";
                     ?>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </body>
</html>