package thanos.utils;

import java.io.File;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.apache.commons.io.FileUtils;
import org.apache.commons.lang3.StringUtils;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.select.Elements;
import org.testng.ITestNGListener;
import org.testng.TestNG;

import thanos.utils.EmailHelper.EmailContentType;


/**
 * This class is used to Generate TestNG.xml on runtime and execute it, and after execution it send automation report to passed email ids
 * @author MukeshR
 */

public class TriggerTestNgXmlFile
{
	public static boolean isDebugMode = false;
	public static boolean remoteExecution = true;
	public static boolean isWebServerPresent = false;
	private static String slackThreadIdToReply = "";
	private static String entityName = "QA-Dashboard";
	// Update these with your Slack configuration
	private static String ownerName = "@YourName:@YOUR_SLACK_USER_ID";
	private static String slackChannelName = "#your-channel:YOUR_SLACK_CHANNEL_ID";
	// Update with your results server IP or hostname
	private static String resultsMachineIp = "YOUR_RESULTS_SERVER_IP";
	private static int variableCount = 1;
	static Date startDate = new Date();
	
	static
	{
		java.security.Security.setProperty("networkaddress.cache.ttl", "0");
		java.security.Security.setProperty("networkaddress.cache.negative.ttl", "0");
	}
	
	public static void main(String... args)
	{
		try
		{
			System.out.println("Total params passed = " + args.length);
			String thanosToken = checkIfEmpty("thanosToken", args[0], null);
			System.setProperty("thanosToken", thanosToken);
			String projectName = checkIfEmpty("projectName", args[1], "DataPopulator");
			String sendEmailTo = checkIfEmpty("sendEmailTo", args[2], "your-email@example.com");
			String jobBuildTag = checkIfEmpty("jobBuildTag", args[3], CommonUtilities.generateRandomAlphaNumericString(15));
			String groupNames = checkIfEmpty("groupNames", args[4], "dataPopulator");
			String sendSlackMessage = checkIfEmpty("sendSlackMessage", args[5], "false");
			String branchName = checkIfEmpty("branchName", args[6], "main");
			String debugMode = checkIfEmpty("debugMode", args[7], "false");

			// These are the extra variables being used
			String resultsDirectory = null;
			boolean sendReportOnSlack = Boolean.parseBoolean(sendSlackMessage);
			isDebugMode = Boolean.parseBoolean(debugMode);
			resultsDirectory = decideResultsDirectory(resultsMachineIp, projectName, jobBuildTag);
			
			// Update TestNG.xml on runtime and execute
			loadAndExecuteTestNgFile("TestNgFile.xml", projectName, resultsDirectory, debugMode, groupNames, branchName);
			
			// After execution of testcases, compose email and send
			triggerNotifications(projectName, resultsDirectory, sendEmailTo, sendReportOnSlack);
			
		}
		catch (Exception e)
		{
			e.printStackTrace();
			commentAndStopExecution("Exception occurred in 'GenerateTestNGXmlAndRun.main'");
		}
		// Sometimes this code creates problem in exiting, so exiting forcefully
		System.exit(0);
	}
	
	private static void loadAndExecuteTestNgFile(String filePath, String projectName, String resultsDirectory, String debugMode, String groupNames, String branchName)
	{
		logCommentForDebugging("<----------------- UPDATING TESTNG.XML File -------------------->");
		try
		{
			String testNgXml = FileUtils.readFileToString(new File(filePath), "UTF8");
			testNgXml = testNgXml.replace("{$resultsDirectory}", resultsDirectory);
			testNgXml = testNgXml.replace("{$remoteExecution}", remoteExecution ? "true" : "false");
			testNgXml = testNgXml.replace("{$debugMode}", debugMode);
			testNgXml = testNgXml.replace("{$groupNames}", groupNames);
			testNgXml = testNgXml.replace("{$branchName}", branchName);
			filePath = resultsDirectory + File.separator + "RunTime_TestNG.xml";
			FileUtils.writeStringToFile(new File(filePath), testNgXml, "UTF8");

			TestNG myTestNG = new TestNG();
			List<String> suitefiles=new ArrayList<String>();
			// Set listeners
			List<Class<? extends ITestNGListener>> listnerClasses = new ArrayList<Class<? extends ITestNGListener>>();
			listnerClasses.add(org.testng.reporters.FailedReporter.class);
			listnerClasses.add(org.uncommons.reportng.HTMLReporter.class);
			listnerClasses.add(org.uncommons.reportng.JUnitXMLReporter.class);
			myTestNG.setListenerClasses(listnerClasses);
			myTestNG.setUseDefaultListeners(false);
			
			// Creating the Result Directory where we can save automation results + screenshots + other data
			myTestNG.setOutputDirectory(resultsDirectory);
			
			// Set ReportNG Properties
			System.setProperty("org.uncommons.reportng.title", projectName + " Report");
			System.setProperty("org.uncommons.reportng.escape-output", "false");
						
			suitefiles.add(filePath);
			myTestNG.setTestSuites(suitefiles);
			System.out.println("TestNG.xml for Execution : " + CommonUtilities.convertFilePathToHtmlUrl(filePath));
			logCommentForDebugging("<----------------- EXECUTING TESTNG.XML File -------------------->");
			myTestNG.run();
		}
		catch (Exception e)
		{
			e.printStackTrace();
			commentAndStopExecution("Exception occurred in 'GenerateTestNGXmlAndRun.generateAndRunTestNGXml'");
		}
	}
	
	private static void triggerNotifications(String projectName, String resultsDirectory, String sendEmailTo, boolean sendReportOnSlack)
	{
		String subject = null;
		String passPercentage = null;
		String htmlFolderPath = resultsDirectory + File.separator + "html";
		String resultLink = CommonUtilities.convertFilePathToHtmlUrl(htmlFolderPath) + "/index.html";
		try
		{
			// Modifying Contents of Report
			String reportContents = removeUnexecutedClassesAndFormatReport(isWebServerPresent, htmlFolderPath);
						
			// Composing Subject line for Slack and Email notification
			Matcher matcher = Pattern.compile("class=\"passRate suite\">(\\d+)").matcher(reportContents);
			if (matcher.find()) passPercentage = matcher.group(1);
			subject = passPercentage + "% : " + entityName + " - "+projectName+" Report";
			
			// Print report in console along with URL
			System.out.println("~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~");
			System.out.println(subject);
			ArrayList<ArrayList<String>> data = printReportOnConsole(reportContents);
			System.out.println("Full Report:- " + resultLink);
			System.out.println("=================================================================================");
			
			// Code to compose and send report on Email & Slack
			if (sendReportOnSlack)
			{
				if (Double.parseDouble(passPercentage) <= 95)
				{
					String userId = ownerName.split(":")[1];
					// Choose between user or user group
					userId = userId.startsWith("@") ? "<" + userId + ">" : "<!subteam^" + userId + ">";
					SlackHelper.slackThreads.add(userId + " - I don't feel so good, please analyze and fix me... :pray: ");
				}
				
				String finalSlackMessage = "*" + subject + "*\nTotal Test(s) Passed: " + data.get(data.size() - 1).get(1) + "\nTotal Test(s) Failed: " + data.get(data.size() - 1).get(2) + "\n*Full report* : " + resultLink;	
				if(StringUtils.isEmpty(slackThreadIdToReply))
				{
					reportContents = updateHtmlString(reportContents, getUpdatableDom(slackChannelName.split(":")[1], SlackHelper.triggerSlackNotifications(slackChannelName.split(":")[0], finalSlackMessage, null, slackChannelName.split(":")[1])));
				}
				else
				{
					SlackHelper.slackThreads.add(finalSlackMessage);
					Collections.reverse(SlackHelper.slackThreads);
					SlackHelper.multipleReplyOnThread(slackChannelName.split(":")[0], slackThreadIdToReply, SlackHelper.slackThreads);
					reportContents = updateHtmlString(reportContents, getUpdatableDom(slackChannelName.split(":")[1], slackThreadIdToReply));
				}
			}
			EmailHelper.sendEmail(sendEmailTo, subject, reportContents, EmailContentType.Html, null);
			if (!passPercentage.equalsIgnoreCase("100"))
				System.exit(1);
		}
		catch (Exception e)
		{
			e.printStackTrace();
			commentAndStopExecution("Exception occurred in 'sendEmailAfterExecution'");
		}
	}

	private static String decideResultsDirectory(String resultsMachineIp, String projectName, String jobBuildTag)
	{
		// Correcting file separators for non windows
		if (!Config.osName.startsWith("Window"))
		{
			String userDirectory = System.getProperty("user.dir");
			userDirectory = userDirectory.replace("\\", File.separator);
			userDirectory = userDirectory.replace("/", File.separator);
			System.setProperty("user.dir", userDirectory);
		}
		
		String localDirectory = System.getProperty("user.dir") + File.separator + "test-output";
		String finalDirectory = System.getProperty("user.dir") + File.separator + "test-output";
		if (remoteExecution)
		{
			finalDirectory = File.separator + File.separator + resultsMachineIp + File.separator + "RegressionResults";
			isWebServerPresent = true;
		}
		
		String resultsDirectory = finalDirectory + File.separator + projectName + File.separator + jobBuildTag.replace("jenkins-", "");
		boolean isCreated = CommonUtilities.createFolder(normalizePath(resultsDirectory));
		if (!isCreated)
		{
			System.out.println("Unable to access the Remote machine, so using local directory...");
			resultsDirectory = localDirectory + File.separator + projectName + File.separator + jobBuildTag.replace("jenkins-", "");
			isCreated = CommonUtilities.createFolder(normalizePath(resultsDirectory));
			if (!isCreated)
				commentAndStopExecution("Exiting, as Results Directory is not created !");
		}
		return resultsDirectory;
	}
	
	private static void commentAndStopExecution(String message)
	{
		message = "\033[31m =======>>" + message + "<<======= \033[0m";
		System.out.println("\n" + message + "\n");
		System.exit(1);
	}

	
	private static String checkIfEmpty(String argumentName, String value, String defaultValue)
	{
		String finalValue = null;
		System.out.println("Value of argument[" + variableCount + "]-"+argumentName+" = " + value);
		finalValue = StringUtils.isEmpty(value) || value.equalsIgnoreCase("null") ? defaultValue : value.trim();
		variableCount++;
		return finalValue;
	}
	
	private static void logCommentForDebugging(String message)
	{
		if (isDebugMode)
			System.out.println(message);
	}
	
	@SuppressWarnings("serial")
	public static HashMap<String, String> machines = new HashMap<String, String>()
	{
		{
			put("18.136.85.76", "10.102.140.83");
		}
	};
	
	/**
	 * Update the html value the before HTML report
	 * @param originalString
	 * @param replacableText
	 * @return
	 */
	
	private static String updateHtmlString(String originalString, String replacableText)
	{
		int indexOfbody = originalString.indexOf("</p>");
		String Firsthalf = originalString.substring(0, indexOfbody);
		String LastHalf = originalString.substring(indexOfbody);
		return Firsthalf + replacableText + LastHalf;
	}
	
	/**
	 * Generate dynamic slack link(s)
	 * and convert to HTML body
	 * @param slackChannelId
	 * @param threadTimestamp
	 * @return
	 */
	
	private static String getUpdatableDom(String slackChannelId, String threadTimestamp)
	{
		String permalink = "https://app.slack.com/archives/" + slackChannelId + "/p" + threadTimestamp;
		String updateDom = "<a href=\"" + permalink + "\" style=\"display: inline-block; margin-left: 15px;\" >Link to Slack Thread</a>";
		return updateDom;
	}
	
	private static String normalizePath(String currentFilePath){
		Path normalizedPath = Paths.get(currentFilePath).normalize();
		return String.valueOf(normalizedPath);
	}
	
	private static String removeUnexecutedClassesAndFormatReport(Boolean isWebServerPresent, String htmlFolderPath)
	{
		String htmlLink = CommonUtilities.convertFilePathToHtmlUrl(htmlFolderPath);
		String resultLink = htmlLink + "/index.html";
		String indexFile = htmlFolderPath + File.separator + "index.html";
		String suitesFile = htmlFolderPath + File.separator + "suites.html";
		String overviewFile = htmlFolderPath + File.separator + "overview.html";
		removeUnexecutedClasses(indexFile);
		String strText = removeUnexecutedClasses(overviewFile);
		strText = strText.replaceAll("output.html", resultLink);
		if (isWebServerPresent)
		{
			strText = strText.replaceAll("href=\"suite", "href=\"" + htmlLink + "/suite");
			strText = strText.replaceAll("Log Output", "Full Report");
		}
		else
		{
			strText = strText.replaceAll("href=\"suite", "href=\"#");
			strText = strText.replaceAll("target=\"_blank\"", "");
			strText = strText.replaceAll("Log Output", " ");
		}
		Pattern pattern = Pattern.compile("class=\"passRate suite\">(\\d+)");
		Matcher matcher = pattern.matcher(strText);
		if (matcher.find())
		{
			int passPercentage = Integer.parseInt(matcher.group(1));
			if (passPercentage < 100)
				formatReport(suitesFile);
		}
		else
		{
			commentAndStopExecution("Not able to read percentage from report, so cant compose & send email");
		}
		return strText;
	}
	
	private static void formatReport(String filePath)
	{
		String strFileData = null;
		File file = new File(normalizePath(filePath));
		// Code to change to structure of TestNG Report
		String predefinedFormat = "<!--?xml version=\"1.0\" encoding=\"utf-8\" ?--><!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n" + "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"\" lang=\"\">\n" + " <head> \n" + "  <title>Thanos Test Execution Report</title> \n" + "  <meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\"> \n" + "  <meta name=\"description\" content=\"TestNG unit test results.\"> \n" + "  <link href=\"reportng.css\" rel=\"stylesheet\" type=\"text/css\"> \n" + "  <script type=\"text/javascript\" src=\"reportng.js\"></script>\n" + "  <style type=\"text/css\">\n" + "      .test{\n" + "        font-size:1em;\n" + "        font-family: sans-serif;\n" + "      }\n" + "  </style>\n" + " </head> \n" + " <body style=\"margin-top: 0;\"> \n" + "  <div id=\"sidebarHeader\"> \n" + "   <h2>Thanos Test Execution Report</h2> \n"
				+ "   <p> <a href=\"overview.html\" target=\"main\">Overview</a> · <a href=\"output.html\" target=\"main\">Log Output</a> </p> \n" + "  </div> \n" + "  <table id=\"suites\"> \n" + "   <thead> \n" + "    <tr> \n" + "     <th class=\"header suite\" onclick=\"toggleElement('tests-1', 'table-row-group'); toggle('toggle-1')\" style=\"line-height:1.6em;white-space: nowrap;\"> <span id=\"toggle-1\" class=\"toggle\">&#x25bc;</span>List of testcases executed :</th> \n" + "    </tr> \n" + "   </thead> \n" + "   <tbody id=\"tests-1\" class=\"tests\">\n" + "    <tr>\n" + "        <td>\n" + "            <table id=\"suites\" style=\"padding-left:12px;\"> \n" + "               <thead> \n" + "                <tr> \n"
				+ "                 <th class=\"suite\" onclick=\"toggleElement('tests-failures', 'table-row-group'); toggle('toggle-failures')\" style=\"text-align:left;line-height:1.5em;background-color: #ff8888;\"> <span id=\"toggle-failures\" class=\"toggle\">&#x25bc;</span>Failed Cases </th> \n" + "                </tr> \n" + "               </thead> \n" + "               <tbody id=\"tests-failures\" class=\"tests\">\n" + "                \n" + "               </tbody>\n" + "           </table>\n" + "        </td>\n" + "    </tr>\n" + "    <tr>\n" + "        <td>\n" + "            <table id=\"suites\" style=\"padding-left:12px;\"> \n" + "               <thead> \n" + "                <tr> \n" + "                 <th class=\"suite\" onclick=\"toggleElement('tests-passed', 'table-row-group'); toggle('toggle-passed')\" style=\"text-align:left;line-height:1.5em;background-color: #88ee88;\"> <span id=\"toggle-passed\" class=\"toggle\">&#x25b6;</span>Passed Cases </th> \n"
				+ "                </tr> \n" + "               </thead> \n" + "               <tbody id=\"tests-passed\" class=\"tests\" style=\"display: none;\">\n" + "\n" + "               </tbody>\n" + "           </table>\n" + "        </td>\n" + "    </tr>\n" + "   </tbody> \n" + "  </table>  \n" + " </body>\n" + "</html>";
		try
		{
			strFileData = FileUtils.readFileToString(file, "UTF8");
			Document document1 = Jsoup.parse(strFileData);
			Document document2 = Jsoup.parse(predefinedFormat);
			org.jsoup.nodes.Element testsFailures = document2.getElementById("tests-failures");
			org.jsoup.nodes.Element testsPassed = document2.getElementById("tests-passed");
			for (org.jsoup.nodes.Element element : document1.getElementsByClass("failureIndicator"))
			{
				element = element.parent().parent();
				testsFailures.append(element.html().replace("?", "&#x2718;"));
				element.remove();
			}
			for (org.jsoup.nodes.Element element : document1.getElementsByClass("successIndicator"))
			{
				element = element.parent().parent();
				testsPassed.append(element.html().replace("?", "&#x2714;"));
				element.remove();
			}
			strFileData = document2.toString();
			FileUtils.writeStringToFile(file, strFileData, "UTF8");
		}
		catch (Exception e)
		{
			System.out.println("Exception while changing structure of TestNG report");
			e.printStackTrace();
		}
	}
	
	private static String removeUnexecutedClasses(String filePath)
	{
		String strFileData = null;
		File file = new File(normalizePath(filePath));
		// Code to remove 'N/A' containing rows from TestNG Report
		try
		{
			strFileData = FileUtils.readFileToString(file, "UTF8");
			strFileData = strFileData.replace("<frameset cols=\"20%,*\">", "<frameset cols=\"24%,*\">");
			Document document = Jsoup.parse(strFileData);
			for (org.jsoup.nodes.Element element : document.select("td:eq(5)"))
			{
				String content = element.getElementsMatchingOwnText("N/A").text();
				if (content.equalsIgnoreCase("N/A"))
				{
					element = element.parent();
					element.remove();
				}
			}
			strFileData = document.toString();
			FileUtils.writeStringToFile(file, strFileData, "UTF8");
		}
		catch (Exception e)
		{
			System.out.println("Exception while removing 'N/A' containing rows from report");
			e.printStackTrace();
		}
		return strFileData;
	}
	
	private static ArrayList<ArrayList<String>> printReportOnConsole(String strText)
	{
		Document doc = Jsoup.parse(strText);
		Elements rows = doc.body().getElementsByTag("tr");
		ArrayList<ArrayList<String>> data = new ArrayList<>();
		int rowCount = 0;
		int max = -1;
		for (org.jsoup.nodes.Element row : rows)
		{
			Elements colHead = row.getElementsByTag("th");
			ArrayList<String> rowData = new ArrayList<>();
			int j = 0;
			for (org.jsoup.nodes.Element col : colHead)
			{
				rowData.add(j, col.text());
				if (!StringUtils.isEmpty(rowData.get(j)) && max < rowData.get(j).length())
					max = rowData.get(j).length();
				j++;
			}
			j = 0;
			Elements cols = row.getElementsByTag("td");
			for (org.jsoup.nodes.Element col : cols)
			{
				rowData.add(j, col.text());
				if (!StringUtils.isEmpty(rowData.get(j)) && max < rowData.get(j).length())
					max = rowData.get(j).length();
				j++;
			}
			if (!rowData.get(0).equals("Total"))
			{
				if (rowData.size() > 1)
					rowData.remove(1);
				if (rowData.size() > 2)
					rowData.remove(2);
			}
			else
			{
				rowData.remove(2);
			}
			data.add(rowCount, rowData);
			rowCount++;
		}
		int newMax = max;
		for (int i = 0; i < (max + 42); i++)
			System.out.print("=");
		System.out.println();
		for (int i = 1; i < rowCount; i++)
		{
			System.out.print("| ");
			max = newMax;
			for (int k = 0; k < 4; k++)
			{
				String dataToPrint = "";
				int len = 0;
				try
				{
					dataToPrint = data.get(i).get(k);
					System.out.print(dataToPrint);
					len = dataToPrint.length();
				}
				catch (Exception e)
				{
					System.out.print("");
				}
				for (int j = 0; j < max - len; j++)
				{
					System.out.print(" ");
				}
				System.out.print(" | ");
				max = 10;
			}
			System.out.println();
		}
		for (int i = 0; i < (newMax + 42); i++)
			System.out.print("=");
		System.out.println();
		return data;
	}
	
}
