<html>
    <head>
    </head>
    <body>
        <form name="testform" method="POST" action="resultsPage.html">
            <?php 
            require "db_config.php";
            echo "<br>Select Your Project <select name='projectName' id='projectName'>
            <option value=''>Select One</option>";
            $sql = "select projectName from results group by projectName"; 
            foreach ($dbo->query($sql) as $row) 
            { 
               echo "<option value='$row[projectName]'>$row[projectName]</option>"; 
            } 
            ?>
            </select>
            <input type="submit" value="submit" />
        </form>
    <script src="js/jquery-2.1.4.js"></script>
    <script src="js/fusioncharts.js"></script>
    <script src="js/fusioncharts.charts.js"></script>
    <script src="js/themes/fusioncharts.theme.candy.js"></script>
    <script src="app.js"></script>
    <div id="chart-container1">Charts are loading here...</div>
    </body>
</html>
