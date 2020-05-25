<!DOCTYPE html>
<html lang="en">
   <head>
      <title>QA Dashboard</title>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
      <meta name="theme-color" content="#000000">
      <link rel="stylesheet" href="css/bootstrap.min.css">
      <link rel="stylesheet" href="css/main_style.css">

   </head>
   <body data-gr-c-s-loaded="true">
      <div id="root">
         <div data-reactroot="">
            <div class="container-fluid">
               <div class="row flex-xl-nowrap">
                  <div id="nav" class="col-12 col-md-3 col-xl-2 bd-sidebar">
                     <div class="row">
                        <div class="col-md-12 col-8">
                           <div class="text-sm-left text-md-center logo">
                              <a href='/'>QUALITY DASHBOARD</a>
                           </div>
                        </div>
                        <div class="col-md-12 col-4 text-right">
                           <button class="btn btn-link bd-search-docs-toggle d-md-none p-0 ml-3 collapsed" type="button" data-toggle="collapse" data-target="#bd-docs-nav" aria-controls="bd-docs-nav" aria-expanded="false" aria-label="Toggle docs navigation">
                              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30" focusable="false">
                                 <title>Menu</title>
                                 <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-miterlimit="10" d="M4 7h22M4 15h22M4 23h22"></path>
                              </svg>
                           </button>
                        </div>
                     </div>
                     <nav class="bd-links collapse" id="bd-docs-nav">
                        <ul class="left-options">
                           <li class="left-option" id="results">
                              <a href="results.php">
                              Thanos Results
                              </a>
                           </li>
                           <li class="left-option" id="testrail">
                              <a href="testrail.php">
                              Testrail Numbers
                              </a>
                           </li>
                           <li class="left-option" id="jira">
                              <a href="jira.php">
                              Bug Metrics
                              </a>
                           </li>
                        </ul>
                     </nav>
                  </div>
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
                              <div class="chart-title" id="text2">FOR THANOS DATA</div>
                              <div id="chart1" class="project-dropdown">
                                 <form name="testform" method="POST" action="results.php">
                                    <?php 
                                       require "db_config.php";
                                       echo "<br><center><select name='projectName' id='projectName'>
                                       <option value=''>Choose your project</option>";
                                       
                                       $sql = "select projectName from results group by projectName"; 
                                       foreach ($dbo->query($sql) as $row) 
                                       { 
                                          echo "<option value='$row[projectName]'>$row[projectName]</option>"; 
                                       } 
                                       ?>
                                    </select>
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
                                    <?php 
                                       require "db_config.php";
                                       echo "<br><center><select name='projectName' id='projectName'>
                                       <option value=''>Choose your project</option>";
                                       
                                       $sql = "select projectName from testrail group by projectName"; 
                                       foreach ($dbo->query($sql) as $row) 
                                       { 
                                          echo "<option value='$row[projectName]'>$row[projectName]</option>"; 
                                       } 
                                       ?>
                                    </select>
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
                                    <?php 
                                       require "db_config.php";
                                       echo "<br><center><select name='projectName' id='projectName'>
                                       <option value=''>Choose your project</option>";
                                       
                                       $sql = "select projectName from jira group by projectName"; 
                                       foreach ($dbo->query($sql) as $row) 
                                       { 
                                          echo "<option value='$row[projectName]'>$row[projectName]</option>"; 
                                       } 
                                       ?>
                                    </select>
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
                           <img class="dashboardImage" src="assets/dashboard.svg">
                        </center>
                     </div>
                     <div class="row">
                        <div class="col-md-12 pb-3">
                           <center>
                              <br>
                              <span class="custom-text-3">Note: </span>
                              <span class="custom-text-3">This website is created by Mukesh Singh Rajput to track the day to day QA Activities within the organisation </span>
                           </center>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </body>
</html>