<div class="row center">
   <div id="footer" class="col-md-12 pb-3">
      <br>
      <span class="custom-text-3">Note: </span> <span class="custom-text-3">This <a target="_blank" href="https://github.com/msr5464/QA-Dashboard" style="color: lightgreen;"><b>open source</b></a> dashboard is created by <a target="_blank" href="https://www.linkedin.com/in/mukesh-rajput" style="color: lightgreen;font-size:12px;"><b>Mukesh Rajput</b></a> to track the day to day Quality progress within any organisation </span>
      <br>
      <?php
         $latestEntry = getLastUpdatedTime();
         foreach ($latestEntry as $row) 
         {
            echo "<span class='custom-text-3'>Data last updated on: $row[createdAt]</span>";
         }
		?>
   </div>
</div>