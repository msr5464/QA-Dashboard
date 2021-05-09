<div class="row center">
   <div id="footer" class="col-md-12 pb-3">
         <br>
         <span class="custom-text-3">Note: </span>
         <span class="custom-text-3">This website is created by <a href="https://www.linkedin.com/in/mukesh-rajput" style="color: lightgreen;"><b>Mukesh Singh Rajput</b></a> to track the day to day QA progress within the organisation </span>
         <br>
         <?php 
		  require "server/db-config.php";
        $vertical = $verticalName;
        if($vertical == null || $vertical == "")
        {
            $vertical = $_COOKIE['selectedVertical'];
        }
         $sql = "select createdAt from ".str_replace(" ", "_", $vertical)."_".$pageName." order by id desc limit 1";
        if($projectName != null && $projectName != "")
		  {
            $sql = "select createdAt from ".str_replace(" ", "_", strtolower($vertical))."_".$pageName." where projectName='".$projectName."'order by id desc limit 1";
        }
		  foreach ($dbo->query($sql) as $row) 
		  { 
		    echo "<span class='custom-text-3'>Data last updated on: $row[createdAt]</span>"; 
		  }
		?>
   </div>
</div>