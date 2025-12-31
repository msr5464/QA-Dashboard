package thanos;

import java.util.ArrayList;
import java.util.HashMap;

import org.joda.time.LocalDate;
import org.json.JSONObject;
import org.testng.annotations.Test;

import thanos.helpers.BugMetricsHelper;
import thanos.helpers.ResultsHelper;
import thanos.helpers.TestCoverageHelper;
import thanos.helpers.TestCoverageHelper.DataType;
import thanos.utils.CommonUtilities;
import thanos.utils.Config;
import thanos.utils.TestBase;

public class Payments extends TestBase
{
	String entityName = "PaymentGateway";
	String teamName = "Payments";
	
	@Test(priority = 1, dataProvider = "getTestConfig", description = "Fetch Only FCT testcases from the TestRail", groups = { "dataPopulator" })
	public void fetchFctTestcasesFromTestrail(Config testConfig)
	{
		TestCoverageHelper testRailHelper = new TestCoverageHelper(testConfig, DataType.FctTests);
		JSONObject testRailProjects = CommonUtilities.getJsonObjectFromJsonFile(getEntityConfigPath(teamName) + "TestRailConfig.json");
		testRailHelper.fetchDataFromTestRail(testConfig, testRailProjects, entityName, DataType.FctTests);
	}
	
	@Test(priority = 2, dataProvider = "getTestConfig", description = "Fetch All the testcases from the TestRail", groups = { "dataPopulator" })
	public void fetchAllTestcasesFromTestrail(Config testConfig)
	{
		TestCoverageHelper testRailHelper = new TestCoverageHelper(testConfig, DataType.AllTests);
		JSONObject testRailProjects = CommonUtilities.getJsonObjectFromJsonFile(getEntityConfigPath(teamName) + "TestRailConfig.json");
		testRailHelper.fetchDataFromTestRail(testConfig, testRailProjects, entityName, DataType.AllTests);
	}

	@Test(priority = 4, dataProvider = "getTestConfig", description = "Fetch tickets tested and ALL bugs from Jira using unified approach", groups = {"dataPopulator" })
	public void fetchBugMetricsData(Config testConfig) 
	{
		BugMetricsHelper bugMetricsHelper = new BugMetricsHelper(testConfig);
		JSONObject jiraProjects = CommonUtilities.getJsonObjectFromJsonFile(getEntityConfigPath(teamName) + "JiraConfig.json");

		HashMap<String, String> jiraFilters = new HashMap<>();
		jiraFilters.put("ticketTestedFilter", "Total-Tickets-Tested");
		jiraFilters.put("reportedBugsFilter", "Total-Bugs-Reported");

		testConfig.logComment("====================================================");
		testConfig.logComment("PART 1: Fetching ALL Bugs (STG+FCT+PRD) → paymentgateway_jira_bugs");
		testConfig.logComment("====================================================");
		testConfig.putRunTimeProperty("tableName", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_jira_bugs");
		bugMetricsHelper.fetchDataForProjectsAndVerticals(testConfig, jiraProjects, entityName, LocalDate.now(), jiraFilters, false);

		testConfig.logComment("====================================================");
		testConfig.logComment("PART 2: Fetching Tickets Tested → paymentgateway_jira_tickets");
		testConfig.logComment("====================================================");
		testConfig.putRunTimeProperty("tableName2", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_jira_tickets");
		bugMetricsHelper.fetchDataForProjectsAndVerticals(testConfig, jiraProjects, entityName, LocalDate.now(), jiraFilters, true);
	}

	@Test(priority = 3, dataProvider = "getTestConfig", description = "Code for calculating Vertical level and Entity level data", groups = {"dataPopulator" })
	public void fetchAutomationStabilityData(Config testConfig) 
	{
		ResultsHelper resultsHelper = new ResultsHelper();
		ArrayList<String> environmentAndGroupNamePairs = new ArrayList<String>();
		environmentAndGroupNamePairs.add("staging,regression");
		environmentAndGroupNamePairs.add("staging,androidCases");
		environmentAndGroupNamePairs.add("staging,iosCases");
		
		LocalDate startDate = LocalDate.now().minusDays(7);
		LocalDate endDate = LocalDate.now();
		for (LocalDate date = startDate; date.isBefore(endDate.plusDays(1)); date = date.plusDays(1)) {
			JSONObject automationProjects = CommonUtilities.getJsonObjectFromJsonFile(getEntityConfigPath(teamName) + "AutomationConfig.json");
			resultsHelper.fetchAndUpdateResultsData(testConfig, entityName, automationProjects, environmentAndGroupNamePairs, date);
		}
	}
}