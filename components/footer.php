<div class="row center">
   <div id="footer" class="col-md-12 pb-3">
      <br>
      <span class="custom-text-3">Note: </span>
      <span class="custom-text-3">This website is created by <a href="https://www.linkedin.com/in/mukesh-rajput" style="color: lightgreen;"><b>Mukesh Singh Rajput</b></a> to track the day to day QA progress within the organisation </span>
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