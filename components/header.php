<div class="row">
   <div class="col-md-12 pt-4 mt-3">
      <h4>
         <label class="handLink" id="verticalName">
            <?php 
            if (isset($_POST['verticalName']) && $_POST['verticalName'] != '') {
               $verticalName = $_POST['verticalName']; echo $verticalName;
            }
            ?> 
         </label>
         <label class="handLink" id="dash">
          - 
          </label>
         <label class="handLink" id="selectedYear">
            <?php 
            if (isset($_POST['selectedYear']) && $_POST['selectedYear'] != '') {
               $selectedYear = $_POST['selectedYear']; echo $selectedYear;
            }
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
         <li id="lastNdays" class="filter" data-toggle="modal" data-target="#myModal"><label>CUSTOM DATE FILTER</label></li>
      </ul>
   </div>
   <div class="col-md-5 text-right date-indicator">
      <label class="handLink" id="projectName"><?php 
      if(isset($_POST['projectNamesDropdown'])) { 
         $projectName="'".implode("','",$_POST['projectNamesDropdown'])."'";
         echo $projectName;
      }?></label>
      <div id="selectProject" class="project-dropdown">
         <form name='testform' method='POST' action='<?php echo $pageName; ?>.php'>
            <select name='projectNamesDropdown[]' id='projectNamesDropdown' style="width: 45%;" multiple data-placeholder="Choose your project..." class="chosen-select">
            </select>
            <input type='submit' value='submit' />
         </form>
      </div>
   </div>
</div>


<!-- Custom Filter Modal -->
  <div class="modal" id="myModal" role="dialog">
    <div class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title ">Create your Custom Date Filter</h1>
        </div>
        <div class="modal-body">
            <h6 class="modal-title ">Choose Start Date & End Date within selected year</h4>
            <label class="">Start Date:</label>
            <input type="date" id="startDate" name="startDate" value="2022-01-01">
            <br> &nbsp;
            <label class=""> End Date: </label>
            <input type="date" id="endDate" name="endDate" value="2022-12-31">
            <br>
            <button type="button" id="applyDateFilter" class="" data-dismiss="modal">Apply</button>
        </div>
        <div class="modal-footer">
          <button type="button" class="close" data-dismiss="modal">x</button>
        </div>
      </div>
    </div>
  </div>