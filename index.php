<html>
    <head>
        <script src="js/jquery-2.1.4.js"></script>
        <script src="js/fusioncharts.js"></script>
        <script src="js/fusioncharts.charts.js"></script>
        <script src="js/themes/fusioncharts.theme.candy.js"></script>
        <link rel="stylesheet" type="text/css" href="index_css.css">
        <script src="index_chart.js"></script>
        <script src="testrail_latest_chart.js"></script>

    </head>
    <body>
        <div id="header">
            
        </div>
        <div id="container">
            <div id="first">
            <form name="testform" method="POST" action="p0p1.html">
                <?php 
                    require "db_config.php";
                    echo "<br><center><label>Select Your Thanos Project</label> <select name='projectName' id='projectName'>
                    <option value=''>Select One</option>";

                    $sql = "select projectName from results group by projectName"; 
                    foreach ($dbo->query($sql) as $row) 
                    { 
                       echo "<option value='$row[projectName]'>$row[projectName]</option>"; 
                    } 
                ?>
                </select>
                <input type="submit" value="submit" />
                </center>
            </form>
            </div>

            <div id="second">
            <form name="testform" method="POST" action="p0p1.html">
                <?php 
                    require "db_config.php";
                    echo "<br><center><label>Select Your Testrail Project</label> <select name='projectName' id='projectName'>
                    <option value=''>Select One</option>";

                    $sql = "select projectName from testrail group by projectName"; 
                    foreach ($dbo->query($sql) as $row) 
                    { 
                       echo "<option value='$row[projectName]'>$row[projectName]</option>"; 
                    } 
                ?>
                </select>
                <input type="submit" value="submit" />
                </center>
            </form>
            </div>
            
            <div id="third">
                <br>
                <a href="coverage.html">Test Automation Coverage</a>
                <br><br>
                <a href="testrail_p0p1.html">Testrail - P0 Automation Coverage</a>
                <br><br>
                <a href="testrail_latest.html">Testrail - Automation Testcases Metrics</a>
                <br><br>
                <a href="testrail_single_pie.html">Testrail - Automation Cases Breakdown for Saudagar</a>
            </div>
        </div>
        <div id="chart-container">
        <center>
        <label id="chart-container1">Charts are loading here...</label>
        <br><br>
        <label id="chart-container2">Charts are loading here...</label>
        </center>
        </div>
    </body>
</html>
