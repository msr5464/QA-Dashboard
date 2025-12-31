package thanos.helpers;

import java.text.SimpleDateFormat;
import java.time.Duration;
import java.time.LocalDateTime;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

import org.apache.commons.lang3.StringUtils;
import org.joda.time.LocalDate;
import org.json.JSONArray;
import org.json.JSONObject;

import thanos.helpers.CommonJiraHelper.CustomField;
import thanos.models.BugData;
import thanos.models.BugData.BugCategory;
import thanos.models.BugData.Classification;
import thanos.utils.CommonUtilities;
import thanos.utils.Config;
import thanos.utils.Database;
import thanos.utils.Database.DatabaseName;
import thanos.utils.Database.QueryType;
import thanos.utils.JiraClient;

/**
 * Unified Bug and Metrics Helper
 * Handles both tickets (paymentgateway_jira_tickets) and bugs (paymentgateway_jira_bugs)
 * Consolidates logic from old ProdBugsHelper, FctBugsHelper, StagingBugsHelper
 */
public class BugMetricsHelper {
	private JiraClient jiraClient = null;
	private HashMap<String, String> listOfTickets = new HashMap<String, String>();
	private HashMap<String, String> listOfBugs = new HashMap<String, String>();
	private String[] invalidStatuses = { "Invalid", "Not a Bug", "Duplicate Bug", "Duplicate" };
	private String[] ticketTestedCreatedAtStatuses = { "Merged to Develop", "Sanity in progress", "Sanity Completed", "Deployed to Production", "Done" };
	
	// Status definitions for timing calculations
	private String devStatuses = "To Do;Open;Reopened;Blocked;Dev In Progress;Dev testing;Under Code Review;Ready to merge;Merged to Develop;Sanity In Progress;In Review;QA DONE;Development In Progress;Ready for Development;Ready for QA;QA in Progress;QA Testing;QA Testing in Progress;";
	private String qaStatuses = "";
	private String prdQaTimeStatuses = "To Do;Bug PIC Review;Reopened;";
	private String prdPmTimeStatuses = "Pending PM;";
	private String prdDevTimeStatuses = "Ready for Development;Dev in Progress;Dev Testing;";
	private String prdDevlopmentTimeStatuses = prdQaTimeStatuses + prdDevTimeStatuses + prdPmTimeStatuses;
	private String prdTotalTimeStatuses = prdDevlopmentTimeStatuses + "Deployment Ready;";
	
	public BugMetricsHelper(Config testConfig) {
		jiraClient = new JiraClient(testConfig);
	}

	public void fetchDataForProjectsAndVerticals(Config testConfig, JSONObject jsonObject, String entityName,
			LocalDate date, HashMap<String, String> jiraFilters, boolean isTickets) {
		Iterator<String> jiraConfigKeys = jsonObject.keys();

		while (jiraConfigKeys.hasNext()) {
			String jiraConfigKey = jiraConfigKeys.next();

			if (jiraConfigKey.equals("jiraProjects")) {
				fetchProjectWiseDataAndInsertIntoDb(testConfig, entityName, jsonObject.getJSONArray("jiraProjects"),
						date, jiraFilters, isTickets);
			} else {
				if (jsonObject.get(jiraConfigKey) instanceof JSONObject) {
					String verticalName = jiraConfigKey;
					if (!jiraConfigKey.toLowerCase().startsWith("vertical"))
						verticalName = "Vertical - " + jiraConfigKey;
					CommonJiraHelper.addVerticalEntryInDatabase(testConfig, verticalName);
					fetchDataForProjectsAndVerticals(testConfig, jsonObject.getJSONObject(jiraConfigKey), entityName,
							date, jiraFilters, isTickets);
				} else {
					if (isTickets) {
					testConfig.logFail("Invalid JiraConfig file format");
					}
				}
			}
		}
	}

	private void fetchProjectWiseDataAndInsertIntoDb(Config testConfig, String entityName,
			JSONArray jiraProjects, LocalDate date, HashMap<String, String> jiraFilters, boolean isTickets) {

		for (int counter = 0; counter < jiraProjects.length(); counter++) {
			String projectName = jiraProjects.getJSONObject(counter).getString("projectName");
			String projectKey = jiraProjects.getJSONObject(counter).getString("projectKey");
			String verticalName = jiraProjects.getJSONObject(counter).has("productVerticalFieldValue")
					? "Vertical - " + jiraProjects.getJSONObject(counter).getString("productVerticalFieldValue")
					: null;

			if (isTickets) {
				testConfig.logComment("Fetching Tickets Tested for Project Name = " + projectName);
				// Fetch tickets tested → paymentgateway_jira_tickets
				applyJiraFilterForTickets(testConfig, projectKey, projectName, verticalName, date,
						jiraFilters.get("ticketTestedFilter"));
				CommonJiraHelper.checkForDeletedTickets(testConfig, testConfig.getRunTimeProperty("tableName2"),
						verticalName, listOfTickets);
				testConfig.logComment("==============================================");
			} else {
				testConfig.logComment("Fetching Bugs for Project Name = " + projectName);
				// Fetch PRD bugs
				fetchBugsByCategory(testConfig, BugCategory.PRD, "PRD", projectName,
						verticalName, date, jiraFilters.get("reportedBugsFilter"));
				// Fetch FCT bugs
				fetchBugsByCategory(testConfig, BugCategory.FCT, "FCT", projectName, verticalName, date,
						jiraFilters.get("reportedBugsFilter"));
				// Fetch STG bugs (default)
				fetchBugsByCategory(testConfig, BugCategory.STG, projectKey, projectName, verticalName, date,
						jiraFilters.get("reportedBugsFilter"));
				// Check for deleted bugs
				CommonJiraHelper.checkForDeletedTickets(testConfig, testConfig.getRunTimeProperty("tableName"),
						verticalName, listOfBugs);
			}
		}
	}

	private void applyJiraFilterForTickets(Config testConfig, String projectKey, String projectName,
			String verticalName, LocalDate date, String jiraFilter) {

		if (projectKey.equalsIgnoreCase("PRD-FCT"))
			return;
		String filterToBeUsed = "filter = " + jiraFilter + " AND project = " + projectKey
				+ " AND 'Product Vertical[Dropdown]'='" + verticalName.replace("Vertical - ", "")
				+ "' AND updatedDate >='" + CommonJiraHelper.startDate + "' AND updatedDate < '"
				+ new SimpleDateFormat("yyyy/MM/dd").format(date.plusDays(1).toDate()) + "' order by created asc";

		// Step 1: Collect all ticket issue keys and issue data from search
		IssueCollectionResult result = collectIssuesFromSearch(testConfig, filterToBeUsed, "tickets");
		java.util.List<String> allIssueKeys = result.allIssueKeys;
		java.util.Map<String, JSONObject> issueDataMap = result.issueDataMap;
		java.util.Map<String, String> numericIdToIssueKeyMap = result.numericIdToIssueKeyMap;

		testConfig.logComment("Fetched " + allIssueKeys.size() + " tickets across " + result.pageCount + " page(s) for "
				+ verticalName);

		// Step 2: Bulk fetch changelogs for all tickets (no retry for tickets)
		java.util.Map<String, JSONObject> changelogMap = bulkFetchChangelogsForIssues(
				allIssueKeys, numericIdToIssueKeyMap, testConfig, "tickets", false);

		// Step 3: Process each ticket with its changelog (if available from bulk fetch)
		int processedCount = 0;
		int noChangelogCount = 0;
		for (String issueId : allIssueKeys) {
			JSONObject issue = issueDataMap.get(issueId);

			// Attach changelog to issue JSON if available from bulk fetch
			if (changelogMap.containsKey(issueId)) {
				issue.put("changelog", changelogMap.get(issueId));
			} else {
				noChangelogCount++;
				testConfig.logCommentForDebugging("No changelog found for ticket " + issueId);
			}

			// Process the ticket details
			processTicketIssueDetails(testConfig, issue, projectName, verticalName, issueId);

			listOfTickets.put(issueId, verticalName);
			processedCount++;
		}

		testConfig.logComment("Successfully processed " + processedCount + " tickets for " + verticalName +
				(noChangelogCount > 0 ? " (" + noChangelogCount + " without changelog)" : ""));
	}

	private void processTicketIssueDetails(Config testConfig, JSONObject issue, String projectName, String verticalName,
			String issueId) {
		// When ticket is moved to any status in ticketTestedCreatedAtStatuses, consider it as createdAt
		String createdAt = null;
		if (issue.has("changelog")) {
			// Check all statuses in ticketTestedCreatedAtStatuses and find the earliest one
			String earliestStatus = null;
			String earliestTime = null;
			for (String status : ticketTestedCreatedAtStatuses) {
				String time = CommonJiraHelper.getTimeWhenTicketMovedToStatusFromChangelog(testConfig,
						issue.getJSONObject("changelog"), status);
				if (time != null) {
					// If this is the first status found, or if this time is earlier than the current earliest
					if (earliestTime == null || time.compareTo(earliestTime) < 0) {
						earliestTime = time;
						earliestStatus = status.trim();
					}
				}
			}
			
			if (earliestTime != null) {
				createdAt = earliestTime;
				testConfig.logCommentForDebugging("Ticket " + issueId + ": Found '" + earliestStatus
						+ "' time from changelog: " + createdAt);
			} else {
				testConfig.logCommentForDebugging("Ticket " + issueId
						+ ": No matching status found in changelog (checked: " + ticketTestedCreatedAtStatuses
						+ "), using creation time");
			}
		} else {
			testConfig.logCommentForDebugging("Ticket " + issueId + ": No changelog available, using creation time");
		}

		// Fallback to issue creation time if no matching status found in changelog
		if (createdAt == null)
			createdAt = CommonUtilities.formatDate(issue.getJSONObject("fields").getString("created").split("\\+")[0],
					"yyyy-MM-dd'T'HH:mm:ss", "yyyy-MM-dd HH:mm:ss");

		String updatedAt = CommonUtilities.formatDate(LocalDateTime.now().toString(), "yyyy-MM-dd'T'HH:mm:ss",
				"yyyy-MM-dd HH:mm:ss");
		
		String teamName = CommonJiraHelper.getCustomValue(issue, CustomField.TeamName, "TBD");
		if (StringUtils.isEmpty(teamName))
			teamName = "TBD";
		projectName = teamName;

		String status = issue.getJSONObject("fields").getJSONObject("status").getString("name");
		String priority = issue.getJSONObject("fields").getJSONObject("priority").getString("name");
		String title = issue.getJSONObject("fields").getString("summary");

		JSONArray versions = issue.getJSONObject("fields").getJSONArray("fixVersions");
		String version = versions.isEmpty() ? "" : versions.getJSONObject(0).getString("name");
		String storyPoints = CommonJiraHelper.getNumericCustomValue(issue, CustomField.StoryPoints, "0");

		addJiraTicketsInDatabase(testConfig, createdAt, updatedAt, projectName, verticalName, issueId, status, title,
				priority, version, storyPoints);
	}

	private void addJiraTicketsInDatabase(Config testConfig, String createdAt, String updatedAt, String projectName,
			String verticalName, String issueId, String status, String title, String priority, String version,
			String storyPoints) {
		String selectQuery = "SELECT id from {$tableName2} where issueId = '{$issueId}';";
		String updateQuery = "UPDATE {$tableName2} SET createdAt='{$createdAt}',updatedAt='{$updatedAt}',projectName='{$projectName}',verticalName='{$verticalName}',status='{$status}',title='{$title}',priority='{$priority}',version='{$version}',storyPoints='{$storyPoints}', isDeleted=0 WHERE id = {$id};";
		String insertQuery = "INSERT into {$tableName2} (createdAt,updatedAt,projectName,verticalName,issueId,status,title,priority,version,storyPoints,isDeleted) values ('{$createdAt}','{$updatedAt}','{$projectName}','{$verticalName}','{$issueId}','{$status}','{$title}','{$priority}','{$version}','{$storyPoints}',0);";

		testConfig.putRunTimeProperty("createdAt", createdAt);
		testConfig.putRunTimeProperty("updatedAt", updatedAt);
		testConfig.putRunTimeProperty("projectName", projectName);
		testConfig.putRunTimeProperty("verticalName", verticalName);
		testConfig.putRunTimeProperty("issueId", issueId);
		testConfig.putRunTimeProperty("status", status);
		title = title.replace("\\", " ");
		title = title.replace("'", "");
		title = title.length() > 250 ? title.substring(0, 250) : title;
		testConfig.putRunTimeProperty("title", title);
		testConfig.putRunTimeProperty("priority", priority);
		testConfig.putRunTimeProperty("version", version);
		testConfig.putRunTimeProperty("storyPoints", storyPoints);

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

	// ========================================================================
	// COMMON HELPER METHODS (Used by both tickets and bugs)
	// ========================================================================

	/**
	 * Collects all issues from Jira search with pagination
	 * 
	 * @return Object containing allIssueKeys, issueDataMap, and
	 *         numericIdToIssueKeyMap
	 */
	private static class IssueCollectionResult {
		java.util.List<String> allIssueKeys;
		java.util.Map<String, JSONObject> issueDataMap;
		java.util.Map<String, String> numericIdToIssueKeyMap;
		int pageCount;

		IssueCollectionResult() {
			this.allIssueKeys = new java.util.ArrayList<>();
			this.issueDataMap = new java.util.HashMap<>();
			this.numericIdToIssueKeyMap = new java.util.HashMap<>();
			this.pageCount = 0;
		}
	}

	private IssueCollectionResult collectIssuesFromSearch(Config testConfig, String filterToBeUsed, String entityType) {
		IssueCollectionResult result = new IssueCollectionResult();
		String nextPageToken = null;
		boolean isLast = false;

		do {
			String response = jiraClient.performSearchAndGetResults(testConfig, jiraClient, filterToBeUsed,
					nextPageToken);
			if (response == null)
				break;

			JSONObject searchResults = new JSONObject(response);
			JSONArray issuesArray = searchResults.getJSONArray("issues");
			isLast = searchResults.getBoolean("isLast");
			nextPageToken = searchResults.optString("nextPageToken", null);
			result.pageCount++;

			// Collect issue keys and store issue data
			for (int i = 0; i < issuesArray.length(); i++) {
				JSONObject issue = issuesArray.getJSONObject(i);
				String issueKey = issue.getString("key");
				String numericId = issue.optString("id", null);
				result.allIssueKeys.add(issueKey);
				result.issueDataMap.put(issueKey, issue);
				// Map numeric ID to issue key for changelog lookup
				if (numericId != null && !numericId.isEmpty()) {
					result.numericIdToIssueKeyMap.put(numericId, issueKey);
				}
			}

			testConfig.logCommentForDebugging("Fetched " + entityType + " page " + result.pageCount + " with " +
					issuesArray.length() + " " + entityType + ". Total so far: " + result.allIssueKeys.size());
		} while (!isLast);

		return result;
	}

	/**
	 * Processes a single changelog entry and adds it to the changelog map
	 */
	private void processChangelogEntry(JSONObject changelogEntry, java.util.Map<String, JSONObject> changelogMap,
			java.util.Map<String, String> numericIdToIssueKeyMap, Config testConfig,
			String entityType) {
		String numericIssueId = changelogEntry.optString("issueId", null);
		if (numericIssueId != null) {
			String issueKey = numericIdToIssueKeyMap.get(numericIssueId);
			if (issueKey != null) {
				changelogMap.put(issueKey, changelogEntry);
			} else {
				testConfig.logCommentForDebugging("Warning: Could not map numeric issueId " + numericIssueId +
						" to " + entityType + " key for changelog");
			}
		}
	}

	/**
	 * Handles pagination for bulk changelog fetch
	 */
	private void handleChangelogPagination(java.util.List<String> batch, String changelogNextPageToken,
			java.util.Map<String, JSONObject> changelogMap,
			java.util.Map<String, String> numericIdToIssueKeyMap,
			Config testConfig, int batchNumber, String entityType) {
		if (changelogNextPageToken == null || changelogNextPageToken.isEmpty()) {
			return;
		}

		testConfig.logCommentForDebugging("Changelog pagination detected for " + entityType + " batch " + batchNumber +
				", fetching additional pages...");
		String currentPageToken = changelogNextPageToken;
		boolean hasMorePages = true;
		int changelogPageCount = 1;

		while (hasMorePages) {
			try {
				Thread.sleep(500);
				changelogPageCount++;
				io.restassured.response.Response nextPageResponse = jiraClient.bulkFetchChangelogs(batch, null,
						currentPageToken);

				if (nextPageResponse.getStatusCode() == 200) {
					JSONObject nextPageResults = new JSONObject(nextPageResponse.asString());
					JSONArray nextPageArray = nextPageResults.optJSONArray("issueChangeLogs");

					if (nextPageArray != null && nextPageArray.length() > 0) {
						testConfig.logCommentForDebugging("Fetched changelog page " + changelogPageCount +
								" with " + nextPageArray.length() + " entries");
						for (int k = 0; k < nextPageArray.length(); k++) {
							JSONObject changelogEntry = nextPageArray.getJSONObject(k);
							processChangelogEntry(changelogEntry, changelogMap, numericIdToIssueKeyMap, testConfig,
									entityType);
						}
					}

					currentPageToken = nextPageResults.optString("nextPageToken", null);
					hasMorePages = currentPageToken != null && !currentPageToken.isEmpty();
				} else {
					testConfig.logComment("Warning: Failed to fetch next changelog page for " + entityType +
							" (status: " + nextPageResponse.getStatusCode() + "), stopping pagination");
					hasMorePages = false;
				}
			} catch (InterruptedException e) {
				Thread.currentThread().interrupt();
				testConfig.logComment("Warning: Pagination interrupted for " + entityType + ", stopping");
				hasMorePages = false;
			} catch (Exception e) {
				testConfig.logComment("Warning: Error fetching next changelog page for " + entityType + ": " +
						e.getMessage());
				hasMorePages = false;
			}
		}
	}

	/**
	 * Bulk fetches changelogs for issues with optional retry logic
	 * 
	 * @param allIssueKeys           List of issue keys to fetch changelogs for
	 * @param numericIdToIssueKeyMap Map from numeric ID to issue key
	 * @param testConfig             Test configuration
	 * @param entityType             Type of entity ("tickets" or "issues") for
	 *                               logging
	 * @param enableRetry            Whether to enable retry logic with exponential
	 *                               backoff
	 * @return Map of issue key to changelog JSON object
	 */
	private java.util.Map<String, JSONObject> bulkFetchChangelogsForIssues(
			java.util.List<String> allIssueKeys,
			java.util.Map<String, String> numericIdToIssueKeyMap,
			Config testConfig, String entityType, boolean enableRetry) {

		java.util.Map<String, JSONObject> changelogMap = new java.util.HashMap<>();

		if (allIssueKeys.isEmpty()) {
			return changelogMap;
		}

		testConfig.logComment("Bulk fetching changelogs for " + allIssueKeys.size() + " " + entityType + "...");

		// Process in batches of 1,000 (bulk fetch limit per API documentation)
		int totalBatches = (int) Math.ceil((double) allIssueKeys.size() / 1000);
		boolean anyBatchFailed = false;
		int maxRetries = enableRetry ? 3 : 1;

		for (int batchStart = 0; batchStart < allIssueKeys.size(); batchStart += 1000) {
			int batchEnd = Math.min(batchStart + 1000, allIssueKeys.size());
			java.util.List<String> batch = new java.util.ArrayList<>(
					allIssueKeys.subList(batchStart, batchEnd));
			int batchNumber = (batchStart / 1000) + 1;
			testConfig.logCommentForDebugging("Processing changelog batch " + batchNumber + "/" + totalBatches +
					" (" + batch.size() + " " + entityType + ")");

			boolean success = false;
			long baseDelayMs = 1000; // Start with 1 second

			for (int retry = 0; retry < maxRetries && !success; retry++) {
				try {
					if (retry > 0 && enableRetry) {
						// Exponential backoff: 1s, 2s, 4s
						long delayMs = baseDelayMs * (1L << (retry - 1));
						testConfig.logComment("Retrying bulk fetch changelogs (attempt " +
								(retry + 1) + "/" + maxRetries + ") after " + delayMs + "ms delay...");
						Thread.sleep(delayMs);
					}

					io.restassured.response.Response changelogResponse = jiraClient.bulkFetchChangelogs(batch, null); // null
																														// =
																														// fetch
																														// all
																														// fields

					int statusCode = changelogResponse.getStatusCode();
					if (statusCode == 200) {
						String responseBody = changelogResponse.asString();
						JSONObject changelogResults = new JSONObject(responseBody);
						JSONArray changelogArray = changelogResults.optJSONArray("issueChangeLogs");

						if (changelogArray != null && changelogArray.length() > 0) {
							int entriesInBatch = 0;
							// Process each changelog entry
							for (int j = 0; j < changelogArray.length(); j++) {
								JSONObject changelogEntry = changelogArray.getJSONObject(j);
								String numericIssueId = changelogEntry.optString("issueId", null);
								if (numericIssueId != null) {
									String issueKey = numericIdToIssueKeyMap.get(numericIssueId);
									if (issueKey != null) {
										changelogMap.put(issueKey, changelogEntry);
										entriesInBatch++;
									}
								}
							}

							// Handle pagination if needed
							String changelogNextPageToken = changelogResults.optString("nextPageToken", null);
							handleChangelogPagination(batch, changelogNextPageToken, changelogMap,
									numericIdToIssueKeyMap, testConfig, batchNumber, entityType);

							success = true;
							testConfig.logCommentForDebugging("Successfully fetched changelogs for " + entityType +
									" batch " + batchNumber + " (" + entriesInBatch + " changelog entries from " +
									batch.size() + " " + entityType + ")");
						} else {
							// Check if response has different structure or is truly empty
							if (changelogArray == null) {
								testConfig.logComment("Warning: Bulk fetch response has no 'issueChangeLogs' array. " +
										"Response keys: " + java.util.Arrays.toString(
												org.json.JSONObject.getNames(changelogResults))
										+
										(enableRetry && retry < maxRetries - 1 ? ", retrying..." : ""));
							} else {
								testConfig.logComment("Warning: Bulk fetch returned empty changelog array " +
										"(length: " + changelogArray.length() + ")" +
										(enableRetry && retry < maxRetries - 1 ? ", retrying..." : ""));
							}
						}
					} else {
						// Log error response for debugging
						String errorBody = changelogResponse.asString();
						if (errorBody.length() > 0 && errorBody.length() < 500) {
							testConfig.logComment("Error response body: " + errorBody);
						}

						// Retry on 5xx errors, fail fast on 4xx errors
						if (statusCode >= 500 && enableRetry && retry < maxRetries - 1) {
							testConfig.logComment("Warning: Bulk fetch returned status " +
									statusCode + ", will retry...");
						} else {
							testConfig.logComment("Warning: Bulk fetch returned status " +
									statusCode + " for batch " + batchNumber);
							if (!enableRetry || statusCode < 500) {
								break; // Don't retry on 4xx errors or if retry disabled
							}
						}
					}
				} catch (InterruptedException e) {
					Thread.currentThread().interrupt();
					testConfig.logComment("Error: Bulk fetch interrupted for " + entityType);
					break;
				} catch (Exception e) {
					testConfig.logComment("Warning: Bulk fetch attempt " + (retry + 1) + " failed for " + entityType +
							": " + e.getMessage() +
							(enableRetry && retry < maxRetries - 1 ? ", will retry..." : ""));
				}
			}

			if (!success) {
				anyBatchFailed = true;
				testConfig.logComment("Failed to bulk fetch changelogs for " + entityType + " batch " + batchNumber +
						" after " + maxRetries + " attempts.");
			}
		}

		// Fail the test if any batch failed after all retries (only for bugs which
		// require changelogs)
		if (anyBatchFailed && enableRetry) {
			testConfig.logFail("Bulk fetch changelogs failed for one or more batches after " +
					maxRetries + " retry attempts. Cannot proceed without changelog data.");
		}

		testConfig.logComment("Successfully fetched changelogs for " + changelogMap.size() + " out of " +
				allIssueKeys.size() + " " + entityType + ".");

		return changelogMap;
	}

	private void fetchBugsByCategory(Config testConfig, BugCategory category,
			String projectKey, String projectName,
			String verticalName, LocalDate date, String jiraFilter) {

		testConfig.logComment("Fetching " + category + " bugs for " + verticalName);
		String filterToBeUsed = buildBugJqlFilter(category, projectKey, verticalName, date, jiraFilter);

		// Step 1: Collect all issue keys and issue data from search
		IssueCollectionResult result = collectIssuesFromSearch(testConfig, filterToBeUsed, "issues");
		java.util.List<String> allIssueKeys = result.allIssueKeys;
		java.util.Map<String, JSONObject> issueDataMap = result.issueDataMap;
		java.util.Map<String, String> numericIdToIssueKeyMap = result.numericIdToIssueKeyMap;

		testConfig.logCommentForDebugging(
				"Total issues fetched: " + allIssueKeys.size() + " across " + result.pageCount + " page(s)");

		// Step 2: Bulk fetch changelogs for all issues (with retry for bugs - required
		// for timing calculations)
		java.util.Map<String, JSONObject> changelogMap = bulkFetchChangelogsForIssues(
				allIssueKeys, numericIdToIssueKeyMap, testConfig, "issues", true);

		// If bulk fetch failed (returned empty map and we have issues), exit early
		if (changelogMap.isEmpty() && !allIssueKeys.isEmpty()) {
			return; // Exit early since we can't calculate timing without changelogs
		}

		// Step 3: Process each issue with its changelog (if available from bulk fetch)
		int processedCount = 0;
		int skippedCount = 0;
		int noChangelogCount = 0;
		for (String issueId : allIssueKeys) {
			JSONObject issue = issueDataMap.get(issueId);

			// Attach changelog to issue JSON if available from bulk fetch
			if (changelogMap.containsKey(issueId)) {
				issue.put("changelog", changelogMap.get(issueId));
			} else {
				noChangelogCount++;
				testConfig.logCommentForDebugging("No changelog found for issue " + issueId);
			}

			BugData bugData = processBugIssue(testConfig, issue, category,
					projectName, verticalName, issueId);

			if (bugData != null && bugData.shouldInsert()) {
				insertBugIntoDatabase(testConfig, bugData);
				listOfBugs.put(issueId, verticalName);
				processedCount++;
			} else {
				skippedCount++;
				testConfig.logCommentForDebugging(
						"Skipped issue " + issueId + " (bugData=" + (bugData == null ? "null" : "not null") +
								", shouldInsert=" + (bugData != null ? bugData.shouldInsert() : "N/A") + ")");
			}
		}

		testConfig.logCommentForDebugging(
				"Processing summary: " + processedCount + " processed, " + skippedCount + " skipped, " +
						noChangelogCount + " without changelog");

		// Log success with processed count
		testConfig.logComment(
				"Successfully processed " + processedCount + " " + category + " bugs for " + verticalName + ".");
	}

	private String buildBugJqlFilter(BugCategory category, String projectKey,
			String verticalName, LocalDate date, String jiraFilter) {
		String datePart = " AND createdDate >='" + CommonJiraHelper.startDate +
				"' AND createdDate < '" +
				new SimpleDateFormat("yyyy/MM/dd").format(date.plusDays(1).toDate()) +
				"' order by created asc";

		switch (category) {
			case PRD:
				return "filter = " + jiraFilter + " AND project = PRD AND 'Product Vertical[Dropdown]'='" +
						verticalName.replace("Vertical - ", "") + "'" + datePart;
			case FCT:
				return "filter = " + jiraFilter + " AND project = FCT AND 'Product Vertical[Dropdown]'='" +
						verticalName.replace("Vertical - ", "") + "'" + datePart;
			case STG:
			default:
				if (projectKey.equalsIgnoreCase("PRD-FCT"))
					return "filter = " + jiraFilter + " AND project not in (PRD,FCT) AND 'Product Vertical[Dropdown]'='"
							+
							verticalName.replace("Vertical - ", "") + "'" + datePart;
				else
					return "filter = " + jiraFilter + " AND project = " + projectKey +
							" AND 'Product Vertical[Dropdown]'='" + verticalName.replace("Vertical - ", "") + "'"
							+ datePart;
		}
	}

	private BugData processBugIssue(Config testConfig, JSONObject issue, BugCategory category,
			String projectName, String verticalName, String issueId) {
		try {
			BugData bug = new BugData();
			bug.setIssueId(issueId);
			bug.setBugCategory(category);

			String createdAt = issue.getJSONObject("fields").getString("created").split("\\+")[0];
			bug.setCreatedAt(CommonUtilities.formatDate(createdAt, "yyyy-MM-dd'T'HH:mm:ss", "yyyy-MM-dd HH:mm:ss"));
			bug.setUpdatedAt(CommonUtilities.formatDate(LocalDateTime.now().toString(), "yyyy-MM-dd'T'HH:mm:ss",
					"yyyy-MM-dd HH:mm:ss"));

			String teamName = CommonJiraHelper.getCustomValue(issue, CustomField.TeamName, "TBD");
			if (StringUtils.isEmpty(teamName))
				teamName = "TBD";
			bug.setTeamName(teamName);
			bug.setProjectName(teamName);
			bug.setVerticalName(verticalName);

			bug.setPriority(issue.getJSONObject("fields").getJSONObject("priority").getString("name"));
			bug.setStatus(issue.getJSONObject("fields").getJSONObject("status").getString("name"));
			bug.setBugType(CommonJiraHelper.getCustomValue(issue, CustomField.BugType, "Unknown Bug"));

			String rootCause = CommonJiraHelper.getCustomValue(issue, CustomField.TechRootCause, "TBD");
			if (category == BugCategory.FCT && rootCause.contains("Code related Bug")) {
				rootCause = CommonJiraHelper.getCustomValue(issue, CustomField.BugCauseClassification, "TBD");
			}
			bug.setRootCause(rootCause);

			bug.setProductArea(CommonJiraHelper.getCustomValue(issue, CustomField.ProductArea, "TBD"));
			bug.setBugPlatform(CommonJiraHelper.getMultiSelectCustomValue(issue, CustomField.Platform, "TBD"));
			bug.setTitle(issue.getJSONObject("fields").getString("summary").replace("'", ""));
			bug.setEnvironment(CommonJiraHelper.getCustomValue(issue, CustomField.AffectedEnvironment,
					category == BugCategory.PRD ? "Production" : "TBD"));
			bug.setBugFoundBy(CommonJiraHelper.getCustomValue(issue, CustomField.BugFoundBy, "TBD"));

			JSONArray versions = issue.getJSONObject("fields").getJSONArray("versions");
			bug.setVersion(versions.isEmpty() ? "" : versions.getJSONObject(0).getString("name"));

			Classification classification = determineClassification(category, bug.getStatus(),
					bug.getEnvironment(),
					bug.getBugType(),
					bug.getRootCause());
			bug.setClassification(classification);

			int isInvalid = 0;
			for (String invalidStatus : invalidStatuses) {
				if (bug.getBugType().contains(invalidStatus) ||
						bug.getRootCause().contains(invalidStatus) ||
						bug.getStatus().contains(invalidStatus)) {
					isInvalid = 1;
					break;
				}
			}
			bug.setIsInvalid(isInvalid);

			if (category == BugCategory.PRD) {
				bug.setEnvironment("Production");
				if (bug.getBugType().equalsIgnoreCase("Beta Testing Bug") ||
						bug.getBugType().equalsIgnoreCase("Prod Testing Bug")) {
					bug.setBugFoundBy("manual");
				} else {
					bug.setBugFoundBy("real prod users");
				}
			}

			calculateAndSetTimingMetrics(testConfig, bug, category, issueId, bug.getCreatedAt(), issue);

			return bug;

		} catch (Exception e) {
			testConfig.logComment("Error processing bug " + issueId + ": " + e.getMessage());
			e.printStackTrace();
			return null;
		}
	}

	private Classification determineClassification(BugCategory category, String status,
			String environment, String bugType,
			String rootCause) {
		if (rootCause.contains("Not a Bug") || rootCause.contains("Duplicate")) {
			return Classification.INVALID;
		}

		switch (category) {
			case PRD:
				if (status.equalsIgnoreCase("Wont fix") || environment.equalsIgnoreCase("Demo")) {
					return Classification.OTHERS;
				}
				return Classification.PAYMENTGATEWAY;
			case FCT:
			case STG:
				if (bugType.contains("Partner")) {
					return Classification.PARTNER;
				}
				return Classification.PAYMENTGATEWAY;
			default:
				return Classification.PAYMENTGATEWAY;
		}
	}

	private void calculateAndSetTimingMetrics(Config testConfig, BugData bug,
			BugCategory category, String issueId,
			String createdAt, JSONObject issueJson) {
		try {
			if (category == BugCategory.PRD) {
				calculatePrdTimingMetrics(testConfig, bug, issueId, createdAt, issueJson);
			} else {
				calculateStgFctTimingMetrics(testConfig, bug, issueId, createdAt, issueJson);
			}
		} catch (Exception e) {
			testConfig.logComment("Could not calculate timing for " + issueId + ": " + e.getMessage());
		}
	}

	private void calculatePrdTimingMetrics(Config testConfig, BugData bug,
			String issueId, String createdAt, JSONObject issueJson) {
		// Try to get changelog from search response first
		JSONObject changelog = null;
		if (issueJson != null && issueJson.has("changelog")) {
			changelog = issueJson.getJSONObject("changelog");
		}

		// Get updatedAt and current status from issue JSON for resolved bugs
		String updatedAt = null;
		String currentStatus = bug.getStatus(); // Get current status from bug object
		if (issueJson != null && issueJson.has("fields")) {
			JSONObject fields = issueJson.getJSONObject("fields");
			if (fields.has("updated")) {
				String updatedStr = fields.getString("updated");
				// Convert from Jira format to our format
				updatedAt = CommonUtilities.formatDate(updatedStr, "yyyy-MM-dd'T'HH:mm:ss.SSSX", "yyyy-MM-dd HH:mm:ss");
			}
		}

		// Calculate clock hours for: devTime, pmTime, developmentTime, overallTime
		Map<String, Duration> statusTimes = CommonJiraHelper.calculateStatusTimeUsingClockHours(
				testConfig, jiraClient, issueId, createdAt, changelog, updatedAt, currentStatus);

		// Calculate working hours for: qaTime (only qaTime uses working hours)
		Map<String, Duration> workingHoursTime = CommonJiraHelper.calculateStatusTimeUsingWorkingHours(
				testConfig, jiraClient, issueId, createdAt, false, changelog, updatedAt, currentStatus);

		int devTime = 0, overallTime = 0, developmentTime = 0, pmTime = 0;

		// Calculate devTime, developmentTime, overallTime, pmTime using clock hours
		for (Map.Entry<String, Duration> entry : statusTimes.entrySet()) {
			String statusKey = entry.getKey();
			int seconds = (int) entry.getValue().getSeconds();

			// Use exact match with semicolon delimiters to avoid partial matches
			boolean isInDevTimeStatuses = (prdDevTimeStatuses + ";").contains(statusKey + ";");
			boolean isInPmTimeStatuses = (prdPmTimeStatuses + ";").contains(statusKey + ";");
			boolean isInDevelopmentTimeStatuses = (prdDevlopmentTimeStatuses + ";").contains(statusKey + ";");
			boolean isInTotalTimeStatuses = (prdTotalTimeStatuses + ";").contains(statusKey + ";");

			// devTime: Only count statuses in prdDevTimeStatuses (exact match, clock hours)
			if (isInDevTimeStatuses) {
				devTime += seconds;
			}

			// pmTime: Only count statuses in prdPmTimeStatuses (exact match, clock hours)
			if (isInPmTimeStatuses) {
				pmTime += seconds;
			}

			// developmentTime: Only count statuses in prdDevlopmentTimeStatuses (exact
			// match, clock hours)
			// Includes: prdQaTimeStatuses + prdDevTimeStatuses + prdPmTimeStatuses
			if (isInDevelopmentTimeStatuses) {
				developmentTime += seconds;
			}

			// overallTime: Only count statuses in prdTotalTimeStatuses (exact match, clock
			// hours)
			// Includes: prdDevlopmentTimeStatuses + "Deployment Ready"
			if (isInTotalTimeStatuses) {
				overallTime += seconds;
			}
		}

		// qaTime: Only count statuses in prdQaTimeStatuses (exact match, working hours)
		// Only qaTime uses working hours; all others use clock hours
		int qaTime = 0;
		for (Map.Entry<String, Duration> entry : workingHoursTime.entrySet()) {
			String statusKey = entry.getKey();
			// Use exact match with semicolon delimiters to avoid partial matches
			if ((prdQaTimeStatuses + ";").contains(statusKey + ";")) {
				qaTime += (int) entry.getValue().getSeconds();
			}
		}

		bug.setDevTime(devTime);
		bug.setQaTime(qaTime);
		bug.setOverallTime(overallTime);
		bug.setPmTime(pmTime);
		bug.setDevelopmentTime(developmentTime);
	}

	private void calculateStgFctTimingMetrics(Config testConfig, BugData bug,
			String issueId, String createdAt, JSONObject issueJson) {
		// Try to get changelog from search response first
		JSONObject changelog = null;
		if (issueJson != null && issueJson.has("changelog")) {
			changelog = issueJson.getJSONObject("changelog");
		}

		// Get updatedAt and current status from issue JSON for resolved bugs
		String updatedAt = null;
		String currentStatus = bug.getStatus(); // Get current status from bug object
		if (issueJson != null && issueJson.has("fields")) {
			JSONObject fields = issueJson.getJSONObject("fields");
			if (fields.has("updated")) {
				String updatedStr = fields.getString("updated");
				// Convert from Jira format to our format
				updatedAt = CommonUtilities.formatDate(updatedStr, "yyyy-MM-dd'T'HH:mm:ss.SSSX", "yyyy-MM-dd HH:mm:ss");
			}
		}

		Map<String, Duration> statusTimes = CommonJiraHelper.calculateStatusTimeUsingWorkingHours(
				testConfig, jiraClient, issueId, createdAt, true, changelog, updatedAt, currentStatus);

		int devTime = 0, qaTime = 0, overallTime = 0;

		for (Map.Entry<String, Duration> entry : statusTimes.entrySet()) {
			String statusKey = entry.getKey();
			int seconds = (int) entry.getValue().getSeconds();

			if (devStatuses.contains(statusKey)) {
				devTime += seconds;
			}
			if (qaStatuses.contains(statusKey)) {
				qaTime += seconds;
			}
			overallTime += seconds;
		}

		bug.setDevTime(devTime);
		bug.setQaTime(qaTime);
		bug.setOverallTime(overallTime);
	}

	private void insertBugIntoDatabase(Config testConfig, BugData bug) {
		String selectQuery = "SELECT id FROM {$tableName} WHERE issueId = '{$issueId}';";

		String updateQuery = "UPDATE {$tableName} SET " +
				"createdAt='{$createdAt}', updatedAt='{$updatedAt}', " +
				"bugCategory='{$bugCategory}', classification='{$classification}', " +
				"teamName='{$teamName}', projectName='{$projectName}', verticalName='{$verticalName}', " +
				"productArea='{$productArea}', priority='{$priority}', status='{$status}', " +
				"bugType='{$bugType}', rootCause='{$rootCause}', bugPlatform='{$bugPlatform}', " +
				"title='{$title}', environment='{$environment}', bugFoundBy='{$bugFoundBy}', " +
				"version='{$version}', devTime={$devTime}, qaTime={$qaTime}, " +
				"overallTime={$overallTime}, pmTime={$pmTime}, developmentTime={$developmentTime}, " +
				"isInvalid={$isInvalid}, isDeleted=0 WHERE id = {$id};";

		String insertQuery = "INSERT INTO {$tableName} (" +
				"createdAt, updatedAt, issueId, bugCategory, classification, " +
				"teamName, projectName, verticalName, productArea, priority, status, " +
				"bugType, rootCause, bugPlatform, title, environment, bugFoundBy, version, " +
				"devTime, qaTime, overallTime, pmTime, developmentTime, isInvalid, isDeleted" +
				") VALUES (" +
				"'{$createdAt}', '{$updatedAt}', '{$issueId}', '{$bugCategory}', '{$classification}', " +
				"'{$teamName}', '{$projectName}', '{$verticalName}', '{$productArea}', '{$priority}', '{$status}', " +
				"'{$bugType}', '{$rootCause}', '{$bugPlatform}', '{$title}', '{$environment}', " +
				"'{$bugFoundBy}', '{$version}', {$devTime}, {$qaTime}, {$overallTime}, " +
				"{$pmTime}, {$developmentTime}, {$isInvalid}, 0);";

		testConfig.putRunTimeProperty("createdAt", bug.getCreatedAt());
		testConfig.putRunTimeProperty("updatedAt", bug.getUpdatedAt());
		testConfig.putRunTimeProperty("issueId", bug.getIssueId());
		testConfig.putRunTimeProperty("bugCategory", bug.getCategoryString());
		testConfig.putRunTimeProperty("classification", bug.getClassificationString());
		testConfig.putRunTimeProperty("teamName", bug.getTeamName());
		testConfig.putRunTimeProperty("projectName", bug.getProjectName());
		testConfig.putRunTimeProperty("verticalName", bug.getVerticalName());
		testConfig.putRunTimeProperty("productArea", bug.getProductArea());
		testConfig.putRunTimeProperty("priority", bug.getPriority());
		testConfig.putRunTimeProperty("status", bug.getStatus());
		testConfig.putRunTimeProperty("bugType", bug.getBugType());
		testConfig.putRunTimeProperty("rootCause", bug.getRootCause());
		testConfig.putRunTimeProperty("bugPlatform", bug.getBugPlatform());

		String title = bug.getTitle();
		if (title != null) {
			title = title.replace("\\", " ").replace("'", "");
			title = title.length() > 250 ? title.substring(0, 250) : title;
		}
		testConfig.putRunTimeProperty("title", title != null ? title : "");

		testConfig.putRunTimeProperty("environment", bug.getEnvironment() != null ? bug.getEnvironment() : "");
		testConfig.putRunTimeProperty("bugFoundBy", bug.getBugFoundBy() != null ? bug.getBugFoundBy() : "");
		testConfig.putRunTimeProperty("version", bug.getVersion() != null ? bug.getVersion() : "");
		testConfig.putRunTimeProperty("devTime", bug.getDevTime() != null ? String.valueOf(bug.getDevTime()) : "0");
		testConfig.putRunTimeProperty("qaTime", bug.getQaTime() != null ? String.valueOf(bug.getQaTime()) : "0");
		testConfig.putRunTimeProperty("overallTime",
				bug.getOverallTime() != null ? String.valueOf(bug.getOverallTime()) : "0");
		testConfig.putRunTimeProperty("pmTime", bug.getPmTime() != null ? String.valueOf(bug.getPmTime()) : "0");
		testConfig.putRunTimeProperty("developmentTime",
				bug.getDevelopmentTime() != null ? String.valueOf(bug.getDevelopmentTime()) : "0");
		testConfig.putRunTimeProperty("isInvalid", String.valueOf(bug.getIsInvalid()));

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