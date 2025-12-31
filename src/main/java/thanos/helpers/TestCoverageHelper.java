package thanos.helpers;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.TimeZone;

import org.apache.commons.lang3.StringUtils;
import org.joda.time.LocalDate;
import org.joda.time.LocalTime;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;

import thanos.utils.CommonUtilities;
import thanos.utils.Config;
import thanos.utils.Database;
import thanos.utils.Database.DatabaseName;
import thanos.utils.Database.QueryType;
import thanos.utils.TestRailClient;

public class TestCoverageHelper {
	private HashMap<String, String> verticalLevelData;
	private HashMap<String, String> entityLevelData;
	private TestRailClient testRailClient = null;
	
	public enum DataType
	{
		AllTests,
		FctTests,
		ProdTests;
	}

	@SuppressWarnings("serial")
	public TestCoverageHelper(Config testConfig, DataType dataType) {
		testRailClient = connectToTestRail(testConfig);
		verticalLevelData = new HashMap<String, String>() {
			{
				put("totalCases", "0");
				put("totalAutomationCases", "0");
				put("alreadyAutomated", "0");
				put("p0Cases", "0");
				put("p0AutomationCases", "0");
				put("p0AutomatedCases", "0");
				put("p1Cases", "0");
				put("p1AutomationCases", "0");
				put("p1AutomatedCases", "0");
				put("p2Cases", "0");
				put("p2AutomationCases", "0");
				put("p2AutomatedCases", "0");
				put("api_totalCases", "0");
				put("api_totalAutomationCases", "0");
				put("api_alreadyAutomated", "0");
				put("api_p0Cases", "0");
				put("api_p0AutomationCases", "0");
				put("api_p0AutomatedCases", "0");
				put("api_p1Cases", "0");
				put("api_p1AutomationCases", "0");
				put("api_p1AutomatedCases", "0");
				put("api_p2Cases", "0");
				put("api_p2AutomationCases", "0");
				put("api_p2AutomatedCases", "0");
				put("web_totalCases", "0");
				put("web_totalAutomationCases", "0");
				put("web_alreadyAutomated", "0");
				put("web_p0Cases", "0");
				put("web_p0AutomationCases", "0");
				put("web_p0AutomatedCases", "0");
				put("web_p1Cases", "0");
				put("web_p1AutomationCases", "0");
				put("web_p1AutomatedCases", "0");
				put("web_p2Cases", "0");
				put("web_p2AutomationCases", "0");
				put("web_p2AutomatedCases", "0");
				put("android_totalCases", "0");
				put("android_totalAutomationCases", "0");
				put("android_alreadyAutomated", "0");
				put("android_p0Cases", "0");
				put("android_p0AutomationCases", "0");
				put("android_p0AutomatedCases", "0");
				put("android_p1Cases", "0");
				put("android_p1AutomationCases", "0");
				put("android_p1AutomatedCases", "0");
				put("android_p2Cases", "0");
				put("android_p2AutomationCases", "0");
				put("android_p2AutomatedCases", "0");
				put("ios_totalCases", "0");
				put("ios_totalAutomationCases", "0");
				put("ios_alreadyAutomated", "0");
				put("ios_p0Cases", "0");
				put("ios_p0AutomationCases", "0");
				put("ios_p0AutomatedCases", "0");
				put("ios_p1Cases", "0");
				put("ios_p1AutomationCases", "0");
				put("ios_p1AutomatedCases", "0");
				put("ios_p2Cases", "0");
				put("ios_p2AutomationCases", "0");
				put("ios_p2AutomatedCases", "0");
			}
		};
		entityLevelData = new HashMap<>();
	}

	private TestRailClient connectToTestRail(Config testConfig) {
		TestRailClient client = new TestRailClient(testConfig.getRunTimeProperty("testRailHostUrl"));
		client.setUser(CommonUtilities.decryptMessage(System.getProperty("testRailUsername").getBytes()));
		client.setPassword(CommonUtilities.decryptMessage(System.getProperty("testRailPassword").getBytes()));
		testConfig.logComment("Connected to TestRail server successfully.");
		return client;
	}

	public void fetchDataFromTestRail(Config testConfig, org.json.JSONObject jsonObject, String entityName, DataType dataType) {
		LocalDate date = LocalDate.now();
		//testConfig.putRunTimeProperty("tableName", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_testrail");
		switch(dataType)
		{
		case AllTests:
			testConfig.putRunTimeProperty("tableName", entityName.toLowerCase().trim().replaceAll(" ", "_")+"_tests_all");
			break;
		case FctTests:
			testConfig.putRunTimeProperty("tableName", entityName.toLowerCase().trim().replaceAll(" ", "_")+"_tests_fct");
			break;
		}
		fetchDataForProjectsAndVerticals(testConfig, jsonObject, entityName, dataType, date);

		// At the end, insert data of Entity to All Entities table
		if (entityLevelData.size() != 0) {
			switch(dataType)
			{
			case AllTests:
				testConfig.putRunTimeProperty("tableName", "entities_tests_all");
				break;
			case FctTests:
				testConfig.putRunTimeProperty("tableName", "entities_tests_fct");
				break;
			}
			addTestrailDataInDatabase(testConfig, date, entityName, entityLevelData);
		}
	}

	private void fetchDataForProjectsAndVerticals(Config testConfig, org.json.JSONObject jsonObject, String entityName, DataType dataType, LocalDate date) {
		Iterator<String> testRailConfigKeys = jsonObject.keys();
		while (testRailConfigKeys.hasNext()) {
			String testRailConfigKey = testRailConfigKeys.next();
			if (testRailConfigKey.equals("testRailSuites")) {
				fetchProjectWiseTestRailDataAndInsertIntoDb(testConfig, jsonObject.getJSONArray("testRailSuites"), dataType, date);
				if (testConfig.getRunTimeProperty("FetchEntityLevelData").equalsIgnoreCase("true"))
					updateEntityLevelTestRailNumbers(testConfig, entityLevelData);
			} else {
				if (jsonObject.get(testRailConfigKey) instanceof org.json.JSONObject) {
					String verticalName = testRailConfigKey;
					if (!testRailConfigKey.toLowerCase().startsWith("vertical"))
						verticalName = "Vertical - " + testRailConfigKey;
					testConfig.putRunTimeProperty("verticalName", verticalName);
					fetchDataForProjectsAndVerticals(testConfig, jsonObject.getJSONObject(testRailConfigKey), entityName, dataType, date);
					switch(dataType)
					{
					case AllTests:
						testConfig.putRunTimeProperty("tableName", "paymentgateway_tests_all");
						break;
					case FctTests:
						testConfig.putRunTimeProperty("tableName", "paymentgateway_tests_fct");
						break;
					case ProdTests:
						testConfig.putRunTimeProperty("tableName", "paymentgateway_prod_tests");
						break;
					}
					if (!testRailConfigKey.toLowerCase().startsWith("vertical"))
						testRailConfigKey = "Vertical - " + testRailConfigKey;
					addTestrailDataInDatabase(testConfig, date, testRailConfigKey, verticalLevelData);
					verticalLevelData.replaceAll((key, value) -> "0");
				} else {
					testConfig.logFail("Invalid TestRailConfig file format");
				}
			}
		}
	}

	private void fetchProjectWiseTestRailDataAndInsertIntoDb(Config testConfig, org.json.JSONArray testRailProjects, DataType dataType, LocalDate date) {
		for (int counter = 0; counter < testRailProjects.length(); counter++) {
			String projectName = testRailProjects.getJSONObject(counter).getString("suiteName");
			String suiteId = testRailProjects.getJSONObject(counter).getString("suiteId");
			String projectId = testRailProjects.getJSONObject(counter).getString("projectId");
			testConfig.logComment("ProjectName = " + projectName);

			HashMap<String, String> projectTestRailNumbers = fetchProjectSpecificData(testConfig, projectName, suiteId, projectId, dataType);
			addTestrailDataInDatabase(testConfig, date, projectName, projectTestRailNumbers);
			testConfig.logComment("==============================================");
			verticalLevelData.put("totalCases", String.valueOf(Integer.parseInt(verticalLevelData.get("totalCases")) + Integer.parseInt(projectTestRailNumbers.get("totalCases"))));
			verticalLevelData.put("totalAutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("totalAutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("totalAutomationCases"))));
			verticalLevelData.put("alreadyAutomated", String.valueOf(Integer.parseInt(verticalLevelData.get("alreadyAutomated")) + Integer.parseInt(projectTestRailNumbers.get("alreadyAutomated"))));
			verticalLevelData.put("p0Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("p0Cases")) + Integer.parseInt(projectTestRailNumbers.get("p0Cases"))));
			verticalLevelData.put("p1Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("p1Cases")) + Integer.parseInt(projectTestRailNumbers.get("p1Cases"))));
			verticalLevelData.put("p2Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("p2Cases")) + Integer.parseInt(projectTestRailNumbers.get("p2Cases"))));
			verticalLevelData.put("p0AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("p0AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("p0AutomationCases"))));
			verticalLevelData.put("p1AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("p1AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("p1AutomationCases"))));
			verticalLevelData.put("p2AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("p2AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("p2AutomationCases"))));
			verticalLevelData.put("p0AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("p0AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("p0AutomatedCases"))));
			verticalLevelData.put("p1AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("p1AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("p1AutomatedCases"))));
			verticalLevelData.put("p2AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("p2AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("p2AutomatedCases"))));
			int automationCoveragePerc = calculatePercentage(Integer.parseInt(verticalLevelData.get("alreadyAutomated")), Integer.parseInt(verticalLevelData.get("totalAutomationCases")));
			verticalLevelData.put("automationCoveragePerc", String.valueOf(automationCoveragePerc));
			int p0CoveragePerc = calculatePercentage(Integer.parseInt(verticalLevelData.get("p0AutomatedCases")), Integer.parseInt(verticalLevelData.get("p0AutomationCases")));
			verticalLevelData.put("p0CoveragePerc", String.valueOf(p0CoveragePerc));
			int p1CoveragePerc = calculatePercentage(Integer.parseInt(verticalLevelData.get("p1AutomatedCases")), Integer.parseInt(verticalLevelData.get("p1AutomationCases")));
			verticalLevelData.put("p1CoveragePerc", String.valueOf(p1CoveragePerc));
			int p2CoveragePerc = calculatePercentage(Integer.parseInt(verticalLevelData.get("p2AutomatedCases")), Integer.parseInt(verticalLevelData.get("p2AutomationCases")));
			verticalLevelData.put("p2CoveragePerc", String.valueOf(p2CoveragePerc));

			verticalLevelData.put("api_totalCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_totalCases")) + Integer.parseInt(projectTestRailNumbers.get("api_totalCases"))));
			verticalLevelData.put("api_totalAutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_totalAutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("api_totalAutomationCases"))));
			verticalLevelData.put("api_alreadyAutomated", String.valueOf(Integer.parseInt(verticalLevelData.get("api_alreadyAutomated")) + Integer.parseInt(projectTestRailNumbers.get("api_alreadyAutomated"))));
			verticalLevelData.put("api_p0Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p0Cases")) + Integer.parseInt(projectTestRailNumbers.get("api_p0Cases"))));
			verticalLevelData.put("api_p1Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p1Cases")) + Integer.parseInt(projectTestRailNumbers.get("api_p1Cases"))));
			verticalLevelData.put("api_p2Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p2Cases")) + Integer.parseInt(projectTestRailNumbers.get("api_p2Cases"))));
			verticalLevelData.put("api_p0AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p0AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("api_p0AutomationCases"))));
			verticalLevelData.put("api_p1AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p1AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("api_p1AutomationCases"))));
			verticalLevelData.put("api_p2AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p2AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("api_p2AutomationCases"))));
			verticalLevelData.put("api_p0AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p0AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("api_p0AutomatedCases"))));
			verticalLevelData.put("api_p1AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p1AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("api_p1AutomatedCases"))));
			verticalLevelData.put("api_p2AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("api_p2AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("api_p2AutomatedCases"))));

			verticalLevelData.put("web_totalCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_totalCases")) + Integer.parseInt(projectTestRailNumbers.get("web_totalCases"))));
			verticalLevelData.put("web_totalAutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_totalAutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("web_totalAutomationCases"))));
			verticalLevelData.put("web_alreadyAutomated", String.valueOf(Integer.parseInt(verticalLevelData.get("web_alreadyAutomated")) + Integer.parseInt(projectTestRailNumbers.get("web_alreadyAutomated"))));
			verticalLevelData.put("web_p0Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p0Cases")) + Integer.parseInt(projectTestRailNumbers.get("web_p0Cases"))));
			verticalLevelData.put("web_p1Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p1Cases")) + Integer.parseInt(projectTestRailNumbers.get("web_p1Cases"))));
			verticalLevelData.put("web_p2Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p2Cases")) + Integer.parseInt(projectTestRailNumbers.get("web_p2Cases"))));
			verticalLevelData.put("web_p0AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p0AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("web_p0AutomationCases"))));
			verticalLevelData.put("web_p1AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p1AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("web_p1AutomationCases"))));
			verticalLevelData.put("web_p2AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p2AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("web_p2AutomationCases"))));
			verticalLevelData.put("web_p0AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p0AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("web_p0AutomatedCases"))));
			verticalLevelData.put("web_p1AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p1AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("web_p1AutomatedCases"))));
			verticalLevelData.put("web_p2AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("web_p2AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("web_p2AutomatedCases"))));

			verticalLevelData.put("android_totalCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_totalCases")) + Integer.parseInt(projectTestRailNumbers.get("android_totalCases"))));
			verticalLevelData.put("android_totalAutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_totalAutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("android_totalAutomationCases"))));
			verticalLevelData.put("android_alreadyAutomated", String.valueOf(Integer.parseInt(verticalLevelData.get("android_alreadyAutomated")) + Integer.parseInt(projectTestRailNumbers.get("android_alreadyAutomated"))));
			verticalLevelData.put("android_p0Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p0Cases")) + Integer.parseInt(projectTestRailNumbers.get("android_p0Cases"))));
			verticalLevelData.put("android_p1Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p1Cases")) + Integer.parseInt(projectTestRailNumbers.get("android_p1Cases"))));
			verticalLevelData.put("android_p2Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p2Cases")) + Integer.parseInt(projectTestRailNumbers.get("android_p2Cases"))));
			verticalLevelData.put("android_p0AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p0AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("android_p0AutomationCases"))));
			verticalLevelData.put("android_p1AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p1AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("android_p1AutomationCases"))));
			verticalLevelData.put("android_p2AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p2AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("android_p2AutomationCases"))));
			verticalLevelData.put("android_p0AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p0AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("android_p0AutomatedCases"))));
			verticalLevelData.put("android_p1AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p1AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("android_p1AutomatedCases"))));
			verticalLevelData.put("android_p2AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("android_p2AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("android_p2AutomatedCases"))));

			verticalLevelData.put("ios_totalCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_totalCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_totalCases"))));
			verticalLevelData.put("ios_totalAutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_totalAutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_totalAutomationCases"))));
			verticalLevelData.put("ios_alreadyAutomated", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_alreadyAutomated")) + Integer.parseInt(projectTestRailNumbers.get("ios_alreadyAutomated"))));
			verticalLevelData.put("ios_p0Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p0Cases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p0Cases"))));
			verticalLevelData.put("ios_p1Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p1Cases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p1Cases"))));
			verticalLevelData.put("ios_p2Cases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p2Cases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p2Cases"))));
			verticalLevelData.put("ios_p0AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p0AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p0AutomationCases"))));
			verticalLevelData.put("ios_p1AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p1AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p1AutomationCases"))));
			verticalLevelData.put("ios_p2AutomationCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p2AutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p2AutomationCases"))));
			verticalLevelData.put("ios_p0AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p0AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p0AutomatedCases"))));
			verticalLevelData.put("ios_p1AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p1AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p1AutomatedCases"))));
			verticalLevelData.put("ios_p2AutomatedCases", String.valueOf(Integer.parseInt(verticalLevelData.get("ios_p2AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("ios_p2AutomatedCases"))));
		}
	}

	@SuppressWarnings("unchecked")
	private HashMap<String, String> fetchProjectSpecificData(Config testConfig, String projectName, String suiteId, String projectId, DataType dataType) {
		HashMap<String, String> projectTestRailNumbers = new HashMap<>();
		int totalCases = 0;
		int totalAutomationCases = 0;
		int alreadyAutomated = 0;
		int p0Cases = 0;
		int p1Cases = 0;
		int p2Cases = 0;
		int p0AutomationCases = 0;
		int p1AutomationCases = 0;
		int p2AutomationCases = 0;
		int p0AutomatedCases = 0;
		int p1AutomatedCases = 0;
		int p2AutomatedCases = 0;
		int automationCoveragePerc = 0;
		int p0CoveragePerc = 0;
		int p1CoveragePerc = 0;
		int p2CoveragePerc = 0;

		int api_p0Cases = 0;
		int api_p1Cases = 0;
		int api_p2Cases = 0;
		int api_totalCases = 0;
		int api_totalAutomationCases = 0;
		int api_alreadyAutomated = 0;
		int api_p0AutomationCases = 0;
		int api_p0AutomatedCases = 0;
		int api_p1AutomationCases = 0;
		int api_p1AutomatedCases = 0;
		int api_p2AutomationCases = 0;
		int api_p2AutomatedCases = 0;

		int web_p0Cases = 0;
		int web_p1Cases = 0;
		int web_p2Cases = 0;
		int web_totalCases = 0;
		int web_totalAutomationCases = 0;
		int web_alreadyAutomated = 0;
		int web_p0AutomationCases = 0;
		int web_p0AutomatedCases = 0;
		int web_p1AutomationCases = 0;
		int web_p1AutomatedCases = 0;
		int web_p2AutomationCases = 0;
		int web_p2AutomatedCases = 0;

		int android_p0Cases = 0;
		int android_p1Cases = 0;
		int android_p2Cases = 0;
		int android_totalCases = 0;
		int android_totalAutomationCases = 0;
		int android_alreadyAutomated = 0;
		int android_p0AutomationCases = 0;
		int android_p0AutomatedCases = 0;
		int android_p1AutomationCases = 0;
		int android_p1AutomatedCases = 0;
		int android_p2AutomationCases = 0;
		int android_p2AutomatedCases = 0;

		int ios_p0Cases = 0;
		int ios_p1Cases = 0;
		int ios_p2Cases = 0;
		int ios_totalCases = 0;
		int ios_totalAutomationCases = 0;
		int ios_alreadyAutomated = 0;
		int ios_p0AutomationCases = 0;
		int ios_p0AutomatedCases = 0;
		int ios_p1AutomationCases = 0;
		int ios_p1AutomatedCases = 0;
		int ios_p2AutomationCases = 0;
		int ios_p2AutomatedCases = 0;
		Set<Object> incorrectDataTestRail = new HashSet<>();
		Set<Long> processedTestrailIds = new HashSet<>(); // Track processed testrailIds
		JSONArray cases = null;
		JSONObject response = null;
		JSONObject links = null;
		String nextLink = null;
		int offset = 0;
		int retryCount = 0;
		boolean hideNonNativeIosTests = false;
		boolean insertTestcasesIntoDB = false;
		do {
			for (retryCount = 0; retryCount <= 4; retryCount++) {
				try {
					switch(dataType)
					{
					case AllTests:
						response = (JSONObject) testRailClient.sendGet("get_cases/" + projectId + "&suite_id=" + suiteId + "&offset=" + offset);
						insertTestcasesIntoDB = true;
						break;
					case FctTests:
						response = (JSONObject) testRailClient.sendGet("get_cases/" + projectId + "&suite_id=" + suiteId +"&type_id=11,13"+ "&offset=" + offset);
						hideNonNativeIosTests = true;
						break;
					case ProdTests:
						response = (JSONObject) testRailClient.sendGet("get_cases/" + projectId + "&suite_id=" + suiteId +"&type_id=11"+ "&offset=" + offset);
						break;
					}
					
					cases = (JSONArray) response.get("cases");
					links = (JSONObject) response.get("_links");
					nextLink = (String) links.get("next");
					if (nextLink != null)
						offset = Integer.parseInt(nextLink.substring(nextLink.indexOf("&offset=") + "&offset=".length(), nextLink.indexOf("&limit")));
					break;
				} catch (Exception e) {
					if (retryCount == 1)
						testConfig.logExceptionAndFail("Ending execution...", e);
					else {
						testConfig.logException("Retrying after exception...", e);
						CommonUtilities.waitForSeconds(testConfig, 2);
					}
				}
			}

			testConfig.logComment("Number of cases: " + cases.size());
			for (int i = 0, size = cases.size(); i < size; i++) 
			{
				int nonNativeTestsToRemove = 0;
				JSONObject objectInArray = (JSONObject) cases.get(i);				
				testConfig.logCommentForDebugging("TC ID:" + objectInArray.get("id"));
				Long createdOn = (Long) objectInArray.get("created_on");
				//Long updatedOn = (Long) objectInArray.get("updated_on");
				Long testrailId = (Long) objectInArray.get("id");
				String title = (String) objectInArray.get("title");
				Long priority = (Long) objectInArray.get("priority_id");
				Long type = (Long) objectInArray.get("type_id");
				Long executionMode = (Long) objectInArray.get("custom_execution_mode");
				List<Long> platform = (List<Long>) objectInArray.get("custom_platform");
				Boolean isMobileNative =  (Boolean) objectInArray.get("custom_native_implementation");
				List<Long> apiAutomationStatus = (List<Long>) objectInArray.get("custom_api_automation_status_m");
				List<Long> webAutomationStatus = (List<Long>) objectInArray.get("custom_web_automation_status_m");
				List<Long> androidAutomationStatus = (List<Long>) objectInArray.get("custom_android_automation_status_m");
				List<Long> iosAutomationStatus = (List<Long>) objectInArray.get("custom_ios_automation_status_m");
				String references = (String) objectInArray.get("refs");
				Long updatedBy = (Long) objectInArray.get("updated_by");
				Long isDeleted = (Long) objectInArray.get("is_deleted");
				
				if(insertTestcasesIntoDB) {
					processedTestrailIds.add(testrailId); // Track this testrailId as processed
					addTestcasesDataInDatabase(testConfig, projectName, createdOn, testrailId,suiteId, title,priority,type,executionMode,platform,isMobileNative,apiAutomationStatus,webAutomationStatus,androidAutomationStatus,iosAutomationStatus,references,updatedBy,isDeleted);
				}
				
				
				try {
					// Calculating Platform specific test case count
					for (long j : platform) {
						switch ((int) j) {

						// Android
						case 3:
							android_totalCases++;

							// checking whether the test case is Automation and Android Automation Status is
							// not updated
							if (executionMode == 2L && androidAutomationStatus.size() == 0)
								incorrectDataTestRail.add(objectInArray.get("id"));

							// Calculating total Automation Cases for Android
							if (executionMode == 2L)
								android_totalAutomationCases++;
							break;
						// iOS
						case 4:
							if(hideNonNativeIosTests)
							{
								// Only count in IOS if its mobile native test
								if(isMobileNative != null && isMobileNative)
								{
									ios_totalCases++;

									// checking whether the test case is Automation and IOS Automation Status is not updated
									if (executionMode == 2L && iosAutomationStatus.size() == 0)
										incorrectDataTestRail.add(objectInArray.get("id"));

									// Calculating total Automation Cases for iOS
									if (executionMode == 2L)
										ios_totalAutomationCases++;
								}
								else
								{
									nonNativeTestsToRemove = 1;
								}
							}
							else
							{
								ios_totalCases++;

								// checking whether the test case is Automation and IOS Automation Status is not updated
								if (executionMode == 2L && iosAutomationStatus.size() == 0)
									incorrectDataTestRail.add(objectInArray.get("id"));

								// Calculating total Automation Cases for iOS
								if (executionMode == 2L)
									ios_totalAutomationCases++;
							}
							
							break;
						// Web
						case 2:
							web_totalCases++;

							// checking whether the test case is Automation and Web Automation Status is not
							// updated
							if (executionMode == 2L && webAutomationStatus.size() == 0)
								incorrectDataTestRail.add(objectInArray.get("id"));

							// Calculating total Automation Cases for Web
							if (executionMode == 2L)
								web_totalAutomationCases++;
							break;
						// Api
						case 1:
							api_totalCases++;

							// checking whether the test case is Automation and Api Automation Status is not
							// updated
							if (executionMode == 2L && apiAutomationStatus.size() == 0)
								incorrectDataTestRail.add(objectInArray.get("id"));

							// Calculating total Automation Cases for Api
							if (executionMode == 2L)
								api_totalAutomationCases++;
							break;
						default:
							testConfig.logFail(projectName + "==>Platform" + platform);
							break;
						}
					}

					totalCases = android_totalCases + ios_totalCases + web_totalCases + api_totalCases;
					// Checking whether automation status of a platform does not exist without filling respective Platform field
					if ((androidAutomationStatus.size() > 0 && !platform.contains(3L)) || (iosAutomationStatus.size() > 0 && !platform.contains(4L)) || (webAutomationStatus.size() > 0 && !platform.contains(2L)) || (apiAutomationStatus.size() > 0 && !platform.contains(1L)))
						incorrectDataTestRail.add(objectInArray.get("id"));

					// Calculating automation test case count based on Priority
					if (executionMode == 2L) {
						totalAutomationCases = totalAutomationCases + 1 * platform.size() - nonNativeTestsToRemove;
					}
					
					if(StringUtils.isEmpty(references))
						incorrectDataTestRail.add(objectInArray.get("id"));

					
					compareTrueLocally(testConfig, "Priority of Automated Test Case " + objectInArray.get("id"), priority == 4L || priority == 3L || priority == 2L || priority == 1L);
					switch (priority.intValue()) {
					// NA
					case 5:
						incorrectDataTestRail.add(objectInArray.get("id"));
						if (androidAutomationStatus.contains(2L)) {
							android_alreadyAutomated++;
						}

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
								if (iosAutomationStatus.contains(2L)) {
									ios_alreadyAutomated++;
								}
						}
						else
						{
							if (iosAutomationStatus.contains(2L)) {
								ios_alreadyAutomated++;
							}
						}
						

						if (apiAutomationStatus.contains(2L)) {
							api_alreadyAutomated++;
						}

						if (webAutomationStatus.contains(2L)) {
							web_alreadyAutomated++;
						}
						break;
					// P0
					case 4:
						if (platform.contains(3L))
							android_p0Cases++;

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
							{
								nonNativeTestsToRemove = 0;
								if (platform.contains(4L))
									ios_p0Cases++;
							}
							else
							{
								nonNativeTestsToRemove = 1;
							}
						}
						else
						{
							if (platform.contains(4L))
								ios_p0Cases++;
						}
								
						if (platform.contains(1L))
							api_p0Cases++;

						if (platform.contains(2L))
							web_p0Cases++;

						if (androidAutomationStatus.contains(1L) || androidAutomationStatus.contains(2L)) {
							p0AutomationCases++;
							android_p0AutomationCases++;
							if (androidAutomationStatus.contains(2L)) {
								p0AutomatedCases++;
								android_p0AutomatedCases++;
								android_alreadyAutomated++;
							}
						}

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
								if (iosAutomationStatus.contains(1L) || iosAutomationStatus.contains(2L)) {
									p0AutomationCases++;
									ios_p0AutomationCases++;
									if (iosAutomationStatus.contains(2L)) {
										p0AutomatedCases++;
										ios_p0AutomatedCases++;
										ios_alreadyAutomated++;
									}
								}
						}
						else
						{
							if (iosAutomationStatus.contains(1L) || iosAutomationStatus.contains(2L)) {
								p0AutomationCases++;
								ios_p0AutomationCases++;
								if (iosAutomationStatus.contains(2L)) {
									p0AutomatedCases++;
									ios_p0AutomatedCases++;
									ios_alreadyAutomated++;
								}
							}
						}

						if (apiAutomationStatus.contains(1L) || apiAutomationStatus.contains(2L)) {
							p0AutomationCases++;
							api_p0AutomationCases++;
							if (apiAutomationStatus.contains(2L)) {
								p0AutomatedCases++;
								api_p0AutomatedCases++;
								api_alreadyAutomated++;
							}
						}

						if (webAutomationStatus.contains(1L) || webAutomationStatus.contains(2L)) {
							p0AutomationCases++;
							web_p0AutomationCases++;
							if (webAutomationStatus.contains(2L)) {
								p0AutomatedCases++;
								web_p0AutomatedCases++;
								web_alreadyAutomated++;
							}
						}
						
						// Calculate p0Cases as sum of individual platform cases, accounting for nonNativeTestsToRemove
						p0Cases = android_p0Cases + ios_p0Cases + api_p0Cases + web_p0Cases;
						break;
					// P1
					case 3:
						if (platform.contains(3L))
							android_p1Cases++;

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
							{
								nonNativeTestsToRemove = 0;
								if (platform.contains(4L))
									ios_p1Cases++;
							}
							else
							{
								nonNativeTestsToRemove = 1;
							}
						}
						else
						{
							if (platform.contains(4L))
								ios_p1Cases++;
						}

						if (platform.contains(1L))
							api_p1Cases++;

						if (platform.contains(2L))
							web_p1Cases++;

						if (androidAutomationStatus.contains(1L) || androidAutomationStatus.contains(2L)) {
							p1AutomationCases++;
							android_p1AutomationCases++;
							if (androidAutomationStatus.contains(2L)) {
								p1AutomatedCases++;
								android_p1AutomatedCases++;
								android_alreadyAutomated++;
							}
						}

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
								if (iosAutomationStatus.contains(1L) || iosAutomationStatus.contains(2L)) {
									p1AutomationCases++;
									ios_p1AutomationCases++;
									if (iosAutomationStatus.contains(2L)) {
										p1AutomatedCases++;
										ios_p1AutomatedCases++;
										ios_alreadyAutomated++;
									}
								}
						}
						else
						{
							if (iosAutomationStatus.contains(1L) || iosAutomationStatus.contains(2L)) {
								p1AutomationCases++;
								ios_p1AutomationCases++;
								if (iosAutomationStatus.contains(2L)) {
									p1AutomatedCases++;
									ios_p1AutomatedCases++;
									ios_alreadyAutomated++;
								}
							}
						}
						

						if (apiAutomationStatus.contains(1L) || apiAutomationStatus.contains(2L)) {
							p1AutomationCases++;
							api_p1AutomationCases++;
							if (apiAutomationStatus.contains(2L)) {
								p1AutomatedCases++;
								api_p1AutomatedCases++;
								api_alreadyAutomated++;
							}
						}

						if (webAutomationStatus.contains(1L) || webAutomationStatus.contains(2L)) {
							p1AutomationCases++;
							web_p1AutomationCases++;
							if (webAutomationStatus.contains(2L)) {
								p1AutomatedCases++;
								web_p1AutomatedCases++;
								web_alreadyAutomated++;
							}
						}
						// Calculate p1Cases as sum of individual platform cases, accounting for nonNativeTestsToRemove
						p1Cases = android_p1Cases + ios_p1Cases + api_p1Cases + web_p1Cases;
						break;

					// P2
					case 2:
						if (platform.contains(3L))
							android_p2Cases++;

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
							{
								nonNativeTestsToRemove = 0;
								if (platform.contains(4L))
									ios_p2Cases++;
							}
							else
							{
								nonNativeTestsToRemove = 1;
							}
						}
						else
						{
							if (platform.contains(4L))
								ios_p2Cases++;
						}

						if (platform.contains(1L))
							api_p2Cases++;

						if (platform.contains(2L))
							web_p2Cases++;

						if (androidAutomationStatus.contains(1L) || androidAutomationStatus.contains(2L)) {
							p2AutomationCases++;
							android_p2AutomationCases++;
							if (androidAutomationStatus.contains(2L)) {
								p2AutomatedCases++;
								android_p2AutomatedCases++;
								android_alreadyAutomated++;
							}
						}

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
								if (iosAutomationStatus.contains(1L) || iosAutomationStatus.contains(2L)) {
									p2AutomationCases++;
									ios_p2AutomationCases++;
									if (iosAutomationStatus.contains(2L)) {
										p2AutomatedCases++;
										ios_p2AutomatedCases++;
										ios_alreadyAutomated++;
									}
								}
						}
						else
						{
							if (iosAutomationStatus.contains(1L) || iosAutomationStatus.contains(2L)) {
								p2AutomationCases++;
								ios_p2AutomationCases++;
								if (iosAutomationStatus.contains(2L)) {
									p2AutomatedCases++;
									ios_p2AutomatedCases++;
									ios_alreadyAutomated++;
								}
							}
						}
						

						if (apiAutomationStatus.contains(1L) || apiAutomationStatus.contains(2L)) {
							p2AutomationCases++;
							api_p2AutomationCases++;
							if (apiAutomationStatus.contains(2L)) {
								p2AutomatedCases++;
								api_p2AutomatedCases++;
								api_alreadyAutomated++;
							}
						}

						if (webAutomationStatus.contains(1L) || webAutomationStatus.contains(2L)) {
							p2AutomationCases++;
							web_p2AutomationCases++;
							if (webAutomationStatus.contains(2L)) {
								p2AutomatedCases++;
								web_p2AutomatedCases++;
								web_alreadyAutomated++;
							}
						}
						// Calculate p2Cases as sum of individual platform cases, accounting for nonNativeTestsToRemove
						p2Cases = android_p2Cases + ios_p2Cases + api_p2Cases + web_p2Cases;
						break;
					// P3
					case 1:
						if (androidAutomationStatus.contains(2L)) {
							android_alreadyAutomated++;
						}

						if(hideNonNativeIosTests)
						{
							// Only count in IOS if its mobile native test
							if(isMobileNative != null && isMobileNative)
							{
								if (iosAutomationStatus.contains(2L)) {
									ios_alreadyAutomated++;
								}
							}
						}
						else
						{
							if (iosAutomationStatus.contains(2L)) {
								ios_alreadyAutomated++;
							}
						}

						if (apiAutomationStatus.contains(2L)) {
							api_alreadyAutomated++;
						}

						if (webAutomationStatus.contains(2L)) {
							web_alreadyAutomated++;
						}
						break;
					default:
						testConfig.logFail(projectName + "==>priority=" + priority.intValue());
					}
					compareTrueLocally(testConfig, "Priority of Automated Test Case " + objectInArray.get("id"), priority == 4L || priority == 3L || priority == 2L || priority == 1L);

				} catch (NullPointerException e) {
					incorrectDataTestRail.add(objectInArray.get("id"));
				}
			}
		} while (nextLink != null);

		// Mark testcases as deleted if they are no longer in TestRail
		if (insertTestcasesIntoDB && processedTestrailIds.size() > 0) {
			markDeletedTestcasesInDatabase(testConfig, projectName, suiteId, processedTestrailIds);
		}

		if (incorrectDataTestRail.size() > 0) {
			testConfig.logFail("[" + projectName + "] Information missing for Platform/ Execution Mode/ Automation Status/ References fields against these Testrail ids: ");
			for (Object o : incorrectDataTestRail)
				testConfig.logFail(o.toString());
		}

		testConfig.logComment("==========Count of Total Cases (Manual & Automation)==========");
		testConfig.logComment("Total Android cases = " + android_totalCases);
		testConfig.logComment("Total IOS cases = " + ios_totalCases);
		testConfig.logComment("Total Web cases = " + web_totalCases);
		testConfig.logComment("Total Api cases = " + api_totalCases);
		testConfig.logComment("Total Manual & Automation cases = " + totalCases);

		testConfig.logComment("================Count of Only Automation Cases================");
		testConfig.logComment("Only Automation cases for Android = " + android_totalAutomationCases);
		testConfig.logComment("Only Automation cases for iOS = " + ios_totalAutomationCases);
		testConfig.logComment("Only Automation cases for Web = " + web_totalAutomationCases);
		testConfig.logComment("Only Automation cases for Api = " + api_totalAutomationCases);
		testConfig.logComment("Total Automation cases = " + totalAutomationCases);

		testConfig.logComment("===============Count of Already Automated Cases===============");
		testConfig.logComment("Already Automated Cases for Android = " + android_alreadyAutomated);
		testConfig.logComment("Already Automated Cases for IOS = " + ios_alreadyAutomated);
		testConfig.logComment("Already Automated Cases for Api = " + api_alreadyAutomated);
		testConfig.logComment("Already Automated Cases for Web = " + web_alreadyAutomated);
		alreadyAutomated = android_alreadyAutomated + ios_alreadyAutomated + api_alreadyAutomated + web_alreadyAutomated;
		testConfig.logComment("Total Already Automated cases = " + alreadyAutomated);
		testConfig.logComment("==============================================================");
		automationCoveragePerc = calculatePercentage(alreadyAutomated, totalAutomationCases);
		testConfig.logComment("Total Automation Coverage Percentage = " + automationCoveragePerc + "%");
		testConfig.logComment("Total P0 cases = " + p0Cases);
		testConfig.logComment("P0 Automation cases = " + p0AutomationCases);
		testConfig.logComment("P0 Automated cases = " + p0AutomatedCases);
		p0CoveragePerc = calculatePercentage(p0AutomatedCases, p0AutomationCases);
		testConfig.logComment("Automation Coverage For P0 Cases = " + p0CoveragePerc);
		testConfig.logComment("Total P1 cases = " + p1Cases);
		testConfig.logComment("P1 Automation cases = " + p1AutomationCases);
		testConfig.logComment("P1 Automated cases = " + p1AutomatedCases);
		p1CoveragePerc = calculatePercentage(p1AutomatedCases, p1AutomationCases);
		testConfig.logComment("Automation Coverage For P1 Cases = " + p1CoveragePerc);
		testConfig.logComment("Total P2 cases = " + p2Cases);
		testConfig.logComment("P2 Automation cases = " + p2AutomationCases);
		testConfig.logComment("P2 Automated cases = " + p2AutomatedCases);
		p2CoveragePerc = calculatePercentage(p2AutomatedCases, p2AutomationCases);
		testConfig.logComment("Automation Coverage For P2 Cases = " + p2CoveragePerc);

		projectTestRailNumbers.put("totalCases", String.valueOf(totalCases));
		projectTestRailNumbers.put("totalAutomationCases", String.valueOf(totalAutomationCases));
		projectTestRailNumbers.put("alreadyAutomated", String.valueOf(alreadyAutomated));
		projectTestRailNumbers.put("p0Cases", String.valueOf(p0Cases));
		projectTestRailNumbers.put("p1Cases", String.valueOf(p1Cases));
		projectTestRailNumbers.put("p2Cases", String.valueOf(p2Cases));
		projectTestRailNumbers.put("p0AutomationCases", String.valueOf(p0AutomationCases));
		projectTestRailNumbers.put("p1AutomationCases", String.valueOf(p1AutomationCases));
		projectTestRailNumbers.put("p2AutomationCases", String.valueOf(p2AutomationCases));
		projectTestRailNumbers.put("p0AutomatedCases", String.valueOf(p0AutomatedCases));
		projectTestRailNumbers.put("p1AutomatedCases", String.valueOf(p1AutomatedCases));
		projectTestRailNumbers.put("p2AutomatedCases", String.valueOf(p2AutomatedCases));
		projectTestRailNumbers.put("automationCoveragePerc", String.valueOf(automationCoveragePerc));
		projectTestRailNumbers.put("p0CoveragePerc", String.valueOf(p0CoveragePerc));
		projectTestRailNumbers.put("p1CoveragePerc", String.valueOf(p1CoveragePerc));
		projectTestRailNumbers.put("p2CoveragePerc", String.valueOf(p2CoveragePerc));

		projectTestRailNumbers.put("api_totalCases", String.valueOf(api_totalCases));
		projectTestRailNumbers.put("api_totalAutomationCases", String.valueOf(api_totalAutomationCases));
		projectTestRailNumbers.put("api_alreadyAutomated", String.valueOf(api_alreadyAutomated));
		projectTestRailNumbers.put("api_p0Cases", String.valueOf(api_p0Cases));
		projectTestRailNumbers.put("api_p1Cases", String.valueOf(api_p1Cases));
		projectTestRailNumbers.put("api_p2Cases", String.valueOf(api_p2Cases));
		projectTestRailNumbers.put("api_p0AutomationCases", String.valueOf(api_p0AutomationCases));
		projectTestRailNumbers.put("api_p0AutomatedCases", String.valueOf(api_p0AutomatedCases));
		projectTestRailNumbers.put("api_p1AutomationCases", String.valueOf(api_p1AutomationCases));
		projectTestRailNumbers.put("api_p1AutomatedCases", String.valueOf(api_p1AutomatedCases));
		projectTestRailNumbers.put("api_p2AutomationCases", String.valueOf(api_p2AutomationCases));
		projectTestRailNumbers.put("api_p2AutomatedCases", String.valueOf(api_p2AutomatedCases));

		projectTestRailNumbers.put("web_totalCases", String.valueOf(web_totalCases));
		projectTestRailNumbers.put("web_totalAutomationCases", String.valueOf(web_totalAutomationCases));
		projectTestRailNumbers.put("web_alreadyAutomated", String.valueOf(web_alreadyAutomated));
		projectTestRailNumbers.put("web_p0Cases", String.valueOf(web_p0Cases));
		projectTestRailNumbers.put("web_p1Cases", String.valueOf(web_p1Cases));
		projectTestRailNumbers.put("web_p2Cases", String.valueOf(web_p2Cases));
		projectTestRailNumbers.put("web_p0AutomationCases", String.valueOf(web_p0AutomationCases));
		projectTestRailNumbers.put("web_p0AutomatedCases", String.valueOf(web_p0AutomatedCases));
		projectTestRailNumbers.put("web_p1AutomationCases", String.valueOf(web_p1AutomationCases));
		projectTestRailNumbers.put("web_p1AutomatedCases", String.valueOf(web_p1AutomatedCases));
		projectTestRailNumbers.put("web_p2AutomationCases", String.valueOf(web_p2AutomationCases));
		projectTestRailNumbers.put("web_p2AutomatedCases", String.valueOf(web_p2AutomatedCases));

		projectTestRailNumbers.put("android_totalCases", String.valueOf(android_totalCases));
		projectTestRailNumbers.put("android_totalAutomationCases", String.valueOf(android_totalAutomationCases));
		projectTestRailNumbers.put("android_alreadyAutomated", String.valueOf(android_alreadyAutomated));
		projectTestRailNumbers.put("android_p0Cases", String.valueOf(android_p0Cases));
		projectTestRailNumbers.put("android_p1Cases", String.valueOf(android_p1Cases));
		projectTestRailNumbers.put("android_p2Cases", String.valueOf(android_p2Cases));
		projectTestRailNumbers.put("android_p0AutomationCases", String.valueOf(android_p0AutomationCases));
		projectTestRailNumbers.put("android_p0AutomatedCases", String.valueOf(android_p0AutomatedCases));
		projectTestRailNumbers.put("android_p1AutomationCases", String.valueOf(android_p1AutomationCases));
		projectTestRailNumbers.put("android_p1AutomatedCases", String.valueOf(android_p1AutomatedCases));
		projectTestRailNumbers.put("android_p2AutomationCases", String.valueOf(android_p2AutomationCases));
		projectTestRailNumbers.put("android_p2AutomatedCases", String.valueOf(android_p2AutomatedCases));

		projectTestRailNumbers.put("ios_totalCases", String.valueOf(ios_totalCases));
		projectTestRailNumbers.put("ios_totalAutomationCases", String.valueOf(ios_totalAutomationCases));
		projectTestRailNumbers.put("ios_alreadyAutomated", String.valueOf(ios_alreadyAutomated));
		projectTestRailNumbers.put("ios_p0Cases", String.valueOf(ios_p0Cases));
		projectTestRailNumbers.put("ios_p1Cases", String.valueOf(ios_p1Cases));
		projectTestRailNumbers.put("ios_p2Cases", String.valueOf(ios_p2Cases));
		projectTestRailNumbers.put("ios_p0AutomationCases", String.valueOf(ios_p0AutomationCases));
		projectTestRailNumbers.put("ios_p0AutomatedCases", String.valueOf(ios_p0AutomatedCases));
		projectTestRailNumbers.put("ios_p1AutomationCases", String.valueOf(ios_p1AutomationCases));
		projectTestRailNumbers.put("ios_p1AutomatedCases", String.valueOf(ios_p1AutomatedCases));
		projectTestRailNumbers.put("ios_p2AutomationCases", String.valueOf(ios_p2AutomationCases));
		projectTestRailNumbers.put("ios_p2AutomatedCases", String.valueOf(ios_p2AutomatedCases));

		return projectTestRailNumbers;
	}

	private Integer calculatePercentage(int value, int total) {
		int percentage = 0;
		if (total > 0 && value > 0)
			percentage = (value * 100) / total;

		return percentage;
	}

	private void addTestrailDataInDatabase(Config testConfig, LocalDate date, String projectName, HashMap<String, String> testRailNumbers) {
		String selectQuery1 = "SELECT id,alreadyAutomated,positiveDelta,negativeDelta from {$tableName} where projectName = '{$projectName}' AND DATE(createdAt)= DATE('{$createdAt}') ORDER BY createdAt DESC LIMIT 1;";
		String selectQuery2 = "SELECT id,alreadyAutomated,(0) as positiveDelta,(0) as negativeDelta from {$tableName} where projectName = '{$projectName}' ORDER BY id DESC LIMIT 1;";
		String updateQuery = "UPDATE {$tableName} SET createdAt='{$createdAt}',totalCases={$totalCases},totalAutomationCases={$totalAutomationCases},alreadyAutomated={$alreadyAutomated},automationCoveragePerc={$automationCoveragePerc},p0Cases={$p0Cases},p0AutomationCases={$p0AutomationCases},p0AutomatedCases={$p0AutomatedCases},p0CoveragePerc={$p0CoveragePerc},p1Cases={$p1Cases},p1AutomationCases={$p1AutomationCases},p1AutomatedCases={$p1AutomatedCases},p1CoveragePerc={$p1CoveragePerc},p2Cases={$p2Cases},p2AutomationCases={$p2AutomationCases},p2AutomatedCases={$p2AutomatedCases},p2CoveragePerc={$p2CoveragePerc},positiveDelta={$positiveDelta},negativeDelta={$negativeDelta},api_totalCases={$api_totalCases},api_totalAutomationCases={$api_totalAutomationCases},api_alreadyAutomated={$api_alreadyAutomated},api_p0Cases={$api_p0Cases},api_p0AutomationCases={$api_p0AutomationCases},api_p0AutomatedCases={$api_p0AutomatedCases},api_p1Cases={$api_p1Cases},api_p1AutomationCases={$api_p1AutomationCases},api_p1AutomatedCases={$api_p1AutomatedCases},api_p2Cases={$api_p2Cases},api_p2AutomationCases={$api_p2AutomationCases},api_p2AutomatedCases={$api_p2AutomatedCases},web_totalCases={$web_totalCases},web_totalAutomationCases={$web_totalAutomationCases},web_alreadyAutomated={$web_alreadyAutomated},web_p0Cases={$web_p0Cases},web_p0AutomationCases={$web_p0AutomationCases},web_p0AutomatedCases={$web_p0AutomatedCases},web_p1Cases={$web_p1Cases},web_p1AutomationCases={$web_p1AutomationCases},web_p1AutomatedCases={$web_p1AutomatedCases},web_p2Cases={$web_p2Cases},web_p2AutomationCases={$web_p2AutomationCases},web_p2AutomatedCases={$web_p2AutomatedCases},android_totalCases={$android_totalCases},android_totalAutomationCases={$android_totalAutomationCases},android_alreadyAutomated={$android_alreadyAutomated},android_p0Cases={$android_p0Cases},android_p0AutomationCases={$android_p0AutomationCases},android_p0AutomatedCases={$android_p0AutomatedCases},android_p1Cases={$android_p1Cases},android_p1AutomationCases={$android_p1AutomationCases},android_p1AutomatedCases={$android_p1AutomatedCases},android_p2Cases={$android_p2Cases},android_p2AutomationCases={$android_p2AutomationCases},android_p2AutomatedCases={$android_p2AutomatedCases},ios_totalCases={$ios_totalCases},ios_totalAutomationCases={$ios_totalAutomationCases},ios_alreadyAutomated={$ios_alreadyAutomated},ios_p0Cases={$ios_p0Cases},ios_p0AutomationCases={$ios_p0AutomationCases},ios_p0AutomatedCases={$ios_p0AutomatedCases},ios_p1Cases={$ios_p1Cases},ios_p1AutomationCases={$ios_p1AutomationCases},ios_p1AutomatedCases={$ios_p1AutomatedCases},ios_p2Cases={$ios_p2Cases},ios_p2AutomationCases={$ios_p2AutomationCases},ios_p2AutomatedCases={$ios_p2AutomatedCases} WHERE id={$id};";
		String insertQuery = "INSERT into {$tableName} (createdAt,projectName,totalCases,totalAutomationCases,alreadyAutomated,automationCoveragePerc,p0Cases,p0AutomationCases,p0AutomatedCases,p0CoveragePerc,p1Cases,p1AutomationCases,p1AutomatedCases,p1CoveragePerc,p2Cases,p2AutomationCases,p2AutomatedCases,p2CoveragePerc,positiveDelta,negativeDelta,api_totalCases,api_totalAutomationCases,api_alreadyAutomated,api_p0Cases,api_p0AutomationCases,api_p0AutomatedCases,api_p1Cases,api_p1AutomationCases,api_p1AutomatedCases,api_p2Cases,api_p2AutomationCases,api_p2AutomatedCases,web_totalCases,web_totalAutomationCases,web_alreadyAutomated,web_p0Cases,web_p0AutomationCases,web_p0AutomatedCases,web_p1Cases,web_p1AutomationCases,web_p1AutomatedCases,web_p2Cases,web_p2AutomationCases,web_p2AutomatedCases,android_totalCases,android_totalAutomationCases,android_alreadyAutomated,android_p0Cases,android_p0AutomationCases,android_p0AutomatedCases,android_p1Cases,android_p1AutomationCases,android_p1AutomatedCases,android_p2Cases,android_p2AutomationCases,android_p2AutomatedCases,ios_totalCases,ios_totalAutomationCases,ios_alreadyAutomated,ios_p0Cases,ios_p0AutomationCases,ios_p0AutomatedCases,ios_p1Cases,ios_p1AutomationCases,ios_p1AutomatedCases,ios_p2Cases,ios_p2AutomationCases,ios_p2AutomatedCases) values ('{$createdAt}','{$projectName}',{$totalCases},{$totalAutomationCases},{$alreadyAutomated},{$automationCoveragePerc},{$p0Cases},{$p0AutomationCases},{$p0AutomatedCases},{$p0CoveragePerc},{$p1Cases},{$p1AutomationCases},{$p1AutomatedCases},{$p1CoveragePerc},{$p2Cases},{$p2AutomationCases},{$p2AutomatedCases},{$p2CoveragePerc},{$positiveDelta},{$negativeDelta},{$api_totalCases},{$api_totalAutomationCases},{$api_alreadyAutomated},{$api_p0Cases},{$api_p0AutomationCases},{$api_p0AutomatedCases},{$api_p1Cases},{$api_p1AutomationCases},{$api_p1AutomatedCases},{$api_p2Cases},{$api_p2AutomationCases},{$api_p2AutomatedCases},{$web_totalCases},{$web_totalAutomationCases},{$web_alreadyAutomated},{$web_p0Cases},{$web_p0AutomationCases},{$web_p0AutomatedCases},{$web_p1Cases},{$web_p1AutomationCases},{$web_p1AutomatedCases},{$web_p2Cases},{$web_p2AutomationCases},{$web_p2AutomatedCases},{$android_totalCases},{$android_totalAutomationCases},{$android_alreadyAutomated},{$android_p0Cases},{$android_p0AutomationCases},{$android_p0AutomatedCases},{$android_p1Cases},{$android_p1AutomationCases},{$android_p1AutomatedCases},{$android_p2Cases},{$android_p2AutomationCases},{$android_p2AutomatedCases},{$ios_totalCases},{$ios_totalAutomationCases},{$ios_alreadyAutomated},{$ios_p0Cases},{$ios_p0AutomationCases},{$ios_p0AutomatedCases},{$ios_p1Cases},{$ios_p1AutomationCases},{$ios_p1AutomatedCases},{$ios_p2Cases},{$ios_p2AutomationCases},{$ios_p2AutomatedCases});";

		Date formatedDate = date.toLocalDateTime(LocalTime.now()).toDate(TimeZone.getDefault());
		String createdAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(formatedDate);
		testConfig.putHashmapStringTypeAsRunTimeProperty(testRailNumbers);
		testConfig.putRunTimeProperty("createdAt", createdAt);
		testConfig.putRunTimeProperty("projectName", projectName);

		Iterator<Map.Entry<String, String>> itr = testRailNumbers.entrySet().iterator();
		while (itr.hasNext()) {
			Map.Entry<String, String> entry = itr.next();
			testConfig.logCommentForDebugging("Count for " + entry.getKey() + " = " + entry.getValue());
		}

		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true")) {
			Map<String, String> result = Database.executeSelectQuery(testConfig, selectQuery1, DatabaseName.QA_Dashbaord);
			if (result != null && result.size() > 0) {
				testConfig.putRunTimeProperty("id", result.get("id"));
				calculateDelta(testConfig, result, testRailNumbers);
				Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.QA_Dashbaord);
			} else {
				result = Database.executeSelectQuery(testConfig, selectQuery2, DatabaseName.QA_Dashbaord);
				calculateDelta(testConfig, result, testRailNumbers);
				Database.executeQuery(testConfig, insertQuery, QueryType.update, DatabaseName.QA_Dashbaord);
			}
		}
	}

	private void calculateDelta(Config testConfig, Map<String, String> result, HashMap<String, String> testRailNumbers) {
		int newAlreadyAutomatedCount = Integer.parseInt(testRailNumbers.get("alreadyAutomated"));
		int oldAlreadyAutomatedCount = newAlreadyAutomatedCount;
		int positiveDelta = 0;
		int negativeDelta = 0;

		if (result != null) {
			oldAlreadyAutomatedCount = Integer.parseInt(result.get("alreadyAutomated"));
			positiveDelta = Integer.parseInt(result.get("positiveDelta"));
			negativeDelta = Integer.parseInt(result.get("negativeDelta"));
		}

		int diff = newAlreadyAutomatedCount - oldAlreadyAutomatedCount;
		if (diff > 0)
			positiveDelta = positiveDelta + diff;
		else
			negativeDelta = negativeDelta + diff;

		testConfig.putRunTimeProperty("positiveDelta", positiveDelta);
		testConfig.putRunTimeProperty("negativeDelta", negativeDelta);
	}

	private void updateEntityLevelTestRailNumbers(Config testConfig, HashMap<String, String> entityLevelTestRailNumbers) {
		HashMap<String, String> entityLevelInsertion = new HashMap<>();
		for (String keys : verticalLevelData.keySet())
			entityLevelInsertion.put(keys, verticalLevelData.get(keys));

		entityLevelInsertion.forEach((key, value) -> entityLevelTestRailNumbers.merge(key, value, (v1, v2) -> String.valueOf(Integer.valueOf(v1) + Integer.valueOf(v2))));
		int automationCoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("alreadyAutomated")), Integer.parseInt(entityLevelTestRailNumbers.get("totalAutomationCases")));
		entityLevelTestRailNumbers.put("automationCoveragePerc", String.valueOf(automationCoveragePerc));
		int p0CoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("p0AutomatedCases")), Integer.parseInt(entityLevelTestRailNumbers.get("p0AutomationCases")));
		entityLevelTestRailNumbers.put("p0CoveragePerc", String.valueOf(p0CoveragePerc));
		int p1CoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("p1AutomatedCases")), Integer.parseInt(entityLevelTestRailNumbers.get("p1AutomationCases")));
		entityLevelTestRailNumbers.put("p1CoveragePerc", String.valueOf(p1CoveragePerc));
		int p2CoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("p2AutomatedCases")), Integer.parseInt(entityLevelTestRailNumbers.get("p2AutomationCases")));
		entityLevelTestRailNumbers.put("p2CoveragePerc", String.valueOf(p2CoveragePerc));
	}

	private void compareTrueLocally(Config testConfig, String what, boolean actual) {
		if (!actual)
			testConfig.logFail("Failed to verify '" + what + "'");

	}
	
	private void addTestcasesDataInDatabase(Config testConfig, String projectName, Long createdOn, Long testrailId, String suiteId, String title, Long priority, Long type, Long executionMode, List<Long> platform, Boolean isMobileNative, List<Long> apiAutomationStatus, List<Long> webAutomationStatus, List<Long> androidAutomationStatus, List<Long> iosAutomationStatus, String reference, Long updatedBy, Long isDeleted) {
		
		Date date1 = new Date(createdOn * 1000);
		String createdAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(date1);
		testConfig.putRunTimeProperty("createdAt", createdAt);
		String updatedAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(new Date());
		testConfig.putRunTimeProperty("updatedAt", updatedAt);
		testConfig.putRunTimeProperty("projectName", projectName);
		testConfig.putRunTimeProperty("testrailId", testrailId);
		testConfig.putRunTimeProperty("suiteId", suiteId);
		testConfig.putRunTimeProperty("title", sanitiseString(title));
		
		String priorityValue = "Pn";
		if(priorityValue != null)
		{
			switch (priority.intValue()) {
			case 1:
				priorityValue = "P3";
				break;
			case 2:
				priorityValue = "P2";
				break;
			case 3:
				priorityValue = "P1";
				break;
			case 4:
				priorityValue = "P0";
				break;
			}
		}
		testConfig.putRunTimeProperty("priority", priorityValue);
		
		String typeValue = "undefined";
		if(typeValue != null)
		{
			switch (type.intValue()) {
			case 9:
				typeValue = "All / Functional";
				break;
			case 13:
				typeValue = "FCT / Regression";
				break;
			case 11:
				typeValue = "Prod Sanity";
				break;
			}
		}
		testConfig.putRunTimeProperty("type", typeValue);
		testConfig.putRunTimeProperty("executionMode", (executionMode != null && executionMode == 2L) ? "Automatable" : "Manual");
		
		String platformValue = "";
		if(platform != null)
		{
			for (long j : platform) {
				switch ((int) j) {
				case 1:
					platformValue = platformValue + "," + "api";
					break;
				case 2:
					platformValue = platformValue + "," + "web";
					break;
				case 3:
					platformValue = platformValue + "," + "android";
					break;
				case 4:
					platformValue = platformValue + "," + "ios";
					break;
				}
			}
		}

		testConfig.putRunTimeProperty("platform", platformValue.replaceAll("^,|,$", ""));
		testConfig.putRunTimeProperty("isMobileNative", (isMobileNative != null && isMobileNative)? true : false);
		
		if (apiAutomationStatus == null || apiAutomationStatus.isEmpty()) {
		    testConfig.putRunTimeProperty("apiAutomationStatus", "NA");
		} else {
		    testConfig.putRunTimeProperty(
		        "apiAutomationStatus",
		        apiAutomationStatus.contains(2L) ? "Already Automated" : "Pending Automation"
		    );
		}

		if (webAutomationStatus == null || webAutomationStatus.isEmpty()) {
		    testConfig.putRunTimeProperty("webAutomationStatus", "NA");
		} else {
		    testConfig.putRunTimeProperty(
		        "webAutomationStatus",
		        webAutomationStatus.contains(2L) ? "Already Automated" : "Pending Automation"
		    );
		}

		
		if (androidAutomationStatus == null || androidAutomationStatus.isEmpty()) {
		    testConfig.putRunTimeProperty("androidAutomationStatus", "NA");
		} else {
		    testConfig.putRunTimeProperty(
		        "androidAutomationStatus",
		        androidAutomationStatus.contains(2L) ? "Already Automated" : "Pending Automation"
		    );
		}

		
		if (iosAutomationStatus == null || iosAutomationStatus.isEmpty()) {
		    testConfig.putRunTimeProperty("iosAutomationStatus", "NA");
		} else {
		    testConfig.putRunTimeProperty(
		        "iosAutomationStatus",
		        iosAutomationStatus.contains(2L) ? "Already Automated" : "Pending Automation"
		    );
		}

		testConfig.putRunTimeProperty("reference", reference != null ? reference : "undefined");
		testConfig.putRunTimeProperty("updatedBy", updatedBy);
		testConfig.putRunTimeProperty("isDeleted", isDeleted);
		
		// Set automation status timestamps
		String now = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(new Date());
		String defaultTimestamp = "2001-01-01 00:00:00";
		// API
		if (testConfig.getRunTimeProperty("apiAutomationStatus").equals("Already Automated") && (testConfig.getRunTimeProperty("apiAutomatedOn") == null || testConfig.getRunTimeProperty("apiAutomatedOn").equals(defaultTimestamp))) {
			testConfig.putRunTimeProperty("apiAutomatedOn", now);
		} else if (!testConfig.getRunTimeProperty("apiAutomationStatus").equals("Already Automated")) {
			testConfig.putRunTimeProperty("apiAutomatedOn", defaultTimestamp);
		}
		// Web
		if (testConfig.getRunTimeProperty("webAutomationStatus").equals("Already Automated") && (testConfig.getRunTimeProperty("webAutomatedOn") == null || testConfig.getRunTimeProperty("webAutomatedOn").equals(defaultTimestamp))) {
			testConfig.putRunTimeProperty("webAutomatedOn", now);
		} else if (!testConfig.getRunTimeProperty("webAutomationStatus").equals("Already Automated")) {
			testConfig.putRunTimeProperty("webAutomatedOn", defaultTimestamp);
		}
		// Android
		if (testConfig.getRunTimeProperty("androidAutomationStatus").equals("Already Automated") && (testConfig.getRunTimeProperty("androidAutomatedOn") == null || testConfig.getRunTimeProperty("androidAutomatedOn").equals(defaultTimestamp))) {
			testConfig.putRunTimeProperty("androidAutomatedOn", now);
		} else if (!testConfig.getRunTimeProperty("androidAutomationStatus").equals("Already Automated")) {
			testConfig.putRunTimeProperty("androidAutomatedOn", defaultTimestamp);
		}
		// iOS
		if (testConfig.getRunTimeProperty("iosAutomationStatus").equals("Already Automated") && (testConfig.getRunTimeProperty("iosAutomatedOn") == null || testConfig.getRunTimeProperty("iosAutomatedOn").equals(defaultTimestamp))) {
			testConfig.putRunTimeProperty("iosAutomatedOn", now);
		} else if (!testConfig.getRunTimeProperty("iosAutomationStatus").equals("Already Automated")) {
			testConfig.putRunTimeProperty("iosAutomatedOn", defaultTimestamp);
		}

		String selectQuery = "SELECT id from `paymentgateway_tests_data` where testrailId = '{$testrailId}' ORDER BY createdAt DESC LIMIT 1;";
		String updateQuery = "UPDATE `paymentgateway_tests_data` SET createdAt='{$createdAt}', updatedAt='{$updatedAt}', projectName='{$projectName}', verticalName='{$verticalName}', suiteId='{$suiteId}', title='{$title}', priority='{$priority}', type='{$type}', executionMode='{$executionMode}', platform='{$platform}', isMobileNative={$isMobileNative}, apiAutomationStatus='{$apiAutomationStatus}', webAutomationStatus='{$webAutomationStatus}', androidAutomationStatus='{$androidAutomationStatus}', iosAutomationStatus='{$iosAutomationStatus}', apiAutomatedOn='{$apiAutomatedOn}', webAutomatedOn='{$webAutomatedOn}', androidAutomatedOn='{$androidAutomatedOn}', iosAutomatedOn='{$iosAutomatedOn}', reference='{$reference}', updatedBy='{$updatedBy}', isDeleted={$isDeleted} WHERE id = '{$id}';";
		String insertQuery = "INSERT INTO `paymentgateway_tests_data` (createdAt,updatedAt,projectName,verticalName,testrailId,suiteId,title,priority,type,executionMode,platform,isMobileNative,apiAutomationStatus,webAutomationStatus,androidAutomationStatus,iosAutomationStatus,apiAutomatedOn,webAutomatedOn,androidAutomatedOn,iosAutomatedOn,reference,updatedBy,isDeleted) VALUES ('{$createdAt}','{$updatedAt}','{$projectName}','{$verticalName}','{$testrailId}','{$suiteId}','{$title}','{$priority}','{$type}','{$executionMode}','{$platform}',{$isMobileNative},'{$apiAutomationStatus}','{$webAutomationStatus}','{$androidAutomationStatus}','{$iosAutomationStatus}','{$apiAutomatedOn}','{$webAutomatedOn}','{$androidAutomatedOn}','{$iosAutomatedOn}','{$reference}','{$updatedBy}',{$isDeleted});";

		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true")) {
			Map<String, String> result = Database.executeSelectQuery(testConfig, selectQuery, DatabaseName.QA_Dashbaord);
			if (result != null && result.size() > 0) {
				testConfig.putRunTimeProperty("id", result.get("id"));
				Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.QA_Dashbaord);
			} else {
				Database.executeQuery(testConfig, insertQuery, QueryType.update, DatabaseName.QA_Dashbaord);
			}
		}
	}
	
	/**
	 * Marks testcases as deleted in the database if they are no longer present in TestRail
	 * @param testConfig Configuration object
	 * @param projectName Project name
	 * @param suiteId Suite ID
	 * @param processedTestrailIds Set of testrailIds that were found in the current TestRail fetch
	 */
	private void markDeletedTestcasesInDatabase(Config testConfig, String projectName, String suiteId, Set<Long> processedTestrailIds) {
		if (processedTestrailIds == null || processedTestrailIds.isEmpty()) {
			return;
		}
		
		if (!testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true")) {
			return;
		}
		
		try {
			// Build the list of processed testrailIds for the SQL IN clause
			StringBuilder testrailIdsList = new StringBuilder();
			for (Long testrailId : processedTestrailIds) {
				if (testrailIdsList.length() > 0) {
					testrailIdsList.append(",");
				}
				testrailIdsList.append(testrailId);
			}
			
			testConfig.putRunTimeProperty("projectName", projectName);
			testConfig.putRunTimeProperty("suiteId", suiteId);
			testConfig.putRunTimeProperty("testrailIdsList", testrailIdsList.toString());
			
		// Update query to mark testcases as deleted that are not in the processed list
		// Only update testcases that are currently not deleted (isDeleted = 0)
		String updateQuery = "UPDATE `paymentgateway_tests_data` SET isDeleted = 1, updatedAt = NOW() " +
					"WHERE projectName = '{$projectName}' " +
					"AND suiteId = '{$suiteId}' " +
					"AND testrailId NOT IN ({$testrailIdsList}) " +
					"AND isDeleted = 0;";
			
			Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.QA_Dashbaord);
			testConfig.logComment("Marked deleted testcases for project: " + projectName + ", suiteId: " + suiteId);
		} catch (Exception e) {
			testConfig.logException("Error marking deleted testcases in database", e);
		}
	}
	
	private String sanitiseString(String rawData) {
	    if (rawData == null) {
	        return null; // Handle null input gracefully
	    }
	    return rawData
	        .replace("\\", "\\\\")  // Escape backslash
	        .replace("'", "\\'")    // Escape single quote
	        .replace("\"", "\\\"")  // Escape double quote
	        .replace("\0", "\\0")   // Escape null character
	        .replace("\n", "\\n")   // Escape newline
	        .replace("\r", "\\r")   // Escape carriage return
	        .replace("\t", "\\t")   // Escape tab
	        .replace("%", "\\%")    // Escape percent (for LIKE queries)
	        .replace("_", "\\_")   // Escape underscore (for LIKE queries)
	    	.replace("{$", "{");   // Escape $ sign to handle runtime property replacements
	    
	}
}