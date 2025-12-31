<div id="nav" class="col-12 col-md-3 col-xl-2 bd-sidebar">
 <div class="row">
    <div class="col-md-12 col-8">
       <div class="text-sm-left text-md-center logo">
          <a id="qaDashboard" href='/' style="color: #00ff00;">QUALITY DASHBOARD</a>
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
      <li class="hide left-option <?php echo $activateProdTestsPage; ?>" id="prod-tests">
          <a href="prod-tests.php">
           ProdSanity Tests - Total
          </a>
       </li>
       <li class="hide left-option <?php echo $activateProdTestsAutoPage; ?>" id="prod-tests-auto">
          <a href="prod-tests-auto.php">
          ProdSanity Tests - Automatable
          </a>
       </li>
       <li class="hide left-option <?php echo $activateFctTestsPage; ?>" id="fct-tests">
          <a href="tests-fct.php">
           FCT Tests - Total
          </a>
       </li>
       <li class="hide left-option <?php echo $activateFctTestsAutoPage; ?>" id="fct-tests-auto">
          <a href="tests-fct-auto.php">
          FCT Tests - Automatable
          </a>
       </li>
       <li class="hide left-option <?php echo $activateResultPage; ?>" id="results">
          <a href="results.php">
          Automation Stability
          </a>
       </li>
       <li class="hide left-option <?php echo $activateProdBugsPage; ?>" id="prod-bugs">
          <a href="bugs-prod.php">
          PRD / Production Bugs
          </a>
       </li>
       <li class="hide left-option <?php echo $activateFctBugsPage; ?>" id="fct-bugs">
          <a href="bugs-fct.php">
          FCT / Regression Bugs
          </a>
       </li>
       <li class="hide left-option <?php echo $activateStagingBugsPage; ?>" id="staging-bugs">
          <a href="bugs-staging.php">
          STG / Feature Bugs
          </a>
       </li>
       <li class="hide left-option <?php echo $activateTotalBugsPage; ?>" id="total-bugs">
          <a href="bugs-total.php">
          Overall Bug Metrics
          </a>
       </li>
       <li class="hide left-option <?php echo $activateAllTestsPage; ?>" id="all-tests">
          <a href="tests-all.php">
          Overall Tests - Total
          </a>
       </li>
       <li class="hide left-option <?php echo $activateAllTestsAutoPage; ?>" id="all-tests-auto">
          <a href="tests-all-auto.php">
          Overall Tests - Automatable
          </a>
       </li>
       <li class="left-option">
       </li>
       <li class="left-option active">
          <img class="logo" src="images/ThanosLogo.png" title="Powered by Thanos and created by Mukesh Rajput">
       </li>
    </ul>
 </nav>
</div>