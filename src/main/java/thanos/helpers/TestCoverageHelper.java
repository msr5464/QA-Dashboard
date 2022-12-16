package thanos.helpers;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Iterator;
import java.util.Map;
import java.util.Set;
import java.util.TimeZone;
import org.joda.time.LocalDate;
import org.joda.time.LocalTime;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import thanos.utils.CommonUtilities;
import thanos.utils.Config;
import thanos.utils.Database;
import thanos.utils.TestRailClient;
import thanos.utils.Database.DatabaseName;
import thanos.utils.Database.QueryType;

public class TestCoverageHelper
{
	private HashMap<String, String> podLevelData;
	private HashMap<String, String> entityLevelData;
	private TestRailClient testRailClient = null;
	
	@SuppressWarnings("serial")
	public TestCoverageHelper(Config testConfig)
	{
		testRailClient = connectToTestRail(testConfig);
		podLevelData = new HashMap<String, String>()
		{
			{
				put("totalCases", "0");
				put("totalAutomationCases", "0");
				put("alreadyAutomated", "0");
				put("p0Cases", "0");
				put("p1Cases", "0");
				put("p2Cases", "0");
				put("p0AutomatedCases", "0");
				put("p1AutomatedCases", "0");
				put("p2AutomatedCases", "0");
				put("automationCoveragePerc", "0");
				put("p0CoveragePerc", "0");
				put("p1CoveragePerc", "0");
				put("p2CoveragePerc", "0");
			}
		};
		entityLevelData = new HashMap<>();
	}
	
	private TestRailClient connectToTestRail(Config testConfig)
	{
		TestRailClient client = new TestRailClient(testConfig.getRunTimeProperty("TestRailHostUrl"));
		client.setUser(testConfig.getRunTimeProperty("TestRailUsername"));
		client.setPassword(testConfig.getRunTimeProperty("TestRailApiToken"));
		testConfig.logComment("Connected to TestRail server successfully.");
		return client;
	}
	
	public void fetchDataFromTestRail(Config testConfig, org.json.JSONObject jsonObject, String entityName)
	{
		LocalDate date = LocalDate.now();
		testConfig.putRunTimeProperty("tableName", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_testrail");
		fetchDataForProjectsAndPods(testConfig, jsonObject, entityName, date);
		
		// At the end, insert data of Entity to All Entities table
		if (entityLevelData.size() != 0)
		{
			testConfig.putRunTimeProperty("tableName", "all_entities_testrail");
			addTestrailDataInDatabase(testConfig, date, entityName, entityLevelData);
		}
	}
	
	private void fetchDataForProjectsAndPods(Config testConfig, org.json.JSONObject jsonObject, String entityName, LocalDate date)
	{
		Iterator<String> testRailConfigKeys = jsonObject.keys();
		while (testRailConfigKeys.hasNext())
		{
			String testRailConfigKey = testRailConfigKeys.next();
			if (testRailConfigKey.equals("testRailSuites"))
			{
				fetchProjectWiseTestRailDataAndInsertIntoDb(testConfig, jsonObject.getJSONArray("testRailSuites"), entityName, date);
				updateEntityLevelTestRailNumbers(testConfig, entityLevelData);
			}
			else
			{
				if (jsonObject.get(testRailConfigKey) instanceof org.json.JSONObject)
				{
					fetchDataForProjectsAndPods(testConfig, jsonObject.getJSONObject(testRailConfigKey), entityName, date);
					testConfig.putRunTimeProperty("tableName", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_testrail");
					if (!testRailConfigKey.toLowerCase().startsWith("pod"))
						testRailConfigKey = "Pod - " + testRailConfigKey;
					addTestrailDataInDatabase(testConfig, date, testRailConfigKey, podLevelData);
					podLevelData.replaceAll((key, value) -> "0");
				}
				else
				{
					testConfig.logFail("Invalid TestRailConfig file format");
				}
			}
		}
	}
	
	private void fetchProjectWiseTestRailDataAndInsertIntoDb(Config testConfig, org.json.JSONArray testRailProjects, String entityName, LocalDate date)
	{
		for (int counter = 0; counter < testRailProjects.length(); counter++)
		{
			String projectName = testRailProjects.getJSONObject(counter).getString("suiteName");
			String suiteId = testRailProjects.getJSONObject(counter).getString("suiteId");
			String projectId = testRailProjects.getJSONObject(counter).getString("projectId");
			testConfig.logComment("ProjectName = " + projectName);
			
			HashMap<String, String> projectTestRailNumbers = fetchProjectSpecificData(testConfig, projectName, suiteId, projectId);
			addTestrailDataInDatabase(testConfig, date, projectName, projectTestRailNumbers);
			testConfig.logComment("==============================================");
			podLevelData.put("totalCases", String.valueOf(Integer.parseInt(podLevelData.get("totalCases")) + Integer.parseInt(projectTestRailNumbers.get("totalCases"))));
			podLevelData.put("totalAutomationCases", String.valueOf(Integer.parseInt(podLevelData.get("totalAutomationCases")) + Integer.parseInt(projectTestRailNumbers.get("totalAutomationCases"))));
			podLevelData.put("alreadyAutomated", String.valueOf(Integer.parseInt(podLevelData.get("alreadyAutomated")) + Integer.parseInt(projectTestRailNumbers.get("alreadyAutomated"))));
			podLevelData.put("p0Cases", String.valueOf(Integer.parseInt(podLevelData.get("p0Cases")) + Integer.parseInt(projectTestRailNumbers.get("p0Cases"))));
			podLevelData.put("p1Cases", String.valueOf(Integer.parseInt(podLevelData.get("p1Cases")) + Integer.parseInt(projectTestRailNumbers.get("p1Cases"))));
			podLevelData.put("p2Cases", String.valueOf(Integer.parseInt(podLevelData.get("p2Cases")) + Integer.parseInt(projectTestRailNumbers.get("p2Cases"))));
			podLevelData.put("p0AutomatedCases", String.valueOf(Integer.parseInt(podLevelData.get("p0AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("p0AutomatedCases"))));
			podLevelData.put("p1AutomatedCases", String.valueOf(Integer.parseInt(podLevelData.get("p1AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("p1AutomatedCases"))));
			podLevelData.put("p2AutomatedCases", String.valueOf(Integer.parseInt(podLevelData.get("p2AutomatedCases")) + Integer.parseInt(projectTestRailNumbers.get("p2AutomatedCases"))));
			
			int automationCoveragePerc = calculatePercentage(Integer.parseInt(podLevelData.get("alreadyAutomated")), Integer.parseInt(podLevelData.get("totalAutomationCases")));
			podLevelData.put("automationCoveragePerc", String.valueOf(automationCoveragePerc));
			int p0CoveragePerc = calculatePercentage(Integer.parseInt(podLevelData.get("p0AutomatedCases")), Integer.parseInt(podLevelData.get("p0Cases")));
			podLevelData.put("p0CoveragePerc", String.valueOf(p0CoveragePerc));
			int p1CoveragePerc = calculatePercentage(Integer.parseInt(podLevelData.get("p1AutomatedCases")), Integer.parseInt(podLevelData.get("p1Cases")));
			podLevelData.put("p1CoveragePerc", String.valueOf(p1CoveragePerc));
			int p2CoveragePerc = calculatePercentage(Integer.parseInt(podLevelData.get("p2AutomatedCases")), Integer.parseInt(podLevelData.get("p2Cases")));
			podLevelData.put("p2CoveragePerc", String.valueOf(p2CoveragePerc));
		}
	}
	
	private HashMap<String, String> fetchProjectSpecificData(Config testConfig, String projectName, String suiteId, String projectId)
	{
		HashMap<String, String> projectTestRailNumbers = new HashMap<>();
		int totalCases = 0;
		int totalAutomationCases = 0;
		int alreadyAutomated = 0;
		int p0Cases = 0;
		int p1Cases = 0;
		int p2Cases = 0;
		int p0AutomatedCases = 0;
		int p1AutomatedCases = 0;
		int p2AutomatedCases = 0;
		int automationCoveragePerc = 0;
		int p0CoveragePerc = 0;
		int p1CoveragePerc = 0;
		int p2CoveragePerc = 0;
		
		Set<Object> incorrectDataTestRail = new HashSet<>();
		JSONArray cases = null;
		JSONObject response = null;
		JSONObject links = null;
		String nextLink = null;
		int offset = 0;
		int retryCount = 0;
		
		do
		{
			for (retryCount = 0; retryCount <= 1; retryCount++)
			{
				try
				{
					response = (JSONObject) testRailClient.sendGet("get_cases/" + projectId + "&suite_id=" + suiteId + "&offset=" + offset);
					cases = (JSONArray) response.get("cases");
					links = (JSONObject) response.get("_links");
					nextLink = (String) links.get("next");
					if (nextLink != null)
						offset = Integer.parseInt(nextLink.substring(nextLink.indexOf("&offset=") + "&offset=".length(), nextLink.indexOf("&limit")));
					break;
				}
				catch (Exception e)
				{
					if (retryCount == 1)
						testConfig.logExceptionAndFail("Ending execution...", e);
					else
					{
						testConfig.logException("Retrying after exception...", e);
						CommonUtilities.waitForSeconds(testConfig, 2);
					}
				}
			}
			testConfig.logComment("Number of cases: " + cases.size());
			for (int i = 0, size = cases.size(); i < size; i++)
			{
				JSONObject objectInArray = (JSONObject) cases.get(i);
				Long automationStatus = (Long) objectInArray.get("custom_automation_type");
				totalCases++;
				/*
				 * 3, Automated 4, Pending automation 5, Manual 6, Needs modification 7, iOS
				 * Automated 8, Android Automated 9, Blocked 10, Cannot be automated
				 */
				switch (automationStatus.intValue())
				{
				case 3:
					alreadyAutomated++;
					totalAutomationCases++;
					break;
				case 4:
					totalAutomationCases++;
					break;
				case 6:
					totalAutomationCases++;
					break;
				default:
					// testConfig.logFail(suiteName+"==>automationStatus=" +
					// automationStatus.intValue());
					break;
				}
				
				if (automationStatus.intValue() == 3 || automationStatus.intValue() == 4 || automationStatus.intValue() == 6)
				{
					Long priority = (Long) objectInArray.get("priority_id");
					compareTrueLocally(testConfig, "Priority of Automated Test Case", priority == 4L || priority == 3L || priority == 2L || priority == 1L);
					switch (priority.intValue())
					{
					case 5:
						incorrectDataTestRail.add(objectInArray.get("id"));
						break;
					case 4:
						p0Cases++;
						if (automationStatus.intValue() == 3)
							p0AutomatedCases++;
						break;
					case 3:
						p1Cases++;
						if (automationStatus.intValue() == 3)
							p1AutomatedCases++;
						break;
					case 2:
						p2Cases++;
						if (automationStatus.intValue() == 3)
							p2AutomatedCases++;
						break;
					case 1:
						break;
					default:
						testConfig.logFail(projectName + "==>priority=" + priority.intValue());
						
					}
				}
			}
		}
		while (nextLink != null);
		
		if (incorrectDataTestRail.size() > 0)
		{
			testConfig.logFail("Please enter valid Priority for Test Case =>");
			for (Object o : incorrectDataTestRail)
				testConfig.logFail(o.toString());
		}
		
		testConfig.logComment("Total Cases = " + totalCases);
		testConfig.logComment("Total Automation Cases = " + totalAutomationCases);
		testConfig.logComment("Already Automated = " + alreadyAutomated);
		automationCoveragePerc = calculatePercentage(alreadyAutomated, totalAutomationCases);
		testConfig.logComment("AutomationCoveragePerc = " + automationCoveragePerc + "%");
		
		testConfig.logComment("p0Cases = " + p0Cases);
		testConfig.logComment("p0AutomatedCases = " + p0AutomatedCases);
		p0CoveragePerc = calculatePercentage(p0AutomatedCases, p0Cases);
		testConfig.logComment("p0CoveragePerc = " + p0CoveragePerc);
		
		testConfig.logComment("p1Cases = " + p1Cases);
		testConfig.logComment("p1AutomatedCases = " + p1AutomatedCases);
		p1CoveragePerc = calculatePercentage(p1AutomatedCases, p1Cases);
		testConfig.logComment("p1CoveragePerc = " + p1CoveragePerc);
		
		testConfig.logComment("p2Cases = " + p2Cases);
		testConfig.logComment("p2AutomatedCases = " + p2AutomatedCases);
		p2CoveragePerc = calculatePercentage(p2AutomatedCases, p2Cases);
		testConfig.logComment("p2CoveragePerc = " + p2CoveragePerc);
		
		projectTestRailNumbers.put("totalCases", Integer.toString(totalCases));
		projectTestRailNumbers.put("totalAutomationCases", Integer.toString(totalAutomationCases));
		projectTestRailNumbers.put("alreadyAutomated", Integer.toString(alreadyAutomated));
		projectTestRailNumbers.put("p0Cases", Integer.toString(p0Cases));
		projectTestRailNumbers.put("p1Cases", Integer.toString(p1Cases));
		projectTestRailNumbers.put("p2Cases", Integer.toString(p2Cases));
		projectTestRailNumbers.put("p0AutomatedCases", Integer.toString(p0AutomatedCases));
		projectTestRailNumbers.put("p1AutomatedCases", Integer.toString(p1AutomatedCases));
		projectTestRailNumbers.put("p2AutomatedCases", Integer.toString(p2AutomatedCases));
		projectTestRailNumbers.put("automationCoveragePerc", Integer.toString(automationCoveragePerc));
		projectTestRailNumbers.put("p0CoveragePerc", Integer.toString(p0CoveragePerc));
		projectTestRailNumbers.put("p1CoveragePerc", Integer.toString(p1CoveragePerc));
		projectTestRailNumbers.put("p2CoveragePerc", Integer.toString(p2CoveragePerc));
		return projectTestRailNumbers;
	}
	
	private Integer calculatePercentage(int value, int total)
	{
		int percentage = 0;
		if (total > 0 && value > 0)
			percentage = (value * 100) / total;
		
		return percentage;
	}
	
	private void addTestrailDataInDatabase(Config testConfig, LocalDate date, String projectName, HashMap<String, String> testRailNumbers)
	{
		String selectQuery1 = "SELECT id,alreadyAutomated,positiveDelta,negativeDelta from {$tableName} where projectName = '{$projectName}' AND DATE(createdAt)= DATE('{$createdAt}') ORDER BY createdAt DESC LIMIT 1;";
		String selectQuery2 = "SELECT id,alreadyAutomated,(0) as positiveDelta,(0) as negativeDelta from {$tableName} where projectName = '{$projectName}' ORDER BY id DESC LIMIT 1;";
		String updateQuery = "UPDATE {$tableName} SET createdAt='{$createdAt}',totalCases={$totalCases},totalAutomationCases={$totalAutomationCases},alreadyAutomated={$alreadyAutomated},automationCoveragePerc={$automationCoveragePerc},p0Cases={$p0Cases},p0AutomatedCases={$p0AutomatedCases},p0CoveragePerc={$p0CoveragePerc},p1Cases={$p1Cases},p1AutomatedCases={$p1AutomatedCases},p1CoveragePerc={$p1CoveragePerc},p2Cases={$p2Cases},p2AutomatedCases={$p2AutomatedCases},p2CoveragePerc={$p2CoveragePerc},positiveDelta={$positiveDelta},negativeDelta={$negativeDelta} WHERE id = {$id};";
		String insertQuery = "INSERT into {$tableName} (createdAt,projectName,totalCases,totalAutomationCases,alreadyAutomated,automationCoveragePerc,p0Cases,p0AutomatedCases,p0CoveragePerc,p1Cases,p1AutomatedCases,p1CoveragePerc,p2Cases,p2AutomatedCases,p2CoveragePerc,positiveDelta,negativeDelta) values ('{$createdAt}','{$projectName}',{$totalCases},{$totalAutomationCases},{$alreadyAutomated},{$automationCoveragePerc},{$p0Cases},{$p0AutomatedCases},{$p0CoveragePerc},{$p1Cases},{$p1AutomatedCases},{$p1CoveragePerc},{$p2Cases},{$p2AutomatedCases},{$p2CoveragePerc},{$positiveDelta},{$negativeDelta});";
		
		Date formatedDate = date.toLocalDateTime(LocalTime.now()).toDate(TimeZone.getDefault());
		String createdAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(formatedDate);
		testConfig.putHashmapStringTypeAsRunTimeProperty(testRailNumbers);
		testConfig.putRunTimeProperty("createdAt", createdAt);
		testConfig.putRunTimeProperty("projectName", projectName);
		
		Iterator<Map.Entry<String, String>> itr = testRailNumbers.entrySet().iterator();
		while (itr.hasNext())
		{
			Map.Entry<String, String> entry = itr.next();
			testConfig.logComment("Count for " + entry.getKey() + " = " + entry.getValue());
		}
		
		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true"))
		{
			Map<String, String> result = Database.executeSelectQuery(testConfig, selectQuery1, DatabaseName.Thanos);
			if (result != null && result.size() > 0)
			{
				testConfig.putRunTimeProperty("id", result.get("id"));
				calculateDelta(testConfig, result, testRailNumbers);
				Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.Thanos);
			}
			else
			{
				result = Database.executeSelectQuery(testConfig, selectQuery2, DatabaseName.Thanos);
				calculateDelta(testConfig, result, testRailNumbers);
				Database.executeQuery(testConfig, insertQuery, QueryType.update, DatabaseName.Thanos);
			}
		}
	}
	
	private void calculateDelta(Config testConfig, Map<String, String> result, HashMap<String, String> testRailNumbers)
	{
		int newAlreadyAutomatedCount = Integer.parseInt(testRailNumbers.get("alreadyAutomated"));
		int oldAlreadyAutomatedCount = newAlreadyAutomatedCount;
		int positiveDelta = 0;
		int negativeDelta = 0;
		
		if (result != null)
		{
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
	
	private void updateEntityLevelTestRailNumbers(Config testConfig, HashMap<String, String> entityLevelTestRailNumbers)
	{
		HashMap<String, String> entityLevelInsertion = new HashMap<>();
		for (String keys : podLevelData.keySet())
			entityLevelInsertion.put(keys, podLevelData.get(keys));
		
		entityLevelInsertion.forEach((key, value) -> entityLevelTestRailNumbers.merge(key, value, (v1, v2) -> String.valueOf(Integer.valueOf(v1) + Integer.valueOf(v2))));
		int automationCoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("alreadyAutomated")), Integer.parseInt(entityLevelTestRailNumbers.get("totalAutomationCases")));
		entityLevelTestRailNumbers.put("automationCoveragePerc", String.valueOf(automationCoveragePerc));
		int p0CoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("p0AutomatedCases")), Integer.parseInt(entityLevelTestRailNumbers.get("p0Cases")));
		entityLevelTestRailNumbers.put("p0CoveragePerc", String.valueOf(p0CoveragePerc));
		int p1CoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("p1AutomatedCases")), Integer.parseInt(entityLevelTestRailNumbers.get("p1Cases")));
		entityLevelTestRailNumbers.put("p1CoveragePerc", String.valueOf(p1CoveragePerc));
		int p2CoveragePerc = calculatePercentage(Integer.parseInt(entityLevelTestRailNumbers.get("p2AutomatedCases")), Integer.parseInt(entityLevelTestRailNumbers.get("p2Cases")));
		entityLevelTestRailNumbers.put("p2CoveragePerc", String.valueOf(p2CoveragePerc));
	}
	
	private void compareTrueLocally(Config testConfig, String what, boolean actual)
	{
		if (!actual)
			testConfig.logFail("Failed to verify '" + what + "'");
		
	}
}