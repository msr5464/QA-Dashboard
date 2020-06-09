<div class="row">
   <div id="footer" class="col-md-12 pb-3">
      <center>
         <br>
         <span class="custom-text-3">Note: </span>
         <span class="custom-text-3">This website is created by <a href="https://www.linkedin.com/in/mukesh-rajput" style="color: lightgreen;"><b>Mukesh Singh Rajput</b></a> to track the day to day QA progress within the organisation </span>
         <br>
         <?php 
		  require "server/db-config.php";
		  $sql = "select createdAt from ".$pageName." order by id desc limit 1"; 
		  foreach ($dbo->query($sql) as $row) 
		  { 
		    echo "<span class='custom-text-3'>Data last updated on: $row[createdAt]</span>"; 
		  }
		?>
      </center>
   </div>
</div>