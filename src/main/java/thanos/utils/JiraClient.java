package thanos.utils;

import io.restassured.RestAssured;
import io.restassured.response.Response;

public class JiraClient {
    private final String baseUrl;
    private final String username;
    private final String password;

    public JiraClient(Config testConfig) {
        this.baseUrl = testConfig.getRunTimeProperty("JiraHostUrl");;
        this.username = CommonUtilities.decryptMessage(System.getProperty("JiraUsername").getBytes());;
        this.password = CommonUtilities.decryptMessage(System.getProperty("JiraPassword").getBytes());;
    }

    public Response getIssue(String issueKey) {
        String url = baseUrl + "/rest/api/2/issue/" + issueKey;
        return executeApi(url);
    }

    public Response searchIssues(String jqlQuery, int maxResults, String nextPageToken) {
        String url = baseUrl + "/rest/api/3/search/jql";
        
        io.restassured.specification.RequestSpecification request = RestAssured.given()
                .auth().preemptive().basic(username, password)
                .param("jql", jqlQuery)
                .param("maxResults", maxResults)
				.param("fieldsByKeys", true)
				.param("fields", "*all,-comment");
        
        if (nextPageToken != null && !nextPageToken.isEmpty()) {
            request = request.param("nextPageToken", nextPageToken);
        }
        
        return request.get(url);
    }
    
    /**
     * Bulk fetch changelogs for multiple issues (up to 1,000 issues per request)
     * Uses POST /rest/api/3/changelog/bulkfetch endpoint
     * 
     * @param issueIdsOrKeys List of issue IDs or keys (max 1,000)
     * @param fieldIds Optional list of field IDs to filter (max 10). If null, returns all fields.
     * @return Response containing paginated changelog data
     */
    public Response bulkFetchChangelogs(java.util.List<String> issueIdsOrKeys, java.util.List<String> fieldIds) {
        return bulkFetchChangelogs(issueIdsOrKeys, fieldIds, null);
    }
    
    /**
     * Bulk fetch changelogs for multiple issues with pagination support
     * Uses POST /rest/api/3/changelog/bulkfetch endpoint
     * 
     * @param issueIdsOrKeys List of issue IDs or keys (max 1,000)
     * @param fieldIds Optional list of field IDs to filter (max 10). If null, returns all fields.
     * @param nextPageToken Optional pagination token for fetching next page
     * @return Response containing paginated changelog data
     */
    public Response bulkFetchChangelogs(java.util.List<String> issueIdsOrKeys, java.util.List<String> fieldIds, String nextPageToken) {
        String url = baseUrl + "/rest/api/3/changelog/bulkfetch";
        
        // Build request body
        org.json.JSONObject requestBody = new org.json.JSONObject();
        org.json.JSONArray issuesArray = new org.json.JSONArray();
        for (String issueIdOrKey : issueIdsOrKeys) {
            issuesArray.put(issueIdOrKey);
        }
        requestBody.put("issueIdsOrKeys", issuesArray);
        
        if (fieldIds != null && !fieldIds.isEmpty()) {
            org.json.JSONArray fieldsArray = new org.json.JSONArray();
            for (String fieldId : fieldIds) {
                fieldsArray.put(fieldId);
            }
            requestBody.put("fieldIds", fieldsArray);
        }
        
        if (nextPageToken != null && !nextPageToken.isEmpty()) {
            requestBody.put("nextPageToken", nextPageToken);
        }
        
        return RestAssured.given()
                    .auth().preemptive().basic(username, password)
                .contentType("application/json")
                .body(requestBody.toString())
                .post(url);
    }
    
    private Response executeApi(String url) {
    	Response response = RestAssured.given()
        		.auth().preemptive().basic(username, password)
                .get(url);
        
        if (response.getStatusCode() != 200) {
        	System.out.println("Execution failed!");
        }
        return response;
    }
    
    public Response testConnection() {

        String apiEndpoint = "/rest/api/2/myself";
        String apiUrl = baseUrl + apiEndpoint;
        return executeApi(apiUrl);
    }
    
    public Response getIssueHistory(String issueKey) {
        String apiEndpoint = baseUrl + "/rest/api/2/issue/"+issueKey+"/changelog?maxResults=1000&startAt=0";
        return executeApi(apiEndpoint);
    }
    
	public String performSearchAndGetResults(Config testConfig, JiraClient jiraClient, String jqlQuery, String nextPageToken) {
		testConfig.logComment("Executing Query :- " + jqlQuery);
		Response results = jiraClient.searchIssues(jqlQuery, 100, nextPageToken);
		return results.asString();
	}
	
	public String performSearchAndGetAllResults(Config testConfig, JiraClient jiraClient, String jqlQuery) {
		testConfig.logComment("Executing Query with pagination :- " + jqlQuery);
		
		java.util.List<String> allTicketKeys = new java.util.ArrayList<>();
		
		int maxResults = 100;
		int totalFetched = 0;
		String nextPageToken = null;
		boolean isLast = false;
		
		while (!isLast) {
			Response results = jiraClient.searchIssues(jqlQuery, maxResults, nextPageToken);
			String responseString = results.asString();
			
			// Parse the response to get issue keys and pagination info
			io.restassured.path.json.JsonPath jsonPath = new io.restassured.path.json.JsonPath(responseString);
			java.util.List<String> issueKeys = jsonPath.getList("issues.key");
			isLast = jsonPath.getBoolean("isLast");
			nextPageToken = jsonPath.getString("nextPageToken");
			
			if (issueKeys == null || issueKeys.isEmpty()) {
				break; // No more results
			}
			
			// Add issue keys to our list
			allTicketKeys.addAll(issueKeys);
			
			totalFetched += issueKeys.size();
			testConfig.logComment("Fetched " + totalFetched + " tickets so far...");
		}
		
		testConfig.logComment("Total tickets fetched: " + totalFetched);
		
		// Return a simple JSON with just the issue keys
		StringBuilder result = new StringBuilder();
		result.append("{\"issues\":[");
		for (int i = 0; i < allTicketKeys.size(); i++) {
			if (i > 0) result.append(",");
			result.append("{\"key\":\"").append(allTicketKeys.get(i)).append("\"}");
		}
		result.append("]}");
		
		return result.toString();
	}

}
