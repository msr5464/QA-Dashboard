<?php 
 require("utils/config.php");
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <title>QA Dashboard</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="theme-color" content="#000000">
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/website.css?<?php echo $version; ?>">
        <script src="js/common-functions.js?<?php echo $version; ?>"></script>
        <script type="text/javascript">
            var x = getCookie('entity');
            if (x)
            {
               window.location.href = 'testrail.php';
            }
        </script>
    </head>

    <body data-gr-c-s-loaded="true">
        <div id="root">
            <div data-reactroot="">
                <div class="container-fluid">
                    <div class="row flex-xl-nowrap">
                    <?php
                     $fullData = getActiveVerticalData();
                     require "components/left-menu.php";
                    ?>
                            <div id="content-body" class="col-12 col-md-9 col-xl-10 pl-4 pr-4 bd-content">
                                <div class="row">
                                    <div class="col-md-12 pt-4 mt-3">
                                        <h3>QA DASHBOARD</h3> </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-7"> </div>
                                    <div class="col-md-5 text-right date-indicator" id="date">THANOS</div>
                                </div>
                                <div id="chart-container">
                                    <br>
                                    <br>
                                    <center>
                                        <p class="paragraph1">This dashboard is created to track overall Quality Metrics for whole organization at one place! </p>
                                        <br>
                                        <p id="demo" class="paragraph2">To fetch the numbers/metrics, please select your respective Entity / Vertical name! </p>
                                        <br>
                                        <div class="mt-3 db-chart">
                                            <div id="parent1" class="col-lg-6">
                                                <div class="chart-card mb-4">
                                                    <center>
                                                        <div class="chart-title greyBackground" id="text2"><b>Select the name of your Entity / Vertical name:</b></div>
                                                        <div id="chart1" class="project-dropdown greyBackground">
                                                            <form name="testform" method="POST" action="testrail.php">
                                                                <br>
                                                                <select name='verticalName' id='verticalName' style="width: 35%">
                                                                    <option value=''>Choose your Entity Name</option>
                                                                    <?php
                                                                        foreach ($fullData as $row) 
                                                                        {
                                                                            echo "<option value='$row[verticalName]'>$row[verticalName]</option>";
                                                                        }
                                                                    ?>
                                                                </select>
                                                                &nbsp;&nbsp;
                                                                <button type="submit" value="submit">submit</button></form>
                                                     <br> </div>
                                                    </center>
                                                </div>
                                            </div>
                                        </div>
                                        <br> <img class="dashboardImage" alt="Dashboard Image" src="images/dashboard.svg"> </center>
                                </div>
                                <div class="row center">
                                    <div id="footer" class="col-md-12 pb-3">
                                        <br> <span class="custom-text-3">Note: </span> <span class="custom-text-3">This <a target="_blank" href="https://github.com/msr5464/QA-Dashboard" style="color: lightgreen;"><b>open source</b></a> dashboard is created by <a target="_blank" href="https://www.linkedin.com/in/mukesh-rajput" style="color: lightgreen;font-size:12px;"><b>Mukesh Rajput</b></a> to track the day to day Quality progress within any organisation </span> </div>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>