<div class="row">
   <div class="col-md-12 pt-4 mt-3">
      <h4>
         <label class="handLink" id="dash">
         Entity - 
          </label>
         <label class="handLink" id="entityName">
            <?php 
            if (isset($_POST['entityName']) && $_POST['entityName'] != '') {
               $entityName = $_POST['entityName']; 
               if(preg_match('/^[a-z0-9& .\-]+$/i', $entityName))
                  echo $entityName;
            }
            ?> 
         </label>
      <h4>
   </div>
</div>
<div class="row mt-3">
   <div class="col-md-6">
      <ul id="filters" class="buttonwrapper">
         <li id="last1days" class="filter"><label>LAST 1 DAY</label></li>
         <li id="last7days" class="filter"><label>LAST 7 DAYS</label></li>
         <li id="last15days" class="filter"><label>LAST 15 DAYS</label></li>
         <li id="last30days" class="filter"><label>LAST 30 DAYS</label></li>
         <li id="last90days" class="filter"><label>LAST 90 DAYS</label></li>
         <li id="lastNdays" class="filter" data-toggle="modal" data-target="#myModal"><label>CUSTOM DATE FILTER</label></li>
      </ul>
   </div>
   <div class="col-md-6 text-right date-indicator">
      <label class="handLink" id="projectName"><?php 
      if(isset($_POST['projectNamesDropdown'])) { 
         $projectName="'".implode("','",$_POST['projectNamesDropdown'])."'";
         logger("projectName=".$projectName);
         if(preg_match('/^[a-z0-9& ().\'\-,\/]+$/i', $projectName))
            echo $projectName;
      }?></label>
      <input type='button' id="addFiltersButton" style="font-size: 14px;" value='Modify Filter' class="hide" />
      <div id="selectProject" class="project-dropdown">
         <form name='testform' method='POST' action='<?php echo $pageName; ?>.php'>
            <select name='projectNamesDropdown[]' id='projectNamesDropdown' style="width: 240px;" multiple data-placeholder="Choose your project..." class="chosen-select">
            </select>
            <select name='countryDropdown' id='countryDropdown' style="width: 130px;" multiple data-placeholder="Select Country" class="hide">
               <option value="Indonesia">Singapore</option>
               <option value="Indonesia">Indonesia</option>
               <option value="Vietnam">Vietnam</option>
               <option value="Vietnam">Philippines</option>
               <option value="Vietnam">Thailand</option>
               <option value="Vietnam">Malaysia</option>
            </select>
            <select name='platformDropdown' id='platformDropdown' style="width: 140px;" multiple data-placeholder="Select Platform" class="hide">
               <option value="Api">Api</option>
               <option value="Web">Web</option>
               <option value="Android">Android</option>
               <option value="iOS">iOS</option>
               
            </select>
            <select name='environmentDropdown' id='environmentDropdown' style="width: 140px;" multiple data-placeholder="Select Environment" class="hide">
               <option value="Staging">Staging</option>
               <option value="Production">Production</option>
            </select>
            <select name='prodBugCategoryDropdown' id='prodBugCategoryDropdown' style="width: 140px;" multiple data-placeholder="Select Bug Category" class="hide">
               <option value="PaymentGateway">PaymentGateway</option>
               <option value="Invalid">Invalid</option>
               <option value="Others">Others</option>
            </select>
            <select name='fctBugCategoryDropdown' id='fctBugCategoryDropdown' style="width: 140px;" multiple data-placeholder="Select Bug Category" class="hide">
               <option value="PaymentGateway">PaymentGateway</option>
               <option value="Partner">Partner</option>
               <option value="Invalid">Invalid</option>
            </select>
            <select name='stgBugCategoryDropdown' id='stgBugCategoryDropdown' style="width: 140px;" multiple data-placeholder="Select Bug Category" class="hide">
               <option value="PaymentGateway">PaymentGateway</option>
               <option value="Partner">Partner</option>
               <option value="Invalid">Invalid</option>
            </select>
            <select name='bugCategoryDropdown' id='bugCategoryDropdown' style="width: 140px;" multiple data-placeholder="Select Bug Category" class="hide">
               <option value="STG">STG</option>
               <option value="FCT">FCT</option>
               <option value="PRD">PRD</option>
            </select>
            <input type='submit' id="goButton" value='Go' />
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
            <h6 class="modal-title ">Choose Start Date & End Date as per your requirements</h4>
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