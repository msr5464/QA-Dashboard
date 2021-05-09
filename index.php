<!DOCTYPE html>
<html lang="en">
   <head>
      <title>QA Dashboard</title>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <meta name="theme-color" content="#000000">
      <link rel="stylesheet" href="css/bootstrap.min.css">
      <link rel="stylesheet" href="css/website.css?1008">
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
                           <h3>QA DASHBOARD</h3>
                        </div>
                     </div>
                     <div class="row mt-3">
                        <div class="col-md-7">
                        </div>
                        <div class="col-md-5 text-right date-indicator" id="date">THANOS</div>
                     </div>
                     <div id="chart-container">
                        <br><br>
                        <center>
                           <p class="paragraph1">This dashboard is created to track all QA activities at one centralised place!
                           </p>
                           <br>
                           <p class="paragraph2">To fetch the numbers/metrics, please select your respective PDG / Vertical name!
                           </p>
                           <br>
                           <div class="mt-3 db-chart">
                              <div id="parent1" class="col-lg-6">
                                 <div class="chart-card mb-4">
                                    <center>
                                       <div class="chart-title" id="text2"><b>Select the name of your PDG / Vertical name:</b></div>
                                       <div id="chart1" class="project-dropdown">
<form name="testform" method="POST" action="results.php">
   <br>
   <select name='selectedYear' id='selectedYear'>
      <option value='2021'>2021</option>
      <option value='2020'>2020</option>
   </select>
   <select name='verticalName' id='verticalName'>
      <option value=''>Choose your vertical</option>
      <?php 
         require "server/db-config.php";
         $sql = "select verticalName,tableName from vertical order by verticalName desc";
         foreach ($dbo->query($sql) as $row) 
         { 
           echo "<option value='$row[verticalName]'>$row[verticalName]</option>";
         }
         ?>
   </select>
   &nbsp;&nbsp;&nbsp;
   <script type="text/javascript">
      var x = getCookie('selectedYear');
      if (x)
      {
         document.getElementById("selectedYear").value = x;
      }
      var y = getCookie('selectedVertical');
      if (y)
      {
         document.getElementById("verticalName").value = y;
      }
        function getCookie(name) {
          var nameEQ = name + "=";
          var ca = document.cookie.split(';');
          for(var i=0;i < ca.length;i++) {
              var c = ca[i];
              while (c.charAt(0)==' ') c = c.substring(1,c.length);
              if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
          }
          return null;
      }
   </script>
   <input type="submit" value="submit" />
</form>
                                          <br>
                                       </div>
                                    </center>
                                 </div>
                              </div>
                           </div>
                           <br>
                           <img class="dashboardImage" alt="Dashboard Image" src="images/dashboard.svg">
                        </center>
                     </div>
                     <div class="row center">
                        <div id="footer" class="col-md-12 pb-3">
                           <br>
                           <span class="custom-text-3">Note: </span>
                           <span class="custom-text-3">This website is created by <a href="https://www.linkedin.com/in/mukesh-rajput" style="color: lightgreen;"><b>Mukesh Singh Rajput</b></a> to track the day to day QA progress within the organisation </span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </body>
</html>