<div id="nav" class="col-12 col-md-3 col-xl-2 bd-sidebar">
 <div class="row">
    <div class="col-md-12 col-8">
       <div class="text-sm-left text-md-center logo">
          <a href='/'>QUALITY DASHBOARD</a>
       </div>
    </div>
    <div class="col-md-12 col-4 text-right">
       <button class="btn btn-link bd-search-docs-toggle d-md-none p-0 ml-3 collapsed" type="button" data-toggle="collapse" data-target="#bd-docs-nav" aria-controls="bd-docs-nav" aria-expanded="false" aria-label="Toggle docs navigation">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30" focusable="false">
             <title>Menu</title>
             <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-miterlimit="10" d="M4 7h22M4 15h22M4 23h22"></path>
          </svg>
       </button>
    </div>
 </div>
 <nav class="bd-links collapse" id="bd-docs-nav">
    <ul class="left-options">
       <li class="left-option <?php echo $activateResultPage; ?>" id="results">
          <a href="results.php">
          Thanos Results
          </a>
       </li>
       <li class="left-option <?php echo $activateTestrailPage; ?>" id="testrail">
          <a href="testrail.php">
          Testrail Numbers
          </a>
       </li>
       <li class="left-option <?php echo $activateJiraPage; ?>" id="jira">
          <a href="jira.php">
          Bug Metrics
          </a>
       </li>
    </ul>
 </nav>
</div>