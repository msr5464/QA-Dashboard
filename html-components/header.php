<div class="row">
   <div class="col-md-12 pt-4 mt-3">
      <h4>
         <label class="handLink" id="verticalName">
            <?php 
               $verticalName = $_POST['verticalName']; echo $verticalName
            ?> 
         </label>
          - 
         <label class="handLink" id="selectedYear">
            <?php 
               $selectedYear = $_POST['selectedYear']; echo $selectedYear
            ?> 
         </label>
      <h4>
   </div>
</div>
<div class="row mt-3">
   <div class="col-md-7">
      <ul id="filters" class="buttonwrapper">
         <li id="last1days" class="filter"><label>LAST 1 DAY</label></li>
         <li id="last7days" class="filter"><label>LAST 7 DAYS</label></li>
         <li id="last15days" class="filter"><label>LAST 15 DAYS</label></li>
         <li id="last30days" class="filter"><label>LAST 30 DAYS</label></li>
         <li id="last90days" class="filter"><label>LAST 90 DAYS</label></li>
         <li id="last180days" class="filter"><label>LAST 180 DAYS</label></li>
         <li id="last365days" class="filter"><label>LAST 365 DAYS</label></li>
      </ul>
   </div>
   <div class="col-md-5 text-right date-indicator">
      <label class="handLink" id="projectName"><?php $projectName = $_POST['projectNames']; echo $projectName ?></label>
      <div id="selectProject" class="project-dropdown">
         <form name='testform' method='POST' action='<?php echo $pageName; ?>.php'>
            <select name='projectNames' id='projectNames'>
               <option value=''>Choose your project</option>
            </select>
            <input type='submit' value='submit' />
         </form>
      </div>
   </div>
</div>