<div class="row">
   <div class="col-md-12 pt-4 mt-3">
      <h2></h2>
   </div>
</div>
<div class="row mt-3">
   <div class="col-md-7">
      <ul class="buttonwrapper">
         <li id="week" class="filter"><label id="weeklyData">WEEKLY</label></li>
         <li id="month" class="filter"><label id="monthlyData">MONTHLY</label></li>
         <li id="quarter" class="filter"><label id="quarterlyData">QUARTERLY</label></li>
         <li id="year" class="filter"><label id="yearlyData">YEARLY</label></li>
      </ul>
   </div>
   <div class="col-md-5 text-right date-indicator">
      <label class="handLink" id="projectName"><?php echo $_POST['projectName'] ?? ''; ?></label>
      <div id="selectProject" class="project-dropdown">
         <?php
            echo "<form name='testform' method='POST' action='".$pageName.".php'>";
            require "html-components/project-dropdown.php";
            echo "&nbsp;<input type='submit' value='submit' /></form>";
         ?>
      </div>
   </div>
</div>