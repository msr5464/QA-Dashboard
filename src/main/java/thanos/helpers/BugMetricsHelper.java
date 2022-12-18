package thanos.helpers;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.TimeZone;
import org.joda.time.LocalDate;
import org.joda.time.LocalDateTime;
import org.joda.time.LocalTime;
import net.rcarz.jiraclient.BasicCredentials;
import net.rcarz.jiraclient.Issue;
import net.rcarz.jiraclient.Issue.SearchResult;
import thanos.utils.Config;
import thanos.utils.Database;
import thanos.utils.Database.DatabaseName;
import thanos.utils.Database.QueryType;
import net.rcarz.jiraclient.JiraClient;
import net.rcarz.jiraclient.JiraException;

public class BugMetricsHelper
{
	private HashMap<String, Integer> podLevelData;
	private HashMap<String, Integer> entityLevelData;
	private JiraClient jiraClient = null;
	
	@SuppressWarnings("serial")
	public BugMetricsHelper(Config testConfig)
	{
		jiraClient = connectToJira(testConfig);
		podLevelData = new HashMap<String, Integer>()
		{
			{
				put("totalTicketsTested", 0);
				put("totalProdBugs", 0);
				put("p0ProdBugs", 0);
				put("p1ProdBugs", 0);
				put("p2ProdBugs", 0);
				put("pnProdBugs", 0);
				put("totalStgBugs", 0);
				put("p0StgBugs", 0);
				put("p1StgBugs", 0);
				put("p2StgBugs", 0);
				put("pnStgBugs", 0);
			}
		};
		entityLevelData = new HashMap<>();
	}
	
	private JiraClient connectToJira(Config testConfig)
	{
		BasicCredentials creds = new BasicCredentials(testConfig.getRunTimeProperty("JiraUsername"), testConfig.getRunTimeProperty("JiraApiToken"));
		JiraClient jiraClient = new JiraClient(testConfig.getRunTimeProperty("JiraHostUrl"), creds);
		testConfig.logComment("Connected to Jira server successfully.");
		return jiraClient;
	}
	
	public void fetchDataFromJira(Config testConfig, org.json.JSONObject jsonObject, String entityName, LocalDate date, HashMap<String, String> jiraFilters)
	{
		fetchDataForProjectsAndPods(testConfig, jsonObject, entityName, date, jiraFilters);
		
		// At the end, insert data of Entity to All Entities table
		if (entityLevelData.size() != 0)
		{
			testConfig.putRunTimeProperty("tableName1", "all_entities_jira");
			addJiraDataInDatabase(testConfig, date, entityName, entityLevelData);
			entityLevelData.replaceAll((key, value) -> 0);
		}
	}
	
	private void fetchDataForProjectsAndPods(Config testConfig, org.json.JSONObject jsonObject, String entityName, LocalDate date, HashMap<String, String> jiraFilters)
	{
		Iterator<String> jiraConfigKeys = jsonObject.keys();
		while (jiraConfigKeys.hasNext())
		{
			String jiraConfigKey = jiraConfigKeys.next();
			if (jiraConfigKey.equals("jiraProjects"))
			{
				fetchProjectWiseJiraDataAndInsertIntoDb(testConfig, entityName, jsonObject.getJSONArray("jiraProjects"), date, jiraFilters);
				updateEntityLevelJiraNumbers(entityLevelData);
			}
			else
			{
				if (jsonObject.get(jiraConfigKey) instanceof org.json.JSONObject)
				{
					String podName = jiraConfigKey;
					if (!jiraConfigKey.toLowerCase().startsWith("pod"))
						podName = "Pod - " + jiraConfigKey;
					testConfig.putRunTimeProperty("podName", podName);
					fetchDataForProjectsAndPods(testConfig, jsonObject.getJSONObject(jiraConfigKey), entityName, date, jiraFilters);
					testConfig.putRunTimeProperty("tableName1", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_jira");
					addJiraDataInDatabase(testConfig, date, podName, podLevelData);
					podLevelData.replaceAll((key, value) -> 0);
				}
				else
				{
					testConfig.logFail("Invalid JiraConfig file format");
				}
			}
			
		}
	}
	
	private void fetchProjectWiseJiraDataAndInsertIntoDb(Config testConfig, String entityName, org.json.JSONArray jiraProjects, LocalDate date, HashMap<String, String> jiraFilters)
	{
		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true"))
		{
			testConfig.putRunTimeProperty("tableName1", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_jira");
			testConfig.putRunTimeProperty("tableName2", entityName.toLowerCase().trim().replaceAll(" ", "_") + "_bugs");
			Database.executeQuery(testConfig, "UPDATE {$tableName2} SET isDeleted=1 where podName='{$podName}';", QueryType.update, DatabaseName.Thanos);
		}
		
		HashMap<String, Integer> projectJiraNumbers = new HashMap<>();
		for (int counter = 0; counter < jiraProjects.length(); counter++)
		{
			String projectName = jiraProjects.getJSONObject(counter).getString("projectName");
			String projectKey = jiraProjects.getJSONObject(counter).getString("projectKey");
			String subProjectForFCT = jiraProjects.getJSONObject(counter).has("subProjectForFCT") ? jiraProjects.getJSONObject(counter).getString("subProjectForFCT") : null;
			String affectsEnvironment = jiraProjects.getJSONObject(counter).has("environmentCustomField") ? jiraProjects.getJSONObject(counter).getString("environmentCustomField") : jiraFilters.get("environmentCustomField");
			String bugFoundByField = jiraProjects.getJSONObject(counter).has("bugFoundByCustomField") ? jiraProjects.getJSONObject(counter).getString("bugFoundByCustomField") : jiraFilters.get("bugFoundByCustomField");
			String bugCategoryField = jiraProjects.getJSONObject(counter).has("bugCategoryCustomField") ? jiraProjects.getJSONObject(counter).getString("bugCategoryCustomField") : jiraFilters.get("bugCategoryCustomField");
			testConfig.logComment("Project Name = " + projectName);
			
			int totalTicketsTested = 0;
			int p0ProdBugs = 0;
			int p1ProdBugs = 0;
			int p2ProdBugs = 0;
			int pnProdBugs = 0;
			int totalProdBugs = 0;
			int p0StgBugs = 0;
			int p1StgBugs = 0;
			int p2StgBugs = 0;
			int pnStgBugs = 0;
			int totalStgBugs = 0;
			
			testConfig.putRunTimeProperty("projectKey", projectKey);
			String filterToBeUsed = testConfig.replaceArgumentsWithRunTimeProperties(jiraFilters.get("ticketTestedFilter")) + " AND updatedDate < '" + new SimpleDateFormat("yyyy/MM/dd").format(date.plusDays(1).toDate()) + "'";
			SearchResult allTestedTickets = performSearchForIssuesAndGetResult(testConfig, jiraClient, filterToBeUsed, 0);
			totalTicketsTested = allTestedTickets.total;
			
			HashMap<String, Integer> hashMap = applyJiraFilter(testConfig, projectKey, projectName, null, date, jiraFilters.get("reportedBugsFilter"), affectsEnvironment, bugFoundByField, bugCategoryField);
			p0ProdBugs = hashMap.get("p0ProdBugs");
			p1ProdBugs = hashMap.get("p1ProdBugs");
			p2ProdBugs = hashMap.get("p2ProdBugs");
			pnProdBugs = hashMap.get("pnProdBugs");
			totalProdBugs = hashMap.get("totalProdBugs");
			p0StgBugs = hashMap.get("p0StgBugs");
			p1StgBugs = hashMap.get("p1StgBugs");
			p2StgBugs = hashMap.get("p2StgBugs");
			pnStgBugs = hashMap.get("pnStgBugs");
			totalStgBugs = hashMap.get("totalStgBugs");
			
			if (subProjectForFCT != null)
			{
				HashMap<String, Integer> fctHashMap = applyJiraFilter(testConfig, projectName, projectName, subProjectForFCT, date, jiraFilters.get("reportedBugsFilter"), affectsEnvironment, bugFoundByField, bugCategoryField);
				p0ProdBugs = p0ProdBugs + fctHashMap.get("p0ProdBugs");
				p1ProdBugs = p1ProdBugs + fctHashMap.get("p1ProdBugs");
				p2ProdBugs = p2ProdBugs + fctHashMap.get("p2ProdBugs");
				pnProdBugs = pnProdBugs + fctHashMap.get("pnProdBugs");
				totalProdBugs = totalProdBugs + fctHashMap.get("totalProdBugs");
				p0StgBugs = p0StgBugs + fctHashMap.get("p0StgBugs");
				p1StgBugs = p1StgBugs + fctHashMap.get("p1StgBugs");
				p2StgBugs = p2StgBugs + fctHashMap.get("p2StgBugs");
				pnStgBugs = pnStgBugs + fctHashMap.get("pnStgBugs");
				totalStgBugs = totalStgBugs + fctHashMap.get("totalStgBugs");
			}
			
			testConfig.logComment("P0 Prod Bugs = " + p0ProdBugs);
			testConfig.logComment("P1 Prod Bugs = " + p1ProdBugs);
			testConfig.logComment("P2 Prod Bugs = " + p2ProdBugs);
			testConfig.logComment("PN Prod Bugs = " + pnProdBugs);
			testConfig.logComment("Total Prod Bugs = " + totalProdBugs);
			testConfig.logComment("P0 Stg Bugs = " + p0StgBugs);
			testConfig.logComment("P1 Stg Bugs = " + p1StgBugs);
			testConfig.logComment("P2 Stg Bugs = " + p2StgBugs);
			testConfig.logComment("PN Stg Bugs = " + pnStgBugs);
			testConfig.logComment("Total Stg Bugs = " + totalStgBugs);
			
			projectJiraNumbers.put("totalTicketsTested", totalTicketsTested);
			projectJiraNumbers.put("p0ProdBugs", p0ProdBugs);
			projectJiraNumbers.put("p1ProdBugs", p1ProdBugs);
			projectJiraNumbers.put("p2ProdBugs", p2ProdBugs);
			projectJiraNumbers.put("pnProdBugs", pnProdBugs);
			projectJiraNumbers.put("totalProdBugs", totalProdBugs);
			projectJiraNumbers.put("p0StgBugs", p0StgBugs);
			projectJiraNumbers.put("p1StgBugs", p1StgBugs);
			projectJiraNumbers.put("p2StgBugs", p2StgBugs);
			projectJiraNumbers.put("pnStgBugs", pnStgBugs);
			projectJiraNumbers.put("totalStgBugs", totalStgBugs);
			
			addJiraDataInDatabase(testConfig, date, projectName, projectJiraNumbers);
			testConfig.logComment("==============================================");
			
			podLevelData.put("totalTicketsTested", podLevelData.get("totalTicketsTested") + totalTicketsTested);
			podLevelData.put("p0ProdBugs", podLevelData.get("p0ProdBugs") + p0ProdBugs);
			podLevelData.put("p1ProdBugs", podLevelData.get("p1ProdBugs") + p1ProdBugs);
			podLevelData.put("p2ProdBugs", podLevelData.get("p2ProdBugs") + p2ProdBugs);
			podLevelData.put("pnProdBugs", podLevelData.get("pnProdBugs") + pnProdBugs);
			podLevelData.put("totalProdBugs", podLevelData.get("totalProdBugs") + totalProdBugs);
			podLevelData.put("p0StgBugs", podLevelData.get("p0StgBugs") + p0StgBugs);
			podLevelData.put("p1StgBugs", podLevelData.get("p1StgBugs") + p1StgBugs);
			podLevelData.put("p2StgBugs", podLevelData.get("p2StgBugs") + p2StgBugs);
			podLevelData.put("pnStgBugs", podLevelData.get("pnStgBugs") + pnStgBugs);
			podLevelData.put("totalStgBugs", podLevelData.get("totalStgBugs") + totalStgBugs);
		}
	}
	
	private HashMap<String, Integer> applyJiraFilter(Config testConfig, String projectKey, String projectName, String subProjectForFCT, LocalDate date, String jiraFilter, String environmentField, String bugFoundByField, String bugCategoryField)
	{
		int totalCases = 0;
		int p0ProdBugs = 0;
		int p1ProdBugs = 0;
		int p2ProdBugs = 0;
		int pnProdBugs = 0;
		int totalProdBugs = 0;
		int p0StgBugs = 0;
		int p1StgBugs = 0;
		int p2StgBugs = 0;
		int pnStgBugs = 0;
		int totalStgBugs = 0;
		
		String filterToBeUsed = testConfig.replaceArgumentsWithRunTimeProperties(jiraFilter) + " AND createdDate < '" + new SimpleDateFormat("yyyy/MM/dd").format(date.plusDays(1).toDate()) + "' order by key";
		if (subProjectForFCT != null)
		{
			testConfig.logComment("ProjectKey = " + projectKey + ", SubProjectForFCT = " + subProjectForFCT);
			testConfig.putRunTimeProperty("projectKey", "GFCT");
			filterToBeUsed = testConfig.replaceArgumentsWithRunTimeProperties(jiraFilter) + " AND createdDate < '" + new SimpleDateFormat("yyyy/MM/dd").format(date.plusDays(1).toDate()) + "' AND 'Release Stream Name'='" + subProjectForFCT + "' order by key";
		}
		
		SearchResult searchResults = performSearchForIssuesAndGetResult(testConfig, jiraClient, filterToBeUsed, 0);
		int totalBugs = (searchResults == null) ? 0 : searchResults.total;
		
		while (totalCases < totalBugs)
		{
			System.out.println(searchResults.start);
			System.out.println(searchResults.total);
			System.out.println(totalCases);
			
			for (Issue issue : searchResults.issues)
			{
				String createdAt = issue.getField("created").toString().split("\\+")[0];
				Date parsedDate = LocalDateTime.parse(createdAt).toDate(TimeZone.getDefault());
				createdAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(parsedDate);
				System.out.println(createdAt);
				String issueId = issue.getKey();
				System.out.println(issueId);
				String status = issue.getStatus().toString();
				System.out.println(status);
				String title = issue.getSummary().replace("'", "");
				System.out.println(title);
				String environment = getCustomValue(issue.getField("customfield_" + environmentField).toString());
				System.out.println(environment);
				String bugFoundBy = getCustomValue(issue.getField("customfield_" + bugFoundByField).toString());
				System.out.println(bugFoundBy);
				String bugCategory = getCustomValue(issue.getField("customfield_" + bugCategoryField).toString());
				System.out.println(bugCategory);
				String priority = issue.getPriority().toString();
				System.out.println(priority);
				String sprint = getCustomValue(issue.getField("customfield_10007").toString());
				System.out.println(sprint);
				addJiraBugsInDatabase(testConfig, createdAt, projectName, testConfig.getRunTimeProperty("podName"), issueId, status, title, environment, bugFoundBy, bugCategory, priority, sprint);
				
				totalCases++;
				Object response = issue.getField("customfield_" + environmentField);
				if (response.toString().equalsIgnoreCase("null"))
				{
					testConfig.logFail("Environment field not found for: " + issue.getKey());
				}
				else
				{
					net.sf.json.JSONObject jsonObject = (net.sf.json.JSONObject) issue.getField("customfield_" + environmentField);
					if (jsonObject.get("value").toString().equalsIgnoreCase("Production"))
					{
						totalProdBugs++;
						switch (issue.getPriority().toString())
						{
						case "Blocker":
						case "P0":
							p0ProdBugs++;
							break;
						case "P1":
							p1ProdBugs++;
							break;
						case "P2":
							p2ProdBugs++;
							break;
						default:
							pnProdBugs++;
						}
					}
					else
					{
						totalStgBugs++;
						switch (issue.getPriority().toString())
						{
						case "Blocker":
						case "P0":
							p0StgBugs++;
							break;
						case "P1":
							p1StgBugs++;
							break;
						case "P2":
							p2StgBugs++;
							break;
						default:
							pnStgBugs++;
						}
					}
				}
			}
			searchResults = performSearchForIssuesAndGetResult(testConfig, jiraClient, filterToBeUsed, totalCases);
		}
		
		HashMap<String, Integer> hashMap = new HashMap<String, Integer>();
		hashMap.put("p0ProdBugs", p0ProdBugs);
		hashMap.put("p1ProdBugs", p1ProdBugs);
		hashMap.put("p2ProdBugs", p2ProdBugs);
		hashMap.put("pnProdBugs", pnProdBugs);
		hashMap.put("totalProdBugs", totalProdBugs);
		hashMap.put("p0StgBugs", p0StgBugs);
		hashMap.put("p1StgBugs", p1StgBugs);
		hashMap.put("p2StgBugs", p2StgBugs);
		hashMap.put("pnStgBugs", pnStgBugs);
		hashMap.put("totalStgBugs", totalStgBugs);
		return hashMap;
	}
	
	private void addJiraDataInDatabase(Config testConfig, LocalDate date, String projectName, HashMap<String, Integer> jiraNumbers)
	{
		String selectQuery = "SELECT id from {$tableName1} where projectName = '{$projectName}' AND DATE(createdAt)= DATE('{$createdAt}') ORDER BY createdAt DESC LIMIT 1;";
		String updateQuery = "UPDATE {$tableName1} SET createdAt='{$createdAt}',totalTicketsTested={$totalTicketsTested},totalProdBugs={$totalProdBugs},p0ProdBugs={$p0ProdBugs},p1ProdBugs={$p1ProdBugs},p2ProdBugs={$p2ProdBugs},pnProdBugs={$pnProdBugs},totalStgBugs={$totalStgBugs},p0StgBugs={$p0StgBugs},p1StgBugs={$p1StgBugs},p2StgBugs={$p2StgBugs},pnStgBugs={$pnStgBugs} WHERE id = {$id};";
		String insertQuery = "INSERT into {$tableName1} (createdAt,projectName,totalTicketsTested,totalProdBugs,p0ProdBugs,p1ProdBugs,p2ProdBugs,pnProdBugs,totalStgBugs,p0StgBugs,p1StgBugs,p2StgBugs,pnStgBugs) values ('{$createdAt}','{$projectName}',{$totalTicketsTested},{$totalProdBugs},{$p0ProdBugs},{$p1ProdBugs},{$p2ProdBugs},{$pnProdBugs},{$totalStgBugs},{$p0StgBugs},{$p1StgBugs},{$p2StgBugs},{$pnStgBugs});";
		
		Date formatedDate = date.toLocalDateTime(LocalTime.now()).toDate(TimeZone.getDefault());
		String createdAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(formatedDate);
		testConfig.putRunTimeProperty("createdAt", createdAt);
		testConfig.putRunTimeProperty("projectName", projectName);
		testConfig.putRunTimeProperty(jiraNumbers);
		
		Iterator<Map.Entry<String, Integer>> itr = jiraNumbers.entrySet().iterator();
		while (itr.hasNext())
		{
			Map.Entry<String, Integer> entry = itr.next();
			testConfig.logComment("Count for " + entry.getKey() + " = " + entry.getValue());
		}
		
		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true"))
		{
			Map<String, String> result = null;
			ResultSet resultSet = (ResultSet) Database.executeQuery(testConfig, selectQuery, QueryType.select, DatabaseName.Thanos);
			try
			{
				while (resultSet.next())
				{
					result = Database.createHashMapFromResultSet(testConfig, resultSet);
					testConfig.logComment("Query Result :- " + result.toString());
					break;
				}
			}
			catch (SQLException var8)
			{
				testConfig.logExceptionAndFail(var8);
			}
			
			if (result != null && result.size() > 0)
			{
				testConfig.putRunTimeProperty("id", result.get("id"));
				Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.Thanos);
			}
			else
			{
				Database.executeQuery(testConfig, insertQuery, QueryType.update, DatabaseName.Thanos);
			}
		}
	}
	
	private void updateEntityLevelJiraNumbers(HashMap<String, Integer> entityLevelJiraNumbers)
	{
		podLevelData.forEach((key, value) -> entityLevelJiraNumbers.merge(key, value, (v1, v2) -> v1 + v2));
	}
	
	private String getCustomValue(String rowData)
	{
		if (rowData.equalsIgnoreCase("null"))
			return "";
		else
			return rowData.split(",")[1].split(":")[1].replace("\"", "");
	}
	
	private void addJiraBugsInDatabase(Config testConfig, String createdAt, String projectName, String podName, String issueId, String status, String title, String environment, String bugFoundBy, String bugCategory, String priority, String sprint)
	{
		String selectQuery = "SELECT id from {$tableName2} where issueId = '{$issueId}';";
		String updateQuery = "UPDATE {$tableName2} SET projectName='{$projectName}',podName='{$podName}',status='{$status}',title='{$title}',environment='{$environment}',bugFoundBy='{$bugFoundBy}',bugCategory='{$bugCategory}',priority='{$priority}',sprint='{$sprint}', isDeleted=0 WHERE id = {$id};";
		String insertQuery = "INSERT into {$tableName2} (createdAt,projectName,podName,issueId,status,title,environment,bugFoundBy,bugCategory,priority,sprint,isDeleted) values ('{$createdAt}','{$projectName}','{$podName}','{$issueId}','{$status}','{$title}','{$environment}','{$bugFoundBy}','{$bugCategory}','{$priority}','{$sprint}',0);";
		
		testConfig.putRunTimeProperty("createdAt", createdAt);
		testConfig.putRunTimeProperty("projectName", projectName);
		testConfig.putRunTimeProperty("podName", podName);
		testConfig.putRunTimeProperty("issueId", issueId);
		testConfig.putRunTimeProperty("status", status);
		testConfig.putRunTimeProperty("title", title);
		testConfig.putRunTimeProperty("environment", environment);
		testConfig.putRunTimeProperty("bugFoundBy", bugFoundBy);
		testConfig.putRunTimeProperty("bugCategory", bugCategory);
		testConfig.putRunTimeProperty("priority", priority);
		testConfig.putRunTimeProperty("sprint", sprint);
		
		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true"))
		{
			Map<String, String> result = Database.executeSelectQuery(testConfig, selectQuery, DatabaseName.Thanos);
			if (result != null && result.size() > 0)
			{
				testConfig.putRunTimeProperty("id", result.get("id"));
				Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.Thanos);
			}
			else
			{
				Database.executeQuery(testConfig, insertQuery, QueryType.update, DatabaseName.Thanos);
			}
		}
	}
	
	public static SearchResult performSearchForIssuesAndGetResult(Config testConfig, JiraClient jiraClient, String jqlQuery, int startPoint)
	{
		SearchResult results = null;
		try
		{
			testConfig.logComment("Executing Query :- " + jqlQuery);
			results = jiraClient.searchIssues(jqlQuery, null, 100, startPoint);
		}
		catch (JiraException e)
		{
			testConfig.logException("Unable to execute the JQL search query", e);
		}
		
		if (results == null)
		{
			testConfig.logWarning("Unable to find any matching Jira tickets, please check your JQL query!");
		}
		return results;
	}
}
