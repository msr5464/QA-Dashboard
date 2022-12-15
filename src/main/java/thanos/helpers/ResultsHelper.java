package thanos.helpers;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.io.Writer;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.TimeZone;
import org.apache.commons.lang.StringUtils;
import org.joda.time.LocalDate;
import org.joda.time.LocalTime;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import com.opencsv.CSVWriter;
import thanos.utils.CommonUtilities;
import thanos.utils.Config;
import thanos.utils.Database;
import thanos.utils.Database.DatabaseName;
import thanos.utils.Database.QueryType;
import thanos.utils.GcpHelper;

public class ResultsHelper
{
	public String bucketName = "qa-thanos-results";
	private static String csvFileName = "";
	private static CSVWriter csvWriter;
	private static Writer writer = null;
	private boolean executedOnce = false;
	
	public enum FileType
	{
		UnitTestCoverage,
		AutomationResults
	}
	
	Map<String, Integer> entityLevelData = null;
	
	@SuppressWarnings("serial")
	public ResultsHelper()
	{
		entityLevelData = new HashMap<String, Integer>()
		{
			{
				put("duration", 0);
				put("percentage", 0);
				put("totalCases", 0);
				put("passedCases", 0);
				put("failedCases", 0);
			}
		};
		
	}
	
	public String getFilePath(Config testConfig)
	{
		String localFilePath = null;
		testConfig.logCommentForDebugging("User directory: " + System.getProperty("user.dir"));
		if (new File(System.getProperty("user.dir") + File.separator + "pom.xml").exists())
		{
			localFilePath = System.getProperty("user.dir") + File.separator + "target" + File.separator;
		}
		else
		{
			localFilePath = System.getProperty("user.dir") + File.separator + "build" + File.separator;
		}
		return localFilePath;
	}
	
	private String getTableName(Config testConfig, String entityName, FileType type)
	{
		String mysqlTableName = "";
		switch (type)
		{
		case AutomationResults:
			mysqlTableName = entityName.toLowerCase().trim().replaceAll(" ", "_") + "_results";
			break;
		case UnitTestCoverage:
			mysqlTableName = entityName.toLowerCase().trim().replaceAll(" ", "_") + "_units";
			break;
		}
		testConfig.putRunTimeProperty("tableName", mysqlTableName);
		return mysqlTableName;
	}
	
	public List<String> readCsvFileAndInsertToDB(Config testConfig, String entityName, List<String> downloadFileNames, FileType type)
	{
		String insertQuery = "";
		boolean isDatabaseEnabled = testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true") ? true : false;
		String mysqlTableName = getTableName(testConfig, entityName, type);
		String automationResultsColumns = "createdAt,projectName,environment,groupName,duration,percentage,totalCases,passedCases,failedCases,buildTag,resultLink";
		String unitTestCoverageColumns = "createdAt,projectName,statementsTotalCount,statementsCurrentCount,statementsPercentage,branchesTotalCount,branchesCurrentCount,branchesPercentage,functionsTotalCount,functionsCurrentCount,functionsPercentage,linesTotalCount,linesCurrentCount,linesPercentage,buildTag,resultLink";
		switch (type)
		{
		case AutomationResults:
			insertQuery = "INSERT INTO " + mysqlTableName + " (" + automationResultsColumns + ") VALUES(?,?,?,?,?,?,?,?,?,?,?) ";
			break;
		case UnitTestCoverage:
			insertQuery = "INSERT INTO " + mysqlTableName + " (" + unitTestCoverageColumns + ") VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ";
			break;
		}
		String line = "";
		int count = 1;
		HashMap<String, String> csvCompleteData = new HashMap<>();
		String[] csvData;
		String[] csvHeaders = new String[0];
		String[] dbColumns;
		BufferedReader csvFile;
		int[] recordsUpdatedInDB;
		List<String> processedFiles = new ArrayList<String>();
		Connection conn = null;
		if (isDatabaseEnabled)
			conn = (Connection) Database.getConnection(testConfig, DatabaseName.Thanos);
		
		for (String fileName : downloadFileNames)
		{
			try
			{
				String localFilePath = getFilePath(testConfig);
				testConfig.logComment("Processing file.. " + (fileName));
				
				String gitlabJobId = fileName.split("_")[fileName.split("_").length - 1].replace(".csv", "");
				if (StringUtils.isEmpty(gitlabJobId))
					testConfig.logWarning("Gitlab CI Job Id not found, so skipping file - " + (fileName));
				else
				{
					count = 1;
					csvFile = new BufferedReader(new FileReader(localFilePath + fileName));
					
					PreparedStatement pstmt = null;
					if (isDatabaseEnabled)
						pstmt = conn.prepareStatement(insertQuery);
					
					while ((line = csvFile.readLine()) != null)
					{
						
						if (count == 1)
						{
							csvHeaders = line.split(",");
							testConfig.logComment("File headers: " + line);
							count++;
						}
						else
						{
							count++;
							csvData = line.split(",");
							testConfig.logComment("Csv data: " + line);
							for (int i = 0; i < csvHeaders.length; i++)
							{
								csvCompleteData.put(csvHeaders[i], csvData[i]);
							}
							
							if (isDatabaseEnabled)
							{
								switch (type)
								{
								case AutomationResults:
									dbColumns = automationResultsColumns.split(",");
									pstmt.setString(1, csvCompleteData.get(dbColumns[0]));// createdAt
									pstmt.setString(2, csvCompleteData.get(dbColumns[1]));// project
									pstmt.setString(3, csvCompleteData.get(dbColumns[2]).toLowerCase()); // environment
									pstmt.setString(4, csvCompleteData.get(dbColumns[3]).toLowerCase());// groupName
									if (csvCompleteData.get(dbColumns[4]).contains(":"))
										pstmt.setString(5, String.valueOf(java.time.Duration.between(java.time.LocalTime.MIN, java.time.LocalTime.parse(csvCompleteData.get(dbColumns[4]))).getSeconds()));// duration in seconds
									else
										pstmt.setString(5, csvCompleteData.get(dbColumns[4]));
									pstmt.setInt(6, (int) Double.parseDouble(csvCompleteData.get(dbColumns[5])));// Percentage
									pstmt.setInt(7, Integer.parseInt(csvCompleteData.get(dbColumns[6]))); // total cases
									pstmt.setInt(8, Integer.parseInt(csvCompleteData.get(dbColumns[7]))); // Passed cases
									pstmt.setInt(9, Integer.parseInt(csvCompleteData.get(dbColumns[8]))); // Failed Cases
									pstmt.setString(10, csvCompleteData.get(dbColumns[9]));// buildTag
									pstmt.setString(11, csvCompleteData.get(dbColumns[10]));// resultLink
									break;
								case UnitTestCoverage:
									dbColumns = unitTestCoverageColumns.split(",");
									pstmt.setString(1, csvCompleteData.get(dbColumns[0]));// createdAt
									pstmt.setString(2, csvCompleteData.get(dbColumns[1]));// projectName
									pstmt.setInt(3, Integer.parseInt(csvCompleteData.get(dbColumns[2])));// statementsTotalCount
									pstmt.setInt(4, Integer.parseInt(csvCompleteData.get(dbColumns[3])));// statementCurrentCount
									pstmt.setFloat(5, Float.parseFloat(csvCompleteData.get(dbColumns[4])));// statementsPercentage
									pstmt.setInt(6, Integer.parseInt(csvCompleteData.get(dbColumns[5])));// branchesTotalCount
									pstmt.setInt(7, Integer.parseInt(csvCompleteData.get(dbColumns[6])));// branchesCurrentCount
									pstmt.setFloat(8, Float.parseFloat(csvCompleteData.get(dbColumns[7]))); // branchesPercentage
									pstmt.setInt(9, Integer.parseInt(csvCompleteData.get(dbColumns[8]))); // functionsTotalCount
									pstmt.setInt(10, Integer.parseInt(csvCompleteData.get(dbColumns[9]))); // functionsCurrentCount
									pstmt.setFloat(11, Float.parseFloat(csvCompleteData.get(dbColumns[10])));// functionsPercentage
									pstmt.setInt(12, Integer.parseInt(csvCompleteData.get(dbColumns[11])));// linesTotalCount
									pstmt.setInt(13, Integer.parseInt(csvCompleteData.get(dbColumns[12])));// linesCurrentCount
									pstmt.setFloat(14, Float.parseFloat(csvCompleteData.get(dbColumns[13])));// linesPercentage
									pstmt.setString(15, fileName.substring(fileName.indexOf("UnitTest") + 9, fileName.indexOf("csv") - 1));// buildTag
									pstmt.setString(16, csvCompleteData.get(dbColumns[15]));// resultLink
									break;
								}
								pstmt.addBatch();
							}
						}
					}
					if (isDatabaseEnabled)
					{
						recordsUpdatedInDB = pstmt.executeBatch();
						processedFiles.add(fileName);
						CommonUtilities.compareEquals(testConfig, " Data rows in file and records updated in DB", count - 2, recordsUpdatedInDB.length);
					}
					testConfig.logComment("File Processed: " + fileName);
				}
			}
			catch (Exception e)
			{
				testConfig.logExceptionAndFail("Some Error occured", e);
			}
		}
		if (isDatabaseEnabled)
			CommonUtilities.compareEquals(testConfig, "Count of files processed", downloadFileNames.size(), processedFiles.size());
		else
			testConfig.logFail("Database not enabled, so none of the files fully processed!");
		return processedFiles;
	}
	
	public void fetchAndUpdateResultsData(Config testConfig, String entityName, JSONObject jsonObject, ArrayList<String> environmentAndGroupNamePairs, LocalDate date)
	{
		for (int i = 0; i < environmentAndGroupNamePairs.size(); i++)
		{
			Iterator<String> automationConfigKeys = jsonObject.keys();
			while (automationConfigKeys.hasNext())
			{
				String automationConfigKey = automationConfigKeys.next();
				if (automationConfigKey.equals("automationSuites"))
				{
					calculateDataForAPod(testConfig, entityName, jsonObject.getJSONArray("automationSuites"), environmentAndGroupNamePairs.get(i), date);
				}
				else
				{
					if (jsonObject.get(automationConfigKey) instanceof org.json.JSONObject)
					{
						String podName = automationConfigKey;
						JSONObject innerJsonObject = jsonObject.getJSONObject(automationConfigKey);
						calculateDataForAPod(testConfig, entityName, innerJsonObject.getJSONArray("automationSuites"), environmentAndGroupNamePairs.get(i), date);
						
						if (testConfig.getRunTimeProperty("dataNotFound").equalsIgnoreCase("false"))
							updateDataInDatabase(testConfig, podName, environmentAndGroupNamePairs.get(i), date);
					}
					else
					{
						testConfig.logFail("Invalid AutomationConfig file format");
					}
				}
			}
			
			// At the end, insert data of Entity to All Entities table
			if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true") && entityLevelData.get("duration") > 0)
			{
				testConfig.putRunTimeProperty("tableName", "all_entities_results");
				testConfig.putRunTimeProperty("duration", entityLevelData.get("duration"));
				testConfig.putRunTimeProperty("percentage", entityLevelData.get("percentage"));
				testConfig.putRunTimeProperty("totalCases", entityLevelData.get("totalCases"));
				testConfig.putRunTimeProperty("passedCases", entityLevelData.get("passedCases"));
				testConfig.putRunTimeProperty("failedCases", entityLevelData.get("failedCases"));
				updateDataInDatabase(testConfig, entityName, environmentAndGroupNamePairs.get(i), date);
				entityLevelData.replaceAll((key, value) -> 0);
			}
			executedOnce = true;
		}
	}
	
	private void calculateDataForAPod(Config testConfig, String entityName, JSONArray jsonArray, String environmentAndGroupNamePair, LocalDate date)
	{
		String projectNames = "";
		String newProjectName = "";
		String platformName = "";
		getTableName(testConfig, entityName, FileType.AutomationResults);
		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true"))
		{
			for (int counter = 0; counter < jsonArray.length(); counter++)
			{
				String projectName = jsonArray.getJSONObject(counter).getString("projectName");
				try
				{
					platformName = jsonArray.getJSONObject(counter).getString("platformName");
				}
				catch (JSONException e)
				{
					platformName = "";
				}
				if (StringUtils.isEmpty(platformName))
				{
					newProjectName = projectName;
				}
				else
				{
					newProjectName = platformName + " - " + projectName;
					if (!executedOnce)
						appendPlatformInProjectName(testConfig, projectName, newProjectName);
				}
				
				if (StringUtils.isEmpty(projectNames))
					projectNames = "'" + newProjectName + "'";
				else
					projectNames = projectNames + "," + "'" + newProjectName + "'";
			}
			aggregateData(testConfig, projectNames, environmentAndGroupNamePair, date);
		}
	}
	
	private void aggregateData(Config testConfig, String projectNames, String environmentAndGroupNamePair, LocalDate date)
	{
		int duration = 0;
		int percentage = 0;
		int totalCases = 0;
		int passedCases = 0;
		int failedCases = 0;
		testConfig.putRunTimeProperty("projectNames", projectNames);
		testConfig.putRunTimeProperty("environment", environmentAndGroupNamePair.split(",")[0]);
		testConfig.putRunTimeProperty("groupName", environmentAndGroupNamePair.split(",")[1]);
		Date formatedDate = date.toLocalDateTime(LocalTime.now()).toDate(TimeZone.getDefault());
		String createdAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(formatedDate);
		testConfig.putRunTimeProperty("createdAt", createdAt);
		
		String selectQuery = "select round(SUM(duration),0) as duration, Floor(sum(passedCases)*100/sum(totalCases)) as percentage, Floor(sum(totalCases)) as totalCases, Floor(sum(passedCases)) as passedCases, Floor(sum(failedCases)) as failedCases from (select id, AVG(duration) as duration, AVG(totalCases) as totalCases, AVG(passedCases) as passedCases, AVG(failedCases) as failedCases from {$tableName} where projectName in ({$projectNames}) AND DATE(createdAt)= DATE('{$createdAt}') AND environment='{$environment}' AND groupName='{$groupName}' group by projectName) as x";
		Map<String, String> result = Database.executeSelectQuery(testConfig, selectQuery, DatabaseName.Thanos);
		if (result != null && result.size() > 0 && !result.get("totalCases").equalsIgnoreCase(""))
		{
			duration = Integer.parseInt(result.get("duration").replace(".0", ""));
			percentage = Integer.parseInt(result.get("percentage"));
			totalCases = Integer.parseInt(result.get("totalCases"));
			passedCases = Integer.parseInt(result.get("passedCases"));
			failedCases = Integer.parseInt(result.get("failedCases"));
			
			testConfig.putRunTimeProperty("duration", duration);
			testConfig.putRunTimeProperty("percentage", percentage);
			testConfig.putRunTimeProperty("totalCases", totalCases);
			testConfig.putRunTimeProperty("passedCases", passedCases);
			testConfig.putRunTimeProperty("failedCases", failedCases);
			
			duration = entityLevelData.get("duration") + duration;
			totalCases = entityLevelData.get("totalCases") + totalCases;
			passedCases = entityLevelData.get("passedCases") + passedCases;
			failedCases = entityLevelData.get("failedCases") + failedCases;
			percentage = Math.round(passedCases * 100 / totalCases);
			entityLevelData.put("duration", duration);
			entityLevelData.put("percentage", percentage);
			entityLevelData.put("totalCases", totalCases);
			entityLevelData.put("passedCases", passedCases);
			entityLevelData.put("failedCases", failedCases);
			testConfig.putRunTimeProperty("dataNotFound", "false");
		}
		else
		{
			testConfig.putRunTimeProperty("dataNotFound", "true");
		}
	}
	
	private void updateDataInDatabase(Config testConfig, String projectName, String environmentAndGroupNamePair, LocalDate date)
	{
		testConfig.putRunTimeProperty("projectName", projectName);
		testConfig.putRunTimeProperty("environment", environmentAndGroupNamePair.split(",")[0]);
		testConfig.putRunTimeProperty("groupName", environmentAndGroupNamePair.split(",")[1]);
		Date formatedDate = date.toLocalDateTime(LocalTime.now()).toDate(TimeZone.getDefault());
		String createdAt = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(formatedDate);
		testConfig.putRunTimeProperty("createdAt", createdAt);
		
		String selectQuery = "select id from {$tableName} where projectName = '{$projectName}' AND DATE(createdAt)= DATE('{$createdAt}') AND environment='{$environment}' AND groupName='{$groupName}' ORDER BY id DESC LIMIT 1;";
		String updateQuery = "update {$tableName} set createdAt='{$createdAt}',duration='{$duration}',percentage='{$percentage}',totalCases='{$totalCases}',passedCases='{$passedCases}',failedCases='{$failedCases}' WHERE id = {$id};";
		String insertQuery = "insert into {$tableName} (createdAt,projectName,environment,groupName,duration,percentage,totalCases,passedCases,failedCases) values ('{$createdAt}','{$projectName}','{$environment}','{$groupName}','{$duration}','{$percentage}','{$totalCases}','{$passedCases}','{$failedCases}');";
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
	
	private void appendPlatformInProjectName(Config testConfig, String projectName, String newProjectName)
	{
		testConfig.putRunTimeProperty("projectName", projectName);
		testConfig.putRunTimeProperty("newProjectName", newProjectName);
		String updateQuery = "update {$tableName} set projectName='{$newProjectName}' where projectName='{$projectName}';";
		Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.Thanos);
	}
	
	/**
	 * Create Csv file and upload to GCP Bucket
	 * @param entityName Entity Name | Eg: Togoto / Merchant / Loan
	 * @param createdAt Timestamp in format {YYYY-MM-DD HH:MM:SS} | Eg: 2021-05-05 16:30:41
	 * @param projectName Project Name | Eg: Portal
	 * @param environment Environment Name | Eg: Staging, Production
	 * @param groupName Group Name | Eg: smoke, regression
	 * @param duration Total Execution time in seconds - 123
	 * @param percentage Total Percent | Eg: 16.30
	 * @param totalCases Total Test Cases
	 * @param passedCases Total Test Cases with Status as PASS
	 * @param failedCases Total Test Cases with Status as FAIL
	 * @param buildTag Runtime unique number | build tag | Eg: For Gitlab-
	 *        CI_JOB_ID
	 * @param resultLink Test Execution Report Link
	 */
	public void createAutomationResultsCsvAndUploadToGcpBucket(Config testConfig, String gcpBucketAuthKeyLocation, String entityName, String createdAt, String projectName, String environment, String groupName, String duration, String percentage, String totalCases, String passedCases, String failedCases, String buildTag, String resultLink)
	{
		csvFileName = entityName + "_TestResults" + "_" + buildTag + ".csv";
		String localFilePath = getFilePath(testConfig);
		CommonUtilities.createFolder(localFilePath);
		try
		{
			writer = Files.newBufferedWriter(Paths.get(localFilePath + csvFileName));
		}
		catch (IOException e)
		{
			e.printStackTrace();
		}
		csvWriter = new CSVWriter(writer, CSVWriter.DEFAULT_SEPARATOR, CSVWriter.NO_QUOTE_CHARACTER, CSVWriter.DEFAULT_ESCAPE_CHARACTER, CSVWriter.DEFAULT_LINE_END);
		csvWriter.writeNext(new String[] { "createdAt", "projectName", "environment", "groupName", "duration", "percentage", "totalCases", "passedCases", "failedCases", "buildTag", "resultLink" });
		csvWriter.writeNext(new String[] { createdAt, projectName, environment, groupName, duration, percentage, totalCases, passedCases, failedCases, buildTag, resultLink });
		
		try
		{
			writer.close();
		}
		catch (IOException e)
		{
			e.printStackTrace();
		}
		GcpHelper.uploadFileInGcpBucket(testConfig, gcpBucketAuthKeyLocation, bucketName, getFilePath(testConfig) + csvFileName, csvFileName);
	}
}
