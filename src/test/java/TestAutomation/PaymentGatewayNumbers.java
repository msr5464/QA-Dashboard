package TestAutomation;

import java.io.File;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import org.joda.time.LocalDate;
import org.json.JSONObject;
import org.testng.annotations.Test;
import TestAutomation.helpers.BugMetricsHelper;
import TestAutomation.helpers.ResultsHelper;
import TestAutomation.helpers.TestCoverageHelper;
import TestAutomation.helpers.ResultsHelper.FileType;
import TestAutomation.utils.CommonUtilities;
import TestAutomation.utils.GcpHelper;
import TestAutomation.utils.TestBase;

public class PaymentGatewayNumbers extends TestBase
{
	String entityName = "Payment Gateway";
	
	@Test(priority = 1, description = "Fetch the latest numbers from the TestRail for all the mentioned projects under Payment Gateway", groups = { "dataPopulator" })
	public void fetchTestCoverageData()
	{
		TestCoverageHelper testRailHelper = new TestCoverageHelper(testConfig);
		JSONObject testRailProjects = CommonUtilities.getJsonObjectFromJsonFile(getEntityConfigPath(entityName) + "TestRailConfig.json");
		testRailHelper.fetchDataFromTestRail(testConfig, testRailProjects, entityName);
	}
	
	@Test(priority = 2, description = "Fetch the latest numbers from the Jira for all the mentioned projects under Payment Gateway", groups = { "dataPopulator" })
	public void fetchBugMetricsData()
	{
		BugMetricsHelper bugMetricsHelper = new BugMetricsHelper(testConfig);
		JSONObject jiraProjects = CommonUtilities.getJsonObjectFromJsonFile(getEntityConfigPath(entityName) + "JiraConfig.json");
		
		HashMap<String, String> jiraFilters = new HashMap<>();
		String ticketTestedFilter = "project in ({$projectKey}) AND Issuetype in (Story, Chore, Task, Epic) AND (created >= '2022/01/01' OR updatedDate >= '2022/01/01' AND status not in (Done) AND created >= '2021/01/01') AND status in ('QA Test', Done, Testing, 'QA Done', 'Ready for QA', 'Ready to Deploy')";
		String reportedBugsFilter = "project in ({$projectKey}) AND Issuetype in (Bug, 'Story Problem') AND created >= '2022/01/01' AND status != Invalid";
		jiraFilters.put("ticketTestedFilter", ticketTestedFilter);
		jiraFilters.put("reportedBugsFilter", reportedBugsFilter);
		jiraFilters.put("environmentCustomField", "12873");
		jiraFilters.put("bugFoundByCustomField", "13271");
		jiraFilters.put("bugCategoryCustomField", "14905");
		bugMetricsHelper.fetchDataFromJira(testConfig, jiraProjects, entityName, LocalDate.now(), jiraFilters);
	}
	
	@Test(priority = 3, description = "Fetch the Thanos test results from Gcp and save to thanos DB for Payment Gateway", groups = { "dataPopulator" })
	public void fetchAutomationStabilityData()
	{
		String fileNamePrefix = entityName.replace(" ", "") + "_TestResults_";
		ResultsHelper resultsHelper = new ResultsHelper();
		
		List<String> downloadFileNames = GcpHelper.getFilesListInAscSortedOrder(testConfig, null, resultsHelper.bucketName, fileNamePrefix);
		Boolean isDownloadSuccess = GcpHelper.downloadFiles(testConfig, null, resultsHelper.bucketName, downloadFileNames, resultsHelper.getFilePath(testConfig));
		if (isDownloadSuccess && downloadFileNames.size() > 0)
		{
			List<String> processedFiles = resultsHelper.readCsvFileAndInsertToDB(testConfig, entityName, downloadFileNames, FileType.AutomationResults);
			GcpHelper.renameOrDeleteMultipleFiles(testConfig, null, resultsHelper.bucketName, processedFiles, false, "P_");
			
			// Code for calculating Pod level and Entity level data
			ArrayList<String> environmentAndGroupNamePairs = new ArrayList<String>();
			environmentAndGroupNamePairs.add("staging,regression");
			environmentAndGroupNamePairs.add("sandbox,regression");
			environmentAndGroupNamePairs.add("production,production");
			LocalDate startDate = LocalDate.now().minusDays(5);
			LocalDate endDate = LocalDate.now();
			for (LocalDate date = startDate; date.isBefore(endDate.plusDays(1)); date = date.plusDays(1))
			{
				JSONObject automationProjects = CommonUtilities.getJsonObjectFromJsonFile(getEntityConfigPath(entityName) + "AutomationConfig.json");
				resultsHelper.fetchAndUpdateResultsData(testConfig, entityName, automationProjects, environmentAndGroupNamePairs, date);
			}
		}
	}
	
	@Test(priority = 4, description = "Fetch the latest numbers of unit test coverage from gcp bucket and insert to DB", groups = { "fullDataPopulator", "automationResultsFetcher" })
	public void fetchCodeCoverageData()
	{
		String fileNamePrefix = entityName.replace(" ", "") + "_UnitTests_";
		ResultsHelper resultsHelper = new ResultsHelper();
		
		List<String> downloadFileNames = GcpHelper.getFilesListInAscSortedOrder(testConfig, null, resultsHelper.bucketName, fileNamePrefix);
		Boolean isDownloadSuccess = GcpHelper.downloadFiles(testConfig, null, resultsHelper.bucketName, downloadFileNames, resultsHelper.getFilePath(testConfig));
		if (isDownloadSuccess && downloadFileNames.size() > 0)
		{
			downloadFileNames = resultsHelper.readCsvFileAndInsertToDB(testConfig, entityName, downloadFileNames, FileType.UnitTestCoverage);
			GcpHelper.renameOrDeleteMultipleFiles(testConfig, null, resultsHelper.bucketName, downloadFileNames, false, "P_");
		}
	}
	
	private String getEntityConfigPath(String entityName)
	{
		return System.getProperty("user.dir") + File.separator + "Parameters" + File.separator + entityName.replaceAll(" ", "") + File.separator;
	}
}
