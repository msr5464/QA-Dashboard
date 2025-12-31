package thanos.helpers;

import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.time.DayOfWeek;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.time.ZoneId;
import java.time.ZonedDateTime;
import java.time.format.DateTimeFormatter;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import io.restassured.response.Response;
import thanos.utils.CommonUtilities;
import thanos.utils.Config;
import thanos.utils.Database;
import thanos.utils.Database.DatabaseName;
import thanos.utils.Database.QueryType;
import thanos.utils.JiraClient;

public class CommonJiraHelper {
	public static String startDate = LocalDate.now().minusYears(1).format(DateTimeFormatter.ofPattern("yyyy/MM/dd"));

	public enum CustomField {
		AffectedEnvironment("10077"),
		BugFoundBy("10267"),
		Platform("10350"),
		BugType("10329"),
		TechRootCause("10348"),
		BugCauseClassification("10386"),
		ProductArea("10209"),
		ProductVertical("10251"),
		TeamName("10300"),
		Sprint("10007"),
		StoryPoints("10026");

		String fieldId;

		CustomField(String fieldId) {
			this.fieldId = fieldId;
		}

	}

	public static void addJiraBugsInDatabase(Config testConfig, String prdOrFct, String createdAt, String updatedAt,
			String issueId, String teamName, String projectName, String verticalName, String priority, String status,
			String bugType, String rootCause, String category, String productArea, String bugPlatform, String title,
			int devTime, int qaTime, int overallTime, int pmTime, int developmentTime, String environment,
			String bugFoundBy, String version, int isInvalid) {
		testConfig.putRunTimeProperty("createdAt", createdAt);
		testConfig.putRunTimeProperty("updatedAt", updatedAt);
		testConfig.putRunTimeProperty("issueId", issueId);
		testConfig.putRunTimeProperty("teamName", teamName);
		testConfig.putRunTimeProperty("projectName", projectName);
		testConfig.putRunTimeProperty("verticalName", verticalName);
		testConfig.putRunTimeProperty("priority", priority);
		testConfig.putRunTimeProperty("status", status);
		testConfig.putRunTimeProperty("bugType", bugType);
		testConfig.putRunTimeProperty("rootCause", rootCause);
		testConfig.putRunTimeProperty("category", category);
		testConfig.putRunTimeProperty("productArea", productArea);
		testConfig.putRunTimeProperty("bugPlatform", bugPlatform);
		title = title.replace("\\", " ");
		title = title.replace("'", "");
		title = title.length() > 250 ? title.substring(0, 250) : title;
		testConfig.putRunTimeProperty("title", title);
		testConfig.putRunTimeProperty("environment", environment);

		String selectQuery = "SELECT id from {$tableName} where issueId = '{$issueId}';";
		String updateQuery = "";
		String insertQuery = "";
		if (prdOrFct != null && prdOrFct.equalsIgnoreCase("PRD")) {
			testConfig.putRunTimeProperty("devTime", devTime);
			testConfig.putRunTimeProperty("qaTime", qaTime);
			testConfig.putRunTimeProperty("overallTime", overallTime);
			testConfig.putRunTimeProperty("pmTime", pmTime);
			testConfig.putRunTimeProperty("developmentTime", developmentTime);
			updateQuery = "UPDATE {$tableName} SET createdAt='{$createdAt}',updatedAt='{$updatedAt}',issueId='{$issueId}',teamName='{$teamName}',projectName='{$projectName}',verticalName='{$verticalName}',priority='{$priority}',status='{$status}',bugType='{$bugType}',rootCause='{$rootCause}',category='{$category}',productArea='{$productArea}',bugPlatform='{$bugPlatform}',title='{$title}',environment='{$environment}',devTime='{$devTime}',qaTime='{$qaTime}',overallTime='{$overallTime}',pmTime='{$pmTime}',developmentTime='{$developmentTime}',isDeleted=0 WHERE id = {$id};";
			insertQuery = "INSERT INTO {$tableName} (createdAt,updatedAt,issueId,teamName,projectName,verticalName,priority,status,bugType,rootCause,category,productArea,bugPlatform,title,environment,devTime,qaTime,overallTime,pmTime,developmentTime,isDeleted) VALUES ('{$createdAt}','{$updatedAt}','{$issueId}','{$teamName}','{$projectName}','{$verticalName}','{$priority}','{$status}','{$bugType}','{$rootCause}','{$category}','{$productArea}','{$bugPlatform}','{$title}','{$environment}','{$devTime}','{$qaTime}','{$overallTime}','{$pmTime}','{$developmentTime}',0);";
		} else if (prdOrFct != null && (prdOrFct.equalsIgnoreCase("FCT") || prdOrFct.equalsIgnoreCase("STG"))) {
			testConfig.putRunTimeProperty("devTime", devTime);
			testConfig.putRunTimeProperty("qaTime", qaTime);
			testConfig.putRunTimeProperty("overallTime", overallTime);
			testConfig.putRunTimeProperty("bugFoundBy", bugFoundBy);
			testConfig.putRunTimeProperty("version", version);
			updateQuery = "UPDATE {$tableName} SET createdAt='{$createdAt}',updatedAt='{$updatedAt}',issueId='{$issueId}',teamName='{$teamName}',projectName='{$projectName}',verticalName='{$verticalName}',priority='{$priority}',status='{$status}',bugType='{$bugType}',rootCause='{$rootCause}',category='{$category}',productArea='{$productArea}',bugPlatform='{$bugPlatform}',title='{$title}',devTime='{$devTime}',qaTime='{$qaTime}',overallTime='{$overallTime}',environment='{$environment}',bugFoundBy='{$bugFoundBy}',version='{$version}',isDeleted=0 WHERE id = {$id};";
			insertQuery = "INSERT INTO {$tableName} (createdAt,updatedAt,issueId,teamName,projectName,verticalName,priority,status,bugType,rootCause,category,productArea,bugPlatform,title,devTime,qaTime,overallTime,environment,bugFoundBy,version,isDeleted) VALUES ('{$createdAt}','{$updatedAt}','{$issueId}','{$teamName}','{$projectName}','{$verticalName}','{$priority}','{$status}','{$bugType}','{$rootCause}','{$category}','{$productArea}','{$bugPlatform}','{$title}','{$devTime}','{$qaTime}','{$overallTime}','{$environment}','{$bugFoundBy}','{$version}',0);";
		} else {

			testConfig.putRunTimeProperty("bugFoundBy", bugFoundBy);
			testConfig.putRunTimeProperty("version", version);
			testConfig.putRunTimeProperty("isInvalid", isInvalid);
			updateQuery = "UPDATE {$tableName} SET createdAt='{$createdAt}',updatedAt='{$updatedAt}',issueId='{$issueId}',teamName='{$teamName}',projectName='{$projectName}',verticalName='{$verticalName}',priority='{$priority}',status='{$status}',bugType='{$bugType}',rootCause='{$rootCause}',category='{$category}',productArea='{$productArea}',bugPlatform='{$bugPlatform}',title='{$title}',environment='{$environment}',bugFoundBy='{$bugFoundBy}',version='{$version}',isInvalid={$isInvalid},isDeleted=0 WHERE id = {$id};";
			insertQuery = "INSERT INTO {$tableName} (createdAt,updatedAt,issueId,teamName,projectName,verticalName,priority,status,bugType,rootCause,category,productArea,bugPlatform,title,environment,bugFoundBy,version,isInvalid,isDeleted) VALUES ('{$createdAt}','{$updatedAt}','{$issueId}','{$teamName}','{$projectName}','{$verticalName}','{$priority}','{$status}','{$bugType}','{$rootCause}','{$category}','{$productArea}','{$bugPlatform}','{$title}','{$environment}','{$bugFoundBy}','{$version}',{$isInvalid},0);";
		}

		if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true")) {
			Map<String, String> result = Database.executeSelectQuery(testConfig, selectQuery,
					DatabaseName.QA_Dashbaord);
			if (result != null && result.size() > 0) {
				testConfig.putRunTimeProperty("id", result.get("id"));
				Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.QA_Dashbaord);
			} else {
				Database.executeQuery(testConfig, insertQuery, QueryType.update, DatabaseName.QA_Dashbaord);
			}
		}
	}

	/**
	 * Add vertical name as projectName entry in database for all bug categories
	 * (PRD, FCT, STG)
	 * This ensures vertical names appear in projectName dropdowns across all bug
	 * pages
	 */
	public static void addVerticalEntryInDatabase(Config testConfig, String verticalName) {
		String createdAt = CommonUtilities.formatDate(LocalDateTime.now().toString(), "yyyy-MM-dd'T'HH:mm:ss",
				"yyyy-MM-dd HH:mm:ss");

		// Common properties for all vertical entries
		testConfig.putRunTimeProperty("createdAt", createdAt);
		testConfig.putRunTimeProperty("updatedAt", createdAt);
		testConfig.putRunTimeProperty("teamName", "TBD");
		testConfig.putRunTimeProperty("projectName", verticalName);
		testConfig.putRunTimeProperty("verticalName", "TBD");
		testConfig.putRunTimeProperty("priority", "P3-Low");
		testConfig.putRunTimeProperty("status", "TBD");
		testConfig.putRunTimeProperty("bugType", "TBD");
		testConfig.putRunTimeProperty("rootCause", "TBD");
		testConfig.putRunTimeProperty("classification", "PaymentGateway"); // Set to PaymentGateway so entries appear in
																			// paymentgateway_jira_bugs table
		testConfig.putRunTimeProperty("productArea", "TBD");
		testConfig.putRunTimeProperty("bugPlatform", "TBD");
		testConfig.putRunTimeProperty("title", "TBD");
		testConfig.putRunTimeProperty("environment", "TBD");

		// Create entries for all three bug categories so they appear in all views
		String[] categories = { "PRD", "FCT", "STG" };
		for (String category : categories) {
			// Generate unique issueId for each category (using TBD prefix as requested)
			testConfig.putRunTimeProperty("issueId", "TBD" + CommonUtilities.generateRandomAlphaNumericString(5));
			testConfig.putRunTimeProperty("bugCategory", category);

			String selectQuery = "SELECT id from {$tableName} where projectName = '{$projectName}' AND bugCategory = '{$bugCategory}' limit 1;";
			String updateQuery = "UPDATE {$tableName} SET createdAt='{$createdAt}',updatedAt='{$updatedAt}',issueId='{$issueId}',teamName='{$teamName}',projectName='{$projectName}',verticalName='{$verticalName}',priority='{$priority}',status='{$status}',bugType='{$bugType}',rootCause='{$rootCause}',bugCategory='{$bugCategory}',classification='{$classification}',productArea='{$productArea}',bugPlatform='{$bugPlatform}',title='{$title}',environment='{$environment}' WHERE id = {$id};";
			String insertQuery = "INSERT INTO {$tableName} (createdAt,updatedAt,issueId,bugCategory,classification,teamName,projectName,verticalName,priority,status,bugType,rootCause,productArea,bugPlatform,title,environment,isDeleted) VALUES ('{$createdAt}','{$updatedAt}','{$issueId}','{$bugCategory}','{$classification}','{$teamName}','{$projectName}','{$verticalName}','{$priority}','{$status}','{$bugType}','{$rootCause}','{$productArea}','{$bugPlatform}','{$title}','{$environment}',1);";

			if (testConfig.getRunTimeProperty("EnableDatabase").equalsIgnoreCase("true")) {
				Map<String, String> result = Database.executeSelectQuery(testConfig, selectQuery,
						DatabaseName.QA_Dashbaord);
				if (result != null && result.size() > 0) {
					testConfig.putRunTimeProperty("id", result.get("id"));
					Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.QA_Dashbaord);
				} else {
					Database.executeQuery(testConfig, insertQuery, QueryType.update, DatabaseName.QA_Dashbaord);
				}
			}
		}
	}

	public static String getCustomValue(JSONObject issue, CustomField customField, String defaultValue) {
		try {
			if (issue.getJSONObject("fields").has("customfield_" + customField.fieldId)) {
				JSONObject customFieldData = issue.getJSONObject("fields")
						.getJSONObject("customfield_" + customField.fieldId);
				if (customFieldData.has("value")) {
					return customFieldData.getString("value");
				} else {
					return customFieldData.toString();
				}
			}
			return defaultValue;
		} catch (Exception e) {
			// e.printStackTrace();
			return defaultValue;
		}
	}

	/**
	 * Get numeric custom field value (like Story Points)
	 * Story Points is a direct number field, not an object
	 */
	public static String getNumericCustomValue(JSONObject issue, CustomField customField, String defaultValue) {
		try {
			String fieldName = "customfield_" + customField.fieldId;
			if (issue.getJSONObject("fields").has(fieldName)) {
				Object fieldValue = issue.getJSONObject("fields").get(fieldName);
				// Handle null values
				if (fieldValue == null || fieldValue == JSONObject.NULL) {
					return defaultValue;
				}
				// Return the numeric value as string
				return String.valueOf(fieldValue);
			}
			return defaultValue;
		} catch (Exception e) {
			// If any error, return default
			return defaultValue;
		}
	}

	public static String getMultiSelectCustomValue(JSONObject issue, CustomField customField, String defaultValue) {
		if (issue.getJSONObject("fields").has("customfield_" + customField.fieldId)) {
			try {
				JSONArray jsonArray = issue.getJSONObject("fields").getJSONArray("customfield_" + customField.fieldId);

				// Create a StringBuilder to store the values
				StringBuilder values = new StringBuilder();

				// Iterate through the JSON array and extract "value" for each object
				for (int i = 0; i < jsonArray.length(); i++) {
					JSONObject jsonObject = jsonArray.getJSONObject(i);
					String value = jsonObject.getString("value");

					// Append the value to the StringBuilder
					values.append(value);

					// If it's not the last element, add a comma and a space
					if (i < jsonArray.length() - 1) {
						values.append(", ");
					}
				}
				return values.toString();
			} catch (JSONException e) {
				// e.printStackTrace();
				return defaultValue;
			}
		}
		return defaultValue;
	}

	public static Map<String, Duration> calculateStatusTimeUsingClockHours(Config testConfig, JiraClient jiraClient,
			String issueKey, String issueCreationTime) {
		return calculateStatusTimeUsingClockHours(testConfig, jiraClient, issueKey, issueCreationTime, null, null,
				null);
	}

	public static Map<String, Duration> calculateStatusTimeUsingClockHours(Config testConfig, JiraClient jiraClient,
			String issueKey, String issueCreationTime, org.json.JSONObject changelogJson, String issueUpdatedAt,
			String currentStatus) {
		// Use changelog from bulk fetch if available, otherwise use empty histories (no
		// individual API calls)
		List<Map<String, Object>> histories;
		if (changelogJson != null) {
			// Try "changeHistories" first (bulk fetch v3 API structure: {issueId,
			// changeHistories: [...]})
			if (changelogJson.has("changeHistories")) {
				org.json.JSONArray historiesArray = changelogJson.getJSONArray("changeHistories");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < historiesArray.length(); i++) {
					org.json.JSONObject historyObj = historiesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					// Bulk fetch returns "created" as timestamp (milliseconds) or date string
					String createdValue = "";
					if (historyObj.has("created")) {
						Object createdObj = historyObj.get("created");
						if (createdObj instanceof Number) {
							// Convert timestamp (milliseconds) to ISO date string
							long timestamp = ((Number) createdObj).longValue();
							java.time.Instant instant = java.time.Instant.ofEpochMilli(timestamp);
							createdValue = instant.atZone(java.time.ZoneId.of("Asia/Singapore"))
									.format(java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss.SSSX"));
						} else {
							createdValue = historyObj.optString("created", "");
						}
					}
					history.put("created", createdValue);
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else if (changelogJson.has("histories")) {
				// Fallback: Try "histories" (alternative bulk fetch structure)
				org.json.JSONArray historiesArray = changelogJson.getJSONArray("histories");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < historiesArray.length(); i++) {
					org.json.JSONObject historyObj = historiesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					history.put("created", historyObj.optString("created", ""));
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else if (changelogJson.has("values")) {
				// Fallback: Try "values" (individual API v2 structure: {values: [...]})
				org.json.JSONArray valuesArray = changelogJson.getJSONArray("values");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < valuesArray.length(); i++) {
					org.json.JSONObject historyObj = valuesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					history.put("created", historyObj.optString("created", ""));
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else {
				// Unknown structure
				histories = new java.util.ArrayList<>();
			}
		} else {
			// No changelog available from bulk fetch - use empty histories (issue may have
			// no changelog history)
			// This avoids individual API calls since bulk fetch should have retrieved all
			// changelogs
			histories = new java.util.ArrayList<>();
		}

		// Sort histories by created date (chronological order - oldest first)
		// Bulk fetch returns histories in reverse chronological order, so we need to
		// sort them
		histories.sort((h1, h2) -> {
			String created1 = (String) h1.get("created");
			String created2 = (String) h2.get("created");
			if (created1 == null || created1.isEmpty())
				return 1;
			if (created2 == null || created2.isEmpty())
				return -1;
			try {
				LocalDateTime dt1 = CommonUtilities.parseDateTime(created1);
				LocalDateTime dt2 = CommonUtilities.parseDateTime(created2);
				return dt1.compareTo(dt2);
			} catch (Exception e) {
				return created1.compareTo(created2); // Fallback to string comparison
			}
		});

		Map<String, Duration> statusTimes = new HashMap<>();

		issueCreationTime = CommonUtilities.formatDate(issueCreationTime, "yyyy-MM-dd HH:mm:ss",
				"yyyy-MM-dd'T'HH:mm:ss.SSSX");
		LocalDateTime previousDateTime = CommonUtilities.parseDateTime(issueCreationTime);
		String previousStatus = "To Do"; // Set the initial status as "To Do"

		for (Map<String, Object> history : histories) {
			@SuppressWarnings("unchecked")
			List<Map<String, Object>> items = (List<Map<String, Object>>) history.get("items");
			String createdStr = (String) history.get("created");

			if (createdStr == null || createdStr.isEmpty()) {
				continue; // Skip entries with invalid dates
			}

			LocalDateTime currentDateTime;
			try {
				currentDateTime = CommonUtilities.parseDateTime(createdStr);
			} catch (Exception e) {
				continue; // Skip entries with unparseable dates
			}

			if (items != null) {
				for (Map<String, Object> item : items) {
					if ("status".equals(item.get("field"))) {
						String newStatus = (String) item.get("toString");
						Duration duration = Duration.between(previousDateTime, currentDateTime);
						statusTimes.merge(previousStatus, duration, Duration::plus);
						previousDateTime = currentDateTime;
						previousStatus = newStatus;
					}
				}
			}
		}

		// Calculate the time taken for the last status transition
		// For resolved bugs: Don't add time for resolved statuses - stop at the last
		// non-resolved status change
		// For open bugs: Add time from last status change to current time
		if (previousDateTime != null && previousStatus != null) {
			// Check if the last status is a resolved/closed status
			boolean lastStatusIsResolved = previousStatus != null &&
					(previousStatus.toLowerCase().contains("resolved") ||
							previousStatus.toLowerCase().contains("closed") ||
							previousStatus.toLowerCase().contains("done"));

			// Check if the bug is currently resolved/closed
			boolean isCurrentlyResolved = currentStatus != null &&
					(currentStatus.toLowerCase().contains("resolved") ||
							currentStatus.toLowerCase().contains("closed") ||
							currentStatus.toLowerCase().contains("done"));

			if (lastStatusIsResolved || isCurrentlyResolved) {
				// For resolved bugs, don't add time for resolved statuses
				// The timing stops at the last non-resolved status change (which was already
				// processed)
			} else {
				// For open bugs, add time from last status change to current time
				LocalDateTime endDateTime = ZonedDateTime.now(ZoneId.of("Asia/Singapore")).toLocalDateTime();
				Duration duration = Duration.between(previousDateTime, endDateTime);
				statusTimes.merge(previousStatus, duration, Duration::plus);
			}
		}
		return statusTimes;
	}

	public static Map<String, Duration> calculateStatusTimeUsingWorkingHours(Config testConfig, JiraClient jiraClient,
			String issueKey, String issueCreationTime, boolean useWorkingDays) {
		return calculateStatusTimeUsingWorkingHours(testConfig, jiraClient, issueKey, issueCreationTime, useWorkingDays,
				null, null, null);
	}

	public static Map<String, Duration> calculateStatusTimeUsingWorkingHours(Config testConfig, JiraClient jiraClient,
			String issueKey, String issueCreationTime, boolean useWorkingDays, org.json.JSONObject changelogJson,
			String issueUpdatedAt, String currentStatus) {
		// Use changelog from bulk fetch if available, otherwise use empty histories (no
		// individual API calls)
		List<Map<String, Object>> histories;
		if (changelogJson != null) {
			// Try "changeHistories" first (bulk fetch v3 API structure: {issueId,
			// changeHistories: [...]})
			if (changelogJson.has("changeHistories")) {
				org.json.JSONArray historiesArray = changelogJson.getJSONArray("changeHistories");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < historiesArray.length(); i++) {
					org.json.JSONObject historyObj = historiesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					// Bulk fetch returns "created" as timestamp (milliseconds) or date string
					String createdValue = "";
					if (historyObj.has("created")) {
						Object createdObj = historyObj.get("created");
						if (createdObj instanceof Number) {
							// Convert timestamp (milliseconds) to ISO date string
							long timestamp = ((Number) createdObj).longValue();
							java.time.Instant instant = java.time.Instant.ofEpochMilli(timestamp);
							createdValue = instant.atZone(java.time.ZoneId.of("Asia/Singapore"))
									.format(java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss.SSSX"));
						} else {
							createdValue = historyObj.optString("created", "");
						}
					}
					history.put("created", createdValue);
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else if (changelogJson.has("histories")) {
				// Fallback: Try "histories" (alternative bulk fetch structure)
				org.json.JSONArray historiesArray = changelogJson.getJSONArray("histories");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < historiesArray.length(); i++) {
					org.json.JSONObject historyObj = historiesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					history.put("created", historyObj.optString("created", ""));
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else if (changelogJson.has("values")) {
				// Fallback: Try "values" (individual API v2 structure: {values: [...]})
				org.json.JSONArray valuesArray = changelogJson.getJSONArray("values");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < valuesArray.length(); i++) {
					org.json.JSONObject historyObj = valuesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					history.put("created", historyObj.optString("created", ""));
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else {
				// Unknown structure
				histories = new java.util.ArrayList<>();
			}
		} else {
			// No changelog available from bulk fetch - use empty histories (issue may have
			// no changelog history)
			// This avoids individual API calls since bulk fetch should have retrieved all
			// changelogs
			histories = new java.util.ArrayList<>();
		}

		// Sort histories by created date (chronological order - oldest first)
		// Bulk fetch returns histories in reverse chronological order, so we need to
		// sort them
		histories.sort((h1, h2) -> {
			String created1 = (String) h1.get("created");
			String created2 = (String) h2.get("created");
			if (created1 == null || created1.isEmpty())
				return 1;
			if (created2 == null || created2.isEmpty())
				return -1;
			try {
				LocalDateTime dt1 = CommonUtilities.parseDateTime(created1);
				LocalDateTime dt2 = CommonUtilities.parseDateTime(created2);
				return dt1.compareTo(dt2);
			} catch (Exception e) {
				return created1.compareTo(created2); // Fallback to string comparison
			}
		});

		Map<String, Duration> statusTimes = new HashMap<>();

		issueCreationTime = CommonUtilities.formatDate(issueCreationTime, "yyyy-MM-dd HH:mm:ss",
				"yyyy-MM-dd'T'HH:mm:ss.SSSX");
		LocalDateTime previousDateTime = CommonUtilities.parseDateTime(issueCreationTime);
		String previousStatus = "To Do"; // Set the initial status as "To Do"

		for (Map<String, Object> history : histories) {
			@SuppressWarnings("unchecked")
			List<Map<String, Object>> items = (List<Map<String, Object>>) history.get("items");
			LocalDateTime currentDateTime = CommonUtilities.parseDateTime((String) history.get("created"));

			for (Map<String, Object> item : items) {
				if ("status".equals(item.get("field"))) {
					String newStatus = (String) item.get("toString");
					Duration duration = calculateDurationWithinWorkingHours(previousDateTime, currentDateTime,
							useWorkingDays);
					statusTimes.merge(previousStatus, duration, Duration::plus);
					previousDateTime = currentDateTime;
					previousStatus = newStatus;
				}
			}
		}

		// Calculate the time taken for the last status transition
		// For resolved bugs: Don't add time for resolved statuses - stop at the last
		// non-resolved status change
		// For open bugs: Add time from last status change to current time
		if (previousDateTime != null && previousStatus != null) {
			// Check if the last status is a resolved/closed status
			boolean lastStatusIsResolved = previousStatus != null &&
					(previousStatus.toLowerCase().contains("resolved") ||
							previousStatus.toLowerCase().contains("closed") ||
							previousStatus.toLowerCase().contains("done"));

			// Check if the bug is currently resolved/closed
			boolean isCurrentlyResolved = currentStatus != null &&
					(currentStatus.toLowerCase().contains("resolved") ||
							currentStatus.toLowerCase().contains("closed") ||
							currentStatus.toLowerCase().contains("done"));

			if (lastStatusIsResolved || isCurrentlyResolved) {
				// For resolved bugs, don't add time for resolved statuses
				// The timing stops at the last non-resolved status change (which was already
				// processed)
			} else {
				// For open bugs, add time from last status change to current time
				LocalDateTime endDateTime = ZonedDateTime.now(ZoneId.of("Asia/Singapore")).toLocalDateTime();
				Duration duration = calculateDurationWithinWorkingHours(previousDateTime, endDateTime, useWorkingDays);
				statusTimes.merge(previousStatus, duration, Duration::plus);
			}
		}
		return statusTimes;
	}

	private static Duration calculateDurationWithinWorkingHours(LocalDateTime startDateTime, LocalDateTime endDateTime,
			boolean useWorkingDays) {
		LocalDateTime currentDateTime = startDateTime;
		Duration totalDuration = Duration.ZERO;

		while (currentDateTime.isBefore(endDateTime)) {
			// Check if the currentDateTime is within working hours (9 AM to 6 PM)
			if (isWithinWorkingHours(currentDateTime, useWorkingDays)) {
				LocalDateTime nextDateTime = currentDateTime.plusMinutes(1);
				if (nextDateTime.isAfter(endDateTime)) {
					nextDateTime = endDateTime;
				}
				totalDuration = totalDuration.plus(Duration.between(currentDateTime, nextDateTime));
			}

			currentDateTime = currentDateTime.plusMinutes(1);
		}
		return totalDuration;
	}

	private static boolean isWithinWorkingHours(LocalDateTime dateTime, boolean useWorkingDays) {
		LocalTime startTime = LocalTime.of(9, 0);
		LocalTime endTime = LocalTime.of(18, 0);
		if (useWorkingDays) {
			startTime = LocalTime.of(0, 1);
			endTime = LocalTime.of(23, 59);
		}

		LocalTime currentTime = dateTime.toLocalTime();
		return !dateTime.toLocalDate().getDayOfWeek().equals(DayOfWeek.SATURDAY)
				&& !dateTime.toLocalDate().getDayOfWeek().equals(DayOfWeek.SUNDAY)
				&& (currentTime.isAfter(startTime) || currentTime.equals(startTime))
				&& (currentTime.isBefore(endTime) || currentTime.equals(endTime));
	}

	/**
	 * Get time when ticket moved to a specific status from bulk-fetched changelog
	 * This avoids individual API calls
	 */
	public static String getTimeWhenTicketMovedToStatusFromChangelog(Config testConfig,
			org.json.JSONObject changelogJson, String statusToTrack) {
		List<Map<String, Object>> histories;
		if (changelogJson != null) {
			// Try "changeHistories" first (bulk fetch v3 API structure)
			if (changelogJson.has("changeHistories")) {
				org.json.JSONArray historiesArray = changelogJson.getJSONArray("changeHistories");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < historiesArray.length(); i++) {
					org.json.JSONObject historyObj = historiesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					// Bulk fetch returns "created" as timestamp (milliseconds) or date string
					String createdValue = "";
					if (historyObj.has("created")) {
						Object createdObj = historyObj.get("created");
						if (createdObj instanceof Number) {
							long timestamp = ((Number) createdObj).longValue();
							java.time.Instant instant = java.time.Instant.ofEpochMilli(timestamp);
							createdValue = instant.atZone(java.time.ZoneId.of("Asia/Singapore"))
									.format(java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss.SSSX"));
						} else {
							createdValue = historyObj.optString("created", "");
						}
					}
					history.put("created", createdValue);
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else if (changelogJson.has("histories")) {
				// Fallback: Try "histories"
				org.json.JSONArray historiesArray = changelogJson.getJSONArray("histories");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < historiesArray.length(); i++) {
					org.json.JSONObject historyObj = historiesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					history.put("created", historyObj.optString("created", ""));
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else if (changelogJson.has("values")) {
				// Fallback: Try "values" (individual API v2 structure)
				org.json.JSONArray valuesArray = changelogJson.getJSONArray("values");
				histories = new java.util.ArrayList<>();
				for (int i = 0; i < valuesArray.length(); i++) {
					org.json.JSONObject historyObj = valuesArray.getJSONObject(i);
					Map<String, Object> history = new java.util.HashMap<>();
					history.put("created", historyObj.optString("created", ""));
					org.json.JSONArray itemsArray = historyObj.optJSONArray("items");
					if (itemsArray != null) {
						List<Map<String, Object>> items = new java.util.ArrayList<>();
						for (int j = 0; j < itemsArray.length(); j++) {
							org.json.JSONObject itemObj = itemsArray.getJSONObject(j);
							Map<String, Object> item = new java.util.HashMap<>();
							item.put("field", itemObj.optString("field", ""));
							item.put("toString", itemObj.optString("toString", ""));
							items.add(item);
						}
						history.put("items", items);
					}
					histories.add(history);
				}
			} else {
				histories = new java.util.ArrayList<>();
			}
		} else {
			histories = new java.util.ArrayList<>();
		}

		LocalDateTime targetDateTime = null;
		DateTimeFormatter formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

		for (Map<String, Object> history : histories) {
			@SuppressWarnings("unchecked")
			List<Map<String, Object>> items = (List<Map<String, Object>>) history.get("items");
			if (items == null)
				continue;
			String createdStr = (String) history.get("created");
			if (createdStr == null || createdStr.isEmpty()) {
				continue;
			}
			LocalDateTime currentDateTime = CommonUtilities.parseDateTime(createdStr);

			for (Map<String, Object> item : items) {
				if ("status".equals(item.get("field"))) {
					String currentStatus = (String) item.get("toString");

					if (statusToTrack.equals(currentStatus)) {
						targetDateTime = currentDateTime;
						break; // Exit the loop once target status is found
					}
				}
			}

			if (targetDateTime != null) {
				break; // Exit the outer loop as well
			}
		}

		if (targetDateTime != null) {
			return targetDateTime.format(formatter);
		} else {
			return null;
		}
	}

	@SuppressWarnings("unchecked")
	public static String getTimeWhenTicketMovedToStatus(Config testConfig, JiraClient jiraClient, String issueKey,
			String statusToTrack) {
		Response responses = jiraClient.getIssueHistory(issueKey);
		List<Map<String, Object>> histories = responses.jsonPath().getList("values");

		LocalDateTime readyForQaDateTime = null;
		DateTimeFormatter formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

		for (Map<String, Object> history : histories) {
			@SuppressWarnings("unchecked")
			List<Map<String, Object>> items = (List<Map<String, Object>>) history.get("items");
			String createdStr = (String) history.get("created");
			if (createdStr == null || createdStr.isEmpty()) {
				continue;
			}
			LocalDateTime currentDateTime = CommonUtilities.parseDateTime(createdStr);

			for (Map<String, Object> item : items) {
				if ("status".equals(item.get("field"))) {
					String currentStatus = (String) item.get("toString");

					if ("Ready for QA".equals(currentStatus)) {
						readyForQaDateTime = currentDateTime;
						break; // Exit the loop once "Ready for QA" is found
					}
				}
			}

			if (readyForQaDateTime != null) {
				break; // Exit the outer loop as well
			}
		}

		if (readyForQaDateTime != null) {
			return readyForQaDateTime.format(formatter);
		} else {
			return null;
		}
	}

	public static void checkForDeletedTickets(Config testConfig, String tableName, String verticalName,
			HashMap<String, String> listOfKeys) {
		testConfig.putRunTimeProperty("sqlTableName", tableName);
		String selectQuery = "Select issueId FROM {$sqlTableName} WHERE verticalName='" + verticalName
				+ "' AND DATE(createdAt)>=DATE('" + startDate + "');";
		ResultSet resultSet = (ResultSet) Database.executeQuery(testConfig, selectQuery, QueryType.select,
				DatabaseName.QA_Dashbaord);

		try {
			while (resultSet != null && resultSet.next()) {
				ResultSetMetaData meta = resultSet.getMetaData();
				for (int col = 1; col <= meta.getColumnCount(); col++) {
					String issueId = resultSet.getObject(col).toString();
					if (listOfKeys.get(issueId) == null) {
						String updateQuery = "update {$sqlTableName} set isDeleted=1 WHERE issueId = '" + issueId
								+ "';";
						Database.executeQuery(testConfig, updateQuery, QueryType.update, DatabaseName.QA_Dashbaord);
					}

				}
			}
		} catch (SQLException e) {
			e.printStackTrace();
		}
	}
}