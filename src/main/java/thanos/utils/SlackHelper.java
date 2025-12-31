package thanos.utils;

import static io.restassured.RestAssured.given;

import java.io.File;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;

import org.json.JSONArray;
import org.json.JSONObject;

import io.restassured.http.ContentType;
import io.restassured.response.Response;
import io.restassured.specification.RequestSpecification;

/**
 * This class will be used for sending Slack Notifications
 * @author mukesh.rajput
 */
public class SlackHelper
{
	public static ArrayList<String> slackThreads = new ArrayList<String>();
	public static String botUserOAuthAccessToken = CommonUtilities.decryptMessage(System.getProperty("slackBotUserOAuthToken").getBytes());
	private static final HttpClient httpClient = HttpClient.newHttpClient();

	public static String triggerSlackNotifications(String slackGroupName, String message, String attachmentFile, String slackChannelId)
	{
		String thread_ts = sendSlackMessage(slackGroupName, message, attachmentFile,slackChannelId);
		
		// Remove duplicates and send thread message
		Set<String> set = new HashSet<>(slackThreads);
		slackThreads.clear();
		slackThreads.addAll(set);
		for (int i = 0; i < set.size(); i++)
			replyOnThread(slackGroupName, "^ " + slackThreads.get(i), thread_ts);
		return thread_ts;
	}
	
	public static String sendSlackMessage(String slackGroupName, String message, String attachmentFile, String... SlackChannelId)
	{
		boolean isMessageSent = false;
		Response response = null;
		String thread_ts = null;
		HashMap<String, String> apiParameters = new HashMap<String, String>();
		apiParameters.put("token", botUserOAuthAccessToken);
		if (attachmentFile == null)
		{
			try
			{
				apiParameters.put("channel", slackGroupName);
				apiParameters.put("text", message);
				apiParameters.put("as_user", "true");
				String apiUrl = "https://slack.com/api/chat.postMessage";
				RequestSpecification reqspec = given().contentType(ContentType.URLENC);
				reqspec = reqspec.formParams(apiParameters);
				response = reqspec.when().post(apiUrl);
				JSONObject jsonObj = new JSONObject(response.asString());
				thread_ts = jsonObj.get("ts").toString();
				if (response.asString().contains("\"ok\":true,\"channel\":\""))
					isMessageSent = true;
			}
			catch (Exception e)
			{
				e.printStackTrace();
			}
		}
		else
		{
			try
			{
				apiParameters.put("channels", slackGroupName);
				apiParameters.put("initial_comment", message);
				String apiUrl = "https://slack.com/api/files.upload";
				RequestSpecification reqspec = given();
				reqspec = reqspec.formParams(apiParameters);
				reqspec = reqspec.multiPart("file", new File(attachmentFile), "multipart/form-data");
				response = reqspec.when().post(apiUrl);

				JSONObject jsonObj = new JSONObject(response.asString());
				JSONArray p = (JSONArray) ((JSONObject) ((JSONObject) ((JSONObject) jsonObj.get("file")).get("shares")).get("public")).get(SlackChannelId[0]);
				thread_ts = ((JSONObject) p.get(0)).get("ts").toString();
				if (response.asString().contains("\"ok\":true,\"file\""))
					isMessageSent = true;
			}
			catch (Exception e)
			{
				e.printStackTrace();
			}
		}
		if (isMessageSent)
			System.out.println("Slack message sent to channel : " + slackGroupName);
		else {
			System.out.println("UNABLE TO SEND SLACK MESSAGE TO : " + slackGroupName);
			if (response != null) {
				System.out.println("Response: " + response.asString());
			}
		}

		return thread_ts;
	}
	
	public static void replyOnThread(String slackGroupName, String message, String thread_ts)
	{
		boolean isMessageSent = false;
		Response response = null;
		HashMap<String, String> apiParameters = new HashMap<String, String>();
		apiParameters.put("token", botUserOAuthAccessToken);
		
		try
		{
			apiParameters.put("channel", slackGroupName);
			apiParameters.put("text", message);
			apiParameters.put("as_user", "true");
			apiParameters.put("thread_ts", thread_ts);
			String apiUrl = "https://slack.com/api/chat.postMessage";
			RequestSpecification reqspec = given().contentType(ContentType.URLENC);
			reqspec = reqspec.formParams(apiParameters);
			response = reqspec.when().post(apiUrl);
			if (response.asString().contains("\"ok\":true,\"channel\":\""))
				isMessageSent = true;
		}
		catch (Exception e)
		{
			e.printStackTrace();
		}
		
		if (isMessageSent)
			System.out.println("Slack thread created for : " + message);
		else {
			System.out.println("Failed to reply on given Thread");
			if (response != null) {
				System.out.println("Response: " + response.asString());
			}
		}
	}
	
	public static void multipleReplyOnThread(String slackGroupName,String slackThreadId, ArrayList<String> messageList)
	{
		
		for (int i=0; i<SlackHelper.slackThreads.size();i++)
		{
			SlackHelper.replyOnThread(slackGroupName, messageList.get(i), slackThreadId);
		}
	}

	 /**
     * Finds and returns the Slack user ID for a given email address.
     *
     * @param email The user's email address
     * @return Slack user ID (e.g., "U1234567890") or null if not found
     * @throws Exception if API call fails or missing token
     */
	 public static String getSlackUserIdByEmail(String email) throws Exception {
        try {
            // Validate email input
            if (email == null || email.trim().isEmpty()) {
                System.err.println("[ERROR] Invalid email provided: " + email);
                throw new IllegalArgumentException("Email parameter is required and must be a non-empty string");
            }

            if (botUserOAuthAccessToken == null || botUserOAuthAccessToken.isEmpty()) {
                System.err.println("[ERROR] SLACK_BOT_TOKEN not found");
                throw new IllegalStateException("SLACK_BOT_TOKEN not found");
            }

            String normalizedEmail = email.trim().toLowerCase();
            System.out.println("[INFO] Looking up Slack user ID for email: " + normalizedEmail);

            String url = "https://slack.com/api/users.lookupByEmail?email="
                    + java.net.URLEncoder.encode(normalizedEmail, "UTF-8");

            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(url))
                    .header("Authorization", "Bearer " + botUserOAuthAccessToken)
                    .GET()
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());
            JSONObject result = new JSONObject(response.body());

            if (!result.getBoolean("ok")) {
                String error = result.optString("error");

                if ("users_not_found".equals(error)) {
                    System.out.println("[WARN] User not found for email: " + normalizedEmail);
                    return null;
                } else if ("missing_scope".equals(error)) {
                    System.err.println("[ERROR] Missing required Slack scope: users:read.email");
                    throw new Exception("Missing required Slack scope: users:read.email. Please add this scope.");
                } else {
                    System.err.println("[ERROR] Slack API error: " + error);
                    throw new Exception("Slack API error: " + error);
                }
            }

            if (result.has("user")) {
                JSONObject userObj = result.getJSONObject("user");
                String userId = userObj.optString("id", null);

                if (userId != null) {
                    System.out.println("[SUCCESS] Slack UserId for email: " + normalizedEmail + " = " + userId);
                    return userId;
                }
            }

            System.out.println("[WARN] User found but no ID returned for email: " + normalizedEmail);
            return null;

        } catch (Exception e) {
            System.err.println("[ERROR] getSlackUserIdByEmail error: " + e.getMessage());
            throw e;
        }
    }
}