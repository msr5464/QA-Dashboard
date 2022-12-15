package thanos.utils;

import org.testng.Assert;
import org.testng.Reporter;

/**
 * This class is used to add logs in the every test step execution or every function
 * @author MukeshR
 */
class Log
{
	
	public static void CommentJson(Config testConfig, String message, String color)
	{
		logToStandard(testConfig, message);
		message = message.replaceAll("\n", "</br>").replaceAll(" ", "&nbsp");
		message = "<font color='" + color + "'>" + message + "</font></br>";
		logInReporter(message);
		testConfig.testLog = testConfig.testLog.concat(message);
	}
	
	public static void Comment(Config testConfig, String message, String color)
	{
		logToStandard(testConfig, message);
		message = "<font color='" + color + "'>" + message + "</font></br>";
		logInReporter(message);
		testConfig.testLog = testConfig.testLog.concat(message);
	}
	
	public static void Comment(Config testConfig, String message)
	{
		Comment(testConfig, message, "Black");
	}
	
	public static void Fail(Config testConfig, String message)
	{
		failure(testConfig, message);
	}
	
	public static void failure(Config testConfig, String message)
	{
		String tempMessage = message;
		testConfig.softAssert.fail(message);
		logToStandard(testConfig, message);
		message = "<font color='Red'>" + message + "</font></br>";
		logInReporter(message);
		testConfig.testLog = testConfig.testLog.concat(message);
		// Stop the execution if end execution flag is ON
		if (testConfig.endExecutionOnfailure)
			Assert.fail("==>" + tempMessage);
	}
	
	private static void logToStandard(Config testConfig, String message)
	{
		System.out.println(message);
	}
	
	public static void logInReporter(String message)
	{
		Reporter.log(message);
	}
	
	public static void Pass(Config testConfig, String message)
	{
		logToStandard(testConfig, message);
		message = "<font color='Green'>" + message + "</font></br>";
		logInReporter(message);
		testConfig.testLog = testConfig.testLog.concat(message);
	}
	
	public static void Warning(Config testConfig, String message)
	{
		logToStandard(testConfig, message);
		message = "<font color='Orange'>" + message + "</font></br>";
		logInReporter(message);
		testConfig.testLog = testConfig.testLog.concat(message);
	}
	
	public static void Warning(Config testConfig, String message, boolean takeScreenshot)
	{
		Warning(testConfig, message);
	}
	
}